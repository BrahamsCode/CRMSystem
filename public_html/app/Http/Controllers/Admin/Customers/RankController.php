<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Enums\RankType;
use App\Models\Rank;
use Illuminate\View\View;

class RankController extends ModuleController
{
    public function index(): View
    {
        $ranks = Rank::where('shop_id', $this->shop()?->id)->ordered()->get();

        return view('admin.customers.ranks', [
            'amountRanks' => $ranks->where('type', RankType::Amount),
            'visitRanks' => $ranks->where('type', RankType::Visits),
        ]);
    }
}
