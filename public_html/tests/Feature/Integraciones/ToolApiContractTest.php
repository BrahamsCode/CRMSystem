<?php

use Illuminate\Support\Facades\DB;

/*
 * Contrato de system/tool/api/** y de la ventana CTI (system/desktop_request/*): cada caso grabado del
 * legacy (tests/Fixtures/integraciones/tool/manifest.json, datos de legacy-base.sql + legacy-seed.sql +
 * tool-seed.sql) se repite contra Laravel en el mismo orden y se compara estado, Content-Type, Location y
 * cuerpo byte a byte. Ver tests/Fixtures/integraciones/README.md.
 */

const TOOL_FIXTURES = __DIR__.'/../../Fixtures/integraciones';

beforeEach(function () {
    foreach (['legacy-base.sql', 'legacy-seed.sql', 'tool-seed.sql'] as $file) {
        DB::unprepared(file_get_contents(TOOL_FIXTURES.'/'.$file));
    }
    config(['services.crm.api_key' => 'fixture-key']);
});

/** htmlData (rawurlencode del HTML de createform_request) se compara sin los espacios entre etiquetas */
function normalizeToolHtmlData(string $body): string
{
    return preg_replace_callback('/"htmlData":"([^"]*)"/', function ($m) {
        $html = preg_replace('/>\s+</', '><', trim(rawurldecode($m[1])));

        return '"htmlData":"'.rawurlencode((string) $html).'"';
    }, $body);
}

it('reproduce byte a byte las respuestas del legacy de tool/api y CTI', function () {
    $manifest = json_decode(file_get_contents(TOOL_FIXTURES.'/tool/manifest.json'), true, flags: JSON_THROW_ON_ERROR);

    foreach ($manifest as $case) {
        $name = $case['name'];
        $response = $this->call($case['method'], $case['path'], $case['form'] ?? []);
        $expected = $case['response'];
        $expectedBody = file_get_contents(TOOL_FIXTURES.'/tool/'.$expected['body_file']);
        $body = $response->getContent();

        if (str_starts_with($name, 'tool_search_form')) {
            // El HTML de los campos lo genera el parcial custom_field de S2: mismo marcado, otros espacios
            [$body, $expectedBody] = [normalizeToolHtmlData($body), normalizeToolHtmlData($expectedBody)];
        }

        expect($response->getStatusCode())->toBe($expected['status'], "estado de {$name}")
            ->and(strtolower((string) $response->headers->get('Content-Type')))->toBe(strtolower((string) $expected['content_type']), "Content-Type de {$name}")
            ->and($response->headers->get('Location'))->toBe($expected['location'], "Location de {$name}")
            ->and($body)->toBe($expectedBody, "cuerpo de {$name}");
    }
});

it('addRaitenRecord registra la visita con el VisitService de S2 (tienda pedida, solo visita)', function () {
    $this->post('/system/tool/api/category1/addRaitenRecord.php', [
        'key' => 'fixture-key', 'customer' => '1610793770001489815', 'shop' => '2', 'staff' => '3', 'raiten_log_stamp_flg' => '1',
    ])->assertOk();

    $visit = DB::table('tb_raiten_rireki')->where('user_cd', 1610793770001489815)->orderByDesc('cd')->first();

    expect($visit->cd)->toBe(900) // hatuban 'raiten_rireki'
        ->and($visit->user_id)->toBe('1000002')
        ->and($visit->shop_cd)->toBe(2)
        ->and($visit->customer_sex)->toBe('女性')
        ->and(DB::table('tb_customer')->where('cd', 1610793770001489815)->value('last_raiten_date'))->toBe($visit->raiten_hiduke);
});

it('sin CRM_API_KEY configurada, tool/api no responde nada', function () {
    config(['services.crm.api_key' => '']);

    expect($this->get('/system/tool/api/category5/getStaffList.php?key=')->getContent())->toBe('');
});
