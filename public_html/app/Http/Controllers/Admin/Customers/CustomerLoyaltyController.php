<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Http\Controllers\Controller;
use App\Models\Customers\Customer;
use App\Models\Staff;
use App\Services\Customers\ManualLoyaltyService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Puntos y sellos manuales de la ficha ($.get de show.blade.php): `inc/point_register.php` (pestaña ポイント),
 * `inc/stamp_register.php` (pestaña スタンプ) e `inc/stamp_use_register.php`. Respuestas en texto como el legacy.
 * La tienda y el personal del movimiento son los de la sesión.
 */
class CustomerLoyaltyController extends Controller
{
    public function points(Request $request, ManualLoyaltyService $loyalty): Response
    {
        $customer = $this->customer($request);
        if (! $customer) {
            return response('');
        }

        return response($loyalty->registerPoints(
            $customer,
            (int) $request->input('point_qty'),
            trim((string) $request->input('point_action_type', '')),
            $this->shopCd($request),
            $this->staff($request)->cd,
        ));
    }

    public function stamps(Request $request, ManualLoyaltyService $loyalty): Response
    {
        $customer = $this->customer($request);
        if (! $customer) {
            return response('');
        }

        return response($loyalty->registerStamps(
            $customer,
            (int) $request->input('stamp_qty'),
            trim((string) $request->input('stamp_action_type', '')),
            $this->shopCd($request),
            $this->staff($request)->cd,
            (string) $request->input('oshirase_mail_flg') === '1',
        ));
    }

    public function useStamps(Request $request, ManualLoyaltyService $loyalty): Response
    {
        $customer = $this->customer($request);
        if ($customer) {
            $loyalty->useStamps(
                $customer,
                trim((string) $request->input('shouhi_action', '')),
                trim((string) $request->input('stamp_count_subtract', '')),
                (string) $request->input('oshirase_mail') === '1',
            );
        }

        return response('');
    }

    private function customer(Request $request): ?Customer
    {
        $cd = (string) $request->input('kaiin_cd', '');

        return ctype_digit($cd) ? Customer::withDeleted()->find((int) $cd) : null;
    }

    private function staff(Request $request): Staff
    {
        return $request->user('admin');
    }

    private function shopCd(Request $request): int
    {
        return (int) (session('shop_cd') ?? $this->staff($request)->shop_cd);
    }
}
