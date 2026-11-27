<?php

namespace App\Services\Customers;

use App\Models\Customers\PointSetting;
use App\Support\LegacyText;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ポイント管理 (legacy: category1/customer/point_setting.php, _frm.php, _register.php; tabla tb_point_setting).
 * Alta/edición en la misma pantalla, lista por `rank` con ▲▼ y borrado lógico.
 */
class PointSettingService
{
    public function __construct(private readonly LegacyRankSorter $sorter) {}

    /** Lista del legacy: `disp_flg = '1' ORDER BY rank` */
    public function active(): Collection
    {
        return PointSetting::query()->where('disp_flg', '1')->orderBy('rank')->orderBy('cd')->get();
    }

    /**
     * point_setting_register.php (new | up): name y point (form2Db: 半角カナ → 全角 y recorte). En el alta, `cd`
     * con hatuban `tb_point_setting`, `touroku_date` y `rank` = nº de filas activas + 1. Siempre `disp_flg = '1'`.
     */
    public function save(PointSetting $item, array $input): PointSetting
    {
        return DB::transaction(function () use ($item, $input) {
            if (! $item->exists) {
                $item->rank = PointSetting::query()->where('disp_flg', '1')->count() + 1;
            }

            $item->name = LegacyText::db($input['name'] ?? '');
            $item->point = (int) LegacyText::db($input['point'] ?? '');
            $item->disp_flg = '1';
            $item->save();

            return $item;
        });
    }

    /** 削除: `disp_flg = '9'` y `update_date` (sin renumerar, como el legacy) */
    public function delete(PointSetting $item): void
    {
        $item->delete();
    }

    /** ▲ (`up`) / ▼ (`down`): intercambia `rank` con el vecino activo y renumera 1…n (bloque 並び順入れ替え) */
    public function move(PointSetting $item, string $kind): void
    {
        $this->sorter->move(
            fn () => PointSetting::query()->where('disp_flg', '1'),
            (int) $item->cd,
            (int) $item->rank,
            $kind,
        );
    }
}
