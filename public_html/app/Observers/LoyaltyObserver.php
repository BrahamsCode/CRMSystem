<?php

namespace App\Observers;

use App\Models\Customer;
use App\Models\Visit;
use App\Services\Promotions\LoyaltyService;

/**
 * Engancha el módulo de promociones a los eventos del módulo de clientes:
 * cada alta y cada visita pueden dar sellos, puntos y cupones.
 */
class LoyaltyObserver
{
    public function __construct(private LoyaltyService $loyalty) {}

    public function created(Customer|Visit $model): void
    {
        $model instanceof Visit
            ? $this->loyalty->onVisit($model)
            : $this->loyalty->onSignup($model);
    }
}
