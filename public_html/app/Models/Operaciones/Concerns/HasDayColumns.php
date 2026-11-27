<?php

namespace App\Models\Operaciones\Concerns;

use InvalidArgumentException;

/**
 * Tablas del legacy con un grupo de columnas por día del mes (`<prefijo>_1` … `<prefijo>_31`):
 * tb_work_time, tb_staff_shift y tb_shop_work.
 *
 * El nombre de columna nunca se arma con la entrada del usuario sin validar: el prefijo sale de
 * la lista del modelo (`DAY_PREFIXES`) y el día debe estar entre 1 y 31. Así no se repite la
 * inyección SQL de category5/working/change_syukkin2.php.
 */
trait HasDayColumns
{
    /** Nombre de la columna del día, validado */
    public static function dayColumn(string $prefix, int $day): string
    {
        if (! in_array($prefix, static::DAY_PREFIXES, true)) {
            throw new InvalidArgumentException("Columna por día desconocida: {$prefix}");
        }
        if ($day < 1 || $day > 31) {
            throw new InvalidArgumentException("Día fuera de rango: {$day}");
        }

        return "{$prefix}_{$day}";
    }

    public function getDayValue(string $prefix, int $day): ?string
    {
        $value = $this->getAttribute(static::dayColumn($prefix, $day));

        return $value === null ? null : (string) $value;
    }

    public function setDayValue(string $prefix, int $day, ?string $value): static
    {
        $this->setAttribute(static::dayColumn($prefix, $day), $value);

        return $this;
    }

    /** Valores de una lista `a<>b<>c` del día (vacíos fuera) */
    public function getDayList(string $prefix, int $day): array
    {
        return static::splitLegacyList($this->getDayValue($prefix, $day));
    }

    /**
     * Parte una lista del legacy separada por `<>`. Se quitan los `<>` finales (el legacy a veces
     * deja uno de más) y se conservan las posiciones vacías intermedias, porque las listas de
     * tb_staff_shift van en paralelo (inicio, fin, 区分 y comentario de cada turno).
     */
    public static function splitLegacyList(?string $value): array
    {
        $value = preg_replace('/(<>)+$/', '', (string) $value);

        return $value === '' ? [] : explode('<>', $value);
    }
}
