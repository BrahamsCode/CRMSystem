<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * Opciones del menú de cada módulo (`tb_base_system_child`): `system_flg = '1'` activa, orden `rank, cd`.
 * Los menús laterales del legacy muestran un bloque solo si su hijo está activo (`left_navi1.php`).
 */
class BaseSystemChild extends LegacyModel
{
    protected $table = 'tb_base_system_child';

    protected $casts = ['cd' => 'integer', 'tb_base_system_cd' => 'integer', 'rank' => 'integer'];

    public function scopeActive(Builder $query, int $baseSystemCd): void
    {
        $query->where('tb_base_system_cd', $baseSystemCd)->where('system_flg', '1')->orderBy('rank')->orderBy('cd');
    }
}
