<?php

namespace App\Http\Controllers\Publico\Integraciones\Appview;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * appview: alta rápida de socio desde la app (member_kiyaku → member_comfirm → member_complete).
 */
class MemberRegistrationController extends Controller
{
    use RendersAppview;

    /** Clave de sesión del token de member_comfirm.php ($_SESSION["customer_regist"]) */
    public const SESSION_TOKEN = 'appview.customer_regist';

    /**
     * member_kiyaku.php: valida nombre y teléfono (solo dígitos) y muestra el 利用規約 con el nombre de la primera
     * tienda activa. La メールアドレス nunca se validaba (variable sin asignar en el legacy): se mantiene.
     */
    public function terms(Request $request): Response
    {
        $name = $request->input('name');
        $tel = $request->input('tel');

        $errors = '';
        if ($name === null || $name === '') {
            $errors .= '名前を必ず入力ください。<br>';
        }
        if ($tel === null || $tel === '') {
            $errors .= '電話番号を必ず入力ください。<br>';
        } elseif (! is_string($tel) || ! preg_match('/^[0-9]*$/', $tel)) {
            $errors .= '電話番号を数字のみで入力ください。<br>';
        }

        // SELECT * FROM tb_shop WHERE disp_flg = '1' LIMIT 1: shop_cd solo podía elegir esa misma fila
        $shop = DB::table('tb_shop')->where('disp_flg', '1')->limit(1)->first(['kaishamei']);

        return $this->page('member-kiyaku', $request, [
            '_error_string' => $errors,
            '_shop_data' => ['kaishamei' => $shop?->kaishamei],
        ]);
    }

    /** member_comfirm.php: confirmación con un token nuevo en la sesión (lo comprueba member_complete) */
    public function confirm(Request $request): Response
    {
        $token = bin2hex(random_bytes(16));
        $request->session()->put(self::SESSION_TOKEN, $token);

        return $this->page('member-comfirm', $request, ['token' => $token]);
    }
}
