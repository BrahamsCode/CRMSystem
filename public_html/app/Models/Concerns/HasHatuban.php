<?php

namespace App\Models\Concerns;

use App\Services\Nucleo\Hatuban;

/**
 * Asigna el `cd` con la numeración `hatuban` del legacy al crear el registro (si no viene ya puesto).
 * El contador (`koumoku`) es el nombre de la tabla; un modelo puede cambiarlo con
 * `protected string $hatubanKoumoku = '...'`.
 */
trait HasHatuban
{
    public static function bootHasHatuban(): void
    {
        static::creating(function ($model) {
            $key = $model->getKeyName();
            if (blank($model->getAttribute($key))) {
                $model->setAttribute($key, app(Hatuban::class)->next($model->hatubanKoumoku()));
            }
        });
    }

    public function hatubanKoumoku(): string
    {
        return property_exists($this, 'hatubanKoumoku') ? $this->hatubanKoumoku : $this->getTable();
    }
}
