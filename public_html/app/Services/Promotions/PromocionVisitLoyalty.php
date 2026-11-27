<?php

namespace App\Services\Promotions;

use App\Models\AdminMaster;
use App\Models\Customers\Customer;
use App\Models\Customers\Visit;
use App\Services\Customers\VisitLoyalty;

/**
 * Sellos y puntos de una visita: la parte de S3 de cUtility::raitenFunc (system/class/cUtility.php).
 * S2 (VisitService::register) la llama; PromocionServiceProvider la enlaza a la interfaz de S2.
 *
 * 1. Puntos de check-in (solo si se registró la visita y admin_master.application_use_flg = 1):
 *    tb_shop.login_default_point_settings con useFlg = 1 y earnPoints > 0 →
 *    PointRuleResolver::points() × earnPoints, con action_flg 'mycolle:checkIn'.
 * 2. Sellos (si $stampFlag = '' y admin_master.raiten_stamp_flg <> '2'): por debajo del máximo y
 *    pasado el intervalo desde last_stamp_time, suma los sellos de la regla (sin pasar del máximo),
 *    deja tb_stamp_log (action_flg 1, raiten_shori '1' si hubo visita) y, si cambió el saldo,
 *    manda el aviso de cupones de ese número de sellos.
 */
class PromocionVisitLoyalty implements VisitLoyalty
{
    public function __construct(
        private PointRuleResolver $rules,
        private PointService $points,
        private StampService $stamps,
    ) {}

    public function stampsForVisit(int $shopCd): int
    {
        return $this->rules->stamps($shopCd);
    }

    public function afterVisit(Customer $customer, int $shopCd, ?Visit $visit, ?int $terminalCd, ?int $staffCd, string $stampFlag): void
    {
        if ($stampFlag === '2') {
            return;
        }

        if ($visit !== null) {
            $this->checkInPoints($customer, $shopCd);
        }

        $this->visitStamps($customer, $shopCd, $visit !== null, $terminalCd, $staffCd, $stampFlag);
    }

    private function checkInPoints(Customer $customer, int $shopCd): void
    {
        if ((string) AdminMaster::current()->application_use_flg !== '1') {
            return;
        }

        $setting = $this->points->defaultPointSetting($shopCd);
        if (! $setting || (string) ($setting['useFlg'] ?? '') !== '1' || (int) ($setting['earnPoints'] ?? 0) <= 0) {
            return;
        }

        $count = $this->rules->points($shopCd, $customer);
        if ($count > 0) {
            $this->points->setPoint($count * (int) $setting['earnPoints'], PointService::ADD, $shopCd, null, (int) $customer->cd, 'mycolle:checkIn');
        }
    }

    private function visitStamps(Customer $customer, int $shopCd, bool $visited, ?int $terminalCd, ?int $staffCd, string $stampFlag): void
    {
        $config = $this->stamps->config();
        $current = (int) $customer->stamp_count;

        if ($stampFlag !== ''
            || $current >= $config['stamp_max']
            || now()->getTimestamp() <= (int) $customer->last_stamp_time + $config['limit_flg']
            || (string) $config['raiten_stamp_flg'] === '2') {
            return;
        }

        $gained = $this->rules->stamps($shopCd);
        $total = min($current + $gained, $config['stamp_max']);

        $this->stamps->setCount($customer, $total, $config['stamp_yukoukigen']);
        $this->stamps->log([
            'tb_shop_cd' => $shopCd,
            'tb_staff_cd' => $staffCd,
            'tb_customer_cd' => (int) $customer->cd,
            'tb_terminal_cd' => $terminalCd,
            'shori_mae_stamp' => $current,
            'sounyu_stamp' => $total - $current,
            'action_flg' => '1',
            'raiten_shori' => $visited ? '1' : '',
        ]);

        if ($total !== $current) {
            $this->stamps->notifyReachedCount($customer, $shopCd, $total);
        }
    }
}
