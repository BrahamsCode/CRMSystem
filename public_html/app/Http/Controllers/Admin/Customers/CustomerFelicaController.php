<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Http\Controllers\Controller;
use App\Models\Customers\Customer;
use App\Services\Customers\CustomerFelicaService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * UID関連付け解除 de la ficha (legacy `inc/customer_card_delete.php` y su copia `customer/customer_card_delete.php`).
 */
class CustomerFelicaController extends Controller
{
    /** 関連付けを解除する (separate_flg = separate): libera la tarjeta y muestra 「解除中です。」 antes de volver a la ficha */
    public function destroy(Request $request, int $customer, CustomerFelicaService $felica): View
    {
        $model = Customer::withDeleted()->findOrFail($customer);
        // Como el legacy: solo con separate_flg = separate (el formulario de la ficha siempre lo envía)
        if ($request->input('separate_flg') === 'separate') {
            $felica->detach($model);
        }

        $popup = $request->input('wintype') === 'no_headers';

        return view('admin.clientes.processing', [
            'mode' => 'felica',
            'popup' => $popup,
            'detailUrl' => route('admin.clientes.show', $popup ? [$model->cd, 'wintype' => 'no_headers'] : [$model->cd]),
        ]);
    }
}
