<?php

namespace App\Http\Controllers\Admin\Reservations;

use App\Http\Controllers\Admin\Reservations\Concerns\SelectsLegacyShop;
use App\Http\Controllers\Controller;
use App\Models\Reservations\SimpleReservation;
use App\Models\Staff;
use App\Services\Customers\CustomerListService;
use App\Services\Customers\CustomerSearchForm;
use App\Services\Nucleo\LegacyUi;
use App\Services\Reservations\ReservationListService;
use App\Services\Reservations\SimpleReservationMailer;
use App\Services\Reservations\SimpleReservationService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * 予約管理 simple (legacy system/category3/reservation/): reserve_customer_search.php, reserve_new.php →
 * reserve_new_confirm.php → reserve_new_register.php → reserve_new_complete.php, reserve_calendar.php,
 * reserve_list.php, reserve_detail.php, reserve_change_status.php, reserve_delete.php, config.php /
 * config_register.php y los AJAX ajax_category.php / ajax_staff.php.
 */
class SimpleReservationController extends Controller
{
    use SelectsLegacyShop;

    /** Parámetros de reserve_list.php que el detalle y el cambio de estado devuelven a la lista */
    private const LIST_PARAMS = ['reserve_cd', 'page', 'dd', 'shop_cd', 'kensu'];

    public function __construct(
        private readonly SimpleReservationService $service,
        private readonly LegacyUi $legacyUi,
    ) {}

    // ---------------------------------------------------------------- 予約新規登録

    /** reserve_customer_search.php: buscador de clientes (search_set1 + カスタマイズ検索 + 来店履歴) y botón 予約 */
    public function customerSearch(Request $request, CustomerListService $list, CustomerSearchForm $form): View
    {
        $staff = $this->staff($request);
        $kensu = (int) $request->input('kensu', 20);
        $kensu = $kensu > 0 ? $kensu : 20;
        $page = max(1, (int) $request->input('page', 1));

        $uniqid = is_string($request->input('search_uniqid')) ? $request->input('search_uniqid') : '';
        if ($request->input('act') === 'up' && $request->isMethod('post')) {
            $uniqid = $list->store($request->all(), $staff, $uniqid);
            $page = 1;
        }
        $description = $list->description($uniqid);
        $filters = $description !== null ? (array) ($description['params'] ?? []) : [];
        $formData = $form->viewData($staff, 'category1', $filters, []);

        $data = $formData + [
            'searched' => $description !== null,
            'searchUniqid' => $description !== null ? $uniqid : '',
            'kensu' => $kensu,
            'page' => $page,
            'itemMaxNo' => count($formData['searchItems'] ?? []),
            'total' => 0,
            'customers' => collect(),
            'siteName' => $this->legacyUi->siteName(),
        ];

        if ($description !== null) {
            $query = $list->query($staff, $description);
            $data['total'] = (clone $query)->count();
            $maxPage = max(1, (int) ceil($data['total'] / $kensu));
            $data['page'] = $page = min($page, $maxPage); // ページオーバーを防ぐ
            $data['customers'] = $query->orderByDesc('touroku_date')->orderByDesc('cd')
                ->offset(($page - 1) * $kensu)->limit($kensu)
                ->get(['cd', 'id', 'kanri_id', 'name']);
        }

        return view('admin.reservas.simple.customer_search', $data);
    }

    /** reserve_new.php: formulario (nuevo, desde el buscador con kaiin_cd, 非会員, «書き直す» o act=up desde el detalle) */
    public function create(Request $request): View
    {
        $data = $this->service->formData($request->all(), $request->has('modoru'));
        if ($data['act'] !== 'up') {
            $data['act'] = 'new'; // isset($_REQUEST['act']) ? … : "new"
        }
        $this->authorizeAct($data['act']);
        $customer = $this->service->customer($data['kaiin_cd']);
        // 携帯電話番号: al empezar un alta se propone el móvil del socio
        if ($data['act'] === 'new' && ! $request->has('modoru')) {
            $data['tel'] = (string) ($customer->kojin_mobile ?? '');
        }

        return view('admin.reservas.simple.form', $this->formView($data, $customer, [], false));
    }

    /** reserve_new_confirm.php: comprueba; con errores vuelve a pintar el formulario, si no muestra la confirmación */
    public function confirm(Request $request): View
    {
        $data = $this->service->formData($request->all(), true);
        $this->authorizeAct($data['act']);
        $customer = $this->service->customer($data['kaiin_cd']);
        $errors = $this->service->validate($data);
        $view = $this->formView($data, $customer, $errors, true);

        if ($errors !== []) {
            return view('admin.reservas.simple.form', $view);
        }

        $staffNames = $view['staffList']->pluck('name', 'cd');

        return view('admin.reservas.simple.confirm', $view + [
            'shopName' => (string) ($view['shops']->firstWhere('cd', (int) $view['shopCd'])?->kaishamei ?? ''),
            'categoryName' => (string) ($view['categories']->firstWhere('cd', (int) $data['category_cd'])?->reserve_cate_name ?? ''),
            'staffName' => (string) ($staffNames[(int) $data['staff_cd']] ?? ''),
            'hidden' => $this->hiddenFields($view['data']),
        ]);
    }

    /** reserve_new_register.php: guarda, envía los correos y pasa al 完了 en 2 s */
    public function store(Request $request, SimpleReservationMailer $mailer): View
    {
        $data = $this->service->formData($request->all(), true);
        $siteName = $this->legacyUi->siteName();
        if (! in_array($data['act'], ['new', 'up'], true) || $this->service->parseDate($data['hiduke']) === null) {
            // 「エラーが発生しました」 con el botón que vuelve al formulario con los datos
            return view('admin.reservas.simple.register_error', ['siteName' => $siteName, 'hidden' => $this->hiddenFields($data)]);
        }
        $this->authorizeAct($data['act']);
        if (! $this->isMasterUser()) {
            $data['tb_shop_cd'] = (string) $this->sessionShopCd();
        }

        $reservation = $this->service->save($data);
        $mailer->send(
            $reservation,
            $data['act'] === 'new' ? '予約登録' : '予約変更',
            $this->senderName(),
            true,
            ['tel' => Gate::allows('kengen', [18, 1]), 'mail' => Gate::allows('kengen', [20, 1])],
        );

        return view('admin.reservas.simple.processing', [
            'title' => '予約新規登録 - ' . $siteName,
            'heading' => '予約新規登録 確認',
            'message' => '登録中です。',
            'alt' => '登録中です。',
            'boxClass' => 'textCenter',
            'seconds' => 2,
            'url' => route('admin.reservas.simple.complete', $reservation->cd),
        ]);
    }

    /** reserve_new_complete.php (?reserve_cd=) */
    public function complete(Request $request): View
    {
        $reservation = $this->findReservation($request);

        return view('admin.reservas.simple.complete', [
            'reservation' => $reservation,
            'siteName' => $this->legacyUi->siteName(),
            'canSeeMail' => Gate::allows('kengen', [20, 1]),
        ] + $this->service->related($reservation));
    }

    // ---------------------------------------------------------------- 予約・来店一覧

    /** reserve_calendar.php (year + mon, o dd = 'Y/n/j' al volver): mes con el número de reservas de cada día */
    public function calendar(Request $request, ReservationListService $lists): View
    {
        $today = now('Asia/Tokyo')->startOfDay();
        $month = $this->listMonth($request) ?? $today->copy()->startOfMonth();
        $onlyShop = $this->isMasterUser() ? null : (int) $this->sessionShopCd();

        return view('admin.reservas.simple.calendar', $lists->simpleCalendar($month, $onlyShop, $today) + [
            'month' => $month,
            'prevMonth' => $month->copy()->subMonth(),
            'nextMonth' => $month->copy()->addMonth(),
            'siteName' => $this->legacyUi->siteName(),
        ]);
    }

    /** reserve_list.php (shop_cd, dd, kensu, page): reservas del día */
    public function index(Request $request): View
    {
        $kensu = (int) $this->param($request, 'kensu');
        $kensu = $kensu > 0 ? $kensu : 10;
        $page = max(1, (int) ($this->param($request, 'page') ?: 1));
        $shopCd = $this->isMasterUser() ? $this->param($request, 'shop_cd') : (string) $this->sessionShopCd();
        $dd = $this->param($request, 'dd');
        $result = $this->service->dayList($shopCd, $dd, $page, $kensu);
        $userAgent = (string) $request->userAgent();

        return view('admin.reservas.simple.list', $result + [
            'kensu' => $kensu,
            'page' => $page,
            'maxPage' => (int) ceil($result['total'] / $kensu),
            'dd' => (string) $dd,
            'shopCd' => (string) $shopCd,
            'searchParams' => ['dd' => (string) $dd, 'shop_cd' => (string) $shopCd, 'kensu' => $kensu],
            'isTablet' => str_contains($userAgent, 'CRMRW107') || str_contains($userAgent, 'CRMH580VT_A'),
            'canSeeCustomer' => Gate::allows('kengen', [1001, 1]),
            'siteName' => $this->legacyUi->siteName(),
        ]);
    }

    /** reserve_detail.php (?reserve_cd=&page&dd&shop_cd&kensu) */
    public function show(Request $request): View
    {
        $reservation = $this->findReservation($request);

        return view('admin.reservas.simple.detail', [
            'reservation' => $reservation,
            'listParams' => $this->listParams($request, $reservation),
            'canSeeMail' => Gate::allows('kengen', [20, 1]),
            'canEdit' => Gate::allows('kengen', [1026, 3]),
            'canCancel' => Gate::allows('kengen', [1026, 4]),
            'siteName' => $this->legacyUi->siteName(),
        ] + $this->service->related($reservation));
    }

    /** reserve_change_status.php (act = del | kakutei | visit): cambia el estado, avisa por correo y vuelve a la lista */
    public function status(Request $request, SimpleReservationMailer $mailer): View
    {
        $reservation = $this->findReservation($request);
        $act = (string) $this->param($request, 'act');
        $message = SimpleReservationService::STATUS_MESSAGES[$act] ?? null;
        if ($act === 'del') {
            abort_unless(Gate::allows('kengen', [1026, 4]), 403);
        }
        if ($message !== null) {
            $this->service->changeStatus($reservation, $act);
            if ($act !== 'visit') {
                $mailer->send($reservation->refresh(), $message, $this->senderName(), false);
            }
        }

        return $this->backToList($request, $reservation, (string) $message, '予約情報 ' . $message, (string) $message . '中です。');
    }

    /** reserve_delete.php (act = del | permanent): como el cambio de estado, sin correo (nadie lo enlaza en el legacy) */
    public function destroy(Request $request): View
    {
        $reservation = $this->findReservation($request);
        $act = (string) $this->param($request, 'act');
        $message = SimpleReservationService::DELETE_MESSAGES[$act] ?? '';
        if ($act === 'del') {
            abort_unless(Gate::allows('kengen', [1026, 4]), 403);
        }
        if ($message !== '') {
            $this->service->changeStatus($reservation, $act);
        }

        return $this->backToList($request, $reservation, $message, '予約情報 ' . $message, $message);
    }

    // ---------------------------------------------------------------- 予約項目設定

    /** config.php */
    public function config(): View
    {
        $items = $this->service->configItems();

        return view('admin.reservas.simple.config', [
            'items' => $items,
            'siteName' => $this->legacyUi->siteName(),
        ]);
    }

    /** config_register.php: guarda las casillas y vuelve a config.php en 2 s */
    public function updateConfig(Request $request): View
    {
        if ($request->filled('act')) {
            $this->service->updateConfig($request->all());
        }

        return view('admin.reservas.simple.processing', [
            'title' => '予約項目設定 - ' . $this->legacyUi->siteName(),
            'heading' => '予約項目設定',
            'message' => '登録中です。',
            'alt' => '登録中です。',
            'boxClass' => 'center',
            'seconds' => 2,
            'url' => route('admin.reservas.simple.config'),
        ]);
    }

    // ---------------------------------------------------------------- AJAX

    /** ajax_category.php (shop_cd): <option> de 予約内容 */
    public function categoryOptions(Request $request): View
    {
        return view('admin.reservas.simple.options.categories', [
            'categories' => $this->service->categories($this->optionShopCd($request)),
        ]);
    }

    /** ajax_staff.php (shop_cd): <option> de 指名 */
    public function staffOptions(Request $request): View
    {
        return view('admin.reservas.simple.options.staff', [
            'staffList' => $this->service->nominationStaff($this->optionShopCd($request)),
        ]);
    }

    // ---------------------------------------------------------------- auxiliares

    /**
     * Variables comunes de reserve_new.php y de la confirmación: tiendas (con la de la sesión si no es master),
     * 予約内容, 指名 y las banderas de tb_koumoku.
     *
     * @param  array<string, string>  $data
     * @param  array<string, string>  $errors
     */
    private function formView(array $data, ?object $customer, array $errors, bool $isConfirm): array
    {
        $isMaster = $this->isMasterUser();
        $shops = $this->service->shops($isMaster, $this->sessionShopCd());
        if (! $isMaster || $shops->count() === 1) {
            $data['tb_shop_cd'] = (string) ($this->sessionShopCd() ?? $shops->first()?->cd ?? '');
        }
        $shopCd = $data['tb_shop_cd'] !== '' ? (int) $data['tb_shop_cd'] : null;
        $shimeiFlg = $this->service->koumokuDisp('reserve_nomination') === '1';

        return [
            'data' => $data,
            'act' => $data['act'],
            'customer' => $customer,
            'isMember' => $data['kaiin_cd'] !== '',
            'shops' => $shops,
            'shopCd' => $shopCd,
            'categories' => $this->service->categories($shopCd),
            'staffList' => $shimeiFlg ? $this->service->nominationStaff($shopCd) : collect(),
            'ninsuFlg' => $this->service->koumokuDisp('reserve_people') === '1',
            'shimeiFlg' => $shimeiFlg,
            'canSeeMail' => Gate::allows('kengen', [20, 1]),
            'fieldErrors' => $errors,
            'isConfirm' => $isConfirm,
            'siteName' => $this->legacyUi->siteName(),
        ];
    }

    /** Campos que viajan en los formularios ocultos (書き直す / この内容で予約する) */
    private function hiddenFields(array $data): array
    {
        return array_intersect_key($data, array_flip(SimpleReservationService::FORM_FIELDS));
    }

    /** Modificar una reserva (act=up) exige el permiso del botón 修正する del detalle */
    private function authorizeAct(string $act): void
    {
        if ($act === 'up') {
            abort_unless(Gate::allows('kengen', [1026, 3]), 403);
        }
    }

    private function findReservation(Request $request): SimpleReservation
    {
        $cd = $request->route('reservation') ?? $request->input('reserve_cd');
        abort_unless(is_numeric($cd), 404);

        return SimpleReservation::query()->findOrFail((int) $cd);
    }

    /** Parámetros de la lista que el detalle reenvía (el legacy reenviaba todo $_REQUEST) */
    private function listParams(Request $request, SimpleReservation $reservation): array
    {
        $params = [];
        foreach (self::LIST_PARAMS as $key) {
            $value = $this->param($request, $key);
            if ($value !== null) {
                $params[$key] = $value;
            }
        }
        $params['reserve_cd'] = (string) $reservation->cd;

        return $params;
    }

    /** Pantalla 「…中です。」 que vuelve a reserve_list.php con dd / shop_cd / reserve_cd en 2 s */
    private function backToList(Request $request, SimpleReservation $reservation, string $message, string $heading, string $text): View
    {
        return view('admin.reservas.simple.processing', [
            'title' => '予約情報 - 総合業務管理システム-',
            'heading' => $heading,
            'message' => $text,
            'alt' => $message . '中です。',
            'boxClass' => 'center',
            'seconds' => 2,
            'url' => route('admin.reservas.simple.index', [
                'dd' => (string) $this->param($request, 'dd'),
                'shop_cd' => (string) $this->param($request, 'shop_cd'),
                'reserve_cd' => (string) $reservation->cd,
            ]),
        ]);
    }

    /** `$_SESSION['shop_name']`: tienda del personal (remitente y firma de los correos) */
    private function senderName(): string
    {
        return (string) $this->shopName($this->sessionShopCd());
    }

    /** Mes de reserve_calendar.php: `dd` ('Y/n/…') tiene prioridad sobre `year` + `mon` */
    private function listMonth(Request $request): ?Carbon
    {
        $dd = (string) $this->param($request, 'dd');
        if ($dd !== '') {
            [$year, $mon] = array_pad(explode('/', $dd), 2, '');

            return $this->monthOf($year, $mon);
        }

        return $this->monthOf((string) $this->param($request, 'year'), (string) $this->param($request, 'mon'));
    }

    private function monthOf(string $year, string $mon): ?Carbon
    {
        if (! preg_match('/^\d{4}$/', $year) || ! preg_match('/^\d{1,2}$/', $mon) || (int) $mon < 1 || (int) $mon > 12) {
            return null;
        }

        return Carbon::create((int) $year, (int) $mon, 1, 0, 0, 0, 'Asia/Tokyo');
    }

    /** Tienda de los AJAX: la pedida (master) o la de la sesión */
    private function optionShopCd(Request $request): ?int
    {
        $shop = $this->param($request, 'shop_cd');
        if (! $this->isMasterUser()) {
            return $this->sessionShopCd();
        }

        return is_numeric($shop) ? (int) $shop : null;
    }

    private function param(Request $request, string $key): ?string
    {
        $value = $request->input($key);

        return is_scalar($value) ? trim((string) $value) : null;
    }

    private function staff(Request $request): Staff
    {
        return $request->user('admin');
    }
}
