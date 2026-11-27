<?php

namespace App\Models;

use App\Enums\Terminales\SignageFileType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * 放映素材 (tb_screensaver). `images` guarda los huecos de imagen como
 * [{"flg": "1", "ext": "jpg", "path": "…"}], en el orden del legacy.
 */
class SignageMaterial extends BaseModel
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'file_type' => SignageFileType::class,
            'images' => 'array',
            'duration_seconds' => 'integer',
            'material_type' => 'integer',
            'select_flg' => 'integer',
            'sort' => 'integer',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function display(): BelongsTo
    {
        return $this->belongsTo(SignageDisplay::class, 'signage_display_id');
    }

    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(SignageProgram::class)
            ->withPivot('position')
            ->withTimestamps();
    }
}
