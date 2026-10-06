<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// --- Módulo de promociones (necesita `php artisan schedule:work` o el cron de schedule:run) ---
Schedule::command('promotions:send-scheduled')->everyMinute()->withoutOverlapping();
Schedule::command('promotions:run-rules')->everyMinute()->withoutOverlapping();
Schedule::command('promotions:loyalty')->everyFifteenMinutes()->withoutOverlapping();
