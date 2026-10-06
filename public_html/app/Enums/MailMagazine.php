<?php

namespace App\Enums;

/**
 * Tres estados: por eso es smallint y no un booleano.
 * Ver Doc/database/ESTANDARES_BD.md §4.
 */
enum MailMagazine: int
{
    case Send = 1;
    case DoNotSend = 2;
    case Undeliverable = 3;

    public function label(): string
    {
        return match ($this) {
            self::Send => 'Enviar',
            self::DoNotSend => 'No enviar',
            self::Undeliverable => 'No entregable',
        };
    }
}
