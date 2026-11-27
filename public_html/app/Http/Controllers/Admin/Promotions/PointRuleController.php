<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Http\Controllers\Controller;
use App\Models\Promotions\PointRule;
use App\Services\Promotions\LegacyTime;
use App\Services\Promotions\StaffContext;
use App\Support\LegacyText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * ポイント発行ルール (category3/point/point_rules_*.php): lista por tienda con 適用 (hyouji_flg), alta y modificación
 * en el mismo formulario (`_frm` + `_errchk` + `_register`) y borrado físico como el legacy.
 * La prioridad al aplicar la resuelve PointRuleResolver (5 → 1, tienda antes que 全店舗, update_date DESC).
 */
class PointRuleController extends Controller
{
    public const TYPES = ['', '時間指定', '曜日指定', '期間指定', '毎月○○日指定', '日付指定'];

    public const DOW = ['日', '月', '火', '水', '木', '金', '土'];

    public function __construct(private readonly StaffContext $staff) {}

    public function index(Request $request): View
    {
        // 表示情報を更新: las marcadas quedan '' (適用) y las demás '9'
        if ($request->input('update_hyouji_flg') === 'update') {
            $checked = (array) $request->input('hyouji_flg_array', []);
            foreach ((array) $request->input('update_cd', []) as $cd) {
                PointRule::query()->whereKey((int) $cd)->update(['hyouji_flg' => isset($checked[$cd]) ? '' : PointRule::HIDDEN_FLAG]);
            }
        }

        $kensu = max(1, (int) ($request->input('kensu') ?: 20));
        $query = PointRule::query()
            ->when(! $this->staff->isMaster(), fn ($q) => $q->whereIn('tb_shop_cd', [PointRule::ALL_SHOPS, (int) $this->staff->shopCd()]));
        $total = (clone $query)->count();
        $maxPage = (int) ceil($total / $kensu);
        $page = max(1, min((int) ($request->input('page') ?: 1), max(1, $maxPage)));

        return view('admin.promocion.point.point_rules_list', [
            'rules' => $query->orderByDesc('tb_shop_cd')->orderByDesc('type_flg')->orderByDesc('update_date')->forPage($page, $kensu)->get(),
            'total' => $total,
            'page' => $page,
            'kensu' => $kensu,
            'from' => ($page - 1) * $kensu,
            'shopNames' => DB::table('tb_shop')->pluck('kaishamei', 'cd')->all(),
        ]);
    }

    /** point_rules_new.php / point_rules_update.php: act=up valida y registra; si no, muestra el formulario */
    public function form(Request $request): View|RedirectResponse
    {
        $cd = $request->routeIs('*.edit') ? (int) $request->input('cd') : null;
        $errors = [];

        if ($request->input('act') === 'up') {
            $data = array_map(fn ($v) => is_array($v) ? $v : LegacyText::form($v), $request->except('_token'));
            [$errors, $times] = $this->validateRule($data);
            if ($errors === []) {
                $this->register($data, $times, $cd, $request);

                return redirect()->route('admin.promocion.points.rules.registered', ['mode' => $cd ? 'update' : 'new']);
            }
        } elseif ($cd !== null) {
            $rule = PointRule::query()->find($cd);
            if (! $rule) {
                return redirect('/system/category3/index.php');
            }
            $data = $this->dataFromRule($rule->getAttributes());
        } else {
            $data = [];
        }

        $shops = DB::table('tb_shop')->where('disp_flg', '<>', '9')
            ->when(! $this->staff->isMaster(), fn ($q) => $q->where('cd', $this->staff->shopCd()))->orderBy('cd')->get(['cd', 'kaishamei']);
        if ($shops->count() === 1) {
            $data['tb_shop_cd'] = (string) $this->staff->shopCd();
        }

        return view('admin.promocion.point.point_rules_frm', [
            'mode' => $cd ? '修正' : '新規登録',
            'action' => $cd ? route('admin.promocion.points.rules.edit') : route('admin.promocion.points.rules.create'),
            'data' => $data,
            'errors' => $errors,
            'cd' => $request->input('cd'),
            'shops' => $shops,
            'singleShop' => $shops->count() === 1 || ! $this->staff->isMaster(),
            'isMaster' => $this->staff->isMaster(),
            'kingakuRanks' => $this->ranks('金額', 'tb_kingaku_rank_master'),
            'raitenRanks' => $this->ranks('来店', 'tb_raiten_rank_master'),
            'thisYear' => (int) Carbon::now(LegacyTime::ZONE)->format('Y'),
        ]);
    }

    /** Pantalla «登録中です» que vuelve a la lista al segundo (fin de point_rules_register.php) */
    public function registered(Request $request): View
    {
        return view('admin.promocion.point.point_rules_done', [
            'mode' => $request->input('mode') === 'update' ? '修正' : '新規登録',
            'message' => '登録中です。',
            'next' => route('admin.promocion.points.rules.index'),
        ]);
    }

    /** point_rules_delete.php: borrado físico y vuelta a la lista con page y kensu */
    public function destroy(Request $request): View
    {
        PointRule::query()->whereKey((int) $request->input('cd'))->delete();

        return view('admin.promocion.point.point_rules_done', [
            'mode' => '削除',
            'message' => '削除中です。',
            'next' => route('admin.promocion.points.rules.index', ['page' => (string) $request->input('page', ''), 'kensu' => (string) $request->input('kensu', '')]),
        ]);
    }

    // ------------------------------------------------------------------

    /**
     * point_rules_errchk.php. Devuelve [errores por campo, [start, end] en epoch para los tipos 3 y 5].
     *
     * @return array{0: array<string, string>, 1: array{0: ?int, 1: ?int}}
     */
    private function validateRule(array $data): array
    {
        $errors = [];
        $type = (string) ($data['type_flg'] ?? '');
        $times = [null, null];

        if (($data['tb_shop_cd'] ?? '') === '') {
            $errors['tb_shop_cd'] = '※店舗を選択して下さい。';
        }
        if ($type === '0') {
            $errors['type_flg'] = '※ルール区分を選択して下さい。';
        }
        if (in_array($type, ['1', '2'], true) && (int) ($data['start_time'] ?? 0) > (int) ($data['end_time'] ?? 0)) {
            $errors['type_flg'] = '※終了時間は開始時間より前の時間です。';
        }
        if (in_array($type, ['3', '5'], true)) {
            $msg = '';
            $start = $this->date($data, 'start');
            if (! $this->validDate($data, 'start')) {
                $msg .= '<br />※開始日付：'.e($start).'は不適切な形式です。';
            } else {
                $times[0] = $type === '5' ? $this->epoch($start.' 00:00:00') : $this->epoch($start.' '.($data['start_time'] ?? ''));
                if ($type === '5') {
                    $times[1] = $this->epoch($start.' 23:59:59');
                }
            }
            if ($type === '3') {
                $end = $this->date($data, 'end');
                if (! $this->validDate($data, 'end')) {
                    $msg .= '<br />※終了日付：'.e($end).'は不適切な形式です。';
                } else {
                    $times[1] = $this->epoch($end.' '.($data['end_time'] ?? ''));
                }
                if ($msg === '' && $times[1] < $times[0]) {
                    $msg = '<br />※終了日時は開始日時より前の日付です。';
                }
            }
            if ($msg !== '') {
                $errors['type_flg'] = $msg;
            }
        }

        return [$errors, $times];
    }

    /** point_rules_register.php: alta con hatuban o modificación; los rangos se guardan como `cd,cd` */
    private function register(array $data, array $times, ?int $cd, Request $request): void
    {
        $type = (string) ($data['type_flg'] ?? '');
        $row = [
            'tb_shop_cd' => (int) $data['tb_shop_cd'],
            'type_flg' => $type,
            'start_time' => match ($type) {
                '1', '2', '4' => (int) ($data['start_time'] ?? 0),
                '3', '5' => $times[0],
                default => null,
            },
            'end_time' => match ($type) {
                '1', '2' => (int) ($data['end_time'] ?? 0),
                '3', '5' => $times[1],
                default => null,
            },
            'day_of_week' => $type === '2' ? (int) ($data['day_of_week'] ?? 0) : null,
            'tb_kingaku_rank_master_cds' => is_array($request->input('tb_kingaku_rank_master_cds')) ? implode(',', array_map('intval', $request->input('tb_kingaku_rank_master_cds'))) : null,
            'tb_raiten_rank_master_cds' => is_array($request->input('tb_raiten_rank_master_cds')) ? implode(',', array_map('intval', $request->input('tb_raiten_rank_master_cds'))) : null,
            'update_date' => time(),
        ];
        if ((string) $request->input('point_count', '') !== '') {
            $row['point_count'] = (int) $data['point_count'];
        }

        DB::transaction(function () use ($row, $cd) {
            if ($cd === null) {
                PointRule::create($row + ['touroku_date' => time(), 'hyouji_flg' => '1']);
            } else {
                PointRule::query()->whereKey($cd)->update($row);
            }
        });
    }

    /** Valores del formulario a partir de la fila (point_rules_update.php sin act) */
    private function dataFromRule(array $row): array
    {
        $data = array_map(fn ($v) => $v === null ? '' : (string) $v, $row);
        switch ($data['type_flg']) {
            case '1':
            case '2':
                foreach (['start_time', 'end_time'] as $k) {
                    $data[$k] = $data[$k] === '0' ? '000' : $data[$k];
                }
                break;
            case '3':
            case '5':
                foreach (['start', 'end'] as $p) {
                    $t = $row[$p.'_time'];
                    $at = is_numeric($t) ? Carbon::createFromTimestamp((int) $t, LegacyTime::ZONE) : null;
                    $data[$p.'_year'] = $at?->format('Y') ?? '';
                    $data[$p.'_month'] = $at?->format('m') ?? '';
                    $data[$p.'_day'] = $at?->format('d') ?? '';
                    $data[$p.'_time'] = $at?->format('G:i') ?? '';
                }
                break;
        }

        return $data;
    }

    private function date(array $data, string $p): string
    {
        return ($data[$p.'_year'] ?? '').'/'.($data[$p.'_month'] ?? '').'/'.($data[$p.'_day'] ?? '');
    }

    private function validDate(array $data, string $p): bool
    {
        return checkdate((int) ($data[$p.'_month'] ?? 0), (int) ($data[$p.'_day'] ?? 0), (int) ($data[$p.'_year'] ?? 0));
    }

    private function epoch(string $datetime): ?int
    {
        try {
            return Carbon::parse($datetime, LegacyTime::ZONE)->getTimestamp();
        } catch (\Throwable) {
            return null;
        }
    }

    /** Rangos de 顧客ランク si su nieto (使用金額 / 来店回数) está activo */
    private function ranks(string $kind, string $table): array
    {
        $enabled = DB::table('tb_base_system_grandchild')->where('system_name', 'like', '顧客ランク%')->where('system_name', 'like', "%{$kind}%")->where('system_flg', '1')->exists();

        return $enabled ? DB::table($table)->where('disp_flg', '1')->orderBy('rank')->orderBy('cd')->pluck('name', 'cd')->all() : [];
    }
}
