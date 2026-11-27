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
const MS_PENDING = [
    // Necesitan el servicio de puntos de S3 (setPoint)
    'ms_point_exchange_not_exchangeable', 'ms_point_exchange_missing', 'ms_point_exchange_ok',
    'ms_point_use_missing', 'ms_point_use_ok', 'ms_point_use_insufficient',
];

beforeEach(function () {
    DB::unprepared(file_get_contents(MS_FIXTURES.'/legacy-base.sql'));
    DB::unprepared(file_get_contents(MS_FIXTURES.'/legacy-seed.sql'));

    // Los mismos valores con los que se grabaron las fixtures (claves solo de prueba)
    config([
        'services.mobileshop.api_user' => 'fixture-user',
        'services.mobileshop.api_pass' => 'fixture-pass',
        'services.mobileshop.private_key_path' => MS_FIXTURES.'/keys/test-only-private.pem',
        'services.mobileshop.public_key_path' => MS_FIXTURES.'/keys/test-only-public.pem',
        'services.mobileshop.domain_list' => 'test',
        'services.mobileshop.test_database' => null,
        'services.mobileshop.extlink_url' => 'http://127.0.0.1:9',
        'services.mobileshop.extlink_pass' => '',
    ]);

    // extlink caído, como al grabar
    Http::fake(['*' => Http::failedConnection()]);
});

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

    return test()->call($case['method'], $case['path'], [], [], [], $server, $content);
}

it('reproduce byte a byte las respuestas del legacy de /api/* (salvo mpa_*)', function () {
    $manifest = json_decode(file_get_contents(MS_FIXTURES.'/legacy/manifest.json'), true, flags: JSON_THROW_ON_ERROR);
    $jwt = null;
    $compared = 0;

    foreach ($manifest as $case) {
        $name = $case['name'];
        if (str_starts_with($name, 'mpa_') || in_array($name, MS_PENDING, true) || $name === 'api_unknown_route') {
            continue;
        }

        $response = replayMobileShopCase($case, $jwt);
        $expected = $case['response'];
        $expectedBody = file_get_contents(MS_FIXTURES.'/legacy/'.$expected['body_file']);

        expect($response->getStatusCode())->toBe($expected['status'], "estado de {$name}")
            ->and(strtolower((string) $response->headers->get('Content-Type')))->toBe(strtolower((string) $expected['content_type']), "Content-Type de {$name}");

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
})->skip(fn () => ! class_exists(\Firebase\JWT\JWT::class), 'falta firebase/php-jwt (pedido a S1)');

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
