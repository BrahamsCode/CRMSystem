<?php

namespace App\Services\Customers;

use App\Models\Customers\Customer;
use App\Services\Promotions\PointService;
use App\Services\Promotions\StampService;
use Illuminate\Support\Facades\DB;

/**
 * Puntos y sellos manuales desde la ficha del cliente (pestañas ポイント y スタンプ), sobre los servicios de S3:
 * - `inc/point_register.php`: 「ポイント処理実行」 → `PointService::setPoint` (sumar o restar).
 * - `inc/stamp_register.php`: 「スタンプ処理実行」 → saldo con `StampService::setCount` (como pushStampCount
 *   「新規登録」, sin tocar last_stamp_time), log action_flg 4 y, si se pide, la クーポンお知らせメール.
 * - `inc/stamp_use_register.php`: 消費 de la ficha antigua (category3).
 * Las respuestas son las del legacy ("OK<>mensaje", "ERROR<>mensaje" o vacío): los JS solo miran si están vacías.
 */
class ManualLoyaltyService
{
    public function __construct(private PointService $points, private StampService $stamps) {}

    /** point_register.php: '' si no hay tipo o la cantidad no es positiva; si no, "OK<>" + mensaje de setPoint */
    public function registerPoints(Customer $customer, int $points, string $actionType, int $shopCd, ?int $staffCd): string
    {
        if ($actionType === '' || $points <= 0) {
            return '';
        }

        $result = $this->points->setPoint($points, (int) $actionType, $shopCd, $staffCd, (int) $customer->cd);

        // El legacy solo imprimía 'res' (vacío cuando setPoint no podía restar)
        return 'OK<>' . ($result['res'] ?? '');
    }

    /** stamp_register.php ('1' 追加, '2' 削減; los mensajes del legacy dicen ポイント) */
    public function registerStamps(Customer $customer, int $stamps, string $actionType, int $shopCd, ?int $staffCd, bool $notify): string
    {
        if ($actionType === '' || $stamps <= 0) {
            return '';
        }

        $result = DB::transaction(function () use ($customer, $stamps, $actionType, $shopCd, $staffCd) {
            $current = (int) DB::table('tb_customer')->where('cd', $customer->cd)->lockForUpdate()->value('stamp_count');
            $config = $this->stamps->config();

            switch ($actionType) {
                case '1':
                    $newCount = $current + $stamps;
                    $field = 'sounyu_stamp';
                    $message = $stamps . 'ポイントを追加しました。';
                    break;
                case '2':
                    if ($current < $stamps) {
                        return 'ERROR<>' . $stamps . 'ポイントを削減することは出来ません！！';
                    }
                    $newCount = $current - $stamps;
                    $field = 'shouhi_stamp';
                    $message = $stamps . 'ポイントを削減しました。';
                    break;
                default:
                    // El legacy seguía con valores sin definir (stamp_count NULL): aquí no se hace nada
                    return 'OK<>';
            }

            $this->stamps->setCount($customer->fresh() ?? $customer, $newCount, $config['stamp_yukoukigen'], signup: true);
            $this->stamps->log([
                'tb_shop_cd' => $shopCd,
                'tb_staff_cd' => $staffCd ?: null,
                'tb_customer_cd' => (int) $customer->cd,
                'tb_terminal_cd' => -1,
                'shori_mae_stamp' => $current,
                $field => $stamps,
                'action_flg' => '4',
            ]);

            return 'OK<>' . $message;
        });

        if ($notify && str_starts_with($result, 'OK<>') && $result !== 'OK<>') {
            $this->notify($customer);
        }

        return $result;
    }

    /**
     * stamp_use_register.php (「スタンプ処理実行」 de la ficha de category3). Como el legacy, SUMA la cantidad al saldo
     * (`stamp_count = coalesce(stamp_count, 0) + n`) aunque el campo se llame stamp_count_subtract; marca
     * last_stamp_time y update_date, sin log. Opcionalmente manda la クーポンお知らせメール.
     */
    public function useStamps(Customer $customer, string $action, string $count, bool $notify): void
    {
        if ($action !== 'スタンプ処理実行' || $count === '') {
            return;
        }

        $now = (string) time();
        DB::table('tb_customer')->where('cd', $customer->cd)->update([
            'stamp_count' => DB::raw('coalesce(stamp_count, 0) + ' . (int) $count),
            'last_stamp_time' => $now,
            'update_date' => $now,
        ]);

        if ($notify) {
            $this->notify($customer);
        }
    }

    /** cOshiraseMail con el aviso de cupones de la tienda del cliente para su número actual de sellos */
    private function notify(Customer $customer): void
    {
        $fresh = Customer::withDeleted()->find($customer->cd);
        if ($fresh && $fresh->tb_shop_cd !== null) {
            $this->stamps->notifyReachedCount($fresh, (int) $fresh->tb_shop_cd, (int) $fresh->stamp_count);
        }
    }
}
