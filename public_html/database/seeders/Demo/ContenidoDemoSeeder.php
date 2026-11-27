<?php

namespace Database\Seeders\Demo;

use App\Models\ContentsMaster;
use App\Models\Customers\Customer;
use App\Models\Mypage\Recommendation;
use App\Models\Mypage\Recruit;
use App\Models\Mypage\RecruitCategory;
use App\Models\Promotions\KujibikiLog;
use App\Models\Senalizacion\SignageWebContact;
use App\Models\Senalizacion\SignageWebContent;
use App\Models\Senalizacion\SignageWebContentCategory;
use App\Models\Senalizacion\SignageWebCustomer;
use App\Models\Senalizacion\SignageWebCycle;
use App\Models\Senalizacion\SignageWebPackage;
use App\Models\Senalizacion\SignageWebTerminal;
use App\Models\Shop;
use App\Models\Terminal;
use App\Models\Terminales\FelicaCard;
use App\Models\Terminales\PaperCard;
use App\Models\Terminales\SignageDisplay;
use App\Models\Terminales\SignageMaterial;
use App\Models\Terminales\SignageProgram;
use App\Services\Mypage\AppImageFiles;
use App\Services\Mypage\LotteryConfig;
use App\Services\Mypage\MypageConfig;
use App\Services\Mypage\RecommendationService;
use App\Services\Mypage\RecruitCategoryService;
use App\Services\Mypage\RecruitService;
use App\Services\Promotions\LotteryService;
use App\Services\Senalizacion\SignageContentService;
use App\Services\Terminales\FelicaCardService;
use App\Services\Terminales\PaperCardService;
use App\Services\Terminales\SignageFeedBuilder;
use App\Services\Terminales\SignageMaterialUploader;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Datos de demostración de S4 (CONVENCIONES §4 «Datos de demostración»): マイコレ, オススメ, 求人, ガチャリン,
 * 電子看板 (programas y displays que el reproductor HTML5 muestra), ペーパーサイネージ, ICカード y signage/.
 *
 * Idempotente por bloque: cada uno mira su marcador (un registro suyo, o que la configuración ya exista) y no
 * duplica ni toca lo que no creó. Lo que depende de clientes (tiradas de ガチャリン, ICカード asignadas) se
 * salta si aún no hay socios y se completa en una ejecución posterior. Las imágenes son placeholders hechos con
 * GD en el disco public, nunca descargados.
 */
class ContenidoDemoSeeder extends Seeder
{
    /** Marcadores de los registros de demo */
    private const RECOMMEND_TITLES = ['春の新メニューのご案内', '週末限定ポイント2倍キャンペーン', 'アプリ会員様限定クーポン配信中'];

    private const RECRUIT_CATEGORIES = ['ホールスタッフ', 'キッチンスタッフ'];

    private const RECRUIT_HEADLINES = ['未経験歓迎！ホールスタッフ募集', '週2日からOK キッチンスタッフ', '土日だけでも歓迎 オープニングスタッフ'];

    private const PROGRAM_NAMES = ['デモ 店頭プログラム', 'デモ 縦型プログラム'];

    private const PAPER_CARD_IDS = ['demo-menu', 'demo-coupon', 'demo-recruit'];

    private const FELICA_PREFIX = 'DEMOIDM';

    private const SIGNAGE_PACKAGE = 'デモ ロビー';

    private const SIGNAGE_EDGE_PACKAGE = 'デモ 季節';

    private const FELICA_EDGE_PREFIX = 'DEMOEDGE';

    private Generator $faker;

    private ?Shop $shop = null;

    /** Hasta tres tiendas activas: los casos límite se reparten entre ellas */
    private Collection $shops;

    public function run(): void
    {
        $this->faker = \Faker\Factory::create('ja_JP');
        $this->faker->seed(20261128);
        mt_srand(20261128);
        $this->shops = Shop::query()->where('disp_flg', '1')->orderBy('cd')->limit(3)->get();
        $this->shop = $this->shops->first();

        foreach ([
            'マイコレ' => 'seedMycolle',
            'オススメ' => 'seedRecommendations',
            '求人' => 'seedRecruits',
            'ガチャリン' => 'seedLottery',
            '電子看板' => 'seedDigitalSignage',
            'ペーパーサイネージ' => 'seedPaperCards',
            'ICカード' => 'seedFelicaCards',
            'signage/' => 'seedSignageWeb',
            // Casos límite (CONVENCIONES §4, tercera pasada): bajas, caducados, japonés largo, opcionales vacíos,
            // fechas de fin de mes/año/29-02, varias tiendas y bastantes filas para paginar
            'オススメ (casos límite)' => 'seedRecommendationEdges',
            '求人 (casos límite)' => 'seedRecruitEdges',
            'ガチャリン (caducadas)' => 'seedLotteryEdges',
            '電子看板 (casos límite)' => 'seedDigitalSignageEdges',
            'ペーパーサイネージ (casos límite)' => 'seedPaperCardEdges',
            'ICカード (casos límite)' => 'seedFelicaCardEdges',
            'ICカード 端末' => 'seedFelicaTerminals',
            'signage/ (periodos)' => 'seedSignageWebEdges',
        ] as $label => $method) {
            $this->command?->info("S4 demo: {$label}");
            $this->{$method}();
        }
    }

    // ------------------------------------------------------------------ マイページ

    /** カバーフロー (4), バナー (3), HOMEメインイメージ y サムネイル de 全店舗, visibles en todas las tiendas */
    private function seedMycolle(): void
    {
        $config = app(MypageConfig::class);
        $files = app(AppImageFiles::class);
        if ($config->text('mypage_config') !== null || $files->numbered(AppImageFiles::COVER) !== []) {
            return;   // ya configurada (por la demo o a mano): no se toca
        }

        $all = fn (int $n) => array_fill(0, $n, [0 => 1]);
        foreach (['春のおすすめ', 'ポイントカード', '新メニュー', '会員特典'] as $i => $text) {
            $files->write(AppImageFiles::COVER, "{$i}.jpg", $this->image(800, 600, "COVER ".($i + 1), $i));
        }
        foreach (['クーポン', 'スタンプ', '求人'] as $i => $text) {
            $files->write(AppImageFiles::BANNER, "{$i}.jpg", $this->image(800, 250, "BANNER ".($i + 1), $i + 4));
        }
        $files->write(AppImageFiles::HOME_IMAGE, '0.jpg', $this->image(800, 600, 'HOME', 7));
        $files->write(AppImageFiles::HOME_IMAGE, '0_s.jpg', $this->image(320, 240, 'HOME', 7));
        $files->write(AppImageFiles::HOME_THUMBNAIL, '0.jpg', $this->image(400, 400, 'THUMB', 8));

        $config->saveLinks(AppImageFiles::COVER, ['https://example.com/spring', '', 'https://example.com/menu', '']);
        $config->saveLinks(AppImageFiles::BANNER, ['https://example.com/coupon', '', 'https://example.com/recruit']);
        $config->saveMypageConfig(['cover' => $all(4), 'banner' => $all(3), 'home_image_flg' => [], 'home_thum_flg' => []]);
    }

    private function seedRecommendations(): void
    {
        foreach (self::RECOMMEND_TITLES as $i => $title) {
            if (Recommendation::query()->withDeleted()->where('title', $title)->exists()) {
                continue;
            }
            $recommendation = Recommendation::create([
                'tb_shop_cd' => $i === 2 ? $this->shop?->cd : null,   // NULL = 全店舗 (recommend.php)
                'disp_date' => now()->subDays(3 + $i * 9)->format('Y/m/d'),
                'title' => $title,
                'naiyou' => $this->faker->realText(80),
                'link' => '', 'pic_link' => '', 'link_type' => '',
                'hyouji_flg' => '', 'img_flg' => '1', 'img_type' => 'jpg',
                'disp_flg' => '1',
            ]);
            $dir = RecommendationService::FOLDER.'/'.$recommendation->cd;
            Storage::disk('public')->put("{$dir}/{$recommendation->cd}_big.jpg", $this->image(600, 400, 'RECOMMEND '.($i + 1), $i));
            Storage::disk('public')->put("{$dir}/{$recommendation->cd}.jpg", $this->image(150, 100, 'R'.($i + 1), $i));
        }
    }

    private function seedRecruits(): void
    {
        $categories = [];
        foreach (self::RECRUIT_CATEGORIES as $name) {
            $categories[] = RecruitCategory::query()->where('shokushu', $name)->first()
                ?? RecruitCategory::create(['shokushu' => $name, 'tb_shop_cd' => $this->shop?->cd, 'disp_flg' => '1']);
        }

        $created = false;
        foreach (self::RECRUIT_HEADLINES as $i => $headline) {
            if (Recruit::query()->withDeleted()->where('boshu_headline', $headline)->exists()) {
                continue;
            }
            $recruit = Recruit::create([
                'tb_shop_cd' => (int) $this->shop?->cd,
                'boshu_shokushu' => $categories[$i % 2]->cd,
                'boshu_headline' => $headline,
                'shigoto_naiyou' => $this->faker->realText(60),
                'koyou_keitai' => 'アルバイト・パート',
                'oubo_shikaku' => '高校生不可・未経験者歓迎',
                'kinmu_chi' => $this->faker->prefecture.$this->faker->city,
                'kinmu_jikan' => ['10:00〜15:00', '17:00〜23:00', '9:00〜18:00'][$i],
                'kyuyo' => '時給'.(1100 + $i * 50).'円〜',
                'kyuka' => 'シフト制',
                'taigu' => '交通費支給・制服貸与・まかないあり',
                'kaisha_name' => (string) ($this->shop?->kaishamei ?: $this->faker->company),
                'add' => $this->faker->address,
                'tel' => sprintf('090-0000-%04d', 100 + $i),
                'fax' => '',
                'jigyou_naiyou' => '飲食店の運営',
                'oubo_houhou' => 'お電話にてご連絡ください。',
                'mensetsuchi' => '店舗',
                'tantou' => $this->faker->lastName,
                'pic_flg1' => '1', 'pic_flg2' => '',
                'disp_flg' => '1',
            ]);
            $dir = RecruitService::FOLDER.'/'.$recruit->cd;
            Storage::disk('public')->put("{$dir}/1.jpg", $this->image(480, 360, 'RECRUIT '.($i + 1), $i + 2));
            Storage::disk('public')->put("{$dir}/1_t.jpg", $this->image(100, 100, 'R'.($i + 1), $i + 2));
            $created = true;
        }

        if ($created) {
            app(SignageFeedBuilder::class)->writeFloormap();
        }
    }

    /** ガチャリン: configuración (使用する, 3回/日, 30日, cuatro premios) y tiradas de socios en los últimos días */
    private function seedLottery(): void
    {
        if (! ContentsMaster::query()->where('category', LotteryConfig::CATEGORY)->exists()) {
            $config = app(LotteryConfig::class);
            $config->saveEnabled('1');
            $config->saveDailyMax(3);
            $config->saveLimit('2', null);
            $config->saveRates([
                'ダイヤ' => ['rate' => 2, 'num' => 1],
                'ゴールド' => ['rate' => 8, 'num' => 3],
                'シルバー' => ['rate' => 15, 'num' => 10],
                'ブロンズ' => ['rate' => 25, 'num' => 30],
            ]);
        }

        if (KujibikiLog::query()->exists()) {
            return;
        }
        $members = $this->members(8);
        if ($members->isEmpty()) {
            $this->command?->warn('  sin socios: tiradas de ガチャリン saltadas');

            return;
        }
        $lottery = app(LotteryService::class)->useRandom(fn (int $min, int $max) => mt_rand($min, $max));
        foreach ($members as $i => $member) {
            foreach ([6, 4, 2, 1] as $days) {
                if (($i + $days) % 3 !== 0) {
                    $lottery->draw((int) $member->cd, now()->subDays($days)->setTime(12, 0));
                }
            }
        }
    }

    // ------------------------------------------------------------------ 端末管理

    /**
     * 電子看板: tres 放映素材 con imagen, dos 放映プログラム (horizontal y vertical, 放映ファイル選択) y un
     * display por programa; se escriben sus XML (hp/uploads/c7/display/<cd>.xml) como al guardar en pantalla.
     */
    private function seedDigitalSignage(): void
    {
        if ($this->shop === null || SignageProgram::query()->withDeleted()->whereIn('name', self::PROGRAM_NAMES)->exists()) {
            return;
        }

        $uploader = app(SignageMaterialUploader::class);
        $materials = [];
        foreach (['ランチメニュー', '期間限定クーポン', 'スタッフ募集'] as $i => $title) {
            $material = SignageMaterial::create([
                'tb_shop_cd' => $this->shop->cd, 'title' => $title, 'comment' => 'デモ素材',
                'categorize_value_0' => 'デモ', 'play_frame_sec' => '10',
                'filetype' => SignageMaterial::FILETYPE_IMAGE, 'rank' => $i, 'disp_flg' => '1',
            ]);
            $uploader->apply($material, [], [], ['photo0' => $this->upload(1280, 720, strtoupper('signage '.($i + 1)), $i)]);
            $materials[] = $material->cd;
        }

        $feed = app(SignageFeedBuilder::class);
        foreach (self::PROGRAM_NAMES as $i => $name) {
            $program = new SignageProgram([
                'tb_shop_cd' => $this->shop->cd, 'name' => $name, 'comment' => 'デモ用',
                'terminal_id' => '', 'terminal_type' => '', 'rank' => $i,
                'disp_conf' => SignageProgram::CONF_FILES, 'act_flg' => '', 'feli_act_flg' => '', 'mypage_act_flg' => '',
                'telop_comment' => $i === 0 ? '本日もご来店ありがとうございます。' : '',
                'vertical_setting' => $i === 0 ? 'landscape' : 'portrait', 'disp_flg' => '1',
            ]);
            $program->setMaterialCds($i === 0 ? $materials : array_reverse($materials))->save();
            $feed->writeProgram($program->cd);

            SignageDisplay::create([
                'tb_shop_cd' => $this->shop->cd, 'name' => ['入口ディスプレイ', 'レジ横ディスプレイ'][$i],
                'comment' => 'デモ用', 'terminal_id' => '', 'terminal_type' => '', 'rank' => $i,
                'tb_c7_movie_program_cds' => (string) $program->cd, 'disp_flg' => '1',
            ]);
        }
    }

    private function seedPaperCards(): void
    {
        $service = app(PaperCardService::class);
        foreach (self::PAPER_CARD_IDS as $i => $id) {
            if (PaperCard::query()->where('id', $id)->exists()) {
                continue;
            }
            $service->handle([
                'type' => 'new', 'id' => $id,
                'url' => 'https://example.com/'.substr($id, 5),
                'title' => ['メニュー表', 'クーポン', '求人情報'][$i],
            ]);
        }
    }

    /** ICカード: cinco tarjetas leídas; las tres primeras asignadas a socios sin tarjeta (si los hay) */
    private function seedFelicaCards(): void
    {
        if (FelicaCard::query()->where('cid', 'like', self::FELICA_PREFIX.'%')->exists()) {
            return;
        }
        $members = $this->members(20)->filter(fn (Customer $c) => (string) $c->felica_no === '')->take(3)->values();
        if ($members->isEmpty()) {
            $this->command?->warn('  sin socios libres: ICカード saltadas');

            return;
        }

        $service = app(FelicaCardService::class);
        for ($i = 1; $i <= 5; $i++) {
            $card = FelicaCard::create([
                'tb_shop_cd' => $this->shop?->cd, 'cid' => sprintf('%s%09d', self::FELICA_PREFIX, $i),
                'typ' => '1', 'typ_exp' => $i % 2 ? '非モバイルFellica' : 'TypeA マイフェア 1k',
                'type' => 'FeliCa', 'flg' => '1', 'point' => 0, 'lost' => 0,
            ]);
            if ($member = $members->get($i - 1)) {
                try {
                    $service->assign($card, $member);
                } catch (Throwable $e) {
                    $this->command?->warn('  ICカード no asignada: '.$e->getMessage());
                }
            }
        }
    }

    // ------------------------------------------------------------------ signage/ (BD `admin`, esquema provisional)

    private function seedSignageWeb(): void
    {
        try {
            $ready = Schema::connection('signage')->hasTable('packages');
        } catch (Throwable) {
            $ready = false;
        }
        if (! $ready) {
            $this->command?->warn('  sin el esquema de signage/ (bd-legacy.sh): saltado');

            return;
        }
        if (SignageWebPackage::query()->where('name', self::SIGNAGE_PACKAGE)->exists()) {
            return;
        }

        $category = SignageWebContentCategory::create(['name' => 'フルHD', 'width' => 1920, 'height' => 1080, 'flg' => 1]);
        $contents = app(SignageContentService::class);
        $cds = [];
        foreach (['ようこそ', 'おすすめ', 'イベント'] as $i => $name) {
            $cds[] = $contents->save('image', $category, ['name' => $name], $this->upload(1920, 1080, 'WEB SIGNAGE '.($i + 1), $i + 3))->cd;
        }

        $package = SignageWebPackage::create([
            'name' => self::SIGNAGE_PACKAGE, 'flg' => 1, 'cycle' => 3,
            'display_direction' => '横', 'display_number' => 1, 'display_type' => 'h_1_1',
        ]);
        foreach ($cds as $cd) {
            SignageWebCycle::create([
                'packages_cd' => $package->cd, 'flg' => 1, 'status' => 1, 'flame_cd' => 1,
                'schedule_repeat_type' => SignageWebCycle::ALWAYS,
                'img_path_ary' => serialize([[0 => (string) $cd, 1 => 'blank']]),
                'action_ary' => serialize([['action' => '3']]),
            ]);
        }
        foreach (['入口', '待合'] as $name) {
            SignageWebTerminal::create([
                'name' => $name, 'flg' => 1, 'packages_cd' => $package->cd,
                'display_direction' => '横', 'display_number' => 1, 'display_type' => 'h_1_1',
            ]);
        }
        SignageWebCustomer::create(['name' => 'デモ株式会社', 'flg' => 1]);
    }

    // ------------------------------------------------------------------ casos límite

    /** オススメ: 12 más (paginan a 10), fechas de fin de mes/año y 29-02, japonés largo, sin imagen, baja y oculta en la app */
    private function seedRecommendationEdges(): void
    {
        $rows = [
            ['title' => $this->long('【長文タイトル】春夏秋冬の限定メニューとポイント還元キャンペーンのお知らせ', 100), 'naiyou' => $this->long('ご来店いただき誠にありがとうございます。期間中は全商品ポイント二倍です。', 1000), 'date' => now()->subMonthNoOverflow()->endOfMonth()],
            ['title' => '年末年始の営業時間について', 'naiyou' => '12月31日は18時閉店、1月1日は休業いたします。', 'date' => now()->subYear()->endOfYear()],
            ['title' => '閏日限定イベント', 'naiyou' => '2月29日だけの特別イベントです。', 'date' => Carbon::create(2024, 2, 29)],
            ['title' => '新年のごあいさつ', 'naiyou' => '', 'date' => now()->startOfYear()],
            ['title' => 'ﾊﾝｶｸｶﾅ ﾉ ｵｼﾗｾ', 'naiyou' => 'ｱﾌﾟﾘ ｶｲｲﾝ ｻﾏ ｹﾞﾝﾃｲ', 'date' => now()->subDays(1)],
            ['title' => '来週公開予定のお知らせ', 'naiyou' => $this->faker->realText(60), 'date' => now()->addWeek()],
            ['title' => 'アプリ非表示のお知らせ', 'naiyou' => 'アプリには表示されません。', 'date' => now()->subDays(20), 'hyouji_flg' => Recommendation::HIDDEN_APP],
            ['title' => '削除済みのお知らせ', 'naiyou' => '一覧には出ません。', 'date' => now()->subDays(30), 'disp_flg' => '9'],
            ['title' => '梅雨の特別メニュー', 'naiyou' => $this->faker->realText(100), 'date' => now()->subMonths(5)->endOfMonth()],
            ['title' => '夏休みファミリー割引', 'naiyou' => $this->faker->realText(100), 'date' => now()->subMonths(4)],
            ['title' => '秋の収穫祭フェア', 'naiyou' => $this->faker->realText(100), 'date' => now()->subMonths(2)],
            ['title' => 'リンク付きのお知らせ', 'naiyou' => '詳しくはリンク先をご覧ください。', 'date' => now()->subDays(10), 'link' => 'https://example.com/news'],
        ];
        foreach ($rows as $i => $row) {
            if (Recommendation::query()->withDeleted()->where('title', $row['title'])->exists()) {
                continue;
            }
            Recommendation::create([
                'tb_shop_cd' => $i % 3 === 0 ? null : $this->shopAt($i)?->cd,
                'disp_date' => $row['date']->format('Y/m/d'),
                'title' => $row['title'], 'naiyou' => $row['naiyou'],
                'link' => $row['link'] ?? '', 'pic_link' => '', 'link_type' => '',
                'hyouji_flg' => $row['hyouji_flg'] ?? '', 'img_flg' => '', 'img_type' => '',
                'disp_flg' => $row['disp_flg'] ?? '1',
            ]);
        }
    }

    /** 求人: 20 職種 más (paginan a 20; uno de baja, uno de 全店舗) y 11 求人 (paginan a 10) en varias tiendas */
    private function seedRecruitEdges(): void
    {
        $names = ['店長候補', '調理補助', '洗い場スタッフ', 'デリバリースタッフ', 'レジスタッフ', '清掃スタッフ', 'バリスタ', 'パティシエ',
            'ソムリエ', '受付スタッフ', '事務スタッフ', '仕込みスタッフ', '早朝スタッフ', '深夜スタッフ', '学生アルバイト', '主婦（夫）パート',
            'シニアスタッフ', '短期スタッフ', $this->long('【長い職種名】アミューズメント施設のホール兼カウンター兼イベント運営スタッフ', 50), '削除済み職種'];
        $categories = [];
        foreach ($names as $i => $name) {
            $categories[$i] = RecruitCategory::query()->withDeleted()->where('shokushu', $name)->first()
                ?? RecruitCategory::create([
                    'shokushu' => $name,
                    'tb_shop_cd' => $i === 0 ? (int) RecruitCategoryService::ALL_SHOPS : (int) $this->shopAt($i)?->cd,
                    'disp_flg' => $name === '削除済み職種' ? '9' : '1',
                ]);
        }

        $created = false;
        for ($i = 1; $i <= 11; $i++) {
            $headline = match ($i) {
                1 => $this->long('【長文】駅チカ徒歩一分・まかない無料・制服貸与・交通費全額支給・未経験大歓迎のスタッフ募集', 100),
                2 => '任意項目なしの求人',
                3 => '削除済みの求人',
                4 => '削除済み職種の求人',
                5 => 'ﾊﾝｶｸｶﾅ ｷｭｳｼﾞﾝ',
                default => $names[$i].'募集（'.$this->shopAt($i)?->kaishamei.'）',
            };
            if (Recruit::query()->withDeleted()->where('boshu_headline', $headline)->exists()) {
                continue;
            }
            $full = $i !== 2;
            $max = fn (string $seed) => $i === 1 ? $this->long($seed, 400) : $seed;
            Recruit::create([
                'tb_shop_cd' => (int) $this->shopAt($i)?->cd,
                'boshu_shokushu' => $categories[$i === 4 ? 19 : $i]->cd,
                'boshu_headline' => $headline,
                'shigoto_naiyou' => $max('接客、配膳、レジ、簡単な調理などをお任せします。'),
                'koyou_keitai' => $full ? 'アルバイト・パート' : '',
                'oubo_shikaku' => $full ? '未経験者歓迎' : '',
                'kinmu_chi' => $full ? (string) $this->shopAt($i)?->kaishamei : '',
                'kinmu_jikan' => $full ? '00:00〜23:59（シフト制）' : '',
                'kyuyo' => $full ? '時給'.number_format(1000 + $i * 37).'円〜' : '',
                'kyuka' => $full ? '週休2日' : '', 'taigu' => $full ? '交通費支給' : '',
                'kaisha_name' => $full ? (string) $this->shopAt($i)?->kaishamei : '',
                'add' => $max($full ? $this->faker->address : ''),
                'tel' => $full ? sprintf('090-0000-%04d', 200 + $i) : '', 'fax' => '',
                'jigyou_naiyou' => $max($full ? '飲食店の運営' : ''),
                'oubo_houhou' => $max($full ? 'お電話にてご連絡ください。' : ''),
                'mensetsuchi' => $max($full ? '店舗' : ''),
                'tantou' => $full ? $this->faker->lastName : '',
                'pic_flg1' => '', 'pic_flg2' => '',
                'disp_flg' => $i === 3 ? '9' : '1',
            ]);
            $created = true;
        }

        if ($created) {
            app(SignageFeedBuilder::class)->writeFloormap();
        }
    }

    /** ガチャリン: tiradas con premio caducado (fin del año pasado y hace 45 días; plazo de 30 días) */
    private function seedLotteryEdges(): void
    {
        if (KujibikiLog::query()->where('time_limit', '<', now()->getTimestamp())->exists()) {
            return;
        }
        $members = $this->members(3);
        if ($members->isEmpty()) {
            return;
        }
        $lottery = app(LotteryService::class)->useRandom(fn (int $min, int $max) => mt_rand($min, $max));
        foreach ($members as $member) {
            foreach ([now()->subYear()->endOfYear()->setTime(12, 0), now()->subDays(45)->setTime(12, 0)] as $at) {
                $lottery->draw((int) $member->cd, $at);
            }
        }
    }

    /**
     * 電子看板: los 放映素材 de la demo van al primer display (el listado filtra por él, como el legacy); 11 素材,
     * 10 プログラム y 10 ディスプレイ más en varias tiendas (paginan a 10), con bajas, nombres al máximo (100),
     * opcionales vacíos, システム内蔵 y un display sin programa.
     */
    private function seedDigitalSignageEdges(): void
    {
        if ($this->shop === null) {
            return;
        }
        $displays = SignageDisplay::query()->where('disp_flg', '1')->orderBy('rank')->orderBy('cd')->get();
        if ($first = $displays->first()) {
            SignageMaterial::query()->whereNull('tb_display_cd')->where('comment', 'デモ素材')
                ->update(['tb_display_cd' => $first->cd]);
        }
        if ($first === null || SignageProgram::query()->withDeleted()->where('name', 'like', 'デモ 限界%')->exists()) {
            return;
        }

        $uploader = app(SignageMaterialUploader::class);
        $materials = [];
        for ($i = 1; $i <= 11; $i++) {
            $material = SignageMaterial::create([
                'tb_shop_cd' => $this->shopAt($i)->cd,
                'tb_display_cd' => $i <= 9 ? $first->cd : $displays->get(1)?->cd,
                'title' => $i === 1 ? $this->long('【最大文字数】季節限定ランチセットのご案内', 100) : sprintf('素材 %02d 季節のおすすめ', $i),
                'comment' => $i === 1 ? $this->long('コメント欄も上限の百文字まで入力した素材です。', 100) : ($i === 2 ? '' : 'デモ限界素材'),
                'categorize_value_0' => $i === 1 ? 'あいうえおかきくけこ' : ($i % 2 ? 'メニュー' : 'お知らせ'),
                'play_frame_sec' => $i === 1 ? '999' : ($i === 2 ? '' : '5'),
                'filetype' => SignageMaterial::FILETYPE_IMAGE, 'rank' => 2 + $i,
                'disp_flg' => $i === 11 ? '9' : '1',
            ]);
            if ($i !== 2) {   // el 2 sin imagen
                $uploader->apply($material, [], [], ['photo0' => $this->upload(640, 360, sprintf('EDGE %02d', $i), $i)]);
            }
            $materials[] = $material->cd;
        }

        $feed = app(SignageFeedBuilder::class);
        for ($i = 1; $i <= 10; $i++) {
            $program = new SignageProgram([
                'tb_shop_cd' => $this->shopAt($i)->cd,
                'name' => $i === 1 ? $this->long('デモ 限界 最大文字数のプログラム名称（店頭・待合・レジ横共通）', 100) : sprintf('デモ 限界 プログラム %02d', $i),
                'comment' => $i === 2 ? '' : 'デモ用', 'terminal_id' => '', 'terminal_type' => '', 'rank' => 1 + $i,
                'disp_conf' => $i === 3 ? SignageProgram::CONF_SYSTEM : SignageProgram::CONF_FILES,
                'act_flg' => '', 'feli_act_flg' => '', 'mypage_act_flg' => '',
                'telop_comment' => $i === 1 ? $this->long('ご来店ありがとうございます。本日のおすすめは季節の限定ランチです。', 200) : '',
                'vertical_setting' => $i % 2 ? 'landscape' : 'portrait',
                'disp_flg' => $i === 10 ? '9' : '1',
            ]);
            $program->setMaterialCds($i === 3 ? [] : array_slice($materials, 0, 1 + $i % 5))->save();
            if ($i !== 3) {
                $feed->writeProgram($program->cd);
            }

            SignageDisplay::create([
                'tb_shop_cd' => $this->shopAt($i)->cd,
                'name' => $i === 1 ? $this->long('デモ 限界 最大文字数のディスプレイ名称（入口・待合・レジ横）', 100) : sprintf('デモ 限界 ディスプレイ %02d', $i),
                'comment' => $i === 2 ? '' : 'デモ用', 'terminal_id' => '', 'terminal_type' => '', 'rank' => 9 + $i,
                'tb_c7_movie_program_cds' => $i === 4 ? '' : (string) $program->cd,   // el 4 sin programa
                'disp_flg' => $i === 10 ? '9' : '1',
            ]);
        }
    }

    /** ペーパーサイネージ: 10 más (paginan a 10), dos 停止, una de baja, título largo y título vacío */
    private function seedPaperCardEdges(): void
    {
        $service = app(PaperCardService::class);
        for ($i = 1; $i <= 10; $i++) {
            $id = sprintf('demo-edge-%02d', $i);
            if (PaperCard::query()->withDeleted()->where('id', $id)->exists()) {
                continue;
            }
            $service->handle([
                'type' => 'new', 'id' => $id,
                'url' => $i === 1 ? 'https://example.com/'.str_repeat('long-path/', 20).'?utm_source=paper&utm_medium=qr' : "https://example.com/edge/{$i}",
                'title' => match ($i) {
                    1 => $this->long('【長いタイトル】季節限定メニューと会員様向けクーポンのご案内', 100),
                    2 => '',
                    3 => 'ﾊﾝｶｸｶﾅ ﾀｲﾄﾙ',
                    default => sprintf('店頭POP %02d', $i),
                },
            ]);
            $card = PaperCard::query()->where('id', $id)->first();
            match (true) {
                $card === null => null,
                in_array($i, [4, 5], true) => $card->update(['hyouji_flg' => PaperCard::STOPPED]),
                $i === 6 => $card->delete(),
                default => null,
            };
        }
    }

    /** ICカード sin socio: 14 en varias tiendas (paginan a 10), una de baja (flg 9), una perdida y una de tipo oculto */
    private function seedFelicaCardEdges(): void
    {
        if (FelicaCard::query()->where('cid', 'like', self::FELICA_EDGE_PREFIX.'%')->exists()) {
            return;
        }
        for ($i = 1; $i <= 14; $i++) {
            FelicaCard::create([
                'tb_shop_cd' => $this->shopAt($i)?->cd, 'cid' => sprintf('%s%08d', self::FELICA_EDGE_PREFIX, $i),
                'typ' => '1', 'typ_exp' => $i === 3 ? 'Mifare Ultralight' : FelicaCard::VISIBLE_TYPES[$i % 2],
                'type' => 'FeliCa', 'flg' => $i === 1 ? FelicaCard::FLG_HIDDEN : '1',
                'point' => $i === 4 ? 99999 : 0, 'lost' => $i === 2 ? 1 : 0,
                'comment' => $i === 5 ? $this->long('紛失届あり・再発行待ちのカードです。', 100) : '',
            ]);
        }
    }

    /** ICカード 端末: 12 (paginan a 10) en varias tiendas, ピットタッチスリム (端末番号) y ビズ (MAC), uno de baja */
    private function seedFelicaTerminals(): void
    {
        if ($this->shop === null || Terminal::query()->where('name', 'like', 'デモ端末%')->exists()) {
            return;
        }
        for ($i = 1; $i <= 12; $i++) {
            $slim = $i % 2 === 1;
            Terminal::create([
                'tb_shop_cd' => $this->shopAt($i)->cd, 'rank' => $i,
                'name' => sprintf('デモ端末 %02d', $i).($i === 1 ? $this->long(' 入口・レジ横・待合スペース共用', 40) : ''),
                'comment' => $i === 2 ? '' : 'デモ用',
                'type' => $slim ? 'ピットタッチスリム' : 'ピットタッチビズ',
                'terminal_no' => $slim ? sprintf('PTS%07d', $i) : '',
                'mac_address' => $slim ? '' : sprintf('00:00:5E:00:53:%02X', $i),
                'flg' => $i === 12 ? '9' : '1',
            ]);
        }
    }

    /**
     * signage/: paquete «デモ 季節» con ciclos por periodo (表示方法 3) que acaban a fin de mes, a fin de año, el 29-02
     * y uno ya caducado, dos que se solapan y uno diario; su terminal; y 3 お問い合わせ de GoTel (uno largo, uno sin nombre).
     */
    private function seedSignageWebEdges(): void
    {
        try {
            $ready = Schema::connection('signage')->hasTable('packages');
        } catch (Throwable) {
            $ready = false;
        }
        if (! $ready || SignageWebPackage::query()->where('name', self::SIGNAGE_EDGE_PACKAGE)->exists()) {
            return;
        }
        $content = SignageWebPackage::query()->where('name', self::SIGNAGE_PACKAGE)->exists()
            ? (string) SignageWebContent::query()->orderBy('cd')->value('cd')
            : 'blank';

        $leap = now()->year;
        while (! Carbon::create($leap)->isLeapYear()) {
            $leap++;
        }
        $periods = [
            [now()->startOfMonth(), now()->endOfMonth()],                               // fin de mes
            [now()->startOfMonth()->addDays(20), now()->endOfYear()],                     // fin de año (se solapa con el anterior)
            [now()->endOfYear()->subDays(6), now()->endOfYear()->addDays(5)->endOfDay()], // cruza el año
            [Carbon::create($leap, 2, 1), Carbon::create($leap, 2, 29)->endOfDay()],      // 29-02
            [now()->subYear()->startOfYear(), now()->subYear()->endOfYear()],             // caducado
        ];
        $package = SignageWebPackage::create([
            'name' => self::SIGNAGE_EDGE_PACKAGE, 'flg' => 1, 'cycle' => count($periods) + 1,
            'display_direction' => '横', 'display_number' => 1, 'display_type' => 'h_1_1',
        ]);
        $cycle = fn (int $type, ?Carbon $start, ?Carbon $end) => SignageWebCycle::create([
            'packages_cd' => $package->cd, 'flg' => 1, 'status' => 1, 'flame_cd' => 1,
            'schedule_repeat_type' => $type, 'schedule_start_time' => $start, 'schedule_end_time' => $end,
            'img_path_ary' => serialize([[0 => $content, 1 => 'blank']]),
            'action_ary' => serialize([['action' => '1']]),
        ]);
        foreach ($periods as [$start, $end]) {
            $cycle(SignageWebCycle::PERIOD, $start, $end);
        }
        $cycle(SignageWebCycle::DAILY, now()->setTime(9, 0), now()->setTime(23, 59, 59));

        SignageWebTerminal::create([
            'name' => '季節 端末', 'flg' => 1, 'packages_cd' => $package->cd,
            'display_direction' => '横', 'display_number' => 1, 'display_type' => 'h_1_1',
        ]);
        foreach ([
            ['subject' => '【お問い合わせ】画面が表示されない', 'name' => 'デモ 太郎', 'message' => $this->long('端末の電源は入っていますが、コンテンツが切り替わりません。', 1000)],
            ['subject' => '【内線追加】顧客番号:1の内線追加に関しまして', 'name' => '', 'message' => '2台の内線を追加ください。'],
            ['subject' => '【お問い合わせ】', 'name' => 'ﾃﾞﾓ ﾊﾅｺ', 'message' => ''],
        ] as $contact) {
            SignageWebContact::create($contact);
        }
    }

    // ------------------------------------------------------------------ apoyo

    /** Tienda i-ésima de las de la demo (rota entre ellas) */
    private function shopAt(int $i): ?Shop
    {
        return $this->shops->isEmpty() ? null : $this->shops->get($i % $this->shops->count());
    }

    /** Texto japonés repetido hasta exactamente $length caracteres (máximo del campo) */
    private function long(string $seed, int $length): string
    {
        return mb_substr(str_repeat($seed, intdiv($length, mb_strlen($seed)) + 1), 0, $length);
    }

    /** Socios activos (本登録), los primeros por cd */
    private function members(int $limit)
    {
        return Customer::query()->members()->where('disp_flg', '1')->orderBy('cd')->limit($limit)->get();
    }

    /** JPEG de color liso con un rótulo (placeholder local, sin red) */
    private function image(int $width, int $height, string $label, int $color): string
    {
        $palette = [[231, 111, 81], [42, 157, 143], [233, 196, 106], [38, 70, 83], [244, 162, 97], [131, 56, 236], [58, 134, 255], [255, 0, 110], [6, 214, 160]];
        [$r, $g, $b] = $palette[$color % count($palette)];
        $img = imagecreatetruecolor($width, $height);
        imagefill($img, 0, 0, imagecolorallocate($img, $r, $g, $b));
        $white = imagecolorallocate($img, 255, 255, 255);
        $font = 5;
        $x = max(0, intdiv($width - imagefontwidth($font) * strlen($label), 2));
        $y = max(0, intdiv($height - imagefontheight($font), 2));
        imagestring($img, $font, $x, $y, $label, $white);
        ob_start();
        imagejpeg($img, null, 85);

        return (string) ob_get_clean();
    }

    /** El mismo placeholder como archivo subido, para pasar por los servicios de subida */
    private function upload(int $width, int $height, string $label, int $color): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'demo').'.jpg';
        file_put_contents($path, $this->image($width, $height, $label, $color));

        return new UploadedFile($path, 'demo.jpg', 'image/jpeg', null, true);
    }
}
