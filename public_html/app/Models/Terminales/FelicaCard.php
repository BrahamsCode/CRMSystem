<?php

namespace App\Models\Terminales;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\Customers\Customer;
use App\Models\LegacyModel;
use App\Models\Shop;
use App\Models\Terminal;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tarjeta IC FeliCa (`tb_customer_card`), カード管理 de system/category7/felica.
 *
 * - `cid`: IDm de la tarjeta (es el valor que va a `tb_customer.felica_no` del socio).
 * - `typ` / `typ_exp`: tipo de tarjeta que manda el lector; el listado solo muestra
 *   los de VISIBLE_TYPES (como `card_list.php`).
 * - `tb_customer_cd`: socio asignado (null = libre). Se cambia solo con FelicaCardService,
 *   que mantiene `tb_customer.felica_no` vía CustomerFelicaService de S2.
 * - `flg = '9'`: oculta en el listado. El borrado del legacy es físico (`ajax_del.php`).
 * - `name` no la escribe nadie: el filtro 会員名 busca en `tb_customer`.
 * - Fechas epoch en texto (`touroku_date`, `update_date`).
 */
class FelicaCard extends LegacyModel
{
    use HasHatuban, LegacyTimestamps;

    /** Tipos que muestra カード管理 (`typ_exp in (...)` de card_list.php) */
    public const VISIBLE_TYPES = ['TypeA マイフェア 1k', '非モバイルFellica'];

    public const FLG_HIDDEN = '9';

    protected $table = 'tb_customer_card';

    protected $casts = [
        'cd' => 'integer',
        'tb_shop_cd' => 'integer',
        'tb_terminal_cd' => 'integer',
        'tb_customer_cd' => 'integer',
        'tb_staff_cd' => 'integer',
        'point' => 'integer',
        'lost' => 'integer',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'tb_shop_cd', 'cd');
    }

    public function terminal(): BelongsTo
    {
        return $this->belongsTo(Terminal::class, 'tb_terminal_cd', 'cd');
    }

    /** Socio asignado; incluye los borrados (el legacy lo muestra igual) */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'tb_customer_cd', 'cd')->withDeleted();
    }
}
