<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Http\Controllers\Controller;
use App\Models\Customers\Customer;
use App\Models\Staff;
use App\Services\Customers\CustomerFormService;
use App\Services\Customers\CustomRecordService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * 追加情報 de varios registros de la pestaña de categoría personalizada de la ficha (botones 「…を追加する」,
 * 「…を修正する」 y 「…を削除する」 de tabs/custom): `inc/add_record_new.php` (formulario), `add_record_confirm.php`
 * (errores o confirmación), `add_record_register.php` y `del_record.php` (pantalla 「登録中です。」/「削除中です。」).
 * POST con kaiin_cd, tab, act (new|up), search_cate_cd, fukusu_group_cd, page como el legacy.
 * ?wintype=no_headers = ventana emergente (versión `inc/` del legacy, sin menú); si no, con menú (`customer/`).
 */
class CustomRecordController extends Controller
{
    /** Claves del POST que el legacy reenviaba en campos ocultos y que no son valores de campos */
    private const PASS_KEYS = ['kaiin_cd', 'tab', 'act', 'search_cate_cd', 'fukusu_group_cd', 'page', 'add_record', 'wintype'];

    /** add_record_new.php: formulario (act=up: valores del registro fukusu_group_cd; 書き直す: lo enviado) */
    public function create(Request $request, CustomRecordService $records, CustomerFormService $form): View
    {
        [$customer, $category, $fields] = $this->context($request, $records);

        $values = [];
        if ($request->input('act') !== 'new') {
            $values = $records->values($customer, $fields, (string) $request->input('fukusu_group_cd', ''));
        }
        if ($request->boolean('rewrite')) {
            // 書き直す desde la confirmación: lo enviado (el legacy volvía a leer la BD y perdía lo editado)
            $values = $this->fieldInput($request);
        }

        return $this->view($request, $form, 'input', $customer, $category, $fields, $values, []);
    }

    /** add_record_confirm.php: con errores vuelve al formulario (con comentario y mensaje); si no, confirmación */
    public function confirm(Request $request, CustomRecordService $records, CustomerFormService $form): View
    {
        [$customer, $category, $fields] = $this->context($request, $records);
        $values = $this->fieldInput($request);
        $errors = $records->validate($fields, $request->all());

        return $this->view($request, $form, $errors ? 'error' : 'confirm', $customer, $category, $fields, $values, $errors);
    }

    /** add_record_register.php: guarda el registro y vuelve a la ficha (meta Refresh de 1 s) */
    public function store(Request $request, CustomRecordService $records, CustomerFormService $form): View|Response
    {
        [$customer, $category, $fields] = $this->context($request, $records);

        $verify = (string) $request->input('add_record_verify', '');
        if ($verify === '') {
            return response('不正アクセス！！');
        }

        $group = (string) $request->input('fukusu_group_cd', '');
        // ダブルクリック防止: el mismo add_record_verify no se registra dos veces
        if ($request->session()->get('add_record_verify') !== $verify) {
            // El legacy no volvía a validar al registrar; se valida para no guardar datos que el confirm rechaza
            if ($records->validate($fields, $request->all()) !== []) {
                return $this->confirm($request, $records, $form);
            }
            $group = $records->save($customer, $category, $fields, $request->except('_token'), $group, $this->sessionShopCd($request));
            $request->session()->put('add_record_verify', $verify);
        }

        return $this->processing($request, 'register', $customer, [
            'tab' => (string) $request->input('tab', ''),
            'page' => (string) $request->input('page', ''),
            'fukusu_group_cd' => $group,
            'search_cate_cd' => (string) $category['cd'],
        ], 1);
    }

    /** del_record.php: borra el registro y vuelve a la ficha (meta Refresh de 2 s) */
    public function destroy(Request $request, CustomRecordService $records): View
    {
        $customer = $this->customer($request);
        $cateCd = (int) $request->input('search_cate_cd');
        $records->delete($customer, $cateCd, (string) $request->input('fukusu_group_cd', ''));

        return $this->processing($request, 'delete', $customer, [
            'tab' => (string) $request->input('tab', ''),
            'search_cate_cd' => (string) $cateCd,
        ], 2);
    }

    // ---------------------------------------------------------------- auxiliares

    /** Cliente, categoría y campos del POST (404 si no existen, el legacy pintaba la pantalla vacía) */
    private function context(Request $request, CustomRecordService $records): array
    {
        $customer = $this->customer($request);
        $category = $records->category((int) $request->input('search_cate_cd'));
        abort_if($category === null, 404);

        return [$customer, $category, $records->fields((int) $category['cd'])];
    }

    /** Valores de los campos enviados (form{cd}…, yoyaku_menu_cd) */
    private function fieldInput(Request $request): array
    {
        return array_filter(
            $request->except('_token'),
            fn ($key) => str_starts_with((string) $key, 'form') || $key === 'yoyaku_menu_cd',
            ARRAY_FILTER_USE_KEY
        );
    }

    /** Pantalla del formulario / confirmación (custom-records/form) */
    private function view(Request $request, CustomerFormService $form, string $mode, Customer $customer, array $category, array $fields, array $values, array $errors): View
    {
        $staff = $this->staff($request);
        $passed = array_intersect_key($request->except('_token'), array_flip(self::PASS_KEYS));
        $passed['kaiin_cd'] = (string) $customer->cd;

        return view('admin.clientes.custom-records.form', [
            'popup' => $request->input('wintype') === 'no_headers',
            'mode' => $mode,
            'customer' => $customer,
            'category' => $category,
            'fields' => $fields,
            'values' => $values,
            'fieldErrors' => $errors,
            'menuItems' => $form->menuItems($staff->isMaster() ? null : $this->sessionShopCd($request)),
            'hidden' => $passed,
            'verifyToken' => (string) time(),
        ]);
    }

    /** Pantalla 「登録中です。」/「削除中です。」 de C3 que vuelve a la ficha */
    private function processing(Request $request, string $mode, Customer $customer, array $params, int $refresh): View
    {
        $popup = $request->input('wintype') === 'no_headers';
        $params = [$customer->cd, 'kaiin_cd' => $customer->cd] + $params;
        if ($popup) {
            $params['wintype'] = 'no_headers';
        }

        return view('admin.clientes.visit-records.processing', [
            'mode' => $mode,
            'popup' => $popup,
            'refresh' => $refresh,
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
