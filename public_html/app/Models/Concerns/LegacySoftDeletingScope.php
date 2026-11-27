<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Oculta los registros borrados del legacy (`disp_flg = '9'`).
 * Igual que el SQL del legacy (`disp_flg <> '9'`): un `disp_flg` NULL también queda fuera.
 * Extensiones del builder: withDeleted(), onlyDeleted(), restore().
 */
class LegacySoftDeletingScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where($model->qualifyColumn($model->getDeletedFlagColumn()), '<>', $model::DELETED_FLAG);
    }

    public function extend(Builder $builder): void
    {
        $builder->macro('withDeleted', fn (Builder $builder) => $builder->withoutGlobalScope($this));

        $builder->macro('onlyDeleted', function (Builder $builder) {
            $model = $builder->getModel();

            return $builder->withoutGlobalScope($this)
                ->where($model->qualifyColumn($model->getDeletedFlagColumn()), $model::DELETED_FLAG);
        });

        $builder->macro('restore', function (Builder $builder) {
            $model = $builder->getModel();

            return $builder->withoutGlobalScope($this)
                ->update([$model->getDeletedFlagColumn() => $model::ACTIVE_FLAG]);
        });
    }
}
