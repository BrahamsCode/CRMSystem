<?php

namespace App\Models\Senalizacion;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * パッケージ (`packages`): diseño de pantalla (`display_type`, p. ej. `h_2_1`) y nº de ciclos (`cycle`).
 * Cada ciclo es una fila de `cycle`; los que sobran quedan con flg = 0 (no se borran).
 */
class SignageWebPackage extends SignageWebModel
{
    protected $table = 'packages';

    public function cycles(): HasMany
    {
        return $this->hasMany(SignageWebCycle::class, 'packages_cd', 'cd');
    }
}
