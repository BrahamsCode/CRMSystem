<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Módulos activables (`tb_base_system`, cd 0–12): `system_flg = '1'` activo, orden `narabi`.
 * Lo usan システム選択 y las pestañas de la cabecera (`BaseSystem::fetchBaseSystem` del legacy).
 */
class BaseSystem extends LegacyModel
{
    /** 女の子管理: retirado, nunca se muestra */
    public const RETIRED_CD = 8;

    public const SNS_NAME = 'SNS管理';

    protected $table = 'tb_base_system';

    protected $casts = ['cd' => 'integer', 'narabi' => 'integer'];

    public function children(): HasMany
    {
        return $this->hasMany(BaseSystemChild::class, 'tb_base_system_cd', 'cd');
    }

    /** Activos en orden; con la UI versión 1 el legacy oculta SNS管理 */
    public function scopeActive(Builder $query, ?string $versionFlg = null): void
    {
        $query->where('system_flg', '1')->orderBy('narabi');
        if ($versionFlg !== null && $versionFlg !== '2') {
            $query->where('system_name', '<>', self::SNS_NAME);
        }
    }
}
