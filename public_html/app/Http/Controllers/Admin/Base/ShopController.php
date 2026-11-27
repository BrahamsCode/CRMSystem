<?php

namespace App\Http\Controllers\Admin\Base;

use App\Http\Controllers\Controller;
use App\Models\AdminMaster;
use App\Models\Area;
use App\Models\Shop;
use App\Models\ShopCategory;
use App\Services\Base\ShopService;
use App\Services\Nucleo\LegacyUi;
use App\Support\LegacyText;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * 店舗情報管理 (system/category0/shop/*.php), mismas URL y pantallas que el legacy:
 * shop_list → shop_detail → shop_new → shop_new_confirm → shop_new_register → shop_new_complete,
 * y shop_delete_register (borrar / 削除を取り消す).
 */
class ShopController extends Controller
{
    public function __construct(private ShopService $shops, private LegacyUi $legacyUi) {}

    /** shop_list.php: 店舗検索 + 店舗一覧 paginado (orden kaishamei_kana) */
    public function index(Request $request): View
    {
        $kensu = (int) ($request->query('kensu') ?: 10);
        $page = max(1, (int) ($request->query('page') ?: 1));
        $selected = array_values(array_filter((array) $request->query('tb_shop_cds', []), 'is_scalar'));

        $query = $this->listQuery($request, $selected);
        $maxrows = (clone $query)->count();
        $rows = $maxrows ? (clone $query)->orderBy('kaishamei_kana')->limit($kensu)->offset(($page - 1) * $kensu)->get() : collect();

        // Condiciones que se arrastran en los enlaces del paginador (como $q del legacy)
        $q = collect(['disp_flg', 'kaishamei', 'tantou', 'mail'])
            ->filter(fn ($k) => (string) $request->query($k, '') !== '')
            ->map(fn ($k) => $k . '=' . $request->query($k))->values()->all();
        if ($selected) {
            $q[] = 'tb_shop_cds=' . implode('&', $selected);
        }

        return view('admin.base.shops.index', [
            'kensu' => $kensu,
            'page' => $page,
            'maxrows' => $maxrows,
            'rows' => $rows,
            'selected' => $selected,
            'pagerBase' => 'shop_list.php?' . implode('&', $q),
            'treeShops' => DB::table('tb_shop')->where('disp_flg', '1')->orderByDesc('touroku_date')->get(),
            'categories' => ShopCategory::titles(),
            'isDevelop' => Gate::allows('nucleo.developer'),
        ]);
    }

    /** Shop::getListSQL del legacy */
    private function listQuery(Request $request, array $selected)
    {
        $query = DB::table('tb_shop');
        foreach (['kaishamei' => 'kaishamei', 'tantou' => 'tantou', 'mail' => 'email'] as $param => $column) {
            $value = LegacyText::trim((string) $request->query($param, ''));
            if ($value !== '') {
                $query->where($column, 'like', '%' . $value . '%');
            }
        }

        $disp = (string) $request->query('disp_flg', '');
        if ($disp === '') {
            $query->where('disp_flg', '1');
        } elseif ($disp !== 'all') {
            $query->where('disp_flg', $disp);
        }

        if ($selected) {
            $query->whereIn('cd', array_map('intval', $selected));
        }

        return $query;
    }

    /** shop_detail.php: 店舗・営業所詳細 */
    public function show(Request $request): View
    {
        $shop = Shop::withDeleted()->find((int) $request->input('shop_cd'));

        return view('admin.base.shops.show', $this->displayData($shop?->getAttributes() ?? []) + [
            'shop' => $shop,
            'isDevelop' => Gate::allows('nucleo.developer'),
            'page' => $request->input('page'),
            'kensu' => $request->input('kensu'),
        ]);
    }

    /** shop_new.php: alta o edición (con shop_cd); al volver de 確認 llegan los datos por POST */
    public function form(Request $request): View
    {
        $shopCd = (int) $request->input('shop_cd') ?: null;
        $this->authorizeNew($shopCd);

        $data = $shopCd ? (Shop::withDeleted()->find($shopCd)?->getAttributes() ?? []) : [];
        if ($request->isMethod('post') && $request->has('kaishamei')) {
            $data = array_merge($data, $request->only(ShopService::FIELDS));
        }
        $start = explode('<>', (string) ($data['start_work_time'] ?? ''));
        $data['start_work_time1'] ??= $start[0] ?? '';
        $data['start_work_time2'] ??= $start[1] ?? '';

        return view('admin.base.shops.form', $this->formData($data, $shopCd));
    }

    /** shop_new_confirm.php: valida; con error vuelve a mostrar el formulario marcado, si no la confirmación */
    public function confirm(Request $request): View
    {
        $shopCd = (int) $request->input('shop_cd') ?: null;
        $this->authorizeNew($shopCd);

        $data = $this->shops->normalize($request->all(), $this->legacyUi->isExtShopDomain());
        $check = $this->shops->check($data, $shopCd);

        if ($check->fails()) {
            return view('admin.base.shops.form', $this->formData($data, $shopCd) + ['check' => $check]);
        }

        return view('admin.base.shops.confirm', $this->displayData($data) + [
            'data' => $data,
            'shopCd' => $shopCd,
            'admin' => AdminMaster::current(),
            'extShop' => $this->legacyUi->isExtShopDomain(),
        ]);
    }

    /** shop_new_register.php: guarda y muestra 登録中です (refresca a shop_new_complete.php) */
    public function register(Request $request): View
    {
        $shopCd = (int) $request->input('shop_cd') ?: null;
        $this->authorizeNew($shopCd);
        abort_unless($request->filled('act'), 400);

        $shop = $this->shops->save($request->all(), $shopCd, $request->getHost());

        return view('admin.base.shops.wait', [
            'title' => '店舗登録情報 - ' . $this->legacyUi->siteName(),
            'heading' => '■店舗登録情報',
            'refresh' => 'shop_new_complete.php?shop_cd=' . $shop->cd,
            'message' => '登録中です。',
            'class' => 'textCenter',
        ]);
    }

    /** shop_new_complete.php: 登録情報は以下の通りです */
    public function complete(Request $request): View
    {
        $shop = Shop::withDeleted()->find((int) $request->query('shop_cd'));

        return view('admin.base.shops.complete', $this->displayData($shop?->getAttributes() ?? []));
    }

    /** shop_delete_register.php: 削除 (disp_flg 9 + reasignación a la tienda 1) o 削除を取り消す */
    public function delete(Request $request): View
    {
        Gate::authorize('nucleo.developer');
        $shopCd = (int) $request->input('shop_cd');

        if ($shopCd !== ShopService::HEAD_SHOP_CD && $request->input('delete_flg') === 'del') {
            $this->shops->delete($shopCd);
        } elseif ($request->input('delete_flg') === 'recover') {
            $this->shops->recover($shopCd);
        }

        return view('admin.base.shops.wait', [
            'title' => '店舗登録情報 - ' . $this->legacyUi->siteName(),
            'heading' => '■店舗登録情報 - ' . $this->legacyUi->siteName(),
            'refresh' => 'shop_list.php?se_flg=2',
            'message' => '削除中です。',
            'class' => 'center',
        ]);
    }

    /** El alta de tiendas solo con IP de desarrollo o support_master (en el legacy solo se ocultaba el enlace) */
    private function authorizeNew(?int $shopCd): void
    {
        if ($shopCd === null) {
            Gate::authorize('nucleo.shop-create');
        }
    }

    private function formData(array $data, ?int $shopCd): array
    {
        return [
            'data' => $data,
            'shopCd' => $shopCd,
            'categories' => ShopCategory::titles(),
            'admin' => AdminMaster::current(),
            'extShop' => $this->legacyUi->isExtShopDomain(),
            'siteName' => $this->legacyUi->siteName(),
        ];
    }

    /** Datos de las tablas de solo lectura (detalle, confirmación, completado) */
    private function displayData(array $data): array
    {
        $start = explode('<>', (string) ($data['start_work_time'] ?? ''));

        return [
            'd' => $data + ['start_work_time1' => $start[0] ?? '', 'start_work_time2' => $start[1] ?? ''],
            'categories' => ShopCategory::titles(),
            'areaName' => ($cd = (int) ($data['area_cd'] ?? 0)) ? Area::withDeleted()->find($cd)?->name : null,
            'admin' => AdminMaster::current(),
            'extShop' => $this->legacyUi->isExtShopDomain(),
        ];
    }
}
