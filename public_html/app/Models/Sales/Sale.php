<?php

namespace App\Models\Sales;

use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Customers\Customer;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Fila de una venta interna (売上データ, category1/karute_menu/karute_new_register.php) sobre `tb_customer_sub`
 * con `tb_search_cate_cd = -1` (scope global). Una venta («cuenta», 請求番号) son varias filas con el mismo
 * `tb_customer_cd` + `category_inner_group` (máximo del cliente + 1, contando también las borradas).
 *
 * Correspondencia campo ↔ significado según `tb_search_form_cd` (todas con `int_data` = tienda,
 * `touroku_date` = epoch del alta, `update_date` = 営業日 'Y/m/d' del formulario, no un epoch):
 *
 * | form_cd | form_title   | rank                     | float_data              | text_data | array_data | form_type            | pic_flg  |
 * |---------|--------------|--------------------------|-------------------------|-----------|------------|----------------------|----------|
 * | -1      | スタッフ情報   | comisión (円)             | 歩合率 % (kingaku_buai)  | nombre    | staff cd   | ''                   | —        |
 * | -2      | メニュー情報   | null                     | precio del menú         | nombre    | menú cd    | 技術/物販/その他        | —        |
 * | -3      | 割引情報       | null                     | importe del descuento   | nombre    | 割引 cd    | '10％' / '500円'      | buai_flg |
 * | -4      | 手動割引情報   | null                     | importe del descuento   | ''        | ''         | '10％' / '500円'      | '2'      |
 * | -5      | 合計          | base de la comisión (円)  | total cobrado           | ''        | ''         | ''                   | —        |
 *
 * Comisión = (int)(base × tb_staff.kingaku_buai / 100). `cd` = createHatsuban(shop) (epoch + tienda 3 cifras +
 * aleatorio 6 cifras) y +1 por fila, no el contador `hatuban`. Sin LegacyTimestamps: `update_date` es el 営業日.
 * El modelo `App\Models\Customers\CustomerSub` (S2) es la misma tabla; este es el de S4 para ventas.
 */
class Sale extends LegacyModel
{
    use LegacySoftDeletes;

    protected $table = 'tb_customer_sub';

    public const CATEGORY = -1;

    public const FORM_STAFF = -1;        // スタッフ情報

    public const FORM_MENU = -2;         // メニュー情報

    public const FORM_DISCOUNT = -3;     // 割引情報

    public const FORM_MANUAL_DISCOUNT = -4; // 手動割引情報 (その他の割引)

    public const FORM_TOTAL = -5;        // 合計

    /** form_type de las filas de menú según tb_karute_menu.type */
    public const MENU_TYPES = ['1' => '技術', '2' => '物販'];

    public const MENU_TYPE_OTHER = 'その他';

    protected $casts = [
        'cd' => 'integer',
        'rank' => 'integer',
        'tb_customer_cd' => 'integer',
        'tb_search_cate_cd' => 'integer',
        'tb_search_form_cd' => 'integer',
        'float_data' => 'float',
        'int_data' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('ventas', function (Builder $query) {
            $query->where($query->qualifyColumn('tb_search_cate_cd'), self::CATEGORY);
        });

        static::creating(function (Sale $sale) {
            $sale->tb_search_cate_cd = self::CATEGORY;
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'tb_customer_cd', 'cd');
    }

    /** Filas de una cuenta (cliente + 請求番号) */
    public function scopeOfGroup(Builder $query, int $customerCd, string $group): void
    {
        $query->where('tb_customer_cd', $customerCd)->where('category_inner_group', $group);
    }

    public function scopeForm(Builder $query, int $formCd): void
    {
        $query->where($query->qualifyColumn('tb_search_form_cd'), $formCd);
    }

    /** Siguiente 請求番号 del cliente: máximo + 1 (incluye las borradas, como el legacy) */
    public static function nextGroup(int $customerCd): int
    {
        $max = static::withDeleted()
            ->where('tb_customer_cd', $customerCd)
            ->orderByRaw('category_inner_group::int DESC')
            ->value('category_inner_group');

        return (int) $max + 1;
    }
}
