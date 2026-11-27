<?php

namespace App\Models\Concerns;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Fechas de alta y modificación del legacy: `touroku_date` / `update_date` como epoch
 * (`time()`) guardado en texto, como hace el legacy. Eloquent las rellena al crear y al guardar.
 *
 * - Formato: `protected string $legacyDateFormat = 'Y/m/d H:i:s';` en las tablas que no usan epoch.
 * - Columnas: `protected ?string $legacyCreatedColumn` / `$legacyUpdatedColumn` (null = no existe).
 * - Al leer quedan tal cual (texto); `legacyDate('touroku_date')` las convierte a Carbon
 *   tolerando vacíos o basura (el legacy deja a veces `update_date` vacío).
 */
trait LegacyTimestamps
{
    public function initializeLegacyTimestamps(): void
    {
        $this->timestamps = true;
    }

    public function getCreatedAtColumn(): ?string
    {
        return property_exists($this, 'legacyCreatedColumn') ? $this->legacyCreatedColumn : 'touroku_date';
    }

    public function getUpdatedAtColumn(): ?string
    {
        return property_exists($this, 'legacyUpdatedColumn') ? $this->legacyUpdatedColumn : 'update_date';
    }

    /** Las columnas de fecha del legacy no se convierten a Carbon al leer (pueden venir vacías) */
    public function getDates(): array
    {
        return [];
    }

    public function setCreatedAt($value): static
    {
        if ($column = $this->getCreatedAtColumn()) {
            $this->setAttribute($column, $this->formatLegacyDate($value));
        }

        return $this;
    }

    public function setUpdatedAt($value): static
    {
        if ($column = $this->getUpdatedAtColumn()) {
            $this->setAttribute($column, $this->formatLegacyDate($value));
        }

        return $this;
    }

    /** También la usa el Builder en `Model::where(...)->update([...])` para `update_date` */
    public function freshTimestampString(): string
    {
        return (string) $this->formatLegacyDate($this->freshTimestamp());
    }

    public function formatLegacyDate(CarbonInterface|int|string|null $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $date = $value instanceof CarbonInterface
            ? $value
            : Carbon::parse(is_numeric($value) ? '@' . $value : $value);
        $format = property_exists($this, 'legacyDateFormat') ? $this->legacyDateFormat : 'U';

        return $date->setTimezone(config('app.timezone'))->format($format);
    }

    /** Fecha del legacy (epoch o texto) como Carbon; null si está vacía o no se entiende */
    public function legacyDate(string $column): ?Carbon
    {
        $value = $this->getAttribute($column);
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        try {
            return is_numeric($value)
                ? Carbon::createFromTimestamp((int) $value, config('app.timezone'))
                : Carbon::parse((string) $value, config('app.timezone'));
        } catch (\Throwable) {
            return null;
        }
    }
}
