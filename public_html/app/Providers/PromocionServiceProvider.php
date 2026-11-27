<?php

namespace App\Providers;

use App\Models\AdminMaster;
use App\Models\ContentsMaster;
use App\Models\Staff;
use App\Services\Customers\VisitLoyalty;
use App\Services\Nucleo\LegacyUi;
use App\Services\Promotions\GApiPushTrigger;
use App\Services\Promotions\NullPosGateway;
use App\Services\Promotions\PosGateway;
use App\Services\Promotions\PromocionVisitLoyalty;
use App\Services\Promotions\PushTrigger;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * Módulo 販売促進管理 (promocion).
 *
 * Reemplaza el crontab que el legacy reescribía desde PHP (system/category3/class/cSendSchedule.php):
 * todas las tareas del módulo corren con un único `schedule:run`. bootstrap/providers.php lo carga solo.
 */
class PromocionServiceProvider extends ServiceProvider
{
    /**
     * Tareas: clase del comando => frecuencia (método de Illuminate\Console\Scheduling\Event).
     * Se registran las que ya existen; el resto llega con su submódulo (§3 del diseño):
     *   promocion:send-mail (M2), promocion:send-follow-push (M5), promocion:point-* (F4),
     *   promocion:stamp-* (F5), promocion:clean-temp (M10).
     */
    public const TASKS = [
        'App\\Console\\Commands\\Promotions\\SendMailCommand' => 'everyMinute',
        'App\\Console\\Commands\\Promotions\\SendFollowPushCommand' => 'everyFiveMinutes',
        'App\\Console\\Commands\\Promotions\\RegisterPointCommand' => 'everyFiveMinutes',
        'App\\Console\\Commands\\Promotions\\PointShikkouCommand' => 'everyMinute',
        'App\\Console\\Commands\\Promotions\\StampShikkouCommand' => 'everyMinute',
        'App\\Console\\Commands\\Promotions\\PaidMemberPointCommand' => 'everyMinute',
        'App\\Console\\Commands\\Promotions\\CleanTempCommand' => 'daily',
    ];

    public function register(): void
    {
        // POS fuera de alcance (CONVENCIONES §3 y §4)
        $this->app->bind(PosGateway::class, NullPosGateway::class);

        // Aviso al servidor de push de la app (g-api), mismo contrato que Push.php::exec
        // (con el interruptor común GApi: fuera de producción solo se anota en el log)
        $this->app->bind(PushTrigger::class, GApiPushTrigger::class);

        // Sellos y puntos de la visita para VisitService de S2 (S2 enlaza la nula con bindIf)
        $this->app->bind(VisitLoyalty::class, PromocionVisitLoyalty::class);
    }

    public function boot(): void
    {
        $this->defineMenuGates();

        // `<title>… - <site_name></title>` del legacy (inc/head.php: tb_office)
        View::composer('admin.promocion.*', fn ($view) => $view->with('siteName', $view->getData()['siteName'] ?? app(LegacyUi::class)->siteName()));

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            foreach (self::TASKS as $command => $frequency) {
                if (class_exists($command)) {
                    $schedule->command($command)->{$frequency}()->withoutOverlapping();
                }
            }
        });
    }

    /**
     * Condiciones del menú lateral (left_navi3.php), sin el pase de master (NucleoServiceProvider::isCondition).
     */
    private function defineMenuGates(): void
    {
        $admin = fn (string $col) => (string) AdminMaster::current()?->{$col} === '1';
        $extShop = fn () => ContentsMaster::text('mycolle', 'app_layout_mode') === '1';

        Gate::define('promocion.app.enabled', fn ($staff = null) => $admin('application_use_flg'));
        Gate::define('promocion.beacon.enabled', fn ($staff = null) => $admin('beacon_use_flg'));
        Gate::define('promocion.smatopi.enabled', fn ($staff = null) => $admin('smatopi_flg'));
        // スマトピ連携: espera a Antony (F8). Hasta que decida se oculta el bloque (sus pantallas no están migradas)
        Gate::define('promocion.smatopi-f8.enabled', fn ($staff = null) => false);
        Gate::define('promocion.ext-shop.enabled', fn ($staff = null) => $extShop());
        Gate::define('promocion.app-ext-shop.enabled', fn ($staff = null) => $admin('application_use_flg') && $extShop());
        // ポイントルール一覧: con ビーコン y (master o kengen 1037-3)
        Gate::define('promocion.point-rules.enabled', fn (?Staff $staff = null) => $admin('beacon_use_flg')
            && $staff !== null && ($staff->isMaster() || Gate::forUser($staff)->allows('kengen.1037.3')));
        // リマインダ: fuera del bucle de hijos; sale si el sistema 9 u 11 (予約) está activo
        Gate::define('promocion.reminder.enabled', fn ($staff = null) => DB::table('tb_base_system')->where('system_flg', '1')->whereIn('cd', [9, 11])->exists());
    }
}
