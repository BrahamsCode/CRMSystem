<?php

use App\Models\Operaciones\ShopWork;
use App\Models\Operaciones\StaffShift;
use App\Models\Operaciones\WorkTime;
use App\Services\Operaciones\LegacyMonth;

// El mes como lo guarda el legacy, sin mktime() ni 1970

it('syukkinbi epoch es el día 1 a las 0:00 en Japón', function () {
    // 2026-02-01 00:00 JST = 2026-01-31 15:00 UTC
    expect(LegacyMonth::of(2026, 2)->epoch())->toBe('1769871600')
        ->and(LegacyMonth::fromEpoch('1769871600')->slash())->toBe('2026/2');
});

it('syukkinbi de tb_shop_work va sin cero a la izquierda', function () {
    expect(LegacyMonth::of(2026, 1)->slash())->toBe('2026/1')
        ->and(LegacyMonth::of(2026, 12)->slash())->toBe('2026/12')
        ->and(LegacyMonth::fromSlash('2026/03')->slash())->toBe('2026/3');
});

it('先月 y 翌月 cruzan el año sin saltar a 1970', function () {
    expect(LegacyMonth::of(2026, 1)->previous()->slash())->toBe('2025/12')
        ->and(LegacyMonth::of(2025, 12)->next()->slash())->toBe('2026/1')
        ->and(LegacyMonth::of(2026, 3)->previous()->daysInMonth())->toBe(28);
});

it('rechaza meses y días que no existen', function (callable $call) {
    $call();
})->throws(InvalidArgumentException::class)->with([
    'mes 13' => [fn () => LegacyMonth::of(2026, 13)],
    'año 1970' => [fn () => LegacyMonth::of(1970, 1)],
    'texto' => [fn () => LegacyMonth::fromSlash('2026-1')],
    '30 de febrero' => [fn () => LegacyMonth::of(2026, 2)->day(30)],
]);

it('solo arma nombres de columna por día de una lista blanca', function () {
    expect(WorkTime::dayColumn('syukkin_time', 31))->toBe('syukkin_time_31')
        ->and(StaffShift::dayColumn('komment', 1))->toBe('komment_1')
        ->and(ShopWork::dayColumn('syukkin_time', 15))->toBe('syukkin_time_15');
});

it('no deja inyectar en el nombre de la columna (change_syukkin2.php)', function (string $prefix, int $day) {
    WorkTime::dayColumn($prefix, $day);
})->throws(InvalidArgumentException::class)->with([
    ['syukkin_time', 0],
    ['syukkin_time', 32],
    ["syukkin_time_1 = '1', taikin_time", 1],
    ['taikin_time', -1],
]);

it('parte las listas <> del legacy', function () {
    expect(WorkTime::splitLegacyList('100<>200<>'))->toBe(['100', '200'])
        ->and(WorkTime::splitLegacyList('100<><>300'))->toBe(['100', '', '300'])
        ->and(WorkTime::splitLegacyList(''))->toBe([])
        ->and(WorkTime::splitLegacyList(null))->toBe([]);
});
