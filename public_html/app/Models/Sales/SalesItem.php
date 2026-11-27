<?php

namespace App\Models\Sales;

use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Builder;

/**
 * Producto (商品管理, `sales_items`). A diferencia de las `tb_*`: `cd` es serial propio
 * (no `hatuban`), `touroku_date`/`update_date` son enteros y el borrado lógico es `disp_flg = 0`
 * (no 9). `available_shop_cds` es un `int[]` de PostgreSQL.
 */
class SalesItem extends LegacyModel
{
    use LegacyTimestamps;

    public $incrementing = true;

    protected $table = 'sales_items';

    protected $casts = [
        'cd' => 'integer',
        'disp_flg' => 'integer',
        'hyouji_flg' => 'integer',
        'is_exchangeable_of_point' => 'integer',
        'number_of_points_exchanged' => 'integer',
    ];

    protected $attributes = ['disp_flg' => 1];

    protected static function booted(): void
    {
        // Como SalesItem::getList() del legacy: solo disp_flg = 1
        static::addGlobalScope('visible', fn (Builder $query) => $query->where('sales_items.disp_flg', 1));
    }

    /** Borrado lógico del legacy (`ajax_list_delete.php`): disp_flg = 0 */
    public function markDeleted(): bool
    {
        return $this->forceFill(['disp_flg' => 0])->save();
    }

    /** `available_shop_cds` ('{1,2}') como array de enteros */
    public function availableShopCds(): array
    {
        // El valor actual (también si aún no se guardó), no el original
        return self::parseCds((string) ($this->attributes['available_shop_cds'] ?? ''));
    }

    /** makeArrAvailableShopCds() / buildMultipleSearchCds() del legacy: '{1,2}', '"1", 2' → [1, 2] */
    public static function parseCds(string $text): array
    {
        $text = str_replace(['"', '{', '}', ' '], '', $text);

        return $text === '' ? [] : array_map('intval', explode(',', $text));
    }

    public function setAvailableShopCds(array $cds): void
    {
        $this->attributes['available_shop_cds'] = '{' . implode(',', array_map('intval', $cds)) . '}';
    }
}
