<?php

namespace App\Http\Controllers\Admin\Reservations;

use App\Http\Controllers\Controller;
use App\Services\Nucleo\LegacyUi;
use App\Support\LegacyCsv;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 予約機種一覧 (legacy system/category3/reservation/model_reservation.php): lista de `tb_change_model` con el socio,
 * su tienda y sus puntos (solo lectura: nadie del repositorio escribe la tabla) y su CSV (csv_print=1).
 */
class ModelChangeController extends Controller
{
    /** ご希望予約時期 → segundos que se suman a touroku_date (該当する予約時期) */
    private const OFFSETS = [
        '今すぐ予約' => 0,
        '３カ月後に予約したい' => 7776000,
        '６カ月後に予約したい' => 15552000,
        '１年後に予約したい' => 31536000,
        '２年後に予約したい' => 63072000,
    ];

    public function index(Request $request, LegacyUi $legacyUi): View|StreamedResponse
    {
        if ((string) $request->input('csv_print') === '1') {
            return $this->csv();
        }

        $kensu = (int) $request->input('kensu');
        $kensu = $kensu > 0 ? $kensu : 20;
        $total = $this->query()->count();
        $maxPage = $total > 0 ? (int) ceil($total / $kensu) : 0;
        $page = min(max(1, (int) $request->input('page', 1)), max(1, $maxPage));

        return view('admin.reservas.model_changes', [
            'rows' => $this->rows($this->query()->offset(($page - 1) * $kensu)->limit($kensu)),
            'total' => $total,
            'kensu' => $kensu,
            'page' => $page,
            'maxPage' => $maxPage,
            'siteName' => $legacyUi->siteName(),
        ]);
    }

    /** CSV 予約機種一覧_<YmdHis>.csv en SJIS-win (cabecera solo si hay filas, como el legacy) */
    private function csv(): StreamedResponse
    {
        $fileName = '予約機種一覧_' . now('Asia/Tokyo')->format('YmdHis') . '.csv';

        return response()->streamDownload(function () {
            $first = true;
            foreach ($this->query()->lazy(500) as $row) {
                $row = $this->row($row);
                $line = $first ? "ID,名前,電話番号,登録店舗,予約申込日,ご希望予約時期,該当する予約時期,希望機種,保有ポイント数\n" : "\n";
                $first = false;
                // El legacy solo entrecomillaba los valores con «{»
                $cells = array_map(fn ($v) => str_contains((string) $v, '{') ? '"' . $v . '"' : (string) $v, [
                    $row['customer_cd'], $row['customer_name'], $row['customer_tel'], $row['shop_name'], $row['touroku'],
                    $row['resevation_type'], $row['resevation'], $row['next_model'], $row['service_point'],
                ]);
                echo LegacyCsv::encode($line . implode(',', $cells));
            }
        }, $fileName, ['Content-Type' => 'application/octet-stream']);
    }

    /** INNER JOIN tb_customer (cd texto), tb_shop y tb_customer_point; ORDER BY touroku_date DESC */
    private function query(): Builder
    {
        return DB::table('tb_change_model')
            ->join('tb_customer', 'tb_change_model.tb_customer_cd', '=', DB::raw('tb_customer.cd::text'))
            ->join('tb_shop', 'tb_shop.cd', '=', 'tb_customer.tb_shop_cd')
            ->join('tb_customer_point', 'tb_customer_point.tb_customer_cd', '=', 'tb_customer.cd')
            ->select([
                'tb_change_model.*',
                'tb_customer.login_id as customer_cd',
                'tb_customer.name as customer_name',
                'tb_customer.tel as customer_tel',
                'tb_shop.kaishamei as shop_name',
                'tb_customer_point.service_point',
            ])
            ->orderByDesc('tb_change_model.touroku_date')
            ->orderByDesc('tb_change_model.cd');
    }

    private function rows(Builder $query): array
    {
        return $query->get()->map(fn ($r) => $this->row($r))->all();
    }

    /** Fila con las fechas ya formateadas ('Y年m月d日'); sin fecha válida, vacío (sin el 1970 del legacy) */
    private function row(object $r): array
    {
        $touroku = is_numeric($r->touroku_date) ? (int) $r->touroku_date : null;
        $offset = self::OFFSETS[(string) $r->resevation_type] ?? null;
        $format = fn (?int $t) => $t !== null ? Carbon::createFromTimestamp($t, 'Asia/Tokyo')->format('Y年m月d日') : '';

        return [
            'customer_cd' => (string) $r->customer_cd,
            'customer_name' => (string) $r->customer_name,
            'customer_tel' => (string) $r->customer_tel,
            'shop_name' => (string) $r->shop_name,
            'touroku' => $format($touroku),
            'resevation_type' => (string) $r->resevation_type,
            'resevation' => $format($touroku !== null && $offset !== null ? $touroku + $offset : null),
            'next_model' => (string) $r->next_model,
            'service_point' => (int) $r->service_point,
        ];
    }
}
