<?php

namespace App\Services\Reservations;

use App\Models\ContentsMaster;
use App\Models\Reservations\FreeReservationRank;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * フリー予約設定 (legacy: category9/yoyaku/free_yoyaku.php, free_yoyaku_register.php y ajax_free_yoyaku_rank.php).
 *
 * Ajustes por tienda en `contents_master` (`category = 'tb_c9_yoyaku'`, `int_data` = tienda, valor en `text_data`):
 *   free_yoyaku_disp_flg       フリー予約の使用 ('1' = usar, '' = no)
 *   free_yoyaku_name           フリー予約の表示名 (vacío = 「指名なし」)
 *   free_yoyaku_staff_select   フリー予約使用時の指名選択 ('1' = mostrar)
 *   free_yoyaku_timeline_staff タイムラインのスタッフ名表示 ('1' = mostrar)
 * Orden del personal en `tb_free_yoyaku_rank` (▲▼).
 */
class FreeReservationService
{
    public const CATEGORY = 'tb_c9_yoyaku';

    /** Campo del formulario => `field` de contents_master */
    public const FIELDS = [
        'hyouji_flg' => 'free_yoyaku_disp_flg',
        'name' => 'free_yoyaku_name',
        'staff_select_flg' => 'free_yoyaku_staff_select',
        'timeline_staff_flg' => 'free_yoyaku_timeline_staff',
    ];

    /**
     * Valores actuales de la tienda, con las claves del formulario.
     *
     * @return array<string, ?string>
     */
    public function settings(int $shopCd): array
    {
        $values = [];
        foreach (self::FIELDS as $input => $field) {
            $values[$input] = $this->entry($field, $shopCd)->value('text_data');
        }

        return $values;
    }

    /**
     * Guarda los cuatro ajustes: UPDATE si existe la fila, si no INSERT (como free_yoyaku_register.php).
     *
     * @param  array<string, ?string>  $values claves del formulario
     */
    public function save(int $shopCd, array $values): void
    {
        DB::transaction(function () use ($shopCd, $values) {
            foreach (self::FIELDS as $input => $field) {
                $text = (string) ($values[$input] ?? '');

                if ($this->entry($field, $shopCd)->exists()) {
                    $this->entry($field, $shopCd)->update(['text_data' => $text]);

                    continue;
                }

                ContentsMaster::create([
                    'category' => self::CATEGORY,
                    'field' => $field,
                    'int_data' => $shopCd,
                    'text_data' => $text,
                    'disp_flg' => '1',
                    'hyouji_flg' => '1',
                ]);
            }
        });
    }

    /**
     * Personal de la tienda con menús asignados, en orden de フリー予約. Al personal sin fila de orden
     * se la crea (rango anterior + 1), como hace ajax_free_yoyaku_rank.php al listar.
     *
     * @return Collection<int, object{cd:int, name:?string, rank:int, prev_cd:?int}>
     *   `rank` es el `send_vl` que el legacy pone en ▲▼; `prev_cd` el empleado de la fila anterior.
     */
    public function ranking(int $shopCd): Collection
    {
        return DB::transaction(function () use ($shopCd) {
            $rows = $this->rankQuery($shopCd)
                ->select('tb1.cd', 'tb1.name', 'tb2.rank')
                ->get();

            $lastRank = 0;
            $prevCd = null;

            return $rows->map(function ($row) use ($shopCd, &$lastRank, &$prevCd) {
                if ($row->rank === null) {
                    // Igual que el legacy: se muestra el rango anterior y se guarda el siguiente
                    $shown = $lastRank;
                    $lastRank = $shown + 1;

                    FreeReservationRank::create([
                        'tb_shop_cd' => $shopCd,
                        'tb_staff_cd' => $row->cd,
                        'rank' => $lastRank,
                        'disp_flg' => '1',
                        'hyouji_flg' => '1',
                    ]);
                } else {
                    $shown = (int) $row->rank;
                    $lastRank = $shown;
                }

                $item = (object) ['cd' => (int) $row->cd, 'name' => $row->name, 'rank' => $shown, 'prev_cd' => $prevCd];
                $prevCd = (int) $row->cd;

                return $item;
            });
        });
    }

    /**
     * ▲ (rankup) / ▼ (rankdown): intercambia el rango del empleado con el de arriba (`$prevStaffCd`)
     * o con el siguiente de la tienda. Si coinciden, corrige el rango antes de intercambiar.
     */
    public function move(int $shopCd, string $type, int $targetStaffCd, ?int $prevStaffCd): void
    {
        DB::transaction(function () use ($shopCd, $type, $targetStaffCd, $prevStaffCd) {
            $target = $this->staffRank($targetStaffCd);

            if ($type === 'rankup') {
                // El legacy hace die('NO send_prevCd Parameter') en la primera fila: aquí no se hace nada
                if ($prevStaffCd === null) {
                    return;
                }
                $other = $this->staffRank($prevStaffCd);
            } else {
                if ($target?->rank === null) {
                    return;
                }
                $other = $this->rankQuery($shopCd)
                    ->select('tb2.cd', 'tb2.rank')
                    ->where('tb2.rank', '>=', $target->rank)
                    ->where('tb1.cd', '!=', $targetStaffCd)
                    ->first();
            }

            // Sin fila de orden en alguno de los dos, el SQL del legacy fallaba y no cambiaba nada
            if (! $target || ! $other || $target->cd === null || $other->cd === null) {
                return;
            }

            $targetRank = (int) $target->rank;
            if ((int) $other->rank === $targetRank) {
                $targetRank += $type === 'rankup' ? 1 : -1;
            }

            FreeReservationRank::withDeleted()->whereKey($target->cd)->toBase()->update(['rank' => (int) $other->rank]);
            FreeReservationRank::withDeleted()->whereKey($other->cd)->toBase()->update(['rank' => $targetRank]);
        });
    }

    /** Filas de contents_master del ajuste en la tienda (solo disp_flg = '1', como el legacy) */
    private function entry(string $field, int $shopCd)
    {
        return ContentsMaster::query()->entry(self::CATEGORY, $field)->where('int_data', $shopCd);
    }

    /** tb_staff LEFT JOIN tb_free_yoyaku_rank del personal de la tienda que tiene menús, ya ordenado */
    private function rankQuery(int $shopCd)
    {
        return DB::table('tb_staff as tb1')
            ->leftJoin('tb_free_yoyaku_rank as tb2', function ($join) {
                $join->on('tb2.tb_staff_cd', '=', 'tb1.cd')->where('tb2.disp_flg', '1');
            })
            ->where('tb1.shop_cd', $shopCd)
            ->whereIn('tb1.cd', DB::table('tb_staff_works')->select('tb_staff_cd')->groupBy('tb_staff_cd'))
            ->where('tb1.disp_flg', '1')
            ->orderBy('tb2.rank')
            ->orderByRaw("COALESCE(tb1.common_flg, '')")
            ->orderBy('tb1.rank')
            ->orderBy('tb1.cd');
    }

    /** Fila de orden (cd, rank) de un empleado activo; cd/rank null si aún no tiene */
    private function staffRank(int $staffCd): ?object
    {
        return DB::table('tb_staff as tb1')
            ->leftJoin('tb_free_yoyaku_rank as tb2', function ($join) {
                $join->on('tb2.tb_staff_cd', '=', 'tb1.cd')->where('tb2.disp_flg', '1');
            })
            ->where('tb1.cd', $staffCd)
            ->where('tb1.disp_flg', '1')
            ->select('tb2.cd', 'tb2.rank')
            ->first();
    }
}
