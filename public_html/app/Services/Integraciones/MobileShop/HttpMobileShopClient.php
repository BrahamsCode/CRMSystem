<?php

namespace App\Services\Integraciones\MobileShop;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Implementación HTTP de MobileShopClient: API extlink de la tienda móvil (有料会員), como src/MobileShop/CustomerForMobileShop.php:
 * token (`/apiSnderAuth.php`), estado de pago (`/apiJudServiceTgt.php`) y baja (`/apiSnderCan.php`).
 * Lo usan /api/mobile_shop/fetch_paid_member y send_unpaid_member_status, y el Job SyncPaidMemberStatus de S2.
 * URL y clave en config('services.mobileshop.extlink_url' / 'extlink_pass').
 */
class HttpMobileShopClient implements MobileShopClient
{
    public const API_ID = 'extlink';

    public const AUTH_ENDPOINT = '/apiSnderAuth.php';

    public const JUDGE_SERVICE_ENDPOINT = '/apiJudServiceTgt.php';

    public const UNPAID_MEMBER_STATUS_ENDPOINT = '/apiSnderCan.php';

    public function __construct(private readonly MobileShopDomains $domains)
    {
    }

    /** JWT de extlink, o false (sin URL configurada o sin `jwt` en la respuesta) */
    public function fetchToken(): string|false
    {
        $url = $this->baseUrl();
        if (! $url) {
            return false;
        }

        $response = $this->post($url.self::AUTH_ENDPOINT, [
            'user_id' => self::API_ID,
            'password' => (string) config('services.mobileshop.extlink_pass'),
        ]);

        return isset($response->jwt) && $response->jwt ? (string) $response->jwt : false;
    }

    /**
     * Estado de pago del socio (`id` público). Guarda paid_member_flg, la hora de la consulta y la
     * fecha de inicio en tb_customer (como el legacy, también cuando extlink no responde) y devuelve
     * el objeto de extlink, o false.
     */
    public function fetchPaidMemberFlg(string|false $token, mixed $customerId): object|false
    {
        $url = $this->baseUrl();
        if (! $url) {
            return false;
        }

        $userId = $this->buildUserId($customerId);
        $response = $this->post($url.self::JUDGE_SERVICE_ENDPOINT, ['user_id' => $userId], (string) $token);
        $result = isset($response->result) && $response->result ? $response->result : false;
        $paidMember = isset($result->data[0]) ? $result->data[0] : false;

        // El legacy lo dejaba en su log 'paid_member'
        Log::info('paid_member', ['user_id' => $userId, 'result' => $paidMember]);

        $flg = isset($paidMember->flg_service) ? (int) $paidMember->flg_service : null;
        $startDate = null;
        if (isset($paidMember->srv_strt_ymd)
            && preg_match('#^([0-9]{4})[/\-]{1}([0-9]{1,2})[/\-]{1}([0-9]{1,2})$#', (string) $paidMember->srv_strt_ymd, $m)
            && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            $startDate = strtotime((string) $paidMember->srv_strt_ymd);
        }

        $updated = DB::table('tb_customer')->where('id', (string) $customerId)->update([
            'paid_member_flg' => $flg,
            'paid_member_flg_confirmation_time' => time(),
            'paid_member_service_start_date' => $startDate,
        ]);

        return $updated ? $paidMember : false;
    }

    /** Avisa a extlink de la baja del pago; devuelve res_can_cd (0 si no hay respuesta válida) */
    public function sendUnpaidMemberStatus(string|false $token, mixed $customerId): int|false
    {
        $url = $this->baseUrl();
        if (! $url) {
            return false;
        }

        $response = $this->post($url.self::UNPAID_MEMBER_STATUS_ENDPOINT, ['user_id' => $this->buildUserId($customerId)], (string) $token);
        $result = isset($response->result) && $response->result ? $response->result : false;

        return isset($result->res_can_cd) ? (int) $result->res_can_cd : 0;
    }

    /** `<n.º de dominio de este sistema><id con 9 dígitos>`, como buildUserId() */
    public function buildUserId(mixed $customerId): string
    {
        return ($this->domains->systemKey() ?? '').sprintf('%09d', (int) $customerId);
    }

    /**
     * URL de extlink, o '' si no hay o si el interruptor está apagado (`services.mobileshop.extlink_enabled`,
     * MOBILESHOP_EXTLINK_ENABLED, por defecto solo en producción). Apagado se comporta como sin URL: no sale ninguna
     * petición, no se toca tb_customer y solo queda en el log, igual que el interruptor común de g-api (App\Support\GApi).
     */
    private function baseUrl(): string
    {
        $url = (string) config('services.mobileshop.extlink_url');
        if ($url !== '' && ! config('services.mobileshop.extlink_enabled')) {
            Log::info('extlink apagado (MOBILESHOP_EXTLINK_ENABLED): no se envía', ['url' => $url]);

            return '';
        }

        return $url;
    }

    /** POST JSON; cualquier error de red o respuesta no JSON da null, como json_decode del error de cURL */
    private function post(string $url, array $data, ?string $token = null): ?object
    {
        try {
            // Sin verificar el certificado, como el cURL del legacy (CURLOPT_SSL_VERIFYPEER false): se mantiene por
            // paridad con el servidor de extlink; anotado en Doc/qa/para-japon.md
            $request = Http::timeout(30)->withoutVerifying()->asJson();
            if ($token !== null) {
                $request = $request->withToken($token);
            }
            $decoded = json_decode($request->post($url, $data)->body());
        } catch (ConnectionException) {
            return null;
        }

        return is_object($decoded) ? $decoded : null;
    }
}
