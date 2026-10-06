<?php

namespace App\Enums;

/** Plataforma de un dispositivo para push */
enum DevicePlatform: int
{
    case Ios = 1;
    case Android = 2;
    case Web = 3;

    public function label(): string
    {
        return match ($this) {
            self::Ios => 'iOS',
            self::Android => 'Android',
            self::Web => 'Navegador web',
        };
    }
}
