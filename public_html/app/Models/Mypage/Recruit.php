<?php

namespace App\Models\Mypage;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** 求人情報 (`tb_recruit`). Lo lee la señalización (floormap.xml). */
class Recruit extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes, LegacyTimestamps;

    protected $table = 'tb_recruit';

    protected $casts = [
        'cd' => 'integer',
        'tb_shop_cd' => 'integer',
        'boshu_shokushu' => 'integer',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'tb_shop_cd', 'cd');
    }

    /** 募集職種. Como el legacy, solo un 職種 con disp_flg = '1' tiene nombre */
    public function category(): BelongsTo
    {
        return $this->belongsTo(RecruitCategory::class, 'boshu_shokushu', 'cd')->where('disp_flg', '1');
    }
}
