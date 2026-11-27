<?php

namespace App\Http\Controllers\Publico\Integraciones;

use App\Http\Controllers\Controller;
use App\Services\Integraciones\LegacyJson;
use App\Services\Integraciones\MobileShop\InvalidFilterException;
use App\Services\Integraciones\MobileShop\MobileShopApi;
use App\Services\Integraciones\MobileShop\MobileShopClient;
use App\Services\Integraciones\MobileShop\MobileShopJwt;
use App\Services\Promotions\PointService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * API JWT de la tienda móvil (`/api/*` del legacy). Contrato: misma URL, mismos parámetros y la
 * misma respuesta byte a byte, HTTP 200 también en los errores y cuerpo JSON incluso en GET.
 * Fixtures y hallazgos: tests/Fixtures/integraciones/README.md.
 */
class MobileShopController extends Controller
{
    public function __construct(
        private readonly MobileShopJwt $jwt,
        private readonly MobileShopApi $api,
        private readonly MobileShopClient $client,
    ) {
    }

    /** POST /api/auth */
    public function auth(Request $request): Response
    {
        if (! $this->jwt->isConfigured()) {
            return LegacyJson::response(['message' => 'Invalid config.']);
        }

        $data = $this->body($request);
        $userId = is_object($data) ? ($data->user_id ?? null) : null;
        $password = is_object($data) ? ($data->password ?? null) : null;

        if (! $this->jwt->validCredentials($userId, $password)) {
            return LegacyJson::response(['message' => 'Invalid User.']);
        }

        return LegacyJson::response(['message' => 'Success.', 'jwt' => $this->jwt->issue($userId)]);
    }

    /** GET /api/shop */
    public function shop(Request $request): Response
    {
        $claims = $this->claims($request);
        if ($claims instanceof Response) {
            return $claims;
        }

        $result = $request->isMethod('GET') ? $this->api->shops() : null;

        return LegacyJson::response(['message' => 'Success.', 'result' => $result]);
    }

    /** GET /api/customer */
    public function customer(Request $request): Response
    {
        $claims = $this->claims($request);
        if ($claims instanceof Response) {
            return $claims;
        }

        $result = null;
        if ($request->isMethod('GET')) {
            try {
                $result = $this->api->searchCustomers((array) $this->body($request));
            } catch (InvalidFilterException) {
                // El legacy imprimía esto antes de header(): Content-Type por defecto de PHP
                return LegacyJson::response(['message' => 'Error.'], jsonHeader: false);
            }
            if ($result === null) {
                // Sin filtros el legacy terminaba sin imprimir nada
                return new Response('', 200, ['Content-Type' => 'text/html; charset=UTF-8']);
            }
        }

        return LegacyJson::response(['message' => 'Success.', 'user_id' => $claims->user_id ?? null, 'result' => $result]);
    }

    /** GET /api/point */
    public function point(Request $request): Response
    {
        $claims = $this->claims($request);
        if ($claims instanceof Response) {
            return $claims;
        }

        $data = $this->body($request);
        if (! $this->validId($data->user_id ?? null)) {
            return LegacyJson::response(['status' => 'ng', 'message' => '必須情報がありません。']);
        }

        return LegacyJson::response(['message' => 'Success.', 'result' => $this->api->pointLog($data)]);
    }

    /** GET /api/mobile_shop/customer[/<id>] */
    public function mobileShopCustomer(Request $request, ?string $parameter = null): Response
    {
        $claims = $this->claims($request);
        if ($claims instanceof Response) {
            return $claims;
        }

        $data = $this->body($request);
        $result = [];
        if ($request->isMethod('GET') && $this->validId($parameter)) {
            $result = $this->api->customerDetail($parameter);
        } elseif ($request->isMethod('GET')) {
            $result = $this->api->searchDomainCustomers(is_object($data) ? $data : null);
        }

        return $this->success($claims, $data, $result);
    }

    /** POST /api/mobile_shop/passcode */
    public function passcode(Request $request): Response
    {
        $claims = $this->claims($request);
        if ($claims instanceof Response) {
            return $claims;
        }

        $data = $this->body($request);
        $ok = $this->api->passcodeMatches($data->shop_cd ?? null, $data->passcode ?? null);

        return LegacyJson::response($ok
            ? ['status' => 'ok']
            : ['status' => 'ng', 'message' => 'invalid passcode']);
    }

    /** GET/POST /api/mobile_shop/point: saldo del socio */
    public function mobileShopPoint(Request $request): Response
    {
        $claims = $this->claims($request);
        if ($claims instanceof Response) {
            return $claims;
        }

        $data = $this->body($request);
        if (! $this->validId($data->customer_id ?? null)) {
            return LegacyJson::response(['status' => 'ng', 'message' => 'IDがありません。']);
        }

        $customerCd = $this->api->customerCdById($data->customer_id);
        if (! $customerCd) {
            return LegacyJson::response(['status' => 'ng', 'message' => '顧客コードが見つかりません。']);
        }

        return $this->success($claims, $data, $this->api->currentPoint($customerCd));
    }

    /**
     * POST /api/mobile_shop/point_use: resta puntos con el servicio de S3 (Point::setPoint del legacy),
     * marcando el origen como el legacy: tb_terminal_cd = -3, tb_staff_cd = -1, action_flg 'api:point_use'.
     * Responde el resultado de setPoint sin envoltorio.
     */
    public function pointUse(Request $request, PointService $points): Response
    {
        $claims = $this->claims($request);
        if ($claims instanceof Response) {
            return $claims;
        }

        $data = $this->body($request);
        $errors = [];
        if (! $this->validId($data->customer_id ?? null)) {
            $errors[] = 'IDがありません。';
        }
        if (! $this->validId($data->shop_cd ?? null)) {
            $errors[] = '店舗コードがありません。';
        }
        if (! $this->validId($data->use_points ?? null)) {
            $errors[] = 'ポイント指定がありません。';
        }
        if ($errors) {
            return LegacyJson::response(['status' => 'ng', 'message' => implode(' ', $errors)]);
        }

        $customerCd = $this->api->customerCdById($data->customer_id);
        if (! $customerCd) {
            return LegacyJson::response(['status' => 'ng', 'message' => '顧客コードが見つかりません。']);
        }

        return LegacyJson::response($points->setPoint(
            (int) $data->use_points,
            PointService::SUBTRACT,
            (int) $data->shop_cd,
            -1,
            $customerCd,
            'api:point_use',
            -3,
        ));
    }

    /** GET /api/mobile_shop/sales_items[/<cd>] */
    public function salesItems(Request $request, ?string $parameter = null): Response
    {
        $claims = $this->claims($request);
        if ($claims instanceof Response) {
            return $claims;
        }

        $data = $this->body($request);
        $result = $request->isMethod('GET') && $this->validId($parameter)
            ? $this->api->salesItem((int) $parameter)
            : $this->api->salesItems((array) $data);

        return $this->success($claims, $data, $result);
    }

    /** POST /api/mobile_shop/fetch_paid_member */
    public function fetchPaidMember(Request $request): Response
    {
        $claims = $this->claims($request);
        if ($claims instanceof Response) {
            return $claims;
        }

        $data = $this->body($request);
        $result = [];
        if ($request->isMethod('POST')) {
            $result = $this->client->fetchPaidMemberFlg($this->client->fetchToken(), $data->customer_id ?? null);
        }

        return $this->success($claims, $data, $result);
    }

    /** POST /api/mobile_shop/send_unpaid_member_status */
    public function sendUnpaidMemberStatus(Request $request): Response
    {
        $claims = $this->claims($request);
        if ($claims instanceof Response) {
            return $claims;
        }

        $data = $this->body($request);
        $result = [];
        if ($request->isMethod('POST')) {
            $result = $this->client->sendUnpaidMemberStatus($this->client->fetchToken(), $data->customer_id ?? null);
        }

        return $this->success($claims, $data, $result);
    }

    /** Ruta /api/* desconocida: el legacy daba un fatal (500); aquí 404 JSON (decisión del diseño, §6) */
    public function notFound(): Response
    {
        return new Response(LegacyJson::encode(['message' => 'Not Found.']), 404, ['Content-Type' => LegacyJson::CONTENT_TYPE]);
    }

    /**
     * JWT de la cabecera `Authorization: Bearer <jwt>` (src/Util/JwtAuth.php). Sin cabecera o sin la
     * segunda palabra, el legacy daba un fatal: 500 sin cuerpo. Token inválido: {"status":"ng",…} con 200.
     */
    private function claims(Request $request): object
    {
        $authorization = $request->headers->get('Authorization');
        $jwt = $authorization === null ? null : (explode(' ', $authorization)[1] ?? null);
        if ($jwt === null) {
            return new Response('', 500, ['Content-Type' => 'text/html; charset=UTF-8']);
        }

        try {
            return $this->jwt->decode($jwt);
        } catch (Exception $e) {
            return LegacyJson::response(['status' => 'ng', 'message' => $e->getMessage()]);
        }
    }

    /** Cuerpo JSON como json_decode($body) del legacy: objeto, lista o null */
    private function body(Request $request): mixed
    {
        return json_decode($request->getContent());
    }

    /** preg_match('/^[1-9][0-9]*$/', $valor) del legacy */
    private function validId(mixed $value): bool
    {
        return (is_string($value) || is_int($value)) && preg_match('/^[1-9][0-9]*$/', (string) $value) === 1;
    }

    /** Envoltorio común de mobile_shop/*: message, user_id, payload (el cuerpo recibido) y result */
    private function success(object $claims, mixed $payload, mixed $result): Response
    {
        return LegacyJson::response([
            'message' => 'Success.',
            'user_id' => $claims->user_id ?? null,
            'payload' => $payload,
            'result' => $result,
        ]);
    }
}
