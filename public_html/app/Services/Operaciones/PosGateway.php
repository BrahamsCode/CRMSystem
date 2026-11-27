<?php

namespace App\Services\Operaciones;

/**
 * Lo que 業務支援管理 necesita del servidor POS de cada tienda: el legacy replica el
 * alta, edición y baja del personal en la BD POS (`db_sync_all2`, tb_shop.pos_domain).
 *
 * POS fuera de alcance (Antony, 2026-11-26): se enlaza a NullPosGateway.
 */
interface PosGateway
{
    /** Replica en el POS de su tienda el alta o la edición de un empleado (tb_staff.cd) */
    public function syncStaff(int $staffCd): void;

    /** Da de baja al empleado en el POS de su tienda */
    public function removeStaff(int $staffCd): void;
}
