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

    private Carbon $now;

    private int $shopCd;

    private string $staffCd;

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
        $this->staffCd = (string) (DB::table('tb_staff')->where('disp_flg', '1')->orderBy('cd')->value('cd') ?? '');

        if (! $this->demoMails()->exists()) {
            DB::transaction(fn () => $this->seedConfiguration());
        }

        $customers = $this->customers();
        $sentCds = $this->demoMails()->where('配信状況', '2')->pluck('cd');
        if ($customers->isEmpty()) {
            $this->command?->warn('PromocionDemoSeeder: sin socios activos; solo configuración.');

            return;
        }
        if (DB::table('mailmag_sended_log')->whereIn('mailmag_master_cd', $sentCds)->exists()) {
            return;
        }
        DB::transaction(fn () => $this->seedPerCustomer($customers));
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
            '登録者' => $this->staffCd, '備考' => self::DEMO_MARK, '受付メールアドレス' => 'なし', '配信状況' => $status,
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
