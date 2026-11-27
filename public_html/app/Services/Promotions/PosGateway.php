<?php

namespace App\Services\Promotions;

use Carbon\CarbonInterface;

/**
 * Conexión con el servidor POS de cada tienda (tb_shop.pos_domain, BD crm2019 remota) para promoción:
 * búsqueda de clientes por compras en el POS (`pos_search` → {POS_RESULT}) y recordatorios de
 * reservas del POS (`posyoyaku`, `posremdeco`).
 *
 * POS fuera de alcance (CONVENCIONES §3): PromocionServiceProvider enlaza NullPosGateway.
 */
interface PosGateway
{
    /** @return list<int> cd de tb_customer que cumplen la búsqueda del POS */
    public function searchCustomerCds(int $shopCd, string $posSearch): array;

    /** @return list<int> cd de tb_customer con reserva en el POS ese día */
    public function reservationCustomerCdsOn(int $shopCd, CarbonInterface $day): array;
}
