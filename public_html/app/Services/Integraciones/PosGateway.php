<?php

namespace App\Services\Integraciones;

/**
 * Lo que las integraciones necesitan del servidor POS de cada tienda:
 * `mpa_customerSync.php` (copiar clientes a la BD POS) y
 * `tool/api/category0/setPosdomain.php` (registrar el dominio POS de la tienda).
 *
 * POS fuera de alcance (Antony, 2026-11-26): se enlaza a NullPosGateway.
 */
interface PosGateway
{
    /**
     * Copia clientes socios a la BD POS de cada tienda.
     *
     * @param  string  $scope  id numérico del cliente, 'unsynchronized', 'all' o 'full' (como el legacy)
     */
    public function syncCustomers(string $scope): void;

    /** Registra el servidor POS de una tienda ("{domain}.pos-s.net") y su paquete */
    public function setShopDomain(int $shopCd, string $domain, ?string $package): void;
}
