<?php

namespace App\Services\Customers\Reports;

use App\Models\Staff;
use App\Services\Customers\CustomerSearch;
use App\Support\LegacyCsv;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * csv出力管理画面 (`system/category1/shukei/customerDataOutPut.php`): una fila por visita (`tb_raiten_rireki`)
 * con los datos del cliente (`tb_koumoku` koumoku_select = '1'), sus 追加情報 de registro único (`csv_flg = '1'`),
 * la fecha/hora de la visita y los 追加情報 de varios registros enlazados a esa visita (`category_inner_group`).
 *
 * Decisiones frente al legacy:
 *   - Filtros: los del buscador común (`CustomerSearch`, port de search_set2.php) sobre `search_set1` + カスタマイズ検索.
 *     El legacy tenía aquí una copia propia que rompía la consulta en los campos de texto personalizados
 *     (`cd = any(select  from  where …)`) y, tras buscar, dejaba de exigir `touroku_kubun = '2'`/`disp_flg`.
 *     Aquí siempre: socios (`touroku_kubun = '2'`) y `disp_flg` según 登録区分 (por defecto 登録済), como 顧客検索.
 *   - No master: limitado a la tienda de la sesión (el legacy no filtraba por tienda en esta pantalla).
 *   - 年齢 se calcula por fila (el legacy arrastraba la edad de la fila anterior si la fecha no era válida).
 *   - Orden `cstm.cd DESC, calt.cd` (el legacy solo `cstm.cd DESC`: los bloques LIMIT/OFFSET no eran estables).
 */
class VisitCsvService
{
    /** Opciones de 件づつ (el primero es el valor por defecto) */
    public const LIMITS = [100, 250, 500, 1000, 2500, 5000];

    public function __construct(private CustomerSearch $search) {}

    /** Tamaño de bloque elegido (`csv_limit`), 100 si no es una de las opciones */
    public function limit(mixed $value): int
    {
        return in_array((int) $value, self::LIMITS, true) ? (int) $value : self::LIMITS[0];
    }

    /** Visitas que entran en la búsqueda (`calt` = tb_raiten_rireki, `cstm` = tb_customer) */
    public function query(Staff $staff, array $filters): Builder
    {
        $customers = $this->search->forCategory('category1')
            ->forStaff($staff->isMaster(), $staff->isMaster() ? null : (int) $staff->shop_cd)
            ->query($filters)->select('cd');

        return DB::table('tb_raiten_rireki as calt')
            ->leftJoin('tb_customer as cstm', 'cstm.cd', '=', 'calt.user_cd')
            ->whereRaw("COALESCE(calt.disp_flg, '') <> '9'")
            ->whereIn('calt.user_cd', $customers);
    }

    public function count(Staff $staff, array $filters): int
    {
        return $this->query($staff, $filters)->count();
    }

    /**
     * Columnas del CSV: clave => título, en el orden del legacy.
     *
     * @return array{columns: array<string, string>, customerColumns: list<string>, customForms: array<int, string>}
     */
    public function columns(): array
    {
        $columns = [];
        $customerColumns = [];
        $existing = array_flip(Schema::getColumnListing('tb_customer'));

        // 顧客項目 (tb_koumoku de tb_customer con koumoku_select = '1')
        $koumoku = DB::table('tb_koumoku')->where('tb_name', 'tb_customer')->where('koumoku_select', '1')
            ->orderBy('rank')->orderBy('cd')->get(['tb_koumoku', 'tb_koumoku_name']);
        foreach ($koumoku as $row) {
            $columns[$row->tb_koumoku] = (string) $row->tb_koumoku_name;
            if ($row->tb_koumoku !== 'age' && isset($existing[$row->tb_koumoku])) {
                $customerColumns[] = $row->tb_koumoku;
            }
        }
        // 年齢 necesita kojin_birth aunque no sea columna del CSV
        $customerColumns[] = 'kojin_birth';

        // 追加情報 de registro único con csv_flg = '1'
        $customForms = [];
        $forms = DB::table('tb_search_form as tb2')
            ->rightJoin('tb_search_cate as tb1', 'tb1.cd', '=', 'tb2.tb_search_cate_cd')
            ->where('tb1.category', 'category1')
            ->whereRaw("COALESCE(tb1.disp_flg, '') <> '9'")
            ->where('tb2.csv_flg', '1')
            ->where('tb1.hyouji_flg', '1')
            ->whereRaw("COALESCE(tb1.record_type, '') = ''")
            ->orderBy('tb1.rank')->orderBy('tb2.rank')->orderBy('tb2.cd')
            ->get(['tb2.cd', 'tb2.name', 'tb2.form_type']);
        foreach ($forms as $form) {
            $columns['custom' . $form->cd] = (string) $form->name;
            $customForms[(int) $form->cd] = (string) $form->form_type;
        }

        $columns['raiten_date_ymd'] = '来店日時';
        $columns['raiten_date_hi'] = '来店時間';

        // 追加情報 de varios registros (clave rank_<rank>, como el legacy)
        $multi = DB::table('tb_search_form as tb1')
            ->leftJoin('tb_search_cate as tb2', 'tb2.cd', '=', 'tb1.tb_search_cate_cd')
            ->whereRaw("COALESCE(tb2.record_type, '') <> ''")
            ->whereRaw("COALESCE(tb1.disp_flg, '') <> '9'")
            ->whereIn('tb1.hyouji_flg', ['1', '2'])
            ->where('tb2.hyouji_flg', '1')
            ->where('tb2.category', 'category1')
            ->orderBy('tb1.rank')->orderBy('tb1.cd')
            ->get(['tb1.rank', 'tb1.name']);
        foreach ($multi as $row) {
            $columns['rank_' . $row->rank] = (string) $row->name;
        }

        return ['columns' => $columns, 'customerColumns' => array_values(array_unique($customerColumns)), 'customForms' => $customForms];
    }

    /** Nombre de archivo del legacy: 顧客データ(1件～100件)[2026年05月20日12時00分出力].csv */
    public function filename(int $start, int $end): string
    {
        return '顧客データ(' . $start . '件～' . $end . '件)[' . now('Asia/Tokyo')->format('Y年m月d日H時i分') . '出力].csv';
    }

    /**
     * CSV del bloque `page` de `view` filas (sin `page`: todas). Cabecera sin comillas y celdas `"\t<valor>"`
     * (tabulador para que Excel no convierta números), vacías si el dato no existe, como el legacy.
     */
    public function download(Staff $staff, array $filters, ?int $page, int $view): StreamedResponse
    {
        $meta = $this->columns();
        $query = $this->query($staff, $filters);

        $start = 1;
        $end = null;
        if ($page !== null) {
            $total = (clone $query)->count();
            $start = $view * $page + 1;
            $end = min($view * ($page + 1), max($total, $start - 1));
        } else {
            $end = (clone $query)->count();
        }

        $select = array_map(fn ($c) => "cstm.$c", $meta['customerColumns']);
        $query->select(array_merge($select, [
            'cstm.cd as kaiin_cd', 'calt.user_cd', 'calt.tb_customer_sub_inner_group', 'calt.cd as raiten_cd', 'calt.raiten_time',
        ]))->orderByDesc('cstm.cd')->orderBy('calt.cd');
        if ($page !== null) {
            $query->offset($view * $page)->limit($view);
        }

        $lookups = $this->lookups();
        $columns = $meta['columns'];

        $rows = function () use ($query, $meta, $lookups, $columns) {
            foreach ($query->cursor() as $visit) {
                yield $this->line($this->record((array) $visit, $meta['customForms'], $lookups), $columns, $lookups);
            }
        };

        $header = LegacyCsv::line(array_values($columns), ['quote' => 'none']);

        return response()->streamDownload(function () use ($rows, $header) {
            $buffer = $header;
            $count = 0;
            foreach ($rows() as $line) {
                $buffer .= $line;
                if (++$count % 200 === 0) {
                    echo LegacyCsv::encode($buffer);
                    $buffer = '';
                    flush();
                }
            }
            echo LegacyCsv::encode($buffer);
        }, $this->filename($start, (int) $end), ['Content-Type' => 'text/x-csv; charset=Shift_JIS']);
    }

    /** Fila de datos (clave => valor) con los 追加情報 del cliente y de la visita */
    private function record(array $visit, array $customForms, array $lookups): array
    {
        $time = $visit['raiten_time'] ?? null;
        $isEpoch = is_numeric($time);
        $visit['raiten_date_ymd'] = $isEpoch ? Carbon::createFromTimestamp((int) $time, 'Asia/Tokyo')->format('Y/m/d') : '';
        $visit['raiten_date_hi'] = $isEpoch ? Carbon::createFromTimestamp((int) $time, 'Asia/Tokyo')->format('H:i') : '';
        if (array_key_exists('tb_staff_cd', $visit)) {
            $visit['tb_staff_cd'] = $lookups['staff'][$visit['tb_staff_cd']] ?? null;
        }

        // 追加情報 de registro único (la última fila gana, como el legacy)
        if ($customForms && $visit['kaiin_cd'] !== null) {
            $subs = DB::table('tb_customer_sub')->where('tb_customer_cd', $visit['kaiin_cd'])
                ->whereIn('tb_search_form_cd', array_keys($customForms))
                ->whereRaw("COALESCE(disp_flg, '') <> '9'")->orderBy('cd')
                ->get(['tb_search_form_cd', 'text_data', 'float_data']);
            foreach ($subs as $sub) {
                $visit['custom' . $sub->tb_search_form_cd] = match ($customForms[(int) $sub->tb_search_form_cd]) {
                    '数値テキスト' => $this->number($sub->float_data),
                    'チェックボックス', 'テーブル' => str_replace('<>', "\n", (string) $sub->text_data),
                    default => $sub->text_data,
                };
            }
        }

        // 追加情報 de varios registros enlazados a la visita
        $details = DB::table('tb_customer_sub as detail')
            ->leftJoin('tb_search_form as detail_plus', 'detail_plus.cd', '=', 'detail.tb_search_form_cd')
            ->where('detail.tb_customer_cd', $visit['user_cd'])
            ->where('detail.category_inner_group', (string) $visit['tb_customer_sub_inner_group'])
            ->orderBy('detail.cd')
            ->get(['detail_plus.rank', 'detail_plus.form_type', 'detail.text_data', 'detail.float_data']);
        foreach ($details as $detail) {
            $value = match ($detail->form_type) {
                'メニュー項目' => implode(',', array_map(fn ($cd) => $lookups['menu'][$cd] ?? '', explode('<>', (string) $detail->text_data))),
                '数値テキスト' => $this->number($detail->float_data),
                default => $detail->text_data,
            };
            $visit['rank_' . $detail->rank] = $value;
        }

        return $visit;
    }

    /** Línea del CSV (UTF-8) con el formato de cada columna según su título, como el legacy */
    private function line(array $record, array $columns, array $lookups): string
    {
        $cells = [];
        foreach ($columns as $key => $label) {
            if (! isset($record[$key]) && $label !== '年齢') {
                $cells[] = '';

                continue;
            }
            $value = $record[$key] ?? null;
            $text = match ($label) {
                '会員登録日', '情報変更日' => is_numeric($value) ? Carbon::createFromTimestamp((int) $value, 'Asia/Tokyo')->format('Y/m/d') : '',
                '年齢' => $this->age($record['kojin_birth'] ?? null),
                '顧客グループ' => $lookups['groups'][$value] ?? '',
                '登録区分' => match ((string) $value) {
                    '1' => '登録済み', '9' => '削除済み', '0', '' => '退会済み', default => '',
                },
                'メルマガ配信' => match ((string) $value) {
                    '1' => '配信する', '9' => '不達配信', '0', '' => '配信しない', default => '',
                },
                '登録店舗' => $lookups['shops'][$value] ?? '',
                default => (string) $value,
            };
            $cells[] = "\"\t" . $text . '"';
        }

        return LegacyCsv::line($cells, ['quote' => 'none']);
    }

    /** Edad a hoy desde `kojin_birth` (Y/m/d); vacía si la fecha no es válida */
    private function age(?string $birth): string
    {
        $parts = explode('/', (string) $birth);
        if (count($parts) !== 3 || ! checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
            return '';
        }

        return (string) Carbon::create((int) $parts[0], (int) $parts[1], (int) $parts[2], 0, 0, 0, 'Asia/Tokyo')->age;
    }

    /** float_data como lo imprimía PHP (sin `.0` en los enteros) */
    private function number(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        $float = (float) $value;

        return floor($float) == $float ? (string) (int) $float : (string) $float;
    }

    /** Tablas de nombres: menús, personal, grupos y tiendas */
    private function lookups(): array
    {
        return [
            'menu' => DB::table('tb_yoyaku_koumoku_master')->where('disp_flg', '1')->pluck('name', 'cd')->all(),
            'staff' => DB::table('tb_staff')->pluck('name', 'cd')->all(),
            'groups' => DB::table('tb_customer_group')->pluck('name', 'cd')->all(),
            'shops' => DB::table('tb_shop')->pluck('kaishamei', 'cd')->all(),
        ];
    }
}
