<?php

namespace App\Services\Sales;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * スタッフ別集計 y 日報・週報・月報 (legacy: ajax_staffTotal.php, ajax_report.php). Agregan las ventas de
 * `tb_customer_sub` (ver SaleService) por 営業日 (`update_date` 'Y/m/d'). Sin límite de 60 días: el legacy tampoco
 * lo aplicaba (decisión de S0).
 */
class SalesReportService
{
    /**
     * Periodo del legacy: las dos fechas (BETWEEN) o solo una (=). Devuelve null y el error con el texto del legacy.
     *
     * @return array{0: ?array{from:string, to:string, label:string}, 1: ?string}
     */
    public function period(?string $date1, ?string $date2): array
    {
        $date1 = trim((string) $date1);
        $date2 = trim((string) $date2);

        if ($date1 === '' && $date2 === '') {
            return [null, __('ventas.report_period_required', [], 'ja')];
        }
        if ($date1 === '' || $date2 === '') {
            $day = $this->normalize($date1 !== '' ? $date1 : $date2);

            return $day ? [['from' => $day, 'to' => $day, 'label' => $day], null] : [null, __('ventas.report_date_invalid', [], 'ja')];
        }
        $from = $this->normalize($date1);
        $to = $this->normalize($date2);
        if (! $from || ! $to) {
            return [null, __('ventas.report_date_invalid', [], 'ja')];
        }

        return [['from' => $from, 'to' => $to, 'label' => $from.' ～ '.$to], null];
    }

    /**
     * ajax_staffTotal.php: por día (売上 = filas 合計, スタッフ歩合 = rank de la fila スタッフ), por cliente y por menú.
     *
     * @return array{byDay: Collection, byCustomer: Collection, byMenu: Collection, salesTotal: float, commissionTotal: float}
     */
    public function staffTotals(string $staffCd, array $period): array
    {
        $byDay = $this->staffBase($staffCd, $period, SaleService::FORM_TOTAL)
            ->groupBy('tb1.update_date')->orderBy('tb1.update_date')
            ->selectRaw('tb1.update_date AS business_date, SUM(tb2.float_data) AS price_total, SUM(tb1.rank) AS buai_total')
            ->get();

        $byCustomer = $this->staffBase($staffCd, $period, SaleService::FORM_TOTAL)
            ->join('tb_customer as tb3', 'tb1.tb_customer_cd', '=', 'tb3.cd')
            ->groupBy('tb1.tb_customer_cd', 'tb3.name')->orderBy('tb1.tb_customer_cd')
            ->selectRaw('tb1.tb_customer_cd, tb3.name, COUNT(tb2.float_data) AS cnt, SUM(tb2.float_data) AS price_total')
            ->get();

        $byMenu = $this->staffBase($staffCd, $period, SaleService::FORM_MENU)
            ->groupBy('tb2.text_data')->orderBy('tb2.text_data')
            ->selectRaw('tb2.text_data AS menu_name, COUNT(tb2.float_data) AS cnt, SUM(tb2.float_data) AS price_total')
            ->get();

        return [
            'byDay' => $byDay,
            'byCustomer' => $byCustomer,
            'byMenu' => $byMenu,
            'salesTotal' => (float) $byDay->sum('price_total'),
            'commissionTotal' => (float) $byDay->sum('buai_total'),
        ];
    }

    /**
     * ajax_report.php: totales por día y por スタッフ (nombre) de 売上 (-2), 割引 (-3), その他の割引 (-4) y
     * スタッフ歩合 (rank de -1 cruzado con -5), y 経費 (form -10) por día. `$shopCd <= 0` = 全店舗.
     */
    public function periodTotals(int $shopCd, array $period): array
    {
        $days = [];
        $staff = [];
        $totals = ['price_total' => 0.0, 'discount' => 0.0, 'other_discount' => 0.0, 'buai' => 0.0, 'keihi' => 0.0];

        $parts = [
            'price_total' => [SaleService::FORM_MENU, 'price_total'],
            'discount' => [SaleService::FORM_DISCOUNT, 'price_total'],
            'other_discount' => [SaleService::FORM_OTHER_DISCOUNT, 'price_total'],
            'buai' => [SaleService::FORM_TOTAL, 'buai_total'],
        ];

        foreach ($parts as $key => [$form, $column]) {
            $rows = DB::table('tb_customer_sub as tb1')
                ->join('tb_customer_sub as tb2', fn ($join) => $join->on('tb1.tb_customer_cd', '=', 'tb2.tb_customer_cd')
                    ->on('tb1.category_inner_group', '=', 'tb2.category_inner_group')
                    ->where('tb2.tb_search_form_cd', $form))
                ->join('tb_staff as tb3', DB::raw('tb1.array_data::bigint'), '=', 'tb3.cd')
                ->where('tb1.tb_search_form_cd', SaleService::FORM_STAFF)
                ->where('tb1.disp_flg', '1')->where('tb2.disp_flg', '1')
                ->when($shopCd > 0, fn ($q) => $q->where('tb1.int_data', $shopCd))
                ->tap(fn ($q) => $this->wherePeriod($q, $period))
                ->groupBy('tb1.update_date', 'tb3.cd', 'tb3.name')
                ->orderBy('tb1.update_date')->orderBy('tb3.cd')
                ->selectRaw('tb1.update_date AS business_date, tb3.cd, tb3.name, SUM(tb2.float_data) AS price_total, SUM(tb1.rank) AS buai_total')
                ->get();

            foreach ($rows as $row) {
                $value = (float) $row->{$column};
                $days[$row->business_date][$key] = ($days[$row->business_date][$key] ?? 0) + $value;
                $staff[$row->name][$key] = ($staff[$row->name][$key] ?? 0) + $value;
                $totals[$key] += $value;
            }
        }

        $expenses = DB::table('tb_customer_sub as tb1')
            ->where('tb1.tb_search_form_cd', ExpenseService::FORM)->where('tb1.disp_flg', '1')
            ->when($shopCd > 0, fn ($q) => $q->where('tb1.int_data', $shopCd))
            ->tap(fn ($q) => $this->wherePeriod($q, $period))
            ->groupBy('tb1.update_date')->orderBy('tb1.update_date')
            ->selectRaw('tb1.update_date AS business_date, SUM(tb1.float_data) AS price_total')
            ->get();
        foreach ($expenses as $row) {
            $days[$row->business_date]['keihi'] = ($days[$row->business_date]['keihi'] ?? 0) + (float) $row->price_total;
            $totals['keihi'] += (float) $row->price_total;
        }

        // El legacy recorre $dayTotals en el orden en que aparecen (primero los días con ventas); se conserva
        $junRieki = $totals['price_total'] - ($totals['discount'] + $totals['other_discount'] + $totals['keihi']);

        return [
            'days' => $days,
            'staff' => $staff,
            'totals' => $totals,
            'junRieki' => $junRieki,
            'araRieki' => $junRieki - $totals['buai'],
        ];
    }

    /** Base de ajax_staffTotal.php: fila スタッフ (-1) de la スタッフ cruzada con la fila `$form` de la misma cuenta */
    private function staffBase(string $staffCd, array $period, int $form): Builder
    {
        return DB::table('tb_customer_sub as tb1')
            ->join('tb_customer_sub as tb2', fn ($join) => $join->on('tb1.tb_customer_cd', '=', 'tb2.tb_customer_cd')
                ->on('tb1.category_inner_group', '=', 'tb2.category_inner_group')
                ->where('tb2.tb_search_form_cd', $form))
            ->where('tb1.tb_search_form_cd', SaleService::FORM_STAFF)
            ->where('tb1.disp_flg', '1')->where('tb2.disp_flg', '1')
            ->where('tb1.array_data', $staffCd)
            ->tap(fn ($q) => $this->wherePeriod($q, $period));
    }

    private function wherePeriod(Builder $query, array $period): void
    {
        $period['from'] === $period['to']
            ? $query->where('tb1.update_date', $period['from'])
            : $query->whereBetween('tb1.update_date', [$period['from'], $period['to']]);
    }

    /** explode('/') + checkdate del legacy y date('Y/m/d', strtotime()) para normalizar */
    private function normalize(string $date): ?string
    {
        $parts = explode('/', $date);
        if (count($parts) !== 3 || ! checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
            return null;
        }

        return sprintf('%04d/%02d/%02d', (int) $parts[0], (int) $parts[1], (int) $parts[2]);
    }
}
