<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Http\Requests\Customers\CustomerMasterRequest;
use App\Models\Promotions\CustomerGroup;
use App\Support\LegacyText;

/**
 * 顧客グループ設定 (legacy: category1/customer/customer_group_category_new.php, _frm.php, _register.php).
 * Edita `name` y `default_flg` (初期値: uno solo). Los campos de puntos de tb_customer_group (keisan_flg,
 * shiyou_point_jyougen, point_percentage_*) no salen en esta pantalla del legacy: no se tocan.
 * Tabla de S3 (modelo CustomerGroup, sin cambios). La sincronización con los POS de tienda (db_sync_all2) queda fuera.
 */
class CustomerGroupController extends CustomerMasterController
{
    protected function modelClass(): string
    {
        return CustomerGroup::class;
    }

    protected function screen(): string
    {
        return 'group';
    }

    protected function routeName(): string
    {
        return 'admin.clientes.groups';
    }

    protected function values(CustomerMasterRequest $request): array
    {
        return parent::values($request) + [
            'default_flg' => LegacyText::form($request->input('default_flg')),
            'synchronize_flg' => '',
        ];
    }
}
