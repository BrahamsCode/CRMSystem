<?php

namespace App\Models\Operaciones;

use App\Models\LegacyModel;

/** 雇用形態 (`tb_employmentsystem`): solo lectura, el legacy no escribe en ella */
class EmploymentSystem extends LegacyModel
{
    protected $table = 'tb_employmentsystem';

    protected $casts = ['cd' => 'integer'];

    public function getNameAttribute(): ?string
    {
        return $this->employmentsystem;
    }
}
