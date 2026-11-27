<?php

namespace App\Enums\Mypage;

/** Cómo se elige la imagen de una celda del banner */
enum BannerMode: int
{
    case Default = 1;  // デフォルト選択
    case Free = 2;     // 自由選択
}
