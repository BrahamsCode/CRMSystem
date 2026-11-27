<?php

namespace Database\Seeders\Demo;

use App\Services\Nucleo\Hatuban;
use App\Services\Promotions\LegacyTime;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Datos de demostración de 販売促進管理 (S3). CONVENCIONES §4 «Datos de demostración».
 *
 * Configuración (siempre): 8 cupones, ポイント発行ルール y avisos de caducidad, スタンプ (config si no hay, reglas y
 * aviso de canje), 15 envíos ya hechos (texto, デコメール, cupón y アンケート), 3 programados, reglas de フォロー/自動
 * de correo y de push, 2 encuestas (アンケート y お客様アンケート), historial de push y 5 新着情報.
 * Por cliente (solo si hay socios activos, p. ej. los de ClientesDemoSeeder): cupones entregados y usados,
 * destinatarios (mailmag_sended_log), clics (クリックカウンター), entregas y aperturas de push y respuestas.
 *
 * Nada sale de verdad: los programados no tienen fila en send_schedule y las reglas de フォロー/自動 (correo y push)
 * quedan en 停止 (disp_flg = 7); se ponen en marcha desde sus pantallas. DM発行 y QR no guardan datos en el legacy:
 * salen de los socios y de admin_master.
 *
 * Idempotente: marca 備考 = DEMO_MARK en sus mailmag_master; la parte por cliente se marca con sus destinatarios.
 */
class PromocionDemoSeeder extends Seeder
{
    public const DEMO_MARK = '[demo-promocion]';

    /** Marca de los casos límite (envíos, cupones, push, encuestas y rangos de la tercera pasada) */
    public const EDGE_MARK = '[demo-promocion-limites]';

    /** Tiendas activas (cd), para repartir los datos; -1 = 本部 */
    private array $shops = [];

    /** @var array<string, int> cupones de los casos límite */
    private array $edgeCoupons = [];

    private Carbon $now;

    private int $shopCd;

    /** @var array<string, int> */
    private array $coupons = [];

    /** @var array<string, int> */
    private array $surveys = [];

    /** @var array<string, bool> contadores de hatuban ya ajustados en esta ejecución */
    private array $synced = [];

    /** @var array<string, bool> tablas que en esta BD ya tienen cd en formato legacy (no caben en hatuban) */
    private array $legacyCd = [];

    /** Tablas que sus pantallas numeran con createHatsuban (epoch + tienda + aleatorio), no con hatuban */
    private const LEGACY_CD_TABLES = ['point_shikkou_alert'];

    /** hatuban.kazu es integer */
    private const HATUBAN_MAX = 2147483647;

    public function run(): void
    {
        $this->now = Carbon::now(LegacyTime::ZONE);
        $this->shopCd = (int) (DB::table('tb_shop')->where('disp_flg', '1')->orderBy('cd')->value('cd') ?? 1);
        $this->shops = DB::table('tb_shop')->where('disp_flg', '1')->orderBy('cd')->pluck('cd')->map(fn ($cd) => (int) $cd)->all() ?: [$this->shopCd];

        if (! $this->demoMails()->exists()) {
            DB::transaction(fn () => $this->seedConfiguration());
        }
        // Casos límite (tercera pasada): bloque aparte con su propia marca, para BD ya sembradas
        if (! $this->edgeMails()->exists()) {
            DB::transaction(fn () => $this->seedEdgeConfiguration());
        }

        $customers = $this->customers();
        $sentCds = $this->demoMails()->where('配信状況', '2')->pluck('cd');
        if ($customers->isEmpty()) {
            $this->command?->warn('PromocionDemoSeeder: sin socios activos; solo configuración.');

            return;
        }
        if (! DB::table('mailmag_sended_log')->whereIn('mailmag_master_cd', $sentCds)->exists()) {
            DB::transaction(fn () => $this->seedPerCustomer($customers));
        }

        $demo = $this->demoCustomers();
        $edgeSent = $this->edgeMails()->where('配信状況', '2')->pluck('cd');
        if ($demo->isEmpty()) {
            $this->command?->warn('PromocionDemoSeeder: sin socios demo (demo%@example.com); casos límite solo de configuración.');

            return;
        }
        if (! DB::table('mailmag_sended_log')->whereIn('mailmag_master_cd', $edgeSent)->exists()) {
            DB::transaction(fn () => $this->seedEdgePerCustomer($demo));
        }
    }

    // ---------------------------------------------------------------------------------------------
    // Configuración
    // ---------------------------------------------------------------------------------------------

    private function seedConfiguration(): void
    {
        $this->seedCoupons();
        $this->seedPointAndStampConfig();
        $this->seedSurveys();
        $this->seedMails();
        $this->seedPushes();
        $this->seedNews();
    }

    private function seedCoupons(): void
    {
        $at = $this->epoch($this->now->copy()->subMonths(6));
        $endOfYear = $this->epoch($this->now->copy()->addMonths(2)->endOfMonth());
        $rows = [
            'signup' => ['新規会員登録特典 ドリンク1杯無料', "ご登録ありがとうございます。\n次回ご来店時にワンドリンクサービス！", '4', '2', null, null],
            'birthday' => ['お誕生日おめでとうクーポン', "お誕生日月のご来店で\nお会計から20%OFF", '', '2', null, null],
            'return' => ['再来店クーポン 500円OFF', "前回のご来店から30日以内のご来店で\n500円OFF", '', '4', 30, null],
            'sale' => ['冬の感謝祭 全品10%OFF', "期間中、全メニュー10%OFF\n他クーポンとの併用不可", '', '5', $endOfYear, null],
            'stamp' => ['スタンプ10個達成特典', 'スタンプ10個でお好きなデザート1品プレゼント', '2', '3', null, 10],
            'referral' => ['お友達ご紹介特典', 'ご紹介いただいたお客様に次回1,000円OFF', '5', '3', null, null],
            'survey' => ['アンケート回答特典', 'アンケートにご回答いただいたお客様に5%OFF', '3', '2', null, null],
            'point' => ['ポイント交換 ソフトドリンク', 'ポイント100でソフトドリンク1杯と交換', '10', '1', null, 100],
        ];
        foreach ($rows as $key => [$title, $comment, $kubun, $limit, $timeLimit, $points]) {
            $cd = $this->nextCd('tb_mycoupon_master');
            DB::table('tb_mycoupon_master')->insert([
                'cd' => $cd, 'tb_shop_cd' => -1, 'title' => $title, 'comment' => $comment,
                'attention' => '有効期限内にご利用ください<>1回のご来店につき1枚まで',
                'type_flg' => '', 'coupon_kubun' => $kubun, 'limit_flg' => $limit,
                'time_limit' => $timeLimit === null ? null : (string) $timeLimit,
                'touroku_date' => (string) $at, 'update_date' => (string) $at, 'disp_flg' => '1', 'hyouji_flg' => '1',
                'other_use_flg' => '', 'shouhi_point' => $points, 'saitouroku_flg' => '',
            ]);
            $this->coupons[$key] = $cd;
        }
    }

    private function seedPointAndStampConfig(): void
    {
        $at = $this->epoch($this->now->copy()->subMonths(3));
        // ポイント発行ルール: ランチタイム 2倍, 毎週水曜 +3, 全日 (時間帯 0:00–23:59) は店舗ごと
        foreach ([
            ['1', 1100, 1400, null, 2],
            ['2', 0, 2359, 3, 3],
            ['1', 1700, 1900, null, 1],
        ] as [$type, $start, $end, $dow, $points]) {
            DB::table('tb_point_rules')->insert([
                'cd' => $this->nextCd('tb_point_rules'), 'tb_shop_cd' => -1, 'type_flg' => $type, 'start_time' => $start, 'end_time' => $end,
                'day_of_week' => $dow, 'point_count' => $points, 'touroku_date' => $at, 'update_date' => $at, 'hyouji_flg' => '1',
            ]);
        }
        // ポイント失効のお知らせ (point_config のアラート)
        foreach ([[30, 'ポイント失効のお知らせ（30日前）'], [7, 'ポイント失効のお知らせ（7日前）']] as $i => [$days, $title]) {
            DB::table('point_shikkou_alert')->insert([
                'cd' => $this->nextCd('point_shikkou_alert'), 'tb_shop_cd' => $this->shopCd, 'flg' => '1', 'title' => $title,
                'message' => "いつもご利用ありがとうございます。\nお持ちのポイントがあと{$days}日で失効します。ぜひご来店ください。",
                'alert_day' => (string) $days, 'alert_time' => '10:00', 'touroku_date' => $at, 'sort' => $i,
            ]);
        }

        // スタンプ: la configuración general (tb_shop_cd = 1) solo si aún no existe
        if (! DB::table('tb_stamp_config')->where('tb_shop_cd', 1)->exists()) {
            DB::table('tb_stamp_config')->insert([
                'cd' => $this->nextCd('tb_stamp_config'), 'tb_shop_cd' => 1, 'limit_flg' => '1', 'bonus_flg' => '1',
                'update_date' => (string) $at, 'stamp_max' => 10, 'touroku_bonus' => 1,
            ]);
        }
        foreach ([['1', 1100, 1400, null, 2], ['2', 0, 2359, 0, 2]] as [$type, $start, $end, $dow, $count]) {
            DB::table('tb_stamp_rules')->insert([
                'cd' => $this->nextCd('tb_stamp_rules'), 'tb_shop_cd' => -1, 'type_flg' => $type, 'start_time' => $start, 'end_time' => $end,
                'day_of_week' => $dow, 'stamp_count' => $count, 'touroku_date' => $at, 'update_date' => $at, 'hyouji_flg' => '1',
            ]);
        }
        DB::table('tb_stamp_oshirase_coupon')->insert([
            'cd' => $this->nextCd('tb_stamp_oshirase_coupon'), 'hyouji_flg' => '1', 'disp_flg' => '1', 'touroku_date' => (string) $at,
            'update_date' => (string) $at, 'rank' => 1, 'tb_shop_cd' => $this->shopCd, 'stamp_count' => 10,
            'tb_mycoupon_master_cd' => (string) $this->coupons['stamp'],
            'naiyou' => "スタンプが10個たまりました！\n特典クーポンをお受け取りください。",
        ]);
    }

    private function seedSurveys(): void
    {
        $forms = [
            ['本日のご満足度はいかがでしたか？', 'ラジオボタン', '大変満足<>満足<>普通<>不満', '1', 'checkBtn'],
            ['ご来店のきっかけを教えてください', 'チェックボックス', 'ホームページ<>SNS<>お知り合いの紹介<>通りがかり', '', ''],
            ['ご意見・ご要望', 'テキストエリア', '', '', 'writeArea'],
        ];
        foreach (['anketo' => ['ご来店満足度アンケート', 'tb_c3_anketo'], 'anketo2' => ['お客様アンケート（店内用）', 'tb_c3_anketo2']] as $family => [$name, $prefix]) {
            $cate = $this->nextCd("{$prefix}_cate");
            DB::table("{$prefix}_cate")->insert([
                'cd' => $cate, 'rank' => 1, 'name' => $name, 'comment' => 'デモ用のアンケートです', 'disp_flg' => '1',
                'hyouji_flg' => $family === 'anketo' ? '1' : null,
                'start_date' => $this->epoch($this->now->copy()->subMonths(2)->startOfDay()),
                'end_date' => $this->epoch($this->now->copy()->addMonths(2)->endOfDay()),
                'touroku_date' => (string) $this->epoch($this->now->copy()->subMonths(2)),
                'hakkou_date' => $family === 'anketo' ? (string) $this->epoch($this->now->copy()->subMonths(1)) : null,
                'attached_cd' => $family === 'anketo' ? $this->coupons['survey'] : null,
            ]);
            foreach ($forms as $i => [$label, $type, $choices, $required, $class]) {
                DB::table("{$prefix}_form")->insert([
                    'cd' => $this->nextCd("{$prefix}_form"), 'tb_c3_anketo_cate_cd' => $cate, 'rank' => $i + 1, 'name' => $label,
                    'form_type' => $type, 'flds_row' => $choices === '' ? null : 2, 'titles' => $choices, 'data' => $choices,
                    'hissu' => $required, 'hyouji_flg' => '1', 'disp_flg' => '1', 'style_class' => $class,
                ]);
            }
            $this->surveys[$family] = $cate;
        }
    }

    private function seedMails(): void
    {
        $where = "WHERE disp_flg = '1' AND touroku_kubun = '2'";
        $sent = [
            // [días atrás, tipo, título, cupón]
            [170, '', '【新メニューのお知らせ】春の限定メニュー登場', null],
            [150, 'deco', '春のおすすめコース', null],
            [130, '', 'ゴールデンウィークの営業時間について', null],
            [115, 'coupon', '【会員様限定】再来店クーポンのお届け', 'return'],
            [100, 'deco', '梅雨のキャンペーンのご案内', null],
            [85, '', '夏季休業のお知らせ', null],
            [70, '', '【ポイント2倍】ランチタイムキャンペーン', null],
            [60, 'deco', '夏の特別メニュー', null],
            [48, 'coupon', 'お誕生日月のお客様へ', 'birthday'],
            [40, 'anketo', 'ご来店満足度アンケートのお願い', null],
            [32, '', '秋の味覚フェア開催中', null],
            [24, 'deco', '秋の味覚フェア（デコメール版）', null],
            [16, 'coupon', '【冬の感謝祭】全品10%OFFクーポン', 'sale'],
            [9, '', '年末年始の営業日のお知らせ', null],
            [3, 'deco', '忘年会・新年会のご予約受付中', null],
        ];
        foreach ($sent as [$daysAgo, $type, $title, $coupon]) {
            $at = $this->now->copy()->subDays($daysAgo)->setTime(10, 0);
            $this->insertMail($type, $title, $this->mailBody($type, $title), $at, '2', $where, [
                'type_data_cd' => $type === 'anketo' ? $this->surveys['anketo'] : ($coupon ? $this->coupons[$coupon] : null),
            ]);
        }

        // 予約配信 (sin send_schedule: no salen solos; al editarlos desde 予約メール配信 se programan)
        foreach ([[4, '', 'クリスマス限定コースのご案内'], [9, 'deco', '新年のご挨拶'], [13, 'coupon', '【新春】お年玉クーポン']] as [$daysAhead, $type, $title]) {
            $this->insertMail($type, $title, $this->mailBody($type, $title), $this->now->copy()->addDays($daysAhead)->setTime(11, 30), '1', $where, [
                'type_data_cd' => $type === 'coupon' ? $this->coupons['sale'] : null,
            ]);
        }

        // フォロー / 自動 (配信状況 1, 停止)
        $visitSql = "AND cd IN (select user_cd from tb_raiten_rireki where coalesce(disp_flg, '') = '' group by user_cd having max(raiten_time::integer) >= extract(epoch from timestamp 'today'-interval '30 days') and max(raiten_time::integer) < extract(epoch from timestamp 'tomorrow'-interval '30 days'))";
        $birthSql = "AND SPLIT_PART(kojin_birth,'/',2)=TO_CHAR(timestamp 'today'+ interval '7days','MM') AND SPLIT_PART(kojin_birth,'/',3)=TO_CHAR(timestamp 'today'+ interval '7days','DD')";
        $rules = [
            ['follow', 'ご来店ありがとうございました', "{$where} {$visitSql}", '最終来店日から30日後', '-1<>1<>30', '10:00', null],
            ['follow', 'もうすぐお誕生日ですね', "{$where} {$birthSql}", '誕生日から7日前', '-1<>3<>7', '09:00', 'birthday'],
            ['auto', '毎月1日のお知らせ', $where, '全員', '1<>1,2,3,4,5,6,7,8,9,10,11,12<>1', '12:00', null],
        ];
        foreach ($rules as [$type, $title, $sql, $display, $parameter, $time, $coupon]) {
            $this->insertMail($type, $title, $this->mailBody('', $title), $this->now->copy()->subMonths(2), '1', $sql, [
                '配信日時' => $time, '配信日' => null, '配信時間' => $time, 'send_parameter' => $parameter, 'search_display' => $display,
                'disp_flg' => '7', 'type_data_cd' => $coupon ? $this->coupons[$coupon] : null,
            ]);
        }
    }

    /** Una fila de mailmag_master con los campos que pone MailRegistrar::register */
    private function insertMail(string $type, string $title, string $body, Carbon $at, string $status, string $where, array $extra = []): int|string
    {
        $cd = $this->nextCd('mailmag', 'mailmag_master');
        DB::table('mailmag_master')->insert($extra + [
            'cd' => $cd, '店id' => (string) $this->shopCd,
            '投稿日時' => $at->copy()->subDay()->format('Y/m/d H:i:s'), '投稿日' => $at->copy()->subDay()->format('Y/m/d'), '投稿時間' => $at->format('H:i:s'),
            'タイトル' => $title, 'pc内容' => $body,
            '配信日時' => $at->format('Y/m/d H:i:s'), '配信日' => $at->format('Y/m/d'), '配信時間' => $at->format('H:i:s'),
            'pc配信数' => 0, 'docomo配信数' => 0, 'au配信数' => 0, 'vodafone配信数' => 0, 'その他配信数' => 0, '配信総数' => 0,
            // 登録者 = フッター表記店舗 (tourokusya: cd de tb_shop o -1); la lista de un usuario de tienda filtra por él
            '登録者' => (string) $this->shopCd, '備考' => self::DEMO_MARK, '受付メールアドレス' => 'なし', '配信状況' => $status,
            '画像有無' => '0', 'type_flg' => $type, 'search_display' => '全員', '振分配信' => $where, '振分条件' => '',
            'disp_flg' => '',
        ]);

        return $cd;
    }

    private function mailBody(string $type, string $title): string
    {
        $text = "%name%様\n\nいつもご利用いただき誠にありがとうございます。\n{$title}のご案内です。\n\nスタッフ一同、皆様のご来店を心よりお待ちしております。";

        return $type === 'deco'
            ? '<p style="text-align:center;"><span style="color:#cc0000;font-size:large;"><b>'.e($title).'</b></span></p><p>'.nl2br(e($text)).'</p>'
            : $text;
    }

    private function seedPushes(): void
    {
        foreach ([
            [140, 'ランチタイムポイント2倍キャンペーン', '本日11時〜14時のご来店でポイント2倍！', null],
            [75, '夏季休業のお知らせ', '8月13日〜16日は休業いたします。', null],
            [28, '秋の味覚フェア開催中', '旬の食材を使った限定メニューをご用意しました。', 50],
            [5, '忘年会のご予約受付中', '10名様以上のご予約でドリンク1杯サービス。', null],
        ] as [$daysAgo, $title, $message, $point]) {
            $at = $this->epoch($this->now->copy()->subDays($daysAgo)->setTime(12, 0));
            DB::table('push_master')->insert([
                'cd' => $this->nextCd('push_master'), 'tb_shop_cd' => -1, 'title' => $title, 'message' => serialize([$message]),
                'img_flg' => '', 'wherestr' => "WHERE disp_flg = '1' AND touroku_kubun = '2'", 'search_display' => '全員', 'disp_flg' => '1',
                'touroku_date' => $at, 'update_date' => $at, 'pos_search' => '', 'pos_tb_shop_cd' => 0, 'version' => 2,
                'post_type' => $point ? '2' : '1', 'post_point' => $point, 'point_expiration_date' => $point ? $at + 30 * 86400 : null,
                'extra_data' => null, 'type_flg' => null, 'pic_link' => serialize([]),
            ]);
        }

        $at = $this->epoch($this->now->copy()->subMonths(2));
        foreach ([
            ['follow', 'またのご来店をお待ちしております', 'ご来店ありがとうございました。次回使えるクーポンをお届けします。', ''],
            ['auto', '週末のおすすめ', '今週末のおすすめメニューをチェック！', '2<>5'],
        ] as [$type, $title, $message, $parameter]) {
            DB::table('push_follow_master')->insert([
                'cd' => $this->nextCd('push_follow_master'), 'tb_shop_cd' => -1, 'title' => $title, 'message' => serialize([$message]),
                'wherestr' => "WHERE disp_flg = '1' AND touroku_kubun = '2'", 'search_display' => '全員',
                'disp_flg' => '7', 'post_point' => null, 'point_expiration_days' => null, 'type_flg' => $type, 'send_time' => '10:00',
                'send_parameter' => $parameter, 'next_send_date' => null, 'touroku_date' => $at, 'update_date' => $at,
                'pos_search' => '', 'pos_tb_shop_cd' => 0, 'pic_link' => serialize([]),
            ]);
        }
    }

    private function seedNews(): void
    {
        foreach (['年末年始の営業時間のお知らせ', '冬の感謝祭を開催します', '新メニュー「季節の御膳」登場', 'ポイントカードがアプリになりました', '駐車場拡張工事のお知らせ'] as $i => $title) {
            DB::table('tb_digital_signage')->insert([
                'cd' => $this->nextCd('tb_digital_signage'), 'title' => $title, 'play_time' => 15, 'rank' => $i + 1,
                'update' => (string) $this->epoch($this->now->copy()->subDays(4 + $i * 9)), 'disp_flg' => '1',
            ]);
        }
    }

    // ---------------------------------------------------------------------------------------------
    // Por cliente
    // ---------------------------------------------------------------------------------------------

    private function seedPerCustomer(Collection $customers): void
    {
        $this->loadConfiguration();
        $this->seedIssuedCoupons($customers);
        $this->seedRecipientsAndClicks($customers);
        $this->seedPushHistory($customers);
        $this->seedAnswers($customers);
    }

    /** Si la configuración se sembró en una ejecución anterior (sin socios), se recuperan sus códigos */
    private function loadConfiguration(): void
    {
        $titles = ['signup' => '新規会員登録特典 ドリンク1杯無料', 'birthday' => 'お誕生日おめでとうクーポン', 'return' => '再来店クーポン 500円OFF',
            'sale' => '冬の感謝祭 全品10%OFF', 'survey' => 'アンケート回答特典'];
        foreach ($titles as $key => $title) {
            $this->coupons[$key] ??= (int) DB::table('tb_mycoupon_master')->where('title', $title)->orderByDesc('cd')->value('cd');
        }
        $this->surveys['anketo'] ??= (int) DB::table('tb_c3_anketo_cate')->where('name', 'ご来店満足度アンケート')->orderByDesc('cd')->value('cd');
        $this->surveys['anketo2'] ??= (int) DB::table('tb_c3_anketo2_cate')->where('name', 'お客様アンケート（店内用）')->orderByDesc('cd')->value('cd');
    }

    private function seedIssuedCoupons(Collection $customers): void
    {
        $limitDays = ['signup' => 30, 'birthday' => 30, 'return' => 30, 'sale' => 60];
        foreach ($customers->values() as $i => $customer) {
            foreach (['signup', 'birthday', 'return', 'sale'] as $j => $key) {
                if (($i + $j) % 3 === 2) {
                    continue;
                }
                $issued = $this->now->copy()->subDays(mt_rand(5, 150))->setTime(mt_rand(10, 20), mt_rand(0, 59));
                $row = [
                    'cd' => $this->nextCd('tb_mycoupon'), 'tb_mycoupon_master_cd' => $this->coupons[$key], 'tb_shop_cd' => -1,
                    'tb_customer_cd' => $customer->cd, 'time_limit' => (string) $this->epoch($issued->copy()->addDays($limitDays[$key])->endOfDay()),
                    'used_flg' => null, 'touroku_date' => (string) $this->epoch($issued), 'used_date' => null,
                    'coupon_kubun' => $key === 'signup' ? '4' : '', 'print_flg' => null, 'kakunin_flg' => null,
                ];
                // Uno de cada tres, usado (CouponService::markUsed: pasa a tb_mycoupon_used con tienda y fecha)
                if (($i + $j) % 3 === 0) {
                    $row['used_date'] = (string) $this->epoch($issued->copy()->addDays(mt_rand(1, 20)));
                    $row['tb_shop_cd'] = $this->shopCd;
                    DB::table('tb_mycoupon_used')->insert($row);
                } else {
                    DB::table('tb_mycoupon')->insert($row);
                }
            }
        }
    }

    private function seedRecipientsAndClicks(Collection $customers): void
    {
        $withEmail = $customers->filter(fn ($c) => (string) $c->email1 !== '')->values();
        $types = ['' => '', 'deco' => 'deco', 'coupon' => 'coupon', 'anketo' => 'anketo'];
        foreach ($this->demoMails()->where('配信状況', '2')->orderBy('cd')->get(['cd', 'type_flg', '配信日時']) as $mail) {
            $sentAt = Carbon::parse($mail->{'配信日時'}, LegacyTime::ZONE);
            $recipients = $withEmail->filter(fn ($c, $i) => ($i + $mail->cd) % 5 !== 0)->values();
            foreach ($recipients as $c) {
                DB::table('mailmag_sended_log')->insert(['mailmag_master_cd' => $mail->cd, 'tb_customer_cd' => $c->cd, 'email' => $c->email1]);
            }
            $byCarrier = $recipients->countBy(fn ($c) => match ((string) $c->mailmaga_email_type) {
                'docomo', 'DoCoMo' => 'docomo', 'AU', 'au' => 'au', 'SoftBank', 'softbank' => 'vodafone', 'パソコン', '' => 'pc', default => 'その他',
            });
            DB::table('mailmag_master')->where('cd', $mail->cd)->update([
                'pc配信数' => $byCarrier['pc'] ?? 0, 'docomo配信数' => $byCarrier['docomo'] ?? 0, 'au配信数' => $byCarrier['au'] ?? 0,
                'vodafone配信数' => $byCarrier['vodafone'] ?? 0, 'その他配信数' => $byCarrier['その他'] ?? 0, '配信総数' => $recipients->count(),
            ]);

            // クリックカウンター: más o menos un tercio abre el enlace en los días siguientes
            foreach ($recipients->filter(fn ($c, $i) => $i % 3 === 0) as $c) {
                $clicked = $sentAt->copy()->addMinutes(mt_rand(5, 3 * 24 * 60));
                if ($clicked->greaterThan($this->now)) {
                    continue;
                }
                DB::table('tb_mailmag_analyze')->insert([
                    'cd' => $this->nextCd('tb_mailmag_analyze'), 'mailmag_master_cd' => $mail->cd, 'tb_customer_cd' => $c->cd,
                    'touroku_date' => $this->epoch($clicked), 'customer_age' => $this->age($c->kojin_birth, $clicked),
                    'customer_sex' => (string) $c->kojin_sex, 'customer_job' => (string) $c->job,
                    'mailmag_type' => $types[(string) $mail->type_flg] ?? '',
                ]);
            }
        }
    }

    private function seedPushHistory(Collection $customers): void
    {
        $pushes = DB::table('push_master')->whereIn('title', ['ランチタイムポイント2倍キャンペーン', '夏季休業のお知らせ', '秋の味覚フェア開催中', '忘年会のご予約受付中'])
            ->where('wherestr', "WHERE disp_flg = '1' AND touroku_kubun = '2'")->orderBy('cd')->get(['cd', 'touroku_date']);
        // push_notifications no tiene hatuban (el legacy ponía cd = 1): se sigue el máximo
        $cd = (int) DB::table('push_notifications')->max('cd');
        foreach ($pushes as $push) {
            foreach ($customers->values() as $i => $c) {
                if (($i + $push->cd) % 4 === 0) {
                    continue;
                }
                $opened = $i % 2 === 0 ? (int) $push->touroku_date + mt_rand(60, 6 * 3600) : null;
                DB::table('push_notifications')->insert([
                    'cd' => ++$cd, 'type' => 'push', 'type_master_cd' => $push->cd, 'tb_customer_cd' => $c->cd,
                    'registration_id' => 'demo-'.$c->cd, 'touroku_date' => (int) $push->touroku_date,
                    'open_cnt' => $opened ? mt_rand(1, 3) : null, 'open_date' => $opened, 'extra_data' => null,
                ]);
            }
        }
    }

    private function seedAnswers(Collection $customers): void
    {
        $answers = [
            ['大変満足', 'ホームページ<>SNS<>', 'とても美味しかったです。また来ます。'],
            ['満足', 'お知り合いの紹介<>', '接客が丁寧で気持ちよく過ごせました。'],
            ['普通', '通りがかり<>', ''],
            ['大変満足', 'SNS<>', '季節のメニューが楽しみです。'],
            ['不満', 'ホームページ<>', '少し待ち時間が長かったです。'],
        ];
        foreach (['anketo' => 'tb_c3_anketo', 'anketo2' => 'tb_c3_anketo2'] as $family => $prefix) {
            $cate = $this->surveys[$family] ?? 0;
            $forms = DB::table("{$prefix}_form")->where('tb_c3_anketo_cate_cd', $cate)->orderBy('rank')->pluck('cd')->all();
            if (count($forms) < 3) {
                continue;
            }
            // Solo los grupos numéricos (y que quepan en bigint): datos reales pueden traer otros valores
            $group = (int) DB::table("{$prefix}_sub")->max(DB::raw("CASE WHEN category_inner_group ~ '^[0-9]{1,18}$' THEN CAST(category_inner_group AS bigint) END"));
            foreach ($customers->take(12)->values() as $i => $c) {
                $answer = $answers[($i + ($family === 'anketo2' ? 2 : 0)) % count($answers)];
                $at = (string) $this->epoch($this->now->copy()->subDays(mt_rand(1, 40))->setTime(mt_rand(10, 21), mt_rand(0, 59)));
                $group++;
                foreach ($forms as $k => $formCd) {
                    DB::table("{$prefix}_sub")->insert([
                        'cd' => $this->nextCd("{$prefix}_sub"), 'tb_customer_cd' => $c->cd, 'tb_c3_anketo_cate_cd' => $cate,
                        'tb_c3_anketo_form_cd' => $formCd, 'text_data' => $answer[$k], 'category_inner_group' => (string) $group,
                        'disp_flg' => '1', 'form_type' => '1', 'touroku_date' => $at,
                    ]);
                }
            }
        }
    }

    // ---------------------------------------------------------------------------------------------
    // Casos límite (tercera pasada, CONVENCIONES §4): varias tiendas, estados distintos, fechas de fin de mes,
    // de año y del 29/02, japonés largo hasta el máximo del campo (100), vacíos, importes 0 y grandes, y
    // bastantes filas para que las listas paginen (20 por página).
    // ---------------------------------------------------------------------------------------------

    private function seedEdgeConfiguration(): void
    {
        $this->seedEdgeCoupons();
        $this->seedEdgeMails();
        $this->seedEdgePushes();
        $this->seedEdgeSurveys();
        $this->seedEdgeRanks();
        $this->seedEdgeNews();
    }

    /** 23 cupones メルマガ配信用 (la lista por defecto): caducado, fin de año, 29/02, fin de mes, largo, oculto, de baja… */
    private function seedEdgeCoupons(): void
    {
        $at = $this->epoch($this->now->copy()->subMonths(4));
        $longTitle = $this->fit('【全店共通・会員様限定】日頃のご愛顧に感謝を込めて、年末年始にご利用いただける特別コースとお飲み物のサービスを含む大感謝祭クーポンをお届けします。', 100);
        $specs = [
            // [clave, título, comentario, limit_flg, time_limit, disp_flg, hyouji_flg]
            ['expired', '【期限切れ】夏の早割クーポン', '9月末で終了しました', '5', $this->epoch($this->now->copy()->subMonthsNoOverflow(2)->endOfMonth()), '1', '1'],
            ['yearEnd', '【年末まで】年忘れ感謝クーポン', '12月31日 23:59まで有効', '5', $this->epoch($this->nextYearEnd()), '1', '1'],
            ['leap', '【閏日限定】2月29日まで使える特別クーポン', '2028年2月29日まで有効', '5', $this->epoch(Carbon::create(2028, 2, 29, 23, 59, 59, LegacyTime::ZONE)), '1', '1'],
            ['monthEnd', '【月末限定】月末感謝デー 10%OFF', '', '5', $this->epoch($this->now->copy()->endOfMonth()), '1', '1'],
            ['long', $longTitle, str_repeat('ご利用条件をよくお読みのうえ、ご来店時にスタッフへこの画面をご提示ください。', 6), '2', null, '1', '1'],
            ['hidden', '【非表示】スタッフ確認用クーポン', '', '1', null, '1', ''],
            ['deleted', '【削除済み】旧ランチクーポン', '終了したクーポンです', '3', null, '9', '1'],
            ['deleted2', '【削除済み】旧ディナークーポン', '', '4', '30', '9', '1'],
        ];
        $limits = ['1', '2', '3', '4'];
        for ($n = 1; $n <= 15; $n++) {
            $specs[] = ["filler{$n}", sprintf('%d月のおすすめクーポン No.%02d', ($n % 12) + 1, $n), "期間中のご来店で使えるクーポンです。\n他のクーポンとの併用はできません。",
                $limits[$n % 4], $limits[$n % 4] === '4' ? '14' : null, '1', '1'];
        }
        $points = ['point0' => ['ポイント交換 お試し（0ポイント）', '10', 0], 'pointMax' => ['ポイント交換 特別ディナー招待券', '10', 999999]];
        foreach ($specs as $i => [$key, $title, $comment, $limit, $timeLimit, $disp, $hyouji]) {
            $this->edgeCoupons[$key] = $this->insertEdgeCoupon($i, $title, $comment, '', $limit, $timeLimit, $disp, $hyouji, null, $at);
        }
        foreach (array_values($points) as $i => [$title, $kubun, $point]) {
            $this->edgeCoupons[array_keys($points)[$i]] = $this->insertEdgeCoupon($i, $title, '', $kubun, '1', null, '1', '1', $point, $at);
        }
    }

    private function insertEdgeCoupon(int $i, string $title, string $comment, string $kubun, string $limit, int|string|null $timeLimit, string $disp, string $hyouji, ?int $points, int $at): int
    {
        $cd = $this->nextCd('tb_mycoupon_master');
        DB::table('tb_mycoupon_master')->insert([
            'cd' => $cd, 'tb_shop_cd' => $this->shopFor($i), 'title' => $title, 'comment' => $comment,
            'attention' => $i % 3 === 0 ? '' : '有効期限内にご利用ください<>1回のご来店につき1枚まで',
            'type_flg' => '', 'coupon_kubun' => $kubun, 'limit_flg' => $limit, 'time_limit' => $timeLimit === null ? null : (string) $timeLimit,
            'touroku_date' => (string) ($at + $i * 3600), 'update_date' => (string) ($at + $i * 3600), 'disp_flg' => $disp, 'hyouji_flg' => $hyouji,
            'other_use_flg' => '', 'shouhi_point' => $points, 'saitouroku_flg' => '',
        ]);

        return (int) $cd;
    }

    /**
     * Envíos: 16 de texto en fin de mes (12 meses), 1/1 0:00, 29/02/2024, uno largo (asunto de 100 y cuerpo largo),
     * uno sin destinatarios, uno con errores de entrega y 2 de baja; 16 デコメール; 3 de cupón; 3 programados
     * (fin de mes, 31/12 y 29/02/2028). Repartidos entre las tiendas (登録者) y 本部 (-1).
     */
    private function seedEdgeMails(): void
    {
        $where = "WHERE disp_flg = '1' AND touroku_kubun = '2'";
        $sent = [];
        for ($m = 1; $m <= 12; $m++) {
            $at = $this->now->copy()->subMonthsNoOverflow($m)->endOfMonth()->setTime(23, 59, 0);
            $sent[] = ['', sprintf('【%d月末】月末感謝デーのお知らせ（%d/%d）', $at->month, $at->month, $at->day), $at, []];
        }
        $sent[] = ['', '新年あけましておめでとうございます', Carbon::create($this->now->year, 1, 1, 0, 0, 0, LegacyTime::ZONE), []];
        $sent[] = ['', '【閏日】2月29日限定イベントのお知らせ', Carbon::create(2024, 2, 29, 10, 0, 0, LegacyTime::ZONE), []];
        $sent[] = ['', $this->fit('【重要・全店舗のお客様へ】年末年始の営業時間変更とご予約受付期間、ならびに会員様限定の特別コースとお飲み物サービスについての詳しいご案内（必ず最後までお読みください）', 100),
            $this->now->copy()->subDays(6)->setTime(9, 0), ['body' => $this->longBody()]];
        $sent[] = ['', '【対象者なし】条件に該当するお客様がいなかった配信', $this->now->copy()->subDays(12)->setTime(15, 0), ['key' => 'empty']];
        $sent[] = ['', '【不達あり】メールアドレス確認のお願い', $this->now->copy()->subDays(20)->setTime(18, 30), ['key' => 'errors']];
        $sent[] = ['', '【削除済み】誤って配信したお知らせ', $this->now->copy()->subDays(25)->setTime(11, 0), ['disp' => '9']];
        $sent[] = ['', '【削除済み】テスト配信', $this->now->copy()->subDays(45)->setTime(11, 0), ['disp' => '9']];
        for ($k = 0; $k < 16; $k++) {
            $sent[] = ['deco', sprintf('デコメール通信 Vol.%02d', 16 - $k), $this->now->copy()->subDays(2 + $k * 7)->setTime(12, 0), []];
        }
        foreach (['expired', 'yearEnd', 'leap'] as $k => $coupon) {
            $sent[] = ['coupon', '【クーポン】'.DB::table('tb_mycoupon_master')->where('cd', $this->edgeCoupons[$coupon])->value('title'),
                $this->now->copy()->subDays(30 + $k * 20)->setTime(10, 0), ['coupon' => $coupon]];
        }

        foreach ($sent as $i => [$type, $title, $at, $opt]) {
            $this->insertMail($type, $title, $opt['body'] ?? $this->mailBody($type, $title), $at, '2', $where, [
                '備考' => self::EDGE_MARK, '登録者' => (string) $this->shopFor($i), '店id' => (string) $this->shopFor($i),
                'disp_flg' => $opt['disp'] ?? '', 'type_data_cd' => isset($opt['coupon']) ? $this->edgeCoupons[$opt['coupon']] : null,
                '振分条件' => ($opt['key'] ?? '') === 'empty' ? 'ご来店回数 100回以上' : '',
            ]);
        }

        $endOfMonth = $this->now->copy()->endOfMonth()->setTime(23, 59, 0);
        foreach ([
            ['', '【予約】月末のご挨拶', $endOfMonth->greaterThan($this->now) ? $endOfMonth : $endOfMonth->addMonthNoOverflow()->endOfMonth()->setTime(23, 59, 0)],
            ['deco', '【予約】大晦日のご挨拶', $this->nextYearEnd()->setTime(23, 59, 0)],
            ['', '【予約】閏日のお知らせ（2028/2/29）', Carbon::create(2028, 2, 29, 10, 0, 0, LegacyTime::ZONE)],
        ] as $i => [$type, $title, $at]) {
            $this->insertMail($type, $title, $this->mailBody($type, $title), $at, '1', $where, [
                '備考' => self::EDGE_MARK, '登録者' => (string) $this->shopFor($i + 1), '店id' => (string) $this->shopFor($i + 1),
            ]);
        }
    }

    /** 23 push: repartidos por tienda, fin de mes, largo, ポイント 0 y grande, sin destinatarios y de baja */
    private function seedEdgePushes(): void
    {
        $specs = [];
        for ($m = 1; $m <= 12; $m++) {
            $at = $this->now->copy()->subMonthsNoOverflow($m)->endOfMonth()->setTime(20, 0);
            $specs[] = [sprintf('%d月末のおすすめ（%d/%d）', $at->month, $at->month, $at->day), '今月のおすすめメニューをご紹介します。', $at, null, '1'];
        }
        for ($k = 0; $k < 6; $k++) {
            $specs[] = [sprintf('週末限定のお知らせ No.%02d', $k + 1), '週末のご来店でドリンク1杯サービス。', $this->now->copy()->subDays(3 + $k * 9)->setTime(17, 0), null, '1'];
        }
        $specs[] = [$this->fit('【全店舗共通・会員様限定】年末年始の営業時間と特別コースのご予約受付、ポイント2倍キャンペーンについてのお知らせです。詳しくはアプリのお知らせ欄をご確認ください。', 100),
            str_repeat('年末年始も皆様のご来店を心よりお待ちしております。', 12), $this->now->copy()->subDays(8)->setTime(9, 0), null, '1'];
        $specs[] = ['【ポイント0】参加特典', 'イベント参加の記録です。', $this->now->copy()->subDays(15)->setTime(12, 0), 0, '1'];
        $specs[] = ['【ポイント大量付与】周年記念', '周年記念として特別ポイントを付与しました。', $this->now->copy()->subDays(22)->setTime(12, 0), 99999, '1'];
        $specs[] = ['【対象者なし】条件に該当なし', '該当するお客様がいませんでした。', $this->now->copy()->subDays(26)->setTime(12, 0), null, '1'];
        $specs[] = ['【削除済み】誤配信', '削除済みの配信です。', $this->now->copy()->subDays(33)->setTime(12, 0), null, '9'];

        foreach ($specs as $i => [$title, $message, $at, $point, $disp]) {
            $ts = $this->epoch($at);
            DB::table('push_master')->insert([
                'cd' => $this->nextCd('push_master'), 'tb_shop_cd' => $this->shopFor($i), 'title' => $title, 'message' => serialize([$message]),
                'img_flg' => '', 'wherestr' => "WHERE disp_flg = '1' AND touroku_kubun = '2' AND email1 LIKE 'demo%@example.com'",
                'search_display' => str_starts_with($title, '【対象者なし】') ? 'ご来店回数 100回以上' : '全員', 'disp_flg' => $disp,
                'touroku_date' => $ts, 'update_date' => $ts, 'pos_search' => '', 'pos_tb_shop_cd' => 0, 'version' => 2,
                'post_type' => $point === null ? '1' : '2', 'post_point' => $point, 'point_expiration_date' => $point === null ? null : $ts + 90 * 86400,
                'extra_data' => null, 'type_flg' => null, 'pic_link' => serialize([]),
            ]);
        }
    }

    /** Encuestas sin respuestas: borradores de アンケート (largo, terminada, sin preguntas) y お客様アンケート (activa, futura, de baja) */
    private function seedEdgeSurveys(): void
    {
        $lastYearEnd = Carbon::create($this->now->year - 1, 12, 31, 23, 59, 59, LegacyTime::ZONE);
        $specs = [
            ['tb_c3_anketo', $this->fit('【回答なし】ご来店いただいたお客様へのサービス品質向上のための詳しいアンケート（全店舗共通・所要時間約五分）', 100), null, '1', $this->now->copy()->subDays(10), $this->nextYearEnd(), 3],
            ['tb_c3_anketo', '【終了】昨年末のアンケート（回答なし）', null, '1', $lastYearEnd->copy()->subMonth(), $lastYearEnd, 3],
            ['tb_c3_anketo', '【設問なし】作成中のアンケート', null, '1', $this->now->copy(), $this->now->copy()->endOfMonth(), 0],
            ['tb_c3_anketo2', '【回答なし】店内アンケート（新メニュー）', null, '1', $this->now->copy()->subDays(5), $this->now->copy()->endOfMonth(), 3],
            ['tb_c3_anketo2', '【開始前】閏日アンケート', null, '1', Carbon::create(2028, 2, 1, 0, 0, 0, LegacyTime::ZONE), Carbon::create(2028, 2, 29, 23, 59, 59, LegacyTime::ZONE), 3],
            ['tb_c3_anketo2', '【削除済み】旧アンケート', null, '9', $this->now->copy()->subMonths(8), $this->now->copy()->subMonths(6), 3],
        ];
        $forms = [
            ['本日のお料理はいかがでしたか？', 'ラジオボタン', '大変良い<>良い<>普通<>悪い', '1', 'checkBtn'],
            ['次回お試しになりたいメニュー', 'チェックボックス', '季節の御膳<>デザート<>ドリンク', '', ''],
            ['その他ご意見', 'テキストエリア', '', '', 'writeArea'],
        ];
        foreach ($specs as $i => [$prefix, $name, $hyouji, $disp, $start, $end, $formCount]) {
            $cate = $this->nextCd("{$prefix}_cate");
            DB::table("{$prefix}_cate")->insert([
                'cd' => $cate, 'rank' => 10 + $i, 'name' => $name, 'comment' => $i % 2 === 0 ? '' : '回答のないアンケートです', 'disp_flg' => $disp,
                'hyouji_flg' => $hyouji, 'start_date' => $this->epoch($start->copy()->startOfDay()), 'end_date' => $this->epoch($end),
                'touroku_date' => (string) $this->epoch($start), 'hakkou_date' => null, 'attached_cd' => null,
            ]);
            foreach (array_slice($forms, 0, $formCount) as $k => [$label, $type, $choices, $required, $class]) {
                DB::table("{$prefix}_form")->insert([
                    'cd' => $this->nextCd("{$prefix}_form"), 'tb_c3_anketo_cate_cd' => $cate, 'rank' => $k + 1, 'name' => $label,
                    'form_type' => $type, 'flds_row' => $choices === '' ? null : 2, 'titles' => $choices, 'data' => $choices,
                    'hissu' => $required, 'hyouji_flg' => '1', 'disp_flg' => '1', 'style_class' => $class,
                ]);
            }
        }
    }

    /**
     * Rangos: los 4 niveles de cada tipo los crea ClientesDemoSeeder (S2) y los asigna a sus socios por compras y
     * visitas; aquí, los casos que faltan: uno oculto en マイページ (hyouji_flg 9) con nombre largo y sin tope
     * (jyouken2 vacío) y uno de baja (disp_flg 9), asignados a algunos socios demo en seedEdgePerCustomer.
     */
    private function seedEdgeRanks(): void
    {
        $at = (string) $this->epoch($this->now->copy()->subMonths(5));
        foreach ([
            ['tb_kingaku_rank_master', $this->fit('ブラック（招待制・年間ご利用金額百万円以上のお客様限定の最上位ランク）', 100), 1000000, null, '1', '9', 10],
            ['tb_raiten_rank_master', '【廃止】旧ゴールド会員', 20, 50, '9', '1', 10],
        ] as [$table, $name, $from, $to, $disp, $hyouji, $rank]) {
            if (DB::table($table)->where('name', $name)->exists()) {
                continue;
            }
            DB::table($table)->insert([
                'cd' => $this->nextCd($table), 'name' => $name, 'jyouken1' => $from, 'jyouken2' => $to, 'disp_flg' => $disp,
                'hyouji_flg' => $hyouji, 'touroku_date' => $at, 'update_date' => $at, 'rank' => $rank,
            ]);
        }
    }

    /** 新着情報: título largo y uno de baja (la lista muestra los 10 últimos) */
    private function seedEdgeNews(): void
    {
        foreach ([
            [$this->fit('【重要】年末年始の営業時間と全店舗共通の特別コースのご予約受付についてのお知らせ（詳しくは店頭スタッフまで）', 100), '1', 1],
            ['【削除済み】旧キャンペーンのお知らせ', '9', 2],
        ] as [$title, $disp, $days]) {
            DB::table('tb_digital_signage')->insert([
                'cd' => $this->nextCd('tb_digital_signage'), 'title' => $title, 'play_time' => 0, 'rank' => 90 + $days,
                'update' => (string) $this->epoch($this->now->copy()->subDays($days)), 'disp_flg' => $disp,
            ]);
        }
    }

    /** Por socio demo: cupones (caducado sin usar, vigente, usado, de un cupón de baja), destinatarios, errores de entrega, push y rangos */
    private function seedEdgePerCustomer(Collection $customers): void
    {
        $this->loadEdgeConfiguration();
        $customers = $customers->values();

        // Cupones entregados
        foreach ($customers->take(40) as $i => $c) {
            [$key, $used] = [['expired', false], ['yearEnd', false], ['monthEnd', true], ['deleted', false]][$i % 4];
            $master = DB::table('tb_mycoupon_master')->where('cd', $this->edgeCoupons[$key])->first(['time_limit']);
            $issued = $this->now->copy()->subDays(10 + $i)->setTime(12, 0);
            $row = [
                'cd' => $this->nextCd('tb_mycoupon'), 'tb_mycoupon_master_cd' => $this->edgeCoupons[$key], 'tb_shop_cd' => -1, 'tb_customer_cd' => $c->cd,
                'time_limit' => $master->time_limit ?? (string) $this->epoch($issued->copy()->addDays(30)->endOfDay()), 'used_flg' => null,
                'touroku_date' => (string) $this->epoch($issued), 'used_date' => null, 'coupon_kubun' => '', 'print_flg' => null, 'kakunin_flg' => null,
            ];
            if ($used) {
                $row['used_date'] = (string) $this->epoch($issued->copy()->addDays(2));
                $row['tb_shop_cd'] = $this->shopFor($i);
                DB::table('tb_mycoupon_used')->insert($row);
            } else {
                DB::table('tb_mycoupon')->insert($row);
            }
        }

        // Errores de entrega: socios con 不達配信数 (3 o más → メルマガ区分 不達配信 '9')
        $errorCustomers = $customers->slice(10, 6)->values();
        foreach ($errorCustomers as $k => $c) {
            DB::table('tb_customer')->where('cd', $c->cd)->update(['error_send_cnt' => $k + 1] + ($k + 1 >= 3 ? ['mailmaga_flg' => '9'] : []));
        }

        // Destinatarios y clics de los envíos (el de 対象者なし sin ninguno; el de 不達あり con los socios con error)
        $withEmail = $customers->filter(fn ($c) => (string) $c->email1 !== '')->values();
        foreach ($this->edgeMails()->where('配信状況', '2')->orderBy('cd')->get(['cd', 'タイトル', 'type_flg', '配信日時']) as $j => $mail) {
            $title = (string) $mail->{'タイトル'};
            $recipients = match (true) {
                str_starts_with($title, '【対象者なし】') => collect(),
                str_starts_with($title, '【不達あり】') => $errorCustomers->merge($withEmail->take(20))->unique('cd')->values(),
                default => $withEmail->slice(($j * 7) % max(1, $withEmail->count() - 30), 30)->values(),
            };
            foreach ($recipients as $c) {
                DB::table('mailmag_sended_log')->insert(['mailmag_master_cd' => $mail->cd, 'tb_customer_cd' => $c->cd, 'email' => $c->email1]);
            }
            DB::table('mailmag_master')->where('cd', $mail->cd)->update(['pc配信数' => $recipients->count(), '配信総数' => $recipients->count()]);
            $sentAt = Carbon::parse($mail->{'配信日時'}, LegacyTime::ZONE);
            foreach ($recipients->filter(fn ($c, $i) => $i % 4 === 0) as $c) {
                $clicked = $sentAt->copy()->addHours(2 + $c->cd % 40);
                if ($clicked->greaterThan($this->now)) {
                    continue;
                }
                DB::table('tb_mailmag_analyze')->insert([
                    'cd' => $this->nextCd('tb_mailmag_analyze'), 'mailmag_master_cd' => $mail->cd, 'tb_customer_cd' => $c->cd,
                    'touroku_date' => $this->epoch($clicked), 'customer_age' => $this->age($c->kojin_birth, $clicked),
                    'customer_sex' => (string) $c->kojin_sex, 'customer_job' => (string) $c->job, 'mailmag_type' => (string) $mail->type_flg,
                ]);
            }
        }

        // Push (los de 対象者なし y de baja, sin entregas)
        $cd = (int) DB::table('push_notifications')->max('cd');
        $pushes = DB::table('push_master')->where('wherestr', 'like', "%email1 LIKE 'demo%@example.com'%")->where('disp_flg', '1')
            ->where('title', 'not like', '【対象者なし】%')->orderBy('cd')->get(['cd', 'touroku_date']);
        foreach ($pushes as $j => $push) {
            foreach ($customers->slice(($j * 5) % max(1, $customers->count() - 25), 25)->values() as $i => $c) {
                $opened = $i % 3 === 0 ? (int) $push->touroku_date + 600 + $i * 60 : null;
                DB::table('push_notifications')->insert([
                    'cd' => ++$cd, 'type' => 'push', 'type_master_cd' => $push->cd, 'tb_customer_cd' => $c->cd,
                    'registration_id' => 'demo-'.$c->cd, 'touroku_date' => (int) $push->touroku_date,
                    'open_cnt' => $opened ? 1 + $i % 3 : null, 'open_date' => $opened, 'extra_data' => null,
                ]);
            }
        }

        // Rangos: algunos socios demo en el rango oculto (今回) y en el de baja (前回)
        $black = DB::table('tb_kingaku_rank_master')->where('hyouji_flg', '9')->where('name', 'like', 'ブラック%')->value('cd');
        $retired = DB::table('tb_raiten_rank_master')->where('disp_flg', '9')->where('name', '【廃止】旧ゴールド会員')->value('cd');
        if ($black) {
            DB::table('tb_customer')->whereIn('cd', $customers->slice(0, 3)->pluck('cd'))->update(['kingaku_rank_now' => $black]);
        }
        if ($retired) {
            DB::table('tb_customer')->whereIn('cd', $customers->slice(3, 4)->pluck('cd'))->update(['raiten_rank_pre' => $retired]);
        }
    }

    private function loadEdgeConfiguration(): void
    {
        foreach (['expired' => '【期限切れ】夏の早割クーポン', 'yearEnd' => '【年末まで】年忘れ感謝クーポン', 'monthEnd' => '【月末限定】月末感謝デー 10%OFF',
            'deleted' => '【削除済み】旧ランチクーポン'] as $key => $title) {
            $this->edgeCoupons[$key] ??= (int) DB::table('tb_mycoupon_master')->where('title', $title)->orderByDesc('cd')->value('cd');
        }
    }

    private function edgeMails()
    {
        return DB::table('mailmag_master')->where('備考', self::EDGE_MARK);
    }

    /** Socios demo de ClientesDemoSeeder (demo1…demo300@example.com), activos */
    private function demoCustomers(): Collection
    {
        return DB::table('tb_customer')->where('disp_flg', '1')->where('email1', 'like', 'demo%@example.com')->orderBy('cd')->limit(80)
            ->get(['cd', 'email1', 'kojin_birth', 'kojin_sex', 'job', 'mailmaga_email_type']);
    }

    /** Reparte entre las tiendas activas y 本部 (-1) */
    private function shopFor(int $i): int
    {
        $all = [...$this->shops, -1];

        return $all[$i % count($all)];
    }

    /** El 31/12 a las 23:59:59 más próximo (este año o el siguiente) */
    private function nextYearEnd(): Carbon
    {
        $end = $this->now->copy()->endOfYear();

        return $end->greaterThan($this->now) ? $end : $end->addYear();
    }

    /** Recorta a $max caracteres (maxlength del formulario) */
    private function fit(string $text, int $max): string
    {
        return mb_substr(str_repeat($text, (int) ceil($max / max(1, mb_strlen($text)))), 0, $max);
    }

    private function longBody(): string
    {
        $sections = ['営業時間のご案内', 'ご予約の受付について', '特別コースの内容', 'お飲み物サービス', 'ポイント2倍キャンペーン', '駐車場のご案内', 'お支払い方法', '感染症対策の取り組み'];
        $body = "%name%様\n\nいつも当店をご利用いただき、誠にありがとうございます。\n";
        foreach ($sections as $n => $title) {
            $body .= sprintf("\n■%d．%s\n", $n + 1, $title)
                .str_repeat("年末年始の期間中は大変混み合うことが予想されますので、お早めのご予約をおすすめいたします。ご不明な点がございましたら、各店舗のスタッフまでお気軽にお問い合わせください。\n", 3);
        }

        return $body."\n今後とも変わらぬご愛顧のほど、よろしくお願い申し上げます。\n\n※本メールは送信専用です。";
    }

    // ---------------------------------------------------------------------------------------------
    // Utilidades
    // ---------------------------------------------------------------------------------------------

    private function demoMails()
    {
        return DB::table('mailmag_master')->where('備考', self::DEMO_MARK);
    }

    /** Socios activos (hasta 60), con los datos que usan los clics */
    private function customers(): Collection
    {
        return DB::table('tb_customer')->where('disp_flg', '1')->where('touroku_kubun', '2')->orderBy('cd')->limit(60)
            ->get(['cd', 'email1', 'kojin_birth', 'kojin_sex', 'job', 'mailmaga_email_type']);
    }

    /**
     * Código de hatuban. Antes del primer uso en esta ejecución, el contador se adelanta por encima del cd máximo
     * de la tabla (por si hay filas que no pasaron por hatuban) para no repetir códigos.
     * Las tablas que la pantalla numera con createHatsuban (point_shikkou_alert) y cualquier tabla cuyo cd máximo
     * no quepa en hatuban.kazu (integer: filas con cd en formato legacy) usan Hatuban::legacyCd, como el legacy.
     */
    private function nextCd(string $koumoku, ?string $table = null): int|string
    {
        $table ??= $koumoku;
        if (in_array($table, self::LEGACY_CD_TABLES, true) || ($this->legacyCd[$table] ?? false)) {
            return Hatuban::legacyCd($this->shopCd);
        }
        if (! isset($this->synced[$koumoku])) {
            $max = (string) (DB::table($table)->max('cd') ?? '0');
            if (strlen($max) > 9 && (float) $max > self::HATUBAN_MAX) {
                $this->legacyCd[$table] = true;

                return Hatuban::legacyCd($this->shopCd);
            }
            $max = (int) $max;
            $kazu = (int) DB::table('hatuban')->where('koumoku', $koumoku)->value('kazu');
            if ($kazu <= $max) {
                DB::table('hatuban')->updateOrInsert(['koumoku' => $koumoku], ['kazu' => $max + 1]);
            }
            $this->synced[$koumoku] = true;
        }

        return app(Hatuban::class)->next($koumoku);
    }

    private function epoch(Carbon $at): int
    {
        return $at->getTimestamp();
    }

    private function age(?string $birth, Carbon $at): ?int
    {
        if (! preg_match('#^(\d{4})/(\d{1,2})/(\d{1,2})$#', (string) $birth, $m)) {
            return null;
        }

        return (int) Carbon::create((int) $m[1], (int) $m[2], (int) $m[3], 0, 0, 0, LegacyTime::ZONE)->diffInYears($at, true);
    }
}
