<?php

namespace App\Models;

use App\Enums\Terminales\SignageContentMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** 放映プログラム: lista ordenada de materiales que reproduce un display */
class SignageProgram extends BaseModel
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
            'felica_action_flg' => 'integer',
            'mypage_action_flg' => 'integer',
            'sort' => 'integer',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /** Materiales en orden de reproducción */
    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(SignageMaterial::class)
            ->withPivot('position')
            ->withTimestamps()
            ->orderByPivot('position');
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
            ->withPivot('usage', 'position')
            ->withTimestamps()
            ->orderByPivot('position');
    }

    public function displays(): HasMany
    {
        return $this->hasMany(SignageDisplay::class);
    }
}
