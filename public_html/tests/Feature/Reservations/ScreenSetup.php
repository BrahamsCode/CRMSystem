<?php

namespace Tests\Feature\Reservations;

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Tests\Unit\Customers\LegacyFixtures;

/**
 * Preparación de las pruebas de pantalla de S2 mientras el núcleo (S1) no publica el layout,
 * el login del personal ni el Gate `kengen`:
 * - Si no existen `layouts.legacy`/`layouts.legacy-popup`, usa los de `stubs/` (mismas secciones).
 * - Entra como el usuario master en el guard `admin` (GenericUser) y define `kengen` permisivo
 *   solo si el núcleo no lo definió.
 * - Crea la tienda 1 y el empleado 10.
 * Cuando S1 publique todo, basta con quitar los atajos de aquí.
 */
final class ScreenSetup
{
    public static function boot(): void
    {
        if (! View::exists('layouts.legacy') || ! View::exists('layouts.legacy-popup')) {
            View::prependLocation(__DIR__ . '/stubs');
        }

        if (! Gate::has('kengen')) {
            Gate::define('kengen', fn ($user = null, ...$args) => true);
        }

        $master = new GenericUser(['id' => 1, 'cd' => 1, 'login_id' => 'master', 'shop_cd' => 1, 'name' => 'master']);
        Auth::guard('admin')->setUser($master);
        Auth::shouldUse('admin');

        LegacyFixtures::shop(1);
        LegacyFixtures::staff(10, 1, '山田');
    }
}
