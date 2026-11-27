<?php

namespace App\Services\Reservations;

use App\Mail\Reservas\ReservationMail;
use App\Models\Reservations\SimpleReservation;
use App\Models\Shop;
use App\Services\Promotions\SystemDomain;
use Illuminate\Support\Facades\Mail;

/**
 * Correos de la reserva simple (category3/reservation) con el mismo texto que el legacy:
 * - reserve_new_register.php: 予約登録 / 予約変更 a la tienda, al 指名 (si hay) y al cliente.
 * - reserve_change_status.php: 予約取り消し / 予約確定 a los mismos tres.
 *
 * Diferencias buscadas: el enlace con md5(login_id)/md5(login_pass) a /hp/user/login.php se cambia por el enlace
 * firmado de Mi página de ReservationMailer (se omite mientras S4 no publique la ruta), y el texto se envía sin las
 * entidades HTML con que se guarda en tb_reserve.
 */
class SimpleReservationMailer
{
    public function __construct(
        private readonly SimpleReservationService $service,
        private readonly SystemDomain $domain,
        private readonly ReservationMailer $reservationMailer,
    ) {}

    /**
     * @param  string  $com1  '予約登録' | '予約変更' | '予約取り消し' | '予約確定'
     * @param  string  $senderName  `$_SESSION['shop_name']`: tienda del personal que hace la operación
     * @param  bool  $staffSeesContact  permisos 18/20 del personal (en el correo al 担当 del registro)
     * @param  bool  $withPeople  el registro añade ○人数 si se indicó; el cambio de estado nunca (el legacy miraba `ninsuu`)
     */
    public function send(SimpleReservation $reservation, string $com1, string $senderName, bool $withPeople, ?array $staffSeesContact = null): void
    {
        $related = $this->service->related($reservation);
        $d = fn (string $column) => SimpleReservationService::decode($reservation->{$column});
        $name = $d('user_name');
        $tel = $d('user_tel');
        $mail = $d('user_mail');
        $shopName = (string) $related['shopName'];
        $shopMail = is_numeric($reservation->shop_cd) ? (string) Shop::withDeleted()->whereKey((int) $reservation->shop_cd)->value('email') : '';
        $at = $reservation->reservedAt();
        $when = $at?->format('Y年m月d日 H時i分') ?? '';
        $categoryName = SimpleReservationService::decode($related['categoryName'] ?? '');

        $ninsuCom = $withPeople && $d('reserve_people') !== '' ? "\n○人数：\n" . $d('reserve_people') . '人' : '';
        $hasStaff = (string) $reservation->reserve_nomination !== '';
        $shimeiCom = $hasStaff ? "\n○指名：\n" . ($related['staff']->name ?? '') : '';

        if ((string) $reservation->user_cd !== '') {
            $customer = $related['customer'];
            $link = $customer !== null ? $this->reservationMailer->mypageLink((int) $customer->cd, (string) $customer->login_id) : null;
            $comTouroku = $customer !== null ? "\n→修正はログイン後、予約一覧からどうぞ\n" . ($link !== null ? $link . "\n" : '') : '';
        } else {
            $comTouroku = "\n会員登録がお済みでない方は、登録していただくとマイページで予約の変更・キャンセルなどが可能となります。\n"
                . "ぜひご登録いただくことをオススメいたします。\n"
                . "↓会員登録はこちらから\n"
                . $this->domain->hpAddress() . "mailsyori/base.php?subject=1\n";
        }

        $common = "○店舗選択：\n" . $shopName . "\n"
            . "○予約日：\n" . $when . $ninsuCom . $shimeiCom . "\n"
            . "○予約内容：\n" . $categoryName . "\n";
        $footer = '(C)' . $senderName . "\n";
        $subject = $name . '様より' . $com1 . '申請';

        // お店
        $message = "\n■" . $senderName . "\n\n" . $name . '様、より' . $com1 . "がありました。\n\n"
            . "○名前：\n" . $name . "\n○電話番号：\n" . $tel . "\n○メールアドレス：\n" . $mail . "\n"
            . $common . "\n" . $footer;
        $this->deliver($shopMail, $subject, $message, $senderName, 'shop');

        // 担当 (指名あり): el registro solo da teléfono y correo con permiso 18 / 20; el cambio de estado nunca
        if ($hasStaff) {
            $message = "\n■" . $senderName . "\n\n" . $name . '様、より' . $com1 . "がありました。\n店舗に詳細をご確認ください。\n\n"
                . "○名前：\n" . $name . "\n";
            if ($staffSeesContact !== null) {
                if ($staffSeesContact['tel']) {
                    $message .= "\n○電話番号：\n" . $tel . "\n";
                }
                if ($staffSeesContact['mail']) {
                    $message .= "\n○メールアドレス：\n" . $mail . "\n";
                }
                $message .= "\n";
            }
            $message .= $common . "\n" . $footer;
            $this->deliver((string) ($related['staff']->email1 ?? ''), $subject, $message, $senderName, 'staff');
        }

        // お客様
        $message = "\n■" . $senderName . "\n\n" . $name . "様、いつもご利用ありがとうございます。\n以下の内容で" . $com1 . "を申請いたしました。\n\n"
            . "○名前：\n" . $name . "\n○電話番号：\n" . $tel . "\n○メールアドレス：\n" . $mail . "\n"
            . $common . $comTouroku . "\n\n" . $footer;
        $this->deliver($mail, $com1 . '確認メール', $message, $senderName, 'customer');
    }

    /** mb_convert_kana('KV') del destinatario y del cuerpo; un fallo del transporte no corta la operación */
    private function deliver(string $to, string $subject, string $body, string $fromName, string $recipientType): void
    {
        $to = trim(mb_convert_kana($to, 'KV'));
        if ($to === '') {
            return;
        }

        try {
            Mail::to($to)->send(new ReservationMail(
                $subject,
                mb_convert_kana($body, 'KV'),
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
