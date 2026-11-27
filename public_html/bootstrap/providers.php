<?php

/*
 * Proveedores de la aplicación. Además de los fijos, se registra cualquier
 * app/Providers/<Modulo>ServiceProvider.php que agregue un módulo (CONVENCIONES §4):
 * así ningún módulo tiene que editar este archivo y no hay conflictos al fusionar.
 */
$providers = [
    App\Providers\AppServiceProvider::class,
    App\Providers\NucleoServiceProvider::class,
];

foreach (glob(__DIR__ . '/../app/Providers/*ServiceProvider.php') ?: [] as $archivo) {
    $clase = 'App\\Providers\\' . basename($archivo, '.php');
    if (! in_array($clase, $providers, true)) {
        $providers[] = $clase;
    }
}

return $providers;
