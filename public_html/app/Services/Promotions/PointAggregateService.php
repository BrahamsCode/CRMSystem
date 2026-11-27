<?php

namespace App\Services\Promotions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * ポイント集計 (legacy system/category3/point/ajax_point_aggregate.php): suma de tb_point_log por tienda y
 * clase de movimiento, con la misma forma de respuesta que espera el JS de point_aggregate.php
 * (`{status, data: {startDate, endDate, result: [{cd, name, point: {acquire: {...}, use: {...}}}]}}`).
 */
class PointAggregateService
{
    /** action_flg que suman sounyu_point como 付与 (el resto reparte jidou/shouhi/sounyu) */
    public const ACQUIRE_PATTERN = '/^(crm:newRegist|mycolle:newRegist|mycolle:signUp|mycolle:introduce|mycolle:push|mycolle:dailyLogin|mycolle:checkIn)$/';

    private const DATE_PATTERN = '/^([1-2][0-9]{3})[\/|\-]([0-9]{1,2})[\/|\-]([0-9]{1,2})$/';

    public function __construct(private readonly StaffContext $staff) {}

    /**
     * @return array{status: string, errorMsg?: string, data?: array}
     */
    public function aggregate(?string $startDate, ?string $endDate): array
    {
        $start = $end = null;
        if ($startDate !== null && $startDate !== '' && $startDate !== '0') {
            $start = $this->parseDate($startDate);
            if ($start === null) {
                return ['status' => 'error', 'errorMsg' => '期間開始日付は不適切な形式です。'];
            }
        }
        if ($endDate !== null && $endDate !== '' && $endDate !== '0') {
            $end = $this->parseDate($endDate)?->setTime(23, 59, 59);
            if ($end === null) {
                return ['status' => 'error', 'errorMsg' => '期間終了日付は不適切な形式です。'];
            }
        }

        $result = $this->shops();
        $pointData = $this->pointData($start?->getTimestamp(), $end?->getTimestamp());

        // Como el legacy: la clave `point` solo aparece si hay movimientos (null para la tienda sin ellos)
        if ($pointData !== null && $result !== null) {
            foreach ($result as $key => $shop) {
                $result[$key]['point'] = $pointData[$shop['cd']] ?? null;
            }
        }

        return ['status' => 'ok', 'data' => ['startDate' => $startDate, 'endDate' => $endDate, 'result' => $result]];
    }

    /** Tiendas visibles (todas para master, la propia si no) precedidas de 管理画面 (-1); null si no hay */
    private function shops(): ?array
    {
        $shops = DB::table('tb_shop')->where('disp_flg', '1')
            ->when(! $this->staff->isMaster(), fn ($q) => $q->where('cd', (int) $this->staff->shopCd()))
            ->orderBy('cd')
            ->get(['cd', 'kaishamei as name'])
            ->map(fn ($s) => ['cd' => (string) $s->cd, 'name' => $s->name])
            ->all();

        return $shops === [] ? null : [['cd' => '-1', 'name' => '管理画面'], ...$shops];
    }

    /**
     * Movimientos agrupados por tienda y clase (mismo CASE que el legacy, incluida su regex de clase de caracteres).
     *
     * @return array<string, array{acquire?: array<string, int>, use?: array<string, int>}>|null
     */
    private function pointData(?int $start, ?int $end): ?array
    {
        $rows = DB::table('tb_point_log')
            ->selectRaw("tb_shop_cd,
                CASE
                    WHEN kaikei IS NOT NULL THEN 'accounting'
                    WHEN push_master_cd = 0 THEN 'mycolle:dailyLogin'
                    WHEN action_flg ~ '^[crm:|mycolle:]' THEN action_flg
                    WHEN kaikei IS NULL AND action_flg IS NULL THEN 'adjust'
                    ELSE 'other'
                END AS action_flg,
                SUM(shouhi_point) AS shouhi_point, SUM(jidou_point) AS jidou_point, SUM(sounyu_point) AS sounyu_point")
            ->when($start !== null, fn ($q) => $q->where('touroku_date', '>=', $start))
            ->when($end !== null, fn ($q) => $q->where('touroku_date', '<=', $end))
            ->when(! $this->staff->isMaster(), fn ($q) => $q->where('tb_shop_cd', (int) $this->staff->shopCd()))
            ->groupByRaw('1, 2')
            ->orderByRaw('1, 2')
            ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        $data = [];
        foreach ($rows as $row) {
            $shop = (string) $row->tb_shop_cd;
            $flg = (string) $row->action_flg;
            $sounyu = (int) $row->sounyu_point;

            if (preg_match(self::ACQUIRE_PATTERN, $flg)) {
                $this->add($data[$shop]['acquire'], $flg, $sounyu);

                continue;
            }

            $this->add($data[$shop]['use'], $flg, (int) $row->shouhi_point);
            $this->add($data[$shop]['acquire'], $flg, (int) $row->jidou_point);
            // 調整ポイント: el sounyu negativo cuenta como uso y el positivo como 付与
            $sounyu < 0
                ? $this->add($data[$shop]['use'], 'adjust', abs($sounyu))
                : $this->add($data[$shop]['acquire'], 'adjust', $sounyu);
        }

        return $data;
    }

    private function add(?array &$bucket, string $key, int $value): void
    {
        $bucket[$key] = ($bucket[$key] ?? 0) + $value;
    }

    /** Fecha `Y/m/d` o `Y-m-d` válida (checkdate) a las 0:00 de Asia/Tokyo, como strtotime del legacy */
    private function parseDate(string $value): ?Carbon
    {
        if (! preg_match(self::DATE_PATTERN, $value, $m) || ! checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return null;
        }

        return Carbon::create((int) $m[1], (int) $m[2], (int) $m[3], 0, 0, 0, LegacyTime::ZONE);
    }
}
