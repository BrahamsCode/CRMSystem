<?php

namespace App\Http\Controllers\Admin\Mypage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mypage\RecruitRequest;
use App\Models\Mypage\Recruit;
use App\Services\Mypage\LegacyTempImages;
use App\Services\Mypage\MypageShop;
use App\Services\Mypage\RecruitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * 求人情報 (system/category6/recruit/): recruit_list.php, recruit_new.php, recruit_update.php,
 * recruit_register.php, recruit_detail.php, recruit_complete.php, recruit_delete.php, ajax_category.php
 * y el AjaxUpload de las imágenes (inc/upload.php, inc/disp_pic.php).
 */
class RecruitController extends Controller
{
    public function __construct(
        private readonly RecruitService $service,
        private readonly MypageShop $shop,
    ) {}

    /** recruit_list.php */
    public function index(Request $request): View
    {
        return view('admin.mypage.recruits.index', ['pager' => $this->service->paginate($request)]);
    }

    /** recruit_new.php */
    public function create(): View
    {
        return view('admin.mypage.recruits.form', $this->formData('create', null));
    }

    /** recruit_update.php (llega por POST desde el 修正する del detalle) */
    public function edit(int $cd): View
    {
        return view('admin.mypage.recruits.form', $this->formData('edit', $this->findOrFail($cd)));
    }

    /** recruit_new.php act=up → recruit_register.php (register_type=new) → recruit_complete.php */
    public function store(RecruitRequest $request): RedirectResponse
    {
        $recruit = $this->service->save($request->recruitData($this->shop), null);

        return redirect()->route('admin.mypage.recruits.complete', ['cd' => $recruit->cd, 'register_type' => 'new', 'act' => 'up']);
    }

    /** recruit_update.php act=up → recruit_register.php (register_type=update) → recruit_complete.php */
    public function update(RecruitRequest $request, int $cd): RedirectResponse
    {
        $recruit = $this->service->save($request->recruitData($this->shop), $this->findOrFail($cd));

        return redirect()->route('admin.mypage.recruits.complete', ['cd' => $recruit->cd, 'register_type' => 'update', 'act' => 'up']);
    }

    /** recruit_detail.php */
    public function show(int $cd): View
    {
        return view('admin.mypage.recruits.show', $this->showData('detail', $cd, null));
    }

    /** recruit_complete.php */
    public function complete(Request $request, int $cd): View
    {
        return view('admin.mypage.recruits.show', $this->showData('complete', $cd, (string) $request->query('register_type')));
    }

    /** recruit_delete.php (delete_flg=del) */
    public function destroy(Request $request): View
    {
        if ($request->input('delete_flg') === 'del') {
            $this->service->delete($request->input('cd'));
        }

        return view('admin.mypage.processing', [
            'screen' => 'recruit-destroy',
            'refreshUrl' => route('admin.mypage.recruits.index'),
        ]);
    }

    /** ajax_category.php: <option> de los 職種 de la tienda */
    public function categoryOptions(Request $request): View
    {
        return view('admin.mypage.recruits.category-options', [
            'categories' => $this->service->categoriesFor(filter_var($request->input('shop_cd'), FILTER_VALIDATE_INT)),
        ]);
    }

    /** inc/upload.php (AjaxUpload): 'success' o 'error' */
    public function upload(Request $request, LegacyTempImages $temp): Response
    {
        $file = $request->file('userfile');
        $ok = $file !== null && in_array(strtolower($file->getClientOriginalExtension()), ['jpg', 'jpeg', 'png', 'gif'], true)
            && $temp->store(RecruitService::FOLDER, (string) $request->input('time'), $request->input('lastTime'), $file, 'recruit');

        return response($ok ? 'success' : 'error', 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /** inc/disp_pic.php: HTML de la vista previa ('ERROR' si no existe, que el JS ya contempla) */
    public function tempPicture(Request $request, LegacyTempImages $temp): Response
    {
        $time = (string) $request->query('t');
        $path = $temp->validName($time) ? $temp->path(RecruitService::FOLDER, $time, 'jpg') : null;

        $html = $path !== null && Storage::disk('public')->exists($path)
            ? '<br/><br/><img src="' . e($temp->url($path)) . '" /><br/><br/>'
            : 'ERROR';

        return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    private function findOrFail(int $cd): Recruit
    {
        return $this->service->find($cd) ?? abort(404);
    }

    /** Datos del formulario (contrato en recruits/form.blade.php) */
    private function formData(string $mode, ?Recruit $recruit): array
    {
        $shops = $this->service->shops();
        $singleShop = ($shops->count() === 1 || ! $this->shop->isMaster()) ? $shops->first() : null;

        // Tienda elegida: lo reenviado tras un error, la del 求人, o la única disponible
        $shopCd = old('tb_shop_cd', $recruit?->tb_shop_cd ?? ($mode === 'create' && $shops->count() === 1 ? $shops->first()->cd : null));

        return [
            'mode' => $mode,
            'recruit' => $recruit,
            'shops' => $shops,
            'singleShop' => $singleShop,
            'showAllShopsOption' => $this->shop->isMaster(),
            'selectedShopCd' => $shopCd,
            'categories' => filled($shopCd) ? $this->service->categoriesFor($shopCd) : collect(),
            'categoryAlert' => $mode === 'create' && ! $this->service->hasCategories($shopCd),
            'service' => $this->service,
        ];
    }

    /** Datos del detalle / 完了 (contrato en recruits/show.blade.php) */
    private function showData(string $mode, int $cd, ?string $registerType): array
    {
        $recruit = $this->service->find($cd);

        return [
            'mode' => $mode,
            'recruit' => $recruit,
            'registerType' => $registerType,
            'shopName' => $recruit ? $this->service->shopName($recruit) : '',
            'categoryName' => $recruit ? $this->service->categoryName($recruit) : '',
            'canEdit' => $this->shop->can(RecruitService::KENGEN, MypageShop::EDIT),
            'canDelete' => $this->shop->can(RecruitService::KENGEN, MypageShop::DELETE),
            'service' => $this->service,
        ];
    }
}
