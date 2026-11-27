<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Http\Controllers\Controller;
use App\Models\ContentsMaster;
use App\Models\Promotions\MailmagMaster;
use App\Models\Promotions\MycouponMaster;
use App\Services\Promotions\CouponKubun;
use App\Services\Promotions\StaffContext;
use App\Support\LegacyText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * マイクーポン管理 (category3/mycoupon): 一覧 (mycoupon_list.php, con 表示 en bloque), 新規登録 / 修正
 * (mycoupon_new.php, mycoupon_update.php, _frm, _errchk, _register), 詳細 con クーポン解析 (mycoupon_detail.php)
 * y 削除 (mycoupon_delete.php, disp_flg = 9).
 */
class CouponController extends Controller
{
    public const LIMIT_LABELS = ['', '発行から1週間', '発行から1ヶ月', '発行から3ヶ月', '発行から○○日間', '指定日付まで'];

    public const LOTTERY_RANKS = ['ダイヤ', 'ゴールド', 'シルバー', 'ブロンズ', 'ハズレ'];

    public function __construct(private readonly StaffContext $staff) {}

    public function index(Request $request): View|RedirectResponse
    {
        // 表示情報を更新: hyouji_flg '1' si está marcada, '' si no
        if ($request->input('update_hyouji_flg') === 'update') {
            $checked = array_map('strval', array_keys((array) $request->input('hyouji_flg_array', [])));
            foreach ((array) $request->input('update_cd', []) as $cd) {
                MycouponMaster::query()->whereKey((int) $cd)->update(['hyouji_flg' => in_array((string) $cd, $checked, true) ? '1' : '']);
            }
        }

        $shops = $this->staff->shops();
        if ($shops->isEmpty()) {
            return redirect('/system/logout.php');
        }

        $shopCd = (string) $request->input('shop_cd', '');
        $kubun = (string) $request->input('coupon_kubun', '');
        $perPage = max(1, (int) ($request->input('kensu') ?: 20));
        $scopeShop = $shopCd !== '' ? $shopCd : (string) $this->staff->shopCd();

        $coupons = MycouponMaster::query()
            ->where('disp_flg', '1')
            ->when($scopeShop !== '-1', fn ($q) => $q->where(fn ($w) => $w->where('tb_shop_cd', (int) $scopeShop)->orWhere('tb_shop_cd', -1)))
            ->where('coupon_kubun', $kubun)
            ->orderBy('cd')
            ->paginate($perPage, ['*'], 'page', max(1, (int) $request->input('page', 1)));

        return view('admin.promocion.mycoupon.mycoupon_list', [
            'coupons' => $coupons,
            'shops' => $shops,
            'shopCd' => $shopCd !== '' ? $shopCd : (string) $this->staff->shopCd(),
            'kubun' => $kubun,
            'kubunOptions' => CouponKubun::options(),
            'kubunLabels' => CouponKubun::labels(),
            'perPage' => $perPage,
            'isMaster' => $this->staff->isMaster(),
            'staffShopCd' => $this->staff->shopCd(),
            'canEditAny' => Gate::allows('kengen.1009.3'),
            'shopNames' => DB::table('tb_shop')->pluck('kaishamei', 'cd')->all(),
        ]);
    }

    /** mycoupon_new.php / mycoupon_update.php: formulario y, con act = up, validación y registro */
    public function form(Request $request): View|RedirectResponse
    {
        $cd = (int) $request->input('cd');
        $update = $cd > 0;

        if ($request->input('act') === 'up') {
            $data = $this->input($request);
            $errors = $this->validateInput($data, $request);
            if ($errors === []) {
                $saved = $this->register($data, $update ? $cd : null);

                return redirect()->route('admin.promocion.coupons.registered', ['cd' => $saved->cd, 'register_type' => $update ? 'update' : 'new']);
            }
        } else {
            $errors = [];
            $data = [];
            if ($update) {
                $coupon = MycouponMaster::query()->find($cd);
                if (! $coupon || (! $this->staff->isMaster() && (string) $coupon->tb_shop_cd !== (string) $this->staff->shopCd())) {
                    return redirect()->route('admin.promocion.coupons.index');
                }
                $data = $coupon->getAttributes();
                if ((string) $coupon->limit_flg === '4') {
                    $data['time_limit_val1'] = $coupon->time_limit;
                } elseif ((string) $coupon->limit_flg === '5') {
                    $data['time_limit'] = $data['time_limit_val2'] = is_numeric($coupon->time_limit) ? date('Y/m/d', (int) $coupon->time_limit) : '';
                }
                $data['kujibiki_name'] = ContentsMaster::query()->where('category', 'tb_c6_kujibiki')->where('field', 'coupon')->where('int_data', $cd)->value('text_data');
            }
        }

        $shops = $this->staff->shops()->where('disp_flg', '<>', '9');
        if ($shops->count() === 1 && ! isset($data['tb_shop_cd'])) {
            $data['tb_shop_cd'] = (string) $this->staff->shopCd();
        }

        return view('admin.promocion.mycoupon.mycoupon_frm', [
            'title' => $update ? 'マイクーポン' : 'クーポン',
            'mode' => $update ? '修正' : '新規登録',
            'action' => route($update ? 'admin.promocion.coupons.edit' : 'admin.promocion.coupons.create'),
            'data' => $data,
            'errors' => $errors,
            'cd' => $update ? $cd : '',
            'shops' => $shops,
            'isMaster' => $this->staff->isMaster(),
            'kubunOptions' => CouponKubun::options(),
            'showPoints' => DB::table('tb_base_system_child')->where('system_name', 'スタンプ管理')->where('system_flg', '1')->exists()
                || in_array('ポイント交換用', CouponKubun::labels(), true),
        ]);
    }

    /** Pantalla 登録中です (mycoupon_register.php) que pasa al detalle a los 2 s */
    public function registered(Request $request): View
    {
        return view('admin.promocion.mycoupon.mycoupon_register', [
            'title' => $request->input('register_type') === 'update' ? 'マイクーポン' : 'クーポン',
            'mode' => $request->input('register_type') === 'update' ? '修正' : '新規登録',
            'next' => route('admin.promocion.coupons.show', ['cd' => (int) $request->input('cd'), 'register_type' => $request->input('register_type')]),
        ]);
    }

    public function show(Request $request): View|RedirectResponse
    {
        $coupon = MycouponMaster::query()->where('cd', (int) $request->input('cd'))->first();
        if (! $coupon) {
            return redirect()->route('admin.promocion.coupons.index');
        }
        $cd = (int) $coupon->cd;
        $data = $coupon->getAttributes();
        if ((string) $coupon->limit_flg === '5') {
            $data['time_limit'] = is_numeric($coupon->time_limit) ? date('Y/m/d', (int) $coupon->time_limit) : '';
        }
        $kubun = (string) $coupon->coupon_kubun;
        $canEdit = $this->staff->isMaster() || (Gate::allows('kengen.1009.3') && (string) $coupon->tb_shop_cd === (string) $this->staff->shopCd());

        $usedCount = (int) DB::table('tb_mycoupon_used')->where('tb_mycoupon_master_cd', $cd)->count();

        return view('admin.promocion.mycoupon.mycoupon_detail', [
            'data' => $data,
            'shopName' => (string) $coupon->tb_shop_cd === '-1' ? '全店舗' : (string) DB::table('tb_shop')->where('cd', $coupon->tb_shop_cd)->value('kaishamei'),
            'kubunLabel' => CouponKubun::label($kubun),
            'canEdit' => $canEdit,
            'canSend' => $this->staff->isMaster() || Gate::allows('kengen.1005.2'),
            'expired' => (string) $coupon->limit_flg === '5' && strtotime($data['time_limit'].' 23:59:59') < time(),
            'usedCount' => $usedCount,
            'usedByMonth' => $usedCount > 0 ? $this->usedByMonth($cd) : collect(),
            // kaiseki_disp(): solo cupones de correo (区分 '' o 1); el legacy fallaba aquí con pg_query(['connect'])
            'mailIssues' => in_array($kubun, ['', '0', '1'], true) ? MailmagMaster::query()->where('type_data_cd', $cd)->where('type_flg', 'coupon')->orderByDesc('配信日時')->limit(50)->get() : collect(),
            'followIssues' => in_array($kubun, ['', '0', '1'], true) ? MailmagMaster::query()->where('type_data_cd', $cd)->whereIn('type_flg', ['follow', 'followdeco', 'auto', 'autodeco'])->get() : collect(),
            'back' => $request->only(['kensu', 'page', 'coupon_kubun', 'shop_cd', 'shop', 'func', 'kikan_option', 'search_start', 'search_end']),
            'fromStats' => str_contains((string) $request->headers->get('referer'), 'mycoupon_stats'),
        ]);
    }

    /** mycoupon_delete.php: disp_flg = 9 y, al segundo, vuelta a la lista con sus filtros */
    public function destroy(Request $request): View
    {
        $coupon = MycouponMaster::query()->find((int) $request->input('cd'));
        $canDelete = $coupon && ($this->staff->isMaster() || (Gate::allows('kengen.1009.3') && (string) $coupon->tb_shop_cd === (string) $this->staff->shopCd()));
        if ($request->input('delete_flg') === 'del' && $canDelete) {
            MycouponMaster::query()->whereKey($coupon->cd)->update(['disp_flg' => '9']);
        }

        return view('admin.promocion.mycoupon.mycoupon_delete', [
            'next' => route('admin.promocion.coupons.index', array_map(fn ($v) => LegacyText::form($v), $request->only(['shop_cd', 'coupon_kubun', 'page', 'kensu']))),
        ]);
    }

    // ------------------------------------------------------------------

    /** get_form() de cada campo; attention[] se une con <> (mycoupon_new.php) */
    private function input(Request $request): array
    {
        $data = [];
        foreach ($request->except(['_token', 'attention']) as $key => $value) {
            $data[$key] = LegacyText::form($value);
        }
        if ($request->has('attention')) {
            $data['attention'] = implode('<>', array_map(fn ($v) => LegacyText::form($v), (array) $request->input('attention')));
        }

        return $data;
    }

    /** mycoupon_errchk.php: mismas reglas y mensajes */
    private function validateInput(array $data, Request $request): array
    {
        $errors = [];
        if (($data['tb_shop_cd'] ?? '') === '') {
            $errors['tb_shop_cd'] = '<p class="m0">※店舗を選択して下さい。</p>';
        }
        if (($data['title'] ?? '') === '') {
            $errors['title'] = '<p class="m0">※必須項目です。</p>';
        }
        if (($data['shouhi_point'] ?? '') === '' && ($data['coupon_kubun'] ?? '') === '2') {
            $errors['shouhi_point'] = '<p class="m0">※必須項目です。</p>';
        }
        $limit = $data['limit_flg'] ?? '';
        if ($limit === '0') {
            $errors['limit_flg'] = '<p class="m0">※有効期限を選択して下さい。</p>';
        }
        if ($limit === '4' && ! preg_match('/^[0-9]+$/', (string) $request->input('time_limit', ''))) {
            $errors['limit_flg'] = '<p class="m0">※日数は半角数字で入力して下さい。</p>';
        }
        if ($limit === '5') {
            $date = (string) ($data['time_limit'] ?? '');
            $parts = explode('/', $date);
            if ($date === '') {
                $errors['limit_flg'] = '<br />※日付を入力して下さい。';
            } elseif (count($parts) !== 3 || ! checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
                $errors['limit_flg'] = '<br />※日付：'.e($date).'は不適切な形式です。';
            }
        }

        return $errors;
    }

    /** mycoupon_register.php: alta (hatuban) o modificación; premio de ガチャリン en contents_master si 区分 = 9 */
    private function register(array $data, ?int $cd): MycouponMaster
    {
        return DB::transaction(function () use ($data, $cd) {
            $kubun = ($data['coupon_kubun'] ?? '') === '0' ? '' : (string) ($data['coupon_kubun'] ?? '');
            $now = time();
            $row = [
                'title' => (string) ($data['title'] ?? ''),
                'comment' => (string) ($data['comment'] ?? ''),
                'limit_flg' => (string) ($data['limit_flg'] ?? ''),
                'saitouroku_flg' => (string) ($data['saitouroku_flg'] ?? ''),
                'time_limit' => match ($data['limit_flg'] ?? '') {
                    '4' => (int) $data['time_limit'],
                    '5' => strtotime($data['time_limit'].' 23:59:59'),
                    default => null,
                },
                'attention' => (string) ($data['attention'] ?? ''),
                'coupon_kubun' => $kubun,
                'tb_shop_cd' => (int) $data['tb_shop_cd'],
                'shouhi_point' => (($data['shouhi_point'] ?? '') !== '' && in_array($kubun, ['2', '10'], true)) ? (int) $data['shouhi_point'] : null,
                'update_date' => $now,
            ];

            if ($cd === null) {
                $coupon = MycouponMaster::create($row + ['disp_flg' => '1', 'hyouji_flg' => '1', 'touroku_date' => $now]);
            } else {
                MycouponMaster::query()->withDeleted()->whereKey($cd)->update($row);
                $coupon = MycouponMaster::query()->withDeleted()->findOrFail($cd);
            }

            if ($kubun === '9') {
                $name = (string) ($data['kujibiki_name'] ?? '');
                $exists = DB::table('contents_master as tb1')
                    ->join('tb_mycoupon_master as tb2', 'tb1.int_data', '=', 'tb2.cd')
                    ->where('tb1.category', 'tb_c6_kujibiki')->where('tb1.field', 'coupon')
                    ->where('tb1.text_data', $name)->where('tb1.int_data', $coupon->cd)->where('tb2.disp_flg', '1')->exists();
                if (! $exists) {
                    // createHatsuban(): epoch + tienda (3 cifras) + aleatorio (6 cifras)
                    $key = $now.sprintf('%03s', $data['tb_shop_cd']).sprintf('%06d', random_int(0, 999999));
                    DB::table('contents_master')->insert(['cd' => $key, 'category' => 'tb_c6_kujibiki', 'field' => 'coupon', 'text_data' => $name,
                        'int_data' => $coupon->cd, 'disp_flg' => '1', 'hyouji_flg' => '1', 'touroku_date' => $now, 'update_date' => $now]);
                }
            }

            return $coupon;
        });
    }

    /** Usos por mes (30 meses) con sexo, edad y ocupación (mycoupon_detail.php) */
    private function usedByMonth(int $cd)
    {
        $age = "date_part('year', AGE(TO_TIMESTAMP(tb1.used_date::integer), TO_TIMESTAMP(tb2.kojin_birth_date)))";
        $jobs = ['job1' => '会社員', 'job2' => '自営・自由業', 'job3' => '公務員', 'job4' => 'その他', 'job5' => 'その他学生', 'job6' => '専業主婦',
            'job7' => 'フリーター', 'job8' => '無職', 'job9' => '大学生', 'job10' => '高校生', 'job11' => '中学生以下'];
        $select = "date_part('year', to_timestamp(tb1.used_date::int)) AS year, date_part('month', to_timestamp(tb1.used_date::int)) AS month, count(*) AS count,
            sum(case when tb2.kojin_sex = '女性' then 1 else 0 end) AS women, sum(case when tb2.kojin_sex = '男性' then 1 else 0 end) AS men,
            sum(case when coalesce(tb2.kojin_sex,'') = '' then 1 else 0 end) AS sex_none,
            sum(case when $age < 20 then 1 else 0 end) AS age0, sum(case when $age between 20 and 29 then 1 else 0 end) AS age20,
            sum(case when $age between 30 and 39 then 1 else 0 end) AS age30, sum(case when $age between 40 and 49 then 1 else 0 end) AS age40,
            sum(case when $age between 50 and 59 then 1 else 0 end) AS age50, sum(case when $age between 60 and 69 then 1 else 0 end) AS age60,
            sum(case when $age between 70 and 79 then 1 else 0 end) AS age70, sum(case when $age > 79 then 1 else 0 end) AS age80,
            sum(case when tb2.kojin_birth_date IS NULL then 1 else 0 end) AS age_none";
        foreach ($jobs as $alias => $job) {
            $select .= ", sum(case when tb2.job = '".$job."' then 1 else 0 end) AS $alias";
        }
        $select .= ", sum(case when COALESCE(tb2.job,'') = '' then 1 else 0 end) AS job_none";

        return DB::table('tb_mycoupon_used as tb1')
            ->join('tb_customer as tb2', 'tb1.tb_customer_cd', '=', 'tb2.cd')
            ->where('tb1.tb_mycoupon_master_cd', $cd)
            ->selectRaw($select)
            ->groupByRaw('year, month')->orderByDesc('year')->orderByDesc('month')->limit(30)->get();
    }
}
