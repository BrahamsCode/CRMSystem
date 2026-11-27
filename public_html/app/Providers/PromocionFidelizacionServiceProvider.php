<?php

namespace App\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

/**
 * Fidelización de `promocion` hecha por S2 (F4 puntos, F5 sellos): tareas programadas de sus comandos.
 * Separado de PromocionServiceProvider (S3) para no tocar archivos de otra sesión.
 */
class PromocionFidelizacionServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            // F4
            // Horario del crontab de cSendSchedule; la condición se evalúa en cada schedule:run (el legacy rehacía el crontab al guardar)
            $schedule->command(\App\Console\Commands\Promotions\GrantRegisterPointsCommand::class)
                ->everyFiveMinutes()
                ->when(fn () => \App\Console\Commands\Promotions\GrantRegisterPointsCommand::shouldSchedule())
                ->withoutOverlapping();
            $schedule->command(\App\Console\Commands\Promotions\GrantPaidMemberPointsCommand::class)
                ->dailyAt('10:00')
                ->timezone(\App\Services\Promotions\LegacyTime::ZONE)
                ->when(fn () => \App\Console\Commands\Promotions\GrantPaidMemberPointsCommand::shouldSchedule())
                ->withoutOverlapping();
            // fin F4

            // F5

            // fin F5
        });
    }
}
