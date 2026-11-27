<?php

use App\Enums\PointMovement;
use App\Services\Integraciones\LegacyJson;
use Carbon\CarbonImmutable;

// Lo que imprime el legacy con `echo json_encode($x)` sin flags

/** Secuencia de escape JSON (barra invertida + u + 4 hex) de cada carácter, armada a mano */
function escapedUnicode(string $text): string
{
    return implode('', array_map(
        fn (string $char) => chr(92).'u'.bin2hex(mb_convert_encoding($char, 'UTF-16BE', 'UTF-8')),
        mb_str_split($text),
    ));
}

it('escapa el japonés como secuencias de escape unicode, igual que json_encode sin flags', function () {
    expect(LegacyJson::encode(['message' => '必須情報がありません。']))
        ->toBe('{"message":"'.escapedUnicode('必須情報がありません。').'"}')
        ->and(escapedUnicode('必'))->toBe(chr(92).'u5fc5');
});

it('escapa la barra de las URL', function () {
    expect(LegacyJson::encode(['imageUrl' => 'http://example.test/hp/uploads/c6/a.jpg?r=1']))
        ->toBe('{"imageUrl":"http:\/\/example.test\/hp\/uploads\/c6\/a.jpg?r=1"}');
});

it('no usa flags: 1.0 sale como 1 (sin JSON_PRESERVE_ZERO_FRACTION) y [] como lista', function () {
    expect(LegacyJson::FLAGS)->toBe(0)
        ->and(LegacyJson::encode(['a' => 1.0, 'b' => [], 'c' => null]))->toBe('{"a":1,"b":[],"c":null}');
});

it('devuelve cadena vacía si json_encode falla, como `echo false`', function () {
    expect(LegacyJson::encode(['name' => "\xB1\x31"]))->toBe('');
});

it('mantiene el orden de las claves', function () {
    expect(LegacyJson::encode(['message' => 'Success.', 'user_id' => 'u', 'result' => '']))
        ->toBe('{"message":"Success.","user_id":"u","result":""}');
});

it('pgText convierte los números a texto como pg_fetch_assoc', function () {
    $row = LegacyJson::pgText(['cd' => 12, 'rate' => 1.5, 'name' => '店舗', 'zip' => null]);

    expect($row)->toBe(['cd' => '12', 'rate' => '1.5', 'name' => '店舗', 'zip' => null])
        ->and(LegacyJson::encode($row))->toBe('{"cd":"12","rate":"1.5","name":"'.escapedUnicode('店舗').'","zip":null}');
});

it('pgText convierte booleanos a t/f, enums a su valor y fechas a Y-m-d H:i:s', function () {
    expect(LegacyJson::pgText([
        'ok' => true,
        'ng' => false,
        'type' => PointMovement::Use,
        'at' => CarbonImmutable::create(2026, 11, 27, 9, 5, 0),
    ]))->toBe(['ok' => 't', 'ng' => 'f', 'type' => '6', 'at' => '2026-11-27 09:05:00']);
});

it('pgText recorre listas y conserva los índices', function () {
    expect(LegacyJson::pgText([['cd' => 1], ['cd' => 2]]))->toBe([['cd' => '1'], ['cd' => '2']]);
});
