<?php

namespace App\Http\Controllers\Publico\Integraciones;

use App\Http\Controllers\Controller;
use App\Models\Integraciones\UrlTransferData;
use App\Services\Integraciones\ToolApi\ToolApiOutput as Out;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * Utilitarios públicos del legacy (Doc/diseno/operaciones-integraciones.md §1.10):
 * - /version/crm_version.php: el archivo está vacío en crm2-php8 y en development (200 sin cuerpo); el análisis 07
 *   hablaba de «2.704» (anotado).
 * - /ptag/<ID>: enlace de seguimiento → 302 a tb_url_list.url y registro en url_transfer_data.
 * - /air/air_info_out.php: última インフォメーション visible para un login_id (cliente Adobe AIR; confirmar uso con Japón).
 */
class UtilityController extends Controller
{
    public function crmVersion(): Response
    {
        return Out::text('');
    }

    /**
     * ptag/index.php: el ID es todo lo que sigue a '/ptag/' en la URL (con la query, como el legacy). El socio sale
     * de la cookie UID = md5(login_id/login_pass) que deja la マイページ (sin cifrar: se lee de la cabecera Cookie).
     */
    public function ptag(Request $request): Response
    {
        $parts = explode('/ptag/', (string) $request->server('REQUEST_URI'), 2);
        $id = rawurldecode($parts[1] ?? '');
        if ($id === '') {
            return Out::text('GETデータがありません');
        }

        $url = DB::table('tb_url_list')->where('id', $id)->where('disp_flg', '1')->where('hyouji_flg', '1')->value('url');
        if ($url === null) {
            return Out::text('無効なIDです。');
        }

        $uid = $this->rawCookie($request, 'UID');
        $customerCd = $uid === '' ? null : DB::table('tb_customer')
            ->whereRaw("md5(login_id||'/'||login_pass) = ?", [$uid])->where('disp_flg', '1')->where('touroku_kubun', '2')->value('cd');

        UrlTransferData::record($id, $customerCd === null ? null : (int) $customerCd);

        return Out::text('', 302, ['Location' => htmlspecialchars_decode((string) $url)]);
    }

    /** air_info_out.php?login_id=: "update=<Y年n月j日 H:i>&generation=<comment>" de la última información visible */
    public function airInfo(Request $request): Response
    {
        $staff = DB::table('tb_staff')->where('login_id', (string) (is_scalar($request->input('login_id')) ? $request->input('login_id') : ''))->first();

        $query = DB::table('tb_information')->where('update', '<>', '')->where('disp_flg', '<>', '9')->orderByDesc('update')->limit(1);
        if (($staff->login_id ?? null) !== 'master') {
            // Mismas condiciones que el legacy, incluido el cruce belongpost/employmentsystem de sus OR
            $or = function (string $column, ?string $value, string $operator, string $fallbackColumn, string $fallback) use ($query) {
                $query->where(function ($q) use ($column, $value, $operator, $fallbackColumn, $fallback) {
                    if ((string) $value !== '') {
                        $operator === '='
                            ? $q->where($column, $value)
                            : $q->where($column, 'like', '%'.$value.'%');
                    }
                    $fallback === 'null' ? $q->orWhereNull($fallbackColumn) : $q->orWhere($fallbackColumn, '');
                });
            };
            $or('shop_cd', isset($staff->shop_cd) ? (string) $staff->shop_cd : null, '=', 'shop_cd', 'null');
            $or('belongpost', $staff->belongpost ?? null, 'like', 'employmentsystem', '');
            $or('employmentsystem', $staff->employmentsystem ?? null, 'like', 'belongpost', '');
            $or('officialposition', $staff->officialposition ?? null, 'like', 'officialposition', '');
        }

        $info = $query->first();
        if (! $info) {
            return Out::text('');
        }
        $update = is_numeric($info->update) ? date('Y年n月j日 H:i', (int) $info->update) : '';

        return Out::text('update='.$update.'&generation='.$info->comment);
    }

    /** Valor de una cookie tal como la envió el navegador (sin el descifrado de Laravel) */
    private function rawCookie(Request $request, string $name): string
    {
        foreach (explode(';', (string) $request->headers->get('cookie')) as $pair) {
            [$key, $value] = array_pad(explode('=', trim($pair), 2), 2, '');
            if ($key === $name) {
                return urldecode($value);
            }
        }

        return '';
    }
}
