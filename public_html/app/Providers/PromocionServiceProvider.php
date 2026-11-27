<?php

namespace App\Providers;

use App\Services\Customers\VisitLoyalty;
use App\Services\Promotions\NullPosGateway;
use App\Services\Promotions\PosGateway;
use App\Services\Promotions\PromocionVisitLoyalty;
use Illuminate\Console\Scheduling\Schedule;
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
     *   promocion:send-mail (M2), promocion:send-follow-push (M5), promocion:point-* y
     *   promocion:customer-rank (F4), promocion:stamp-* (F5), promocion:clean-temp (M10).
     */
    public const TASKS = [
        'App\\Console\\Commands\\Promotions\\SendMailCommand' => 'everyMinute',
        'App\\Console\\Commands\\Promotions\\SendFollowPushCommand' => 'everyFiveMinutes',
        'App\\Console\\Commands\\Promotions\\RegisterPointCommand' => 'everyFiveMinutes',
        'App\\Console\\Commands\\Promotions\\PointShikkouCommand' => 'everyMinute',
        'App\\Console\\Commands\\Promotions\\StampShikkouCommand' => 'everyMinute',
        'App\\Console\\Commands\\Promotions\\CustomerRankCommand' => 'everyMinute',
        'App\\Console\\Commands\\Promotions\\PaidMemberPointCommand' => 'everyMinute',
        'App\\Console\\Commands\\Promotions\\CleanTempCommand' => 'daily',
    ];

    public function register(): void
    {
        // POS fuera de alcance (CONVENCIONES §3 y §4)
        $this->app->bind(PosGateway::class, NullPosGateway::class);

        // Sellos y puntos de la visita para VisitService de S2 (S2 enlaza la nula con bindIf)
        $this->app->bind(VisitLoyalty::class, PromocionVisitLoyalty::class);
    }

    public function boot(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            foreach (self::TASKS as $command => $frequency) {
                if (class_exists($command)) {
                    $schedule->command($command)->{$frequency}()->withoutOverlapping();
                }
            }
        });
    }
}
