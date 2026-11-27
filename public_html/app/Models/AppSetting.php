<?php

namespace App\Models;

/** Configuración de la app de la mypage (fila única) */
class AppSetting extends BaseModel
{
    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'customer_group_display_flg' => 'integer',
            'home_comment_flg' => 'integer',
        ];
    }

    /** La fila única; se crea con los valores por defecto si falta */
    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }
}
