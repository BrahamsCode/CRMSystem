<?php

namespace App\Services\Integraciones\Appview;

/**
 * appview/class/Password.php: ofuscación (no cifrado) del contenido del QR de socio, "<HTTP_HOST>@<customer_cd>".
 * Los lectores del legacy la decodifican con la misma clave fija: se mantiene tal cual por contrato.
 */
class LegacyPassword
{
    private const DEFAULT_KEY = 'わたしのかぎ';

    public static function encode(string $message, string $key = ''): string
    {
        $key = rawurlencode($key === '' ? self::DEFAULT_KEY : $key);
        $message = rawurlencode($message);

        $result = '';
        for ($i = 0; $i < strlen($message); $i++) {
            $result .= chr(ord($message[$i]) + ord($key[$i % strlen($key)]));
        }

        return base64_encode($result);
    }

    public static function decode(string $message, string $key = ''): string
    {
        $key = rawurlencode($key === '' ? self::DEFAULT_KEY : $key);
        $message = (string) base64_decode($message);

        $result = '';
        for ($i = 0; $i < strlen($message); $i++) {
            $result .= chr(ord($message[$i]) - ord($key[$i % strlen($key)]));
        }

        return rawurldecode($result);
    }
}
