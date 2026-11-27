<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Promotions\TestMailAddressRequest;
use App\Models\Promotions\TestMailAddress;
use App\Models\Shop;
use App\Models\Staff;
use App\Services\Promotions\RankOrder;
use App\Services\Promotions\StaffContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * テストメールアドレス管理: test_mail_address.php, ajax_test_mail_address_new.php,
 * test_mail_address_register.php, ajax_test_mail_address_staff.php
 */
class TestAddressController extends Controller
{
    public function __construct(private StaffContext $staff) {}

    public function index(Request $request): View
    {
        // Quien no es master solo ve su tienda
        $shopCd = $this->staff->isMaster() ? $request->input('shop') : $this->staff->shopCd();
        $shopCd = $shopCd === null || $shopCd === '' ? null : (int) $shopCd;

        return view('admin.promocion.mail_mag.test_mail_address', [
            'isMaster' => $this->staff->isMaster(),
            'shops' => $this->staff->shops(),
            'shopCd' => $shopCd,
            'shopName' => $shopCd === -1 ? '全店舗用' : Shop::withDeleted()->find($shopCd)?->kaishamei,
            'address' => $request->filled('cd') ? TestMailAddress::find($request->integer('cd')) : null,
        ]);
    }

    public function ajax(Request $request, RankOrder $order): View|Response
    {
        if (! $request->filled('shop_cd')) {
            return response('');
        }

        $shopCd = (int) $request->input('shop_cd');
        $scope = fn () => TestMailAddress::query()->where('tb_shop_cd', $shopCd);
        $type = (string) $request->input('send_type');

        if (in_array($type, [RankOrder::UP, RankOrder::DOWN], true) && $request->filled('send_targetCd') && $request->filled('send_vl')) {
            $order->move($scope(), $request->integer('send_targetCd'), $type);
        }

        return view('admin.promocion.mail_mag.ajax_test_mail_address_new', [
            'addresses' => $scope()->where('disp_flg', '1')->orderBy('rank')->orderBy('cd')->get(),
            'shopCd' => $shopCd,
            'isMaster' => $this->staff->isMaster(),
        ]);
    }

    /** act = new | up | del, y vuelve a la pantalla de la tienda */
    public function register(TestMailAddressRequest $request): RedirectResponse
    {
        $shopCd = (int) $request->input('tb_shop_cd');

        DB::transaction(function () use ($request, $shopCd) {
            match ($request->input('act')) {
                'new' => TestMailAddress::create([
                    'name' => (string) $request->input('name'),
                    'email' => (string) $request->input('email'),
                    'tb_shop_cd' => $shopCd,
                    'rank' => (int) TestMailAddress::query()->where('disp_flg', '1')->where('tb_shop_cd', $shopCd)->max('rank') + 1,
                    'disp_flg' => '1',
                ]),
                'up' => TestMailAddress::query()->whereKey($request->integer('cd'))->firstOrFail()->update([
                    'name' => (string) $request->input('name'),
                    'email' => (string) $request->input('email'),
                    'tb_shop_cd' => $shopCd,
                    'disp_flg' => '1',
                ]),
                'del' => $this->delete($request->integer('cd'), $request->integer('rank')),
                default => null,
            };
        });

        return redirect()->route('admin.promocion.test-addresses.index', ['shop' => $request->input('tb_shop_cd')]);
    }

    /** ajax_test_mail_address_staff.php: nombre y correo de un miembro del personal */
    public function staff(Request $request): JsonResponse|Response
    {
        $staff = $request->filled('cd') ? Staff::query()->where('disp_flg', '1')->find($request->integer('cd')) : null;

        return $staff ? response()->json(['name' => $staff->name, 'email' => $staff->email1]) : response('');
    }

    /** Borrado lógico y los de detrás suben un puesto en la misma tienda */
    private function delete(int $cd, int $rank): void
    {
        $address = TestMailAddress::withDeleted()->find($cd);
        if (! $address) {
            return;
        }

        $address->update(['rank' => null, 'disp_flg' => '9']);
        TestMailAddress::withDeleted()->where('tb_shop_cd', $address->tb_shop_cd)->where('rank', '>', $rank)->decrement('rank');
    }
}
