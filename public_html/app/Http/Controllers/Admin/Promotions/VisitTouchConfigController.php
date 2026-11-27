<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Promotions\VisitTouchConfigRequest;
use App\Services\Promotions\StaffContext;
use App\Services\Promotions\VisitTouchConfigService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** カードリーダ設定 (visit/touch_config.php + touch_config_register.php) */
class VisitTouchConfigController extends Controller
{
    public function __construct(
        private readonly StaffContext $staff,
        private readonly VisitTouchConfigService $config,
    ) {}

    public function edit(): View
    {
        return view('admin.promocion.visit.touch_config', [
            'shops' => $this->config->shops($this->staff->isMaster(), $this->staff->shopCd()),
            'options' => VisitTouchConfigService::options(),
        ]);
    }

    /** 更新: guarda y muestra «登録中です» con vuelta a la pantalla al segundo, como el legacy */
    public function update(VisitTouchConfigRequest $request): View
    {
        if ($request->has('send')) {
            $this->config->update($request->intervals(), $this->staff->isMaster(), $this->staff->shopCd());
        }

        return view('admin.promocion.visit.touch_config_register', ['next' => route('admin.promocion.visit.touch-config')]);
    }

    /** GET de la URL del legacy (menú): misma pantalla */
    public function legacy(): RedirectResponse
    {
        return redirect()->route('admin.promocion.visit.touch-config');
    }
}
