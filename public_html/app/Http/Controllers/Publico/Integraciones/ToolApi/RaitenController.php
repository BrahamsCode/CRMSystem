<?php

namespace App\Http\Controllers\Publico\Integraciones\ToolApi;

use App\Http\Controllers\Controller;
use App\Models\Customers\Customer;
use App\Services\Customers\VisitService;
use App\Services\Integraciones\ToolApi\RelatedFeatures;
use App\Services\Integraciones\ToolApi\ToolApiOutput as Out;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * system/tool/api/category1: getRaitenList.php, getRaitenDetail.php y addRaitenRecord.php (visitas).
 * setRaitenDetail.php (alta/edición manual con puntos, sellos y 関連情報) espera el servicio de S2 de
 * kanren_record_register.
 */
class RaitenController extends Controller
{
    use PagesToolApi;

    /** Terminal con la que el legacy marcaba las visitas de tool/api (raitenFunc(..., -2, ...)) */
    public const TERMINAL_CD = -2;

    /** getRaitenList.php: visitas no borradas, por socio y rango de fechas (strtotime), de la más reciente */
    public function index(Request $request): Response
    {
        $where = ["coalesce(tb1.disp_flg,'')<>'9'"];
        $bindings = [];
        if (($cd = Out::form2Db($request->input('cd'))) !== '') {
            $where[] = 'tb1.user_cd = ?';
            $bindings[] = (int) $cd;
        }
        foreach (['s_date' => '>=', 'e_date' => '<='] as $param => $operator) {
            if (($date = Out::form2Db($request->input($param))) === '') {
                continue;
            }
            $time = strtotime($date);
            if ($time === false) {
                // El legacy concatenaba false (''): consulta inválida → NO_DATA
                return $this->listResponse($request, null);
            }
            $where[] = "tb1.raiten_time::integer {$operator} ?";
            $bindings[] = $time;
        }

        $rows = Out::rows('SELECT '.Out::textColumns('tb_raiten_rireki', 'tb1').', tb2.kaishamei::text AS kaishamei'
            .' FROM tb_raiten_rireki AS tb1 INNER JOIN tb_shop AS tb2 ON tb1.shop_cd = tb2.cd'
            .' WHERE '.implode(' AND ', $where).' ORDER BY tb1.raiten_time DESC'.$this->page($request), $bindings);

        return $this->listResponse($request, $rows);
    }

    /**
     * getRaitenDetail.php: la visita, su registro de puntos o sellos (según 関連機能, con el nombre del
     * personal) y los campos y datos personalizados de su grupo (rawurlencode). Sin categoría de 関連機能
     * activa: NO_DATA.
     */
    public function show(Request $request, RelatedFeatures $related): Response
    {
        $cd = Out::form2Db($request->input('cd'));
        if ($cd === '') {
            return Out::empty();
        }

        $visit = Out::rows('SELECT '.Out::textColumns('tb_raiten_rireki', 'tb1').', tb2.kaishamei::text AS kaishamei'
            .' FROM tb_raiten_rireki AS tb1 INNER JOIN tb_shop AS tb2 ON tb1.shop_cd = tb2.cd'
            ." WHERE tb1.cd = ? AND coalesce(tb1.disp_flg,'')<>'9'", [(int) $cd])[0] ?? null;
        $category = $related->category();
        if (! $visit || ($category['disp_flg'] ?? null) !== '1') {
            return Out::envelope($request, 'NO_DATA', 0, '', list: false);
        }

        $data = '"raiten_data":'.Out::object($visit);

        [$logType, $logTable, $logCd] = match ($related->logType()) {
            '1' => ['ポイント', 'tb_point_log', $visit['tb_point_log_cd']],
            '2' => ['スタンプ', 'tb_stamp_log', $visit['tb_stamp_log_cd']],
            default => [null, null, ''],
        };
        if ($logTable && $logCd !== '') {
            $log = Out::rows('SELECT '.Out::textColumns($logTable, 'tb1').', tb2.name::text AS staff_name'
                ." FROM {$logTable} tb1 INNER JOIN tb_staff tb2 ON tb1.tb_staff_cd = tb2.cd WHERE tb1.cd = ?", [(int) $logCd])[0] ?? null;
            if ($log) {
                $data .= ', "log_data":{ "log_type":"'.$logType.'", '.implode(',', Out::pairs($log)).' }';
            }
        }

        $forms = [];
        $subs = [];
        foreach ($related->forms((int) $category['cd']) as $form) {
            $forms[] = Out::object($form, encode: true);
            $sub = $visit['user_cd'] === '' ? null : (Out::rows('SELECT '.Out::textColumns('tb_customer_sub', 'tb_customer_sub').' FROM tb_customer_sub'
                ." WHERE tb_search_form_cd = ? AND tb_customer_cd = ? AND disp_flg <> '9' AND category_inner_group = ?",
                [(int) $form['cd'], (int) $visit['user_cd'], $visit['tb_customer_sub_inner_group']])[0] ?? null);
            if ($sub) {
                $subs[] = Out::object($sub, encode: true);
            }
        }
        if ($forms) {
            $data .= ', "customer_sub_form":[ '.implode(',', $forms).' ]';
        }
        if ($subs) {
            $data .= ', "customer_sub_data":[ '.implode(',', $subs).' ]';
        }

        return Out::envelope($request, 'SUCCESS', 1, $data, list: false);
    }

    /**
     * addRaitenRecord.php: registra la visita de un socio activo (cUtility::raitenFunc → VisitService de S2)
     * en `shop` (o su tienda), con `staff` y raiten_log_stamp_flg; terminal -2.
     */
    public function store(Request $request, VisitService $visits): Response
    {
        $customerCd = Out::form2Db($request->input('customer'));
        if ($customerCd === '') {
            return Out::empty();
        }

        $customer = preg_match('/^-?[0-9]+$/', $customerCd)
            ? Customer::query()->where('cd', $customerCd)->where('touroku_kubun', '2')->where('disp_flg', '1')
                // INNER JOIN tb_shop del legacy (también tiendas borradas)
                ->whereExists(fn ($q) => $q->from('tb_shop')->whereColumn('tb_shop.cd', 'tb_customer.tb_shop_cd'))->first()
            : null;

        $status = 'NO_DATA';
        if ($customer) {
            $shop = Out::form2Db($request->input('shop'));
            $staff = Out::form2Db($request->input('staff'));
            $visits->register($customer, $shop !== '' ? (int) $shop : (int) $customer->tb_shop_cd, VisitService::ORIGIN_TERMINAL,
                self::TERMINAL_CD, $staff !== '' ? (int) $staff : null, Out::form2Db($request->input('raiten_log_stamp_flg')));
            $status = 'SUCCESS';
        }

        return Out::jsonp($request, '{ "status":"'.$status.'" }');
    }
}
