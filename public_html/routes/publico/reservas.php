<?php

use App\Http\Controllers\Publico\Reservas\PublicReservationController;
use Illuminate\Support\Facades\Route;

/*
 * Reserva pública (S2, R7): URL literales del legacy r/ y reserve/, sin login ni CSRF (grupo publico de routes/web.php).
 * Nombres publico.reservas.*. Doc/diseno/clientes-ventas-reservas.md (sección r/ y reserve/).
 */
Route::name('publico.reservas.')->group(function () {
    // --- R7 ---
    // r/ (CRM) y su alias reserve/ (la variante POS tb_c2_* queda descartada): mismas URL .php; GET y POST porque
    // el legacy lee $_REQUEST y los JS piden por POST. Los estáticos (css/js/img) los sirve public/legacy/{r,reserve}/.
    foreach (['r' => 'r', 'reserve' => 'reserve'] as $carpeta => $nombre) {
        Route::prefix($carpeta)->name($nombre.'.')->group(function () use ($carpeta) {
            $c = PublicReservationController::class;
            Route::redirect('/', '/'.$carpeta.'/index.php')->name('home');
            Route::match(['get', 'post'], 'index.php', [$c, 'index'])->name('index');
            Route::match(['get', 'post'], 'check.php', [$c, 'check'])->name('check');
            Route::match(['get', 'post'], 'ajax_getScheduleData.php', [$c, 'schedule'])->name('schedule');
            Route::match(['get', 'post'], 'ajax_getChoiceData.php', [$c, 'choices'])->name('choices');
            Route::match(['get', 'post'], 'ajax_reserveRegister.php', [$c, 'register'])->middleware('throttle:60,1')->name('register');
            Route::match(['get', 'post'], 'ajax_reserveDelete.php', [$c, 'cancel'])->middleware('throttle:60,1')->name('cancel');
        });
    }
    // Solo en r/ (QuickReservation::getReservationWaitTime)
    Route::match(['get', 'post'], 'r/getReservationWaitTime.php', [PublicReservationController::class, 'waitTime'])->name('r.wait-time');
    // --- fin R7 ---
});
