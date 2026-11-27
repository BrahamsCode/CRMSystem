<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Http\Controllers\Controller;
use App\Models\Customers\Customer;
use App\Models\Customers\Karte;
use App\Models\Staff;
use App\Services\Customers\KarteService;
use App\Services\Nucleo\LegacyUi;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * カルテ de la ficha (pestaña 5): `system/category1/inc/karute_new.php`, `karute_update.php`, `karute_frm.php`,
 * `karute_register.php`; y カルテ詳細 de 美容 (`system/category1/biyou/karute_detail.php`).
 * ?wintype=no_headers = ventana emergente (versión `inc/` del legacy, sin menú); si no, con menú.
 */
class KarteController extends Controller
{
    public function __construct(private KarteService $kartes) {}

    /** 「カルテを追加する」 (karute_new.php): fecha de hoy */
    public function create(Request $request, int $customer): View
    {
        $model = $this->customer($customer);

        return $this->form($request, $model, null, ['hiduke' => Carbon::now('Asia/Tokyo')->format('Y/m/d')], []);
    }

    /** 「この内容で登録する」 del alta (act=up → karute_register.php con register_type new) */
    public function store(Request $request, int $customer): View
    {
        $model = $this->customer($customer);

        return $this->submit($request, $model, new Karte);
    }

    /** 「カルテを修正する」 (karute_update.php) */
    public function edit(Request $request, int $customer, int $karte): View
    {
        $model = $this->customer($customer);
        $item = $this->karte($request, $model, $karte);

        return $this->form($request, $model, $item, $this->kartes->formData($item), []);
    }

    /** 「この内容で登録する」 de la edición */
    public function update(Request $request, int $customer, int $karte): View
    {
        $model = $this->customer($customer);

        return $this->submit($request, $model, $this->karte($request, $model, $karte));
    }

    /** カルテ詳細 (biyou/karute_detail.php?cd=) */
    public function show(int $karte): View
    {
        $item = DB::table('tb_karute as tb1')
            ->join('tb_sejutsu_cate as tb2', 'tb1.tb_sejutsu_cate_cd', '=', 'tb2.cd')
            ->where('tb1.cd', $karte)
            ->select('tb1.*', 'tb2.name as sejutsu_name')
            ->first();
        abort_if($item === null, 404); // el legacy volvía a ../index.php
        $customer = DB::table('tb_customer')->where('cd', $item->tb_customer_cd)->select('id', 'name')->first();
        abort_if($customer === null, 404);

        $shopName = (string) $item->tb_shop_cd === '-1'
            ? '全店舗'
            : (string) DB::table('tb_shop')->where('cd', $item->tb_shop_cd)->value('kaishamei');

        return view('admin.clientes.kartes.show', [
            'siteName' => app(LegacyUi::class)->siteName(),
            'karte' => $item,
            'customer' => $customer,
            'shopName' => $shopName,
            'uketsukeDate' => is_numeric($item->uketsuke_date)
                ? Carbon::createFromTimestamp((int) $item->uketsuke_date, 'Asia/Tokyo')->format('Y/m/d')
                : '',
        ]);
    }

    /** URL legacy biyou/karute_detail.php?cd= */
    public function legacyShow(Request $request): View
    {
        return $this->show((int) $request->query('cd'));
    }

    // ---------------------------------------------------------------- auxiliares

    /** Comprueba lo enviado (karute_frm.php con act=up): con errores vuelve al formulario; sin errores registra */
    private function submit(Request $request, Customer $customer, Karte $karte): View
    {
        $data = $this->kartes->clean($request->except(['_token', '_method']));
        if (! $this->staff($request)->isMaster()) {
            // Sin master el legacy pintaba la tienda de la sesión en un campo oculto: se fuerza en servidor
            $data['tb_shop_cd'] = (string) $this->sessionShopCd($request);
        }

        $errors = $this->kartes->errors($data);
        if ($errors !== []) {
            return $this->form($request, $customer, $karte->exists ? $karte : null, $data, $errors);
        }

        $cd = $this->kartes->save($karte, $customer, $data);

        // karute_register.php: 「登録中です。」 y meta Refresh de 1 s a la ficha con la カルテ seleccionada
        $params = [$customer->cd, 'kaiin_cd' => $customer->cd, 'tab' => (string) $request->input('tab', ''), 'from' => 'reg', 'karute_cd' => $cd];
        if ($this->popup($request)) {
            $params['wintype'] = 'no_headers';
        }

        return view('admin.clientes.kartes.processing', [
            'popup' => $this->popup($request),
            'detailUrl' => route('admin.clientes.show', $params),
        ]);
    }

    private function form(Request $request, Customer $customer, ?Karte $karte, array $data, array $errors): View
    {
        $staff = $this->staff($request);
        $sessionShopCd = $this->sessionShopCd($request);
        $shops = $this->kartes->shops($staff, $sessionShopCd);
        if (count($shops) === 1) {
            $data['tb_shop_cd'] = (string) $sessionShopCd; // 1 tienda: la de la sesión, como el legacy
        }
        $shopCd = $data['tb_shop_cd'] ?? '';

        return view('admin.clientes.kartes.form', [
            'popup' => $this->popup($request),
            'siteName' => app(LegacyUi::class)->siteName(),
            'customer' => $customer,
            'karte' => $karte,
            'data' => $data,
            'fieldErrors' => $errors,
            'shops' => $shops,
            'fixedShop' => count($shops) === 1 || ! $staff->isMaster(),
            'serviceCategories' => $this->kartes->serviceCategories($shopCd),
            'hidden' => $this->hidden($request),
            'karuteCd' => $karte?->cd ?? $request->input('karute_cd'),
        ]);
    }

    /** $form_content del legacy: todo lo recibido salvo los campos del formulario, que vuelve como oculto */
    private function hidden(Request $request): array
    {
        return collect($request->except(array_merge(KarteService::FORM_FIELDS, ['_token', '_method', 'send', 'karute_cd'])))
            ->filter(fn ($v) => is_scalar($v))
            ->map(fn ($v) => (string) $v)
            ->all();
    }

    private function customer(int $cd): Customer
    {
        $customer = Customer::withDeleted()->find($cd);
        abort_if($customer === null, 404); // el legacy volvía a ../index.php

        return $customer;
    }

    /** La カルテ del cliente; sin master, solo las de la tienda de la sesión (el legacy volvía a ../index.php) */
    private function karte(Request $request, Customer $customer, int $cd): Karte
    {
        $karte = Karte::query()->where('tb_customer_cd', $customer->cd)->find($cd);
        abort_if($karte === null, 404);
        abort_if(! $this->staff($request)->isMaster() && (int) $karte->tb_shop_cd !== $this->sessionShopCd($request), 403);

        return $karte;
    }

    private function popup(Request $request): bool
    {
        return $request->input('wintype') === 'no_headers';
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
