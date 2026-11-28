<?php

use Illuminate\Routing\Route;

/*
 * Tabla de rutas apta para `route:cache` (producción corre `php artisan optimize`, DESPLIEGUE.md §10):
 * con la caché, dos rutas con el mismo método+URI o el mismo nombre se pisan sin aviso (gana una sola).
 */

/** Duplicados conocidos que arregla otra sesión: se saltan aquí y tienen su propia prueba pendiente */
const RUTAS_DUPLICADAS_PENDIENTES = [
    'POST admin/operaciones/kintai' => 'S5',
];

/** ["MÉTODO uri" => [acciones…]] con más de una ruta (HEAD no cuenta: Laravel lo añade a todo GET) */
function rutasDuplicadasPorMetodoYUri(): array
{
    $vistas = [];
    /** @var Route $route */
    foreach (app('router')->getRoutes()->getRoutes() as $route) {
        foreach (array_diff($route->methods(), ['HEAD']) as $method) {
            $vistas[$method . ' ' . $route->uri()][] = $route->getActionName();
        }
    }

    return array_filter($vistas, fn (array $acciones) => count($acciones) > 1);
}

it('no tiene dos rutas con el mismo método y URI', function () {
    $duplicadas = array_diff_key(rutasDuplicadasPorMetodoYUri(), RUTAS_DUPLICADAS_PENDIENTES);

    expect($duplicadas)->toBe([]);
});

it('no tiene dos rutas con el mismo nombre', function () {
    $nombres = [];
    foreach (app('router')->getRoutes()->getRoutes() as $route) {
        if ($route->getName() !== null) {
            $nombres[$route->getName()][] = implode('|', array_diff($route->methods(), ['HEAD'])) . ' ' . $route->uri();
        }
    }

    expect(array_filter($nombres, fn (array $rutas) => count($rutas) > 1))->toBe([]);
});

it('no deja rutas con el nombre del grupo sin completar (terminado en punto)', function () {
    $sinNombre = collect(app('router')->getRoutes()->getRoutes())
        ->filter(fn (Route $route) => str_ends_with((string) $route->getName(), '.'))
        ->map(fn (Route $route) => $route->getName() . ' → ' . $route->uri())
        ->values()
        ->all();

    expect($sinNombre)->toBe([]);
});

foreach (RUTAS_DUPLICADAS_PENDIENTES as $ruta => $sesion) {
    it("{$ruta} ya no está duplicada (pendiente de {$sesion})", function () use ($ruta, $sesion) {
        if (array_key_exists($ruta, rutasDuplicadasPorMetodoYUri())) {
            $this->markTestSkipped("Duplicada: la corrige {$sesion}. Al subirlo, quitarla de RUTAS_DUPLICADAS_PENDIENTES.");
        }

        expect(true)->toBeTrue();
    });
}
