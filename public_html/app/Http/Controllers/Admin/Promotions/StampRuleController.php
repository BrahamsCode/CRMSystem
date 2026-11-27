<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Http\Controllers\Controller;
use App\Models\Promotions\StampRule;
use App\Services\Promotions\LegacyTime;
use App\Services\Promotions\StaffContext;
use App\Support\LegacyText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * スタンプ発行ルール (category3/stamp/stamp_rules_*.php, F5 de S2): mismo patrón que PointRuleController de S3
 * (lista por tienda con 適用, alta y modificación en el mismo formulario, borrado físico) sin los rangos de cliente,
 * que la versión de sellos no tiene.
 */
class StampRuleController extends Controller
{
    public const TYPES = PointRuleController::TYPES;

    public const DOW = PointRuleController::DOW;

    public function __construct(private readonly StaffContext $staff) {}

    public function index(Request $request): View
    {
        // 表示情報を更新: las marcadas quedan '' (適用) y las demás '9'
        if ($request->input('update_hyouji_flg') === 'update') {
            $checked = (array) $request->input('hyouji_flg_array', []);
            foreach ((array) $request->input('update_cd', []) as $cd) {
                StampRule::query()->whereKey((int) $cd)->update(['hyouji_flg' => isset($checked[$cd]) ? '' : StampRule::HIDDEN_FLAG]);
            }
        }

        $kensu = max(1, (int) ($request->input('kensu') ?: 20));
        $query = StampRule::query()
            ->when(! $this->staff->isMaster(), fn ($q) => $q->whereIn('tb_shop_cd', [StampRule::ALL_SHOPS, (int) $this->staff->shopCd()]));
        $total = (clone $query)->count();
        $maxPage = (int) ceil($total / $kensu);
        $page = max(1, min((int) ($request->input('page') ?: 1), max(1, $maxPage)));

        return view('admin.promocion.stamp.stamp_rules_list', [
            'rules' => $query->orderByDesc('tb_shop_cd')->orderByDesc('type_flg')->orderByDesc('update_date')->forPage($page, $kensu)->get(),
            'total' => $total,
            'page' => $page,
            'kensu' => $kensu,
            'from' => ($page - 1) * $kensu,
            'shopNames' => DB::table('tb_shop')->pluck('kaishamei', 'cd')->all(),
        ]);
    }

    /** stamp_rules_new.php / stamp_rules_update.php: act=up valida y registra; si no, muestra el formulario */
    public function form(Request $request): View|RedirectResponse
    {
        $cd = $request->routeIs('*.edit') ? (int) $request->input('cd') : null;
        $errors = [];

        if ($request->input('act') === 'up') {
            $data = array_map(fn ($v) => is_array($v) ? $v : LegacyText::form($v), $request->except('_token'));
            [$errors, $times] = $this->validateRule($data);
            if ($errors === []) {
                $this->register($data, $times, $cd, $request);

                return redirect()->route('admin.promocion.stamps.rules.registered', ['mode' => $cd ? 'update' : 'new']);
            }
        } elseif ($cd !== null) {
            $rule = StampRule::query()->find($cd);
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

        return view('admin.promocion.stamp.stamp_rules_frm', [
            'mode' => $cd ? '修正' : '新規登録',
            'action' => $cd ? route('admin.promocion.stamps.rules.edit') : route('admin.promocion.stamps.rules.create'),
            'data' => $data,
            'errors' => $errors,
            'cd' => $request->input('cd'),
            'shops' => $shops,
            'singleShop' => $shops->count() === 1 || ! $this->staff->isMaster(),
            'isMaster' => $this->staff->isMaster(),
            'thisYear' => (int) Carbon::now(LegacyTime::ZONE)->format('Y'),
        ]);
    }

    /** Pantalla «登録中です» que vuelve a la lista al segundo (fin de stamp_rules_register.php) */
    public function registered(Request $request): View
    {
        return view('admin.promocion.stamp.stamp_rules_done', [
            'mode' => $request->input('mode') === 'update' ? '修正' : '新規登録',
            'message' => '登録中です。',
            'next' => route('admin.promocion.stamps.rules.index'),
        ]);
    }

    /** stamp_rules_delete.php: borrado físico y vuelta a la lista con page y kensu */
    public function destroy(Request $request): View
    {
        StampRule::query()->whereKey((int) $request->input('cd'))->delete();

        return view('admin.promocion.stamp.stamp_rules_done', [
            'mode' => '削除',
            'message' => '削除中です。',
            'next' => route('admin.promocion.stamps.rules.index', ['page' => (string) $request->input('page', ''), 'kensu' => (string) $request->input('kensu', '')]),
        ]);
    }

    // ------------------------------------------------------------------

    /**
     * stamp_rules_errchk.php. Devuelve [errores por campo, [start, end] en epoch para los tipos 3 y 5].
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

    /** stamp_rules_register.php: alta con hatuban o modificación */
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
            'update_date' => time(),
        ];
        if ((string) $request->input('stamp_count', '') !== '') {
            $row['stamp_count'] = (int) $data['stamp_count'];
        }

        DB::transaction(function () use ($row, $cd) {
            if ($cd === null) {
                StampRule::create($row + ['touroku_date' => time(), 'hyouji_flg' => '1']);
            } else {
                StampRule::query()->whereKey($cd)->update($row);
            }
        });
    }

    /** Valores del formulario a partir de la fila (stamp_rules_update.php sin act) */
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
}
