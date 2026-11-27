<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Http\Controllers\Controller;
use App\Models\Customers\Customer;
use App\Services\Integraciones\MobileShop\MobileShopClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * 有料会員 (pestaña 14): `system/category1/inc/ajax_changePaidMemberFlg.php`. Avisa a MobileShop
 * (cliente de S5) de que el socio deja de ser de pago, con las mismas respuestas que el legacy.
 */
class PaidMemberController extends Controller
{
    public function __invoke(Request $request, MobileShopClient $client): JsonResponse|Response
    {
        $cd = (string) $request->input('cd');
        if (! ctype_digit($cd)) {
            return response('type error');
        }

        $customer = Customer::withDeleted()->find((int) $cd);
        $resultCode = $client->sendUnpaidMemberStatus($client->fetchToken(), $customer?->id);

        // Log::addLog('paid_member', "$cd:$code") del legacy
        Log::info('paid_member', ['entry' => $cd . ':' . ($resultCode === false ? '' : (string) $resultCode)]);

        return $resultCode === 1
            ? response()->json(['status' => 'ok', 'message' => 'Success.'])
            : response()->json(['status' => 'ng', 'message' => '処理失敗しました。code:' . ($resultCode === false ? '' : (string) $resultCode)]);
    }
}
