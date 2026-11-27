<?php

namespace Database\Seeders\Demo;

use App\Models\Area;
use App\Models\Operaciones\StaffGroup;
use App\Models\Shop;
use App\Models\ShopCategory;
use App\Models\Staff;
use App\Services\Nucleo\Hatuban;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Datos de demostración del núcleo (CONVENCIONES §4 «Datos de demostración»): 3 tiendas más con su
 * エリア y 店舗グループ, grupos de staff 店長/スタッフ con sus permisos (`tb_kengen`), 10 staff y los
 * módulos y opciones de menú activos para que salgan los menús de los 6 módulos.
 *
 * Idempotente: cada fila se busca por su clave natural (nombre de tienda, área, grupo, `login_id`) antes
 * de crearla. No toca la tienda ni el staff del backup (master conserva su clave). Lo único que cambia de
 * lo existente es configuración: `system_flg` de módulos y opciones de menú, banderas de admin_master y el 歩合
 * de master si no tiene.
 *
 * Staff de demo: contraseña común DEMO_PASSWORD; `tencho_<tienda>` (店長, todos los permisos) y
 * `<tienda>01/02` (スタッフ, sin borrar ni CSV y solo ver スタッフ管理); `qa_partial` (QA部分権限, LIMITED_PASSWORD:
 * solo 閲覧 y nada en un contenido por módulo) y `taishoku01` (退職). Nombres fijos (no Faker) para que kanji y
 * kana coincidan; correos @example.com y teléfonos 090-0000-xxxx. 歩合 (kingaku_buai) según BUAI.
 *
 * Casos límite: 心斎橋店 de baja (disp_flg 9), una tienda con nombre de 255 caracteres y 天王寺店 sin campos
 * opcionales (sin área ni grupo).
 */
class NucleoDemoSeeder extends Seeder
{
    public const DEMO_PASSWORD = 'demo1234';

    /** Módulos visibles (tb_base_system): cd => [nombre, narabi] del legacy */
    private const MODULES = [
        1 => ['顧客管理', 1], 3 => ['販売促進管理', 3], 6 => ['マイページ管理', 4], 5 => ['業務支援管理', 6],
        7 => ['端末管理', 7], 9 => ['予約管理', 8], 0 => ['基本情報管理', 11],
    ];

    /**
     * Opciones de menú (tb_base_system_child) que piden los config/menu/*.php: módulo => nombres.
     * Fuera a propósito: ビーコン管理 (en pausa, sin pantallas), スマトピ連携 (oculto hasta F8), ガチャリン.
     */
    private const CHILDREN = [
        1 => ['顧客管理', '入退室管理', '初期設定管理', 'カルテ管理'],
        3 => ['メルマガ管理', 'デコメール管理', 'プッシュ通知', 'フォローメール管理', '自動メール管理', 'マイクーポン管理',
            'スタンプ管理', 'ポイント管理', 'アンケート管理', '来店履歴管理', '予約管理'],
        5 => ['スタッフ管理', '勤怠管理', 'スケジュール管理', '初期設定管理'],
        6 => ['マイページ管理', '求人情報管理'],
        7 => ['電子看板管理', 'ペーパーサイネージ', 'ICカード管理'],
        9 => ['予約管理'],
    ];

    private const SHOP_CATEGORIES = ['直営店', 'フランチャイズ'];

    /** Áreas: nombre => padre (null = raíz) */
    private const AREAS = ['関西' => null, '大阪' => '関西', '京都' => '関西'];

    private const SHOPS = [
        'umeda' => [
            'kaishamei' => '梅田店', 'kaishamei_kana' => 'ウメダテン', 'zip' => '5300001', 'pref' => '大阪府',
            'add' => '大阪市北区梅田', 'add_kana' => 'オオサカシキタクウメダ', 'add_number' => '1-2-3',
            'add_build' => 'デモ梅田ビル3F', 'add_build_kana' => 'デモウメダビル3F', 'tel' => '09000001001',
            'email' => 'umeda@example.com', 'area' => '大阪', 'category' => '直営店',
            'latitude' => 34.7025, 'longitude' => 135.4959,
        ],
        'namba' => [
            'kaishamei' => '難波店', 'kaishamei_kana' => 'ナンバテン', 'zip' => '5420076', 'pref' => '大阪府',
            'add' => '大阪市中央区難波', 'add_kana' => 'オオサカシチュウオウクナンバ', 'add_number' => '4-5-6',
            'add_build' => 'デモなんばビル1F', 'add_build_kana' => 'デモナンバビル1F', 'tel' => '09000001002',
            'email' => 'namba@example.com', 'area' => '大阪', 'category' => '直営店',
            'latitude' => 34.6659, 'longitude' => 135.5013,
        ],
        'kyoto' => [
            'kaishamei' => '京都店', 'kaishamei_kana' => 'キョウトテン', 'zip' => '6008216', 'pref' => '京都府',
            'add' => '京都市下京区東塩小路町', 'add_kana' => 'キョウトシシモギョウクヒガシシオコウジチョウ', 'add_number' => '7-8',
            'add_build' => 'デモ京都駅前ビル2F', 'add_build_kana' => 'デモキョウトエキマエビル2F', 'tel' => '09000001003',
            'email' => 'kyoto@example.com', 'area' => '京都', 'category' => 'フランチャイズ',
            'latitude' => 34.9858, 'longitude' => 135.7588,
        ],
        // Casos límite (CONVENCIONES §4): tienda de baja (como 削除 de shop_delete_register.php), nombre largo hasta
        // el máximo de kaishamei (varchar 255) y una con solo los campos que pide el alta (sin área ni grupo)
        'shinsaibashi' => [
            'kaishamei' => '心斎橋店', 'kaishamei_kana' => 'シンサイバシテン', 'zip' => '5420085', 'pref' => '大阪府',
            'add' => '大阪市中央区心斎橋筋', 'add_kana' => 'オオサカシチュウオウクシンサイバシスジ', 'add_number' => '2-1',
            'tel' => '09000001004', 'email' => 'shinsaibashi@example.com', 'area' => '大阪', 'category' => '直営店',
            'disp_flg' => '9',
        ],
        'long' => [
            'kaishamei' => null, 'kaishamei_kana' => 'デモチョウメイテンポ', 'zip' => '5300011', 'pref' => '大阪府',
            'add' => '大阪市北区大深町', 'add_kana' => 'オオサカシキタクオオフカチョウ', 'add_number' => '9-9-9',
            'add_build' => 'デモグランフロント北館タワーＣ２７階デモ長名称テナント区画', 'tel' => '09000001005',
            'email' => 'long@example.com', 'area' => '大阪', 'category' => 'フランチャイズ',
        ],
        'tennoji' => ['kaishamei' => '天王寺店', 'kaishamei_kana' => 'テンノウジテン'],
    ];

    /** Base del nombre largo: se repite hasta 255 caracteres, el máximo de tb_shop.kaishamei (ver longName()) */
    private const LONG_NAME_BASE = 'デモ長名称店舗ヴィヴァテック関西エリア統括本部直営大阪北区大深町グランフロント北館店';

    /**
     * Staff: [login_id, tienda (null = la tienda 1 del backup), grupo, nombre, kana, 性別, 雇用形態, 生年月日, 入社日, extras].
     * extras: `pass` (por defecto DEMO_PASSWORD) y `disp_flg` (9 = 退職, como staff_delete_register.php).
     */
    private const STAFF = [
        ['tencho_honten', null, '店長', '佐藤 健一', 'サトウ ケンイチ', '男性', '正社員', '1978/04/12', '2008/04/01'],
        ['tencho_umeda', 'umeda', '店長', '高橋 美咲', 'タカハシ ミサキ', '女性', '正社員', '1985/09/03', '2012/04/01'],
        ['umeda01', 'umeda', 'スタッフ', '田中 翔太', 'タナカ ショウタ', '男性', '正社員', '1995/01/20', '2018/04/01'],
        ['umeda02', 'umeda', 'スタッフ', '伊藤 さくら', 'イトウ サクラ', '女性', 'アルバイト', '2001/03/28', '2023/06/15'],
        ['tencho_namba', 'namba', '店長', '渡辺 大輔', 'ワタナベ ダイスケ', '男性', '正社員', '1982/11/07', '2010/10/01'],
        ['namba01', 'namba', 'スタッフ', '山本 優花', 'ヤマモト ユウカ', '女性', '正社員', '1993/07/14', '2016/04/01'],
        ['namba02', 'namba', 'スタッフ', '中村 拓也', 'ナカムラ タクヤ', '男性', 'パート', '1990/05/02', '2021/09/01'],
        ['tencho_kyoto', 'kyoto', '店長', '小林 由美', 'コバヤシ ユミ', '女性', '正社員', '1980/02/25', '2009/04/01'],
        ['kyoto01', 'kyoto', 'スタッフ', '加藤 陽菜', 'カトウ ヒナ', '女性', '契約社員', '1997/12/09', '2020/04/01'],
        ['kyoto02', 'kyoto', 'スタッフ', '吉田 蓮', 'ヨシダ レン', '男性', 'アルバイト', '2002/08/18', '2024/04/01'],
        // Usuario limitado para probar permisos (como el antiguo qa_partial de crmsystem2_nucleo, que migrate:fresh borra)
        ['qa_partial', 'umeda', self::LIMITED_GROUP, '品質 確認', 'ヒンシツ カクニン', '女性', '契約社員', '1999/02/28', '2025/04/01', ['pass' => self::LIMITED_PASSWORD]],
        // 退職 (disp_flg 9): no entra ni sale en listas de activos
        ['taishoku01', 'namba', 'スタッフ', '松本 隆', 'マツモト タカシ', '男性', 'アルバイト', '1998/06/30', '2019/04/01', ['disp_flg' => '9']],
        // Kana de ancho medio y la tienda de nombre largo
        ['long01', 'long', 'スタッフ', '長谷川 千代子', 'ﾊｾｶﾞﾜ ﾁﾖｺ', '女性', 'パート', '1972/02/29', '2016/02/29'],
    ];

    public const LIMITED_GROUP = 'QA部分権限';

    public const LIMITED_PASSWORD = 'qa12345';

    /** 歩合 (tb_staff.kingaku_buai, %) por grupo; master recibe el de 店長 si no tiene */
    private const BUAI = ['店長' => 15, 'スタッフ' => 10, self::LIMITED_GROUP => 5];

    /**
     * QA部分権限: 閲覧 solo en todo (sin alta, edición ni borrado) y nada en un contenido de cada módulo con kengen:
     * 1002 csv出力 (顧客管理), 1009 クーポン管理 (販売促進管理), 1035 マイコレ管理 (マイページ管理), 1027 予約項目管理 y
     * 1029 予約設定 (予約管理). 1024 スタッフ管理 queda en solo 閲覧 (業務支援管理: sin スタッフ新規登録). 端末管理 no
     * usa kengen en el legacy y 基本情報管理 es solo de master.
     */
    private const LIMITED_NONE = [1002, 1009, 1027, 1029, 1035];

    /** Contenidos de permiso que スタッフ no tiene enteros: cd => [閲覧, 新規, 編集, 削除] */
    private const STAFF_LIMITS = [1002 => [0, 0, 0, 0], 1024 => [1, 0, 0, 0], 1034 => [0, 0, 0, 0]];

    public function __construct(private readonly Hatuban $hatuban) {}

    public function run(): void
    {
        DB::transaction(function () {
            $this->modules();
            $categories = $this->shopCategories();
            $areas = $this->areas();
            $shops = $this->shops($categories, $areas);
            $groups = $this->staffGroups();
            $this->staff($shops, $groups);
        });
    }

    /** Módulos y opciones de menú activos; admin_master con UI 2 y la app activa (menús de マイコレ y プッシュ通知) */
    private function modules(): void
    {
        foreach (self::MODULES as $cd => [$name, $narabi]) {
            DB::table('tb_base_system')->where('cd', $cd)->exists()
                ? DB::table('tb_base_system')->where('cd', $cd)->update(['system_flg' => '1'])
                : DB::table('tb_base_system')->insert(['cd' => $cd, 'system_name' => $name, 'system_flg' => '1', 'narabi' => $narabi]);
        }

        foreach (self::CHILDREN as $module => $names) {
            foreach ($names as $name) {
                $query = DB::table('tb_base_system_child')->where('tb_base_system_cd', $module)->where('system_name', $name);
                if ($query->exists()) {
                    $query->update(['system_flg' => '1']);

                    continue;
                }
                // Sin numeración propia en el legacy: el siguiente cd libre, al final de su módulo
                DB::table('tb_base_system_child')->insert([
                    'cd' => (int) DB::table('tb_base_system_child')->max('cd') + 1,
                    'tb_base_system_cd' => $module,
                    'system_name' => $name,
                    'system_flg' => '1',
                    'rank' => (int) DB::table('tb_base_system_child')->where('tb_base_system_cd', $module)->max('rank') + 1,
                ]);
            }
        }

        // UI 2 (ペーパーサイネージ), app (マイコレ, QR, プッシュ通知) y ビーコン使用 (ポイントルール一覧, ポイント集計; solo
        // banderas, no conecta a la BD de beacons). El modo ext-shop (contents_master mycolle/app_layout_mode) se deja
        // como esté: cambia campos de muchas pantallas, no es una bandera de menú.
        DB::table('admin_master')->where('cd', 1)->update([
            'base_system_version_flg' => '2', 'application_use_flg' => '1', 'beacon_use_flg' => '1',
        ]);
    }

    /** @return array<string, int> título => cd */
    private function shopCategories(): array
    {
        $cds = [];
        foreach (self::SHOP_CATEGORIES as $title) {
            $category = ShopCategory::query()->where('title', $title)->where('flg', 1)->first()
                ?? ShopCategory::create([
                    'title' => $title,
                    'flg' => 1,
                    'sort' => ShopCategory::query()->where('flg', 1)->count() + 1,
                    'record_date' => now()->format('Y-m-d H:i:s'),
                    'update_date' => now()->format('Y-m-d H:i:s'),
                ]);
            $cds[$title] = $category->cd;
        }

        return $cds;
    }

    /** @return array<string, int> nombre => cd */
    private function areas(): array
    {
        $cds = [];
        foreach (self::AREAS as $name => $parent) {
            $pid = $parent === null ? null : $cds[$parent];
            $siblings = fn () => Area::query()->when($pid, fn ($q) => $q->where('pid', $pid), fn ($q) => $q->whereNull('pid'));
            $area = $siblings()->where('name', $name)->first()
                ?? Area::create([
                    'pid' => $pid,
                    'name' => $name,
                    'comment' => '',
                    'flg' => '1',
                    'disp_flg' => '1',
                    'row_lis' => (int) $siblings()->max('row_lis') + 1,
                ]);
            $cds[$name] = $area->cd;
        }

        return $cds;
    }

    /** Nombre de 255 caracteres (multibyte) para la tienda de nombre largo */
    public static function longName(): string
    {
        return mb_substr(str_repeat(self::LONG_NAME_BASE, 10), 0, 255);
    }

    /** @return array<string, int> clave => cd */
    private function shops(array $categories, array $areas): array
    {
        $cds = [];
        foreach (self::SHOPS as $key => $data) {
            $data['kaishamei'] ??= self::longName();
            $staff = collect(self::STAFF)->first(fn ($s) => $s[1] === $key && $s[2] === '店長');
            $shop = Shop::withDeleted()->where('kaishamei', $data['kaishamei'])->first() // también la de baja
                ?? Shop::create([
                    'master_cd' => 1,
                    'kaishamei' => $data['kaishamei'],
                    'kaishamei_kana' => $data['kaishamei_kana'],
                    'zip' => $data['zip'] ?? '',
                    'pref' => $data['pref'] ?? '',
                    'add' => $data['add'] ?? '',
                    'add_kana' => $data['add_kana'] ?? '',
                    'add_number' => $data['add_number'] ?? '',
                    'add_build' => $data['add_build'] ?? '',
                    'add_build_kana' => $data['add_build_kana'] ?? '',
                    'tel' => $data['tel'] ?? '',
                    'mycolle_tel' => $data['tel'] ?? '',
                    'fax' => '',
                    'email' => $data['email'] ?? '',
                    'shop_url' => '',
                    'tantou' => $staff[3] ?? '',
                    'tantou_kana' => $staff[4] ?? '',
                    'kyuyo_marume' => '15',
                    'start_work_time' => '10<>00',
                    'break_time' => '60',
                    'shop_category_cd' => isset($data['category']) ? $categories[$data['category']] : null,
                    'area_cd' => isset($data['area']) ? $areas[$data['area']] : null,
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'is_closed' => 0,
                    'is_new_store' => 0,
                    'beacon_visiting_area' => 0,
                    'beacon_accounting_area' => 0,
                    'disp_flg' => $data['disp_flg'] ?? '1',
                ]);
            $cds[$key] = $shop->cd;
        }

        return $cds;
    }

    /** Grupos 店長/スタッフ/QA部分権限 y su fila de tb_kengen por contenido visible (como 権限管理) */
    private function staffGroups(): array
    {
        $cds = [];
        foreach (['店長', 'スタッフ', self::LIMITED_GROUP] as $name) {
            $group = StaffGroup::query()->where('name', $name)->first()
                ?? StaffGroup::create([
                    'name' => $name,
                    'rank' => (int) StaffGroup::query()->where('disp_flg', '1')->max('rank') + 1,
                    'disp_flg' => '1',
                ]);
            $cds[$name] = $group->cd;

            $existing = DB::table('tb_kengen')->where('tb_staff_group_cd', $group->cd)->pluck('tb_kengen_contents_cd')->map(fn ($cd) => (int) $cd)->all();
            $now = (string) time();
            foreach (DB::table('tb_kengen_contents')->where('hyouji_flg', '1')->orderBy('cd')->pluck('cd') as $contentsCd) {
                if (in_array((int) $contentsCd, $existing, true)) {
                    continue;
                }
                $flags = match ($name) {
                    '店長' => [1, 1, 1, 1],
                    'スタッフ' => self::STAFF_LIMITS[(int) $contentsCd] ?? [1, 1, 1, 0],
                    default => in_array((int) $contentsCd, self::LIMITED_NONE, true) ? [0, 0, 0, 0] : [1, 0, 0, 0],
                };
                DB::table('tb_kengen')->insert([
                    'cd' => $this->hatuban->next('tb_kengen'),
                    'tb_staff_group_cd' => $group->cd,
                    'tb_kengen_contents_cd' => $contentsCd,
                    'etsuran_flg' => (string) $flags[0],
                    'shinki_flg' => (string) $flags[1],
                    'shusei_flg' => (string) $flags[2],
                    'sakujo_flg' => (string) $flags[3],
                    'touroku_date' => $now,
                    'update_date' => $now,
                ]);
            }
        }

        return $cds;
    }

    /** Staff como スタッフ新規登録: cd createHatsuban, スタッフID createID, contador hatuban tb_staff */
    private function staff(array $shops, array $groups): void
    {
        $headShop = (int) DB::table('tb_shop')->where('disp_flg', '1')->orderBy('cd')->value('cd');
        foreach (self::STAFF as $i => [$loginId, $shopKey, $group, $name, $kana, $sex, $employment, $birthday, $entrance]) {
            $extras = self::STAFF[$i][9] ?? [];
            $buai = self::BUAI[$group];
            if (Staff::withDeleted()->where('login_id', $loginId)->exists()) {
                // Sembrado por la versión anterior de este seeder, con 歩合 0: se completa
                DB::table('tb_staff')->where('login_id', $loginId)->where(fn ($q) => $q->whereNull('kingaku_buai')->orWhere('kingaku_buai', 0))
                    ->update(['kingaku_buai' => $buai]);

                continue;
            }
            $shopCd = $shopKey === null ? $headShop : $shops[$shopKey];
            $this->hatuban->next('tb_staff');
            $count = DB::table('tb_staff')->count() + 1;
            $now = (string) time();

            DB::table('tb_staff')->insert([
                'cd' => (int) (time() . '000' . sprintf('%06d', mt_rand(0, 999999))),
                'office_cd' => 1,
                'shop_cd' => $shopCd,
                'id' => $shopCd . '1' . sprintf('%05d', $count),
                'login_id' => $loginId,
                'login_pass' => $extras['pass'] ?? self::DEMO_PASSWORD,
                'rank_pass' => '',
                'name' => $name,
                'name_kana' => $kana,
                'sex' => $sex,
                'birthday' => $birthday,
                'entranceday' => $entrance,
                'employmentsystem' => $employment,
                'email1' => $loginId . '@example.com',
                'mtel1' => sprintf('090-0000-%04d', 2001 + $i),
                'reserve_flg' => '1',
                'tb_staff_group_cd' => $groups[$group],
                'pos_rank' => 10,
                'kingaku_buai' => $buai,
                'synchronize_flg' => '',
                'touroku_date' => $now,
                'update_date' => $now,
                'disp_flg' => $extras['disp_flg'] ?? Staff::ACTIVE_FLAG,
            ]);
        }

        // Pedido de S2 (informes de 歩合 en 0): master recibe el de 店長 solo si no tiene, para no pisar un valor real
        DB::table('tb_staff')->where('login_id', 'master')->where(fn ($q) => $q->whereNull('kingaku_buai')->orWhere('kingaku_buai', 0))
            ->update(['kingaku_buai' => self::BUAI['店長']]);
    }
}
