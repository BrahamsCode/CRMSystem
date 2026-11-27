<?php

namespace App\Http\Controllers\Admin\Base;

use App\Http\Controllers\Controller;
use App\Models\BaseSystem;
use Illuminate\View\View;

/**
 * 基本情報管理トップ (system/category0/index.php): portada del módulo con su menú lateral.
 */
class IndexController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.base.index', [
            'systemName' => (string) BaseSystem::query()->where('cd', 0)->where('system_flg', '1')->value('system_name'),
        ]);
    }
}
