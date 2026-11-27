<?php

use App\Services\Integraciones\Appview\LegacyPassword;
use Illuminate\Support\Facades\DB;

/*
 * Contrato de appview/*.php (WebView de la app): cada caso grabado del legacy (tests/Fixtures/integraciones/appview/
 * manifest.json, datos de legacy-base.sql + legacy-seed.sql + appview-seed.sql) se repite contra Laravel en el
 * mismo orden y se compara estado, Content-Type, Location y cuerpo byte a byte. Del legacy se quitan los avisos
 * de PHP 8.5 que crm2-php8 imprime (class/Customer.php activa display_errors) y el token aleatorio de
 * member_comfirm.php. Ver tests/Fixtures/integraciones/README.md.
 */

const APPVIEW_FIXTURES = __DIR__.'/../../Fixtures/integraciones';

beforeEach(function () {
    foreach (['legacy-base.sql', 'legacy-seed.sql', 'appview-seed.sql'] as $file) {
        DB::unprepared(file_get_contents(APPVIEW_FIXTURES.'/'.$file));
    }
});

function normalizeAppview(string $body): string
{
    $body = (string) preg_replace('#<br />\r?\n<b>(Deprecated|Warning|Notice)</b>:  .*?<br />\r?\n#', '', $body);

    return (string) preg_replace('/name="_token" value="[0-9a-f]{32}"/', 'name="_token" value="TOKEN"', $body);
}

/** El id de sesión de las redirecciones de coupon_control.php (PHPSESSID del legacy, sesión de Laravel aquí) */
function normalizeSessionId(?string $location): ?string
{
    return $location === null ? null : (string) preg_replace('/PHPSESSID=[^&]+/', 'PHPSESSID=SID', $location);
}

it('reproduce byte a byte las pantallas de appview grabadas del legacy', function () {
    $manifest = json_decode(file_get_contents(APPVIEW_FIXTURES.'/appview/manifest.json'), true, flags: JSON_THROW_ON_ERROR);

    foreach ($manifest as $case) {
        $name = $case['name'];
        if ($name === 'av_coupon_control_use') {
            // El legacy da 500 (constante `N` sin definir) sin usar el cupón; Laravel lo usa (prueba aparte). Se
            // salta para que los casos siguientes partan del mismo estado que al grabar.
            continue;
        }
        $host = null;
        $server = [];
        foreach ($case['headers'] ?? [] as $header) {
            [$key, $value] = explode(': ', $header, 2);
            if ($key === 'Host') {
                $host = $value;
            } else {
                $server['HTTP_'.strtoupper(str_replace('-', '_', $key))] = $value;
            }
        }
        $uri = $host ? 'http://'.$host.$case['path'] : $case['path'];
        $this->flushSession(); // cada caso se grabó sin cookies: sesión nueva
        $response = $this->call($case['method'], $uri, $case['form'] ?? [], [], [], $server);
        $expected = $case['response'];
        $expectedBody = normalizeAppview(file_get_contents(APPVIEW_FIXTURES.'/appview/'.$expected['body_file']));
        $body = normalizeAppview($response->getContent());
        if ($name === 'av_member_complete') {
            // cd del socio nuevo (createHatsuban: hora + azar); el alta se comprueba en AppviewRegistrationTest
            [$body, $expectedBody] = preg_replace('/"customer_cd":"\d+"/', '"customer_cd":"CD"', [$body, $expectedBody]);
        }

        expect($response->getStatusCode())->toBe($expected['status'], "estado de {$name}: ".$response->exception?->getMessage())
            ->and(strtolower((string) $response->headers->get('Content-Type')))->toBe(strtolower((string) $expected['content_type']), "Content-Type de {$name}")
            ->and(normalizeSessionId($response->headers->get('Location')))->toBe(normalizeSessionId($expected['location']), "Location de {$name}")
            ->and($body)->toBe($expectedBody, "cuerpo de {$name}");
    }
});

it('qr_code: el QR lleva "<host>@<customer_cd>" ofuscado con la clave del legacy', function () {
    $body = $this->get('http://crm.example.test/appview/qr_code.php?customer_cd=1609897927001809158')->getContent();

    preg_match('/qr_img\.php\?d=([^&]+)&/', $body, $m);
    expect(LegacyPassword::decode($m[1]))->toBe('crm.example.test@1609897927001809158');
});

it('member_comfirm guarda en la sesión el token que muestra', function () {
    $body = $this->post('/appview/member_comfirm.php', ['name' => 'a'])->getContent();

    preg_match('/name="_token" value="([0-9a-f]{32})"/', $body, $m);
    expect(session('appview.customer_regist'))->toBe($m[1]);
});
