<?php

namespace App\Http\Controllers\Publico\Integraciones\Appview;

use App\Http\Controllers\Controller;
use App\Services\Integraciones\Appview\MobileTheme;
use App\Services\Integraciones\ToolApi\ToolApiOutput as Out;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/** appview/stamp.php: tarjeta de sellos del socio (solo lectura) */
class StampController extends Controller
{
    use RendersAppview;

    /**
     * Sellos del socio sobre la configuración de la tienda 1 (stamp_max, 5 por fila, hasta 10 filas), con el
     * dibujo del socio o el patrón por defecto, y la fecha de caducidad si admin_master.stamp_shikkou_sousa = 1.
     */
    public function show(Request $request, MobileTheme $theme): Response
    {
        $in = MobileTheme::input($request, fn (string $v) => $v);
        $customerCd = (int) ($in['customer_cd'] ?? 0);
        if ($customerCd == 0) {
            return Out::text('');
        }

        $config = DB::table('tb_stamp_config')->where('tb_shop_cd', 1)->first();
        $customer = DB::table('tb_customer')->where('cd', $customerCd)->first();

        $picDefault = null;
        if ($customer) {
            $picDefault = (string) $customer->tb_stamp_pattern_cd !== ''
                ? (string) $customer->tb_stamp_pattern_cd
                : DB::table('tb_stamp_pattern')->where('pic_default', '1')->value('cd');
        }
        $master = DB::table('admin_master')->where('cd', 1)->first(['stamp_shikkou_sousa']);

        return $this->page('stamp', $request, [
            'emoji' => $theme->emoji(...),
            'tb_stamp_config_array' => self::row($config, ['stamp_max']),
            'customer_array' => self::row($customer, ['stamp_count', 'tb_stamp_pattern_cd', 'stamp_yukoukigen']),
            'pic_default_no' => $picDefault === null ? null : (string) $picDefault,
            'yukoukigen_flg_max' => $master ? 1 : 0,
            'yukoukigen_flg_array' => self::row($master, ['stamp_shikkou_sousa']),
        ]);
    }
}
