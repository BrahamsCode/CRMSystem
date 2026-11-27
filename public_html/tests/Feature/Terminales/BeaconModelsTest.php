<?php

use App\Models\Terminales\Beacon\Beacon;
use App\Models\Terminales\Beacon\BeaconReceiver;

/* ビーコン (PROVISIONAL): solo modelos sobre la conexión `beacon` (la configura S1); sin pantallas ni BD real */

beforeEach(function () {
    // Sin la conexión de S1: una pgsql cualquiera, solo para compilar el SQL (no se conecta)
    config(['database.connections.beacon' => config('database.connections.beacon') ?? config('database.connections.pgsql')]);
});

it('usa la conexión beacon con las tablas y fechas del legacy', function () {
    expect((new Beacon)->getConnectionName())->toBe('beacon')
        ->and((new Beacon)->getTable())->toBe('beacon')
        ->and((new BeaconReceiver)->getTable())->toBe('terminal')
        ->and(Beacon::CREATED_AT)->toBe('registration_date')
        ->and(Beacon::UPDATED_AT)->toBe('modified_date');
});

it('normaliza uuid, major y minor como ajax_beaconSave.php y nace con flg = 1', function () {
    $beacon = new Beacon(['uuid' => 'ABCD-EF', 'major' => 7, 'minor' => '12']);

    expect($beacon->uuid)->toBe('abcd-ef')
        ->and($beacon->major)->toBe('0007')
        ->and($beacon->minor)->toBe('0012')
        ->and($beacon->flg)->toBe(1);
});

it('filtra por flg y dominio, y los 受信端末 por tienda, con el orden del legacy', function () {
    expect(Beacon::query()->activeFor('crm.example')->listOrder()->toSql())
        ->toBe('select * from "beacon" where "flg" = ? and "domain" = ? order by "modified_date" desc, "cd" desc')
        ->and(BeaconReceiver::query()->activeFor('crm.example')->forShop(3)->listOrder()->toSql())
        ->toBe('select * from "terminal" where "flg" = ? and "domain" = ? and "shop_cd" = ? order by "cd" desc');
});
