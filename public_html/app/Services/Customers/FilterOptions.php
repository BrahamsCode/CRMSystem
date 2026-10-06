<?php

namespace App\Services\Customers;

use App\Models\Coupon;
use App\Models\CustomCategory;
use App\Models\CustomerGroup;
use App\Models\Shop;

/** Listas que necesita el panel de filtros (x-customers.search-filters) */
class FilterOptions
{
    public static function for(?int $shopId): array
    {
        return [
            'shops' => Shop::active()->orderBy('name')->get(),
            'groups' => CustomerGroup::active()->ordered()->get(),
            // Solo las categorías y campos marcados como filtro de búsqueda
            'categories' => CustomCategory::with(['fields' => fn ($q) => $q->where('search_flg', 1)->ordered(), 'fields.options'])
                ->where('shop_id', $shopId)
                ->where('search_flg', 1)
                ->ordered()
                ->get()
                ->filter(fn ($c) => $c->fields->isNotEmpty())
                ->values(),
            'coupons' => Coupon::orderByDesc('id')->get(['id', 'name']),
        ];
    }
}
