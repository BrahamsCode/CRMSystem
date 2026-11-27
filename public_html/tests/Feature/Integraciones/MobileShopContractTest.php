<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/*
 * Contrato de la API JWT de la tienda móvil: cada caso del manifiesto grabado del legacy
 * (tests/Fixtures/integraciones/legacy/manifest.json) se repite contra Laravel en el mismo orden,
 * con los mismos datos (legacy-base.sql + legacy-seed.sql), y se compara estado, Content-Type
 * y cuerpo byte a byte. Ver tests/Fixtures/integraciones/README.md.
 */

const MS_FIXTURES = __DIR__.'/../../Fixtures/integraciones';

/** Casos que todavía no tienen endpoint en Laravel (o se comparan aparte) */
const MS_PENDING = [];

beforeEach(function () {
    // firebase/php-jwt es dependencia del proyecto (composer.json de S1): sin él, el vendor del
    // worktree está desactualizado. Se avisa con claridad en vez de fallar en /api/auth.
    if (! class_exists(\Firebase\JWT\JWT::class)) {
        $this->markTestSkipped('falta firebase/php-jwt en vendor: ejecutar composer install en este worktree');
    }

    DB::unprepared(file_get_contents(MS_FIXTURES.'/legacy-base.sql'));
    DB::unprepared(file_get_contents(MS_FIXTURES.'/legacy-seed.sql'));

    // Los mismos valores con los que se grabaron las fixtures (claves solo de prueba). Se fijan aquí
    // para que la prueba no dependa del .env ni de la config cacheada del worktree.
    $keys = realpath(MS_FIXTURES.'/keys');
    config([
        'services.mobileshop.private_key_path' => $keys.'/test-only-private.pem',
        'services.mobileshop.public_key_path' => $keys.'/test-only-public.pem',
        'services.mobileshop.api_user' => 'fixture-user',
        'services.mobileshop.api_pass' => 'fixture-pass',
        'services.mobileshop.domain_list' => 'test',
        'services.mobileshop.test_database' => null,
        'services.mobileshop.extlink_url' => 'http://127.0.0.1:9',
        'services.mobileshop.extlink_enabled' => true, // encendido: la petición «sale» y Http::fake la hace fallar
        'services.mobileshop.extlink_pass' => '',
    ]);

    // extlink caído, como al grabar
    Http::fake(['*' => Http::failedConnection()]);
});

/** JWT emitido por /api/auth con las credenciales de prueba; falla con la respuesta si no lo hay */
function mobileShopToken(): string
{
    $response = test()->call('POST', '/api/auth', [], [], [], ['CONTENT_TYPE' => 'application/json'],
        json_encode(['user_id' => 'fixture-user', 'password' => 'fixture-pass']));
    $jwt = json_decode($response->getContent())->jwt ?? null;

    expect($jwt)->toBeString("/api/auth no devolvió jwt: HTTP {$response->getStatusCode()} {$response->getContent()}");

    return $jwt;
}

/** Repite un caso del manifiesto contra Laravel */
function replayMobileShopCase(array $case, ?string $jwt)
{
    $server = [];
    if (($case['auth'] ?? false) && $jwt) {
        $server['HTTP_AUTHORIZATION'] = 'Bearer '.$jwt;
    }
    foreach ($case['headers'] ?? [] as $header) {
        [$name, $value] = array_map('trim', explode(':', $header, 2));
        $server['HTTP_'.strtoupper(str_replace('-', '_', $name))] = $value;
    }

    $content = null;
    if (array_key_exists('json', $case)) {
        // Igual que tools/capture.php: el manifiesto se lee como array, {} se envía como []
        $content = json_encode($case['json'], JSON_UNESCAPED_UNICODE);
        $server['CONTENT_TYPE'] = 'application/json';
    }

    return test()->call($case['method'], $case['path'], $case['form'] ?? [], [], [], $server, $content);
}

it('reproduce byte a byte las respuestas del legacy de /api/* y mpa_*', function () {
    $manifest = json_decode(file_get_contents(MS_FIXTURES.'/legacy/manifest.json'), true, flags: JSON_THROW_ON_ERROR);
    $jwt = null;
    $compared = 0;
    // Misma hora en mpa_setraiten_ok y _interval: la segunda visita cae en el intervalo, como al grabar
    \Illuminate\Support\Carbon::setTestNow(now());
    \Illuminate\Support\Facades\Storage::fake('public');

    foreach ($manifest as $case) {
        $name = $case['name'];
        if (in_array($name, MS_PENDING, true) || $name === 'api_unknown_route') {
            continue;
        }

        $response = replayMobileShopCase($case, $jwt);

        if ($name === 'mpa_setraiten_ok') {
            // El legacy registraba la visita y daba un fatal (500); Laravel responde ok (corrección anotada)
            expect($response->getStatusCode())->toBe(200)
                ->and(json_decode($response->getContent(), true))->toBe(['status' => 'ok', 'customer_info' => ['cd' => '1610793770001489815', 'id' => '1000002', 'name' => '佐藤 花子']]);

            continue;
        }
        $expected = $case['response'];
        $expectedBody = file_get_contents(MS_FIXTURES.'/legacy/'.$expected['body_file']);

        expect($response->getStatusCode())->toBe($expected['status'], "estado de {$name}")
            ->and((string) $response->headers->get('Content-Type'))->toBe((string) $expected['content_type'], "Content-Type de {$name}")
            ->and($response->headers->get('Location'))->toBe($expected['location'] ?? null, "Location de {$name}");

        if ($name === 'api_auth_ok') {
            // El token cambia en cada emisión: misma forma y claims válidos
            $body = json_decode($response->getContent());
            expect(array_keys((array) $body))->toBe(['message', 'jwt'])
                ->and($body->message)->toBe('Success.');
            $jwt = $body->jwt;
            [, $payload] = explode('.', $jwt);
            $claims = json_decode(base64_decode(strtr($payload, '-_', '+/')));
            expect($claims->user_id)->toBe('fixture-user')
                ->and($claims->exp - $claims->iat)->toBe(30 * 24 * 60 * 60);

            continue;
        }

        expect($response->getContent())->toBe($expectedBody, "cuerpo de {$name}");
        $compared++;
    }

    expect($compared)->toBeGreaterThan(30);
});

it('point_use resta puntos y deja el rastro del legacy en tb_point_log (terminal -3, staff -1)', function () {
    $this->call('POST', '/api/mobile_shop/point_use', [], [], [],
        ['HTTP_AUTHORIZATION' => 'Bearer '.mobileShopToken(), 'CONTENT_TYPE' => 'application/json'],
        json_encode(['customer_id' => 1000001, 'shop_cd' => 1, 'use_points' => 10]))
        ->assertOk();

    $log = DB::table('tb_point_log')->where('action_flg', 'api:point_use')->first();

    expect(DB::table('tb_customer_point')->where('tb_customer_cd', 1609897927001809158)->value('service_point'))->toBe(490)
        ->and($log->tb_terminal_cd)->toBe(-3)
        ->and($log->tb_staff_cd)->toBe(-1)
        ->and($log->tb_shop_cd)->toBe(1)
        ->and($log->shori_mae_point)->toBe(500)
        ->and($log->shouhi_point)->toBe(10);
});

it('responde 404 JSON a una ruta /api desconocida (el legacy daba un fatal 500)', function () {
    $this->get('/api/nope')
        ->assertNotFound()
        ->assertHeader('Content-Type', 'application/json; charset=UTF-8');

    expect($this->get('/api/nope')->getContent())->toBe('{"message":"Not Found."}');
});

it('no exige CSRF en los POST de la API', function () {
    $this->post('/api/auth', [], ['CONTENT_TYPE' => 'application/json'])
        ->assertOk();
});

it('sin cabecera Authorization responde 500 sin cuerpo, como el legacy', function () {
    $response = $this->get('/api/shop');

    expect($response->getStatusCode())->toBe(500)
        ->and($response->getContent())->toBe('');
});
