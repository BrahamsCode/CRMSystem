<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Http\Controllers\Controller;
use App\Models\Customers\Customer;
use App\Models\Staff;
use App\Services\Customers\VisitRecordService;
use App\Services\Customers\VisitRecordValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * 来店履歴の追加・修正 de la pestaña 9 de la ficha (`system/category1/inc/kanren_record_new.php`,
 * `kanren_record_register.php`, `kanren_del_record.php`) sobre `VisitRecordService`, y las URL que llaman
 * sus JS: `inc/ajax_shop_point_config.php` e `inc/ajax_shop_stamp_config.php`.
 * ?wintype=no_headers = ventana emergente (versión `inc/` del legacy, sin menú); si no, con menú (`customer/`).
 */
class VisitRecordController extends Controller
{
    /** Formulario (POST de 「カルテを追加」/「カルテを修正」 de la pestaña 9, act=new|up) */
    public function create(Request $request, VisitRecordService $records): View|Response
    {
        $customer = $this->customer($request);
        $config = $records->config();
        if ($records->categoryCd($config) === null) {
            return response(''); // el legacy hacía exit sin categoría configurada
        }

        $data = ['selectedShopCd' => null, 'raiten' => ['', '', '', '', ''], 'logArray' => [], 'existLogData' => false, 'values' => []];
        $featureCd = (int) $request->input('related_features_cd');
        if ($featureCd > 0) {
            $visit = DB::table('tb_raiten_rireki')->where('cd', $featureCd)->first();
            if ($visit) {
                $data['selectedShopCd'] = $visit->shop_cd;
                if (is_numeric($visit->raiten_time)) {
                    $t = Carbon::createFromTimestamp((int) $visit->raiten_time, 'Asia/Tokyo');
                    $data['raiten'] = [$t->format('Y'), $t->format('m'), $t->format('d'), $t->format('H'), $t->format('i')];
                }
                $log = $this->relatedLog($config, $visit);
                $data['logArray'] = $log ?? [];
                $data['existLogData'] = $log !== null;
                $data['values'] = $records->fieldValues($customer, $records->fields((int) $records->categoryCd($config)), (string) $visit->tb_customer_sub_inner_group);
            }
        }

        return $this->form($request, $records, $customer, $data, []);
    }

    /** 「この内容で登録する」 (kanren_record_new.php con registerBtn → kanren_record_register.php) */
    public function store(Request $request, VisitRecordService $records): View|Response
    {
        $customer = $this->customer($request);
        $input = $request->except(['_token']);
        if (! $this->staff($request)->isMaster()) {
            // Sin master, la tienda es siempre la de la sesión (el legacy la ponía en un campo oculto)
            $input['selected_shop_cd'] = (string) $this->sessionShopCd($request);
        }

        // El formulario siempre lo envía (time() al pintarlo); sin él el legacy respondía así
        $verify = (string) $request->input('add_record_verify', '');
        if ($verify === '') {
            return response('不正アクセス！！');
        }

        $visitCd = null;
        // ダブルクリック防止: el mismo add_record_verify no se registra dos veces
        if ($request->session()->get('add_record_verify') !== $verify) {
            try {
                $visitCd = $records->save($customer, $input, $this->staff($request));
            } catch (VisitRecordValidationException $e) {
                return $this->invalid($request, $records, $customer, $e);
            }
            $request->session()->put('add_record_verify', $verify);
        }

        return $this->processing($request, 'register', $customer, $visitCd);
    }

    /** 「カルテを削除」 (kanren_del_record.php) */
    public function destroy(Request $request, VisitRecordService $records): View
    {
        $customer = $this->customer($request);
        $records->delete($customer, (int) $request->input('related_features_cd'), $this->staff($request));

        return $this->processing($request, 'delete', $customer, null);
    }

    /** inc/ajax_shop_point_config.php */
    public function shopPointConfig(Request $request, VisitRecordService $records): JsonResponse
    {
        return response()->json($records->shopPointConfig((int) $request->input('shop_cd')), 200, [], JSON_UNESCAPED_UNICODE);
    }

    /** inc/ajax_shop_stamp_config.php: datetime = 'Y,m,d,H,i' (fecha no válida = ahora) */
    public function shopStampConfig(Request $request, VisitRecordService $records): JsonResponse
    {
        $parts = array_pad(explode(',', (string) $request->input('datetime', '')), 5, '');
        $at = checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])
            ? Carbon::create((int) $parts[0], (int) $parts[1], (int) $parts[2], min(23, max(0, (int) $parts[3])), min(59, max(0, (int) $parts[4])), 0, 'Asia/Tokyo')
            : null;

        return response()->json($records->shopStampConfig((int) $request->input('shop_cd'), $at), 200, [], JSON_UNESCAPED_UNICODE);
    }

    // ---------------------------------------------------------------- auxiliares

    /** Vuelve al formulario con lo enviado y los errores (como el legacy al no pasar las comprobaciones) */
    private function invalid(Request $request, VisitRecordService $records, Customer $customer, VisitRecordValidationException $e): View
    {
        $input = $request->except(['_token']);

        return $this->form($request, $records, $customer, [
            'selectedShopCd' => $input['selected_shop_cd'] ?? null,
            'raiten' => [$input['raiten_y'] ?? '', $input['raiten_m'] ?? '', $input['raiten_d'] ?? '', $input['raiten_h'] ?? '', $input['raiten_i'] ?? ''],
            'logArray' => array_intersect_key($input, array_flip(['shiharai_houhou', 'kaikei', 'shouhi_point', 'sounyu_point', 'sounyu_point_add_remove_flg'])),
            'existLogData' => false,
            'values' => $input,
        ], $e->errors());
    }

    private function form(Request $request, VisitRecordService $records, Customer $customer, array $data, array $errors): View
    {
        $config = $records->config();
        $cateCd = (int) $records->categoryCd($config);
        $staff = $this->staff($request);
        $category = DB::table('tb_search_cate')
            ->where('category', 'category1')->whereRaw("COALESCE(disp_flg, '') <> '9'")
            ->where('hyouji_flg', '1')->where('record_type', '1')->where('cd', $cateCd)
            ->first();
        $shops = DB::table('tb_shop')->where('disp_flg', '1')->orderBy('cd')->pluck('kaishamei', 'cd')->all();
        $point = DB::table('tb_customer_point')->where('tb_customer_cd', $customer->cd)->value('service_point');

        return view('admin.clientes.visit-records.create', $data + [
            'popup' => $request->input('wintype') === 'no_headers',
            'customer' => $customer,
            'packType' => (string) ($config[1] ?? ''),
            'actualUnits' => (string) ($config[1] ?? '') === '2' ? (int) $customer->stamp_count : (int) $point,
            'isMaster' => $staff->isMaster(),
            'shops' => $shops,
            'sessionShopCd' => $this->sessionShopCd($request),
            'category' => $category ? (array) $category : null,
            'fields' => $category ? $records->fields($cateCd) : [],
            'fieldErrors' => $errors,
            'hidden' => [
                'add_record' => (string) $request->input('add_record', ''),
                'kaiin_cd' => (string) $customer->cd,
                'tab' => (string) $request->input('tab', ''),
                'act' => (string) $request->input('act', ''),
                'related_features_cd' => (string) $request->input('related_features_cd', ''),
                'raiten_cd' => (string) $request->input('raiten_cd', ''),
                'page' => (string) $request->input('page', ''),
            ],
            'verifyToken' => (string) time(),
        ]);
    }

    /** Movimiento de puntos (tipo 1) o de sellos (tipo 2) enlazado a la visita, con el nombre del personal */
    private function relatedLog(array $config, object $visit): ?array
    {
        [$table, $cd] = match ((string) ($config[1] ?? '')) {
            '1' => ['tb_point_log', $visit->tb_point_log_cd],
            '2' => ['tb_stamp_log', $visit->tb_stamp_log_cd],
            default => [null, null],
        };
        if ($table === null || $cd === null) {
            return null;
        }

        $log = DB::table($table . ' as tb1')->join('tb_staff as tb2', 'tb1.tb_staff_cd', '=', 'tb2.cd')
            ->where('tb1.cd', $cd)->select('tb1.*', 'tb2.name as staff_name')->first();

        return $log ? (array) $log : null;
    }

    /** Pantalla 「登録中です。」/「削除中です。」 que vuelve a la ficha (meta Refresh de 1 s) */
    private function processing(Request $request, string $mode, Customer $customer, ?int $visitCd): View
    {
        $popup = $request->input('wintype') === 'no_headers';
        $params = [$customer->cd, 'kaiin_cd' => $customer->cd, 'tab' => (string) $request->input('tab', '')];
        if ($mode === 'register') {
            $params += ['page' => (string) $request->input('page', ''), 'related_features_cd' => $visitCd];
        }
        if ($popup) {
            $params['wintype'] = 'no_headers';
        }

        return view('admin.clientes.visit-records.processing', [
            'mode' => $mode,
            'popup' => $popup,
            'detailUrl' => route('admin.clientes.show', $params),
        ]);
    }

    private function customer(Request $request): Customer
    {
        $customer = Customer::withDeleted()->find((int) $request->input('kaiin_cd'));
        abort_if($customer === null, 404);

        return $customer;
    }

    private function staff(Request $request): Staff
    {
        return $request->user('admin');
    }

    private function sessionShopCd(Request $request): int
    {
        return (int) (session('shop_cd') ?? $this->staff($request)->shop_cd);
    }
}
