<?php

namespace App\Services\Mypage;

use App\Models\Mypage\Recruit;
use App\Models\Mypage\RecruitCategory;
use App\Support\LegacyText;
use Illuminate\Http\Request;

/**
 * 求人職種管理 (system/category6/recruit/recruit_category*.php) sobre tb_recruit_category.
 * tb_shop_cd NULL = 全店舗 (el formulario lo envía como -1).
 */
class RecruitCategoryService
{
    public const ALL_SHOPS = '-1';

    /** page_control.php de category1: 20 por defecto */
    public const DEFAULT_KENSU = 20;

    public function __construct(private readonly LegacyPageControl $pageControl) {}

    /** Lista: disp_flg = '1' ORDER BY update_date DESC (el legacy no filtra por tienda) */
    public function paginate(Request $request): array
    {
        $query = RecruitCategory::query()->where('disp_flg', '1')->orderByDesc('update_date');

        return $this->pageControl->paginate($query, $request, self::DEFAULT_KENSU);
    }

    public function find(mixed $cd): ?RecruitCategory
    {
        return is_numeric($cd) ? RecruitCategory::query()->withDeleted()->whereKey((int) $cd)->first() : null;
    }

    /** recruit_category_register.php */
    public function save(mixed $shopCd, string $name, ?RecruitCategory $category): RecruitCategory
    {
        $category ??= new RecruitCategory;
        $category->tb_shop_cd = (string) $shopCd === self::ALL_SHOPS || ! is_numeric($shopCd) ? null : (int) $shopCd;
        // recruit_category_register.php limpia con get_form() (LegacyText::form): se imprime con {{ }}
        $category->shokushu = LegacyText::form($name);
        $category->disp_flg = '1';
        $category->save();

        return $category;
    }

    /** recruit_category_delete.php: disp_flg = '9' */
    public function delete(mixed $cd): void
    {
        if (is_numeric($cd)) {
            RecruitCategory::query()->withDeleted()->whereKey((int) $cd)->update(['disp_flg' => '9']);
        }
    }

    /** ¿Lo usa algún 求人 activo? Entonces no se ofrece 削除 */
    public function inUse(RecruitCategory $category): bool
    {
        return Recruit::query()->where('disp_flg', '1')->where('boshu_shokushu', $category->cd)->exists();
    }
}
