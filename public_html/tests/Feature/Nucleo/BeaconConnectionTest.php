<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

require_once __DIR__ . '/helpers.php';

/*
 * Conexión `beacon` (BD externa de ビーコン): perezosa, así la app arranca aunque esa BD no responda.
 */

it('define la conexión beacon en pgsql sin heredar la BD principal', function () {
    $config = config('database.connections.beacon');

    expect($config['driver'])->toBe('pgsql')
        ->and($config['database'])->not->toBe(config('database.connections.pgsql.database'))
        ->and($config['options'][PDO::ATTR_TIMEOUT])->toBeInt();
});

it('no conecta hasta la primera consulta y falla con un error controlado si la BD no responde', function () {
    config([
        'database.connections.beacon.host' => '127.0.0.1',
        'database.connections.beacon.port' => '1',
        'database.connections.beacon.database' => 'beacon_inexistente',
        'database.connections.beacon.username' => 'nadie',
    ]);
    DB::purge('beacon');

    $connection = DB::connection('beacon');
    expect($connection->getName())->toBe('beacon');

    // El login sigue respondiendo con la BD de beacons caída
    seedNucleo();
    $this->get('/system/login.php')->assertOk();

    expect(fn () => $connection->select('select 1'))->toThrow(QueryException::class);
    DB::purge('beacon');
});
