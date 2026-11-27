<?php

use App\Services\Operaciones\Working\WorkTimeService;
use Carbon\CarbonImmutable;

// Reglas de WorkTimeService (勤怠一覧): corte de jornada a las 5:00 y 労働時間 de cada variante

$at = fn (int $h, int $m, int $d = 10) => CarbonImmutable::create(2026, 2, $d, $h, $m, 0, 'Asia/Tokyo');
$rules = ['start_hour' => 9, 'start_minute' => 0, 'break' => 60.0, 'marume' => 15.0];

it('resolveWorkDate: una marca antes de las 5:00 es del día siguiente (+24 h)', function () use ($at) {
    $service = new WorkTimeService;
    $day = $at(0, 0);

    expect($service->resolveWorkDate($day, 4, 59)->equalTo($at(4, 59, 11)))->toBeTrue()
        ->and($service->resolveWorkDate($day, 0, 0)->equalTo($at(0, 0, 11)))->toBeTrue()
        ->and($service->resolveWorkDate($day, 5, 0)->equalTo($at(5, 0)))->toBeTrue()
        ->and($service->resolveWorkDate($day, 23, 30)->equalTo($at(23, 30)))->toBeTrue()
        // fin de mes: pasa al mes siguiente sin el mktime de 1970
        ->and($service->resolveWorkDate($at(0, 0, 28), 3, 0)->format('Y-m-d H:i'))->toBe('2026-03-01 03:00');
});

it('parseDate: solo fechas Y/n/j válidas', function () {
    $service = new WorkTimeService;

    expect($service->parseDate('2026/2/10')?->format('Y-m-d'))->toBe('2026-02-10')
        ->and($service->parseDate('2026/2/30'))->toBeNull()
        ->and($service->parseDate('2026/2/1 OR 1=1'))->toBeNull()
        ->and($service->parseDate(null))->toBeNull();
});

it('calculate (work_list): redondeo de entrada y salida con el fin de jornada + descanso', function () use ($at, $rules) {
    $service = new WorkTimeService;
    $now = $at(20, 0);
    $calc = fn ($flg, $kubun, $in, $out) => $service->calculate(WorkTimeService::VARIANT_DAY, $rules, $at(0, 0), $in, $out, $flg, $kubun, $now);

    // 遅刻 9:07 → 9:15; 残業 19:20 → 19:15 (fin 18:00)
    $r = $calc('遅刻', '残業', $at(9, 7), $at(19, 20));
    expect($r['start']->format('H:i'))->toBe('09:15')->and($r['end']->format('H:i'))->toBe('19:15')->and($r['sum'])->toBe(10.0);

    // 早出 8:40 → 8:45; 残業 con 早出: floor → 19:15
    $r = $calc('早出', '残業', $at(8, 40), $at(19, 29));
    expect($r['start']->format('H:i'))->toBe('08:45')->and($r['end']->format('H:i'))->toBe('19:15')->and($r['sum'])->toBe(10.5);

    // 出勤 + 早退 16:50 → 16:45
    $r = $calc('出勤', '早退', $at(8, 55), $at(16, 50));
    expect($r['start']->format('H:i'))->toBe('09:00')->and($r['end']->format('H:i'))->toBe('16:45')->and($r['sum'])->toBe(7.75);

    // 出勤 sin 区分: hasta ahora
    expect($calc('出勤', '', $at(9, 0), null)['sum'])->toBe(11.0);

    // Sin 出勤状況: nada
    expect($calc('', '退社', $at(9, 0), $at(18, 0)))->toBe(['start' => null, 'end' => null, 'sum' => null]);
});

it('calculate (work_list2 y ng): fin = inicio + 8 h y salida real', function () use ($at, $rules) {
    $service = new WorkTimeService;
    $now = $at(20, 0);

    $r = $service->calculate(WorkTimeService::VARIANT_DAY2, $rules, $at(0, 0), $at(8, 40), $at(18, 5), '早出', '退社', $now);
    expect($r['sum'])->toBe(8.25)->and($r['end'])->toBeNull();

    $r = $service->calculate(WorkTimeService::VARIANT_DAY2, $rules, $at(0, 0), $at(9, 7), $at(19, 15), '遅刻', '残業', $now);
    expect($r['sum'])->toBe(10.0);

    // work_list2 con 遅刻 + 早退: el legacy restaba una variable sin definir → vacío
    expect($service->calculate(WorkTimeService::VARIANT_DAY2, $rules, $at(0, 0), $at(9, 7), $at(15, 0), '遅刻', '早退', $now)['sum'])->toBeNull();

    // work_list2 con 出勤 + 残業: como work_list pero fin 17:00
    $r = $service->calculate(WorkTimeService::VARIANT_DAY2, $rules, $at(0, 0), $at(9, 0), $at(18, 0), '出勤', '残業', $now);
    expect($r['end']->format('H:i'))->toBe('18:00')->and($r['sum'])->toBe(9.0);

    $r = $service->calculate(WorkTimeService::VARIANT_NG, $rules, $at(0, 0), $at(9, 7), $at(15, 0), '遅刻', '早退', $now);
    expect($r['sum'])->toBe(5.75);
});
