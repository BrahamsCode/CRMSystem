<?php

namespace App\Services\Operaciones\Working;

use App\Models\Staff;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Búsqueda de スタッフ一覧 / スタッフ勤怠集計一覧 (category5/working): port de system/inc/staff_search_set1.php
 * (formulario, 5 campos por fila) y staff_search_set2.php (filtros por nombre de campo).
 *
 * El legacy guardaba el WHERE en SQL en `$_SESSION['wherestr']` (compartido por las dos pantallas); aquí se guardan
 * las condiciones ya validadas en la sesión y se aplican con el query builder. Solo se filtran los campos que conoce
 * staff_search_set2.php y que además son columnas reales de tb_staff (el legacy concatenaba el nombre en el SQL y
 * fallaba con near_station / traf_section, que no existen).
 */
class WorkingStaffSearch
{
    /** Condiciones y valores del formulario de la última búsqueda (compartidos como $_SESSION['wherestr']) */
    public const SESSION_KEY = 'operaciones.working.search';

    /** Campos que filtra staff_search_set2.php con LIKE '%valor%' */
    private const LIKE_FIELDS = [
        'name_kana', 'name', 'sex', 'bloodtype', 'birthday', 'zip', 'add_kana', 'add', 'add_number', 'add_build_kana',
        'add_build', 'email1', 'email2', 'mmail1', 'mmail2', 'tel', 'fax', 'mtel1', 'mtel2', 'entranceday',
        'employmentsystem', 'officialposition', 'salary', 'paidholiday', 'remarks', 'near_station', 'traf_transport',
        'traf_section', 'traf_charge', 'traf_regularfee', 'traf_remarks',
    ];

    /** 登録区分 → disp_flg */
    private const DISP_FLAGS = ['登録済' => '1', '削除済' => '9', '退会済' => '0'];

    /** @var array<string, true>|null */
    private ?array $columns = null;

    /** Campos del formulario: tb_koumoku de tb_staff con 検索 marcado */
    public function fields(): Collection
    {
        return DB::table('tb_koumoku')->where('tb_name', 'tb_staff')->where('mail_mag_select', '1')->orderBy('cd')->get();
    }

    /**
     * Port de staff_search_set2.php. Sin 登録区分 solo los activos (disp_flg = '1').
     *
     * @return array{conditions: list<array<string, mixed>>, words: array<string, string>}
     */
    public function parse(array $input, ?int $currentYear = null): array
    {
        $currentYear ??= (int) now('Asia/Tokyo')->year;
        $conditions = [];
        $words = [];

        foreach ($input as $key => $value) {
            if (! is_string($key) || ! preg_match('/^[A-Za-z0-9_]+$/', $key) || ! is_scalar($value)) {
                continue;
            }
            $value = trim((string) $value);
            $words[$key] = $value;
            if ($value === '') {
                continue;
            }

            $condition = match (true) {
                $key === 'id' => ['type' => 'suffix', 'column' => 'id', 'value' => $value],
                $key === 'shop_cd', $key === 'pic_name' => ['type' => 'equals', 'column' => $key, 'value' => (string) (int) $value],
                $key === 'pref' => ['type' => 'equals', 'column' => 'pref', 'value' => $value],
                in_array($key, self::LIKE_FIELDS, true) => ['type' => 'like', 'column' => $key, 'value' => $value],
                $key === 'disp_flg' => isset(self::DISP_FLAGS[$value]) ? ['type' => 'equals', 'column' => 'disp_flg', 'value' => self::DISP_FLAGS[$value]] : null,
                // 年齢: por el año de nacimiento (primeros 4 caracteres de birthday), como el legacy
                $key === 'age_1' => ['type' => 'born_before', 'value' => (string) ($currentYear - (int) $value)],
                $key === 'age_2' => ['type' => 'born_after', 'value' => (string) ($currentYear - (int) $value)],
                default => null,
            };
            if ($condition && (! isset($condition['column']) || $this->isColumn($condition['column']))) {
                $conditions[] = $condition;
            }
        }

        if (($input['disp_flg'] ?? '') === '' || ! is_scalar($input['disp_flg'])) {
            $conditions[] = self::activeCondition();
        }

        return ['conditions' => $conditions, 'words' => $words];
    }

    /** Condición por defecto del legacy ("WHERE disp_flg = '1'") */
    public static function activeCondition(): array
    {
        return ['type' => 'equals', 'column' => 'disp_flg', 'value' => Staff::ACTIVE_FLAG];
    }

    /** Consulta de tb_staff con las condiciones (incluye borrados: el disp_flg lo pone la búsqueda) */
    public function query(array $conditions): Builder
    {
        $query = Staff::withDeleted();

        foreach ($conditions as $c) {
            match ($c['type']) {
                'equals' => $query->where($c['column'], $c['value']),
                'suffix' => $query->where($c['column'], 'like', '%'.$this->escapeLike($c['value'])),
                'like' => $query->where($c['column'], 'like', '%'.$this->escapeLike($c['value']).'%'),
                'born_before' => $query->whereRaw("coalesce(substr(birthday,1,4),'0') <= ?", [$c['value']])->where('birthday', '<>', ''),
                'born_after' => $query->whereRaw('substr(birthday,1,4) >= ?', [$c['value']]),
            };
        }

        return $query;
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    private function isColumn(string $column): bool
    {
        $this->columns ??= array_fill_keys(Schema::getColumnListing('tb_staff'), true);

        return isset($this->columns[$column]);
    }
}
