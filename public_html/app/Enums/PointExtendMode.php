<?php

namespace App\Enums;

/** Qué movimiento renueva la validez de los puntos (ポイント有効期間延長動作) */
enum PointExtendMode: int
{
    case AnyMovement = 1;   // ポイントの増減どちらか
    case UseOnly = 2;   // ポイントの使用があった場合のみ

    public function label(): string
    {
        return match ($this) {
            self::AnyMovement => 'Cualquier movimiento (acumular o usar)',
            self::UseOnly => 'Solo al usar puntos',
        };
    }
}
