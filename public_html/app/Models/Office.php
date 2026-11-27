<?php

namespace App\Models;

/**
 * Empresa (`tb_office`, fila `cd = 1`). `kaishamei` vacío = primer acceso (el legacy manda a config.php).
 * Nombre del sitio (`$site_name` de inc/db.php): `hp_hyouji_name` o, si está vacío, `kaishamei`.
 */
class Office extends LegacyModel
{
    public const ROW_CD = 1;

    protected $table = 'tb_office';

    protected $hidden = ['pass'];

    public static function current(): static
    {
        return static::query()->find(self::ROW_CD) ?? new static(['cd' => self::ROW_CD]);
    }

    public function siteName(): string
    {
        return (string) ($this->hp_hyouji_name ?: $this->kaishamei);
    }
}
