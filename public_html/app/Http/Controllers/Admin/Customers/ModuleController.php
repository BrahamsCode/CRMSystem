<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Http\Controllers\Controller;
use App\Models\Shop;

/**
 * Base de los controladores del módulo de clientes.
 *
 * Toda la configuración del módulo (grupos, rangos, motivos, campos, categorías)
 * cuelga de una tienda. Mientras no exista el selector de tienda, se trabaja con
 * la primera activa.
 */
abstract class ModuleController extends Controller
{
    protected function shop(): ?Shop
    {
        return once(fn () => Shop::active()->orderBy('id')->first());
    }
}
