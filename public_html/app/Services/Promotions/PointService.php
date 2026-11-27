<?php

namespace App\Services\Promotions;

use App\Models\AdminMaster;
use App\Models\Promotions\CustomerPoint;
use App\Models\Promotions\PointLog;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Puntos del cliente (legacy system/class/Point.php).
 *
 * setPoint(): como Point::setPoint. Bloquea el saldo (tb_customer_point FOR UPDATE), suma o resta,
 * renueva la caducidad (hoy + admin_master.point_shikkou_kikan días, 365 por defecto, − 1 s) y deja
 * el movimiento en tb_point_log (sounyu_point al sumar, shouhi_point al restar, shori_mae_point = saldo anterior).
 * La fila de tb_customer_point usa cd = cd del cliente, como el INSERT del legacy.
 */
class PointService
{
    public const ADD = 1;

    public const SUBTRACT = 2;

    /**
     * @return array{status: string, res?: string, message?: string}
     */
    public function setPoint(int $points, int $type, int $shopCd, ?int $staffCd, int $customerCd, ?string $actionFlg = null, ?int $terminalCd = -1, ?CarbonInterface $at = null): array
    {
        if ($points < 0 || ! in_array($type, [self::ADD, self::SUBTRACT], true)) {
            return ['status' => 'ng'];
        }

        $at ??= now();

        return DB::transaction(function () use ($points, $type, $shopCd, $staffCd, $customerCd, $actionFlg, $terminalCd, $at) {
            $balance = CustomerPoint::query()->where('tb_customer_cd', $customerCd)->lockForUpdate()->first();
            $before = (int) ($balance?->service_point ?? 0);

            if ($type === self::SUBTRACT && $before < $points) {
                return ['status' => 'ng', 'message' => $points . 'ポイントを削減することは出来ません'];
            }

            $after = $type === self::ADD ? $before + $points : $before - $points;
            $expires = $this->expiryFrom($at);

            if ($balance) {
                $balance->service_point = $after;
                $balance->point_yukoukigen = $expires;
                $balance->save();
            } else {
                CustomerPoint::create([
                    'cd' => $customerCd, 'tb_customer_cd' => $customerCd, 'service_point' => $after,
                    'point_yukoukigen' => $expires, 'touroku_date' => (string) $at->getTimestamp(),
                ]);
            }

            PointLog::create([
                'tb_shop_cd' => $shopCd,
                'tb_staff_cd' => $staffCd,
                'tb_customer_cd' => $customerCd,
                'tb_terminal_cd' => $terminalCd,
                'action_flg' => $actionFlg ?: null,
                'touroku_date' => (string) $at->getTimestamp(),
                'shori_mae_point' => $before,
                $type === self::ADD ? 'sounyu_point' : 'shouhi_point' => $points,
            ]);

            return ['status' => 'ok', 'res' => $points . ($type === self::ADD ? 'ポイントを追加しました。' : 'ポイントを削減しました。')];
        });
    }

    /** Saldo actual (tb_customer_point.service_point) */
    public function balance(int $customerCd): int
    {
        return (int) CustomerPoint::query()->where('tb_customer_cd', $customerCd)->value('service_point');
    }

    /** strtotime("TODAY +N DAYS") - 1 con N = admin_master.point_shikkou_kikan (365 si está vacío) */
    public function expiryFrom(CarbonInterface $at): int
    {
        $days = trim((string) AdminMaster::current()->point_shikkou_kikan);
        $days = $days === '' ? 365 : (int) $days;

        return Carbon::instance($at)->setTimezone(LegacyTime::ZONE)->startOfDay()->addDays($days)->getTimestamp() - 1;
    }

    /**
     * Puntos por check-in de la tienda (tb_shop.login_default_point_settings, array PHP serializado:
     * useFlg, earnPoints…). null si no hay configuración.
     */
    public function defaultPointSetting(int $shopCd): ?array
    {
        if ($shopCd === 0) {
            return null;
        }

        $raw = DB::table('tb_shop')->where('cd', $shopCd)->value('login_default_point_settings');
        if ($raw === null || $raw === '') {
            return null;
        }

        $value = @unserialize((string) $raw, ['allowed_classes' => false]);

        return is_array($value) ? $value : null;
    }
}
