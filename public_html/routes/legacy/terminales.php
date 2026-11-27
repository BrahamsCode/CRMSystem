<?php

use App\Http\Controllers\Admin\Terminales\PaperCardController;
use Illuminate\Support\Facades\Route;

/*
 * URL literales del legacy que llama el JS de 端末管理 (S4). S1 las carga sin prefijo de URL, con el guard
 * del personal y el prefijo de nombre `legacy.` (routes/web.php): nombres legacy.terminales.*
 */
Route::name('terminales.')->group(function () {
    // ペーパーサイネージ: altas, ediciones, 稼働区分 y borrado por AJAX (card_signage/control.php)
    Route::post('system/category7/card_signage/control.php', [PaperCardController::class, 'control'])
        ->name('paper-card-control');

    // ICカード管理 (felica/). Las URL de las pantallas redirigen a las nuevas (menú lateral, marcadores)
    Route::get('system/category7/felica/terminal_list.php', [\App\Http\Controllers\Admin\Terminales\FelicaTerminalController::class, 'legacy'])
        ->name('felica-terminal-list');
    Route::get('system/category7/felica/card_list.php', [\App\Http\Controllers\Admin\Terminales\FelicaCardController::class, 'legacy'])
        ->name('felica-card-list');
    // 会員検索 del colorbox de カード管理
    Route::post('system/category7/felica/ajax_search_kaiin.php', [\App\Http\Controllers\Admin\Terminales\FelicaCardController::class, 'searchMembers'])
        ->name('felica-search-kaiin');
    // ajax_del.php tomaba la tabla de la petición (tbNo): una ruta por tipo, con el CSRF de Laravel
    Route::post('system/category7/felica/ajax_del.php/terminal', [\App\Http\Controllers\Admin\Terminales\FelicaTerminalController::class, 'destroy'])
        ->name('felica-del-terminal');
    Route::post('system/category7/felica/ajax_del.php/card', [\App\Http\Controllers\Admin\Terminales\FelicaCardController::class, 'destroy'])
        ->name('felica-del-card');
});
