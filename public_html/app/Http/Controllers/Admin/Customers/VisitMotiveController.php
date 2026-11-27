<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Http\Requests\Customers\CustomerMasterRequest;
use App\Models\Customers\VisitMotive;

/**
 * 初回来店動機項目設定 (legacy: category1/customer/raiten_douki.php, _frm.php, _register.php; tb_raiten_douki_master).
 */
class VisitMotiveController extends CustomerMasterController
{
    protected function modelClass(): string
    {
        return VisitMotive::class;
    }

    protected function screen(): string
    {
        return 'motive';
    }

    protected function routeName(): string
    {
        return 'admin.clientes.visit-motives';
    }

    protected function values(CustomerMasterRequest $request): array
    {
        return parent::values($request) + ['synchronize_flg' => ''];
    }
}
