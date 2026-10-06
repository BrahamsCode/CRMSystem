<?php

namespace App\Enums;

/** Categoría de una plantilla de mensaje */
enum TemplateCategory: int
{
    case Newsletter = 1;   // メルマガ用
    case Birthday = 2;   // フォローメール【誕生日】
    case AfterVisit = 3;   // フォローメール【来店後】
    case AfterSignup = 4;   // フォローメール【登録後】
    case Reminder = 5;   // リマインダー
    case Push = 6;   // プッシュ

    public function label(): string
    {
        return match ($this) {
            self::Newsletter => 'Newsletter',
            self::Birthday => 'Cumpleaños',
            self::AfterVisit => 'Después de la visita',
            self::AfterSignup => 'Después del alta',
            self::Reminder => 'Recordatorio de reserva',
            self::Push => 'Notificación push',
        };
    }
}
