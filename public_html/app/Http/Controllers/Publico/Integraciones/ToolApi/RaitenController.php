<?php

namespace App\Http\Controllers\Publico\Integraciones\ToolApi;

use App\Http\Controllers\Controller;
use App\Models\Customers\Customer;
use App\Services\Customers\VisitRecordService;
use App\Services\Customers\VisitRecordValidationException;
use App\Services\Customers\VisitService;
use App\Services\Integraciones\ToolApi\RelatedFeatures;
use App\Services\Integraciones\ToolApi\ToolApiOutput as Out;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * system/tool/api/category1: getRaitenList.php, getRaitenDetail.php, addRaitenRecord.php y setRaitenDetail.php
 * (visitas). setRaitenDetail delega en VisitRecordService de S2 (kanren_record_register).
 */
class RaitenController extends Controller
{
    use PagesToolApi;

    /**
     * setRaitenDetail.php: alta o corrección de una visita con puntos/sellos y 関連情報 (VisitRecordService de S2,
     * mismas claves que el legacy). Errores: { "status": "error", "result": { "<campo>" : "<mensaje>" } } (sin ※ ni <br>;
     * la variante JSONP, rota en el legacy por precedencia de operadores, sale con el formato correcto). Un mismo
     * add_record_verify repetido en la sesión no vuelve a guardar y responde SUCCESS sin cd, como el legacy.
     */
    public function update(Request $request, RelatedFeatures $related, VisitRecordService $records): Response
    {
        $errors = [];
        $customer = null;
        $customerCd = Out::form2Db($request->input('kaiin_cd'));
        if ($customerCd === '') {
            $errors['customer'] = '顧客CD番号が空です。';
        } elseif (($related->config()[2] ?? '') === '') {
            $errors['relatedFeaturesConfig'] = 'カルテ使用設定がされていません。';
        } elseif (! $customer = Customer::withDeleted()->where('cd', (int) $customerCd)->first()) {
            // El legacy seguía con el socio vacío; aquí se responde el mismo error que sin kaiin_cd
            $errors['customer'] = '顧客CD番号が空です。';
        }

        $verify = Out::form2Db($request->input('add_record_verify'));
        $data = $request->except(['key', 'data_type', 'callback']);
        $cd = '';
        if (! $errors) {
            // Como el legacy: primero los errores de validación; después add_record_verify vacío o repetido
            $save = $verify !== '' && $request->session()->get('tool_api.add_record_verify') !== $verify;
            try {
                if ($save) {
                    $cd = (string) $records->save($customer, $data);
                    $request->session()->put('tool_api.add_record_verify', $verify);
                } else {
                    $this->validateOnly($records, $customer, $data);
                }
            } catch (VisitRecordValidationException $e) {
                $errors = $e->errors();
            }
            if (! $errors && $verify === '') {
                return Out::text('{ "status": "error", "result": { "add_record_verify": "多重登録エラーです。" } }');
            }
        }

        if ($errors) {
            $pairs = [];
            foreach ($errors as $field => $message) {
                $pairs[] = '"'.$field.'" : "'.str_replace(['※', '<br>'], '', (string) $message).'"';
            }

            return Out::jsonp($request, '{ "status": "error", "result": { '.implode(',', $pairs).' } }');
        }

        return Out::jsonp($request, '{ "status": "SUCCESS", "result": "'.$cd.'" }');
    }

    /**
     * Valida como save() sin guardar nada: VisitRecordService no expone la validación, así que se guarda dentro
     * de una transacción que se deshace siempre (save() no tiene efectos fuera de la BD).
     *
     * @throws VisitRecordValidationException
     */
    private function validateOnly(VisitRecordService $records, Customer $customer, array $data): void
    {
        $rollback = new \RuntimeException('validateOnly');
        try {
            DB::transaction(function () use ($records, $customer, $data, $rollback) {
                $records->save($customer, $data);
                throw $rollback;
            });
        } catch (\RuntimeException $e) {
            if ($e !== $rollback) {
                throw $e;
            }
        }
    }

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
