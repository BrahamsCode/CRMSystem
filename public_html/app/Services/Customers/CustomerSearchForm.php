<?php

namespace App\Services\Customers;

use App\Models\AdminMaster;
use App\Models\ContentsMaster;
use App\Models\Staff;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Datos de vista de los parciales del buscador de clientes (`admin/clientes/partials/search_*`):
 * lo que en el legacy calculaban `inc/search_set1.php`, `search_rireki_set1.php`,
 * `search_kaikei_set1.php`, `search_method_set1.php` y el bloque カスタマイズ検索 de `customer_list.php`.
 *
 * Lo usan 顧客一覧・検索 (S2) y las pantallas de 発行条件選択 de S3 (envíos, cupones, DM, encuestas):
 *   view('...', app(CustomerSearchForm::class)->viewData($staff, 'category3', $request->all(), $hiddenMenus))
 * y en el Blade: @include('admin.clientes.partials.search_set1') etc.
 */
class CustomerSearchForm
{
    /**
     * @param  Staff|null  $staff        personal de la sesión (null = master)
     * @param  string  $category         `tb_search_cate.category` de las categorías personalizadas ('category1' clientes, 'category3' promoción)
     * @param  array  $filters           valores actuales del buscador (request o búsqueda guardada)
     * @param  array  $hiddenSearchMenus menús plegados (cookie `hideSearchMenu`)
     */
    public function viewData(?Staff $staff = null, string $category = 'category1', array $filters = [], array $hiddenSearchMenus = []): array
    {
        $isMaster = $staff === null || $staff->isMaster();
        $shopCd = $isMaster ? null : (int) $staff->shop_cd;
        $admin = AdminMaster::current();
        $shops = $this->shops();

        return [
            'filters' => $filters,
            'hiddenSearchMenus' => $hiddenSearchMenus,
            // cKengen::setBaseSystem: versión 2 de la base pinta las celdas como <td>
            'cellTag' => (string) ($admin?->base_system_version_flg) === '2' ? 'td' : 'th',
            'isMaster' => $isMaster,
            'shopCd' => $shopCd,
            'shopName' => $shopCd ? $shops->firstWhere('cd', $shopCd)['kaishamei'] ?? '' : '',
            'shops' => $shops,
            // TODO(S1): componente común del árbol de áreas (Area::createAreaCheckUi); vacío hasta que exista
            'areaCheckUi' => '',
            'searchItems' => DB::table('tb_koumoku')->where('tb_name', 'tb_customer')->where('mail_mag_select', '1')
                ->orderBy('rank')->orderBy('cd')->get(['cd', 'tb_koumoku', 'tb_koumoku_name'])->map(fn ($r) => (array) $r),
            'jobs' => DB::table('tb_job')->whereRaw("COALESCE(disp_flg, '') <> '9'")->orderBy('rank')->orderBy('cd')->pluck('name'),
            'customerGroups' => $this->cdName('tb_customer_group', "disp_flg = '1'", ['rank', 'cd']),
            'raitenDoukis' => $this->cdName('tb_raiten_douki_master', "disp_flg = '1'", ['rank', 'cd']),
            'terminalNos' => DB::table('tb_customer')->whereNotNull('terminal_no')->where('terminal_no', '<>', '')
                ->distinct()->orderBy('terminal_no')->pluck('terminal_no'),
            'staffs' => $this->staffs($shopCd),
            'raitenRanks' => $this->cdName('tb_raiten_rank_master', "disp_flg = '1'", ['rank', 'cd'])->pluck('name', 'cd'),
            'kingakuRanks' => $this->cdName('tb_kingaku_rank_master', "disp_flg = '1'", ['rank', 'cd'])->pluck('name', 'cd'),
            'nameInputModeFlg' => ContentsMaster::text('tb_c1_customer', 'name_input_mode_flg') === '1',
            'applicationUseFlg' => (string) ($admin?->application_use_flg) === '1',
            'customSearchCategories' => $this->customSearchCategories($category),
            'yoyakuMenus' => $this->yoyakuMenus($shopCd),
            'raitenHistoryEnabled' => DB::table('tb_base_system_child')->where('system_name', '来店履歴管理')->where('system_flg', '1')->exists(),
            'kaikeiResource' => DB::table('tb_base_system_grandchild')->whereIn('system_name', ['ポイント管理', 'スタンプ会計処理'])
                ->where('system_flg', '1')->orderBy('cd')->value('system_name'),
            'minYear' => $this->minYear(),
        ];
    }

    /** Tiendas no borradas (`disp_flg <> '9'`), orden `cd`, con 'cd', 'kaishamei' y 'name' */
    private function shops(): Collection
    {
        return DB::table('tb_shop')->whereRaw("COALESCE(disp_flg, '') <> '9'")->orderBy('cd')->get(['cd', 'kaishamei'])
            ->map(fn ($r) => ['cd' => (int) $r->cd, 'kaishamei' => $r->kaishamei, 'name' => $r->kaishamei]);
    }

    private function cdName(string $table, string $where, array $order): Collection
    {
        $query = DB::table($table)->whereRaw($where);
        foreach ($order as $column) {
            $query->orderBy($column);
        }

        return $query->get(['cd', 'name'])->map(fn ($r) => ['cd' => (int) $r->cd, 'name' => $r->name]);
    }

    /** Personal con socios asignados (como search_set1.php); si no es master, solo su tienda */
    private function staffs(?int $shopCd): Collection
    {
        $query = DB::table('tb_staff as tb1')
            ->join('tb_customer as tb2', 'tb1.cd', '=', 'tb2.tb_staff_cd')
            ->leftJoin('tb_shop as s', 's.cd', '=', 'tb1.shop_cd')
            ->where('tb2.disp_flg', '1')
            ->groupBy('tb1.cd', 'tb1.name', 'tb1.shop_cd', 's.kaishamei')
            ->orderBy('tb1.shop_cd')->orderBy('tb1.cd')
            ->select('tb1.cd', 'tb1.name', 'tb1.shop_cd', 's.kaishamei as shop_kaishamei');
        if ($shopCd !== null) {
            $query->where('tb1.shop_cd', $shopCd);
        }

        return $query->get()->map(fn ($r) => (array) $r);
    }

    /** Categorías buscables con sus campos buscables (bloque カスタマイズ検索) */
    private function customSearchCategories(string $category): Collection
    {
        $categories = DB::table('tb_search_cate')
            ->where('category', $category)
            ->whereRaw("COALESCE(disp_flg, '') <> '9'")
            ->where('serach_flg', '1')->where('hyouji_flg', '1')
            ->orderBy('rank')->orderBy('cd')
            ->get(['cd', 'name']);

        $forms = DB::table('tb_search_form')
            ->whereIn('tb_search_cate_cd', $categories->pluck('cd'))
            ->whereRaw("COALESCE(disp_flg, '') <> '9'")
            ->where('serach_flg', '1')
            ->orderBy('rank')->orderBy('cd')
            ->get()->groupBy('tb_search_cate_cd');

        return $categories->map(fn ($c) => [
            'cd' => (int) $c->cd,
            'name' => $c->name,
            'forms' => ($forms[$c->cd] ?? collect())->map(fn ($f) => (array) $f)->values(),
        ]);
    }

    /** Menús reservables para el tipo メニュー項目 (como makeMenuTable) */
    private function yoyakuMenus(?int $shopCd): Collection
    {
        $query = DB::table('tb_yoyaku_koumoku_master as tb1')
            ->leftJoin('tb_yoyaku_koumoku_category as tb2', 'tb1.tb_yoyaku_koumoku_category_cd', '=', 'tb2.cd')
            ->where('tb1.disp_flg', '1')
            ->orderBy('tb1.type_flg')->orderBy('tb2.rank')->orderBy('tb1.tb_shop_cd')->orderBy('tb1.rank')
            ->select('tb1.cd', 'tb1.name', 'tb2.name as category_name');
        if ($shopCd !== null) {
            $query->whereIn('tb1.tb_shop_cd', [$shopCd, -1]);
        }

        return $query->get()->map(fn ($r) => (array) $r);
    }

    /** Año de la primera visita; el año actual si no hay datos válidos (el legacy daba 1970) */
    private function minYear(): int
    {
        $min = (int) DB::table('tb_raiten_rireki')->whereRaw("raiten_time ~ '^[0-9]+$'")->min(DB::raw('CAST(raiten_time AS bigint)'));
        $now = (int) now('Asia/Tokyo')->year;

        return $min > 0 ? min($now, (int) Carbon::createFromTimestamp($min, 'Asia/Tokyo')->year) : $now;
    }
}
