<?php

namespace App\Services\Customers;

use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CSVインポート (legacy `system/category1/customer/csv_import.php`).
 *
 * Formato (el de `csv_download_template.php`), en Shift_JIS (SJIS-win):
 *   fila 1: columnas de tb_customer (+ `staff_id`, que se traduce a tb_staff_cd; `cd` en la 1.ª columna = actualizar)
 *   fila 2: títulos (se ignoran), fila 3: tipo de cada columna (文字列 / 数字), fila 4: aviso; datos desde la fila 5.
 *
 * Cada fila va en su propia transacción con LOCK TABLE tb_customer, como el legacy: una fila que falla se descarta
 * y las demás se guardan. Altas: cd = createHatsuban, id = login_id = createID, login_pass = randomPass(4).
 *
 * Diferencias con el legacy (anotadas en el informe de S2):
 *   - Los nombres de columna se comprueban contra tb_customer (el legacy los metía en el SQL tal cual): una columna
 *     desconocida o repetida hace fallar la fila, igual que fallaba el SQL del legacy.
 *   - 都道府県 (`pref`) se guarda como texto: el legacy lo pasaba por la rama numérica y lo dejaba vacío.
 *   - 「N件 更新しました。」 cuenta solo las filas que existían en la tienda (el legacy contaba cualquier UPDATE sin error).
 */
class CustomerCsvImporter
{
    /** Columnas que el legacy guarda tal cual (sin la conversión numérica de la rama `default`) */
    private const TEXT_COLUMNS = ['kanri_id', 'name', 'add', 'add_number', 'add_build', 'pref'];

    /** Fila con un valor que el SQL del legacy no habría aceptado */
    private const INVALID = "\0invalid";

    public function __construct(private CustomerRegistrationService $registration) {}

    /**
     * @return array{added: int, updated: int, error: string}
     */
    public function import(string $path, int $shopCd): array
    {
        $lines = $this->lines($path);
        $fields = explode(',', $this->utf8(str_replace(["\r", "\n", '"'], '', $lines[0] ?? '')));
        $types = explode(',', $this->utf8(str_replace(["\r", "\n", '"'], '', $lines[2] ?? '')));

        // スタッフデータ: id → cd
        $staff = DB::table('tb_staff')->orderBy('cd')->pluck('cd', 'id')->all();
        $columns = array_flip(Schema::getColumnListing('tb_customer'));
        $recTime = (string) now()->getTimestamp();

        $added = $updated = 0;
        $error = '';

        for ($i = 4; $i < count($lines); $i++) {
            $values = explode(',', $this->utf8(str_replace(["\r", "\n"], '', $lines[$i])));
            $row = $this->row($fields, $types, $values, $staff, $columns);

            if ($row === null) {
                $error .= 'ERROR... '.($i - 3).'番目：名前を入力してください。';

                continue;
            }
            if ($row === false) {
                continue;
            }

            // cd追加の為アップデート対応: 1.ª columna `cd` numérica = actualizar ese cliente de la tienda
            $updateCd = ($fields[0] ?? '') === 'cd' && is_numeric($values[0] ?? null)
                ? mb_convert_kana((string) $values[0], 'n') : null;

            $result = $this->save($row, $updateCd, $shopCd, $recTime);
            if ($result === 'added') {
                $added++;
            } elseif ($result === 'updated') {
                $updated++;
            }
        }

        if ($added === 0 && $updated === 0) {
            $error .= 'アップロードされたファイルは不適切な形式です。';
        }

        return ['added' => $added, 'updated' => $updated, 'error' => $error];
    }

    /**
     * Columnas → valores de una fila, con las conversiones del legacy.
     *
     * @return array<string, mixed>|null|false null = falta el nombre; false = la fila habría fallado en el SQL
     */
    private function row(array $fields, array $types, array $values, array $staff, array $columns): array|null|false
    {
        $data = [];
        $invalid = false;

        foreach ($fields as $j => $field) {
            // trim de espacios normales y de ancho completo
            $value = preg_replace('/^[ 　]*(.*?)[ 　]*$/u', '$1', (string) ($values[$j] ?? ''));
            $numericType = ($types[$j] ?? '') === '数字';

            if ($field === 'name' && $value === '') {
                return null;
            }
            if ($field === 'cd') {
                continue;
            }

            switch ($field) {
                case 'staff_id':
                    $invalid = $invalid || array_key_exists('tb_staff_cd', $data);
                    $data['tb_staff_cd'] = $value !== '' && isset($staff[$value]) ? (int) $staff[$value] : null;

                    continue 2;

                case 'kojin_birth':
                    [$value, $birthDate] = $this->birth($value);
                    $invalid = $invalid || array_key_exists('kojin_birth_date', $data);
                    $data['kojin_birth_date'] = $birthDate;
                    break;

                case 'mailmaga_flg':
                    $value = strtoupper(mb_convert_kana($value, 'a'));
                    if ($this->truthy($value) && $value !== '1') {
                        $value = ''; // '1'以外無効
                    }
                    break;

                case 'kojin_bloodtype':
                    $value = strtoupper(mb_convert_kana($value, 'a'));
                    if ($this->truthy($value) && ! preg_match('/^(A|B|O|AB)$/', $value)) {
                        $value = '';
                    }
                    break;

                case 'kojin_sex':
                    if ($value === '男') {
                        $value = '男性';
                    } elseif ($value === '女') {
                        $value = '女性';
                    } elseif ($this->truthy($value) && ! preg_match('/^(男性|女性)$/u', $value)) {
                        $value = '';
                    }
                    break;

                case 'email1':
                case 'email2':
                    $value = mb_convert_kana($value, 'a');
                    if ($this->truthy($value) && ! preg_match('/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([\.][a-zA-Z0-9\._-]+)+$/', $value)) {
                        $value = '';
                    }
                    break;

                case 'name_kana':
                    $value = mb_convert_kana($value, 'KC');
                    break;

                default:
                    if (! in_array($field, self::TEXT_COLUMNS, true)) {
                        $value = mb_convert_kana($value, 'n'); // 全角数字を半角に変換
                        if (! is_numeric($value)) {
                            $value = $numericType ? null : '';
                        }
                    }
                    break;
            }

            // 数字 se insertaba sin comillas: un texto o un vacío rompía el SQL de la fila
            if ($numericType && $value !== null && ! is_numeric($value)) {
                $value = self::INVALID;
            }

            $invalid = $invalid || ! isset($columns[$field]) || array_key_exists($field, $data) || $value === self::INVALID;
            $data[$field] = $value;
        }

        return $invalid || $data === [] ? false : $data;
    }

    /**
     * 生年月日: 「1970/11/19」 o sin año 「/10/13」. Fecha inválida → 「//」 y kojin_birth_date NULL.
     *
     * @return array{0: string, 1: int|null}
     */
    private function birth(string $value): array
    {
        [$yy, $mm, $dd] = array_pad(explode('/', mb_convert_kana($value, 'a')), 3, null);
        $yy = (string) $yy;
        $mm = (string) $mm;
        $dd = (string) $dd;

        if ($yy !== '' && $mm !== '' && $dd !== '') {
            $error = ! checkdate((int) $mm, (int) $dd, (int) $yy);
        } elseif ((int) $dd < 1 || (int) $mm < 1 || (int) $mm > 12) {
            $error = true;
        } else {
            $error = match ((int) $mm) {
                2 => (int) $dd > 29,
                4, 6, 9, 11 => (int) $dd > 30,
                default => false,
            };
        }

        if ($error) {
            return ['//', null];
        }

        // strtotime("Y/m/d") del legacy: sin año no hay fecha
        $epoch = $yy !== '' && is_numeric($yy)
            ? Carbon::create((int) $yy, (int) $mm, (int) $dd, 0, 0, 0, 'Asia/Tokyo')->getTimestamp()
            : null;

        return [$yy.'/'.sprintf('%02d', (int) $mm).'/'.sprintf('%02d', (int) $dd), $epoch];
    }

    /** Guarda la fila en su propia transacción. @return 'added'|'updated'|null */
    private function save(array $row, ?string $updateCd, int $shopCd, string $recTime): ?string
    {
        try {
            return DB::transaction(function () use ($row, $updateCd, $shopCd, $recTime) {
                DB::statement('LOCK TABLE tb_customer');

                if ($updateCd !== null) {
                    $affected = DB::table('tb_customer')->where('cd', $updateCd)->where('tb_shop_cd', $shopCd)->update($row);

                    return $affected > 0 ? 'updated' : null;
                }

                $id = $this->registration->createId($shopCd);
                DB::table('tb_customer')->insert([
                    'cd' => $this->registration->uniqueCd($shopCd),
                    'tb_shop_cd' => $shopCd,
                    'id' => $id,
                    'login_id' => $id,
                    'login_pass' => $this->registration->randomPassword(),
                ] + $row + [
                    'local_sync_flg' => '',
                    'synchronize_flg' => '',
                    'disp_flg' => '1',
                    'touroku_kubun' => '2',
                    'touroku_date' => $recTime,
                    'update_date' => $recTime,
                ]);

                return 'added';
            });
        } catch (QueryException) {
            return null; // ROLLBACK de la fila, como el legacy
        }
    }

    /** @return list<string> líneas del archivo como file() */
    private function lines(string $path): array
    {
        $lines = @file($path);

        return $lines === false ? [] : $lines;
    }

    private function utf8(string $value): string
    {
        return mb_convert_encoding($value, 'UTF-8', 'SJIS-win');
    }

    /** Valor «verdadero» de PHP para un texto: ni vacío ni '0' */
    private function truthy(string $value): bool
    {
        return $value !== '' && $value !== '0';
    }
}
