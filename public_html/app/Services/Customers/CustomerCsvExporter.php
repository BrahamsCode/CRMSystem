<?php

namespace App\Services\Customers;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV de 顧客一覧・検索 (legacy `category1/customer/csv_download.php` y `csv_download_ex02.php`,
 * y sus copias Flash `csv_file_out.php` de cada carpeta). Shift_JIS (SJIS-win), CRLF, por bloques.
 *
 * Diferencias con el legacy (anotadas en el informe de S2):
 *   - Las comillas dentro de un valor se duplican (el legacy las dejaba sin escapar y rompía la fila).
 *   - 会員登録日/情報変更日 vacíos salen vacíos (el legacy imprimía 1970/01/01).
 *   - DM用CSV: un valor con coma, comillas o salto de línea va entre comillas (el legacy partía la columna).
 */
class CustomerCsvExporter
{
    public const FORMATS = ['standard', 'ex02', 'template'];

    /** csv_export.php (CSVエクスポート): tablas que se pueden volcar enteras */
    public const DUMP_TABLES = [
        'tb_customer' => '顧客マスタ',
        'tb_customer_group' => '顧客グループマスタ',
        'tb_raiten_douki_master' => '来店動機マスタ',
        'tb_staff' => 'スタッフマスタ',
        'tb_staff_group' => 'スタッフグループマスタ',
    ];

    /** Filas por bloque de lectura */
    private const CHUNK = 500;

    private const CRLF = "\r\n";

    public function download(string $format, Builder $query): StreamedResponse
    {
        return match ($format) {
            'ex02' => $this->ex02($query),
            'template' => $this->template(),
            default => $this->standard($query),
        };
    }

    /**
     * csv_download.php: cabecera con tb_koumoku (tb_customer, koumoku_select = '1', orden cd) más los
     * campos personalizados con csv_flg = '1' de categorías de un solo registro; cada valor `"\t<valor>"`.
     */
    public function standard(Builder $query): StreamedResponse
    {
        $koumoku = DB::table('tb_koumoku')->where('tb_name', 'tb_customer')->where('koumoku_select', '1')
            ->orderBy('cd')->get(['tb_koumoku', 'tb_koumoku_name'])->all();
        $customFields = $this->customFields();
        $lookups = $this->lookups();

        return response()->streamDownload(function () use ($query, $koumoku, $customFields, $lookups): void {
            $title = array_merge(
                array_map(fn ($k) => $this->quote((string) $k->tb_koumoku_name), $koumoku),
                array_map(fn ($f) => $this->quote((string) $f->name), $customFields),
            );
            echo $this->sjis(implode(',', $title).self::CRLF);

            $customFieldCds = array_map(fn ($f) => (int) $f->cd, $customFields);
            foreach ((clone $query)->select('tb_customer.*')->orderBy('tb_customer.name_kana')->orderBy('tb_customer.cd')->lazy(self::CHUNK)->chunk(self::CHUNK) as $rows) {
                $cds = $rows->pluck('cd')->map(fn ($cd) => (int) $cd)->all();
                $points = DB::table('tb_customer_point')->whereIn('tb_customer_cd', $cds)->pluck('service_point', 'tb_customer_cd')->all();
                $customData = $this->customData($cds, $customFields);

                $buffer = '';
                foreach ($rows as $row) {
                    $row = (array) $row;
                    // 2013.05.01 ポイント仕様変更: service_point sale de tb_customer_point
                    $row['service_point'] = $points[$row['cd']] ?? null;

                    $line = [];
                    foreach ($koumoku as $k) {
                        $line[] = $this->quote("\t".$this->standardValue($row, (string) $k->tb_koumoku, (string) $k->tb_koumoku_name, $lookups));
                    }
                    foreach ($customFieldCds as $formCd) {
                        $line[] = $this->quote("\t".($customData[$row['cd']][$formCd] ?? ''));
                    }
                    $buffer .= implode(',', $line).self::CRLF;
                }
                echo $this->sjis($buffer);
                flush();
            }
        }, 'user.csv', ['Content-Type' => 'text/x-csv']);
    }

    /**
     * csv_download_ex02.php (DM用CSV, Aprint): sin cabecera, solo clientes con 郵便番号 o 住所:
     * 名前, 郵便番号, 都道府県+市区町村+丁目・番地, マンション・ビル名, "", "", "", 名前, 様.
     */
    public function ex02(Builder $query): StreamedResponse
    {
        $query = (clone $query)
            ->where(fn (Builder $q) => $q->whereRaw("coalesce(tb_customer.zip, '') <> ''")->orWhereRaw("coalesce(tb_customer.add, '') <> ''"));

        return response()->streamDownload(function () use ($query): void {
            $columns = ['tb_customer.cd', 'tb_customer.name', 'tb_customer.zip', 'tb_customer.pref', 'tb_customer.add', 'tb_customer.add_number', 'tb_customer.add_build'];
            foreach ($query->select($columns)->orderBy('tb_customer.name_kana')->orderBy('tb_customer.cd')->lazy(self::CHUNK)->chunk(self::CHUNK) as $rows) {
                $buffer = '';
                foreach ($rows as $r) {
                    $line = [
                        (string) $r->name,
                        (string) $r->zip,
                        $r->pref.$r->add.$r->add_number,
                        (string) $r->add_build,
                        '', '', '',
                        (string) $r->name,
                        '様',
                    ];
                    $buffer .= implode(',', array_map(fn (string $v) => $this->quoteIfNeeded($v), $line)).self::CRLF;
                }
                echo $this->sjis($buffer);
                flush();
            }
        }, 'CRM_DM_List.csv', ['Content-Type' => 'text/x-csv']);
    }

    /**
     * csv_download_template.php: plantilla de la CSVインポート (columnas, títulos, tipos y aviso; sin datos).
     */
    public function template(): StreamedResponse
    {
        $str = 'kanri_id,name,name_kana,kojin_birth,kojin_sex,kojin_bloodtype,tel,kojin_mobile,email1,email2,zip,pref,add,add_number,add_build,mailmaga_flg,staff_id'.self::CRLF
            .'管理ID,名前,フリガナ,生年月日（○○/○○/○○形式で入力。例：1970/11/19　年がない場合：/10/13）,性別（男性・女性）,血液型（A・B・O・AB）,電話番号（半角数字ハイフン無入力）,携帯番号（半角数字ハイフン無入力）,メール1,メール2,郵便番号（半角数字ハイフン無入力）,都道府県,市区町村,丁目番地,建物名,メルマガ配信（配信無しならば空白、有ならば1入力）,指名担当者（スタッフID。半角数字入力）'.self::CRLF
            .implode(',', array_fill(0, 17, '文字列')).self::CRLF
            .'↑は編集しないでください。追加する商品データは↓から記述してください。'.self::CRLF;

        return response()->streamDownload(function () use ($str): void {
            echo $this->sjis($str);
        }, 'inputCustomerData.csv', ['Content-Type' => 'text/x-csv']);
    }

    /**
     * csv_export.php: volcado de una tabla entera (SELECT * ORDER BY cd) con los nombres de columna como cabecera;
     * cada valor entre comillas con CR, LF, TAB y comillas escapados con «\» y sin caracteres de control.
     * Nombre del archivo: <tabla>_<YmdHis>.csv. La tabla debe estar en DUMP_TABLES.
     */
    public function tableDump(string $table): StreamedResponse
    {
        abort_unless(isset(self::DUMP_TABLES[$table]), 404);

        return response()->streamDownload(function () use ($table): void {
            $first = true;
            foreach (DB::table($table)->orderBy('cd')->lazy(self::CHUNK)->chunk(self::CHUNK) as $rows) {
                $buffer = '';
                foreach ($rows as $row) {
                    $row = (array) $row;
                    if ($first) {
                        $buffer .= implode(',', array_map(fn ($k) => '"'.$k.'"', array_keys($row))).self::CRLF;
                        $first = false;
                    }
                    $buffer .= implode(',', array_map(function ($v) {
                        $v = str_replace(["\r", "\n", "\t", '"'], ['\\r', '\\n', '\\t', '\\"'], (string) $v);

                        return '"'.preg_replace('/[\x00-\x1F\x7F]/', '', $v).'"';
                    }, $row)).self::CRLF;
                }
                echo $this->sjis($buffer);
                flush();
            }
        }, $table.'_'.now('Asia/Tokyo')->format('YmdHis').'.csv', ['Content-Type' => 'text/x-csv']);
    }

    /** Valor de una columna de tb_koumoku con las conversiones del legacy */
    private function standardValue(array $row, string $column, string $label, array $lookups): string
    {
        $value = $row[$column] ?? null;

        return (string) match ($label) {
            '会員登録日', '情報変更日' => $this->epochToDate($value),
            '年齢' => $this->age((string) ($row['kojin_birth'] ?? '')),
            '顧客グループ' => $lookups['groups'][$value] ?? '',
            '指名担当者' => $lookups['staffs'][$value] ?? '',
            '初回来店動機' => $lookups['raitenDoukis'][$value] ?? '',
            '登録店舗' => $lookups['shops'][$value] ?? '',
            default => $value ?? '',
        };
    }

    /** tb_search_form con csv_flg = '1' de categorías visibles de un solo registro (orden del legacy) */
    private function customFields(): array
    {
        return DB::table('tb_search_form as f')
            ->join('tb_search_cate as c', 'f.tb_search_cate_cd', '=', 'c.cd')
            ->where('f.disp_flg', '1')->where('c.disp_flg', '1')->where('c.hyouji_flg', '1')->where('f.csv_flg', '1')
            ->whereRaw("COALESCE(c.record_type, '') = ''")
            ->orderBy('c.rank')->orderBy('f.rank')->orderBy('f.cd')
            ->get(['f.cd', 'f.name', 'f.form_type'])->all();
    }

    /**
     * Valores personalizados de un bloque de clientes: [customer_cd][form_cd] => text_data
     * (float_data si 数値テキスト). Como el LEFT JOIN del legacy, la primera fila de tb_customer_sub.
     */
    private function customData(array $customerCds, array $customFields): array
    {
        if ($customFields === [] || $customerCds === []) {
            return [];
        }
        $numeric = [];
        foreach ($customFields as $f) {
            $numeric[(int) $f->cd] = $f->form_type === '数値テキスト';
        }

        $data = [];
        DB::table('tb_customer_sub')
            ->whereIn('tb_customer_cd', $customerCds)
            ->whereIn('tb_search_form_cd', array_keys($numeric))
            ->orderBy('cd')
            ->get(['tb_customer_cd', 'tb_search_form_cd', 'text_data', 'float_data'])
            ->each(function ($sub) use (&$data, $numeric): void {
                $customerCd = (int) $sub->tb_customer_cd;
                $formCd = (int) $sub->tb_search_form_cd;
                if (! isset($data[$customerCd][$formCd])) {
                    $data[$customerCd][$formCd] = (string) ($numeric[$formCd] ? $sub->float_data : $sub->text_data);
                }
            });

        return $data;
    }

    /** Nombres de 顧客グループ, 指名担当者, 初回来店動機 y 登録店舗 por cd */
    private function lookups(): array
    {
        $names = fn (string $table, string $column) => DB::table($table)->orderBy('cd')->pluck($column, 'cd')->all();

        return [
            'groups' => $names('tb_customer_group', 'name'),
            'staffs' => $names('tb_staff', 'name'),
            'raitenDoukis' => $names('tb_raiten_douki_master', 'name'),
            'shops' => $names('tb_shop', 'kaishamei'),
        ];
    }

    private function epochToDate(mixed $value): string
    {
        if (! is_numeric($value) || (int) $value <= 0) {
            return '';
        }

        return Carbon::createFromTimestamp((int) $value, CustomerSearch::TIMEZONE)->format('Y/m/d');
    }

    /** Edad a hoy (Asia/Tokyo) desde 'Y/m/d'; '' si la fecha no es válida */
    private function age(string $birth): string
    {
        $parts = explode('/', $birth);
        if (count($parts) !== 3 || ! checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
            return '';
        }
        $today = Carbon::now(CustomerSearch::TIMEZONE);
        $age = $today->year - (int) $parts[0];
        if ($today->month < (int) $parts[1] || ($today->month === (int) $parts[1] && $today->day < (int) $parts[2])) {
            $age--;
        }

        return (string) $age;
    }

    private function quote(string $value): string
    {
        return '"'.str_replace('"', '""', $value).'"';
    }

    private function quoteIfNeeded(string $value): string
    {
        return strpbrk($value, ",\"\r\n") === false ? $value : $this->quote($value);
    }

    private function sjis(string $value): string
    {
        return mb_convert_encoding($value, 'SJIS-win', 'UTF-8');
    }
}
