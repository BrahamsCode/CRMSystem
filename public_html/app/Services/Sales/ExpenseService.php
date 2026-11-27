<?php

namespace App\Services\Sales;

use App\Models\Customers\CustomerSub;
use App\Services\Nucleo\Hatuban;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * 経費管理 (legacy: keihi.php, keihi_register.php, keihi_delete.php). Un gasto es una fila de `tb_customer_sub`
 * con `tb_search_cate_cd = -2`, `tb_search_form_cd = -10`: `int_data` = tienda, `update_date` = 使用日 'Y/m/d',
 * `text_data` = 使用目的, `float_data` = 使用金額, `touroku_date` = epoch (también al editar, como el legacy).
 */
class ExpenseService
{
    public const FORM = -10;

    public function __construct(private Hatuban $hatuban) {}

    /** Gasto activo para editar (?cd=); el no master solo los de su tienda */
    public function find(mixed $cd, ?int $onlyShopCd = null): ?object
    {
        if (blank($cd)) {
            return null;
        }

        return $this->base()->where('cd', (int) $cd)->where('disp_flg', '1')
            ->when($onlyShopCd !== null, fn ($q) => $q->where('int_data', $onlyShopCd))
            ->first();
    }

    /** Lista: activos (de la tienda si no es master) por 使用日 descendente */
    public function paginate(?int $onlyShopCd, int $page, int $kensu): LengthAwarePaginator
    {
        $query = $this->base()->where('disp_flg', '1')
            ->when($onlyShopCd !== null, fn ($q) => $q->where('int_data', $onlyShopCd))
            ->orderByDesc('update_date');

        $total = (clone $query)->reorder()->count();
        $maxPage = $kensu ? (int) ceil($total / $kensu) : 0;
        $page = max(1, min($page, max(1, $maxPage)));

        return new LengthAwarePaginator($query->forPage($page, $kensu)->get(), $total, $kensu, $page);
    }

    /** keihi_register.php: alta (cd con hatuban 'tb_customer_sub') o edición */
    public function save(?int $cd, int $shopCd, string $date, string $purpose, float $price): int
    {
        return DB::transaction(function () use ($cd, $shopCd, $date, $purpose, $price) {
            $values = [
                'tb_search_cate_cd' => CustomerSub::EXPENSE_CATEGORY,
                'tb_search_form_cd' => self::FORM,
                'float_data' => $price,
                'text_data' => $purpose,
                'touroku_date' => (string) time(),
                'update_date' => $date,
                'int_data' => $shopCd,
                'disp_flg' => '1',
            ];

            if ($cd === null) {
                $cd = $this->hatuban->next('tb_customer_sub');
                DB::table('tb_customer_sub')->insert(['cd' => $cd] + $values);
            } else {
                $this->base()->where('cd', $cd)->update($values);
            }

            return $cd;
        });
    }

    /** keihi_delete.php: disp_flg 9 (sin tocar update_date, que es el 使用日) */
    public function delete(int $cd, ?int $onlyShopCd = null): void
    {
        $this->base()->where('cd', $cd)
            ->when($onlyShopCd !== null, fn ($q) => $q->where('int_data', $onlyShopCd))
            ->update(['disp_flg' => '9']);
    }

    private function base()
    {
        return DB::table('tb_customer_sub')->where('tb_search_form_cd', self::FORM);
    }
}
