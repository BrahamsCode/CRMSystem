<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DeliveryStatus;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Message;
use App\Models\Visit;
use Illuminate\View\View;

/**
 * Inicio tras el login: selección de módulo (システム選択 en el legacy), con
 * unas cifras del día para entrar directo a lo que hace falta.
 */
class HomeController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.home', [
            'modules' => config('crm.modulos'),
            'today' => [
                'customers' => Customer::where('status', 1)->count(),
                'signups' => Customer::whereDate('created_at', today())->count(),
                'visits' => Visit::whereDate('visited_at', today())->count(),
                'scheduled' => Message::where('delivery_status', DeliveryStatus::Scheduled)->count(),
            ],
        ]);
    }
}
