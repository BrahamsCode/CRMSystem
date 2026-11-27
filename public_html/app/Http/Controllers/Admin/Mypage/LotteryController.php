<?php

namespace App\Http\Controllers\Admin\Mypage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mypage\LotteryRequest;
use App\Services\Mypage\LotteryConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * ガチャリン管理 各種設定 (system/category6/kujibiki/kujibiki_config.php). El legacy procesa el act= y
 * vuelve a pintar la misma pantalla; aquí se guarda y se redirige a ella (PRG).
 */
class LotteryController extends Controller
{
    public function __construct(private readonly LotteryConfig $config) {}

    public function edit(): View
    {
        return view('admin.mypage.lottery.edit', ['config' => $this->config->read()]);
    }

    public function update(LotteryRequest $request): RedirectResponse
    {
        match ($request->input('act')) {
            'change_kujibiki_disp_flg' => $this->config->saveEnabled($request->input('kujibiki_disp_flg')),
            'change_kujibiki_max' => $this->config->saveDailyMax($request->input('kujibiki_max')),
            'change_kujibiki_limit_flg' => $this->config->saveLimit($request->input('kujibiki_limit_flg'), $request->input('time_limit')),
            'change_kujibiki_rate' => $this->config->saveRates($request->prizes()),
        };

        return redirect()->route('admin.mypage.lottery.edit');
    }
}
