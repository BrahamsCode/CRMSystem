<?php

namespace App\Http\Controllers\Admin\Operaciones;

use App\Http\Controllers\Controller;
use App\Models\Operaciones\KintaiConfig;
use App\Models\Operaciones\KintaiHiduke;
use App\Models\Shop;
use App\Models\Staff;
use App\Services\Operaciones\KintaiEntryValidator;
use App\Services\Operaciones\KintaiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * 勤怠管理 (system/category5/kintai): 日付別勤怠データ一覧 (index.php), 新規追加 (kintai_new.php + _up),
 * 編集 (kintai_update.php + _up), 一括削除 de marcas (kintai_update_del.php) y 削除 de la jornada (kintai_delete.php).
 *
 * Diferencias con el legacy (Doc/coordinacion/S5-operaciones.md, Decisiones): editar y borrar exigen master (el
 * legacy solo ocultaba los botones); la edición solo toca marcas de la jornada; la ステータス ignora las marcas
 * borradas; los textos se escapan; los borrados van por POST con CSRF; el CSV Flash (AIR) no se migra.
 */
class KintaiController extends Controller
{
    public function __construct(private readonly KintaiService $kintai)
    {
    }

    public function index(Request $request): View
    {
        $user = $this->user();
        $shopCd = KintaiService::shopFor($user, $request->input('shop'));
        [$yy, $mm, $dd] = KintaiService::requestDate($request->input('yy'), $request->input('mm'), $request->input('dd'));
        $day = KintaiService::date($yy, $mm, $dd);
        [$from, $to] = KintaiService::window(KintaiConfig::forShop($shopCd), $day);

        $entries = array_map(fn (array $entry) => $entry + [
            'current' => $this->kintai->currentStatus($entry['hiduke_cd'], $entry['staff_cd']),
        ], $this->kintai->dayEntries($shopCd, $from, $to));

        return view('admin.operaciones.kintai.index', [
            'isMaster' => $user->isMaster(),
            'shopCd' => $shopCd,
            'shopName' => self::shopName($shopCd),
            'shops' => $this->shops(),
            'yy' => $yy, 'mm' => $mm, 'dd' => $dd,
            'day' => $day,
            'entries' => $entries,
            'query' => self::query($yy, $mm, $dd, $shopCd),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        return $this->createForm($request) ?? to_route('admin.operaciones.kintai.index');
    }

    public function store(Request $request): View|RedirectResponse
    {
        $context = $this->dayContext($request);
        if ($context === null) {
            return to_route('admin.operaciones.kintai.index');
        }
        [$shopCd, $yy, $mm, $dd, $day, $config] = $context;
        [$from, $to] = KintaiService::window($config, $day);
        $input = $request->all();

        $staff = $this->staffOf($shopCd)->firstWhere('cd', (int) $request->input('user'));
        if ($staff === null) {
            $errors = '・「スタッフ名」を選択してください。<br />';
        } else {
            ['errors' => $errors, 'times' => $times] = (new KintaiEntryValidator($day, $to))->forCreate($input);
            // Ya fichó en esta jornada: el legacy reemplaza todos los mensajes por este
            if (($name = $this->kintai->clockedInName($staff->cd, $from, $to)) !== null) {
                $errors = '・'.e($name).'さんは既に出勤しています。<br />&emsp;登録済みの出勤データを編集するか、一度削除してください。<br />';
            }
        }

        if ($errors !== '') {
            return $this->createForm($request, $errors);
        }

        $this->kintai->create(Staff::findOrFail($staff->cd), self::status($request->input('status')), (string) $request->input('bikou', ''), $times);

        return view('admin.operaciones.kintai.done', [
            'title' => '勤怠データ新規追加', 'bodyClass' => 'kintaiNewUp', 'message' => '勤怠データを新規追加しました。',
            'query' => self::query($yy, $mm, $dd, $shopCd),
        ]);
    }

    public function edit(Request $request, int $hiduke): View|RedirectResponse
    {
        return $this->editForm($request, $hiduke) ?? to_route('admin.operaciones.kintai.index');
    }

    public function update(Request $request, int $hiduke): View|RedirectResponse
    {
        $context = $this->editContext($request);
        if ($context === null) {
            return to_route('admin.operaciones.kintai.index');
        }
        [$shopCd, $yy, $mm, $dd, $day, $config, $staffCd] = $context;
        $records = $this->kintai->records($hiduke, $staffCd);
        if ($records->isEmpty()) {
            return $this->editForm($request, $hiduke);
        }

        [, $to] = KintaiService::window($config, $day);
        ['errors' => $errors, 'data' => $data] = (new KintaiEntryValidator($day, $to))->forUpdate($request->all());
        if ($errors !== '') {
            return $this->editForm($request, $hiduke, $errors);
        }

        $data += [
            'shukkin_cd' => (int) $request->input('shukkin_cd'),
            'taikin_cd' => (int) $request->input('taikin_cd'),
            'gaishutu_cd' => array_map('intval', array_filter((array) $request->input('gaishutu_cd', []), 'is_scalar')),
            'modori_cd' => array_map('intval', array_filter((array) $request->input('modori_cd', []), 'is_scalar')),
        ];
        $this->kintai->update($hiduke, $staffCd, (string) $records->last()->felica_no, self::status($request->input('status')),
            (string) $request->input('bikou', ''), $data);

        return view('admin.operaciones.kintai.done', [
            'title' => '勤怠データ編集', 'bodyClass' => 'kintaiUpdateUp', 'message' => '勤怠データを更新しました。',
            'query' => self::query($yy, $mm, $dd, $shopCd),
        ]);
    }

    /** チェックをいれたデータを一括削除: vuelve a la edición */
    public function destroyRecords(Request $request, int $hiduke): RedirectResponse
    {
        $context = $this->editContext($request);
        $cds = (array) $request->input('cd', []);
        if ($context === null || ! KintaiService::digits(...array_values($cds))) {
            return to_route('admin.operaciones.kintai.index');
        }
        [$shopCd, $yy, $mm, $dd, , , $staffCd] = $context;

        $this->kintai->deleteRecords($hiduke, $staffCd, array_map('intval', $cds));

        return to_route('admin.operaciones.kintai.edit', ['hiduke' => $hiduke, 'staff' => $staffCd] + self::query($yy, $mm, $dd, $shopCd));
    }

    /** 削除 de la lista: la jornada entera */
    public function destroy(Request $request, int $hiduke): RedirectResponse
    {
        $context = $this->editContext($request);
        if ($context === null) {
            return to_route('admin.operaciones.kintai.index');
        }
        [$shopCd, $yy, $mm, $dd, , , $staffCd] = $context;

        $this->kintai->deleteDay($hiduke, $staffCd);

        return to_route('admin.operaciones.kintai.index', self::query($yy, $mm, $dd, $shopCd));
    }

    /** Formulario de alta; null si la fecha no son dígitos (el legacy redirige a la lista) */
    private function createForm(Request $request, string $errors = ''): ?View
    {
        $context = $this->dayContext($request);
        if ($context === null) {
            return null;
        }
        [$shopCd, $yy, $mm, $dd, , $config] = $context;

        return view('admin.operaciones.kintai.create', [
            'shopCd' => $shopCd,
            'shopName' => self::shopName($shopCd),
            'yy' => $yy, 'mm' => $mm, 'dd' => $dd,
            'hours' => KintaiService::hourRange($config),
            'staff' => $this->staffOf($shopCd),
            'statuses' => KintaiHiduke::DAY_TYPES,
            'input' => $request->all(),
            'errorText' => $errors,
            'query' => self::query($yy, $mm, $dd, $shopCd),
        ]);
    }

    private function editForm(Request $request, int $hiduke, string $errors = ''): ?View
    {
        $context = $this->editContext($request);
        if ($context === null) {
            return null;
        }
        [$shopCd, $yy, $mm, $dd, , $config, $staffCd] = $context;
        $records = $this->kintai->records($hiduke, $staffCd);
        [$kijunHour] = KintaiService::hourRange($config);
        $byFlag = $records->groupBy('kintai_flg');
        $submitted = $errors !== '';

        // Valor elegido de cada selector: el de la marca, o el enviado si se vuelve a mostrar con errores
        $pick = function (?object $record, string $hourKey, string $minuteKey, ?int $index = null) use ($request, $submitted, $kijunHour) {
            if ($submitted) {
                $hour = $index === null ? $request->input($hourKey) : ($request->input($hourKey)[$index] ?? null);
                $minute = $index === null ? $request->input($minuteKey) : ($request->input($minuteKey)[$index] ?? null);

                return [is_scalar($hour) ? (string) $hour : '', is_scalar($minute) ? (string) $minute : ''];
            }
            if ($record === null) {
                return ['', ''];
            }
            $time = \Carbon\CarbonImmutable::createFromTimestamp((int) $record->kintai_time, \App\Services\Operaciones\LegacyMonth::TIMEZONE);

            return [KintaiService::optionHour($time->hour, $kijunHour), $time->format('i')];
        };

        $shukkin = $byFlag->get('1')?->first();
        $taikin = $byFlag->get('2')?->first();
        $outings = fn (string $flag, string $prefix) => ($byFlag->get($flag) ?? collect())->values()
            ->map(fn ($record, $i) => ['cd' => $record->cd, 'value' => $pick($record, $prefix.'_h', $prefix.'_m', $i)])->all();
        $last = $records->last();

        return view('admin.operaciones.kintai.edit', [
            'hiduke' => $hiduke,
            'staffCd' => $staffCd,
            'shopCd' => $shopCd,
            'yy' => $yy, 'mm' => $mm, 'dd' => $dd,
            'hours' => KintaiService::hourRange($config),
            'hasData' => $records->isNotEmpty(),
            'staffName' => $last?->name,
            'current' => $records->isNotEmpty() ? $this->kintai->currentStatus($hiduke, $staffCd) : null,
            'kinmuStatus' => $submitted ? (string) $request->input('status') : $last?->kinmu_status,
            'bikou' => $submitted ? (string) $request->input('bikou') : $last?->bikou,
            'statuses' => KintaiHiduke::DAY_TYPES,
            'shukkin' => ['cd' => $shukkin?->cd, 'value' => $pick($shukkin, 'shukkin_h', 'shukkin_m')],
            'taikin' => ['cd' => $taikin?->cd, 'value' => $pick($taikin, 'taikin_h', 'taikin_m')],
            'gaishutu' => $outings('3', 'gaishutu'),
            'modori' => $outings('4', 'modori'),
            // Fila nueva de 外出 solo si no hay un 外出 sin su 戻り
            'showNewOuting' => count($byFlag->get('3') ?? []) <= count($byFlag->get('4') ?? []),
            'newValues' => [
                'gaishutu' => [(string) $request->input('gaishutu_h_new', ''), (string) $request->input('gaishutu_m_new', '')],
                'modori' => [(string) $request->input('modori_h_new', ''), (string) $request->input('modori_m_new', '')],
            ],
            'errorText' => $errors,
            'query' => self::query($yy, $mm, $dd, $shopCd),
        ]);
    }

    /**
     * Tienda, fecha y configuración de kintai_new.php: yy/mm/dd deben ser dígitos.
     *
     * @return array{0: int, 1: int, 2: int, 3: int, 4: \Carbon\CarbonImmutable, 5: KintaiConfig}|null
     */
    private function dayContext(Request $request): ?array
    {
        if (! KintaiService::digits($request->input('yy'), $request->input('mm'), $request->input('dd'))) {
            return null;
        }
        $shopCd = KintaiService::shopFor($this->user(), $request->input('shop'));
        [$yy, $mm, $dd] = KintaiService::requestDate($request->input('yy'), $request->input('mm'), $request->input('dd'));

        return [$shopCd, $yy, $mm, $dd, KintaiService::date($yy, $mm, $dd), KintaiConfig::forShop($shopCd)];
    }

    /** Como dayContext() y además staff y shop en dígitos (kintai_update.php, _del, kintai_delete.php) */
    private function editContext(Request $request): ?array
    {
        if (! KintaiService::digits($request->input('staff'), $request->input('shop'))) {
            return null;
        }
        $context = $this->dayContext($request);

        return $context === null ? null : [...$context, (int) $request->input('staff')];
    }

    private function user(): Staff
    {
        return auth('admin')->user();
    }

    /** Tiendas del selector (make_shop_options): todas las no borradas para el master */
    private function shops(): Collection
    {
        return Shop::query()->orderBy('cd')->pluck('kaishamei', 'cd');
    }

    private function staffOf(int $shopCd): Collection
    {
        return KintaiService::staffOptions($shopCd);
    }

    private static function shopName(int $shopCd): ?string
    {
        return Shop::query()->withDeleted()->where('cd', $shopCd)->value('kaishamei');
    }

    /** 勤務状況: uno de los del selector (el legacy guardaba cualquier texto) */
    private static function status(mixed $value): string
    {
        return in_array($value, KintaiHiduke::DAY_TYPES, true) ? $value : KintaiHiduke::DAY_TYPES[0];
    }

    /** yy/mm/dd/shop de los enlaces, con mes y día a 2 cifras como los selectores */
    private static function query(int $yy, int $mm, int $dd, int $shopCd): array
    {
        return ['yy' => (string) $yy, 'mm' => sprintf('%02d', $mm), 'dd' => sprintf('%02d', $dd), 'shop' => (string) $shopCd];
    }
}
