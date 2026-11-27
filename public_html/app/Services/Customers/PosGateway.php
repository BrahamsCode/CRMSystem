<?php

namespace App\Services\Customers;

/**
 * Llamadas de clientes y reservas al servidor POS de cada tienda (`*.pos-s.net`, `$posConnect`).
 * El POS está fuera de alcance (2026-11-26): se enlaza NullPosGateway en ClientesServiceProvider.
 * Origen: `inc/ajax_connectPos*.php`, `ajax_sendPosKoubaiMemoData.php`, `category9/yoyaku/ajax_getReservationData.php`, `reserve/`.
 */
interface PosGateway
{
    /** Historial de compras del socio en el POS de la tienda (pestaña 購買履歴) */
    public function purchaseHistory(int $shopCd, int $customerCd): array;

    /** Envía una nota de compra al POS */
    public function sendPurchaseMemo(int $shopCd, int $customerCd, array $data): bool;

    /** Catálogo de menús del POS para reservas (`reserve/`: `tb_c2_catalog`/`tb_c2_category`) */
    public function reservationCatalog(int $shopCd): array;
}
