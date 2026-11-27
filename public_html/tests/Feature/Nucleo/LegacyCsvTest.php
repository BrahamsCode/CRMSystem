<?php

use App\Support\LegacyCsv;
use Illuminate\Support\Facades\DB;

// LegacyCsv (CSV en SJIS-win por bloques) y conexión `signage`

it('arma líneas con comillas como el legacy', function () {
    expect(LegacyCsv::line(['a', 'b"c', 1]))->toBe("\"a\",\"b\"\"c\",\"1\"\r\n")
        ->and(LegacyCsv::line(['a', 'b,c', 'd'], ['quote' => 'minimal']))->toBe("a,\"b,c\",d\r\n")
        ->and(LegacyCsv::line(['a', 'b'], ['quote' => 'none', 'eol' => "\n"]))->toBe("a,b\n");
});

it('descarga en SJIS-win por bloques con la cabecera del legacy', function () {
    $response = LegacyCsv::download('顧客.csv', fn () => (function () {
        foreach (range(1, 3) as $i) {
            yield [$i, "顧客{$i}", '①'];
        }
    })(), header: ['ID', '名前', '記号'], options: ['chunk' => 2]);

    ob_start();
    $response->sendContent();
    $body = ob_get_clean();

    expect($response->headers->get('Content-Type'))->toBe('text/x-csv')
        ->and($response->headers->get('Content-Disposition'))->toContain('attachment')
        ->and(mb_convert_encoding($body, 'UTF-8', 'SJIS-win'))
        ->toBe("\"ID\",\"名前\",\"記号\"\r\n\"1\",\"顧客1\",\"①\"\r\n\"2\",\"顧客2\",\"①\"\r\n\"3\",\"顧客3\",\"①\"\r\n");
});

it('la conexión signage usa la BD principal y el esquema signage si no hay DB_SIGNAGE_*', function () {
    $signage = config('database.connections.signage');

    expect($signage['driver'])->toBe('pgsql')
        ->and($signage['database'])->toBe(config('database.connections.pgsql.database'))
        ->and($signage['search_path'])->toBe('signage');

    expect(DB::connection('signage')->selectOne('show search_path')->search_path)->toBe('signage');
});
