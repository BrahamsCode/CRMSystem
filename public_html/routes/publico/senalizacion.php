<?php

use App\Http\Controllers\Publico\Senalizacion\DisplayXmlController;
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
});
