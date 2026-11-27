<?php

namespace App\Services\Customers;

/** POS fuera de alcance: no hay datos y nada se envía. */
class NullPosGateway implements PosGateway
{
    public function purchaseHistory(int $shopCd, int $customerCd): array
    {
        return [];
    }

    public function sendPurchaseMemo(int $shopCd, int $customerCd, array $data): bool
    {
        return false;
    }

    public function reservationCatalog(int $shopCd): array
    {
        return [];
    }
}
