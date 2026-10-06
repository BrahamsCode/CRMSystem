<?php

namespace App\Enums;

/** Cómo se muestran los cupones de canje de sellos en Mi página */
enum StampDisplayMode: int
{
    case ByShop = 1;   // 店舗別表示
    case All = 2;   // 一括表示
    case HomeShop = 3;   // 登録店舗のみ表示

    public function label(): string
    {
        return match ($this) {
            self::ByShop => 'Por tienda',
            self::All => 'Todos juntos',
            self::HomeShop => 'Solo la tienda de registro',
        };
    }
}
