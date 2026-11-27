<?php

namespace App\Models;

use App\Enums\Terminales\SignageContentMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** ディスプレイ: pantalla física de señalización digital */
class SignageDisplay extends BaseModel
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'terminal_types' => 'array',
            'content_mode' => SignageContentMode::class,
            'action_flg' => 'integer',
            'member_flg' => 'integer',
            'sort' => 'integer',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(SignageProgram::class, 'signage_program_id');
    }

    public function systems(): BelongsToMany
    {
        return $this->belongsToMany(SignageSystem::class)
            ->withPivot('position')
            ->withTimestamps()
            ->orderByPivot('position');
    }

    public function coupons(): BelongsToMany
    {
        return $this->belongsToMany(Coupon::class)
            ->withPivot('position')
            ->withTimestamps()
            ->orderByPivot('position');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(SignageMaterial::class);
    }
}
