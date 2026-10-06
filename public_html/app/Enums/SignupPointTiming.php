<?php

namespace App\Enums;

/** Cuándo se entregan los puntos de alta */
enum SignupPointTiming: int
{
    case OnSignup = 1;
    case FirstVisit = 2;

    public function label(): string
    {
        return match ($this) {
            self::OnSignup => 'Al darse de alta',
            self::FirstVisit => 'En la primera visita',
        };
    }
}
