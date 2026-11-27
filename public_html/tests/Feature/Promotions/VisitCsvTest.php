<?php

use App\Services\Promotions\VisitStatsService;

require_once __DIR__.'/../Terminales/helpers.php';
require_once __DIR__.'/visit_helpers.php';

/* F7 来店履歴管理: CSV de visitas (csv_download), por tienda (storecsv_download) y por día (daycsv_download), en SJIS-win */

beforeEach(function () {
    s4Login();
    seedVisits();
});

function sjisCsv($response): string
{
    $csv = $response->streamedContent();
    expect(mb_check_encoding($csv, 'SJIS-win'))->toBeTrue();

    return mb_convert_encoding($csv, 'UTF-8', 'SJIS-win');
}

it('CSV出力する: visitas del rango con título y celdas con tabulador, en Shift_JIS', function () {
    $begin = VisitStatsService::at(2026, 11, 27, 5);
    $response = $this->post(route('admin.promocion.visit.csv'), [
        'start_time' => $begin, 'end_time' => $begin + 86399, 'selected_day' => $begin, 'kikan' => 0, 'shop_cd' => 1,
    ])->assertOk()->assertHeader('Content-Disposition', 'attachment; filename=user.csv');

    expect(sjisCsv($response))->toBe(
        "\"本店\"\r\n"
        ."\"2026年11月27日来店\"\r\n"
        ."\"\tユーザID\",\"\t来店日時\",\"\t年齢\",\"\t性別\",\"\t職業\"\r\n"
        ."\"\tK102\",\"\t2026/11/28 03:00\",\"\t22\",\"\t女性\",\"\t学生\"\r\n"
        ."\"\tK102\",\"\t2026/11/27 13:00\",\"\t22\",\"\t女性\",\"\t学生\"\r\n"
        ."\"\tK101\",\"\t2026/11/27 09:40\",\"\t35\",\"\t男性\",\"\t会社員\"\r\n"
        ."\"\tK101\",\"\t2026/11/27 09:10\",\"\t35\",\"\t男性\",\"\t会社員\"\r\n"
    );
});

it('CSV出力する: solo master (el botón ya estaba oculto para el resto)', function () {
    loginShopStaff();

    $this->post(route('admin.promocion.visit.csv'), ['start_time' => 0, 'end_time' => 1, 'kikan' => 0, 'shop_cd' => 1])->assertForbidden();
});

it('store.csv: tiendas × días del mes con subtotales y total', function () {
    $response = $this->post(route('admin.promocion.visit.store-csv'), ['kikan' => 1, 'date' => VisitStatsService::at(2026, 11, 3), 'act' => 'csv_download'])
        ->assertOk()->assertHeader('Content-Disposition', 'attachment; filename=store.csv');

    $lines = explode("\r\n", sjisCsv($response));
    $days = implode(',', array_map(fn ($d) => $d.'日', range(1, 30)));
    $zeros = fn (int $n) => implode(',', array_fill(0, $n, 0));

    expect($lines[0])->toBe(",$days,小計")
        ->and($lines[1])->toBe('本店,'.$zeros(26).',3,1,'.$zeros(2).',4')
        ->and($lines[2])->toBe('支店,'.$zeros(26).',1,'.$zeros(3).',1')
        ->and($lines[3])->toBe('合計,'.$zeros(26).',4,1,'.$zeros(2).',5')
        ->and($lines[4])->toBe('');
});

it('store.csv: 今日 por horas y 今年 por meses', function () {
    $lines = explode("\r\n", sjisCsv($this->post(route('admin.promocion.visit.store-csv'), ['kikan' => 0])->assertOk()));
    expect($lines[0])->toBe(','.implode(',', array_map(fn ($h) => $h.'時', range(0, 23))).',小計')
        ->and($lines[1])->toBe('本店,0,0,0,0,0,0,0,0,0,2,0,0,0,1,0,0,0,0,0,0,0,0,0,0,3');

    $lines = explode("\r\n", sjisCsv($this->post(route('admin.promocion.visit.store-csv'), ['kikan' => 2])->assertOk()));
    expect($lines[1])->toBe('本店,0,0,0,0,0,0,0,0,0,1,4,0,5')
        ->and($lines[3])->toBe('合計,0,0,0,0,0,0,0,0,0,1,5,0,6');
});

it('day.csv: la tabla de 日別 (horas desde las 05:00) de la tienda', function () {
    $response = $this->post(route('admin.promocion.visit.day-csv'), ['shop_cd' => 1, 'kikan' => 0, 'date' => VisitStatsService::at(2026, 11, 27, 5), 'act' => 'csv_download'])
        ->assertOk()->assertHeader('Content-Disposition', 'attachment; filename=day.csv');

    $lines = explode("\r\n", sjisCsv($response));
    expect($lines[0])->toBe(',5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,0,1,2,3,4,合計')
        ->and($lines[1])->toBe('2026/11/27,0,0,0,0,2,0,0,0,1,0,0,0,0,0,0,0,0,0,0,0,0,0,1,0,4')
        ->and($lines[2])->toBe('');

    $lines = explode("\r\n", sjisCsv($this->post(route('admin.promocion.visit.day-csv'), ['shop_cd' => 1, 'kikan' => 2])->assertOk()));
    expect($lines[0])->toStartWith(',1日,2日')->toEndWith('31日,小計')
        ->and($lines[2])->toBe('2月,'.implode(',', array_fill(0, 28, 0)).',,,,0')
        ->and($lines[11])->toBe('11月,'.implode(',', array_fill(0, 26, 0)).',4,0,0,0,,4')
        ->and($lines[13])->toStartWith('合計,')->toEndWith(',5');

    $this->post(route('admin.promocion.visit.day-csv'), ['kikan' => 0])->assertNotFound();
});
