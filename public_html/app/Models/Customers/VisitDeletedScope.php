<?php

namespace App\Models\Customers;

use App\Models\Concerns\LegacySoftDeletingScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Como LegacySoftDeletingScope, pero un `disp_flg` NULL cuenta como visible:
 * `tb_raiten_rireki` se inserta sin `disp_flg` y el legacy filtra con `COALESCE(disp_flg, '') <> '9'`.
 */
class VisitDeletedScope extends LegacySoftDeletingScope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereRaw('COALESCE(' . $model->qualifyColumn($model->getDeletedFlagColumn()) . ", '') <> ?", [$model::DELETED_FLAG]);
    }
}
