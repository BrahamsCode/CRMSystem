<?php

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

/*
 * M4: クリックカウンタ por familia (mailmag_graph2.php) y su CSV (inc/csv_download.php).
 */
beforeEach(function () {
    if (! View::exists('layouts.legacy')) {
        $dir = storage_path('framework/testing/promocion-views/layouts');
        File::ensureDirectoryExists($dir);
        $stub = "<html><head><title>@yield('title')</title>@yield('css')@yield('js')</head><body>@yield('content')</body></html>";
        File::put("$dir/legacy.blade.php", $stub);
        File::put("$dir/legacy-popup.blade.php", $stub);
        View::addLocation(dirname($dir));
    }
    if (! Route::has('legacy.promocion.inc.csv_download')) {
        Route::middleware('web')->name('legacy.')->group(base_path('routes/legacy/promocion.php'));
        app('router')->getRoutes()->refreshNameLookups();
    }
    $this->withoutMiddleware(Authenticate::class);
    DB::table('admin_master')->insert(['cd' => 1, 'application_use_flg' => 1]);
    DB::table('tb_customer')->insert(['cd' => 1, 'id' => 'A001', 'name' => '山田']);
    DB::table('tb_mailmag_analyze')->insert([
        ['cd' => 1, 'mailmag_master_cd' => 10, 'tb_customer_cd' => 1, 'touroku_date' => 1764100000, 'customer_age' => 34, 'customer_sex' => '男性', 'customer_job' => '会社員', 'mailmag_type' => null],
        ['cd' => 2, 'mailmag_master_cd' => 10, 'tb_customer_cd' => 1, 'touroku_date' => 1764200000, 'customer_age' => null, 'customer_sex' => '', 'customer_job' => '', 'mailmag_type' => ''],
        ['cd' => 3, 'mailmag_master_cd' => 20, 'tb_customer_cd' => 1, 'touroku_date' => 1764300000, 'customer_age' => 25, 'customer_sex' => '女性', 'customer_job' => '学生', 'mailmag_type' => 'coupon'],
    ]);
});

it('cuenta los clics de cada familia por sexo, edad y ocupación', function () {
    $this->get(route('admin.promocion.clicks.mail'))->assertOk()
        ->assertSee('■配信お知らせからのマイページアクセス集計')
        ->assertSee('["男性", 1]', false)->assertSee('["不明", 1, "#333333"]', false)
        ->assertSee('["30代", 1]', false)->assertSee('["その他", 1, "#CCCCCC"]', false)
        ->assertSee('["会社員",1,"#1829CE"]', false)->assertSee('$(".ac1").accordion', false)
        ->assertSee('CSV出力する');
    $this->get(route('admin.promocion.clicks.coupon'))->assertOk()->assertSee('["女性", 1, "#D60000"]', false)->assertSee('$(".ac7")', false);
    $this->get(route('admin.promocion.clicks.anketo'))->assertOk()->assertSee('該当する情報がみつかりません。');
});

it('descarga los clics de la familia en SJIS-win con el ID del cliente', function () {
    $csv = $this->post(route('legacy.promocion.inc.csv_download'), ['request_type' => ''])->assertOk()->streamedContent();
    $lines = explode("\r\n", mb_convert_encoding($csv, 'UTF-8', 'SJIS-win'));
    expect($lines[0])->toBe('"メルマガからアクセス数"')
        ->and($lines[1])->toBe("\"\tメルマガCD\",\"\tユーザID\",\"\tアクセス日時\",\"\t年齢\",\"\t性別\",\"\t職業\"")
        ->and($lines[2])->toStartWith("\"\t10\",\"\tA001\",\"\t".date('Y/m/d H:i', 1764200000).'"')
        ->and(count(array_filter($lines)))->toBe(4);
    $this->post(route('legacy.promocion.inc.csv_download'), ['request_type' => 'x'])->assertNotFound();
});
