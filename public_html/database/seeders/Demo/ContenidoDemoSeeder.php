<?php

namespace Database\Seeders\Demo;

use App\Models\ContentsMaster;
use App\Models\Customers\Customer;
use App\Models\Mypage\Recommendation;
use App\Models\Mypage\Recruit;
use App\Models\Mypage\RecruitCategory;
use App\Models\Promotions\KujibikiLog;
use App\Models\Senalizacion\SignageWebContentCategory;
use App\Models\Senalizacion\SignageWebCustomer;
use App\Models\Senalizacion\SignageWebCycle;
use App\Models\Senalizacion\SignageWebPackage;
use App\Models\Senalizacion\SignageWebTerminal;
use App\Models\Shop;
use App\Models\Terminales\FelicaCard;
use App\Models\Terminales\PaperCard;
use App\Models\Terminales\SignageDisplay;
use App\Models\Terminales\SignageMaterial;
use App\Models\Terminales\SignageProgram;
use App\Services\Mypage\AppImageFiles;
use App\Services\Mypage\LotteryConfig;
use App\Services\Mypage\MypageConfig;
use App\Services\Mypage\RecommendationService;
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

    private Generator $faker;

    private ?Shop $shop = null;

    public function run(): void
    {
        $this->faker = \Faker\Factory::create('ja_JP');
        $this->faker->seed(20261128);
        mt_srand(20261128);
        $this->shop = Shop::query()->where('disp_flg', '1')->orderBy('cd')->first();

        foreach ([
            'マイコレ' => 'seedMycolle',
            'オススメ' => 'seedRecommendations',
            '求人' => 'seedRecruits',
            'ガチャリン' => 'seedLottery',
            '電子看板' => 'seedDigitalSignage',
            'ペーパーサイネージ' => 'seedPaperCards',
            'ICカード' => 'seedFelicaCards',
            'signage/' => 'seedSignageWeb',
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
                'tb_shop_cd' => $i === 2 ? $this->shop?->cd : 0,
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

    // ------------------------------------------------------------------ apoyo

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
