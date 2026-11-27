<?php

namespace App\Http\Controllers\Admin\Reservations;

use App\Http\Controllers\Controller;
use App\Models\Reservations\ReservationMenuCategory;
use App\Services\Nucleo\LegacyUi;
use App\Services\Reservations\ReservationFlowService;
use App\Services\Reservations\ReservationInput;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Fragmentos AJAX de 予約管理 con la misma respuesta HTML que el legacy:
 * ajax_yoyaku_menu.php (menús del empleado), ajax_staff.php (<option> de empleados),
 * ajax_search_kaiin.php (buscador de socios de yoyaku.php y related_customer.php)
 * y ajax_yoyaku_koumoku_category.php (lista de 予約項目カテゴリ con send_type).
 */
class ReservationOptionController extends Controller
{
    public function __construct(private readonly ReservationFlowService $flow) {}

    /** ajax_yoyaku_menu.php: tablas ベースメニュー / オプションメニュー con casillas y el botón 確定 */
    public function menus(Request $request): View
    {
        $shopCd = (int) $request->input('shop_cd');
        $staffCd = (int) $request->input('staff_cd');
        $checked = array_filter(explode(',', ReservationInput::form2Db($request->input('menu_cds'))), 'strlen');

        $rows = DB::table('tb_staff_works as tb1')
            ->join('tb_yoyaku_koumoku_master as tb2', 'tb1.tb_yoyaku_koumoku_master_cd', '=', 'tb2.cd')
            ->leftJoin('tb_yoyaku_koumoku_category as tb3', 'tb3.cd', '=', 'tb2.tb_yoyaku_koumoku_category_cd')
            ->where('tb1.tb_staff_cd', $staffCd)
            ->whereIn('tb2.tb_shop_cd', [$shopCd, -1])
            ->where('tb1.disp_flg', '1')
            ->where('tb2.disp_flg', '1')
            ->whereRaw("COALESCE(tb3.disp_flg, '') <> '9'")
            ->where('tb1.hyouji_flg', '1')
            ->where('tb2.hyouji_flg', '1')
            ->where(fn ($q) => $q->whereNull('tb2.tb_yoyaku_koumoku_category_cd')->orWhere('tb3.hyouji_flg', '1'))
            ->orderBy('tb2.type_flg')->orderBy('tb3.rank')->orderBy('tb3.cd')->orderByDesc('tb2.tb_shop_cd')->orderBy('tb2.rank')->orderBy('tb2.cd')
            ->get(['tb2.cd', 'tb1.shoyou_time', 'tb2.name', 'tb2.naiyou', 'tb2.tb_shop_cd', 'tb2.type_flg', 'tb3.name as category']);

        return view('admin.reservas.partials.yoyaku_menu', [
            'baseMenus' => $rows->filter(fn ($r) => (string) $r->type_flg === '1')->values(),
            'optionMenus' => $rows->filter(fn ($r) => (string) $r->type_flg !== '1')->values(),
            'checked' => $checked,
        ]);
    }

    /** ajax_staff.php: <option> de los empleados activos de la tienda */
    public function staff(Request $request): View|Response
    {
        $staff = DB::table('tb_staff')
            ->where('disp_flg', '1')
            ->where('shop_cd', (int) $request->input('shop_cd'))
            ->orderByRaw("coalesce(common_flg, '')")->orderBy('rank')->orderBy('cd')
            ->get(['cd', 'name']);

        if ($staff->isEmpty()) {
            return response('<option value="">登録されていません</option>');
        }

        return view('admin.reservas.partials.staff_options', [
            'staff' => $staff,
            'staffWord' => $this->flow->manageWord('スタッフ', 'c9_yoyaku[スタッフ]'),
        ]);
    }

    /** ajax_search_kaiin.php: socios (touroku_kubun = '2') por palabra; no master → solo los de su tienda */
    public function members(Request $request, LegacyUi $legacyUi): View
    {
        $staff = $request->user('admin');
        $query = DB::table('tb_customer')->where('disp_flg', '1')->where('touroku_kubun', '2')
            ->when(! $staff?->isMaster(), fn ($q) => $q->where('tb_shop_cd', (int) (session('shop_cd') ?? $staff?->shop_cd)));

        $words = ReservationInput::form2Db($request->input('w', ''));
        if ($words !== '') {
            $like = fn (string $value) => '%' . addcslashes($value, '%_\\') . '%';
            foreach (explode(' ', str_replace('　', ' ', $words)) as $word) {
                $query->where(function ($q) use ($word, $like) {
                    $q->where('name', 'like', $like($word))
                        ->orWhere('name_kana', 'like', $like(mb_convert_kana($word, 'KVC')))
                        ->orWhere('id', 'like', $like($word))
                        ->orWhere('tel', 'like', $like(mb_convert_kana($word, 'a')))
                        ->orWhere('email1', 'like', $like(mb_convert_kana($word, 'a')))
                        ->orWhere('kojin_birth', 'like', $like($word))
                        ->orWhere('kanri_id', 'like', $like($word))
                        ->orWhere('kojin_mobile', 'like', $like($word));
                });
            }
        }

        $total = (clone $query)->count();
        $limit = str_contains((string) $request->userAgent(), 'iPad') ? 20 : 200;
        $members = $query->orderByRaw("coalesce(last_raiten_date, '') DESC")->orderByDesc('cd')->limit($limit)
            ->get(['cd', 'id', 'kanri_id', 'name', 'name_kana']);

        return view('admin.reservas.partials.search_kaiin', [
            'total' => $total,
            'members' => $members,
            'btnFontColor' => $legacyUi->versionFlg() === '2' ? '' : '#116699 !important',
        ]);
    }

    /**
     * ajax_yoyaku_koumoku_category.php: aplica `send_type` (rankup / rankdown / hyouji / del sobre send_targetCd)
     * con el servicio de 予約項目カテゴリ y devuelve la lista, como el legacy.
     */
    public function menuCategories(Request $request, ReservationMenuCategoryController $categories): View
    {
        $type = (string) $request->input('send_type');
        $target = $request->filled('send_targetCd') ? ReservationMenuCategory::query()->find((int) $request->input('send_targetCd')) : null;

        if ($target !== null) {
            return match ($type) {
                'rankup', 'rankdown' => $categories->sort($request, $target),
                'hyouji' => $categories->toggle($target),
                'del' => $categories->destroy($target),
                default => $categories->list(),
            };
        }

        return $categories->list();
    }
}
