<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Services\Customers\CustomerCsvExporter;
use App\Services\Customers\CustomerListService;
use App\Services\Customers\CustomerSearchForm;
use App\Models\Customers\Customer;
use App\Services\Customers\CustomerFormService;
use App\Services\Customers\CustomerRegistrationService;
use App\Services\Nucleo\LegacyUi;
use App\Services\Customers\CustomerDeletionService;
use App\Services\Customers\CustomerDetailData;
use App\Services\Customers\PosGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 顧客管理 (category1/customer).
 */
class CustomerController extends Controller
{
    // --- C1-a: 顧客一覧・検索 (customer_list.php) y CSV (csv_download.php, csv_download_ex02.php) ---

    /** Parámetros del formulario que no son filtros */
    private const NON_FILTER_KEYS = ['_token', 'act', 'enter', 'search_uniqid', 'se_flg', 'kensu', 'kensu_act', 'page', 'item_max_no', 'post_wherestr'];

    /**
     * customer_list.php sin act=up: formulario vacío o, con se_flg=1 + search_uniqid (paginación,
     * 表示件数), la búsqueda guardada. Con act=up (enlaces del legacy, p. ej. ctiWindow.php) busca.
     */
    public function index(Request $request, CustomerListService $list, CustomerSearchForm $form): View
    {
        if ($request->input('act') === 'up') {
            return $this->search($request, $list, $form);
        }

        $staff = $this->staff($request);
        if ($request->input('se_flg') == '1') {
            $uniqid = (string) $request->input('search_uniqid');
            $description = $list->description($uniqid);
            if ($description === null) {
                // Sin búsqueda en sesión: todos los socios activos (el legacy consultaba sin WHERE)
                $uniqid = $list->store([], $staff);
                $description = $list->description($uniqid);
            }

            return $this->render($request, $list, $form, $staff, $uniqid, $description, (array) ($description['params'] ?? []));
        }

        return $this->render($request, $list, $form, $staff, (string) $request->input('search_uniqid', ''), null, []);
    }

    /** customer_list.php con act=up: guarda la búsqueda (search_set2.php) con un uniqid nuevo y muestra la página 1 */
    public function search(Request $request, CustomerListService $list, CustomerSearchForm $form): View
    {
        $staff = $this->staff($request);
        $uniqid = $list->store($request->all(), $staff, $request->input('search_uniqid'));

        return $this->render($request, $list, $form, $staff, $uniqid, $list->description($uniqid), $request->except(self::NON_FILTER_KEYS));
    }

    /**
     * この条件でCSV出力する (format=standard, csv_download.php) y DM用CSV出力 (format=ex02,
     * csv_download_ex02.php): con la búsqueda guardada (search_uniqid) o, si no está, con los filtros de la URL.
     */
    public function export(Request $request, CustomerListService $list, CustomerCsvExporter $csv, ?string $format = null): StreamedResponse
    {
        $format ??= (string) $request->input('format', 'standard');

        // C5: plantilla de la CSVインポート (csv_download_template.php), sin datos: mismo permiso que la importación
        if ($format === 'template') {
            abort_unless(Gate::allows('nucleo.maintenance'), 403);

            return $csv->template();
        }

        // CSV出力の権限 (myKengen[1002][1] o master)
        abort_unless(Gate::allows('kengen', [1002, 1]), 403);

        abort_unless(in_array($format, CustomerCsvExporter::FORMATS, true), 404);

        $description = $list->description((string) $request->input('search_uniqid'));
        $params = $request->except(array_merge(self::NON_FILTER_KEYS, ['format', 'titleflg', 'flg', 'exp']));

        return $csv->download($format, $list->query($this->staff($request), $description, $params));
    }

    /** Pinta admin/clientes/index con el buscador y, si hay búsqueda ($description), el resultado */
    private function render(Request $request, CustomerListService $list, CustomerSearchForm $form, Staff $staff, string $uniqid, ?array $description, array $filters): View
    {
        $kensu = (int) $request->input('kensu', 10);
        $kensu = $kensu > 0 ? $kensu : 10;
        $page = max(1, (int) $request->input('page', 1));

        $formData = $form->viewData($staff, 'category1', $filters, $this->hiddenSearchMenus($request));

        $data = $formData + [
            'addScripts' => null, // ページカスタマイズ (inc/head.php): pendiente en el núcleo
            'searched' => $description !== null,
            'searchUniqid' => $uniqid,
            'kensu' => $kensu,
            'page' => $page,
            'searchQuery' => [],
            'itemMaxNo' => count($formData['searchItems']),
            'total' => 0,
            'carrierCounts' => ['pc' => 0, 'docomo' => 0, 'au' => 0, 'softbank' => 0, 'unknown' => 0],
            'customers' => [],
            'showRaitenColumns' => (bool) $formData['raitenHistoryEnabled'],
            'aprintCsvEnabled' => false,
            'lineLinkedCustomerCount' => 0,
            'domainSmatopiId' => null,
            'isTablet' => $this->isTablet($request),
        ];

        if ($description !== null) {
            $query = $list->query($staff, $description);
            $counts = $list->counts($query);
            $data['total'] = $counts['total'];
            $data['carrierCounts'] = array_diff_key($counts, ['total' => 0]);
            $data['customers'] = $list->page($query, $staff, $page, $kensu, $data['showRaitenColumns']);
            $data['searchQuery'] = (array) ($description['params'] ?? []);
            $data['aprintCsvEnabled'] = $list->aprintCsvEnabled();
            $data['domainSmatopiId'] = $list->domainSmatopiId();
            if ($data['domainSmatopiId'] !== null) {
                $data['lineLinkedCustomerCount'] = $list->lineLinkedCount($query);
            }
        }

        return view('admin.clientes.index', $data);
    }

    /** Menús plegados del buscador: cookie `hideSearchMenu` (la escribe el JS, sin cifrar) separada por comas */
    private function hiddenSearchMenus(Request $request): array
    {
        $raw = null;
        foreach (explode(';', (string) $request->headers->get('cookie')) as $pair) {
            [$name, $value] = array_pad(explode('=', trim($pair), 2), 2, '');
            if ($name === 'hideSearchMenu') {
                $raw = urldecode($value);
            }
        }

        return $raw === null || $raw === '' ? [] : explode(',', $raw);
    }

    /** $isTablet de kihon.php: cUserAgent DEVICE_NAME en ('CRMRW107', 'CRMH580VT_A') */
    private function isTablet(Request $request): bool
    {
        $agent = (string) $request->userAgent();

        return str_contains($agent, 'CRMRW107') || str_contains($agent, 'CRMH580VT_A');
    }

    private function staff(Request $request): Staff
    {
        return $request->user('admin');
    }

    // --- fin C1-a ---

    // --- C1-c: alta, edición y confirmación (inc/customer_new.php, customer_new_confirm.php, customer_new_register.php) ---

    /** customer_new.php sin kaiin_cd: alta. Con kaiin_cd (botón 修正 de la ficha del legacy) pasa a la edición */
    public function create(Request $request, CustomerFormService $form, CustomerRegistrationService $registration): View|RedirectResponse
    {
        if ($request->filled('kaiin_cd')) {
            return redirect()->route('admin.clientes.edit', (int) $request->input('kaiin_cd'));
        }
        abort_unless(Gate::allows('kengen', [1001, 2]), 403);

        $staff = $request->user('admin');
        $tel = $request->query('tel');

        return $this->customerFormView($form, $staff, null, $form->newData($staff, is_string($tel) ? $tel : null, $registration), []);
    }

    /** customer_new.php?kaiin_cd=: edición con los datos guardados */
    public function edit(Request $request, Customer $customer, CustomerFormService $form): View
    {
        $this->authorizeCustomerUpdate($request, $customer);

        return $this->customerFormView($form, $request->user('admin'), $customer, $form->editData($customer), $form->customValues($customer));
    }

    /** customer_new_confirm.php: con errores repinta el formulario; sin errores, la pantalla de confirmación */
    public function confirm(Request $request, CustomerFormService $form): View
    {
        $staff = $request->user('admin');
        $customer = $this->postedCustomer($request);
        $customer ? $this->authorizeCustomerUpdate($request, $customer) : abort_unless(Gate::allows('kengen', [1001, 2]), 403);

        $data = $form->normalize($request->all(), $staff);
        $check = $form->validate($data, $customer?->cd);
        if ($check->fails()) {
            return $this->customerFormView($form, $staff, $customer, $data, $data)->withErrors($form->errorBag($check));
        }

        $options = $form->viewOptions($staff);

        return view('admin.clientes.confirm', [
            'customer' => $customer,
            'data' => $data,
            'shops' => $options['shops'],
            'staffName' => ($data['tb_staff_cd'] ?? '') !== '' ? DB::table('tb_staff')->where('cd', (int) $data['tb_staff_cd'])->value('name') : null,
            'imeiEnabled' => $options['imeiEnabled'],
            'reminderEnabled' => $options['reminderEnabled'],
            'customerGroupName' => ($data['tb_customer_group_cd'] ?? '') !== '' ? DB::table('tb_customer_group')->where('cd', (int) $data['tb_customer_group_cd'])->value('name') : null,
            'raitenDoukiName' => ($data['tb_raiten_douki_master_cd'] ?? '') !== '' ? DB::table('tb_raiten_douki_master')->where('cd', (int) $data['tb_raiten_douki_master_cd'])->value('name') : null,
            'customCategories' => $options['customCategories'],
            'menuItems' => $options['menuItems'],
            'siteName' => app(LegacyUi::class)->siteName(),
        ]);
    }

    /** 書き直す (POST "modoru" a customer_new.php): el formulario con lo enviado */
    public function rewrite(Request $request, CustomerFormService $form): View
    {
        $customer = $this->postedCustomer($request);
        $data = $form->postedData(collect($request->all())->except(['_token', '_method', 'modoru'])->all());

        return $this->customerFormView($form, $request->user('admin'), $customer, $data, $data);
    }

    /** customer_new_register.php (act=new): alta y a la ficha (customer_detail.php?kaiin_cd=…&from=reg) */
    public function store(Request $request, CustomerFormService $form, CustomerRegistrationService $registration): View|RedirectResponse
    {
        abort_unless(Gate::allows('kengen', [1001, 2]), 403);
        $staff = $request->user('admin');

        // Los datos vuelven de campos ocultos: se normalizan y comprueban otra vez antes de guardar
        $data = $form->normalize($request->all(), $staff);
        $check = $form->validate($data, null);
        if ($check->fails()) {
            return $this->customerFormView($form, $staff, null, $data, $data)->withErrors($form->errorBag($check));
        }

        $customer = $registration->register($data, (int) $data['tb_shop_cd'], 'admin');

        return redirect()->to($this->customerDetailUrl($customer->cd));
    }

    /** customer_new_register.php (act=up): guarda la edición y vuelve a la ficha */
    public function update(Request $request, Customer $customer, CustomerFormService $form, CustomerRegistrationService $registration): View|RedirectResponse
    {
        $this->authorizeCustomerUpdate($request, $customer);
        $staff = $request->user('admin');

        $data = $form->normalize($request->all(), $staff);
        $check = $form->validate($data, $customer->cd);
        if ($check->fails()) {
            return $this->customerFormView($form, $staff, $customer, $data, $data)->withErrors($form->errorBag($check));
        }

        $registration->update($customer, $data);

        return redirect()->to($this->customerDetailUrl($customer->cd));
    }

    /** Pinta admin/clientes/form (alta, edición, 書き直す o errores del confirm) */
    private function customerFormView(CustomerFormService $form, Staff $staff, ?Customer $customer, array $data, array $customValues): View
    {
        return view('admin.clientes.form', $form->viewOptions($staff) + [
            'customer' => $customer,
            'data' => $data,
            'customValues' => $customValues,
            'siteName' => app(LegacyUi::class)->siteName(),
        ]);
    }

    /** Cliente de la edición según los campos ocultos act=up + customer_cd del formulario */
    private function postedCustomer(Request $request): ?Customer
    {
        if ($request->input('act') !== 'up' || ! $request->filled('customer_cd')) {
            return null;
        }

        return Customer::query()->findOrFail((int) $request->input('customer_cd'));
    }

    /** 修正: myKengen[1001][3] (o master); sin master, solo clientes de la tienda de la sesión */
    private function authorizeCustomerUpdate(Request $request, Customer $customer): void
    {
        abort_unless(Gate::allows('kengen', [1001, 3]), 403);
        $staff = $request->user('admin');
        abort_if(! $staff->isMaster() && (int) $customer->tb_shop_cd !== (int) (session('shop_cd') ?? $staff->shop_cd), 404);
    }

    /** Ficha tras guardar (el legacy esperaba 1 s en 「登録中です。」 y abría customer_detail.php?kaiin_cd=…&from=reg) */
    private function customerDetailUrl(int $cd): string
    {
        return Route::has('admin.clientes.show')
            ? route('admin.clientes.show', [$cd, 'from' => 'reg'])
            : url('system/category1/customer/customer_detail.php?kaiin_cd=' . $cd . '&from=reg');
    }

    /**
     * ajax_search_kaiin.php (search_kaiin.js, botón 会員検索 del 紹介者): socios activos que contienen cada palabra
     * en nombre, カナ, ID, teléfono, correo, nacimiento, 管理番号 o móvil; máximo 200, HTML del legacy.
     */
    public function searchMember(Request $request): View
    {
        $staff = $request->user('admin');
        $query = DB::table('tb_customer')->where('disp_flg', '1')->where('touroku_kubun', '2')
            ->when(! $staff->isMaster(), fn ($q) => $q->where('tb_shop_cd', (int) (session('shop_cd') ?? $staff->shop_cd)));

        $words = (string) $request->input('w', '');
        if ($words !== '') {
            foreach (explode(' ', str_replace('　', ' ', $words)) as $word) {
                $like = fn (string $value) => '%' . addcslashes($value, '%_\\') . '%';
                $query->where(function ($q) use ($word, $like) {
                    $q->where('name', 'like', $like($word))
                        ->orWhere('name_kana', 'like', $like(mb_convert_kana($word, 'KVC')))
                        ->orWhere('id', 'like', $like($word))
                        ->orWhere('tel', 'like', $like(mb_convert_kana($word, 'a')))
                        ->orWhere('email1', 'like', $like(mb_convert_kana($word, 'a')))
                        ->orWhere('kojin_birth', 'like', $like($word))
                        ->orWhere('kanri_id', 'like', $like($word))
                        ->orWhere('kojin_mobile', 'like', $like($word));
                });
            }
        }

        $total = (clone $query)->count();
        $members = $query->orderByRaw("coalesce(last_raiten_date, '') DESC")->orderByDesc('cd')->limit(200)
            ->get(['cd', 'id', 'kanri_id', 'name', 'name_kana']);

        return view('admin.clientes.partials.search_kaiin', ['total' => $total, 'members' => $members]);
    }

    // --- fin C1-c ---

    // --- C1-b: ficha 顧客登録情報 (inc/customer_detail.php, detail_tab*.php) y 削除・復元 (customer_delete_register.php) ---

    /** Ficha con sus pestañas (?tab=N, ?search_cate_cd=…); ?wintype=no_headers = ventana emergente sin menú */
    public function show(Request $request, int $customer, CustomerDetailData $detail): View
    {
        return $this->detailView($request, $customer, $detail, $request->input('wintype') === 'no_headers');
    }

    /** URL legacy customer/customer_detail.php?kaiin_cd= ($window_type = "ALL": con cabecera y menú) */
    public function legacyDetail(Request $request, CustomerDetailData $detail): View|Response
    {
        return $this->legacyDetailView($request, $detail, $request->input('wintype') === 'no_headers');
    }

    /** URL legacy inc/customer_detail.php?kaiin_cd= (ventana emergente: sin menú y con 閉じる) */
    public function legacyDetailPopup(Request $request, CustomerDetailData $detail): View|Response
    {
        return $this->legacyDetailView($request, $detail, true);
    }

    /** 顧客情報を削除する: disp_flg = 9 y libera la tarjeta; pantalla 「削除中です。」 que vuelve a la ficha */
    public function destroy(Request $request, int $customer, CustomerDeletionService $deletion): View
    {
        abort_unless(Gate::allows('kengen', [1001, 4]), 403);
        $model = Customer::withDeleted()->findOrFail($customer);
        $deletion->delete($model);

        return $this->processingView($request, 'delete', $model->cd);
    }

    /** 顧客情報を復元する (delete_flg = recover); si otro socio ya usa su correo, no se restaura (customer_recover_confirm.php) */
    public function restore(Request $request, int $customer, CustomerDeletionService $deletion): View|RedirectResponse
    {
        abort_unless(Gate::allows('kengen', [1001, 4]), 403);
        $model = Customer::withDeleted()->findOrFail($customer);

        if ($deletion->emailTaken($model)) {
            return redirect()->to($this->detailUrl($request, $model->cd))->with('restoreError', true);
        }
        $deletion->restore($model);

        return $this->processingView($request, 'recover', $model->cd);
    }

    /** inc/ajax_connectPosData.php (pestaña 購買履歴): historial del POS, vacío mientras el POS esté fuera de alcance */
    public function posPurchaseHistory(Request $request, PosGateway $pos): JsonResponse
    {
        return response()->json((object) $pos->purchaseHistory((int) $request->input('shop_cd'), (int) $request->input('tb_customer_cd')));
    }

    /** inc/ajax_connectPosKoubaiData.php: detalle de una compra del POS (vacío, POS fuera de alcance) */
    public function posPurchaseDetail(): JsonResponse
    {
        return response()->json((object) []);
    }

    /** inc/ajax_sendPosKoubaiMemoData.php: nota de compra al POS (sin POS no se envía nada) */
    public function posPurchaseMemo(Request $request, PosGateway $pos): Response
    {
        $sent = $pos->sendPurchaseMemo((int) $request->input('shop_cd'), (int) $request->input('tb_customer_cd'), $request->except('_token'));

        return response($sent ? '1' : '');
    }

    private function legacyDetailView(Request $request, CustomerDetailData $detail, bool $popup): View|Response
    {
        // El legacy hacía exit (página en blanco) si kaiin_cd no era numérico
        $cd = (string) $request->input('kaiin_cd', '');
        if (! preg_match('/^[0-9]+$/', $cd)) {
            return response('');
        }

        return $this->detailView($request, (int) $cd, $detail, $popup);
    }

    private function detailView(Request $request, int $cd, CustomerDetailData $detail, bool $popup): View
    {
        $customer = $detail->customer($cd);
        abort_if($customer === null, 404);

        return view('admin.clientes.show', $detail->forShow($customer, $detail->normalizeTab($request->input('tab')), $request, $popup));
    }

    /** Pantalla intermedia del legacy (「削除中です。」…) que recarga la lista de la ventana padre y vuelve a la ficha */
    private function processingView(Request $request, string $mode, int $cd): View
    {
        return view('admin.clientes.processing', [
            'mode' => $mode,
            'popup' => $request->input('wintype') === 'no_headers',
            'detailUrl' => $this->detailUrl($request, $cd),
        ]);
    }

    /** Ficha conservando la ventana emergente (?wintype=no_headers) */
    private function detailUrl(Request $request, int $cd): string
    {
        return route('admin.clientes.show', $request->input('wintype') === 'no_headers' ? [$cd, 'wintype' => 'no_headers'] : [$cd]);
    }

    // --- fin C1-b ---
}
