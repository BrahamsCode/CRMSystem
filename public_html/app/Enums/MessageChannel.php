<?php

namespace App\Enums;

/** Canal de un envío */
enum MessageChannel: int
{
    case TextEmail = 1;   // メルマガ
    case HtmlEmail = 2;   // デコメール
    case Push = 3;   // プッシュ通知

    public function label(): string
    {
        return match ($this) {
            self::TextEmail => 'Email de texto',
            self::HtmlEmail => 'Email con diseño',
            self::Push => 'Notificación push',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::TextEmail => 'mail',
            self::HtmlEmail => 'mail-html',
            self::Push => 'bell',
        };
    }

    public function isEmail(): bool
    {
        return $this !== self::Push;
    }
}
