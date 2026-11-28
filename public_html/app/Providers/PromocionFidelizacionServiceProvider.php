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
            // Caducidad de puntos (S3): sendPointShikkouMail / sendPointShikkouAlertMail a la hora de admin_master
            // (tb_office.start_work_time si falta) y sendPointShikkouAlertMailMultiple a la alert_time de cada aviso
            $points = fn () => $this->app->make(\App\Services\Promotions\PointExpiryMailer::class);
            $schedule->command(\App\Console\Commands\Promotions\PointShikkouAlertCommand::class)->everyMinute()
                ->when(fn () => $points()->isDue(\App\Services\Promotions\PointExpiryMailer::ALERT, now()))
                ->withoutOverlapping();
            $schedule->command(\App\Console\Commands\Promotions\PointShikkouAlertCommand::class, ['--due'])->everyMinute()
                ->when(fn () => $points()->dueAlertCds(now()) !== [])
                ->withoutOverlapping();
            $schedule->command(\App\Console\Commands\Promotions\PointShikkouCommand::class)->everyMinute()
                ->when(fn () => $points()->isDue(\App\Services\Promotions\PointExpiryMailer::SHIKKOU, now()))
                ->withoutOverlapping();
            // fin F4

            // F5
            // Sellos: el legacy (cSendSchedule) ponía en el crontab `min hora * * *` con la hora de admin_master
            // (stamp_shikkou_alert_time / stamp_shikkou_time, 12:00 si falta), solo con スタンプ管理 activo y
            // stamp_shikkou_sousa = '1'. Aquí se evalúa cada minuto y la condición se lee al correr schedule:run.
            foreach ([
                \App\Services\Promotions\StampExpiryMailer::ALERT => \App\Console\Commands\Promotions\StampShikkouAlertCommand::class,
                \App\Services\Promotions\StampExpiryMailer::SHIKKOU => \App\Console\Commands\Promotions\StampShikkouCommand::class,
            ] as $stampKind => $stampCommand) {
                $schedule->command($stampCommand)->everyMinute()
                    ->when(fn () => $this->app->make(\App\Services\Promotions\StampExpiryMailer::class)->isDue($stampKind, now()))
                    ->withoutOverlapping();
            }
            // fin F5
        });
    }
}
