<?php

namespace App\Services\Ventas;

use App\Models\Sales\Expense;
use Illuminate\Database\Eloquent\Builder;

/** 経費管理 (keihi.php, keihi_register.php, keihi_delete.php) */
class ExpenseService
{
    /** 経費一覧: vigentes, de todas las tiendas para master y de la suya para el resto; por 使用日 desc */
    public function listQuery(?int $onlyShop): Builder
    {
        return Expense::query()
            ->where('tb_search_form_cd', Expense::FORM)
            ->where('disp_flg', '1')
            ->when($onlyShop !== null, fn ($q) => $q->ofShop($onlyShop))
            ->orderByDesc('update_date')
            ->orderByDesc('cd');
    }

    /** Gasto vigente por cd (para editar o borrar); null si no existe o no es de la tienda permitida */
    public function find(int $cd, ?int $onlyShop): ?Expense
    {
        return $this->listQuery($onlyShop)->whereKey($cd)->first();
    }

    /**
     * act=new|up. Como el legacy, la edición también pisa touroku_date con la hora actual.
     *
     * @param  array{shop: int, date: string, purpose: string, price: float}  $data
     */
    public function save(?Expense $expense, array $data): Expense
    {
        $expense ??= new Expense;
        $expense->fill([
            'tb_search_cate_cd' => Expense::CATEGORY,
            'tb_search_form_cd' => Expense::FORM,
            'float_data' => $data['price'],
            'text_data' => $data['purpose'],
            'touroku_date' => (string) now()->timestamp,
            'update_date' => $data['date'],
            'int_data' => $data['shop'],
            'disp_flg' => '1',
        ])->save();

        return $expense;
    }

    /** keihi_delete.php: disp_flg 9 (sin tocar fechas, como el legacy) */
    public function delete(Expense $expense): void
    {
        Expense::query()->whereKey($expense->cd)->update(['disp_flg' => Expense::DELETED_FLAG]);
    }
}
