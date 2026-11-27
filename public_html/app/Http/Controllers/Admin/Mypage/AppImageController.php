<?php

namespace App\Http\Controllers\Admin\Mypage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mypage\CustomerGroupDisplayRequest;
use App\Http\Requests\Mypage\HomeCommentRequest;
use App\Services\Mypage\AppImageScreen;
use App\Services\Mypage\MypageConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * マイコレ管理 (system/category6/user_hp/mobile_mycolle_edit*.php): las 5 pantallas en una vista
 * por sección y los dos formularios POST de la pantalla 'all'. Las imágenes van por AJAX
 * (ApliFileUploadController).
 */
class AppImageController extends Controller
{
    public function edit(string $section, AppImageScreen $screen): View
    {
        return view('admin.mypage.app-images.edit', $screen->data($section));
    }

    /** type=home_comment: comment_flg en mypage_config y textos en apli_home_comment */
    public function updateHomeComment(HomeCommentRequest $request, MypageConfig $config): RedirectResponse
    {
        $config->mergeMypageConfig($request->configValues());
        $config->saveHomeComments($request->comments());

        return redirect()->route('admin.mypage.app-images.edit', ['section' => 'all']);
    }

    /** mode=apli_customer_group_disp_upload */
    public function updateCustomerGroupDisplay(CustomerGroupDisplayRequest $request, MypageConfig $config): RedirectResponse
    {
        $config->saveCustomerGroupDisplay($request->display());

        return redirect()->route('admin.mypage.app-images.edit', ['section' => 'all']);
    }
}
