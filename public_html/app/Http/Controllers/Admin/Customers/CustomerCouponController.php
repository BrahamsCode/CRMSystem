<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * マイクーポン削除 de la pestaña マイクーポン (legacy `inc/detail_tab6.php`, que borraba con GET ?coupon_cd=).
 */
class CustomerCouponController extends Controller
{
    /** DELETE de tb_mycoupon (tabla de S3, borrado físico como el legacy) y vuelta a la pestaña */
    public function destroy(Request $request, int $customer): RedirectResponse
    {
        $couponCd = (string) $request->input('coupon_cd', '');
        if (preg_match('/^[0-9]+$/', $couponCd)) {
            // A diferencia del legacy, solo una cupón de este cliente
            DB::table('tb_mycoupon')->where('cd', (int) $couponCd)->where('tb_customer_cd', $customer)->delete();
        }

        $params = [$customer, 'tab' => $request->input('tab', '6'), 'kaiin_cd' => $customer];
        if ($request->input('wintype') === 'no_headers') {
            $params['wintype'] = 'no_headers';
        }

        return redirect()->route('admin.clientes.show', $params);
    }
}
