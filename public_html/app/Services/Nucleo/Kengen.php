<?php

namespace App\Services\Nucleo;

use App\Models\Staff;
use Illuminate\Support\Facades\DB;

/**
 * Matriz de permisos del legacy (`class/cKengen.php`, `$kengen->myKengen[cd][1..4]`):
 * `tb_kengen` (grupo × contenido) unido con `tb_staff.tb_staff_group_cd`, solo grupos con
 * `disp_flg = '1'` y contenidos con `hyouji_flg = '1'`. Funciones: 1 閲覧, 2 新規, 3 編集, 4 削除.
 *
 * Igual que el legacy, un staff sin ninguna fila tiene TODOS los permisos (fallback abierto;
 * cambiarlo es decisión pendiente de Antony). `master` lo resuelve Gate::before.
 */
class Kengen
{
    public const VIEW = 1;

    public const CREATE = 2;

    public const UPDATE = 3;

    public const DELETE = 4;

    /** @var array<int, array<int, array<int, bool>>> matriz por staff cd */
    private array $cache = [];

    public function allows(Staff $staff, int $contentsCd, int $function): bool
    {
        return $this->matrix($staff)[$contentsCd][$function] ?? false;
    }

    /** @return array<int, array<int, bool>> [contents_cd => [1 => bool, 2 => bool, 3 => bool, 4 => bool]] */
    public function matrix(Staff $staff): array
    {
        return $this->cache[$staff->cd] ??= $this->load($staff);
    }

    private function load(Staff $staff): array
    {
        $rows = DB::table('tb_kengen as tb1')
            ->join('tb_kengen_contents as tb2', 'tb1.tb_kengen_contents_cd', '=', 'tb2.cd')
            ->join('tb_staff as tb3', 'tb1.tb_staff_group_cd', '=', 'tb3.tb_staff_group_cd')
            ->join('tb_staff_group as tb4', 'tb1.tb_staff_group_cd', '=', 'tb4.cd')
            ->where('tb3.cd', $staff->cd)
            ->where('tb4.disp_flg', '1')
            ->where('tb2.hyouji_flg', '1')
            ->get(['tb1.tb_kengen_contents_cd', 'tb1.etsuran_flg', 'tb1.shinki_flg', 'tb1.shusei_flg', 'tb1.sakujo_flg']);

        $matrix = [];
        if ($rows->isEmpty()) {
            foreach (DB::table('tb_kengen_contents')->orderBy('cd')->pluck('cd') as $cd) {
                $matrix[(int) $cd] = [1 => true, 2 => true, 3 => true, 4 => true];
            }

            return $matrix;
        }

        foreach ($rows as $row) {
            $matrix[(int) $row->tb_kengen_contents_cd] = [
                1 => (string) $row->etsuran_flg === '1',
                2 => (string) $row->shinki_flg === '1',
                3 => (string) $row->shusei_flg === '1',
                4 => (string) $row->sakujo_flg === '1',
            ];
        }

        return $matrix;
    }
}
