<?php

namespace App\Http\Controllers\Admin\Ventas;

use App\Http\Requests\Ventas\DiscountRequest;
use App\Models\Sales\Discount;
use Illuminate\Http\RedirectResponse;

/** 割引管理 (category1/karute_menu/karute_waribiki.php, karute_waribiki_register.php, ajax_karute_waribiki_list.php) */
class DiscountController extends RankedMasterController
{
    protected function modelClass(): string
    {
        return Discount::class;
    }

    protected function key(): string
    {
        return 'discounts';
    }

    public function store(DiscountRequest $request): RedirectResponse
    {
        return $this->save($request, $request->values());
    }
}
