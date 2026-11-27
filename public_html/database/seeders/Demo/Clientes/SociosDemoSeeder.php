<?php

namespace Database\Seeders\Demo\Clientes;

use App\Models\Customers\CustomCategory;
use App\Models\Customers\CustomerLevel;
use App\Models\Customers\CustomField;
use App\Models\Customers\ServiceCategory;
use App\Models\Customers\VisitMotive;
use App\Models\Promotions\CustomerGroup;
use App\Models\Promotions\KingakuRankMaster;
use App\Models\Promotions\RaitenRankMaster;
use App\Services\Customers\CustomerPhotoImageStore;
use Carbon\CarbonImmutable;
use Faker\Factory as FakerFactory;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Datos de demostración (S2, parte D1): socios de un salón de belleza con su historial.
 *
 * - 300 socios en `tb_customer` (`email1` = demo{n}@example.com, móvil 090-0000-xxxx), repartidos entre las
 *   tiendas activas, con 指名担当者, グループ, 来店動機, レベル, ランク (金額 y 来店), 紹介者 y algunos 有料会員.
 * - Visitas (`tb_raiten_rireki`, 0–20 por socio en los últimos 12 meses) con su ポイント (`tb_point_log` con
 *   会計, como kanren_record_register.php) y su スタンプ (`tb_stamp_log`, como PromocionVisitLoyalty), los saldos
 *   (`tb_customer_point`, `tb_customer.stamp_count`) y los resúmenes del socio (last_raiten_date, shuki_heikin).
 * - カルテ (`tb_karute`), マイアルバム (`tb_photo_album` + imágenes pequeñas con GD en el disco public) y una
 *   categoría personalizada propia 「お客様情報」 (`tb_search_cate`/`tb_search_form`) con sus valores (`tb_customer_sub`).
 * - Maestros que falten (sin tocar los existentes): rangos, grupos, 来店動機, レベル y 施術カテゴリー.
 *
 * Idempotente: el marcador es el socio con email1 = demo1@example.com; si existe, no hace nada.
 * Reglas comunes en CONVENCIONES.md §4 «Datos de demostración».
 */
class SociosDemoSeeder extends Seeder
{
    public const TOTAL = 300;

    public const MARKER_EMAIL = 'demo1@example.com';

    /** Nombre de la categoría personalizada propia (si ya existe una con ese nombre, no se crea otra) */
    public const CUSTOM_CATEGORY = 'お客様情報';

    private const SEED = 20261203;

    /** Socios con fotos en マイアルバム (cada uno 1–2 filas con imagen) */
    private const PHOTO_CUSTOMERS = 12;

    /**
     * Clientes extra con casos límite (demo301…): n => caso. Ver applyEdgeCases().
     */
    public const EDGE_CASES = [
        301 => 'baja',            // disp_flg 9 (como CustomerDeletionService), con visitas anteriores
        302 => 'baja',            // disp_flg 9, sin visitas, メルマガ no
        303 => 'provisional',     // touroku_kubun 1 (仮登録), sin visitas
        304 => 'provisional0',    // touroku_kubun 0, sin visitas
        305 => 'mailError',       // mailmaga_flg 9 (不達配信) con error_send_cnt
        306 => 'long',            // japonés largo hasta el máximo de cada columna varchar(255)
        307 => 'halfKana',        // カナ de ancho medio (ｶﾅ) en nombre y dirección
        308 => 'empty',           // campos opcionales vacíos (cumpleaños '//', sin teléfono ni dirección)
        309 => 'leap',            // cumpleaños 29/02
        310 => 'leap',
        311 => 'monthEnds',       // visitas el último día de cada mes, el 31/12 y el 1/1
    ];

    /** Apellidos [漢字, カナ] (Faker ja_JP no empareja el kanji con su lectura) */
    private const LAST_NAMES = [
        ['佐藤', 'サトウ'], ['鈴木', 'スズキ'], ['高橋', 'タカハシ'], ['田中', 'タナカ'], ['伊藤', 'イトウ'], ['渡辺', 'ワタナベ'],
        ['山本', 'ヤマモト'], ['中村', 'ナカムラ'], ['小林', 'コバヤシ'], ['加藤', 'カトウ'], ['吉田', 'ヨシダ'], ['山田', 'ヤマダ'],
        ['佐々木', 'ササキ'], ['山口', 'ヤマグチ'], ['松本', 'マツモト'], ['井上', 'イノウエ'], ['木村', 'キムラ'], ['林', 'ハヤシ'],
        ['斎藤', 'サイトウ'], ['清水', 'シミズ'], ['山崎', 'ヤマザキ'], ['森', 'モリ'], ['池田', 'イケダ'], ['橋本', 'ハシモト'],
        ['阿部', 'アベ'], ['石川', 'イシカワ'], ['山下', 'ヤマシタ'], ['中島', 'ナカジマ'], ['石井', 'イシイ'], ['小川', 'オガワ'],
        ['前田', 'マエダ'], ['岡田', 'オカダ'], ['長谷川', 'ハセガワ'], ['藤田', 'フジタ'], ['後藤', 'ゴトウ'], ['近藤', 'コンドウ'],
    ];

    private const FEMALE_NAMES = [
        ['陽子', 'ヨウコ'], ['美咲', 'ミサキ'], ['さくら', 'サクラ'], ['花子', 'ハナコ'], ['真由美', 'マユミ'], ['恵', 'メグミ'],
        ['愛', 'アイ'], ['結衣', 'ユイ'], ['葵', 'アオイ'], ['奈々', 'ナナ'], ['彩', 'アヤ'], ['舞', 'マイ'], ['優子', 'ユウコ'],
        ['明美', 'アケミ'], ['由美子', 'ユミコ'], ['千尋', 'チヒロ'], ['麻衣', 'マイ'], ['陽菜', 'ヒナ'], ['杏', 'アン'],
        ['美穂', 'ミホ'], ['香織', 'カオリ'], ['智子', 'トモコ'], ['直美', 'ナオミ'], ['遥', 'ハルカ'], ['凛', 'リン'], ['桃子', 'モモコ'],
    ];

    private const MALE_NAMES = [
        ['翔太', 'ショウタ'], ['大輔', 'ダイスケ'], ['健一', 'ケンイチ'], ['拓也', 'タクヤ'], ['直樹', 'ナオキ'], ['蓮', 'レン'],
        ['悠斗', 'ユウト'], ['陽翔', 'ハルト'], ['誠', 'マコト'], ['亮', 'リョウ'], ['和也', 'カズヤ'], ['隆', 'タカシ'],
        ['浩二', 'コウジ'], ['大樹', 'ダイキ'], ['健太', 'ケンタ'], ['修', 'オサム'],
    ];

    private Generator $faker;

    private CarbonImmutable $now;

    public function run(): void
    {
        if (DB::table('tb_customer')->where('email1', self::MARKER_EMAIL)->exists()) {
            $this->command?->info('SociosDemoSeeder: ya sembrado (marcador), se omite.');

            return;
        }

        $this->faker = FakerFactory::create('ja_JP');
        $this->faker->seed(self::SEED);
        $this->now = CarbonImmutable::now('Asia/Tokyo');

        $photos = DB::transaction(function () {
            $shops = $this->shops();
            $staff = $this->staffByShop($shops);
            $masters = $this->seedMasters();

            $customers = $this->buildCustomers($shops, $staff, $masters);
            $history = $this->buildHistory($customers, $staff, $shops);
            $this->applySummaries($customers, $history, $masters);

            $this->insertChunks('tb_customer', array_map(fn ($c) => $c['row'], $customers));
            $this->insertChunks('tb_raiten_rireki', $history['visits']);
            $this->insertChunks('tb_point_log', $history['points']);
            $this->insertChunks('tb_stamp_log', $history['stamps']);
            $this->insertChunks('tb_customer_point', $history['balances']);

            $this->seedKartes($customers, $history['byCustomer'], $masters['serviceCategories']);
            $this->seedCustomFields($customers, $masters['customFields']);

            return $this->seedPhotoRows($customers, $history['byCustomer']);
        });

        // Las imágenes van al disco después de confirmar las filas
        $this->storePhotoImages($photos);
    }

    // ---------------------------------------------------------------- base

    /** Tiendas activas (si no hay ninguna marcada, las no borradas) */
    private function shops(): array
    {
        $cds = DB::table('tb_shop')->where('disp_flg', '1')->orderBy('cd')->pluck('cd');
        if ($cds->isEmpty()) {
            $cds = DB::table('tb_shop')->whereRaw("COALESCE(disp_flg, '') <> '9'")->orderBy('cd')->pluck('cd');
        }
        if ($cds->isEmpty()) {
            throw new \RuntimeException('SociosDemoSeeder: no hay tiendas (tb_shop).');
        }

        return $cds->map(fn ($cd) => (int) $cd)->all();
    }

    /** [shop_cd => [staff_cd…]] con el staff activo; la tienda sin staff usa el de todas (al menos el master) */
    private function staffByShop(array $shops): array
    {
        $rows = DB::table('tb_staff')->where('disp_flg', '1')->orderBy('cd')->get(['cd', 'shop_cd']);
        if ($rows->isEmpty()) {
            $rows = DB::table('tb_staff')->orderBy('cd')->get(['cd', 'shop_cd']);
        }
        $all = $rows->pluck('cd')->map(fn ($cd) => (int) $cd)->all();

        $byShop = [];
        foreach ($shops as $shop) {
            $own = $rows->where('shop_cd', $shop)->pluck('cd')->map(fn ($cd) => (int) $cd)->all();
            $byShop[$shop] = $own ?: $all;
        }

        return $byShop;
    }

    /**
     * Reserva un bloque de números de `hatuban` (como Hatuban::next, pero de una vez). Devuelve el primero.
     */
    private function reserveCds(string $koumoku, int $count): int
    {
        $kazu = (int) DB::table('hatuban')->where('koumoku', $koumoku)->lockForUpdate()->value('kazu');
        $first = max($kazu, 1);
        DB::table('hatuban')->where('koumoku', $koumoku)->delete();
        DB::table('hatuban')->insert(['koumoku' => $koumoku, 'kazu' => $first + $count]);

        return $first;
    }

    private function insertChunks(string $table, array $rows): void
    {
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }

    // ---------------------------------------------------------------- maestros

    /** Crea solo los maestros que falten; devuelve los que usan los socios */
    private function seedMasters(): array
    {
        $kingaku = $this->rankMaster(KingakuRankMaster::class, [
            ['ブロンズ', 0, 29999], ['シルバー', 30000, 79999], ['ゴールド', 80000, 149999], ['プラチナ', 150000, 99999999],
        ]);
        $raiten = $this->rankMaster(RaitenRankMaster::class, [
            ['ビギナー', 0, 2], ['レギュラー', 3, 7], ['ロイヤル', 8, 13], ['VIP', 14, 9999],
        ]);

        return [
            'kingakuRanks' => $kingaku,
            'raitenRanks' => $raiten,
            'groups' => $this->simpleMaster(CustomerGroup::class, ['一般', '学生', 'ファミリー'], ['default_flg' => '']),
            'motives' => $this->simpleMaster(VisitMotive::class, ['ホットペッパー', 'Instagram', 'ご紹介', '通りがかり', 'ホームページ', 'チラシ']),
            'levels' => $this->simpleMaster(CustomerLevel::class, ['新規', 'リピーター', '常連']),
            'serviceCategories' => $this->serviceCategories(),
            'customFields' => $this->customCategory(),
        ];
    }

    /** Rangos activos [[cd, jyouken1, jyouken2]…]; si no hay ninguno, crea los de demostración */
    private function rankMaster(string $model, array $defaults): array
    {
        if (! $model::query()->where('disp_flg', '1')->exists()) {
            foreach ($defaults as $i => [$name, $from, $to]) {
                $model::create(['name' => $name, 'jyouken1' => $from, 'jyouken2' => $to, 'disp_flg' => '1', 'hyouji_flg' => '1', 'rank' => $i + 1]);
            }
        }

        return $model::query()->where('disp_flg', '1')->orderBy('rank')->orderBy('cd')->get(['cd', 'jyouken1', 'jyouken2'])
            ->map(fn ($r) => [(int) $r->cd, $r->jyouken1, $r->jyouken2])->all();
    }

    /** cd activos de un maestro con nombre/rank; si está vacío, crea los de demostración */
    private function simpleMaster(string $model, array $names, array $extra = []): array
    {
        if (! $model::query()->where('disp_flg', '1')->exists()) {
            foreach ($names as $i => $name) {
                $attributes = ['name' => $name, 'rank' => $i + 1, 'disp_flg' => '1'] + $extra;
                if (array_key_exists('default_flg', $extra)) {
                    $attributes['default_flg'] = $i === 0 ? '1' : '';
                }
                $model::create($attributes);
            }
        }

        return $model::query()->where('disp_flg', '1')->orderBy('rank')->pluck('cd')->map(fn ($cd) => (int) $cd)->all();
    }

    /** 施術カテゴリー [nombre => cd] (las crea el catálogo de D2; si no hay, se crean las básicas) */
    private function serviceCategories(): array
    {
        if (! ServiceCategory::query()->where('disp_flg', '1')->exists()) {
            foreach (['カット', 'カラー', 'パーマ', 'トリートメント', 'ヘッドスパ', '縮毛矯正'] as $name) {
                ServiceCategory::create(['name' => $name, 'tb_shop_cd' => null, 'disp_flg' => '1']);
            }
        }

        return ServiceCategory::query()->where('disp_flg', '1')->orderBy('cd')->pluck('cd', 'name')
            ->map(fn ($cd) => (int) $cd)->all();
    }

    /** Categoría personalizada propia de un solo registro con sus campos; devuelve [nombre => [cd, opciones]] */
    private function customCategory(): array
    {
        $fields = [
            ['髪質', 'セレクト', ['普通', '硬い', '柔らかい', 'くせ毛', '細い']],
            ['頭皮', 'セレクト', ['普通', '乾燥', '脂性', '敏感']],
            ['好みのスタイル', 'テキスト', []],
            ['アレルギー・注意事項', 'テキストエリア', []],
        ];

        $category = CustomCategory::query()->forCustomers()->where('name', self::CUSTOM_CATEGORY)->first();
        if (! $category) {
            $category = CustomCategory::create([
                'name' => self::CUSTOM_CATEGORY, 'category' => 'category1', 'disp_flg' => '1', 'hyouji_flg' => '1',
                'serach_flg' => '1', 'record_type' => '', 'disp_column' => '2',
                'rank' => (int) CustomCategory::query()->forCustomers()->max('rank') + 1,
            ]);
            foreach ($fields as $i => [$name, $type, $options]) {
                CustomField::create([
                    'tb_search_cate_cd' => $category->cd, 'category' => 'category1', 'name' => $name, 'form_type' => $type,
                    'titles' => implode('<>', $options), 'data' => implode('<>', $options),
                    'flds_row' => count($options) ?: null, 'disp_flg' => '1', 'hyouji_flg' => '1', 'serach_flg' => '1', 'rank' => $i + 1,
                ]);
            }
        }

        $result = [];
        foreach ($fields as [$name, $type, $options]) {
            $cd = CustomField::query()->where('tb_search_cate_cd', $category->cd)->where('name', $name)->value('cd');
            if ($cd) {
                $result[$name] = ['cd' => (int) $cd, 'cate_cd' => (int) $category->cd, 'type' => $type, 'options' => $options];
            }
        }

        return ['category' => $category->name, 'fields' => $result];
    }

    // ---------------------------------------------------------------- socios

    /** Filas de tb_customer (sin resúmenes todavía), con datos auxiliares para el historial */
    private function buildCustomers(array $shops, array $staff, array $masters): array
    {
        $f = $this->faker;
        $addresses = $this->addresses();
        $baseCount = DB::table('tb_customer')->count();
        $customers = [];

        $count = self::TOTAL + count(self::EDGE_CASES);
        for ($n = 1; $n <= $count; $n++) {
            // 75 % en la primera tienda si hay varias, el resto repartido
            $shop = count($shops) > 1 && $f->numberBetween(1, 100) > 60 ? $f->randomElement($shops) : $shops[$n % count($shops)];
            $female = $f->numberBetween(1, 100) <= 75;
            // Alta: el 70 % hace más de un año (hasta 4), el 30 % en los últimos 12 meses
            $registered = $f->numberBetween(1, 100) <= 70
                ? $this->now->subDays($f->numberBetween(366, 1460))
                : $this->now->subDays($f->numberBetween(1, 365));
            $registered = $registered->setTime($f->numberBetween(10, 19), $f->randomElement([0, 15, 30, 45]), $f->numberBetween(0, 59));

            // Cumpleaños repartidos en los 12 meses
            $month = ($n % 12) + 1;
            $birth = CarbonImmutable::create($this->now->year - $f->numberBetween(18, 70), $month, 1, 0, 0, 0, 'Asia/Tokyo');
            $birth = $birth->setDay($f->numberBetween(1, $birth->daysInMonth));

            $address = $addresses[($n - 1) % count($addresses)];
            $id = $shop . '1' . sprintf('%05s', $baseCount + $n);
            $mobile = sprintf('090-0000-%04d', $n);
            $paid = $f->numberBetween(1, 100) <= 5;
            $last = $f->randomElement(self::LAST_NAMES);
            $first = $f->randomElement($female ? self::FEMALE_NAMES : self::MALE_NAMES);
            $age = (int) $birth->diffInYears($this->now);
            $jobs = $age < 23 ? ['学生', '学生', 'パート・アルバイト', '会社員']
                : ($female ? ['会社員', '会社員', '公務員', '自営業', '主婦', 'パート・アルバイト', 'その他'] : ['会社員', '会社員', '公務員', '自営業', 'その他']);

            $customers[$n] = [
                'shop' => $shop,
                'registered' => $registered,
                'birth' => $birth,
                'row' => [
                    'cd' => (int) ($registered->getTimestamp() . sprintf('%03s', $shop) . sprintf('%06d', 900000 + $n)),
                    'id' => $id,
                    'login_id' => $id,
                    'login_pass' => sprintf('%04d', $f->numberBetween(0, 9999)),
                    'tb_shop_cd' => $shop,
                    'type' => '個人',
                    'name' => $last[0] . ' ' . $first[0],
                    // 1 de cada 10 con カナ de ancho medio, como los importados de sistemas viejos
                    'name_kana' => $n % 10 === 7 ? mb_convert_kana($last[1] . ' ' . $first[1], 'k') : $last[1] . ' ' . $first[1],
                    'zip' => $address['zip'],
                    'pref' => $address['pref'],
                    'add' => $address['add'],
                    'add_kana' => $address['add_kana'],
                    'add_number' => $f->numberBetween(1, 5) . '-' . $f->numberBetween(1, 30) . '-' . $f->numberBetween(1, 20),
                    'add_build' => $f->numberBetween(1, 100) <= 40 ? $f->randomElement(['グランドール', 'メゾン', 'パークハイツ', 'コーポ', 'サンライズ']) . $f->randomElement(['桜', '緑', '青葉', '中央', '南']) . ' ' . $f->numberBetween(101, 905) : '',
                    'email1' => "demo{$n}@example.com",
                    'tel' => $f->numberBetween(1, 100) <= 30 ? $mobile : '',
                    'kojin_mobile' => $mobile,
                    'kojin_birth' => $birth->format('Y/m/d'),
                    'kojin_birth_date' => $birth->getTimestamp(),
                    'kojin_sex' => $female ? '女性' : '男性',
                    'kojin_bloodtype' => $f->randomElement(['A', 'A', 'A', 'O', 'O', 'B', 'AB', '']),
                    'job' => $f->randomElement($jobs),
                    'mailmaga_flg' => $f->numberBetween(1, 100) <= 80 ? '1' : '0',
                    'mailmaga_email_type' => $f->randomElement(['パソコン', 'DoCoMo', 'AU', 'Softbank', 'その他']),
                    'touroku_kubun' => '2',
                    'disp_flg' => '1',
                    'touroku_date' => (string) $registered->getTimestamp(),
                    'update_date' => (string) $registered->getTimestamp(),
                    'tb_staff_cd' => $f->numberBetween(1, 100) <= 60 ? $f->randomElement($staff[$shop]) : null,
                    'tb_customer_group_cd' => $masters['groups'] ? $f->randomElement($masters['groups']) : null,
                    'tb_raiten_douki_master_cd' => $masters['motives'] ? $f->randomElement($masters['motives']) : null,
                    'tb_customer_level_cd' => $masters['levels'] ? $f->randomElement($masters['levels']) : null,
                    // 紹介者: alguno de los socios demo anteriores
                    'shoukai_tb_customer_cd' => $n > 20 && $f->numberBetween(1, 100) <= 10
                        ? $customers[$f->numberBetween(1, $n - 1)]['row']['cd'] : null,
                    'bikou' => $f->numberBetween(1, 100) <= 25 ? $f->realText(40) : '',
                    'kinmusaki_name' => '',
                    'kinmusaki_name_kana' => '',
                    'hobby' => '',
                    'error_send_cnt' => 0,
                    'stamp_count' => 0,
                    // Resúmenes: los rellena applySummaries() (todas las filas con las mismas columnas para el insert)
                    'last_raiten_date' => null,
                    'shuki_heikin' => null,
                    'last_stamp_time' => null,
                    'stamp_yukoukigen' => null,
                    'paid_member_flg' => $paid ? 2 : null,
                    'paid_member_service_start_date' => $paid ? $this->now->subDays($f->numberBetween(30, 300))->startOfDay()->getTimestamp() : null,
                    'synchronize_flg' => '',
                    'local_dl_sync_flg' => '',
                ],
            ];
        }

        $this->applyEdgeCases($customers);

        return $customers;
    }

    /** Casos límite de los clientes extra (EDGE_CASES) sobre las filas ya generadas */
    private function applyEdgeCases(array &$customers): void
    {
        $long = fn (string $text, int $length = 255) => mb_substr(str_repeat($text, (int) ceil($length / mb_strlen($text)) + 1), 0, $length);
        $now = (string) $this->now->getTimestamp();

        foreach (self::EDGE_CASES as $n => $case) {
            $c = &$customers[$n];
            switch ($case) {
                case 'baja':
                    $c['row']['disp_flg'] = '9';
                    $c['row']['update_date'] = $now;
                    if ($n === 302) {
                        $c['visits'] = [];
                        $c['row']['mailmaga_flg'] = '0';
                    }
                    break;
                case 'provisional':
                case 'provisional0':
                    $c['row']['touroku_kubun'] = $case === 'provisional' ? '1' : '0';
                    $c['visits'] = [];
                    break;
                case 'mailError':
                    $c['row']['mailmaga_flg'] = '9';
                    $c['row']['error_send_cnt'] = 3;
                    break;
                case 'long':
                    $c['row']['name'] = '勅使河原 ' . $long('寿限無五劫の擦り切れ海砂利水魚', 60);
                    $c['row']['name_kana'] = 'テシガワラ ' . $long('ジュゲムゴコウノスリキレカイジャリスイギョ', 60);
                    $c['row']['add'] = $long('大阪府大阪市中央区心斎橋筋二丁目東心斎橋一丁目');
                    $c['row']['add_kana'] = $long('オオサカフオオサカシチュウオウクシンサイバシスジ');
                    $c['row']['add_number'] = $long('一丁目二番地三号');
                    $c['row']['add_build'] = $long('グランドメゾン心斎橋ステーションタワー北棟');
                    $c['row']['kinmusaki_name'] = $long('株式会社日本美容総合研究開発センター');
                    $c['row']['kinmusaki_name_kana'] = $long('カブシキガイシャニホンビヨウソウゴウケンキュウ');
                    $c['row']['hobby'] = $long('映画鑑賞・旅行・料理・カフェ巡り・');
                    $c['row']['bikou'] = $long('初回来店時にカウンセリング長め希望。', 1000);
                    break;
                case 'halfKana':
                    $c['row']['name_kana'] = mb_convert_kana($c['row']['name_kana'], 'k');
                    $c['row']['add_kana'] = mb_convert_kana((string) $c['row']['add_kana'], 'k');
                    break;
                case 'empty':
                    foreach (['tel', 'kojin_mobile', 'zip', 'pref', 'add', 'add_kana', 'add_number', 'add_build', 'kojin_sex',
                        'kojin_bloodtype', 'job', 'mailmaga_email_type', 'bikou'] as $column) {
                        $c['row'][$column] = '';
                    }
                    $c['row']['kojin_birth'] = '//';
                    $c['row']['kojin_birth_date'] = null;
                    foreach (['tb_staff_cd', 'tb_customer_group_cd', 'tb_raiten_douki_master_cd', 'tb_customer_level_cd', 'shoukai_tb_customer_cd'] as $column) {
                        $c['row'][$column] = null;
                    }
                    $c['birth'] = null;
                    break;
                case 'leap':
                    $c['birth'] = CarbonImmutable::create($n === 309 ? 1996 : 2004, 2, 29, 0, 0, 0, 'Asia/Tokyo');
                    $c['row']['kojin_birth'] = $c['birth']->format('Y/m/d');
                    $c['row']['kojin_birth_date'] = $c['birth']->getTimestamp();
                    break;
                case 'monthEnds':
                    $c['registered'] = $this->now->subYears(2)->setTime(12, 0);
                    $c['row']['touroku_date'] = $c['row']['update_date'] = (string) $c['registered']->getTimestamp();
                    $c['row']['cd'] = (int) ($c['registered']->getTimestamp() . sprintf('%03s', $c['shop']) . sprintf('%06d', 900000 + $n));
                    $c['visits'] = $this->monthEndVisits();
                    break;
            }
            unset($c);
        }
    }

    /** Último día de cada mes de los últimos 12 meses (incluye el 31/12) y el 1/1, antes de hoy */
    private function monthEndVisits(): array
    {
        $from = $this->now->subYear()->startOfDay();
        $today = $this->now->startOfDay();
        $times = [];
        for ($i = 12; $i >= 1; $i--) {
            $day = $this->now->startOfMonth()->subMonths($i)->endOfMonth()->setTime(17, 30);
            if ($day >= $from && $day < $today) {
                $times[] = $day;
            }
        }
        $newYear = CarbonImmutable::create($this->now->year, 1, 1, 11, 0, 0, 'Asia/Tokyo');
        if ($newYear >= $from && $newYear < $today) {
            $times[] = $newYear;
        }
        usort($times, fn ($a, $b) => $a <=> $b);

        return $times;
    }

    /**
     * Direcciones: códigos postales reales de la tabla `zip` (Tokio, Osaka, Kanagawa, Hyogo, Kioto) si tiene filas;
     * si no, de Faker.
     */
    private function addresses(): array
    {
        $rows = DB::table('zip')->whereIn('pref1', ['東京都', '大阪府', '神奈川県', '兵庫県', '京都府'])
            ->where('town1', 'not like', '%以下に掲載%')->where('town1', 'not like', '%（%')
            ->orderBy('zip')->orderBy('town1')->get(['zip', 'pref1', 'city1', 'town1', 'city', 'town']);

        $addresses = [];
        if ($rows->isNotEmpty()) {
            for ($i = 0; $i < self::TOTAL + count(self::EDGE_CASES); $i++) {
                $r = $rows[$this->faker->numberBetween(0, $rows->count() - 1)];
                $addresses[] = [
                    'zip' => (string) $r->zip,
                    'pref' => (string) $r->pref1,
                    'add' => $r->city1 . $r->town1,
                    'add_kana' => mb_convert_kana($r->city . $r->town, 'KV'),
                ];
            }

            return $addresses;
        }

        for ($i = 0; $i < self::TOTAL + count(self::EDGE_CASES); $i++) {
            $addresses[] = [
                'zip' => preg_replace('/\D/', '', $this->faker->postcode()),
                'pref' => $this->faker->prefecture(),
                'add' => $this->faker->city() . $this->faker->streetName(),
                'add_kana' => '',
            ];
        }

        return $addresses;
    }

    // ---------------------------------------------------------------- visitas, puntos y sellos

    /** Visitas con su ポイント y スタンプ (cd de hatuban reservados en bloque) y los saldos de cada socio */
    private function buildHistory(array $customers, array $staff, array $shops): array
    {
        $f = $this->faker;
        $plans = [];
        $total = 0;
        foreach ($customers as $n => $c) {
            $plans[$n] = $c['visits'] ?? $this->visitTimes($c['registered']);
            $total += count($plans[$n]);
        }

        $visitCd = $this->reserveCds('raiten_rireki', $total);
        $pointCd = $this->reserveCds('tb_point_log', $total);
        $stampCd = $this->reserveCds('tb_stamp_log', $total);

        $stampMax = (int) DB::table('tb_stamp_config')->where('tb_shop_cd', 1)->orderBy('cd')->value('stamp_max')
            ?: (int) DB::table('tb_stamp_config')->where('cd', 1)->value('stamp_max') ?: 20;
        $pointDays = $this->masterDays('point_shikkou_kikan');
        $stampDays = $this->masterDays('stamp_shikkou_kikan');
        $shopPoint = $this->shopPointSettings($shops);
        $visitPlan = new DemoVisitPlan;

        $visits = $points = $stamps = $balances = $byCustomer = $stampInfo = [];
        foreach ($customers as $n => $c) {
            $row = $c['row'];
            $balance = 0;
            $stampCount = 0;
            $byCustomer[$n] = [];

            foreach ($plans[$n] as $time) {
                $shop = count($shops) > 1 && $f->numberBetween(1, 100) <= 10 ? $f->randomElement($shops) : $c['shop'];
                $staffCd = $row['tb_staff_cd'] && $shop === $c['shop'] && $f->numberBetween(1, 100) <= 80
                    ? $row['tb_staff_cd'] : $f->randomElement($staff[$shop]);
                // Lo cobrado sale de DemoVisitPlan (lo mismo que grabará VentasDemoSeeder): kaikei = total de la venta
                $plan = $visitPlan->for($visitCd, $shop);
                $menu = $plan['menus']->map(fn ($m) => [(string) $m->name, (int) $m->kingaku])->all();
                $kaikei = $plan['total'];
                $epoch = $time->getTimestamp();

                // ポイント (kanren_record_register.php): 還元率 y 上限 de la tienda; keisan_flg 1 = sobre el 差引金額
                $setting = $shopPoint[$shop];
                $cash = $f->numberBetween(1, 100) <= 55;
                $use = $balance >= 300 && $kaikei > 0 && $f->numberBetween(1, 100) <= 20 ? min($balance, $setting['max'], $kaikei) : 0;
                $percent = $cash ? $setting['genkin'] : $setting['card'];
                $amount = $setting['keisan'] === '2' ? $kaikei : $kaikei - $use;
                $earned = (int) floor($amount * $percent / 100);
                $points[] = [
                    'cd' => $pointCd, 'tb_shop_cd' => $shop, 'tb_staff_cd' => $staffCd, 'tb_customer_cd' => $row['cd'],
                    'tb_terminal_cd' => -1, 'touroku_date' => $epoch, 'shori_mae_point' => $balance, 'kaikei' => $kaikei,
                    'sashihiki_kaikei' => $kaikei - $use, 'shiharai_houhou' => $cash ? '1' : '2', 'percentage' => $percent,
                    'shouhi_point' => $use, 'jidou_point' => $earned, 'sounyu_point' => 0, 'meisai_mail' => '', 'raiten_shori' => '1',
                ];
                $balance = $balance - $use + $earned;

                // スタンプ (来店で1個、上限まで)
                $stampLog = null;
                if ($stampCount < $stampMax) {
                    $stampLog = $stampCd++;
                    $stamps[] = [
                        'cd' => $stampLog, 'tb_shop_cd' => $shop, 'tb_staff_cd' => $staffCd, 'tb_customer_cd' => $row['cd'],
                        'tb_terminal_cd' => null, 'touroku_date' => $epoch, 'shori_mae_stamp' => $stampCount, 'sounyu_stamp' => 1,
                        'action_flg' => '1', 'raiten_shori' => '1',
                    ];
                    $stampCount++;
                }

                $visits[] = [
                    'cd' => $visitCd, 'user_id' => $row['id'], 'user_cd' => $row['cd'], 'shop_cd' => $shop,
                    'raiten_time' => (string) $epoch, 'raiten_hiduke' => $time->format('Y/m/d'), 'stamp' => 1,
                    'customer_age' => $c['birth'] ? (int) $c['birth']->diffInYears($time) : null, 'customer_sex' => $row['kojin_sex'],
                    'customer_job' => $row['job'], 'tb_point_log_cd' => $pointCd, 'tb_stamp_log_cd' => $stampLog,
                ];
                $byCustomer[$n][] = ['cd' => $visitCd, 'time' => $time, 'shop' => $shop, 'staff' => $staffCd, 'menus' => $menu, 'kaikei' => $kaikei];
                $visitCd++;
                $pointCd++;
            }

            if ($plans[$n]) {
                $last = end($plans[$n]);
                $balances[] = [
                    'cd' => $row['cd'], 'tb_customer_cd' => $row['cd'], 'service_point' => $balance,
                    'point_yukoukigen' => $last->startOfDay()->addDays($pointDays)->getTimestamp() - 1,
                    'touroku_date' => (string) $plans[$n][0]->getTimestamp(), 'update_date' => (string) $last->getTimestamp(),
                ];
            }
            $stampInfo[$n] = [$stampCount, $plans[$n] ? end($plans[$n]) : null, $stampDays];
        }

        return compact('visits', 'points', 'stamps', 'balances', 'byCustomer', 'stampInfo');
    }

    /** 0–20 instantes de visita en días distintos desde el alta (como mucho 12 meses atrás) hasta ayer, ordenados */
    private function visitTimes(CarbonImmutable $registered): array
    {
        $f = $this->faker;
        $r = $f->numberBetween(1, 100);
        $count = match (true) {
            $r <= 12 => 0,
            $r <= 50 => $f->numberBetween(1, 3),
            $r <= 80 => $f->numberBetween(4, 8),
            $r <= 95 => $f->numberBetween(9, 14),
            default => $f->numberBetween(15, 20),
        };

        $from = max($registered->startOfDay()->addDay()->getTimestamp(), $this->now->subYear()->startOfDay()->getTimestamp());
        $days = intdiv($this->now->startOfDay()->getTimestamp() - $from, 86400);
        $count = min($count, max($days, 0));

        $offsets = [];
        while (count($offsets) < $count) {
            $offsets[$f->numberBetween(0, $days - 1)] = true;
        }
        $offsets = array_keys($offsets);
        sort($offsets);

        return array_map(fn ($d) => CarbonImmutable::createFromTimestamp($from, 'Asia/Tokyo')->addDays($d)
            ->setTime($f->numberBetween(10, 19), $f->randomElement([0, 15, 30, 45]), $f->numberBetween(0, 59)), $offsets);
    }

    /** Días de caducidad de admin_master (vacío = 365, como el legacy) */
    private function masterDays(string $column): int
    {
        $value = trim((string) DB::table('admin_master')->where('cd', 1)->value($column));

        return $value === '' ? 365 : (int) $value;
    }

    /** Ajustes de puntos de cada tienda con los valores por defecto de kanren_record_register.php */
    private function shopPointSettings(array $shops): array
    {
        $settings = [];
        foreach ($shops as $shop) {
            $row = DB::table('tb_shop')->where('cd', $shop)->first(['shiyou_point_jyougen', 'point_percentage_genkin', 'point_percentage_card', 'keisan_flg']);
            $settings[$shop] = [
                'max' => (int) ($row->shiyou_point_jyougen ?? 0) ?: 1000,
                'genkin' => (float) ($row->point_percentage_genkin ?? 0) ?: 1.0,
                'card' => (float) ($row->point_percentage_card ?? 0) ?: 1.0,
                'keisan' => (string) ($row->keisan_flg ?? '') ?: '1',
            ];
        }

        return $settings;
    }

    /** Resúmenes del socio: último 来店, 周期, sellos y rangos (metidos en la fila antes de insertarla) */
    private function applySummaries(array &$customers, array $history, array $masters): void
    {
        $threeMonthsAgo = $this->now->subMonths(3)->getTimestamp();

        foreach ($customers as $n => &$c) {
            $visits = $history['byCustomer'][$n];
            [$stampCount, $lastVisit, $stampDays] = $history['stampInfo'][$n];

            if ($visits) {
                $dates = array_reverse(array_unique(array_map(fn ($v) => $v['time']->format('Y/m/d'), $visits)));
                $c['row']['last_raiten_date'] = $dates[0];
                $recent = array_slice($dates, 0, 11);
                if (count($recent) > 1) {
                    $sum = 0;
                    for ($i = 1; $i < count($recent); $i++) {
                        $sum += (strtotime($recent[$i - 1]) - strtotime($recent[$i])) / 86400;
                    }
                    $c['row']['shuki_heikin'] = (int) round($sum / (count($recent) - 1));
                }
            }
            if ($stampCount > 0) {
                $c['row']['stamp_count'] = $stampCount;
                $c['row']['last_stamp_time'] = $lastVisit->getTimestamp();
                $c['row']['stamp_yukoukigen'] = $lastVisit->startOfDay()->addDays($stampDays)->getTimestamp() - 1;
            }

            // ランク: 今回 = últimos 12 meses; 前回 = hasta hace 3 meses
            $before = array_filter($visits, fn ($v) => $v['time']->getTimestamp() < $threeMonthsAgo);
            $c['row']['kingaku_rank_now'] = $visits ? $this->rankFor($masters['kingakuRanks'], array_sum(array_column($visits, 'kaikei'))) : null;
            $c['row']['kingaku_rank_pre'] = $before ? $this->rankFor($masters['kingakuRanks'], array_sum(array_column($before, 'kaikei'))) : null;
            $c['row']['raiten_rank_now'] = $visits ? $this->rankFor($masters['raitenRanks'], count($visits)) : null;
            $c['row']['raiten_rank_pre'] = $before ? $this->rankFor($masters['raitenRanks'], count($before)) : null;
        }
        unset($c);
    }

    /** cd del rango cuyo intervalo [jyouken1, jyouken2] contiene el valor (el último que encaje) */
    private function rankFor(array $ranks, int $value): ?int
    {
        $found = null;
        foreach ($ranks as [$cd, $from, $to]) {
            if (($from === null || $value >= (int) $from) && ($to === null || $value <= (int) $to)) {
                $found = $cd;
            }
        }

        return $found;
    }

    // ---------------------------------------------------------------- pestañas

    /** カルテ de las 1–3 últimas visitas del 60 % de los socios con visitas (como KarteService::save) */
    private function seedKartes(array $customers, array $byCustomer, array $categories): void
    {
        $f = $this->faker;
        $rows = [];
        foreach ($customers as $n => $c) {
            $visits = $byCustomer[$n];
            if (! $visits || $f->numberBetween(1, 100) > 60) {
                continue;
            }
            foreach (array_slice($visits, -$f->numberBetween(1, 3)) as $visit) {
                $main = $visit['menus'][0][0];
                $category = $categories[$main] ?? $categories[mb_substr($main, -3)] ?? $categories['カット'] ?? reset($categories);
                $rows[] = [
                    'tb_customer_cd' => $c['row']['cd'], 'tb_staff_cd' => $visit['staff'], 'tb_shop_cd' => $visit['shop'],
                    'tb_sejutsu_cate_cd' => $category,
                    'comment' => implode('・', array_column($visit['menus'], 0)) . "\n" . $this->karteComment($main),
                    'pic_flg' => '', 'uketsuke_date' => $visit['time']->startOfDay()->getTimestamp(),
                    'touroku_date' => $visit['time']->getTimestamp(), 'update_date' => $visit['time']->getTimestamp(), 'disp_flg' => '1',
                ];
            }
        }

        $cd = $this->reserveCds('tb_karute', count($rows));
        foreach ($rows as &$row) {
            $row = ['cd' => $cd++] + $row;
        }
        unset($row);
        $this->insertChunks('tb_karute', $rows);
    }

    private function karteComment(string $menu): string
    {
        $f = $this->faker;
        $notes = [
            'カット' => ['前回より' . $f->numberBetween(1, 5) . 'cm短め。', '襟足すっきり、トップは軽めに。', 'レイヤー多めで動きを出す。'],
            'カラー' => ['アッシュベージュ ' . $f->numberBetween(7, 12) . 'トーン。', '根元リタッチ、毛先は退色あり。', 'グレイカラー 7レベル。'],
            'パーマ' => ['毛先中心にゆるめのカール。', 'ロッド' . $f->numberBetween(14, 26) . 'mm。'],
            '縮毛矯正' => ['前髪と顔まわりのみ。', 'ダメージ毛のため薬剤弱め。'],
        ];
        $base = $notes[$menu] ?? ['仕上がりにご満足いただけました。', '次回はトリートメントをおすすめ。'];

        return $f->randomElement($base) . $f->randomElement(['次回は' . $f->numberBetween(1, 3) . 'ヶ月後の予定。', 'ホームケアの説明済み。', '会話少なめを希望。', '']);
    }

    /** お客様情報 (categoría propia) para el 75 % de los socios, una fila por campo como CustomerRegistrationService */
    private function seedCustomFields(array $customers, array $custom): void
    {
        $f = $this->faker;
        $rows = [];
        $seq = 0;
        $epoch = $this->now->getTimestamp();
        foreach ($customers as $c) {
            if ($f->numberBetween(1, 100) > 75) {
                continue;
            }
            foreach ($custom['fields'] as $name => $field) {
                $text = match ($name) {
                    '好みのスタイル' => $f->randomElement(['ショートボブ', 'ナチュラルなミディアム', 'ゆるふわロング', 'ツーブロック', '外ハネボブ', 'センターパート', 'ショートレイヤー']),
                    'アレルギー・注意事項' => $f->randomElement(['特になし', '特になし', '特になし', 'ジアミンアレルギーあり（ノンジアミン使用）', '頭皮がしみやすい', '首が痛いのでシャンプーは浅め']),
                    default => $f->randomElement($field['options']),
                };
                $rows[] = [
                    'cd' => (int) ($epoch . sprintf('%03s', $c['shop']) . sprintf('%06d', ++$seq)),
                    'tb_customer_cd' => $c['row']['cd'], 'tb_search_cate_cd' => $field['cate_cd'], 'tb_search_form_cd' => $field['cd'],
                    'text_data' => $text, 'form_type' => '1', 'form_title' => $custom['category'] . '<>' . $name, 'disp_flg' => '1',
                    'touroku_date' => (string) $epoch, 'update_date' => (string) $epoch, 'synchronize_flg' => '', 'local_sync_flg' => '',
                ];
            }
        }
        $this->insertChunks('tb_customer_sub', $rows);
    }

    /** Filas de マイアルバム (con una imagen cada una) de los socios con más visitas; devuelve [cd => color] */
    private function seedPhotoRows(array $customers, array $byCustomer): array
    {
        $f = $this->faker;
        $candidates = array_filter(array_keys($customers), fn ($n) => count($byCustomer[$n]) >= 3);
        $chosen = array_slice($candidates, 0, self::PHOTO_CUSTOMERS);

        $rows = [];
        foreach ($chosen as $n) {
            foreach (array_slice($byCustomer[$n], -$f->numberBetween(1, 2)) as $visit) {
                $rows[] = [
                    'tb_customer_cd' => $customers[$n]['row']['cd'],
                    'comment' => $f->randomElement(['仕上がり（正面）', '仕上がり（サイド）', 'カラー後', 'ビフォー', 'アフター']) . '　' . implode('・', array_column($visit['menus'], 0)),
                    'disp_date' => (string) $visit['time']->startOfDay()->getTimestamp(),
                    'pic_flg' => '1', 'hyouji_flg' => '1', 'disp_flg' => '1',
                    'touroku_date' => $visit['time']->getTimestamp(), 'update_date' => $visit['time']->getTimestamp(),
                ];
            }
        }

        $cd = $this->reserveCds('tb_photo_album', count($rows));
        $photos = [];
        foreach ($rows as &$row) {
            $photos[$cd] = [$f->numberBetween(120, 230), $f->numberBetween(90, 200), $f->numberBetween(90, 210)];
            $row = ['cd' => $cd++] + $row;
        }
        unset($row);
        $this->insertChunks('tb_photo_album', $rows);

        return $photos;
    }

    /** Imagen pequeña de cada álbum: se deja como temporal y CustomerPhotoImageStore la pasa al álbum con sus tamaños */
    private function storePhotoImages(array $photos): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            return;
        }
        $store = app(CustomerPhotoImageStore::class);

        foreach ($photos as $cd => [$r, $g, $b]) {
            $image = imagecreatetruecolor(480, 360);
            imagefill($image, 0, 0, imagecolorallocate($image, $r, $g, $b));
            $light = imagecolorallocate($image, min($r + 40, 255), min($g + 40, 255), min($b + 40, 255));
            $dark = imagecolorallocate($image, intdiv($r, 2), intdiv($g, 2), intdiv($b, 2));
            imagefilledellipse($image, 240, 150, 170, 200, $light);
            imagefilledrectangle($image, 140, 250, 340, 360, $dark);

            ob_start();
            imagejpeg($image, null, 70);
            $jpeg = (string) ob_get_clean();

            $temp = "demo_album_{$cd}.jpg";
            Storage::disk('public')->put(CustomerPhotoImageStore::TEMP_DIR . '/' . $temp, $jpeg);
            $store->moveToAlbum($temp, (int) $cd, 0);
        }
    }
}
