<?php

namespace App\Services\Customers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Listas maestras con alta/edición en línea, ▲ y borrado lógico de 顧客管理 (legacy: category1/customer/
 * customer_group_category_*, raiten_douki* y customer_level_category_*, que son la misma pantalla con otra tabla).
 * Modelos: CustomerGroup (tb_customer_group, de S3), VisitMotive (tb_raiten_douki_master), CustomerLevel (tb_customer_level).
 */
class CustomerMasterService
{
    public function __construct(
        private CustomerRegistrationService $registration,
        private LegacyRankSorter $sorter,
    ) {}

    /** Lista del legacy: `disp_flg = '1' ORDER BY rank` */
    public function active(string $modelClass): Collection
    {
        return $modelClass::query()->where('disp_flg', '1')->orderBy('rank')->orderBy('cd')->get();
    }

    /**
     * Alta o edición (`*_register.php`). En el alta: `cd` con createHatsuban($shopCd) si se pasa la tienda (grupo y
     * 来店動機) o con `hatuban` (trait HasHatuban: 顧客レベル), y `rank` = nº de filas activas + 1.
     * `default_flg = '1'` (solo 顧客グループ) limpia antes el de todas las filas, como el legacy.
     */
    public function save(Model $item, array $values, ?int $createHatsubanShopCd = null): Model
    {
        return DB::transaction(function () use ($item, $values, $createHatsubanShopCd) {
            if (($values['default_flg'] ?? null) === '1') {
                $item::withDeleted()->toBase()->update(['default_flg' => '']);
            }

            if (! $item->exists) {
                if ($createHatsubanShopCd !== null) {
                    $item->setAttribute('cd', $this->registration->uniqueCd($createHatsubanShopCd));
                }
                $item->setAttribute('rank', $item::query()->where('disp_flg', '1')->count() + 1);
            }

            $item->fill($values);
            $item->setAttribute('disp_flg', '1');
            $item->save();

            return $item;
        });
    }

    /** 削除: `disp_flg = '9'` y `update_date` (sin renumerar, como el legacy) */
    public function delete(Model $item): void
    {
        $item->delete();
    }

    /**
     * ▲ (`up`) / ▼ (`down`) de `*_frm.php`: intercambia el `rank` con el vecino activo y después renumera
     * todas las filas activas 1…n por `rank`.
     */
    public function move(Model $item, string $kind): void
    {
        $this->sorter->move(
            fn () => $item::query()->where('disp_flg', '1'),
            (int) $item->getKey(),
            (int) $item->getAttribute('rank'),
            $kind,
        );
    }
}
