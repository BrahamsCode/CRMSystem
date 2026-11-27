<?php

namespace App\Services\Promotions;

use App\Models\Shop;
use App\Support\LegacyCsv;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV de 来店履歴管理, en SJIS-win con LegacyCsv:
 * - csv_download.php (user.csv): visitas de una tienda entre start_time y end_time, con dos líneas de título y
 *   cada celda como `"\t<valor>"` (el tabulador evita que Excel convierta los números).
 * - storecsv_download.php (store.csv): la tabla de 個別来店履歴 (tiendas × franjas). El legacy solo escribía 今日
 *   y lo mandaba en UTF-8 con `text/x-csv;euc-jp`; aquí los tres periodos en SJIS-win.
 * - daycsv_download.php (day.csv): vacío en el legacy; se implementa con la tabla de day_visit_list.php
 *   (mismo formato que store.csv).
 */
class VisitCsvService
{
    public function __construct(private readonly VisitStatsService $stats) {}

    /** csv_download.php */
    public function visits(int $shopCd, int $start, int $end, ?string $selectedDay, string $kikan): StreamedResponse
    {
        $shopName = (string) Shop::withDeleted()->where('cd', $shopCd)->value('kaishamei');

        $title = '';
        if (is_numeric($selectedDay)) {
            $d = VisitStatsService::date((int) $selectedDay);
            $title = match ($kikan) {
                '0' => $d->format('Y年m月d日'),
                '1' => $d->format('Y年m月'),
                '2' => $d->format('Y年'),
                '3' => '過去',
                default => '',
            };
        } elseif ($kikan === '3') {
            $title = '過去';
        }

        $tab = fn (array $cells) => array_map(fn ($v) => "\t".$v, $cells);

        $rows = function () use ($shopCd, $start, $end, $shopName, $title, $tab) {
            yield [$shopName];
            yield [$title.'来店'];
            yield $tab(['ユーザID', '来店日時', '年齢', '性別', '職業']);

            $query = $this->stats->visits($shopCd, $start, $end)->toBase()
                ->select(['user_id', 'raiten_time', 'customer_age', 'customer_sex', 'customer_job'])
                ->orderByRaw(VisitStatsService::TIME_SQL.' DESC');
            foreach ($query->cursor() as $row) {
                yield $tab([
                    $row->user_id,
                    is_numeric($row->raiten_time) ? VisitStatsService::date((int) $row->raiten_time)->format('Y/m/d H:i') : '',
                    $row->customer_age,
                    $row->customer_sex,
                    $row->customer_job,
                ]);
            }
        };

        return LegacyCsv::download('user.csv', $rows);
    }

    /** storecsv_download.php */
    public function shops(int $kikan, ?int $selectedDay): StreamedResponse
    {
        $table = $this->stats->shopTable($kikan, $selectedDay);

        $rows = [array_merge([''], array_column($table['columns'], 0), ['小計'])];
        foreach ($table['rows'] as $row) {
            $rows[] = array_merge([$row['name']], $row['counts'], [$row['total']]);
        }
        $rows[] = array_merge(['合計'], $table['totals'], [$table['total']]);

        return LegacyCsv::download('store.csv', $rows, null, ['quote' => 'none']);
    }

    /** daycsv_download.php: la tabla 来客数一覧 de day_visit_list.php */
    public function days(array $data): StreamedResponse
    {
        $table = $data['table'];
        $rows = [];

        if ($data['kikan'] !== 2) {
            $hours = array_map(fn ($i) => $i < 24 ? $i : $i - 24, range(VisitStatsService::START_HOUR, VisitStatsService::START_HOUR + 23));
            $rows[] = array_merge([''], $hours, [$data['kikan'] === 1 ? '小計' : '合計']);
            foreach ($table['days'] as $day) {
                $rows[] = array_merge([$day['date']], $day['counts'], [$day['total']]);
            }
            if ($data['kikan'] === 1) {
                $rows[] = array_merge(['合計'], $table['hourTotals'], [$table['total']]);
            }
        } else {
            $rows[] = array_merge([''], array_map(fn ($i) => $i.'日', range(1, 31)), ['小計']);
            foreach ($table['months'] as $month) {
                $rows[] = array_merge([$month['month'].'月'], array_map(fn ($v) => $v ?? '', array_values($month['counts'])), [$month['total']]);
            }
            $rows[] = array_merge(['合計'], array_values($table['dayTotals']), [$table['total']]);
        }

        return LegacyCsv::download('day.csv', $rows, null, ['quote' => 'none']);
    }
}
