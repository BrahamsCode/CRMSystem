<?php

namespace App\Services\Integraciones\MobileShop;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use RuntimeException;

/**
 * JWT de la API de la tienda móvil, como api/auth.php y src/Util/JwtAuth.php del legacy:
 * RS256 con las claves `keys/private.pem` / `keys/public.pem`, claims iat, exp (+30 días) y user_id.
 * Usuario y clave en config('services.mobileshop.api_user' / 'api_pass').
 */
class MobileShopJwt
{
    public const TTL_SECONDS = 30 * 24 * 60 * 60;

    /** El legacy responde «Invalid config.» solo si faltan los dos valores */
    public function isConfigured(): bool
    {
        return (bool) config('services.mobileshop.api_user') || (bool) config('services.mobileshop.api_pass');
    }

    /**
     * Mismo criterio que is_valid() del legacy: comparación `==` con los valores del .env.
     * Se mantiene la comparación laxa para no cambiar qué credenciales acepta el cliente.
     */
    public function validCredentials(mixed $userId, mixed $password): bool
    {
        return $userId == config('services.mobileshop.api_user')
            && $password == config('services.mobileshop.api_pass');
    }

    public function issue(mixed $userId, ?int $now = null): string
    {
        $now ??= time();

        return JWT::encode([
            'iat' => $now,
            'exp' => $now + self::TTL_SECONDS,
            'user_id' => $userId,
        ], $this->readKey('private_key_path'), 'RS256');
    }

    /**
     * Decodifica y valida el token. Lanza las excepciones de firebase/php-jwt, cuyo mensaje
     * el legacy devuelve tal cual en {"status":"ng","message":…}.
     */
    public function decode(string $jwt): object
    {
        return JWT::decode($jwt, new Key($this->readKey('public_key_path'), 'RS256'));
    }

    private function readKey(string $configKey): string
    {
        $path = (string) config("services.mobileshop.{$configKey}");
        $key = is_file($path) ? file_get_contents($path) : false;
        if ($key === false) {
            throw new RuntimeException("No se puede leer la clave de la tienda móvil ({$configKey})");
        }

        return $key;
    }
}
