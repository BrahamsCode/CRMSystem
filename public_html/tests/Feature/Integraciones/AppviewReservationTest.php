<?php

use Illuminate\Support\Facades\DB;

/*
 * Uso real de la reserva web desde la app (S2, r/ y reserve/): appview/member_login.php no deja sesión, solo
 * devuelve a la app el cd del socio por postMessage; la app (マイコレ) abre r/index.php con
 * token = md5(cd:touroku_date) y es la propia reserva la que rellena session('mobile') (user_cd, user_id,
 * user_name, email1, tel), como el legacy. La otra vía del legacy era el login de hp/user (fuera de alcance).
 */

beforeEach(function () {
    foreach (['legacy-base.sql', 'legacy-seed.sql', 'appview-seed.sql'] as $file) {
        DB::unprepared(file_get_contents(__DIR__.'/../../Fixtures/integraciones/'.$file));
    }
});

it('login de appview → token de la app → r/ rellena la sesión mobile del socio', function (string $folder) {
    $login = $this->post('/appview/member_login.php', ['login_id' => '1000001', 'login_pass' => 'pass1'])->getContent();
    preg_match('/"customer_cd":"(\d+)"/', $login, $m);
    expect($m[1] ?? null)->toBe('1609897927001809158')
        ->and(session()->has('mobile'))->toBeFalse(); // el login de appview no deja sesión

    $customer = DB::table('tb_customer')->where('cd', (int) $m[1])->first();
    $token = md5($customer->cd.':'.$customer->touroku_date);

    $this->get('https://localhost/'.$folder.'/index.php?token='.$token)->assertOk();

    expect(session('mobile'))->toMatchArray([
        'user_cd' => '1609897927001809158', 'user_id' => '1000001', 'user_name' => '山田 太郎', 'email1' => 'taro@example.test',
    ]);
})->with(['r', 'reserve']);

it('un token que no es de un socio activo no abre sesión', function () {
    $token = md5('1616386984001912810:1700000400'); // socio dado de baja

    $this->get('https://localhost/r/index.php?token='.$token)->assertOk();

    expect(session('mobile.user_cd'))->toBeNull();
});
