<?php

namespace App\Models\Operaciones;

use App\Models\Concerns\HasHatuban;
use App\Models\LegacyModel;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 各種設定 del terminal 勤怠 (`kintai_tb_config`), una fila por tienda. Horas como 'HH<>MM';
 * `password` en texto plano (la lee el terminal físico). ⏸ 勤怠; los turnos leen su horario.
 */
class KintaiConfig extends LegacyModel
{
    use HasHatuban;

    /** Valores con los que el legacy crea la fila de una tienda (index.php) */
    public const DEFAULTS = [
        'kijun_time' => '05<>00',
        'start_time' => '08<>00',
        'end_time' => '10<>00',
        'password' => '9999',
    ];

    protected $table = 'kintai_tb_config';

    protected $hidden = ['password'];

    protected $casts = [
        'cd' => 'integer',
        'tb_shop_cd' => 'integer',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'tb_shop_cd', 'cd');
    }

    public static function forShop(int $shopCd): self
    {
        return static::query()->firstOrCreate(['tb_shop_cd' => $shopCd], self::DEFAULTS);
    }

    /** 'HH<>MM' → [hora, minuto] */
    public static function splitTime(?string $value): array
    {
        [$hour, $minute] = array_pad(explode('<>', (string) $value), 2, '0');

        return [(int) $hour, (int) $minute];
    }
}
