<?php

use Illuminate\Support\Facades\Route;

// Con route:cache gana la primera ruta registrada y sin caché la última: cada método+URI
// de S5 (operaciones e integraciones) debe tener una sola ruta.
it('las rutas de operaciones e integraciones no repiten método y URI', function () {
    $seen = [];
    $duplicates = [];
    foreach (Route::getRoutes()->getRoutes() as $route) {
        $name = (string) $route->getName();
        if (! preg_match('/^(admin\.operaciones\.|operaciones\.|publico\.integraciones\.)/', $name)) {
            continue;
        }
        foreach (array_diff($route->methods(), ['HEAD']) as $method) {
            $key = $method.' '.$route->getDomain().'/'.$route->uri();
            if (isset($seen[$key])) {
                $duplicates[] = "{$key}: {$seen[$key]} / {$name}";
            }
            $seen[$key] = $name;
        }
    }

    expect($duplicates)->toBe([]);
});

it('勤怠管理: el buscador de la lista y 戻る van por GET (el POST de kintai es el alta)', function () {
    expect(Route::getRoutes()->getByName('admin.operaciones.kintai.index')->methods())->toBe(['GET', 'HEAD']);

    foreach (['index', 'done'] as $view) {
        $html = file_get_contents(resource_path("views/admin/operaciones/kintai/{$view}.blade.php"));
        expect($html)->toContain('<form class="form" method="get" action="{{ route(\'admin.operaciones.kintai.index\') }}">');
    }
});
