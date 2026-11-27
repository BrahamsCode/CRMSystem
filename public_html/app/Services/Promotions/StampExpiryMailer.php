<?php

namespace App\Services\Promotions;

use App\Mail\Promotions\PromotionMail;
use App\Models\AdminMaster;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Caducidad de sellos (F5, S2): legacy category3/stamp/sendStampShikkouMail.php (失効: aviso, tb_stamp_log
 * action_flg 7 y puesta a cero) y sendStampShikkouAlertMail.php (aviso previo), que cSendSchedule metía en el
 * crontab a la hora de admin_master solo con スタンプ管理 activo y stamp_shikkou_sousa = '1'.
 * El texto se arma con MailRenderer de S3 (pie de la tienda del cliente, etiquetas y enlace a Mi página).
 */
class StampExpiryMailer
{
    public const SHIKKOU = 'shikkou';

    public const ALERT = 'alert';

    /** @var array<string, MailRenderer> renderer por tienda (setKihonData) */
    private array $renderers = [];

    public function __construct(
        private readonly StampService $stamps,
        private readonly SystemDomain $domain,
    ) {}

    /**
     * cSendSchedule: la tarea existe si スタンプ管理 está activo y la caducidad está en uso ('1');
     * corre a la hora de admin_master (stamp_shikkou_time / stamp_shikkou_alert_time, 12:00 si falta).
     */
    public function isDue(string $kind, CarbonInterface $now): bool
    {
        $admin = AdminMaster::current();
        if ((string) $admin->stamp_shikkou_sousa !== '1' || ! self::moduleEnabled()) {
            return false;
        }
        $time = (string) ($kind === self::ALERT ? $admin->stamp_shikkou_alert_time : $admin->stamp_shikkou_time);
        [$hour, $minute] = array_pad(explode(':', $time), 2, '');
        $hour = $hour !== '' ? (int) $hour : 12;
        $minute = $minute !== '' ? (int) $minute : 0;
        $at = Carbon::instance($now)->setTimezone(LegacyTime::ZONE);

        return $at->hour === $hour && $at->minute === $minute;
    }

    /** tb_base_system_child «スタンプ管理» activo */
    public static function moduleEnabled(): bool
    {
        return DB::table('tb_base_system_child')->where('system_name', 'スタンプ管理')->where('system_flg', '1')->exists();
    }

    /**
     * sendStampShikkouMail.php: a cada cliente con sellos caducados le manda el aviso y deja el movimiento
     * en tb_stamp_log (shouhi_stamp = saldo, action_flg '7'); después pone a NULL saldo y caducidad.
     *
     * @return int|string clientes procesados, o el motivo por el que no se hizo nada (como el die() del legacy)
     */
    public function expire(?CarbonInterface $now = null): int|string
    {
        $now ??= now();
        $admin = AdminMaster::current();
        if ((string) $admin->stamp_shikkou_mail_naiyou === '') {
            return 'メール内容は空です！！！';
        }
        if ((string) $admin->stamp_shikkou_sousa === '2') {
            return '有効期限無効です！！！';
        }
        $ts = $now->getTimestamp();

        $customers = DB::table('tb_customer')
            ->where('disp_flg', '1')->where('touroku_kubun', '2')
            ->whereRaw('COALESCE(stamp_count, 0) > 0')
            ->whereNotNull('stamp_yukoukigen')->where('stamp_yukoukigen', '<', $ts)
            ->orderBy('cd')->get();

        DB::transaction(function () use ($customers, $admin, $now) {
            foreach ($customers as $customer) {
                $customer = (array) $customer;
                $this->send($customer, (string) $admin->stamp_shikkou_mail_title, (string) $admin->stamp_shikkou_mail_naiyou);
                $this->stamps->log([
                    'tb_customer_cd' => (int) $customer['cd'],
                    'shori_mae_stamp' => (int) $customer['stamp_count'],
                    'shouhi_stamp' => (int) $customer['stamp_count'],
                    'action_flg' => '7',
                ], $now);
            }
        });

        // スタンプをリセットする処理 (como el legacy: todas las caducadas, también de clientes sin aviso)
        DB::table('tb_customer')->whereNotNull('stamp_yukoukigen')->where('stamp_yukoukigen', '<', $ts)
            ->update(['stamp_count' => null, 'stamp_yukoukigen' => null]);

        return $customers->count();
    }

    /**
     * sendStampShikkouAlertMail.php: aviso a quien tiene sellos que caducan el día «hoy + (stamp_shikkou_alert − 1)».
     * El legacy comparaba `to_timestamp(stamp_yukoukigen)::date`; aquí el mismo día en Asia/Tokyo como rango epoch.
     *
     * @return int|string clientes avisados, o el motivo por el que no se hizo nada
     */
    public function alert(?CarbonInterface $now = null): int|string
    {
        $now ??= now();
        $admin = AdminMaster::current();
        if ((string) $admin->stamp_alert_mail_naiyou === '') {
            return 'メール内容は空です！！！';
        }
        $day = Carbon::instance($now)->setTimezone(LegacyTime::ZONE)->startOfDay()->addDays((int) $admin->stamp_shikkou_alert - 1);

        $customers = DB::table('tb_customer')
            ->where('disp_flg', '1')->where('touroku_kubun', '2')
            ->whereNotNull('stamp_yukoukigen')
            ->whereBetween('stamp_yukoukigen', [$day->getTimestamp(), $day->copy()->endOfDay()->getTimestamp()])
            ->whereRaw('COALESCE(stamp_count, 0) > 0')
            ->orderBy('cd')->get();

        foreach ($customers as $customer) {
            $this->send((array) $customer, (string) $admin->stamp_alert_mail_title, (string) $admin->stamp_alert_mail_naiyou);
        }

        return $customers->count();
    }

    /** cMailsoushin: setKihonData(tienda del cliente) + setDefaultFooter() + replaceMessage + addSimpleLogin + send */
    private function send(array $customer, string $subject, string $body): bool
    {
        $to = trim((string) ($customer['email1'] ?? ''));
        if ($to === '') {
            return false;
        }
        $shop = (string) ($customer['tb_shop_cd'] ?? '');
        $renderer = $this->renderers[$shop] ??= app(MailRenderer::class)->forSender($shop);

        [$subject, $body] = $renderer->replace($subject, $body, $customer);
        $body .= $renderer->simpleLogin($customer);

        try {
            Mail::to($to)->send(new PromotionMail(
                $subject,
                $body."\n\n".$renderer->defaultFooter(false),
                false,
                $this->domain->fromAddress(),
                $renderer->senderName(),
                $this->domain->errorAddress(),
            ));

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }
}
