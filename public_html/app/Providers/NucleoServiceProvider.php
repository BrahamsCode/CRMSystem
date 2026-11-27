<?php

namespace App\Providers;

use App\Auth\LegacyStaffUserProvider;
use App\Models\Staff;
use App\Services\Nucleo\Kengen;
use App\Services\Nucleo\LegacyUi;
use App\Services\Nucleo\StaffLogin;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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
        Auth::provider('legacy_staff', fn ($app, array $config) => new LegacyStaffUserProvider($app['hash'], $config['model']));

        RateLimiter::for('staff-login', fn (Request $request) => Limit::perMinute(20)->by((string) $request->input('admin_id') . '|' . $request->ip()));

        $this->defineGates();
    }

    private function defineGates(): void
    {
        // master (login_id = 'master') puede todo, salvo las banderas nucleo.* (soporte, IP), igual que el legacy
        Gate::before(function ($user, string $ability) {
            if (str_starts_with($ability, 'nucleo.')) {
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
