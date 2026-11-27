<?php

namespace App\Models\Operaciones;

use App\Models\Concerns\HasHatuban;
use App\Models\LegacyModel;

/**
 * 役職 (`tb_officialposition`): maestro de cargos. `tb_staff.officialposition` y
 * `tb_information.officialposition` guardan el **texto**, no el `cd`.
 */
class OfficialPosition extends LegacyModel
{
    use HasHatuban;

    protected $table = 'tb_officialposition';

    protected $casts = ['cd' => 'integer'];

    public function getNameAttribute(): ?string
    {
        return $this->officialposition;
    }
}
