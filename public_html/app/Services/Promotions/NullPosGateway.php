<?php

namespace App\Services\Promotions;

use Carbon\CarbonInterface;

/** POS fuera de alcance: no hay clientes del POS (como el legacy cuando no conecta: `{POS_RESULT}` = -1) */
class NullPosGateway implements PosGateway
{
    public function searchCustomerCds(int $shopCd, string $posSearch): array
    {
        return [];
    }

    public function reservationCustomerCdsOn(int $shopCd, CarbonInterface $day): array
    {
        return [];
    }
}
