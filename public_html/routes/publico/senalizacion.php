<?php

use App\Http\Controllers\Publico\Senalizacion\CouponPrintController;
use App\Http\Controllers\Publico\Senalizacion\DisplayXmlController;
use App\Http\Controllers\Publico\Senalizacion\PlayerController;
use App\Http\Controllers\Publico\Senalizacion\PollController;
use App\Http\Controllers\Publico\Senalizacion\SignageUploadController;
use App\Http\Controllers\Publico\Senalizacion\SignageViewController;
use Illuminate\Support\Facades\Route;

/*
 * Señalización (S4): URL del legacy que leen los reproductores. Fuera del grupo
 * admin, sin CSRF ni prefijo (CONVENCIONES §2). Estas rutas bajo /hp/uploads/
 * tienen prioridad sobre la redirección genérica de S1.
 */
Route::name('publico.senalizacion.')->group(function () {
    // XML de un programa (<cd>.xml) y floormap.xml, generados por SignageFeedBuilder
    Route::get('hp/uploads/c7/display/{file}', DisplayXmlController::class)
        ->where('file', '[0-9]+\.xml|floormap\.xml')
        ->name('display-xml');

    /*
     * Reproductor digitalsignage/ (HTML5 en lugar de Flash). GET y POST porque el legacy lee $_REQUEST
     * y los sondeos son jQuery .load() con datos (POST). Los estáticos (coupon_detail_print.css,
     * digitalsignage_logo.jpg, load_data.xml, fla/floormap/*.xml|jpg, tig_game/img/chara.jpg) los sirve
     * public/legacy/digitalsignage/ en su URL original.
     */
    Route::prefix('digitalsignage')->group(function () {
        Route::match(['get', 'post'], '/', [PlayerController::class, 'show'])->name('player');
        Route::match(['get', 'post'], 'index.php', [PlayerController::class, 'show'])->name('player.index');
        Route::match(['get', 'post'], 'index2.php', [PlayerController::class, 'show'])->name('player.index2');
        Route::match(['get', 'post'], 'roulette.php', [PlayerController::class, 'roulette'])->name('roulette');
        Route::match(['get', 'post'], 'slot.php', [PlayerController::class, 'slot'])->name('slot');

        // Sondeos
        Route::match(['get', 'post'], 'ajax_network_check.php', [PollController::class, 'network'])->name('network-check');
        Route::match(['get', 'post'], 'ajax_xml_check.php', [PollController::class, 'xmlCheck'])->name('xml-check');
        Route::match(['get', 'post'], 'ajax_xml_check_2.php', [PollController::class, 'xmlCheck2'])->name('xml-check-2');
        Route::match(['get', 'post'], 'ajax_display_config_check.php', [PollController::class, 'displayConfigCheck'])->name('display-config-check');
        Route::match(['get', 'post'], 'ajax_display_config_check_2.php', [PollController::class, 'displayConfigCheck'])->name('display-config-check-2');

        // Cupones impresos
        $coupons = [
            'ajax_touch_print_coupon_crawl.php' => ['touch', 'touch-print'],
            'ajax_touch_print_coupon_crawl_2.php' => ['touch2', 'touch-print-2'],
            'ajax_shinkyaku_print_coupon_crawl.php' => ['shinkyaku', 'shinkyaku-print'],
            'ajax_shinkyaku_print_coupon_crawl_2.php' => ['shinkyaku2', 'shinkyaku-print-2'],
            'ajax_Mujyouken_print_coupon_crawl_2.php' => ['mujyouken', 'mujyouken-print-2'],
            'ajax_kaiin_raiten_print_coupon_crawl.php' => ['kaiinRaiten', 'kaiin-raiten-print'],
            'ajax_kaiin_mycoupon_print_crawl.php' => ['mycoupon', 'kaiin-mycoupon-print'],
            'ajax_print_coupon_crawl.php' => ['visit', 'visit-print'],
            'ajax_print_coupon_craw_2l.php' => ['visit2', 'visit-print-2'],
            'fla/floormap/ajax_print_crawl.php' => ['floormap', 'floormap-print'],
        ];
        foreach ($coupons as $uri => [$method, $name]) {
            Route::match(['get', 'post'], $uri, [CouponPrintController::class, $method])->name($name);
        }
    });

    /*
     * Reproductor de la señalización web signage/view/ (?cd=<terminal>) y su sondeo ajax.php
     * (?packages_cd=<terminal>). La vista previa view/packages.php va en el admin (admin.senalizacion.packages.preview).
     * /signage/upload/… sirve los archivos del disco public; /signage/css, img, js y view/css|js los sirve
     * public/legacy/signage/ en su URL original.
     */
    Route::prefix('signage')->name('signage.')->group(function () {
        Route::match(['get', 'post'], 'view', [SignageViewController::class, 'show'])->name('view');
        Route::match(['get', 'post'], 'view/index.php', [SignageViewController::class, 'show'])->name('view.index');
        Route::match(['get', 'post'], 'view/ajax.php', [SignageViewController::class, 'poll'])->name('view.poll');
        Route::get('upload/{path}', SignageUploadController::class)->where('path', '.+')->name('upload');
    });
});
