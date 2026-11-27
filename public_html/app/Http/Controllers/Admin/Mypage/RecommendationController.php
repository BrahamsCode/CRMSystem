<?php

namespace App\Http\Controllers\Admin\Mypage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mypage\RecommendationRequest;
use App\Models\AdminMaster;
use App\Services\Mypage\LegacyTempImages;
use App\Services\Mypage\MypageShop;
use App\Services\Mypage\RecommendationPush;
use App\Services\Mypage\RecommendationService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * オススメ管理 (system/category6/user_hp/recommend.php, recommend_register.php,
 * recommend_delete_register.php) y el AjaxUpload de su imagen (inc/upload.php, inc/disp_pic.php).
 */
class RecommendationController extends Controller
{
    public function __construct(
        private readonly RecommendationService $service,
        private readonly MypageShop $shop,
    ) {}

    /** recommend.php: formulario (alta o ?cd= modificación) + lista; POST del 表示情報を更新 */
    public function index(Request $request): View
    {
        if ($request->input('update_hyouji_flg') === 'update') {
            $this->service->updateVisibility((array) $request->input('update_cd', []), (array) $request->input('hyouji_flg_array', []));
        }

        $recommendation = $this->service->find($request->input('cd'));
        $editing = filled($request->input('cd'));
        $shops = $this->shop->selectableShops();

        return view('admin.mypage.recommendations.index', [
            'menuName' => $this->service->menuName(),
            'recommendation' => $recommendation,
            'editing' => $editing,
            'formVisible' => $this->shop->can(RecommendationService::KENGEN, $editing ? MypageShop::EDIT : MypageShop::CREATE),
            'canEdit' => $this->shop->can(RecommendationService::KENGEN, MypageShop::EDIT),
            'canDelete' => $this->shop->can(RecommendationService::KENGEN, MypageShop::DELETE),
            'showShopSelect' => $this->shop->isMaster() && $shops->count() > 1,
            'shops' => $shops,
            'sessionShopCd' => $this->shop->current(),
            'sessionShopName' => $this->shop->name(),
            'imageUrl' => $this->service->formImageUrl($recommendation),
            'applicationUseFlg' => (int) AdminMaster::current()->application_use_flg === 1,
            'pager' => $this->service->paginate($request),
            'service' => $this->service,
        ]);
    }

    /** recommend_register.php: guarda, envía el push y muestra 「登録中です。」 */
    public function store(RecommendationRequest $request, RecommendationPush $push): View
    {
        $recommendation = $request->input('act') === 'up' ? $this->service->find($request->input('cd')) : null;
        abort_if($request->input('act') === 'up' && $recommendation === null, 404);

        $recommendation = $this->service->save($request->recommendationData($this->shop), $recommendation);

        if ($request->wantsPush()) {
            $push->exec($request->getHost(), 'recommend', $recommendation->cd);
        }

        return view('admin.mypage.processing', [
            'screen' => 'recommend-store',
            'menuName' => $this->service->menuName(),
            'refreshUrl' => route('admin.mypage.recommendations.index'),
        ]);
    }

    /** recommend_delete_register.php */
    public function destroy(Request $request): View
    {
        $this->service->delete($request->input('cd'), (string) $request->input('delete_flg'));

        return view('admin.mypage.processing', [
            'screen' => 'recommend-destroy',
            'refreshUrl' => route('admin.mypage.recommendations.index'),
        ]);
    }

    /** inc/upload.php (AjaxUpload): responde 'success' o 'error' en texto */
    public function upload(Request $request, LegacyTempImages $temp): Response
    {
        $file = $request->file('userfile');
        $ok = $file !== null && in_array(strtolower($file->getClientOriginalExtension()), ['jpg', 'jpeg', 'png', 'gif'], true)
            && $temp->store(RecommendationService::FOLDER, (string) $request->input('time'), $request->input('lastTime'), $file, 'recommend');

        return response($ok ? 'success' : 'error', 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /** inc/disp_pic.php: HTML de la vista previa o 'ERROR' */
    public function tempPicture(Request $request, LegacyTempImages $temp): Response
    {
        $extension = $request->query('ext') === 'gif' ? 'gif' : 'jpg';
        $time = (string) $request->query('t');
        $path = $temp->validName($time) ? $temp->path(RecommendationService::FOLDER, $time, $extension) : null;

        $html = $path !== null && Storage::disk('public')->exists($path)
            ? '<br/><br/><img src="' . e($temp->url($path)) . '" style="width:480px;" /><br/><br/>'
            : 'ERROR';

        return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
}
