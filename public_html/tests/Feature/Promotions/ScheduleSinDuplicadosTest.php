<?php

use Illuminate\Console\Scheduling\Schedule;

/*
 * Cada comando de promocion se programa una sola vez: dos providers (PromocionServiceProvider::TASKS y
 * PromocionFidelizacionServiceProvider) registrando el mismo comando enviarían dos veces los avisos.
 */
it('no programa dos veces el mismo comando de promocion', function () {
    $commands = collect(app(Schedule::class)->events())
        // comando con sus argumentos (point-shikkou-alert va dos veces a propósito: sin opciones y con --due)
        ->map(fn ($event) => preg_match('/(promocion:.*)$/', (string) $event->command, $m) ? trim($m[1]) : null)
        ->filter();

    expect($commands)->not->toBeEmpty()
        ->and($commands->duplicates()->values()->all())->toBe([]);
});

it('programa los avisos de caducidad de sellos solo con la condición del crontab legacy (F5)', function () {
    $events = collect(app(Schedule::class)->events())
        ->filter(fn ($event) => preg_match('/promocion:stamp-shikkou(\s|$|\')/', (string) $event->command));

    expect($events)->toHaveCount(1)
        // sin スタンプ管理 activo (BD de prueba vacía) la condición no deja correrlo
        ->and($events->first()->filtersPass(app()))->toBeFalse();
});
