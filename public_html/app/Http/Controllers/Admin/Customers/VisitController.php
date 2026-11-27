<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Http\Controllers\Controller;
use App\Models\Customers\Customer;
use App\Models\Staff;
use App\Services\Customers\VisitService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * 来店処理 (`system/category1/customer/customer_search.php`): registro de visita por 会員番号 con
 * `VisitService::register` (intervalo, sellos y puntos de S3, como `cUtility::raitenFunc`).
 * Y las URL que llaman los JS de la ficha: `inc/raiten_register.php` (alta manual / borrado de la pestaña 来店履歴),
 * `inc/ajax_raiten_list.php` (lista de visitas para 「既に取得済みの来店履歴から流用する」) e
 * `inc/ajax_raiten_time_select.php` (selectores de fecha de 来店履歴の追加・修正).
 */
class VisitController extends Controller
{
    /** Pantalla 来店処理 */
    public function index(): View
    {
        return view('admin.clientes.visits.index', ['errorMsg' => '', 'registered' => null, 'registId' => '']);
    }

    /** 「登録」: busca el socio por 会員番号 (tb_customer.id, también borrados, como el legacy) y registra la visita */
    public function store(Request $request, VisitService $visits): View
    {
        $registId = (string) $request->input('regist_id', '');
        $errorMsg = '';
        $registered = null;

        if (! preg_match('/^[0-9]+$/', $registId)) {
            $errorMsg = '入力される内容は数字のみでお願いいたします。';
        } else {
            $customer = Customer::withDeleted()->where('id', $registId)->orderBy('cd')->first();
            if (! $customer) {
                $errorMsg = '顧客が特定できませんでした。';
            } else {
                $terminal = is_numeric($customer->terminal_no) ? (int) $customer->terminal_no : null;
                $registered = $visits->register($customer, $this->sessionShopCd($request), VisitService::ORIGIN_ADMIN, $terminal) !== null;
            }
        }

        return view('admin.clientes.visits.index', ['errorMsg' => $errorMsg, 'registered' => $registered, 'registId' => $registId]);
    }

    /**
     * inc/raiten_register.php ($.get de la pestaña 来店履歴): 「来店履歴処理実行」 da de alta raiten_count visitas manuales
     * en la fecha indicada; 「来店履歴削除」 borra una. La respuesta no se usa (el JS recarga la pestaña).
     */
    public function legacyRegister(Request $request, VisitService $visits): Response
    {
        $customer = Customer::withDeleted()->find((int) $request->input('kaiin_cd'));
        if (! $customer) {
            return response('');
        }

        $action = (string) $request->input('raiten_action');
        if ($action === '来店履歴処理実行') {
            [$y, $m, $d] = [(int) $request->input('yy'), (int) $request->input('mm'), (int) $request->input('dd')];
            [$h, $i] = [(int) $request->input('hh'), (int) $request->input('ii')];
            if (checkdate($m, $d, $y) && $h >= 0 && $h <= 23 && $i >= 0 && $i <= 59) {
                $shopCd = $this->isMaster($request) ? (int) $request->input('raiten_shop_cd') : $this->sessionShopCd($request);
                $visits->registerManual(
                    $customer,
                    $shopCd,
                    Carbon::create($y, $m, $d, $h, $i, 0, 'Asia/Tokyo'),
                    max(1, (int) $request->input('raiten_count', 1)),
                    filled($request->input('tb_point_log_cd')) ? (int) $request->input('tb_point_log_cd') : null,
                    filled($request->input('tb_stamp_log_cd')) ? (int) $request->input('tb_stamp_log_cd') : null,
                );
            }
        } elseif ($action === '来店履歴削除') {
            $visits->deleteManual($customer, (int) $request->input('raiten_cd'));
        }

        return response('');
    }

    /** inc/ajax_raiten_list.php: visitas del socio sin datos 関連情報 (10 por página; sin master, solo su tienda) */
    public function raitenList(Request $request): View
    {
        $query = DB::table('tb_raiten_rireki as tb1')
            ->join('tb_shop as tb2', 'tb1.shop_cd', '=', 'tb2.cd')
            ->where('tb1.user_cd', (int) $request->input('kaiin_cd'))
            ->whereNull('tb1.tb_search_cate_cd')
            ->whereRaw("COALESCE(tb1.disp_flg, '') <> '9'")
            ->when(! $this->isMaster($request), fn ($q) => $q->where('tb2.cd', $this->sessionShopCd($request)))
            ->orderByDesc('tb1.raiten_time')->orderByDesc('tb1.cd')
            ->select('tb1.cd', 'tb1.raiten_time', 'tb1.shop_cd', 'tb2.kaishamei');

        $kensu = (int) $request->input('kensu', 10) ?: 10;
        $total = (clone $query)->count();
        $maxPage = (int) ceil($total / $kensu);
        $page = max(1, min((int) $request->input('page', 1), $maxPage));

        return view('admin.clientes.visits.raiten-list', [
            'visits' => new LengthAwarePaginator($query->forPage($page, $kensu)->get(), $total, $kensu, $page),
        ]);
    }

    /** inc/ajax_raiten_time_select.php: selectores 年月日時分 con la fecha recibida (y, m, d, h, i) */
    public function timeSelect(Request $request): View
    {
        $pad = fn (string $key) => filled($request->input($key)) ? sprintf('%02s', (string) $request->input($key)) : '';
        $year = (string) $request->input('y', '');
        $current = (int) now('Asia/Tokyo')->format('Y');

        return view('admin.clientes.visits.time-select', [
            'raitenYear' => $year,
            'raitenMonth' => $pad('m'),
            'raitenDay' => $pad('d'),
            'raitenHour' => $pad('h'),
            'raitenMin' => $pad('i'),
            'currentYear' => $current,
            // 年セレクトボックス: desde este año hasta 10 atrás (o hasta el año recibido si es anterior)
            'minYear' => $year !== '' ? min($current - 10, (int) $year) : $current - 10,
        ]);
    }

    private function staff(Request $request): Staff
    {
        return $request->user('admin');
    }

    private function isMaster(Request $request): bool
    {
        return $this->staff($request)->isMaster();
    }

    private function sessionShopCd(Request $request): int
    {
        return (int) (session('shop_cd') ?? $this->staff($request)->shop_cd);
    }
}
