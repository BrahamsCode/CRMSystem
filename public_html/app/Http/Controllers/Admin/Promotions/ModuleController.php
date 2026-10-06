<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Support\Collection;

/**
 * Base de los controladores del módulo de promociones.
 *
 * Igual que en clientes, la configuración por tienda (sellos, puntos, emails de
 * prueba) usa la primera tienda activa hasta que exista el selector de tienda.
 */
abstract class ModuleController extends Controller
{
    protected function shop(): ?Shop
    {
        return once(fn () => Shop::active()->orderBy('id')->first());
    }

    protected function shops(): Collection
    {
        return once(fn () => Shop::active()->orderBy('name')->get());
    }
}
