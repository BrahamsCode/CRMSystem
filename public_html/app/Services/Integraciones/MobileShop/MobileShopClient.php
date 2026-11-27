<?php

namespace App\Services\Integraciones\MobileShop;

/**
 * Cliente de la API extlink de la tienda móvil (有料会員), como src/MobileShop/CustomerForMobileShop.php
 * del legacy. Implementación: HttpMobileShopClient (URL y clave en config('services.mobileshop.extlink_url'
 * / 'extlink_pass')); en pruebas se puede enlazar un doble.
 *
 * Uso (p. ej. ajax_changePaidMemberFlg.php de S2):
 *   $client = app(MobileShopClient::class);
 *   $code = $client->sendUnpaidMemberStatus($client->fetchToken(), $customer->id); // 1 = baja aceptada
 */
interface MobileShopClient
{
    /** JWT de extlink, o false (sin URL configurada o sin `jwt` en la respuesta) */
    public function fetchToken(): string|false;

    /**
     * Estado de pago del socio (`tb_customer.id`, el id público). Guarda paid_member_flg, la hora de la
     * consulta y la fecha de inicio en tb_customer (también cuando extlink no responde) y devuelve el
     * objeto de extlink, o false.
     */
    public function fetchPaidMemberFlg(string|false $token, mixed $customerId): object|false;

    /** Avisa a extlink de la baja del pago; devuelve res_can_cd (1 = ok; 0 sin respuesta válida; false sin URL) */
    public function sendUnpaidMemberStatus(string|false $token, mixed $customerId): int|false;

    /** `<n.º de dominio de este sistema><id con 9 dígitos>`, el user_id que espera extlink */
    public function buildUserId(mixed $customerId): string;
}
