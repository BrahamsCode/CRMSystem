<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Http\Controllers\Controller;
use App\Services\Promotions\CouponKubun;
use App\Services\Promotions\LegacyTime;
use App\Services\Promotions\StaffContext;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Uso de las クーポン (tb_mycoupon_used): 使用数ランキング (mycoupon_ranking.php, top 20),
 * クーポン利用数確認 (mycoupon_stats.php) y 利用者照会 (mycoupon_stats_detail.php).
 * Los periodos cuentan el día de negocio desde las 5:00, como el legacy.
 */
class CouponStatsController extends Controller
{
    /** Etiquetas de couponKubunDisp() del ranking (difieren de fnc_make_array en 1 y 4) */
    public const RANKING_KUBUN = ['' => 'メルマガ配信用', '1' => 'メルマガ配信用', '2' => 'マイページスタンプ交換用', '3' => 'アンケート回答時プレゼント用',
        '4' => '新規登録フォーム完了後プレゼント用', '5' => 'お友達紹介時プレゼント用', '6' => '会員の来店時に印刷用', '7' => 'ゲーム用', '8' => 'デジタルサイネージ用'];

    public function __construct(private readonly StaffContext $staff) {}

    public function ranking(Request $request): View
    {
        $shop = (string) $request->input('shop', '');
        $kubun = (string) $request->input('coupon_kubun', '');

        $query = DB::table('tb_mycoupon_used as tb1')
            ->join('tb_mycoupon_master as tb2', 'tb2.cd', '=', 'tb1.tb_mycoupon_master_cd')
            ->when($shop !== '', fn ($q) => $q->where('tb1.tb_shop_cd', (int) $shop))
            // '0' (メルマガ配信用) se guarda como ''
            ->when($kubun !== '', fn ($q) => $q->where('tb1.coupon_kubun', (int) $kubun === 0 ? '' : $kubun))
            ->groupBy('tb1.tb_mycoupon_master_cd', 'tb2.cd', 'tb2.title', 'tb2.comment', 'tb2.tb_shop_cd', 'tb2.coupon_kubun')
            ->selectRaw('count(*) as cnt, tb1.tb_mycoupon_master_cd, tb2.cd, tb2.title, tb2.comment, tb2.tb_shop_cd, tb2.coupon_kubun')
            // El legacy solo ordenaba por cnt; el cd desempata para que el orden sea estable
            ->orderByDesc('cnt')->orderBy('tb2.cd')->limit(20);

        $rows = $query->get();
        $mailCounts = DB::table('mailmag_master')->where('type_flg', 'coupon')->whereIn('type_data_cd', $rows->pluck('cd')->all())
            ->groupBy('type_data_cd')->selectRaw('type_data_cd, count(*) AS n')->pluck('n', 'type_data_cd')->all();

        return view('admin.promocion.mycoupon.mycoupon_ranking', [
            'rows' => $rows,
            'mailCounts' => $mailCounts,
            'shops' => DB::table('tb_shop')->where('disp_flg', '1')->when(! $this->staff->isMaster(), fn ($q) => $q->where('cd', $this->staff->shopCd()))->orderBy('cd')->get(['cd', 'kaishamei']),
            'shopNames' => DB::table('tb_shop')->pluck('kaishamei', 'cd')->all(),
            'shop' => $shop,
            'kubun' => $kubun,
            'kubunOptions' => $this->rankingOptions(),
        ]);
    }

    public function index(Request $request): View|RedirectResponse
    {
        [$shops, $shopCd] = $this->shopScope($request);
        if ($shops->isEmpty()) {
            return redirect('/system/logout.php');
        }
        [$mode, $apply] = $this->period($request, $shops, $shopCd);
        $perPage = max(1, (int) ($request->input('kensu') ?: 20));

        $query = DB::table('tb_mycoupon_used as tb1')
            ->join('tb_mycoupon_master as tb2', 'tb1.tb_mycoupon_master_cd', '=', 'tb2.cd')
            ->tap($apply)
            ->groupBy('tb2.cd', 'tb2.coupon_kubun', 'tb2.title')
            ->selectRaw('tb2.cd, tb2.coupon_kubun, tb2.title, COUNT(*) AS cnt')
            ->orderByDesc('cnt')->orderBy('tb2.cd');

        return view('admin.promocion.mycoupon.mycoupon_stats', [
            'rows' => $query->paginate($perPage, ['*'], 'page', max(1, (int) $request->input('page', 1))),
            'mode' => $mode,
            'shops' => $shops,
            'shopCd' => $shopCd,
            'isMaster' => $this->staff->isMaster(),
            'filters' => $this->filters($request, $shopCd),
            'kubunLabels' => CouponKubun::labels(),
        ]);
    }

    public function show(Request $request): View|RedirectResponse
    {
        $shops = DB::table('tb_shop')->where('disp_flg', '<>', '9')->orderBy('cd')->get(['cd', 'kaishamei']);
        if ($shops->isEmpty()) {
            return redirect('/system/logout.php');
        }
        $shopCd = $this->staff->isMaster() ? (string) $request->input('shop_cd', '') : (string) $this->staff->shopCd();
        [$mode, $apply] = $this->period($request, $shops, $shopCd);
        $couponCd = (int) $request->input('cd');

        $base = fn () => DB::table('tb_mycoupon_used as tb1')
            ->join('tb_customer as tb2', 'tb1.tb_customer_cd', '=', 'tb2.cd')
            ->where('tb1.tb_mycoupon_master_cd', $couponCd)
            ->tap($apply);

        $rows = $base()->groupBy('tb2.cd', 'tb2.id', 'tb2.name')
            ->selectRaw('tb2.cd, tb2.id, tb2.name, max(tb1.used_date::int) AS last_used, COUNT(*) AS cnt')
            ->orderByDesc('cnt')->orderByDesc('last_used')
            ->paginate(max(1, (int) ($request->input('kensu') ?: 20)), ['*'], 'page', max(1, (int) $request->input('page', 1)));

        // Con más de un uso se listan todas las fechas del cliente
        $times = [];
        foreach ($rows as $row) {
            if ((int) $row->cnt > 1) {
                $times[$row->cd] = $base()->where('tb2.cd', $row->cd)->orderByDesc('used_date')->selectRaw('tb1.used_date::int AS used_date')->pluck('used_date')->all();
            }
        }

        return view('admin.promocion.mycoupon.mycoupon_stats_detail', [
            'rows' => $rows,
            'times' => $times,
            'mode' => $mode,
        ]);
    }

    // ------------------------------------------------------------------

    /** Tiendas del selector y tienda elegida (master: la de la sesión si no llega shop_cd) */
    private function shopScope(Request $request): array
    {
        if ($this->staff->isMaster()) {
            $shops = DB::table('tb_shop')->where('disp_flg', '<>', '9')->orderBy('cd')->get(['cd', 'kaishamei']);
            $shopCd = $request->has('shop_cd') ? (string) $request->input('shop_cd') : (string) $this->staff->shopCd();
        } else {
            $shops = DB::table('tb_shop')->where('cd', $this->staff->shopCd())->where('disp_flg', '<>', '9')->get(['cd', 'kaishamei']);
            $shopCd = (string) $this->staff->shopCd();
        }

        return [$shops, $shopCd];
    }

    /**
     * kikan_option: 1 本日 (defecto), 2 昨日, 3 過去一週間, 4 過去一ヶ月, 5 今月, 6 期間指定 (search_start/end).
     * Antes de las 5:00 cuenta como el día anterior.
     *
     * @return array{0: string, 1: \Closure(Builder): void} [título, filtro de tienda y fechas]
     */
    private function period(Request $request, $shops, string $shopCd): array
    {
        $now = Carbon::now(LegacyTime::ZONE);
        $prevDay = $now->hour < 5;
        $five = fn (Carbon $day) => $day->copy()->setTime(5, 0)->getTimestamp() - ($prevDay ? 86400 : 0);
        $start = $end = null;
        $mode = '';

        switch ((string) $request->input('kikan_option')) {
            case '2':
                $mode = '昨日';
                $start = $five($now->copy()->subDay());
                $end = $start + 86399;
                break;
            case '3':
                $mode = '過去一週間';
                $start = $five($now->copy()->subWeek());
                break;
            case '4':
                $mode = '過去一ヶ月';
                $start = $five($now->copy()->subMonth());
                break;
            case '5':
                $mode = '今月';
                $base = $prevDay ? $now->copy()->subDay() : $now->copy();
                $start = $base->startOfMonth()->setTime(5, 0)->getTimestamp();
                break;
            case '6':
                $from = (string) $request->input('search_start', '');
                $to = (string) $request->input('search_end', '');
                $start = $from !== '' && ($t = strtotime($from.' 05:00:00')) ? $t : null;
                $end = $to !== '' && ($t = strtotime($to.' 04:59:59')) ? $t + 86400 : null;
                if ($start || $end) {
                    $mode = ($start ? date('Y/m/d', $start) : '').' ～ '.($end ? date('Y/m/d', $end - 86400) : '');
                }
                break;
            default:
                $mode = '本日';
                $start = $five($now->copy());
        }

        $storeName = ($shopCd !== '' && $shopCd !== '-1') ? ($shops->firstWhere('cd', (int) $shopCd)?->kaishamei.'で') : '';
        $mode .= '　'.$storeName.'利用されたクーポンの統計';

        $apply = function (Builder $q) use ($shopCd, $start, $end) {
            if ($shopCd !== '' && $shopCd !== '-1') {
                $q->where('tb1.tb_shop_cd', (int) $shopCd);
            }
            if ($start && $end) {
                $q->whereRaw('tb1.used_date::int BETWEEN ? AND ?', [$start, $end]);
            } elseif ($start) {
                $q->whereRaw('tb1.used_date::int >= ?', [$start]);
            } elseif ($end) {
                $q->whereRaw('tb1.used_date::int <= ?', [$end]);
            }
        };

        return [$mode, $apply];
    }

    private function filters(Request $request, string $shopCd): array
    {
        return ['shop_cd' => $shopCd, 'kikan_option' => (string) $request->input('kikan_option', ''),
            'search_start' => (string) $request->input('search_start', ''), 'search_end' => (string) $request->input('search_end', '')];
    }

    /** Opciones de 使用区分 del ranking: sin 印刷; 1, 6, 7, 8 solo con 電子看板管理; 2 y 3 según módulos */
    private function rankingOptions(): array
    {
        $child = fn (string $name) => DB::table('tb_base_system_child')->where('system_name', $name)->where('system_flg', '1')->exists();
        $signage = $child('電子看板管理');
        $options = [];
        foreach (CouponKubun::labels() as $i => $label) {
            if ($i === 1 || ($i === 3 && ! $child('アンケート管理')) || (! $signage && in_array($i, [6, 7, 8], true)) || ($i === 2 && ! $child('スタンプ管理'))) {
                continue;
            }
            $options[(string) $i] = $label;
        }

        return $options;
    }
}
