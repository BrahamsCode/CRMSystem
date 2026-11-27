<?php

namespace App\Services\Reservations;

use App\Models\Reservations\ReservationMenu;
use App\Models\Reservations\StaffWork;
use App\Models\Staff;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * スタッフ施術管理 (legacy: category9/yoyaku/staff_works.php y ajax_menu.php).
 * Menús que atiende cada empleado, su tiempo propio (`shoyou_time`), el 予約非表示 (`mypage_flg`)
 * y el 指名許諾 del empleado (`tb_staff.reserve_flg`).
 */
class StaffWorkService
{
    /**
     * manageWord('スタッフ', 'c9_yoyaku[スタッフ]') del legacy.
     * TODO(S1): modelo ManageWord (tb_manage_words)
     */
    public function staffWord(): string
    {
        $naiyou = DB::table('tb_manage_words')->where('content_name', 'c9_yoyaku[スタッフ]')->value('naiyou');

        return filled($naiyou) ? $naiyou : 'スタッフ';
    }

    /** 指名許諾 del empleado (`tb_staff.reserve_flg`, sin filtrar por disp_flg como el legacy) */
    public function reserveFlg(int $staffCd): ?string
    {
        return Staff::withDeleted()->whereKey($staffCd)->value('reserve_flg');
    }

    /**
     * Menús que se pueden asignar en la tienda (ajax_menu.php): de la tienda o de todas (-1),
     * activos y visibles, sin categoría borrada; con el nombre y el hyouji_flg de la categoría.
     *
     * @return Collection<int, ReservationMenu>
     */
    public function assignableMenus(int $shopCd): Collection
    {
        return ReservationMenu::withDeleted()
            ->from('tb_yoyaku_koumoku_master as tb1')
            ->leftJoin('tb_yoyaku_koumoku_category as tb2', 'tb1.tb_yoyaku_koumoku_category_cd', '=', 'tb2.cd')
            ->select('tb1.*', 'tb2.name as category_name', 'tb2.hyouji_flg as category_hyouji_flg')
            ->where('tb1.disp_flg', '1')
            ->where('tb1.hyouji_flg', '1')
            ->whereIn('tb1.tb_shop_cd', [$shopCd, -1])
            ->whereRaw("COALESCE(tb2.disp_flg, '') <> '9'")
            ->orderBy('tb1.type_flg')
            ->orderBy('tb2.rank')
            ->orderBy('tb1.tb_yoyaku_koumoku_category_cd')
            ->orderBy('tb1.tb_shop_cd')
            ->orderBy('tb1.rank')
            ->orderBy('tb1.cd')
            ->get();
    }

    /**
     * Filas activas del empleado indexadas por menú (como $staffData del legacy).
     *
     * @return Collection<int, StaffWork>
     */
    public function worksByMenu(int $staffCd): Collection
    {
        return StaffWork::withDeleted()
            ->where('disp_flg', '1')
            ->where('tb_staff_cd', $staffCd)
            ->get()
            ->keyBy('tb_yoyaku_koumoku_master_cd');
    }

    /**
     * Reescribe todas las filas del empleado (DELETE + INSERT, como el legacy) y su 指名許諾.
     *
     * @param  array<int|string, mixed>  $menuCds    yoyaku_menu_cd[<menú>] = '1' (solo los marcados)
     * @param  array<int|string, mixed>  $times      shoyou_time[<menú>]
     * @param  array<int|string, mixed>  $mypageFlgs mypage_flg[<menú>] = '1' (予約非表示)
     */
    public function replace(int $staffCd, string $reserveFlg, array $menuCds, array $times, array $mypageFlgs): void
    {
        DB::transaction(function () use ($staffCd, $reserveFlg, $menuCds, $times, $mypageFlgs) {
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('LOCK TABLE tb_staff_works');
            }

            // El legacy borra de verdad (DELETE), no con disp_flg = '9'
            StaffWork::withDeleted()->where('tb_staff_cd', $staffCd)->toBase()->delete();

            Staff::withDeleted()->whereKey($staffCd)->toBase()->update(['reserve_flg' => $reserveFlg]);

            foreach (array_keys($menuCds) as $menuCd) {
                // cd = MAX(cd) + 1 lo pone el modelo
                StaffWork::create([
                    'tb_staff_cd' => $staffCd,
                    'tb_yoyaku_koumoku_master_cd' => (int) $menuCd,
                    'shoyou_time' => (int) ($times[$menuCd] ?? 0),
                    'hyouji_flg' => '1',
                    'disp_flg' => '1',
                    'mypage_flg' => (string) ($mypageFlgs[$menuCd] ?? ''),
                ]);
            }
        });
    }
}
