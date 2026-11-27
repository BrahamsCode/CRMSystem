<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * 販売管理トップ (legacy: system/category2/index.php): portada con la imagen cover2.gif y el menú lateral.
 */
class ModuleController extends Controller
{
    public function index(): View
    {
        return view('admin.ventas.home');
    }
}
