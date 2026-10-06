<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Models\VisitMotive;
use Illuminate\View\View;

class VisitMotiveController extends ModuleController
{
    public function index(): View
    {
        return view('admin.customers.visit-motives', [
            'motives' => VisitMotive::where('shop_id', $this->shop()?->id)
                ->ordered()
                ->get(),
        ]);
    }
}
