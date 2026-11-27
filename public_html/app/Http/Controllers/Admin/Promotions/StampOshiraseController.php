<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Http\Controllers\Controller;
use App\Services\Nucleo\Hatuban;
use App\Services\Promotions\StaffContext;
use App\Support\LegacyText;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * クーポンお知らせ機能管理 (category3/stamp/oshirase_coupon.php, ajax_oshirase_coupon.php, oshirase_coupon_register.php):
 * por tienda y número de sellos, el texto y los cupones de canje (tb_mycoupon_master, coupon_kubun '2') que se
 * mandan al llegar a ese número (los envía StampService::notifyReachedCount de S3).
 */
class StampOshiraseController extends Controller
{
    public function __construct(private readonly StaffContext $staff) {}

    public function index(): View
    {
        return view('admin.promocion.stamp.oshirase_coupon', ['shops' => $this->staff->shops()]);
    }

    /** ajax_oshirase_coupon.php: sin stamp_count, la lista de lo configurado; con él, el formulario de ese número */
    public function ajax(Request $request): Response|View
    {
        $shopCd = (int) $request->input('tb_shop_cd');
        if (! $shopCd) {
            return response('');
        }
        $stampCount = (string) $request->input('stamp_count', '');

        if ($stampCount === '') {
            $rows = DB::table('tb_stamp_oshirase_coupon')->where('tb_shop_cd', $shopCd)->orderBy('stamp_count')->get();
            if ($rows->isEmpty()) {
                return response('登録されていません');
            }

            return view('admin.promocion.stamp.ajax_oshirase_coupon_list', [
                'rows' => $rows,
                'coupons' => DB::table('tb_mycoupon_master')->where('coupon_kubun', '2')->where('disp_flg', '1')->where('hyouji_flg', '1')
                    ->orderBy('cd')->pluck('title', 'cd')->all(),
            ]);
        }

        $row = DB::table('tb_stamp_oshirase_coupon')->where('tb_shop_cd', $shopCd)->where('stamp_count', (int) $stampCount)->first();

        return view('admin.promocion.stamp.ajax_oshirase_coupon_form', [
            'row' => $row,
            'selected' => $row ? explode(',', (string) $row->tb_mycoupon_master_cd) : [],
            'coupons' => DB::table('tb_mycoupon_master')->where('coupon_kubun', '2')->where('disp_flg', '1')->where('hyouji_flg', '1')
                ->whereIn('tb_shop_cd', [$shopCd, -1])->orderByDesc('shouhi_point')->get(),
            'surnameEnabled' => DB::table('contents_master')->where('category', 'tb_c1_customer')->where('field', 'name_input_mode_flg')->where('disp_flg', '1')->value('text_data') === '1',
            'stampEnabled' => DB::table('tb_base_system_child')->where('system_name', 'スタンプ管理')->where('system_flg', '1')->exists(),
            'visitsEnabled' => DB::table('tb_base_system_child')->where('system_name', '来店履歴管理')->where('system_flg', '1')->exists(),
        ]);
    }

    /** oshirase_coupon_register.php: una fila por tienda y número de sellos (alta con hatuban o modificación) */
    public function store(Request $request): Response
    {
        $shopCd = (int) $request->input('tb_shop_cd');
        $stampCount = (int) $request->input('stamp_count');
        // coupon_master llega como «cd,cd,…»: solo se guardan cd numéricos
        $coupons = implode(',', array_filter(array_map('trim', explode(',', (string) $request->input('coupon_master', ''))), 'ctype_digit'));

        if ($shopCd && $stampCount && $coupons !== '') {
            DB::transaction(function () use ($request, $shopCd, $stampCount, $coupons) {
                $now = (string) time();
                $values = [
                    'tb_shop_cd' => $shopCd,
                    'stamp_count' => $stampCount,
                    'tb_mycoupon_master_cd' => $coupons,
                    'naiyou' => LegacyText::db($request->input('naiyou', '')),
                    'disp_flg' => '1',
                    'hyouji_flg' => LegacyText::db($request->input('hyouji_flg', '')),
                    'touroku_date' => $now,
                    'update_date' => $now,
                ];
                $cd = DB::table('tb_stamp_oshirase_coupon')->where('tb_shop_cd', $shopCd)->where('stamp_count', $stampCount)->value('cd');
                if ($cd === null) {
                    DB::table('tb_stamp_oshirase_coupon')->insert(['cd' => app(Hatuban::class)->next('tb_stamp_oshirase_coupon')] + $values);
                } else {
                    DB::table('tb_stamp_oshirase_coupon')->where('cd', $cd)->update($values);
                }
            });
        }

        return response('処理が完了しました。');
    }
}
