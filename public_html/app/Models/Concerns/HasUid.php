<?php

namespace App\Models\Concerns;

use App\Helpers\Uid;

/**
 * Rellena el campo `uid` al crear el registro.
 * Ver Doc/database/ESTANDARES_BD.md §5.
 */
trait HasUid
{
    protected static function bootHasUid(): void
    {
        static::creating(function ($model) {
            if (empty($model->uid)) {
                $model->uid = Uid::getUid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uid';
    }
}
