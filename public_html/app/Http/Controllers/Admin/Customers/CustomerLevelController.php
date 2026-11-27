<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Models\Customers\CustomerLevel;

/**
 * 顧客レベル (legacy: category1/customer/customer_level_category_new.php, _frm.php, _register.php; tb_customer_level).
 * Sin entrada en el menú, como el legacy. El `cd` nuevo sale del contador `hatuban` (no de createHatsuban).
 */
class CustomerLevelController extends CustomerMasterController
{
    protected function modelClass(): string
    {
        return CustomerLevel::class;
    }

    protected function screen(): string
    {
        return 'level';
    }

    protected function routeName(): string
    {
        return 'admin.clientes.levels';
    }

    protected function usesCreateHatsuban(): bool
    {
        return false;
    }
}
