<?php

namespace App\Services\Operaciones;

use Illuminate\Support\Facades\Log;

/** POS fuera de alcance: no sincroniza nada, solo deja rastro en el log */
class NullPosGateway implements PosGateway
{
    public function syncStaff(int $staffCd): void
    {
        Log::debug('PosGateway (operaciones) sin implementar: syncStaff', ['staff_cd' => $staffCd]);
    }

    public function removeStaff(int $staffCd): void
    {
        Log::debug('PosGateway (operaciones) sin implementar: removeStaff', ['staff_cd' => $staffCd]);
    }
}
