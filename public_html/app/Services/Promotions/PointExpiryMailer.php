<?php

namespace App\Services\Promotions;

use App\Mail\Promotions\PromotionMail;
use App\Models\AdminMaster;
use App\Models\Promotions\PointLog;
use App\Models\Promotions\PointShikkouAlert;
use App\Models\Promotions\PushMaster;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Caducidad de puntos: legacy category3/point/sendPointShikkouMail.php (失効: tb_point_log action_flg 7 y puesta a
 * cero; el correo de 失効 está comentado desde 2017), sendPointShikkouAlertMail.php (aviso previo de admin_master)
 * y sendPointShikkouAlertMailMultiple.php (avisos de point_shikkou_alert). Correo a cada cliente y un push para todos.
 * cSendSchedule los metía en el crontab solo con ポイント管理 activo y point_yukoukigen_flg != '2', a la hora de
 * admin_master (o de cada aviso), con tb_office.start_work_time si falta.
 */
class PointExpiryMailer
{
    public const SHIKKOU = 'shikkou';

    public const ALERT = 'alert';

    /** extra_data del push, igual en los dos avisos del legacy */
    public const PUSH_EXTRA_DATA = '{"system":"crm","file":"sendPointShikkouAlertMail","action":"pointExpireAlert"}';

    /** @var array<string, MailRenderer> renderer por tienda (setKihonData) */
    private array $renderers = [];

    public function __construct(
        private readonly SystemDomain $domain,
        private readonly PushTrigger $push,
    ) {}

    /** Condición del crontab: ポイント管理 activo y caducidad en uso */
    public static function scheduled(): bool
    {
        return (string) AdminMaster::current()?->point_yukoukigen_flg !== '2'
            && DB::table('tb_base_system_grandchild')->where('system_name', 'ポイント管理')->where('system_flg', '1')->exists();
    }

    /** La tarea toca ahora: hora de admin_master (point_shikkou_time / point_shikkou_alert_time) o la de apertura */
    public function isDue(string $kind, CarbonInterface $now): bool
    {
        if (! self::scheduled()) {
            return false;
        }
        $admin = AdminMaster::current();

        return $this->matches((string) ($kind === self::ALERT ? $admin->point_shikkou_alert_time : $admin->point_shikkou_time), $now, true);
    }

    /**
     * Avisos múltiples (point_shikkou_alert con flg 1 y alert_day distinto de '' y '0') cuya alert_time es ahora.
     *
     * @return list<int>
     */
    public function dueAlertCds(CarbonInterface $now): array
    {
        if (! self::scheduled()) {
            return [];
        }

        return $this->activeAlerts()
            ->filter(fn ($row) => $this->matches((string) $row->alert_time, $now, false))
            ->map(fn ($row) => (int) $row->cd)->values()->all();
    }

    /**
     * sendPointShikkouMail.php: movimiento de caducidad (shouhi_point = saldo, action_flg '7') de cada cliente con
     * puntos caducados y después NULL en saldo y caducidad de todas las filas caducadas.
     *
     * @return int|string clientes procesados, o el motivo por el que no se hizo nada (como el die() del legacy)
     */
    public function expire(?CarbonInterface $now = null): int|string
    {
        $now ??= now();
        $admin = AdminMaster::current();
        if ((string) $admin->point_shikkou_mail_naiyou === '') {
            return 'メール内容は空です！！！';
        }
        if ((string) $admin->point_yukoukigen_flg === '2') {
            return '有効期限無効です！！！';
        }
        $ts = $now->getTimestamp();

        $customers = $this->customers()
            ->where('tb2.service_point', '>', 0)
            ->whereNotNull('tb2.point_yukoukigen')->where('tb2.point_yukoukigen', '<', $ts)
            ->get();

        DB::transaction(function () use ($customers, $ts) {
            foreach ($customers as $customer) {
                PointLog::create([
                    'tb_customer_cd' => (int) $customer->cd,
                    'touroku_date' => (string) $ts,
                    'shori_mae_point' => (int) $customer->service_point,
                    'shouhi_point' => (int) $customer->service_point,
                    'action_flg' => '7',
                ]);
            }
        });

        // ポイントをリセットする処理 (todas las caducadas, también de clientes dados de baja)
        DB::table('tb_customer_point')->whereNotNull('service_point')
            ->whereNotNull('point_yukoukigen')->where('point_yukoukigen', '<', $ts)
            ->update(['service_point' => null, 'point_yukoukigen' => null]);

        return $customers->count();
    }

    /**
     * sendPointShikkouAlertMail.php: aviso con el texto de admin_master a quien tiene puntos que caducan
     * el día «hoy + (point_shikkou_alert − 1)».
     *
     * @return int|string clientes avisados, o el motivo por el que no se hizo nada
     */
    public function alert(?CarbonInterface $now = null): int|string
    {
        $admin = AdminMaster::current();
        if ((string) $admin->point_alert_mail_naiyou === '') {
            return 'メール内容は空です！！！';
        }

        return $this->notify((int) $admin->point_shikkou_alert, (string) $admin->point_alert_mail_title, (string) $admin->point_alert_mail_naiyou, $now ?? now());
    }

    /**
     * sendPointShikkouAlertMailMultiple.php <cd>: lo mismo con el título, texto y días de un aviso de point_shikkou_alert.
     *
     * @return int|string clientes avisados, o el motivo por el que no se hizo nada
     */
    public function alertMultiple(int $cd, ?CarbonInterface $now = null): int|string
    {
        $row = PointShikkouAlert::query()->where('flg', '1')->where('cd', $cd)->first();
        if (! $row) {
            return '';
        }
        if ((string) $row->message === '') {
            return 'メール内容は空です！！！';
        }

        return $this->notify((int) $row->alert_day, (string) $row->title, (string) $row->message, $now ?? now());
    }

    private function notify(int $alertDay, string $title, string $message, CarbonInterface $now): int
    {
        $day = Carbon::instance($now)->setTimezone(LegacyTime::ZONE)->startOfDay()->addDays($alertDay - 1);

        $customers = $this->customers()
            ->whereNotNull('tb2.point_yukoukigen')
            ->whereBetween('tb2.point_yukoukigen', [$day->getTimestamp(), $day->copy()->endOfDay()->getTimestamp()])
            ->whereRaw('COALESCE(tb2.service_point, 0) > 0')
            ->get();

        if ($customers->isEmpty()) {
            return 0;
        }
        foreach ($customers as $customer) {
            $this->send((array) $customer, $title, $message);
        }
        $this->pushNotice($title, $message, $alertDay, $now);

        return $customers->count();
    }

    /** Clientes activos (touroku_kubun 2) con su saldo, ordenados por cd */
    private function customers()
    {
        return DB::table('tb_customer as tb1')
            ->join('tb_customer_point as tb2', 'tb1.cd', '=', 'tb2.tb_customer_cd')
            ->where('tb1.disp_flg', '1')->where('tb1.touroku_kubun', '2')
            ->select('tb1.*', 'tb2.service_point')
            ->orderBy('tb1.cd');
    }

    /** 2017.03.31 プッシュ通知を追加: push_master (tienda -1) con la SQL del legacy en wherestr (la lee g-api) y aviso */
    private function pushNotice(string $title, string $message, int $alertDay, CarbonInterface $now): void
    {
        $ts = $now->getTimestamp();
        $row = PushMaster::create([
            'tb_shop_cd' => -1,
            'title' => $title,
            'message' => $message,
            'img_flg' => '',
            'wherestr' => self::legacyAlertSql($alertDay),
            'disp_flg' => '1',
            'touroku_date' => $ts,
            'update_date' => $ts,
            'search_display' => '',
            'version' => null,
            'post_type' => null,
            'extra_data' => self::PUSH_EXTRA_DATA,
        ]);

        $this->push->trigger('push', (int) $row->cd);
    }

    /** La SQL de clientes del aviso tal como la armaba el legacy ($customerSQL) */
    public static function legacyAlertSql(int $alertDay): string
    {
        $sql = 'SELECT tb1.cd, tb1.registration_id, tb1.tb_shop_cd, tb1.name, tb1.email1, tb1.id AS mypage_id, tb1.kojin_birth, tb1.zip, tb1.pref, tb1.add, tb1.add_number, tb1.add_build, tb1.tel, tb1.stamp_count, tb1.login_id, tb1.login_pass, tb2.service_point, MAX(tb3.raiten_time) AS last_raiten_time FROM tb_customer tb1 ';
        $sql .= 'INNER JOIN tb_customer_point tb2 ON tb1.cd = tb2.tb_customer_cd ';
        $sql .= 'LEFT JOIN tb_raiten_rireki tb3 ON tb1.cd = tb3.user_cd ';
        $sql .= "WHERE tb1.disp_flg='1' AND tb1.touroku_kubun='2' AND tb2.point_yukoukigen IS NOT NULL AND to_timestamp(tb2.point_yukoukigen)::date = (timestamp 'today' + interval '".($alertDay - 1)." days')::date AND COALESCE(tb2.service_point, 0) > 0 ";
        $sql .= 'GROUP BY 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17';

        return $sql;
    }

    /** @return Collection<int, object> */
    private function activeAlerts(): Collection
    {
        return PointShikkouAlert::query()->where('flg', '1')
            ->whereNotNull('alert_day')->where('alert_day', '!=', '')->where('alert_day', '!=', '0')
            ->orderBy('cd')->get();
    }

    /**
     * «HH:MM» coincide con la hora de Japón de $now. Sin hora: la de apertura de tb_office (start_work_time «H<>M»)
     * si $officeFallback, o 00:00 como el (int) del legacy.
     */
    private function matches(string $time, CarbonInterface $now, bool $officeFallback): bool
    {
        [$hour, $minute] = array_pad(explode(':', $time), 2, '');
        if ($officeFallback) {
            $start = array_pad(explode('<>', (string) DB::table('tb_office')->where('cd', 1)->value('start_work_time')), 2, '');
            $hour = $hour !== '' ? $hour : $start[0];
            $minute = $minute !== '' ? $minute : $start[1];
        }
        $at = Carbon::instance($now)->setTimezone(LegacyTime::ZONE);

        return $at->hour === (int) $hour && $at->minute === (int) $minute;
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
