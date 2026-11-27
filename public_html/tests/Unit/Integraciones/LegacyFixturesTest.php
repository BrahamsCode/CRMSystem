<?php

use App\Services\Integraciones\LegacyJson;

// Las fixtures de tests/Fixtures/integraciones/legacy son respuestas reales del legacy
// (README de esa carpeta). Aquí se valida el manifiesto y que LegacyJson reproduce
// sus bytes: decodificar y volver a codificar cada cuerpo JSON debe dar lo mismo.

function legacyFixtureDir(): string
{
    return dirname(__DIR__, 2).'/Fixtures/integraciones/legacy';
}

/** @return array<int, array<string, mixed>> */
function legacyManifest(): array
{
    return json_decode(file_get_contents(legacyFixtureDir().'/manifest.json'), true, flags: JSON_THROW_ON_ERROR);
}

it('el manifiesto tiene nombre, petición y respuesta de cada caso, y su cuerpo existe', function () {
    $manifest = legacyManifest();

    expect($manifest)->not->toBeEmpty();

    foreach ($manifest as $case) {
        expect($case)->toHaveKeys(['name', 'method', 'path', 'response'])
            ->and($case['response'])->toHaveKeys(['status', 'content_type', 'body_file'])
            ->and(file_exists(legacyFixtureDir().'/'.$case['response']['body_file']))->toBeTrue("falta el cuerpo de {$case['name']}");
    }

    expect(array_unique(array_column($manifest, 'name')))->toHaveCount(count($manifest));
});

it('LegacyJson::encode reproduce byte a byte cada cuerpo JSON del legacy', function () {
    $checked = 0;

    foreach (legacyManifest() as $case) {
        $body = file_get_contents(legacyFixtureDir().'/'.$case['response']['body_file']);
        if ($body === '' || ! in_array($body[0], ['{', '['], true)) {
            continue;
        }

        // Objetos como stdClass: así {} y [] se conservan como en el original
        $decoded = json_decode($body, false, 512, JSON_BIGINT_AS_STRING | JSON_THROW_ON_ERROR);

        expect(LegacyJson::encode($decoded))->toBe($body, "no coincide {$case['name']}");
        $checked++;
    }

    expect($checked)->toBeGreaterThan(30);
});

it('documenta los casos en que el legacy responde 500 sin cuerpo', function () {
    $fatal = collect(legacyManifest())
        ->filter(fn (array $case) => $case['response']['status'] === 500)
        ->pluck('name')
        ->sort()
        ->values()
        ->all();

    expect($fatal)->toBe([
        'api_shop_without_authorization',
        'api_unknown_route',
        'mpa_setraiten_ok',
        'ms_passcode_without_authorization',
    ]);
});
