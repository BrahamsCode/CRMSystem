<?php

namespace App\Models;

/**
 * Sub-opciones de los módulos (`tb_base_system_grandchild`), p. ej. `顧客ランク%` activa el menú de ランク.
 */
class BaseSystemGrandchild extends LegacyModel
{
    protected $table = 'tb_base_system_grandchild';

    protected $casts = ['cd' => 'integer', 'tb_base_system_cd' => 'integer', 'tb_base_system_child_cd' => 'integer', 'rank' => 'integer'];

    /** ¿Hay alguna activa cuyo nombre cumpla el patrón LIKE? (`system_name like '顧客ランク%'`) */
    public static function activeLike(string $pattern): bool
    {
        return static::query()->where('system_name', 'like', $pattern)->where('system_flg', '1')->exists();
    }
}
