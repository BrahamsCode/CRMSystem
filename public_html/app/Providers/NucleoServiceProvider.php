<?php

namespace App\Providers;

use App\Auth\LegacyStaffUserProvider;
use App\Models\Staff;
use App\Services\Nucleo\Kengen;
use App\Services\Nucleo\LegacyUi;
use App\Services\Nucleo\StaffLogin;
use App\Services\Nucleo\TempUploads;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * Núcleo: login del personal (guard `admin` sobre tb_staff), permisos (cKengen → Gates) y la UI legacy.
 * Contrato para los módulos: Doc/contratos/NUCLEO.md.
 */
class NucleoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(Kengen::class);
        $this->app->scoped(LegacyUi::class);
    }

    public function boot(): void
    {
        // login.php borraba en cada acceso los temporales de system/uploads de más de un día (find -mmin +1440)
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->call(fn () => $this->app->make(TempUploads::class)->purge(1440))
                ->name('nucleo:limpiar-subidas')
                ->hourly()
                ->withoutOverlapping();
        });

        self::trustProxies((string) config('crm.trusted_proxies', ''));

        Auth::provider('legacy_staff', fn ($app, array $config) => new LegacyStaffUserProvider($app['hash'], $config['model']));

        RateLimiter::for('staff-login', fn (Request $request) => Limit::perMinute(20)->by((string) $request->input('admin_id') . '|' . $request->ip()));

        $this->defineGates();

        // $siteName en todas las vistas ($site_name de inc/db.php: tb_office cd=1, hp_hyouji_name o kaishamei)
        View::composer('*', function ($view) {
            if (! array_key_exists('siteName', $view->getData())) {
                $view->with('siteName', self::siteName());
            }
        });
    }

    /**
     * Proxies de confianza para el middleware TrustProxies (global en Laravel): `crm.trusted_proxies`
     * ('*' o lista con comas). Vacío no cambia nada: request()->ip() es REMOTE_ADDR, como el legacy.
     */
    public static function trustProxies(string $value): void
    {
        $value = trim($value);
        if ($value === '') {
            return;
        }

        TrustProxies::at($value === '*' ? '*' : array_values(array_filter(array_map('trim', explode(',', $value)))));
    }

    private static function siteName(): string
    {
        try {
            return app(LegacyUi::class)->siteName();
        } catch (\Throwable) {
            return '';
        }
    }

    private function defineGates(): void
    {
        // master (login_id = 'master') pasa todos los PERMISOS, pero no las CONDICIONES, igual que el legacy:
        // banderas nucleo.* (soporte, IP) y Gates `<modulo>.<algo>.enabled` (módulo o función activa:
        // app encendida, UI CRM2…). Una condición que además pida permiso lo consulta dentro (kengen.*).
        Gate::before(function ($user, string $ability) {
            if (self::isCondition($ability)) {
                return null;
            }
            if ($user instanceof Staff && $user->isMaster()) {
                return true;
            }
            if ($user instanceof Staff && preg_match('/^kengen\.(\d+)\.([1-4])$/', $ability, $m)) {
                return app(Kengen::class)->allows($user, (int) $m[1], (int) $m[2]);
            }

            return null;
        });

        foreach (config('kengen.gates', []) as $name => [$cd, $function]) {
            self::defineKengenGate($name, $cd, $function);
        }

        // Gate::allows('kengen', [1001, 2]) o con varios pares (basta uno): Gate::allows('kengen', [[1001, 2], [1033, 2]])
        Gate::define('kengen', function (Staff $staff, ...$args) {
            $pairs = is_array($args[0] ?? null) ? $args : [$args];

            return collect($pairs)->contains(fn ($pair) => count($pair) === 2
                && app(Kengen::class)->allows($staff, (int) $pair[0], (int) $pair[1]));
        });

        Gate::define('nucleo.master', fn (Staff $staff) => $staff->isMaster());
        Gate::define('nucleo.developer', fn (Staff $staff) => self::isDeveloper());
        Gate::define('nucleo.support', fn (Staff $staff) => (bool) self::flag('support_login'));
        Gate::define('nucleo.support-master', fn (Staff $staff) => (bool) self::flag('support_master'));
        // Menús de mantenimiento: (IP de desarrollo o soporte) y no entrar como Relation (left_navi0/1)
        Gate::define('nucleo.maintenance', fn (Staff $staff) => (self::isDeveloper() || self::flag('support_login')) && ! self::flag('relation_login'));
        // 店舗新規登録: IP de desarrollo o support_master (left_navi0)
        Gate::define('nucleo.shop-create', fn (Staff $staff) => self::isDeveloper() || self::flag('support_master'));
    }

    /** ¿Es una condición (master no la pasa por ser master)? `nucleo.*` o `*.enabled` */
    public static function isCondition(string $ability): bool
    {
        return str_starts_with($ability, 'nucleo.') || str_ends_with($ability, '.enabled');
    }

    /** Define un Gate `<modulo>.<acción>` sobre la matriz cKengen (lo pueden usar los ServiceProvider de los módulos) */
    public static function defineKengenGate(string $name, int $contentsCd, int $function): void
    {
        Gate::define($name, fn (Staff $staff) => app(Kengen::class)->allows($staff, $contentsCd, $function));
    }

    /** Banderas de sesión del login legacy: support_login, support_master, relation_login */
    public static function flag(string $key): mixed
    {
        return session(StaffLogin::SESSION_FLAGS . '.' . $key);
    }

    /** Common::isDevelopmentAuthorityFromIP: STAGE=docker o IP en CRM_DEVELOPER_IPS */
    public static function isDeveloper(): bool
    {
        return config('crm.stage') === 'docker'
            || in_array(request()->ip(), config('crm.developer_ips', []), true);
    }
}
