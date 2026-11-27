<?php

namespace App\Services\Customers;

use App\Models\Customers\Customer;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Tarjeta FeliCa del socio (`tb_customer.felica_no`), como `config/card_new.php` (asignar)
 * e `inc/customer_card_delete.php` (UID関連付け解除). Lo usa S4 al borrar una tarjeta.
 */
class CustomerFelicaService
{
    /**
     * Asigna la tarjeta (IDm) al socio. Como el legacy, exige que el socio no tenga tarjeta y que
     * la tarjeta no esté asignada (`tb_user_card.staff_cd` vacío); marca `tb_user_card` y `tb_play_time`.
     *
     * @throws DomainException con el mensaje del legacy si ya está registrada
     */
    public function attach(Customer $customer, string $idm): void
    {
        DB::transaction(function () use ($customer, $idm) {
            $card = DB::table('tb_user_card')->where('felica_no', $idm)->lockForUpdate()->first();
            $current = (string) DB::table('tb_customer')->where('cd', $customer->cd)->value('felica_no');

            if ($current !== '' || ($card && (string) $card->staff_cd !== '')) {
                throw new DomainException("カード {$idm} は既に登録されているカードです。ご確認ください。");
            }

            DB::table('tb_customer')->where('cd', $customer->cd)->update(['felica_no' => $idm]);
            DB::table('tb_user_card')->where('felica_no', $idm)->update(['card_flg' => '1', 'staff_cd' => (string) $customer->cd]);
            DB::table('tb_play_time')->where('staff_cd', $customer->cd)->update(['felica_no' => $idm]);
        });

        $customer->felica_no = $idm;
    }

    /** Libera la tarjeta del socio: las mismas cuatro actualizaciones que `customer_card_delete.php` */
    public function detach(Customer $customer): void
    {
        DB::transaction(function () use ($customer) {
            DB::table('tb_user_card')->where('staff_cd', (string) $customer->cd)->delete();
            DB::table('tb_customer')->where('cd', $customer->cd)->update(['felica_no' => '']);
            DB::table('tb_play_time')->where('staff_cd', $customer->cd)->update(['felica_no' => '']);
            DB::table('tb_customer_card')->where('tb_customer_cd', $customer->cd)->update(['tb_customer_cd' => null]);
        });

        $customer->felica_no = '';
    }

    /** Socio activo con esa tarjeta, o null */
    public function findByIdm(string $idm): ?Customer
    {
        return $idm === '' ? null : Customer::where('felica_no', $idm)->first();
    }
}
