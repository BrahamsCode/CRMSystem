<?php

namespace App\Http\Controllers\Admin\Ventas;

use App\Http\Controllers\Controller;
use App\Models\BaseSystem;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** 販売管理トップ (system/category2/index.php) */
class HomeController extends Controller
{
    public function index(): View
    {
        return view('admin.ventas.home', [
            'systemName' => (string) BaseSystem::query()->where('cd', 2)->value('system_name'),
        ]);
    }

    /** Pestaña de la cabecera (/system/category2/) e index.php del legacy → portada nueva */
    public function legacy(): RedirectResponse
    {
        return redirect()->route('admin.ventas.home');
    }
}
