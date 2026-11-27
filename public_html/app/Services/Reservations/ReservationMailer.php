<?php

namespace App\Services\Reservations;

use App\Mail\Reservas\ReservationMail;
use App\Models\AdminMaster;
use App\Models\Reservations\Reservation;
use App\Services\Nucleo\LegacyUi;
use App\Services\Promotions\MailRenderer;
use App\Services\Promotions\SystemDomain;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

/**
 * Correos de reservas con el mismo texto que el legacy:
 * - registerMails(): los de yoyaku_register.php (casilla 予約受付の旨をお客様にお知らせする): 担当 y お客様.
 * - actionMails(): cYoyaku::sendYoyakuMail($cd, 'new'|'update'|'delete'): お店, 担当 y お客様.
 *
 * Diferencia buscada con el legacy: el enlace a Mi página con md5(login_id)/md5(login_pass) se cambia por un
 * enlace firmado (URL::temporarySignedRoute) a la ruta de Mi página de S4 si existe; si no existe, se omite.
 */
class ReservationMailer
{
    /** Ruta de Mi página (S4) a la que apunta el enlace firmado; mientras no exista se omite el enlace */
    public const MYPAGE_ROUTE = 'publico.mypage.login';

    /** Validez del enlace firmado */
    public const MYPAGE_LINK_DAYS = 30;

    public function __construct(
        private readonly ReservationFlowService $flow,
        private readonly SystemDomain $domain,
        private readonly LegacyUi $legacyUi,
        private readonly MailRenderer $renderer,
    ) {}

    /**
     * yoyaku_register.php: aviso al 担当 (si tiene email1) y a la お客様.
     *
     * @param  array<string, mixed>  $input  petición ya limpia con form2Db (shop, staff, menu, option_menu, kaiin, name, tel, mail, yoyaku_date)
     */
    public function registerMails(Reservation $reservation, array $input): void
    {
        $shopCd = (int) ($input['shop'] ?? 0);
        $staffCd = (int) ($input['staff'] ?? 0);
        $shopName = (string) DB::table('tb_shop')->where('disp_flg', '!=', '9')->where('cd', $shopCd)->value('kaishamei');

        $staff = ($shopCd && $staffCd)
            ? DB::table('tb_staff')->where('disp_flg', '1')->where('shop_cd', $shopCd)->where('cd', $staffCd)->first()
            : null;
        $staffName = ($shopCd && $staffCd) ? (string) ($staff->name ?? '') : '指名なし';

        // Menús: nombre en 半角カナ unidos por ' / ' (y el total de minutos)
        $menuCds = ReservationFlowService::menuCds($input['menu'] ?? null, $input['option_menu'] ?? null);
        $menus = $menuCds === [] ? collect() : $this->flow->staffMenuQuery($staffCd, $menuCds)
            ->leftJoin('tb_yoyaku_koumoku_category as tb3', 'tb3.cd', '=', 'tb2.tb_yoyaku_koumoku_category_cd')
            ->orderBy('tb2.type_flg')->orderBy('tb3.rank')->orderBy('tb3.cd')->orderByDesc('tb2.tb_shop_cd')->orderBy('tb2.rank')->orderBy('tb2.cd')
            ->get();
        $dispMenu = $menus->map(fn ($m) => mb_convert_kana((string) $m->name, 'k') . ' / ')->implode('');
        $dispMenuTotal = (int) $menus->sum('shoyou_time');

        // Datos del cliente: los del socio o los del formulario
        $customer = null;
        if ((string) ($input['kaiin'] ?? '') !== '') {
            $customer = DB::table('tb_customer')->where('cd', (int) $input['kaiin'])->first(['cd', 'id', 'name', 'tel', 'email1', 'login_id']);
        }
        if ($customer !== null) {
            $name = (string) $customer->name;
            $tel = (string) $customer->tel;
            $mail = (string) $customer->email1;
            $link = $this->mypageLink((int) $customer->cd, (string) $customer->login_id);
            $comTouroku = "\n→確認はログイン後、予約一覧からどうぞ\n" . ($link !== null ? $link . "\n" : '');
        } elseif ((string) ($input['kaiin'] ?? '') !== '') {
            // Socio inexistente: el legacy dejaba los datos vacíos
            $name = $tel = $mail = '';
            $comTouroku = '';
        } else {
            $name = (string) ($input['name'] ?? '');
            $tel = (string) ($input['tel'] ?? '');
            $mail = (string) ($input['mail'] ?? '');
            $hp = $this->domain->hpAddress();
            $comTouroku = "\n会員登録がお済みでない方は、登録していただくとマイページで予約の変更・キャンセルなどが可能となります。\n"
                . "是非ご登録いただくことをオススメいたします。\n"
                . "↓会員登録はこちらから\n"
                . $hp . "mailsyori/base.php?subject=1\n\n"
                . "また、予約の取り消し処理はコチラからも行えます。\n"
                . $hp . 'm/yoyaku/yoyaku_m.php?flow=10&yoyaku_cd=' . $reservation->cd . '&t=' . $reservation->touroku_date;
        }
        $name = urldecode($name);
        $mail = urldecode($mail);

        $ymd = (string) ($input['yoyaku_date'] ?? '');
        $when = substr($ymd, 0, 4) . '年' . substr($ymd, 4, 2) . '月' . substr($ymd, 6, 2) . '日 ' . substr($ymd, 8, 2) . '：' . substr($ymd, 10, 2);
        $shimei = $this->flow->manageWord('指名', 'c9_yoyaku[スタッフ]');
        $site = $this->legacyUi->siteName();
        $footer = $this->createMailFooter();

        $mainCom = "○お名前：\n" . $name . " 様\n"
            . "○電話番号：\n" . $tel . "\n"
            . "○メールアドレス：\n" . $mail . "\n"
            . "○店舗選択：\n" . $shopName . "\n"
            . "○予約日：\n" . $when . "\n"
            . '○' . $shimei . "：\n" . $staffName . "\n"
            . "○予約内容：\n" . $dispMenu . "\n"
            . "○目安時間：\n約" . $dispMenuTotal . "分\n";

        // 担当: sin teléfono ni correo del cliente (2011.03.08)
        if ($staff !== null && (string) $staff->email1 !== '') {
            $mainComStaff = "○お名前：\n" . $name . ' 様'
                . "○店舗選択：\n" . $shopName . "\n"
                . "○予約日：\n" . $when . "\n"
                . '○' . $shimei . '：' . $staffName . "\n"
                . "○予約内容：\n" . $dispMenu . "\n"
                . "○目安時間：\n約" . $dispMenuTotal . '分';
            $message = "\n■" . $site . "\n\n" . $name . "様より予約登録申請がありました。\n店舗に詳細をご確認ください。\n" . $mainComStaff . "\n\n" . $footer;
            $this->deliver(mb_convert_kana((string) $staff->email1, 'KV'), $name . '様より予約登録申請', mb_convert_kana($message, 'KV'), $site, 'staff');
        }

        $message = "\n■" . $site . "\n\n" . $name . "様、いつもご利用ありがとうございます。\n以下の内容で予約登録を承りました。\n\n"
            . "また、ご連絡事項などがある場合は、店舗からご連絡させていただく場合がございます。\n\n"
            . $mainCom . "\n" . $comTouroku . "\n\n" . $footer;
        $this->deliver(mb_convert_kana($mail, 'KV'), '予約登録確認メール', mb_convert_kana($message, 'KV'), $site, 'customer');
    }

    /**
     * cYoyaku::sendYoyakuMail(): aviso a la tienda, al 担当 (si tiene email1) y al cliente (si hay correo).
     *
     * @param  string  $type  'new' | 'update' | 'delete'
     */
    public function actionMails(int $yoyakuCd, string $type = 'new'): void
    {
        $y = DB::table('tb_yoyaku as tb1')
            ->leftJoin('tb_customer as tb2', 'tb1.tb_customer_cd', '=', 'tb2.cd')
            ->where('tb1.cd', $yoyakuCd)
            ->selectRaw("tb1.tb_yoyaku_uketsuke_day_cd, tb1.tb_customer_cd, tb1.start_time, tb1.people, tb1.tel,
                tb1.base_tb_yoyaku_koumoku_master_cd, tb1.option_tb_yoyaku_koumoku_master_cd, tb1.free_koumoku, tb1.touroku_date, tb1.entry_type,
                CASE WHEN tb_customer_cd > 0 THEN tb2.name ELSE tb1.name END AS name,
                CASE WHEN tb_customer_cd > 0 THEN tb2.name_kana ELSE tb1.name_kana END AS name_kana,
                CASE WHEN TRIM(COALESCE(tb1.mail, '')) <> '' THEN tb1.mail ELSE tb2.email1 END AS mail,
                CASE WHEN tb_customer_cd > 0 THEN tb2.login_id ELSE '' END AS login_id")
            ->first();
        if ($y === null) {
            return;
        }
        $u = DB::table('tb_yoyaku_uketsuke_day as tb1')
            ->join('tb_staff as tb2', 'tb1.tb_staff_cd', '=', 'tb2.cd')
            ->join('tb_shop as tb3', 'tb2.shop_cd', '=', 'tb3.cd')
            ->where('tb1.cd', $y->tb_yoyaku_uketsuke_day_cd)
            ->first(['tb1.moushikomi_date', 'tb2.cd as staff_cd', 'tb2.name as staff_name', 'tb2.email1 as staff_mail', 'tb3.cd as shop_cd', 'tb3.kaishamei', 'tb3.email as shop_mail']);
        if ($u === null) {
            return;
        }

        $koumoku = $this->flow->koumoku();
        $meyasu = (string) AdminMaster::current()->meyasu_jikan_flg !== '9';

        // Hora: 'HH時MM分' (las de después de medianoche como 24時, 25時…)
        $start = (int) $y->start_time;
        $hh = sprintf('%02d', intdiv(((($start % 86400) + 86400) % 86400), 3600));
        $ii = sprintf('%02d', intdiv(($start % 3600 + 3600) % 3600, 60));
        if ($start >= 86400) {
            $hh = (int) $hh + 24;
        }
        [$yy, $mm, $dd] = array_pad(explode('/', (string) $u->moushikomi_date), 3, '');

        $dispMenu = $this->actionMenuText($y, (int) $u->staff_cd, $meyasu, $dispMenuTotal);

        // Datos personales (el correo a la tienda y al 担当 no lleva el correo del cliente)
        $free = explode('<>', (string) $y->free_koumoku);
        $name = $personalInfo = "\n○お名前：\n" . $y->name . " 様\n";
        $personalInfoToStaff = $personalInfo;
        if (($koumoku['name_kana']['koumoku_disp'] ?? '') == '1' && (string) $y->name_kana !== '') {
            $personalInfo .= "\n○お名前(カナ)：\n" . $y->name_kana . "\n";
        }
        if (($koumoku['tel']['koumoku_disp'] ?? '') == '1') {
            $personalInfo .= "\n○お電話番号：\n" . $y->tel . "\n";
            $personalInfoToStaff .= "\n○お電話番号：\n" . $y->tel . "\n";
        }
        if (($koumoku['mail']['koumoku_disp'] ?? '') == '1') {
            $personalInfo .= "\n○メールアドレス：\n" . $y->mail . "\n";
        }
        foreach ($koumoku as $key => $val) {
            if (preg_match('/^free_koumoku_([0-9]+)/', $key, $m) && (string) $val['koumoku_disp'] === '1') {
                $line = "\n○" . $val['tb_koumoku_name'] . "：\n" . rawurldecode($free[$m[1] - 1] ?? '') . "\n";
                $personalInfo .= $line;
                $personalInfoToStaff .= $line;
            }
        }

        // フリー予約 (entry_type 5, 6, 101): nombre del empleado según la configuración de la tienda
        $staffName = (string) $u->staff_name;
        if (preg_match('/^(5|6|101)$/', (string) $y->entry_type)) {
            $contents = fn (string $field) => DB::table('contents_master')->where('category', 'tb_c9_yoyaku')->where('field', $field)
                ->where('int_data', $u->shop_cd)->where('disp_flg', '1')->value('text_data');
            if ((string) $contents('free_yoyaku_timeline_staff') !== '1') {
                $staffName = '指名なし';
            }
            if ((string) $contents('free_yoyaku_name') !== '') {
                $staffName = (string) $contents('free_yoyaku_name');
            }
        }

        $commonInfo = "\n○店舗選択：\n" . $u->kaishamei . "\n"
            . "\n○予約日：\n" . $yy . '年' . $mm . '月' . $dd . '日 ' . $hh . '時' . $ii . "分\n"
            . "\n○" . $this->flow->manageWord('担当', 'c9_yoyaku[スタッフ]') . "：\n" . $staffName . "\n"
            . "\n○予約内容：\n" . $dispMenu . "\n"
            . ($meyasu ? "\n○目安時間：\n約" . $dispMenuTotal . "分\n" : '');

        [$subjectType, $bodyType, $bodyKaiin] = match ($type) {
            'update' => ['変更', 'を変更いたしました。', '予約の確認・キャンセルはマイページの「予約」から行なえます。'],
            'delete' => ['取り消し', 'を取り消しいたしました。', '予約の申し込みはログイン後、予約メニューからどうぞ'],
            default => ['登録', '登録を承りました。', '予約の確認・キャンセルはマイページの「予約」から行なえます。'],
        };

        // setKihonData(): remitente = empresa (tb_office); el pie solo va en el correo al cliente
        $renderer = $this->renderer->forSender('');
        $fromName = $renderer->senderName();
        $site = $this->legacyUi->siteName();

        // お店
        $message = "\n■" . $site . "\n\n" . $y->name . '様より予約' . $subjectType . "申請がありました。\n管理画面より内容をご確認ください。\n"
            . $personalInfoToStaff . $commonInfo . "\n→予約状況の確認は管理画面より、ログインしてから行ってください。";
        $this->deliver((string) $u->shop_mail, $y->name . '様より予約' . $subjectType . '申請', $message . "\n\n", $fromName, 'shop');

        // 担当
        if ((string) $u->staff_mail !== '') {
            $message = "\n■" . $site . "\n\n" . $y->name . '様より予約' . $subjectType . "申請がありました。\n店舗に詳細をご確認ください。\n" . $name . $commonInfo . "\n";
            $this->deliver((string) $u->staff_mail, $y->name . '様より予約' . $subjectType . '申請', $message . "\n\n", $fromName, 'staff');
        }

        // お客様
        $message = "\n■" . $site . "\n\n" . $y->name . '様、いつもご利用ありがとうございます。' . "\n以下の内容で予約" . $bodyType . "\n";
        if ($type !== 'delete') {
            $message .= "\nまた、ご連絡事項などがある場合は、店舗からご連絡させていただく場合がございます。\n";
        }
        $message .= $personalInfo . $commonInfo;
        if ($y->tb_customer_cd !== null) {
            $link = $this->mypageLink((int) $y->tb_customer_cd, (string) $y->login_id);
            $message .= "\n→" . $bodyKaiin . ($link !== null ? "\n\n※便利なマイページログインはこちら。\n" . $link . "\n" : '');
        } else {
            $hp = $this->domain->hpAddress();
            $message .= "\n会員登録がお済みでない方は、登録していただくとマイページで予約の変更・キャンセルなどが可能となります。"
                . "\n是非ご登録いただくことをオススメいたします。\n↓会員登録はこちらから\n" . $hp . 'mailsyori/base.php?subject=1';
            if ($type !== 'delete') {
                $message .= "\n\nまた、予約の取り消し処理はコチラからも行えます。\n" . $hp . 'm/yoyaku/yoyaku_m.php?flow=10&yoyaku_cd=' . $yoyakuCd
                    . '&t=' . $y->touroku_date . '&shop_cd=' . $u->shop_cd;
            }
        }
        $this->deliver((string) $y->mail, '予約' . $subjectType . '確認メール', $message . "\n\n" . $renderer->defaultFooter(false), $fromName, 'customer');
    }

    /**
     * Enlace firmado a Mi página (sustituye al login con md5(login_id)/md5(login_pass) del legacy).
     * null si el socio no tiene login o la ruta de S4 aún no existe.
     */
    public function mypageLink(int $customerCd, string $loginId): ?string
    {
        if ($customerCd <= 0 || $loginId === '' || ! Route::has(self::MYPAGE_ROUTE)) {
            return null;
        }

        return URL::temporarySignedRoute(self::MYPAGE_ROUTE, Carbon::now()->addDays(self::MYPAGE_LINK_DAYS), ['customer' => $customerCd]);
    }

    /** Texto 予約内容 de sendYoyakuMail (CRM): ■ベースメニュー / ■オプションメニュー agrupados por categoría */
    private function actionMenuText(object $y, int $staffCd, bool $meyasu, ?int &$total): string
    {
        $menuCds = array_filter(array_map('intval', explode(',', (string) $y->base_tb_yoyaku_koumoku_master_cd . ',' . (string) $y->option_tb_yoyaku_koumoku_master_cd)));
        $total = 0;
        if ($menuCds === []) {
            return '';
        }

        $rows = $this->flow->staffMenuQuery($staffCd, array_values($menuCds))
            ->leftJoin('tb_yoyaku_koumoku_category as tb3', 'tb3.cd', '=', 'tb2.tb_yoyaku_koumoku_category_cd')
            ->addSelect(DB::raw("CASE WHEN tb2.tb_yoyaku_koumoku_category_cd IS NULL THEN 'カテゴリ無し' ELSE tb3.name END AS cate_name"))
            ->orderBy('tb2.type_flg')->orderBy('tb2.tb_yoyaku_koumoku_category_cd')->orderBy('tb2.rank')
            ->get();

        $total = (int) $rows->sum('shoyou_time');
        $text = '';
        foreach ([[true, '　■ベースメニュー'], [false, '　■オプションメニュー']] as [$isBase, $title]) {
            $list = $rows->filter(fn ($r) => ((string) $r->type_flg === '1') === $isBase);
            if ($list->isEmpty()) {
                continue;
            }
            $text .= $title . "\n";
            $prev = '';
            foreach ($list as $menu) {
                if ($prev !== (string) $menu->cate_name) {
                    $text .= '　' . $menu->cate_name . "\n";
                    $prev = (string) $menu->cate_name;
                }
                $text .= '　　' . $menu->name . ($meyasu ? '(' . $menu->shoyou_time . "分)\n" : "\n");
            }
        }

        return $text;
    }

    /** createMailFooter('') de system/inc/kihon.php: pie con los datos de la empresa (tb_office) */
    private function createMailFooter(): string
    {
        $flags = (string) AdminMaster::current()->mail_footer === '' ? array_fill(0, 4, '') : array_pad(explode('<>', (string) AdminMaster::current()->mail_footer), 4, '');
        $office = (array) (DB::table('tb_office')->where('cd', 1)->first() ?? []);
        $email = (string) ($office['email'] ?? '');

        $footer = "\n\n本メールは配信専用です。本メールアドレスに直接ご返信は出来ません。\n"
            . ($email !== '' && $flags[2] === '' ? "お問い合わせにつきましては、下記のメールアドレスにお問い合わせ下さい\n" : '')
            . "\n━━━━━━━━━━━\n"
            . ($office['kaishamei'] ?? '');
        if (($office['add'] ?? '') != '' && $flags[0] === '') {
            $footer .= "\n" . $office['add'] . ($office['add_number'] ?? '') . ($office['add_build'] ?? '');
        }
        if (($office['tel'] ?? '') != '' && $flags[1] === '') {
            $footer .= "\nTEL：" . $office['tel'];
        }
        if ($email !== '' && $flags[2] === '') {
            $footer .= "\nE-Mail：" . $email;
        }
        if ($flags[3] === '') {
            $footer .= "\nURL：" . ($office['shop_url'] ?? '');
        }

        return $footer . "\n━━━━━━━━━━━\n\n";
    }

    /** Envía un correo si hay destinatario; un fallo del transporte no corta el alta (como el mail() del legacy) */
    private function deliver(string $to, string $subject, string $body, string $fromName, string $recipientType): void
    {
        $to = trim($to);
        if ($to === '') {
            return;
        }

        try {
            Mail::to($to)->send(new ReservationMail(
                $subject,
                $body,
                $this->domain->fromAddress(),
                $fromName,
                $recipientType,
                $this->domain->errorAddress(),
            ));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
