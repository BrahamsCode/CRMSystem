<?php

namespace App\Services\Customers;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ポイント利用一覧 (`system/category1/customer/point_history.php`): movimientos de `tb_point_log` (visibles) con el
 * cliente, su tienda de registro, el personal y el terminal; filtros del buscador y CSV en SJIS-win.
 *
 * search_type: 1 全顧客表示 (por defecto), 2 ポイント加算日ごとに表示 (filtra 付与ポイント = sounyu_point),
 * 3 ポイント利用日ごとに表示 (filtra 利用前ポイント = shori_mae_point, como el 1).
 */
class PointHistoryService
{
    /** Claves del buscador (las que el legacy reenviaba en la paginación, más las que olvidaba) */
    public const FILTERS = [
        'search_type', 'shop_cd', 'from_point_year', 'from_point_month', 'from_point_day', 'to_point_year', 'to_point_month',
        'to_point_day', 'before_point_from', 'before_point_to', 'add_point_from', 'add_point_to', 'after_point_from',
        'after_point_to', 'texto', 'use_point_from', 'use_point_to',
    ];

    /** Motivo (ポイント利用種別) como el CASE del SELECT legacy */
    private const REASON = <<<'SQL'
        CASE
            WHEN tb1.action_flg LIKE '%mycolle:pointUse%' THEN split_part( tb1.action_flg, ':', 4 )::text
            WHEN tb1.action_flg LIKE '%introduce%' THEN 'お友達紹介で'
            WHEN tb1.action_flg LIKE '%signUp%' THEN '新規会員登録で'
            WHEN tb1.action_flg LIKE '7' THEN '失効処理でポイント失効'
            WHEN tb1.sounyu_point > 0 THEN 'ポイント追加'
            WHEN tb1.shouhi_point > 0 THEN 'ポイント消費'
            ELSE 'その他'
        END
        SQL;

    /** El WHERE del legacy decía 「執行」 en vez de 「失効」 para el filtro por texto: se conserva */
    private const REASON_FILTER = <<<'SQL'
        CASE
            WHEN tb1.action_flg LIKE '%mycolle:pointUse%' THEN split_part( tb1.action_flg, ':', 4 )::text
            WHEN tb1.action_flg LIKE '%introduce%' THEN 'お友達紹介で'
            WHEN tb1.action_flg LIKE '%signUp%' THEN '新規会員登録で'
            WHEN tb1.action_flg LIKE '7' THEN '失効処理でポイント執行'
            WHEN tb1.sounyu_point > 0 THEN 'ポイント追加'
            WHEN tb1.shouhi_point > 0 THEN 'ポイント消費'
            ELSE 'その他'
        END
        SQL;

    private const USE_POINT = <<<'SQL'
        CASE
            WHEN tb1.action_flg LIKE '%mycolle:pointUse%' THEN tb1.shouhi_point
            WHEN tb1.sounyu_point > 0 THEN tb1.sounyu_point
            WHEN tb1.shouhi_point > 0 THEN tb1.shouhi_point
            ELSE 0
        END
        SQL;

    private const AFTER_POINT = '(tb1.shori_mae_point + COALESCE( tb1.sounyu_point, 0 ) - COALESCE( tb1.shouhi_point, 0 ) )';

    /** Filtros recibidos (search_type = 1 si no llega) */
    public function filters(array $input): array
    {
        $filters = [];
        foreach (self::FILTERS as $key) {
            $value = $input[$key] ?? null;
            $filters[$key] = is_array($value) ? '' : trim((string) $value);
        }
        if (! array_key_exists('search_type', $input)) {
            $filters['search_type'] = '1';
        }

        return $filters;
    }

    /** Consulta del $list_sql legacy con los filtros */
    public function query(array $f): Builder
    {
        $query = DB::table('tb_point_log as tb1')
            ->leftJoin('tb_staff as tb2', 'tb1.tb_staff_cd', '=', 'tb2.cd')
            ->leftJoin('tb_terminal as tb3', 'tb1.tb_terminal_cd', '=', 'tb3.cd')
            ->join('tb_customer', 'tb_customer.cd', '=', 'tb1.tb_customer_cd')
            ->join('tb_shop', 'tb_shop.cd', '=', 'tb_customer.tb_shop_cd')
            ->whereRaw("coalesce(tb1.disp_flg, '') <> '9'")
            ->selectRaw('tb1.cd, tb1.tb_shop_cd, tb1.tb_staff_cd, tb1.tb_customer_cd, tb1.tb_terminal_cd, tb1.touroku_date,
                tb1.shori_mae_point, tb1.kaikei, tb1.sashihiki_kaikei, tb1.shiharai_houhou, tb1.percentage, tb1.shouhi_point,
                tb1.jidou_point, tb1.sounyu_point, tb1.meisai_mail, tb1.raiten_shori, tb1.action_flg, tb2.name AS staff_name,
                tb3.terminal_no, tb_customer.login_id, tb_customer.name AS customer_name, tb_customer.tel AS customer_tel,
                tb_shop.kaishamei AS shop_name, ' . self::AFTER_POINT . ' AS shori_ato_point, '
                . self::REASON . ' AS point_reason, ' . self::USE_POINT . ' AS use_point')
            ->orderByDesc('tb1.touroku_date')->orderByDesc('tb1.cd');

        // Números > 0 (el legacy ignoraba vacíos, 0 y texto)
        $positive = fn (string $key) => is_numeric($f[$key] ?? '') && (float) $f[$key] > 0 ? $f[$key] + 0 : null;

        if (($shop = $positive('shop_cd')) !== null) {
            $query->where('tb_shop.cd', (int) $shop);
        }

        [$fy, $fm, $fd] = [$positive('from_point_year'), $positive('from_point_month'), $positive('from_point_day')];
        if ($fy !== null) {
            $from = Carbon::create((int) $fy, $fm !== null ? (int) $fm : 1, $fm !== null && $fd !== null ? (int) $fd : 1, 0, 0, 0, 'Asia/Tokyo');
            $query->where('tb1.touroku_date', '>=', $from->getTimestamp());
        }

        [$ty, $tm, $td] = [$positive('to_point_year'), $positive('to_point_month'), $positive('to_point_day')];
        if ($ty !== null && $tm !== null && $td !== null) {
            $query->where('tb1.touroku_date', '<=', Carbon::create((int) $ty, (int) $tm, (int) $td, 23, 59, 59, 'Asia/Tokyo')->getTimestamp());
        } elseif ($ty !== null && $tm !== null) {
            $query->where('tb1.touroku_date', '<', Carbon::create((int) $ty, (int) $tm, 1, 0, 0, 0, 'Asia/Tokyo')->addMonthNoOverflow()->getTimestamp());
        } elseif ($ty !== null) {
            $query->where('tb1.touroku_date', '<', Carbon::create((int) $ty + 1, 1, 1, 0, 0, 0, 'Asia/Tokyo')->getTimestamp());
        }

        if ((string) ($f['search_type'] ?? '') !== '2') {
            $this->range($query, 'tb1.shori_mae_point', $positive('before_point_from'), $positive('before_point_to'));
        } else {
            $this->range($query, 'tb1.sounyu_point', $positive('add_point_from'), $positive('add_point_to'));
        }
        $this->range($query, self::AFTER_POINT, $positive('after_point_from'), $positive('after_point_to'));
        $this->range($query, self::USE_POINT, $positive('use_point_from'), $positive('use_point_to'));

        if (($f['texto'] ?? '') !== '') {
            $query->whereRaw(self::REASON_FILTER . ' LIKE ?', ['%' . addcslashes($f['texto'], '%_\\') . '%']);
        }

        return $query;
    }

    /** CSV (csv_print=1): 'ポイント利用一覧_YmdHis.csv', SJIS-win, líneas separadas por \n; vacío si no hay datos */
    public function csv(array $f): StreamedResponse
    {
        $byGrant = (string) ($f['search_type'] ?? '') === '2';
        $fileName = 'ポイント利用一覧_' . now('Asia/Tokyo')->format('YmdHis') . '.csv';
        $query = $this->query($f);

        return response()->streamDownload(function () use ($query, $byGrant) {
            $first = true;
            foreach ($query->lazy(500) as $row) {
                if ($first) {
                    echo mb_convert_encoding($byGrant
                        ? 'ポイント付与日,ID,名前,電話番号,登録店舗,付与ポイント数,合計ポイント数,ポイント利用種別,利用ポイント数'
                        : 'ID,名前,電話番号,登録店舗,ポイント利用日,利用前ポイント数,利用後ポイント数,ポイント利用種別,利用ポイント数', 'SJIS-win', 'UTF-8');
                    $first = false;
                }
                echo mb_convert_encoding("\n" . implode(',', $this->csvLine((array) $row, $byGrant)), 'SJIS-win', 'UTF-8');
            }
        }, $fileName, ['Content-Type' => 'application/octet-stream']);
    }

    /** @return list<string> */
    private function csvLine(array $row, bool $byGrant): array
    {
        // Como el legacy: los valores con «{» van entre comillas
        foreach ($row as $k => $v) {
            if (is_string($v) && str_contains($v, '{')) {
                $row[$k] = '"' . $v . '"';
            }
        }
        $date = fn (string $format) => is_numeric($row['touroku_date']) ? Carbon::createFromTimestamp((int) $row['touroku_date'], 'Asia/Tokyo')->format($format) : '';
        $n = fn (string $key) => number_format((float) $row[$key]);

        return $byGrant
            ? [$date('Ymd'), $row['login_id'], $row['customer_name'], $row['customer_tel'], $row['shop_name'], $n('sounyu_point'), $n('shori_ato_point'), $row['point_reason'], $n('use_point')]
            : [$row['login_id'], $row['customer_name'], $row['customer_tel'], $row['shop_name'], $date('Y年m月d日'), $n('shori_mae_point'), $n('shori_ato_point'), $row['point_reason'], $n('use_point')];
    }

    private function range(Builder $query, string $expression, int|float|null $from, int|float|null $to): void
    {
        if ($from !== null) {
            $query->whereRaw($expression . ' >= ?', [$from]);
        }
        if ($to !== null) {
            $query->whereRaw($expression . ' <= ?', [$to]);
        }
    }
}
