<?php

namespace App\Services\Integraciones\Appview;

/**
 * Parámetro `domain` de las pantallas de registro de appview (class/Util::allocatePackage y class/Database):
 * el paquete sale de la subcadena del dominio y la BD de los dos últimos niveles. Solo el paquete «crm» sobre
 * *.crm-s.net / *.crm-tis.jp usa esta BD; «swa»/«rwa» (*.pos-s.net, *.e-pos.link) son BD del POS, fuera de
 * alcance: se tratan como el legacy cuando no conectaba.
 */
class AppviewDomain
{
    /** 'crm', 'swa', 'rwa' o null (allocatePackage) */
    public static function package(string $domain): ?string
    {
        return match (true) {
            str_contains($domain, '.crm-s.net') => 'crm',
            str_contains($domain, '.pos-s.net') => 'swa',
            str_contains($domain, '.e-pos.link') => 'rwa',
            str_contains($domain, '.crm-tis.jp') => 'crm',
            default => null,
        };
    }

    /** ¿El dominio apunta a la BD del CRM? (getConnectionString: crm-s.net o crm-tis.jp por sus dos últimos niveles) */
    public static function usesCrmDatabase(string $domain): bool
    {
        $parts = explode('.', $domain);
        $name = count($parts) < 2 ? '' : $parts[count($parts) - 2].'.'.$parts[count($parts) - 1];

        return in_array($name, ['crm-s.net', 'crm-tis.jp'], true);
    }
}
