<?php

namespace App\Models\Customers;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\LegacyModel;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Visita (来店履歴, `tb_raiten_rireki`). Contador `hatuban` = 'raiten_rireki'.
 * - `user_cd` = `tb_customer.cd`, `user_id` = `tb_customer.id`, `shop_cd` = tienda.
 * - `raiten_time`: epoch en texto; `raiten_hiduke`: día de negocio 'Y/m/d' (corte a las 05:00).
 * - `customer_age/sex/job`: datos del cliente en el momento de la visita.
 * - `disp_flg`: el legacy inserta NULL y considera visible todo lo que no sea '9'
 *   (`COALESCE(disp_flg, '') <> '9'`), por eso usa VisitDeletedScope en lugar del scope común.
 */
class Visit extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes;

    protected string $hatubanKoumoku = 'raiten_rireki';

    protected $table = 'tb_raiten_rireki';

    protected $casts = [
        'cd' => 'integer',
        'user_cd' => 'integer',
        'shop_cd' => 'integer',
        'stamp' => 'integer',
        'customer_age' => 'integer',
        'tb_point_log_cd' => 'integer',
        'tb_stamp_log_cd' => 'integer',
        'tb_search_cate_cd' => 'integer',
    ];

    /** Sustituye al scope común: aquí un `disp_flg` NULL es una visita visible */
    public static function bootLegacySoftDeletes(): void
    {
        static::addGlobalScope(new VisitDeletedScope);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'user_cd', 'cd');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_cd', 'cd');
    }

    public function visitedAt(): ?Carbon
    {
        return is_numeric($this->raiten_time) ? Carbon::createFromTimestamp((int) $this->raiten_time, 'Asia/Tokyo') : null;
    }
}
