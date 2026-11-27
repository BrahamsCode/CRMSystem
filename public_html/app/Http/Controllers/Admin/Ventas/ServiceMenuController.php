<?php

namespace App\Http\Controllers\Admin\Ventas;

use App\Http\Requests\Ventas\ServiceMenuRequest;
use App\Models\Sales\ServiceMenu;
use Illuminate\Http\RedirectResponse;

/** メニュー管理 (category1/karute_menu/karute_menu.php, karute_menu_register.php, ajax_karute_menu_list.php) */
class ServiceMenuController extends RankedMasterController
{
    protected function modelClass(): string
    {
        return ServiceMenu::class;
    }

    protected function key(): string
    {
        return 'menus';
    }

    public function store(ServiceMenuRequest $request): RedirectResponse
    {
        return $this->save($request, $request->values());
    }
}
