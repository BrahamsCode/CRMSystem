<?php

namespace App\Enums;

/** Premio del sorteo de Mi página (ガチャリンランク) */
enum LotteryRank: int
{
    case Diamond = 1;   // ダイヤ
    case Gold = 2;   // ゴールド
    case Silver = 3;   // シルバー
    case Bronze = 4;   // ブロンズ
    case Miss = 5;   // ハズレ

    public function label(): string
    {
        return match ($this) {
            self::Diamond => 'Diamante',
            self::Gold => 'Oro',
            self::Silver => 'Plata',
            self::Bronze => 'Bronce',
            self::Miss => 'Sin premio',
        };
    }
}
