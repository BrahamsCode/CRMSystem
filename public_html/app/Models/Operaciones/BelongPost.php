<?php

namespace App\Models\Operaciones;

use App\Models\Concerns\HasHatuban;
use App\Models\LegacyModel;

/**
 * 所属 (`tb_belongpost`). El alta de personal agrega el texto escrito si no existe
 * (staff_entry_register.php, contador `hatuban` 'belongpost').
 */
class BelongPost extends LegacyModel
{
    use HasHatuban;

    protected $table = 'tb_belongpost';

    protected string $hatubanKoumoku = 'belongpost';

    protected $casts = ['cd' => 'integer'];

    public function getNameAttribute(): ?string
    {
        return $this->belongpost;
    }

    /** Devuelve el 所属 con ese texto, creándolo si no existe (como el legacy) */
    public static function findOrCreateByName(string $name): self
    {
        return static::query()->firstOrCreate(['belongpost' => $name]);
    }
}
