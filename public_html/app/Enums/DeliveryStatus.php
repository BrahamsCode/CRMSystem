<?php

namespace App\Enums;

/** Estado de un envío */
enum DeliveryStatus: int
{
    case Draft = 1;
    case Scheduled = 2;   // 予約
    case Sending = 3;
    case Sent = 4;   // 配信済
    case Cancelled = 5;

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Scheduled => 'Programado',
            self::Sending => 'Enviando',
            self::Sent => 'Enviado',
            self::Cancelled => 'Cancelado',
        };
    }

    /** Tono del badge en las listas */
    public function tone(): string
    {
        return match ($this) {
            self::Scheduled, self::Sending => 'accent',
            self::Sent => 'neutral',
            default => 'outline',
        };
    }

    /** Todavía se puede editar o cancelar */
    public function isPending(): bool
    {
        return in_array($this, [self::Draft, self::Scheduled], true);
    }
}
