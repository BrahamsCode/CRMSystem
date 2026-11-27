<?php

namespace App\Services\Operaciones;

use App\Models\Staff;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Búsqueda de スタッフ一覧 (category5/staff/inc/search_set1.php + search_set2.php).
 *
 * El formulario pinta las filas de tb_koumoku de tb_staff con mail_mag_select = '1'; cada campo
 * lleva un hidden `<campo>_type` que dice cómo se filtra:
 * - between (年齢): campo y campo2 numéricos → BETWEEN; solo campo → >=.
 * - date_hiduke (入社日/退社日): campo = '1' (指定する) y _year/_month/_day [ _to ] → BETWEEN 'Y/m/d' o >=.
 * - yesno: 'no' → vacío o NULL; otro valor → LIKE.
 * - xxx (el resto): columna numérica y valor de dígitos → =; si no, LIKE '%valor%'.
 * Sin 登録区分 (disp_flg) en la búsqueda, solo los activos (disp_flg = '1').
 *
 * En vez de guardar el WHERE en SQL ($_SESSION['category5_wherestr_staff']) se guardan las condiciones
 * ya validadas en la sesión y se aplican con el query builder. Solo se aceptan columnas reales de tb_staff
 * (el legacy concatenaba el nombre del campo en el SQL).
 */
class StaffSearch
{
    /** Condiciones de la última búsqueda (la usan la lista al volver y el CSV) */
    public const SESSION_CONDITIONS = 'operaciones.staff.search_conditions';

    /** Valores del formulario de búsqueda ($_SESSION['category5_serch_words_staff']) */
    public const SESSION_WORDS = 'operaciones.staff.search_words';

    private const NUMERIC_TYPES = ['int8', 'int4', 'int2', 'float4', 'float8'];

    /** @var array<string, string>|null columna => tipo de PostgreSQL */
    private ?array $columns = null;

    /** Campos del formulario de búsqueda: tb_koumoku de tb_staff con 検索 marcado */
    public function fields(): Collection
    {
        return DB::table('tb_koumoku')->where('tb_name', 'tb_staff')->where('mail_mag_select', '1')->orderBy('cd')->get();
    }

    /** Campos del CSV: tb_koumoku de tb_staff con CSV marcado */
    public function csvFields(): Collection
    {
        return DB::table('tb_koumoku')->where('tb_name', 'tb_staff')->where('koumoku_select', '1')->orderBy('cd')->get();
    }

    /**
     * Port de search_set2.php: de la petición saca las condiciones y los valores que vuelve a pintar el formulario.
     *
     * @return array{conditions: list<array<string, mixed>>, words: array<string, string>}
     */
    public function parse(array $input): array
    {
        $conditions = [];
        $words = [];

        foreach ($input as $key => $value) {
            if (! is_string($key) || ! preg_match('/^[A-Za-z0-9_]+$/', $key) || ! is_scalar($value)) {
                continue;
            }
            $type = $input[$key.'_type'] ?? null;
            if (! is_string($type) || $type === '' || ! $this->isColumn($key)) {
                continue;
            }
            $value = (string) $value;

            $condition = match ($type) {
                'between' => $this->between($key, $value, $input, $words),
                'date_hiduke' => $this->dateHiduke($key, $value, $input, $words),
                'yesno' => $this->yesno($key, $value, $words),
                default => $this->plain($key, $value, $words),
            };
            if ($condition) {
                $conditions[] = $condition;
            }
        }

        if (! $this->truthy($input['disp_flg'] ?? null)) {
            $conditions[] = ['type' => 'equals', 'column' => 'disp_flg', 'value' => Staff::ACTIVE_FLAG];
        }

        return ['conditions' => $conditions, 'words' => $words];
    }

    /** Consulta de tb_staff (incluidos los borrados: el filtro de disp_flg lo pone la búsqueda) */
    public function query(array $conditions): Builder
    {
        $query = Staff::withDeleted();

        foreach ($conditions as $c) {
            match ($c['type']) {
                'between' => $query->whereBetween($c['column'], $c['value']),
                'gte' => $query->where($c['column'], '>=', $c['value']),
                'equals' => $query->where($c['column'], $c['value']),
                'like' => $query->where($c['column'], 'like', '%'.$c['value'].'%'),
                'blank' => $query->where(fn ($q) => $q->where($c['column'], '')->orWhereNull($c['column'])),
            };
        }

        return $query;
    }

    private function between(string $key, string $value, array $input, array &$words): ?array
    {
        $to = is_scalar($input[$key.'2'] ?? null) ? (string) $input[$key.'2'] : '';
        $words[$key] = '';
        $words[$key.'2'] = '';

        if ($this->truthy($to) && $this->digits($to) && $this->digits($value)) {
            $words[$key] = $value;
            $words[$key.'2'] = $to;

            return ['type' => 'between', 'column' => $key, 'value' => [(int) $value, (int) $to]];
        }
        if ($this->digits($value)) {
            $words[$key] = $value;

            return ['type' => 'gte', 'column' => $key, 'value' => (int) $value];
        }

        return null;
    }

    private function dateHiduke(string $key, string $value, array $input, array &$words): ?array
    {
        $part = fn (string $suffix, string $default = '') => ($v = is_scalar($input[$key.$suffix] ?? null) ? (string) $input[$key.$suffix] : '') === '' ? $default : $v;

        if ($value !== '1') {
            return null;
        }

        $from = [$part('_year'), $part('_month', '01'), $part('_day', '01')];
        $to = [$part('_year_to'), $part('_month_to', '12'), $part('_day_to', '31')];

        $words[$key] = $value;
        [$words[$key.'_year'], $words[$key.'_month'], $words[$key.'_day']] = $from;

        if ($this->truthy($to[0]) && $this->truthy($to[1]) && $this->truthy($to[2])) {
            [$words[$key.'_year_to'], $words[$key.'_month_to'], $words[$key.'_day_to']] = $to;

            return ['type' => 'between', 'column' => $key, 'value' => [implode('/', $from), implode('/', $to)]];
        }

        return ['type' => 'gte', 'column' => $key, 'value' => implode('/', $from)];
    }

    private function yesno(string $key, string $value, array &$words): ?array
    {
        $words[$key] = $value;

        return match ($value) {
            'no' => ['type' => 'blank', 'column' => $key],
            '' => null,
            default => ['type' => 'like', 'column' => $key, 'value' => $value],
        };
    }

    private function plain(string $key, string $value, array &$words): ?array
    {
        $words[$key] = '';
        if (in_array($this->columns()[$key], self::NUMERIC_TYPES, true)) {
            if (! $this->digits($value)) {
                return null;
            }
            $words[$key] = $value;

            return ['type' => 'equals', 'column' => $key, 'value' => (int) $value];
        }
        if ($value === '') {
            return null;
        }
        $words[$key] = $value;

        return ['type' => 'like', 'column' => $key, 'value' => $value];
    }

    private function isColumn(string $key): bool
    {
        return isset($this->columns()[$key]);
    }

    /** @return array<string, string> */
    private function columns(): array
    {
        return $this->columns ??= collect(Schema::getColumns('tb_staff'))
            ->mapWithKeys(fn (array $c) => [$c['name'] => $c['type_name']])
            ->all();
    }

    private function digits(string $value): bool
    {
        return preg_match('/^[0-9]+$/', $value) === 1;
    }

    /** Verdad de PHP para un valor de la petición: '' y '0' son falsos */
    private function truthy(mixed $value): bool
    {
        return is_scalar($value) && (bool) $value;
    }
}
