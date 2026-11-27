<?php

namespace App\Services\Base;

use App\Models\ContentsMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * システム管理 (system/category0/config/system.php, mantenimiento): activación de módulos (tb_base_system*),
 * banderas de admin_master y valores de contents_master / tb_manage_words. El legacy no pasa estos formularios
 * por form2Db: se guardan tal cual llegan.
 */
class SystemSettingsService
{
    public const SMATOPI_DEVELOP = 'develop';

    public const SMATOPI_PRODUCTION = 'production';

    /** Índices de tb_base_system (por cd) que la pantalla no muestra: categorías 8 (女の子管理) y 10 */
    public const HIDDEN_CATEGORY_ROWS = [8, 10];

    /** Bloques que, tras guardar, vuelven a la pantalla en su ancla (como los header('Location') del legacy) */
    public const ANCHORS = [
        'change_master_cate' => 'menuHyoujiKanri', 'related' => 'karteRenketsu', 'pos_koubai_conf' => 'pos_koubai_conf',
        'mongon' => 'mongonKanri', 'shiire_ya_conf' => 'shiire_ya_conf', 'karute_time_disp_flg' => 'karteRenketsu',
        'atokara_disp_flg' => 'mypageCustomize',
    ];

    /** Aplica el bloque `act` / `act_flg`; devuelve un mensaje de texto si el legacy cortaba con echo + exit */
    public function apply(string $act, Request $request): ?string
    {
        $in = fn (string $key): string => is_array($request->input($key)) ? '' : (string) $request->input($key, '');

        switch ($act) {
            case 'change_master_cate':
                $this->saveMenu($request);
                break;
            case 'related':
                if ((int) $in('raiten_flg') === 1 && $in('karte_flg') === '') {
                    return 'カルテcdを入力してください。';
                }
                $this->admin(['related_features' => $in('raiten_flg') . '<>' . $in('point_flg') . '<>' . $in('karte_flg')]);
                break;
            case 'pos_koubai_conf':
                $this->admin(['pos_koubai_tb_search_form_cd' => $in('pos_koubai_tb_search_form_cd') !== '' ? (int) $in('pos_koubai_tb_search_form_cd') : null]);
                break;
            case 'mongon':
                $this->saveWord($in('naiyou'));
                break;
            case 'shiire_ya_conf':
                DB::table('contents_master')->where('category', 'shiire-ya')->where('field', 'btn_disp_flg')->where('disp_flg', '1')
                    ->update(['text_data' => $in('shiire-ya_link_address'), 'hyouji_flg' => $in('shiire-ya_btn_hyouji_flg'), 'update_date' => time()]);
                break;
            case 'base_system_version':
                $this->admin(['base_system_version_flg' => $in('base_system_version_flg') === '2' ? '2' : '']);
                break;
            case 'application_use_flg':
                $this->admin(['application_use_flg' => (int) $in('application_use_flg'), 'push_api_host' => (string) (int) $in('push_api_host')]);
                break;
            case 'beacon_use_flg':
            case 'ssl_flg':
                $this->admin([$act => (int) $in($act)]);
                break;
            case 'smatopi_environment':
                $this->setSmatopiEnvironment($in('smatopi_environment'));
                break;
            case 'app_layout_mode':
                ContentsMaster::putLegacy('mycolle', 'app_layout_mode', $in('app_layout_mode'));
                break;
            case 'karute_time_disp_flg':
                ContentsMaster::putLegacy('tb_c1_karute', 'time_disp_flg', $in('karute_time_disp_flg'));
                break;
            case 'atokara_disp_flg':
                foreach ((array) $request->input('atokara_disp_flg', []) as $shopCd => $value) {
                    if (preg_match('/^[0-9]+$/', (string) $shopCd)) {
                        ContentsMaster::putLegacy('tb_c3_coupon', 'atokara_disp_flg', (string) $value, false, (int) $shopCd);
                    }
                }
                break;
        }

        return null;
    }

    public function viewData(): array
    {
        $admin = (array) (DB::table('admin_master')->where('cd', 1)->first() ?? []);
        [$raiten, $point, $karte] = array_pad(explode('<>', (string) ($admin['related_features'] ?? '')), 3, '');

        return [
            'admin' => $admin,
            'version' => config('crm.version') ?: DB::table('tb_package_master')->orderBy('cd')->value('version'),
            'cdType' => DB::table('information_schema.columns')->where('table_catalog', DB::raw('current_database()'))
                ->where('table_name', 'tb_customer')->where('column_name', 'cd')->value('data_type'),
            'smatopiEnvironment' => $this->smatopiEnvironment(),
            'appLayoutMode' => ContentsMaster::text('mycolle', 'app_layout_mode'),
            'menu' => $this->menuTree(),
            'raitenFlg' => $raiten,
            'pointFlg' => $point,
            'karteFlg' => $karte,
            'karuteTimeDispFlg' => ContentsMaster::text('tb_c1_karute', 'time_disp_flg'),
            'atokaraShops' => DB::table('tb_shop as tb1')
                ->leftJoin('contents_master as tb2', function ($join) {
                    $join->on('tb1.cd', '=', 'tb2.int_data')->where('tb2.category', 'tb_c3_coupon')
                        ->where('tb2.field', 'atokara_disp_flg')->where('tb2.disp_flg', '1');
                })
                ->where('tb1.disp_flg', '1')->orderBy('tb1.kaishamei_kana')
                ->get(['tb1.cd as shop_cd', 'tb1.kaishamei', 'tb2.text_data']),
            'word' => DB::table('tb_manage_words')->where('content_name', 'c1_customer_detail[カルテ]')->value('naiyou'),
            'shiireYa' => DB::table('contents_master')->where('category', 'shiire-ya')->where('field', 'btn_disp_flg')
                ->where('disp_flg', '1')->orderByDesc('cd')->first(),
            'posKoubaiFields' => DB::table('tb_search_cate as tb1')->join('tb_search_form as tb2', 'tb2.tb_search_cate_cd', '=', 'tb1.cd')
                ->where('tb1.disp_flg', '1')->where('tb1.record_type', '1')->where('tb2.disp_flg', '1')->where('tb2.form_type', 'テキスト')
                ->orderBy('tb1.rank')->orderBy('tb2.rank')
                ->get(['tb1.name as cate_name', 'tb2.name as form_name', 'tb2.cd as tb_search_form_cd']),
        ];
    }

    /**
     * Árbol de メニュー表示 con los índices de los campos del legacy: categoría por posición (orden cd, sin las
     * posiciones 8 y 10), hijos y nietos numerados en orden de aparición.
     */
    private function menuTree(): array
    {
        $categories = DB::table('tb_base_system')->orderBy('cd')->get();
        $tree = [];
        $child = $grandchild = 0;
        foreach ($categories as $row => $category) {
            if (in_array($row, self::HIDDEN_CATEGORY_ROWS, true)) {
                continue;
            }
            $children = [];
            foreach (DB::table('tb_base_system_child')->where('tb_base_system_cd', $category->cd)->orderBy('cd')->get() as $c) {
                $grandchildren = [];
                foreach (DB::table('tb_base_system_grandchild')->where('tb_base_system_child_cd', $c->cd)->orderBy('cd')->get() as $g) {
                    $grandchildren[] = ['index' => $grandchild++, 'row' => $g];
                }
                $children[] = ['index' => $child++, 'row' => $c, 'grandchildren' => $grandchildren];
            }
            $tree[] = ['index' => $row, 'row' => $category, 'children' => $children];
        }

        return ['categories' => $tree, 'count' => $categories->count(), 'childMax' => $child, 'grandchildMax' => $grandchild];
    }

    /** change_master_cate: system_flg '1'/'0' por índice; sin cd numérico no actualiza (arreglo de la migración PHP 8) */
    private function saveMenu(Request $request): void
    {
        DB::transaction(function () use ($request) {
            $set = function (string $table, string $flgKey, string $cdKey) use ($request) {
                $cd = $request->input($cdKey);
                if (is_numeric($cd)) {
                    DB::table($table)->where('cd', (int) $cd)->update(['system_flg' => $request->input($flgKey) === '1' ? '1' : '0']);
                }
            };
            for ($i = 1; $i < (int) $request->input('change_tb_base_system_row'); $i++) {
                $set('tb_base_system', "category{$i}", "category_cd{$i}");
            }
            for ($i = 0; $i < (int) $request->input('child_max'); $i++) {
                $set('tb_base_system_child', "tb_base_system_child_flg{$i}", "tb_base_system_child_cd{$i}");
            }
            for ($i = 0; $i < (int) $request->input('grandchild_max'); $i++) {
                $set('tb_base_system_grandchild', "tb_base_system_grandchild_flg{$i}", "tb_base_system_grandchild_cd{$i}");
            }
        });
    }

    /** 文言管理: la fila cd = 8 'c1_customer_detail[カルテ]' (se crea si falta) */
    private function saveWord(string $naiyou): void
    {
        $name = 'c1_customer_detail[カルテ]';
        DB::transaction(function () use ($name, $naiyou) {
            if (! DB::table('tb_manage_words')->where('content_name', $name)->exists()) {
                DB::table('tb_manage_words')->insert(['cd' => 8, 'content_name' => $name]);
            }
            $now = (string) time();
            DB::table('tb_manage_words')->where('cd', 8)->where('content_name', $name)
                ->update(['naiyou' => $naiyou, 'hyouji_flg' => '1', 'touroku_date' => $now, 'update_date' => $now]);
        });
    }

    /** Smatopi::getEnvironment: 'develop' solo si está guardado así; cualquier otro valor es 'production' */
    private function smatopiEnvironment(): string
    {
        return ContentsMaster::text('smatopi', 'environment') === self::SMATOPI_DEVELOP ? self::SMATOPI_DEVELOP : self::SMATOPI_PRODUCTION;
    }

    /** Smatopi::setEnvironment: la fila vigente pasa a disp_flg = '0' y se inserta la nueva (historial) */
    private function setSmatopiEnvironment(string $env): void
    {
        $env = $env === self::SMATOPI_DEVELOP ? self::SMATOPI_DEVELOP : self::SMATOPI_PRODUCTION;
        DB::transaction(function () use ($env) {
            DB::table('contents_master')->where('category', 'smatopi')->where('field', 'environment')->where('disp_flg', '1')
                ->update(['disp_flg' => '0', 'update_date' => time()]);
            ContentsMaster::create(['category' => 'smatopi', 'field' => 'environment', 'text_data' => $env, 'disp_flg' => '1', 'hyouji_flg' => '1']);
        });
    }

    private function admin(array $values): void
    {
        DB::table('admin_master')->where('cd', 1)->update($values);
    }
}
