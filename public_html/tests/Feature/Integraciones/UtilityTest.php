<?php

use Illuminate\Support\Facades\DB;

/* Utilitarios públicos: /version/crm_version.php, /ptag/<ID> y /air/air_info_out.php (§1.10 del diseño) */

it('crm_version.php responde vacío como el legacy (archivo vacío en crm2-php8 y en development)', function () {
    $response = $this->get('/version/crm_version.php');

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getContent())->toBe('')
        ->and($response->headers->get('Content-Type'))->toBe('text/html; charset=UTF-8');
});

it('ptag: redirige a la URL del enlace, registra el acceso con el socio de la cookie UID y responde los textos del legacy', function () {
    DB::table('tb_url_list')->insert([
        ['cd' => 1, 'id' => 'TAG000000007', 'url' => 'https://example.test/?a=1&amp;b=2', 'disp_flg' => '1', 'hyouji_flg' => '1'],
        ['cd' => 2, 'id' => 'TAG000000008', 'url' => 'https://hidden.test/', 'disp_flg' => '1', 'hyouji_flg' => '9'],
    ]);
    DB::table('tb_customer')->insert(['cd' => 5, 'login_id' => 'u1', 'login_pass' => 'p1', 'disp_flg' => '1', 'touroku_kubun' => '2']);

    $this->call('GET', '/ptag/TAG000000007', [], [], [], ['HTTP_COOKIE' => 'UID='.md5('u1/p1')])
        ->assertStatus(302)
        ->assertHeader('Location', 'https://example.test/?a=1&b=2');
    $this->get('/ptag/TAG000000007')->assertStatus(302);

    $rows = DB::table('url_transfer_data')->orderBy('tb_customer_cd')->get();
    expect($rows)->toHaveCount(2)
        ->and($rows->pluck('tb_customer_cd')->all())->toContain(5)
        ->and($rows->pluck('data_id')->unique()->all())->toBe(['TAG000000007']);

    expect($this->get('/ptag/TAG000000008')->getContent())->toBe('無効なIDです。')
        ->and($this->get('/ptag/')->getContent())->toBe('GETデータがありません');
});

it('air_info_out: la última información visible para el personal (master: todas)', function () {
    DB::table('tb_staff')->insert([
        ['cd' => 1, 'login_id' => 'master', 'shop_cd' => 1, 'belongpost' => '', 'disp_flg' => '1'],
        ['cd' => 2, 'login_id' => 's2', 'shop_cd' => 2, 'belongpost' => 'ホール', 'disp_flg' => '1'],
    ]);
    $at = mktime(9, 5, 0, 3, 4, 2026);
    DB::table('tb_information')->insert([
        ['cd' => 1, 'shop_cd' => null, 'comment' => '全店', 'belongpost' => '', 'employmentsystem' => '', 'officialposition' => '', 'update' => (string) $at, 'disp_flg' => '1'],
        ['cd' => 2, 'shop_cd' => 1, 'comment' => '本店だけ', 'belongpost' => '', 'employmentsystem' => '', 'officialposition' => '', 'update' => (string) ($at + 60), 'disp_flg' => '1'],
    ]);

    expect($this->get('/air/air_info_out.php?login_id=master')->getContent())->toBe('update=2026年3月4日 09:06&generation=本店だけ')
        ->and($this->get('/air/air_info_out.php?login_id=s2')->getContent())->toBe('update=2026年3月4日 09:05&generation=全店');
});
