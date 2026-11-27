<?php

namespace App\Models\Customers;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Campo de una categoría de información adicional. `form_type` en japonés (テキスト, チェックボックス…), opciones en `titles`/`data` separadas por `<>`; `hyouji_flg` '1' público, '2' solo administración, '' oculto.
 * Tabla del legacy `tb_search_form` (CONVENCIONES §0).
 */
class CustomField extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes;

    protected $table = 'tb_search_form';

    protected $casts = [
        'cd' => 'integer',
        'rank' => 'integer',
        'tb_search_cate_cd' => 'integer',
        'flds_row' => 'integer',
        'flds_col' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(CustomCategory::class, 'tb_search_cate_cd', 'cd');
    }

    /** Opciones [valor => etiqueta] a partir de `data` y `titles` */
    public function options(): array
    {
        $values = explode('<>', (string) $this->data);
        $titles = explode('<>', (string) $this->titles);
        $options = [];
        foreach ($values as $i => $value) {
            if ($value !== '') {
                $options[$value] = $titles[$i] ?? $value;
            }
        }

        return $options;
    }
}
