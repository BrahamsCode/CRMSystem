<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Models\CustomerGroup;
use Illuminate\View\View;

class CustomerGroupController extends ModuleController
{
    public function index(): View
    {
        return view('admin.customers.groups', [
            'groups' => CustomerGroup::withCount('customers')
                ->where('shop_id', $this->shop()?->id)
                ->ordered()
                ->get(),
        ]);
    }
}
