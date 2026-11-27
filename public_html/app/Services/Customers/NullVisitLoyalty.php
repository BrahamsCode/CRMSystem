<?php

namespace App\Services\Customers;

use App\Models\Customers\Customer;
use App\Models\Customers\Visit;

/** Implementación nula de VisitLoyalty hasta que S3 publique la suya: no da sellos ni puntos. */
class NullVisitLoyalty implements VisitLoyalty
{
    public function stampsForVisit(int $shopCd): int
    {
        return 0;
    }

    public function afterVisit(Customer $customer, int $shopCd, ?Visit $visit, ?int $terminalCd, ?int $staffCd, string $stampFlag): void {}
}
