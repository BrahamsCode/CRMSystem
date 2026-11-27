<?php

namespace App\Console\Commands\Promotions;

use App\Models\AdminMaster;
use App\Models\Promotions\PointLog;
use App\Services\Promotions\LegacyTime;
use App\Services\Promotions\PointService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * 会員登録時のポイント付与 (legacy system/category3/point/setRegisterPoint.php, cada 5 minutos en el crontab
 * de cSendSchedule). Por tienda, con tb_shop.customer_registration_settings (registPoints, days, hours, minutes):
 * si la hora configurada cayó en los últimos 5 minutos, suma registPoints a los clientes registrados hace `days`
 * días que aún no tienen el movimiento `crm:newRegist`/`mycolle:newRegist` (personal 1, terminal -100).
 *
 * Nombre de clase distinto de `RegisterPointCommand` a propósito: PromocionServiceProvider::TASKS (S3) programaría
 * esa clase sin las condiciones del legacy; la programa PromocionFidelizacionServiceProvider.
 */
class GrantRegisterPointsCommand extends Command
{
    protected $signature = 'promocion:register-point';

    protected $description = '会員登録時のポイント付与: suma los puntos de registro configurados por tienda (setRegisterPoint.php)';

    /** Ventana de la hora configurada (el crontab lo lanzaba cada 5 minutos) */
    public const WINDOW_SECONDS = 300;

    /** tb_terminal_cd con que el legacy marca los puntos del cron */
    public const TERMINAL_CD = -100;

    /** Condición del crontab: ポイント管理 activo y la app en uso (admin_master.application_use_flg = 1) */
    public static function shouldSchedule(): bool
    {
        return (string) AdminMaster::current()->application_use_flg === '1'
            && DB::table('tb_base_system_grandchild')->where('system_name', 'ポイント管理')->where('system_flg', '1')->exists();
    }

    public function handle(PointService $points): int
    {
        $now = Carbon::now(LegacyTime::ZONE);
        $shops = DB::table('tb_shop')->where('disp_flg', '1')->orderBy('cd')->get(['cd', 'customer_registration_settings']);

        foreach ($shops as $shop) {
            $settings = @unserialize((string) $shop->customer_registration_settings, ['allowed_classes' => false]);
            $settings = is_array($settings) ? $settings : [];
            $registPoints = (int) ($settings['registPoints'] ?? 0);
            $days = (int) ($settings['days'] ?? 0);
            $time = sprintf('%02s', $settings['hours'] ?? '').':'.sprintf('%02s', $settings['minutes'] ?? '');
            $realTime = $this->todayAt($now, $time);

            $this->line('店舗'.$shop->cd.' { points: '.$registPoints.', days: '.$days.', time: '.$time.', realTime: '.($realTime?->format('Y-m-d H:i:s') ?? '').' }');

            // 5分以内の設定ならばOK
            if ($registPoints <= 0 || $days <= 0 || $realTime === null
                || $realTime->getTimestamp() > $now->getTimestamp() || $realTime->getTimestamp() < $now->getTimestamp() - self::WINDOW_SECONDS) {
                continue;
            }

            $customers = $this->targets((int) $shop->cd, $now->copy()->startOfDay()->subDays($days));
            if ($customers->isEmpty()) {
                continue;
            }

            $this->line('会員登録にて「'.$registPoints.'ポイント」を加算する顧客リスト');
            foreach ($customers as $customer) {
                $actionFlg = $customer->mycolle_touroku_date ? PointLog::ACTION_APP_SIGNUP : PointLog::ACTION_CRM_SIGNUP;
                $result = $points->setPoint($registPoints, PointService::ADD, (int) $shop->cd, 1, (int) $customer->cd, $actionFlg, self::TERMINAL_CD);
                if ($result['status'] === 'ok') {
                    $this->line($customer->cd.'【'.$customer->id.'】 <== '.$actionFlg);
                }
            }
        }

        $this->line('終了しました。');

        return self::SUCCESS;
    }

    /**
     * Clientes activos registrados (touroku_kubun = 2) en la tienda el día dado (Asia/Tokyo) sin puntos de registro.
     * El legacy comparaba TO_TIMESTAMP(touroku_date)::DATE en la zona de la BD; aquí, el rango del día en Tokio.
     */
    private function targets(int $shopCd, Carbon $day)
    {
        return DB::table('tb_customer as t1')
            ->leftJoin('tb_point_log as t2', function ($join) {
                $join->on('t1.cd', '=', 't2.tb_customer_cd')
                    ->whereIn('t2.action_flg', [PointLog::ACTION_APP_SIGNUP, PointLog::ACTION_CRM_SIGNUP]);
            })
            ->where('t1.disp_flg', '1')
            ->where('t1.touroku_kubun', '2')
            ->where('t1.tb_shop_cd', $shopCd)
            ->whereRaw("(CASE WHEN t1.touroku_date ~ '^[0-9]+$' THEN t1.touroku_date::bigint END) BETWEEN ? AND ?",
                [$day->getTimestamp(), $day->copy()->endOfDay()->getTimestamp()])
            ->whereNull('t2.action_flg')
            ->get(['t1.cd', 't1.id', 't1.mycolle_touroku_date']);
    }

    /** strtotime(date('Y-m-d ').$time): null si la hora no es válida (strtotime devolvía false) */
    private function todayAt(Carbon $now, string $time): ?Carbon
    {
        if (! preg_match('/^([0-9]{1,2}):([0-9]{1,2})$/', $time, $m) || (int) $m[1] > 24 || (int) $m[2] > 59) {
            return null;
        }

        return $now->copy()->startOfDay()->setTime((int) $m[1], (int) $m[2]);
    }
}
