<?php

namespace App\Services\Customers;

use App\Models\Customers\ServiceCategory;
use App\Models\Staff;
use App\Support\LegacyText;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * 施術カテゴリー (legacy `system/category1/biyou/sejutsu_cate.php`, `sejutsu_cate_register.php`,
 * `sejutsu_cate_delete.php`) sobre `tb_sejutsu_cate`. `tb_shop_cd` nulo = 全店舗 (en el formulario, -1).
 */
class ServiceCategoryService
{
    /** Lista (disp_flg = '1', ORDER BY cd DESC); sin master solo las de la tienda de la sesión */
    public function list(Staff $staff, int $sessionShopCd): Collection
    {
        return ServiceCategory::query()
            ->where('disp_flg', '1')
            ->when(! $staff->isMaster(), fn ($q) => $q->where('tb_shop_cd', $sessionShopCd))
            ->orderByDesc('cd')
            ->get();
    }

    /** Tiendas (disp_flg <> 9; sin master solo la de la sesión), cd => kaishamei */
    public function shops(Staff $staff, int $sessionShopCd): array
    {
        return DB::table('tb_shop')
            ->whereRaw("COALESCE(disp_flg, '') <> '9'")
            ->when(! $staff->isMaster(), fn ($q) => $q->where('cd', $sessionShopCd))
            ->orderBy('cd')
            ->pluck('kaishamei', 'cd')
            ->all();
    }

    /** La categoría (sin master, solo de su tienda) o null */
    public function find(Staff $staff, int $sessionShopCd, int $cd): ?ServiceCategory
    {
        return ServiceCategory::query()
            ->when(! $staff->isMaster(), fn ($q) => $q->where('tb_shop_cd', $sessionShopCd))
            ->find($cd);
    }

    /** sejutsu_cate_register.php: name con get_form2(); shop_cd -1 = 全店舗 (nulo); disp_flg = '1' */
    public function save(ServiceCategory $category, string $shopCd, string $name): ServiceCategory
    {
        $category->fill([
            'tb_shop_cd' => $shopCd === '-1' ? null : (int) $shopCd,
            'name' => LegacyText::input($name),
            'disp_flg' => '1',
        ]);
        $category->save();

        return $category;
    }

    /** sejutsu_cate_delete.php: disp_flg = '9' */
    public function delete(ServiceCategory $category): void
    {
        $category->delete();
    }
}
