<?php

namespace App\Services\Terminales;

use App\Models\Customers\Customer;
use App\Models\Integraciones\UrlTransferData;
use App\Models\Terminales\PaperCard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 集計 de ペーパーサイネージ (card_signage/aggregate.php y csv_output.php) sobre los accesos a /ptag/<id>
 * (url_transfer_data, modelo de S5) cruzados con tb_customer (modelo de S2).
 *
 * UrlTransferData todavía no tiene la relación con Customer (S5 la agrega cuando S2 lo publique): se une por
 * `tb_customer_cd = tb_customer.cd` con el nombre de tabla del modelo. Se mantienen los criterios del legacy,
 * que no son homogéneos: el resumen cuenta como socio cualquier tb_customer_cd no nulo; el gráfico, la
 * clasificación y el CSV solo los clientes con disp_flg = '1' y touroku_kubun = '2' (本登録).
 */
class PaperCardStats
{
    public const SEX_MALE = '男性';

    public const SEX_FEMALE = '女性';

    public const SEX_UNKNOWN = '性別未登録';

    public const GUEST = '非会員';

    /** Accesos de la tarjeta (url_transfer_data.data_id = tb_url_list.id, sin filtrar disp_flg como el legacy) */
    private function accesses(PaperCard $card): Builder
    {
        return UrlTransferData::query()->from('url_transfer_data as tb1')->where('tb1.data_id', (string) $card->id);
    }

    private function customerTable(): string
    {
        return (new Customer)->getTable();
    }

    /** Fila 合計/男性/女性/性別未登録/非会員; null si no hay accesos (el legacy dejaba las celdas vacías) */
    public function summary(PaperCard $card): ?array
    {
        $row = $this->accesses($card)
            ->leftJoin($this->customerTable().' as tb3', 'tb3.cd', '=', 'tb1.tb_customer_cd')
            ->selectRaw('count(*) as count')
            ->selectRaw("sum(case when tb3.kojin_sex = ? and tb1.tb_customer_cd is not null then 1 else 0 end) as man", [self::SEX_MALE])
            ->selectRaw("sum(case when tb3.kojin_sex = ? and tb1.tb_customer_cd is not null then 1 else 0 end) as woman", [self::SEX_FEMALE])
            ->selectRaw("sum(case when coalesce(tb3.kojin_sex, '') = '' and tb1.tb_customer_cd is not null then 1 else 0 end) as other")
            ->selectRaw('sum(case when tb1.tb_customer_cd is null then 1 else 0 end) as guest')
            ->toBase()
            ->first();

        return $row && (int) $row->count > 0 ? (array) $row : null;
    }

    /**
     * Accesos por hora (0..23) de cada grupo del gráfico: 男性, 女性, 性別未登録 y 非会員
     * (非会員 = sin cliente 本登録 vigente).
     *
     * @return array<string, list<int>>
     */
    public function hourly(PaperCard $card): array
    {
        $rows = $this->accesses($card)
            ->leftJoin($this->customerTable().' as cst', function ($join) {
                $join->on('cst.cd', '=', 'tb1.tb_customer_cd')
                    ->where('cst.disp_flg', '1')
                    ->where('cst.touroku_kubun', Customer::MEMBER_KUBUN);
            })
            ->selectRaw('case when cst.cd is null then ? when cst.kojin_sex = ? then ? when cst.kojin_sex = ? then ? else ? end as grp',
                [self::GUEST, self::SEX_MALE, self::SEX_MALE, self::SEX_FEMALE, self::SEX_FEMALE, self::SEX_UNKNOWN])
            ->selectRaw("date_part('hour', tb1.access)::int as hour")
            ->selectRaw('count(*) as total')
            ->groupBy('grp', 'hour')
            ->toBase()
            ->get();

        $series = array_fill_keys([self::SEX_MALE, self::SEX_FEMALE, self::SEX_UNKNOWN, self::GUEST], array_fill(0, 24, 0));
        foreach ($rows as $row) {
            $series[$row->grp][(int) $row->hour] = (int) $row->total;
        }

        return $series;
    }

    /** Clientes 本登録 vigentes con más accesos a la tarjeta (id, name, count, cd), paginados por kensu */
    public function ranking(PaperCard $card, int $page, int $kensu, string $path): LengthAwarePaginator
    {
        $counts = $this->accesses($card)
            ->join($this->customerTable().' as tb3', 'tb3.cd', '=', 'tb1.tb_customer_cd')
            ->selectRaw('tb3.cd, count(*) as count')
            ->groupBy('tb3.cd')
            ->toBase();

        $query = Customer::query()->withoutGlobalScopes()
            ->from($this->customerTable().' as ct')
            ->joinSub($counts, 'foo', 'foo.cd', '=', 'ct.cd')
            ->where('ct.disp_flg', '1')
            ->where('ct.touroku_kubun', Customer::MEMBER_KUBUN)
            ->select('ct.cd', 'ct.id', 'ct.name', 'foo.count')
            ->orderByDesc('foo.count')
            ->orderBy('ct.cd');

        $total = (clone $query)->toBase()->getCountForPagination();
        $page = max(1, min($page, (int) ceil($total / $kensu)));

        return new LengthAwarePaginator($query->forPage($page, $kensu)->get(), $total, $kensu, $page, ['path' => $path]);
    }

    /**
     * csv_output.php: アクセス時間,顧客ID,カードID de todos los accesos (o de una tarjeta), en Shift_JIS
     * (SJIS-win) como el legacy, con StreamedResponse. Sin helper común de CSV en app/Support todavía.
     */
    public function csv(?int $cardCd): StreamedResponse
    {
        $query = DB::table('url_transfer_data as data')
            ->leftJoin($this->customerTable().' as cst', function ($join) {
                $join->on('cst.cd', '=', 'data.tb_customer_cd')
                    ->where('cst.disp_flg', '1')
                    ->where('cst.touroku_kubun', Customer::MEMBER_KUBUN);
            })
            ->when($cardCd > 0, fn ($q) => $q->join((new PaperCard)->getTable().' as ul', function ($join) use ($cardCd) {
                $join->on('ul.id', '=', 'data.data_id')->where('ul.cd', $cardCd);
            }))
            ->selectRaw("to_char(data.access, 'YYYY-MM-DD HH24:MI:SS') as access, cst.id, data.data_id")
            ->orderBy('data.access');

        return new StreamedResponse(function () use ($query) {
            echo mb_convert_encoding('アクセス時間,顧客ID,カードID'.PHP_EOL, 'SJIS-win', 'UTF-8');
            foreach ($query->cursor() as $row) {
                echo mb_convert_encoding($row->access.','.$row->id.','.$row->data_id.PHP_EOL, 'SJIS-win', 'UTF-8');
            }
        }, 200, [
            'Content-Type' => 'text/x-csv',
            'Content-Disposition' => 'attachment; filename=shukei.csv',
        ]);
    }
}
