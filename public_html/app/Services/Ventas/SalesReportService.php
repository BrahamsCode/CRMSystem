<?php

namespace App\Services\Ventas;

use App\Models\Sales\Expense;
use App\Models\Sales\Sale;
use App\Models\Shop;
use App\Models\Staff;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Informes de ventas internas: スタッフ別集計 (ajax_staffTotal.php) y 日報・週報・月報 (ajax_report.php).
 * Agrupan por 営業日 (`update_date` 'Y/m/d' de la fila スタッフ -1) sumando la fila hermana de la cuenta
 * (mismo cliente y 請求番号) del tipo que toque: -2 menús, -3 descuentos, -4 その他の割引, -5 合計.
 */
class SalesReportService
{
    /**
     * 集計期間 como el legacy: las dos fechas → BETWEEN; una sola → ese día; ninguna o mal formada → error.
     * El límite «最大60日分» nunca se aplicó en el legacy y tampoco aquí.
     *
     * @return array{error: string}|array{from: string, to: string, label: string}
     */
    public function period(?string $date1, ?string $date2): array
    {
        $date1 = trim((string) $date1);
        $date2 = trim((string) $date2);

        if ($date1 === '' && $date2 === '') {
            return ['error' => '集計期間を入力してください。'];
        }

        if ($date1 === '' || $date2 === '') {
            $day = self::normalize($date1 !== '' ? $date1 : $date2);

            return $day === null ? ['error' => '日付：不適切な形式です。'] : ['from' => $day, 'to' => $day, 'label' => $day];
        }

        $from = self::normalize($date1);
        $to = self::normalize($date2);
        if ($from === null || $to === null) {
            return ['error' => '日付：不適切な形式です。'];
        }

        return ['from' => $from, 'to' => $to, 'label' => $from.' ～ '.$to];
    }

    /** 'Y/m/d' si es una fecha válida 'Y/n/j' (explode('/') + checkdate del legacy) */
    private static function normalize(string $date): ?string
    {
        $parts = explode('/', $date);
        if (count($parts) !== 3 || ! checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
            return null;
        }

        return sprintf('%04d/%02d/%02d', (int) $parts[0], (int) $parts[1], (int) $parts[2]);
    }

    /** tb1 = fila スタッフ (-1) vigente; tb2 = fila hermana vigente del form_cd pedido */
    private function salesJoin(int $siblingForm, array $period): Builder
    {
        return DB::table('tb_customer_sub as tb1')
            ->join('tb_customer_sub as tb2', function ($join) use ($siblingForm) {
                $join->on('tb1.tb_customer_cd', '=', 'tb2.tb_customer_cd')
                    ->on('tb1.category_inner_group', '=', 'tb2.category_inner_group')
                    ->where('tb2.tb_search_cate_cd', Sale::CATEGORY)
                    ->where('tb2.tb_search_form_cd', $siblingForm);
            })
            ->where('tb1.tb_search_cate_cd', Sale::CATEGORY)
            ->where('tb1.tb_search_form_cd', Sale::FORM_STAFF)
            ->where('tb1.disp_flg', '1')
            ->where('tb2.disp_flg', '1')
            ->whereBetween('tb1.update_date', [$period['from'], $period['to']]);
    }

    /**
     * スタッフ別集計 de una スタッフ (de cualquier tienda, como el legacy).
     *
     * @return array{staff: ?Staff, total: int, buai: int, days: list<array>, customers: list<array>, menus: list<array>}
     */
    public function staffTotal(int $staffCd, array $period): array
    {
        $staffFilter = fn (Builder $q) => $q->where('tb1.array_data', (string) $staffCd);

        $days = $this->salesJoin(Sale::FORM_TOTAL, $period)->tap($staffFilter)
            ->groupBy('tb1.update_date')->orderBy('tb1.update_date')
            ->get([DB::raw('tb1.update_date AS business_date'), DB::raw('SUM(tb2.float_data) AS price_total'), DB::raw('SUM(tb1.rank) AS buai_total')])
            ->map(fn ($r) => ['date' => $r->business_date, 'price' => (int) $r->price_total, 'buai' => (int) $r->buai_total])->all();

        $customers = $this->salesJoin(Sale::FORM_TOTAL, $period)->tap($staffFilter)
            ->join('tb_customer as tb3', 'tb1.tb_customer_cd', '=', 'tb3.cd')
            ->groupBy('tb1.tb_customer_cd', 'tb3.name')->orderBy('tb1.tb_customer_cd')
            ->get(['tb1.tb_customer_cd', 'tb3.name', DB::raw('COUNT(tb2.float_data) AS cnt'), DB::raw('SUM(tb2.float_data) AS price_total')])
            ->map(fn ($r) => ['name' => (string) $r->name, 'count' => (int) $r->cnt, 'price' => (int) $r->price_total])->all();

        $menus = $this->salesJoin(Sale::FORM_MENU, $period)->tap($staffFilter)
            ->groupBy('tb2.text_data')->orderBy('tb2.text_data')
            ->get([DB::raw('tb2.text_data AS menu_name'), DB::raw('COUNT(tb2.float_data) AS cnt'), DB::raw('SUM(tb2.float_data) AS price_total')])
            ->map(fn ($r) => ['name' => (string) $r->menu_name, 'count' => (int) $r->cnt, 'price' => (int) $r->price_total])->all();

        return [
            'staff' => Staff::withDeleted()->find($staffCd),
            'total' => array_sum(array_column($days, 'price')),
            'buai' => array_sum(array_column($days, 'buai')),
            'days' => $days,
            'customers' => $customers,
            'menus' => $menus,
        ];
    }

    /**
     * 日報・週報・月報 de una tienda (`$shopCd` > 0) o de todas (-1 / null).
     *
     * @return array{shop_name: string, totals: array, days: array<string, array>, staff: array<string, array>}
     */
    public function periodReport(?int $shopCd, array $period): array
    {
        $byShop = $shopCd !== null && $shopCd > 0;
        $days = [];
        $staff = [];
        $totals = ['price' => 0, 'discount' => 0, 'other_discount' => 0, 'keihi' => 0, 'buai' => 0];

        // [form_cd de la fila hermana, clave, columna sumada]
        foreach ([[Sale::FORM_MENU, 'price', 'price_total'], [Sale::FORM_DISCOUNT, 'discount', 'price_total'],
            [Sale::FORM_MANUAL_DISCOUNT, 'other_discount', 'price_total'], [Sale::FORM_TOTAL, 'buai', 'buai_total']] as [$form, $key, $column]) {
            $rows = $this->salesJoin($form, $period)
                ->join('tb_staff as tb3', DB::raw('tb1.array_data::bigint'), '=', 'tb3.cd')
                ->when($byShop, fn ($q) => $q->where('tb1.int_data', $shopCd))
                ->groupBy('tb1.update_date', 'tb3.cd', 'tb3.name')
                ->orderBy('tb1.update_date')->orderBy('tb3.cd')
                ->get([DB::raw('tb1.update_date AS business_date'), 'tb3.name',
                    DB::raw('SUM(tb2.float_data) AS price_total'), DB::raw('SUM(tb1.rank) AS buai_total')]);

            foreach ($rows as $row) {
                $value = (int) $row->{$column};
                $days[$row->business_date][$key] = ($days[$row->business_date][$key] ?? 0) + $value;
                $staff[(string) $row->name][$key] = ($staff[(string) $row->name][$key] ?? 0) + $value;
                $totals[$key] += $value;
            }
        }

        // 経費 (-10) de la tienda o de todas
        $expenses = DB::table('tb_customer_sub as tb1')
            ->where('tb1.tb_search_cate_cd', Expense::CATEGORY)
            ->where('tb1.tb_search_form_cd', Expense::FORM)
            ->where('tb1.disp_flg', '1')
            ->when($byShop, fn ($q) => $q->where('tb1.int_data', $shopCd))
            ->whereBetween('tb1.update_date', [$period['from'], $period['to']])
            ->groupBy('tb1.update_date')->orderBy('tb1.update_date')
            ->get([DB::raw('tb1.update_date AS business_date'), DB::raw('SUM(tb1.float_data) AS price_total')]);
        foreach ($expenses as $row) {
            $days[$row->business_date]['keihi'] = ($days[$row->business_date]['keihi'] ?? 0) + (int) $row->price_total;
            $totals['keihi'] += (int) $row->price_total;
        }

        // El legacy dejaba los días con solo 経費 al final; aquí van en orden de fecha
        ksort($days, SORT_STRING);
        $days = array_map(fn ($d) => self::withProfit($d), $days);
        $staff = array_map(function ($s) {
            $s += ['price' => 0, 'discount' => 0, 'other_discount' => 0, 'buai' => 0];
            $s['amount'] = $s['price'] - ($s['discount'] + $s['other_discount']);

            return $s;
        }, $staff);

        return [
            'shop_name' => $byShop ? (string) Shop::withDeleted()->whereKey($shopCd)->value('kaishamei') : '全店舗',
            'totals' => self::withProfit($totals) + ['amount' => $totals['price'] - ($totals['discount'] + $totals['other_discount'])],
            'days' => $days,
            'staff' => $staff,
        ];
    }

    /** 純利益 = 売上 − (割引 + その他の割引 + 経費); 粗利 = 純利益 − スタッフ歩合 */
    private static function withProfit(array $row): array
    {
        $row += ['price' => 0, 'discount' => 0, 'other_discount' => 0, 'keihi' => 0, 'buai' => 0];
        $row['jun'] = $row['price'] - ($row['discount'] + $row['other_discount'] + $row['keihi']);
        $row['ara'] = $row['jun'] - $row['buai'];

        return $row;
    }

    /** Filas del CSV de スタッフ別集計 (lo mismo que las tres tablas de la pantalla) */
    public function staffCsvRows(array $report, array $period): array
    {
        $rows = [['スタッフ', (string) $report['staff']?->name, '集計期間', $period['label']],
            ['現在の歩合率', number_format((float) ($report['staff']?->kingaku_buai ?? 0)).'%', '売上', $report['total'], 'スタッフ歩合', $report['buai']],
            [], ['日付', '売上', 'スタッフ歩合']];
        foreach ($report['days'] as $d) {
            $rows[] = [$d['date'], $d['price'], $d['buai']];
        }
        $rows[] = [];
        $rows[] = ['顧客', '件数', '金額'];
        foreach ($report['customers'] as $c) {
            $rows[] = [$c['name'], $c['count'], $c['price']];
        }
        $rows[] = [];
        $rows[] = ['メニュー', '件数', '金額'];
        foreach ($report['menus'] as $m) {
            $rows[] = [$m['name'], $m['count'], $m['price']];
        }

        return $rows;
    }

    /** Filas del CSV de 日報・週報・月報 (tablas 売上 y スタッフ別 de la pantalla) */
    public function periodCsvRows(array $report, array $period): array
    {
        $t = $report['totals'];
        $rows = [[$report['shop_name'], $period['label']], [],
            ['日付', '売上', '割引', 'その他の割引', '経費', '純利益', 'スタッフ歩合', '粗利']];
        foreach ($report['days'] as $day => $d) {
            $rows[] = [$day, $d['price'], $d['discount'], $d['other_discount'], $d['keihi'], $d['jun'], $d['buai'], $d['ara']];
        }
        $rows[] = ['合計', $t['price'], $t['discount'], $t['other_discount'], $t['keihi'], $t['jun'], $t['buai'], $t['ara']];
        $rows[] = [];
        $rows[] = ['スタッフ名', '売上', '割引', 'その他の割引', '金額', 'スタッフ歩合'];
        foreach ($report['staff'] as $name => $s) {
            $rows[] = [$name, $s['price'], $s['discount'], $s['other_discount'], $s['amount'], $s['buai']];
        }
        $rows[] = ['合計', $t['price'], $t['discount'], $t['other_discount'], $t['amount'], $t['buai']];

        return $rows;
    }
}
