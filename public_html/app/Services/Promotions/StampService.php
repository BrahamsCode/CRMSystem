<?php

namespace App\Services\Promotions;

use App\Mail\Promotions\StampCouponNoticeMail;
use App\Models\AdminMaster;
use App\Models\Customers\Customer;
use App\Models\Promotions\MycouponMaster;
use App\Models\Promotions\StampConfig;
use App\Models\Promotions\StampLog;
use App\Models\Promotions\StampOshiraseCoupon;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Sellos del cliente (legacy system/class/cStamp.php y cOshiraseCoupon.php).
 * El saldo vive en tb_customer (stamp_count, stamp_yukoukigen, last_stamp_time); los movimientos en tb_stamp_log.
 */
class StampService
{
    /**
     * cStamp::getStampConfig(): como el legacy, el máximo y el intervalo salen de la fila cd = 1 de
     * tb_stamp_config (la configuración general), aunque la tienda tenga la suya.
     *
     * @return array{stamp_max: int, touroku_bonus: int, limit_flg: int, stamp_yukoukigen: int, raiten_stamp_flg: ?string, stamp_update_type_flg: ?string}
     */
    public function config(?CarbonInterface $at = null): array
    {
        $at ??= now();
        $general = StampConfig::query()->find(1);
        $master = AdminMaster::current();
        $days = trim((string) $master->stamp_shikkou_kikan);
        $days = $days === '' ? 365 : (int) $days;

        return [
            'stamp_max' => (int) ($general?->stamp_max ?? 0),
            'touroku_bonus' => (int) ($general?->touroku_bonus ?? 0),
            'limit_flg' => (int) ($general?->limit_flg ?? 0),
            'stamp_yukoukigen' => Carbon::instance($at)->setTimezone(LegacyTime::ZONE)->startOfDay()->addDays($days)->getTimestamp() - 1,
            'raiten_stamp_flg' => $master->raiten_stamp_flg,
            'stamp_update_type_flg' => $master->stamp_update_type_flg,
        ];
    }

    /**
     * cStamp::pushStampCount(): nuevo saldo; renueva la caducidad si la opción 有効期間更新 no está activa,
     * si el cliente no tenía caducidad o si tenía 0 sellos. Marca last_stamp_time salvo en el alta.
     */
    public function setCount(Customer $customer, int $newCount, int $yukoukigen, bool $signup = false, ?CarbonInterface $at = null): void
    {
        $at ??= now();
        $before = (int) DB::table('tb_customer')->where('cd', $customer->cd)->value('stamp_count');
        $updateType = AdminMaster::current()->stamp_update_type_flg;

        $values = ['stamp_count' => $newCount];
        if (! $updateType || (string) $customer->stamp_yukoukigen === '' || $before === 0) {
            $values['stamp_yukoukigen'] = $yukoukigen;
        }
        if (! $signup) {
            $values['last_stamp_time'] = $at->getTimestamp();
        }

        DB::table('tb_customer')->where('cd', $customer->cd)->update($values);
    }

    /** cStamp::pushStampLog() */
    public function log(array $data, ?CarbonInterface $at = null): StampLog
    {
        return StampLog::create($data + ['touroku_date' => (string) ($at ?? now())->getTimestamp()]);
    }

    /**
     * cOshiraseCoupon::setOshiraseNaiyou(): si la tienda tiene un aviso para ese número de sellos,
     * manda la クーポンお知らせメール con los cupones de canje.
     */
    public function notifyReachedCount(Customer $customer, int $shopCd, int $stampCount): bool
    {
        $notice = StampOshiraseCoupon::query()
            ->where('tb_shop_cd', $shopCd)->where('stamp_count', $stampCount)
            ->where('disp_flg', '1')->where('hyouji_flg', '1')
            ->first();

        if (! $notice || blank($customer->email1)) {
            return false;
        }

        Mail::to($customer->email1)->send(new StampCouponNoticeMail($this->noticeText($notice)));

        return true;
    }

    /** Texto del aviso, igual que el legacy */
    public function noticeText(StampOshiraseCoupon $notice): string
    {
        $text = $notice->naiyou . "\n\n\n";

        foreach (explode(',', (string) $notice->tb_mycoupon_master_cd) as $cd) {
            $coupon = is_numeric(trim($cd)) ? MycouponMaster::withDeleted()->find((int) $cd) : null;
            if (! $coupon) {
                continue;
            }
            $text .= '■' . $coupon->title . "\n";
            $text .= '消費スタンプ：' . $coupon->shouhi_point . "\n\n";
            $text .= $coupon->comment . "\n\n";
            foreach (explode('<>', (string) $coupon->attention) as $attention) {
                $text .= $attention !== '' ? $attention . "\n" : '';
            }
            $text .= "\n-------------------\n\n";
        }

        return $text . "スタンプ交換用クーポンは、マイページの「スタンプ」の中に入っていますので、ご確認ください。\n";
    }
}
