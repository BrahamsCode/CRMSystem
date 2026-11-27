<?php

namespace App\Services\Customers;

use App\Models\Customers\Customer;
use App\Models\Customers\Visit;

/**
 * Sellos y puntos de una visita: la parte de `cUtility::raitenFunc` que es de S3
 * (`cStamp`, `Point`, `cOshiraseCoupon`). S2 la declara y enlaza una implementación nula
 * en ClientesServiceProvider; S3 enlaza la suya cuando la publique.
 */
interface VisitLoyalty
{
    /** Sellos que da una visita en la tienda ahora (`cStamp::getStampCount`); se guarda en `tb_raiten_rireki.stamp` */
    public function stampsForVisit(int $shopCd): int;

    /**
     * Tras registrar (o no) la visita: puntos de check-in si hubo visita y sellos según la
     * configuración de la tienda, su máximo y su intervalo (`$stampFlag` '' = sí, '1' = no).
     */
    public function afterVisit(Customer $customer, int $shopCd, ?Visit $visit, ?int $terminalCd, ?int $staffCd, string $stampFlag): void;
}
