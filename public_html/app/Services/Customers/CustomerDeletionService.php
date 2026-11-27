<?php

namespace App\Services\Customers;

use App\Models\Customers\Customer;
use Illuminate\Support\Facades\DB;

/**
 * 削除・復元 de la ficha (`inc/customer_delete_register.php` con delete_flg = del / recover)
 * y la comprobación de `inc/customer_recover_confirm.php` (correo ya registrado por otro socio).
 * La sincronización con los servidores POS de tienda (db_sync_all2) queda fuera de alcance.
 */
class CustomerDeletionService
{
    public function __construct(private readonly CustomerFelicaService $felica) {}

    /** disp_flg = 9 y libera la tarjeta FeliCa */
    public function delete(Customer $customer): void
    {
        DB::transaction(function () use ($customer) {
            DB::table('tb_customer')->where('cd', $customer->cd)->update([
                'disp_flg' => '9',
                'synchronize_flg' => '',
                'update_date' => (string) time(),
            ]);
            $this->felica->detach($customer);
        });
    }

    /** disp_flg = 1 (vuelve a la lista) */
    public function restore(Customer $customer): void
    {
        DB::table('tb_customer')->where('cd', $customer->cd)->update([
            'disp_flg' => '1',
            'synchronize_flg' => '',
            'error_send_cnt' => 0,
            'update_date' => (string) time(),
        ]);
    }

    /** ¿Otro socio activo (本登録) ya usa su email1? Entonces no se puede restaurar (customer_recover_confirm.php) */
    public function emailTaken(Customer $customer): bool
    {
        $email = (string) $customer->email1;

        return $email !== '' && DB::table('tb_customer')
            ->where('email1', $email)->where('disp_flg', '1')->where('touroku_kubun', Customer::MEMBER_KUBUN)
            ->where('cd', '<>', $customer->cd)
            ->exists();
    }
}
