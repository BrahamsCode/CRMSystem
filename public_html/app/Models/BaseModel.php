<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Base de los modelos del sistema. Aplica lo que el estándar exige a todas las
 * tablas: borrado lógico y el campo `status` como entero.
 *
 * Ver Doc/database/ESTANDARES_BD.md §3.
 */
abstract class BaseModel extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    /** El mismo valor por defecto que la columna: así un modelo recién creado ya está activo en memoria */
    protected $attributes = ['status' => 1];

    protected function casts(): array
    {
        return ['status' => 'integer'];
    }

    /** Registros activos (status = 1) */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 1);
    }

    /** Ordenados por el campo `sort` cuando la tabla lo tiene */
    public function scopeOrdered(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderBy('sort', $direction);
    }
}
