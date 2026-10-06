<?php

namespace App\Support;

/**
 * Importes guardados como entero en la unidad mínima de la moneda
 * (Doc/database/ESTANDARES_BD.md §6). La moneda se configura en config/crm.php.
 */
class Money
{
    public static function format(?int $amount): string
    {
        $currency = config('crm.currency');
        $value = ($amount ?? 0) / (10 ** $currency['decimals']);

        return $currency['symbol'] . number_format($value, $currency['decimals'], $currency['decimal_point'], $currency['thousands']);
    }

    /** Valor de un formulario (ej. «15.50») a la unidad mínima */
    public static function toMinor(string|int|float|null $value): int
    {
        return (int) round(((float) $value) * (10 ** config('crm.currency.decimals')));
    }

    public static function toMajor(?int $amount): string
    {
        $decimals = config('crm.currency.decimals');

        return number_format(($amount ?? 0) / (10 ** $decimals), $decimals, '.', '');
    }
}
