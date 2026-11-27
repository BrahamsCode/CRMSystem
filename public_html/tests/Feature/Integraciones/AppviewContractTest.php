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

it('reproduce byte a byte las pantallas de appview grabadas del legacy', function () {
    $manifest = json_decode(file_get_contents(APPVIEW_FIXTURES.'/appview/manifest.json'), true, flags: JSON_THROW_ON_ERROR);

    foreach ($manifest as $case) {
        $name = $case['name'];
        $host = null;
        foreach ($case['headers'] ?? [] as $header) {
            if (str_starts_with($header, 'Host: ')) {
                $host = substr($header, 6);
            }
        }
        $uri = $host ? 'http://'.$host.$case['path'] : $case['path'];
        $response = $this->call($case['method'], $uri, $case['form'] ?? []);
        $expected = $case['response'];
        $expectedBody = normalizeAppview(file_get_contents(APPVIEW_FIXTURES.'/appview/'.$expected['body_file']));

        expect($response->getStatusCode())->toBe($expected['status'], "estado de {$name}")
            ->and(strtolower((string) $response->headers->get('Content-Type')))->toBe(strtolower((string) $expected['content_type']), "Content-Type de {$name}")
            ->and($response->headers->get('Location'))->toBe($expected['location'], "Location de {$name}")
            ->and(normalizeAppview($response->getContent()))->toBe($expectedBody, "cuerpo de {$name}");
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
