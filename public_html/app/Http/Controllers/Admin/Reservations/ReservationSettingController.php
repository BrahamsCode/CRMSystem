<?php

namespace App\Http\Controllers\Admin\Reservations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reservations\UpdateCommentSettingRequest;
use App\Http\Requests\Reservations\UpdateFeatureSettingRequest;
use App\Http\Requests\Reservations\UpdateFormSettingRequest;
use App\Http\Requests\Reservations\UpdateShopSettingRequest;
use App\Models\AdminMaster;
use App\Models\Shop;
use App\Models\Staff;
use App\Services\Reservations\ReservationSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 予約設定 (category9/yoyaku): 各店舗設定, 予約フォーム設定, 機能設定, 注釈設定.
 * Ventanas emergentes sin menú. Tras guardar, PRG con el flash `saved` (el legacy re-pintaba con act=reg).
 */
class ReservationSettingController extends Controller
{
    public function __construct(private readonly ReservationSettingService $settings)
    {
    }

    // ------------------------------------------------------------------ 各店舗設定 (yoyaku_config.php)

    public function shop(Request $request): View
    {
        $isMaster = $this->isMaster($request);

        return view('admin.reservas.settings.shop', [
            'isMaster' => $isMaster,
            'shopCd' => $isMaster ? $request->query('shop_cd') : $this->staffShopCd($request),
            'shopName' => $isMaster ? null : Shop::query()->find($this->staffShopCd($request))?->kaishamei,
            'shops' => $isMaster ? Shop::query()->orderBy('cd')->get(['cd', 'kaishamei']) : collect(),
            'yoyakuSetsubiFlg' => AdminMaster::current()->yoyaku_setsubi_flg,
            'saved' => (bool) $request->session()->get('saved'),
        ]);
    }

    /** ajax_yoyaku_config.php: datos de la tienda para el JS de la pantalla (mismo JSON) */
    public function shopData(Request $request): JsonResponse
    {
        $shopCd = $this->isMaster($request) ? (int) $request->query('shop_cd') : $this->staffShopCd($request);

        return response()->json($this->settings->shopConfigData($shopCd));
    }

    public function updateShop(UpdateShopSettingRequest $request): RedirectResponse
    {
        // Fuera de master, solo la tienda propia (en el legacy, input oculto con la tienda de sesión)
        $shopCd = $this->isMaster($request) ? (int) $request->input('shop_cd') : $this->staffShopCd($request);

        $this->settings->saveShopConfig($shopCd, $request->validated());

        return redirect()->route('admin.reservas.settings.shop', ['shop_cd' => $shopCd])->with('saved', true);
    }

    // ------------------------------------------------------------------ 予約フォーム設定 (yoyaku_form_config.php)

    public function form(Request $request): View
    {
        return view('admin.reservas.settings.form', [
            'basicFields' => $this->settings->basicFormFields(),
            'freeFields' => $this->settings->freeFormFields(),
            'isTablet' => $this->isTablet($request),
            'saved' => (bool) $request->session()->get('saved'),
        ]);
    }

    public function updateForm(UpdateFormSettingRequest $request): RedirectResponse
    {
        $this->settings->saveFormFields($request->all());

        return redirect()->route('admin.reservas.settings.form')->with('saved', true);
    }

    // ------------------------------------------------------------------ 機能設定 (yoyaku_kinou_config.php)

    public function features(Request $request): View
    {
        $adminMaster = AdminMaster::current();

        return view('admin.reservas.settings.features', [
            'timeIntervals' => ReservationSettingService::TIME_INTERVALS,
            'timeInterval' => $this->settings->timeInterval($adminMaster),
            'meyasuJikanFlg' => (string) $adminMaster->meyasu_jikan_flg,
            'isTablet' => $this->isTablet($request),
            'saved' => (bool) $request->session()->get('saved'),
        ]);
    }

    public function updateFeatures(UpdateFeatureSettingRequest $request): RedirectResponse
    {
        $this->settings->saveFeatures(
            (string) $request->validated('time_interval'),
            (string) ($request->validated('meyasu_jikan_flg') ?? ''),
        );

        return redirect()->route('admin.reservas.settings.features')->with('saved', true);
    }

    // ------------------------------------------------------------------ 注釈設定 (yoyaku_comment_config.php)

    public function comment(Request $request): View
    {
        return view('admin.reservas.settings.comment', [
            'commentFields' => ReservationSettingService::COMMENT_FIELDS,
            'comments' => $this->settings->comments(),
            'isTablet' => $this->isTablet($request),
            'saved' => (bool) $request->session()->get('saved'),
        ]);
    }

    public function updateComment(UpdateCommentSettingRequest $request): RedirectResponse
    {
        // Vacíos incluidos: ConvertEmptyStringsToNull los deja en null y el servicio guarda ''
        $this->settings->saveComments($request->only(array_keys($request->rules())));

        return redirect()->route('admin.reservas.settings.comment')->with('saved', true);
    }

    // ------------------------------------------------------------------

    /** $_SESSION['rank'] == 'master' */
    private function isMaster(Request $request): bool
    {
        return ($request->user()?->login_id ?? null) === Staff::MASTER_LOGIN_ID;
    }

    private function staffShopCd(Request $request): int
    {
        return (int) ($request->user()?->shop_cd ?? 0);
    }

    /** $isTablet de kihon.php: cUserAgent DEVICE_NAME en ('CRMRW107', 'CRMH580VT_A') */
    private function isTablet(Request $request): bool
    {
        return str_contains((string) $request->userAgent(), 'CRMRW107')
            || str_contains((string) $request->userAgent(), 'CRMH580VT_A');
    }
}
