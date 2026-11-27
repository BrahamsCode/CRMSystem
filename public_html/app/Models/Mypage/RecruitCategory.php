<?php

namespace App\Models\Mypage;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** 求人職種 (`tb_recruit_category`): nombre en `shokushu` */
class RecruitCategory extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes, LegacyTimestamps;

    protected $table = 'tb_recruit_category';

    protected $casts = [
        'cd' => 'integer',
        'tb_shop_cd' => 'integer',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'tb_shop_cd', 'cd');
    }

    public function recruits(): HasMany
    {
        return $this->hasMany(Recruit::class, 'boshu_shokushu', 'cd');
    }
}
