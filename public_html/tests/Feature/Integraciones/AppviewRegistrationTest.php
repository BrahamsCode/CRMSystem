<?php

use Illuminate\Support\Facades\DB;

/*
 * appview/member_comfirm.php → member_complete.php: alta rápida de socio con el token de la sesión. En el legacy
 * el INSERT nombra columnas que no existen en el esquema del backup y no guarda nada; aquí se guarda.
 */

beforeEach(function () {
    DB::table('tb_shop')->insert(['cd' => 1, 'kaishamei' => '本店', 'disp_flg' => '1']);
});

it('da de alta al socio con el token de member_comfirm y responde su cd a la app', function () {
    $confirm = $this->post('/appview/member_comfirm.php', ['name' => '新規 会員', 'mail' => 'n@example.test', 'tel' => '0901'])->getContent();
    preg_match('/name="_token" value="([0-9a-f]{32})"/', $confirm, $m);

    $body = $this->post('/appview/member_complete.php', ['_token' => $m[1], 'regist' => '1', 'name' => '新規 会員', 'mail' => 'n@example.test', 'tel' => '0901'])
        ->assertOk()->getContent();

    $customer = DB::table('tb_customer')->where('name', '新規 会員')->first();
    expect($customer)->not->toBeNull()
        ->and($customer->id)->toBe('1100001')
        ->and($customer->login_id)->toBe('1100001')
        ->and($customer->login_pass)->toMatch('/^\d{4}$/')
        ->and($customer->touroku_kubun)->toBe('2')
        ->and($customer->email1)->toBe('n@example.test')
        ->and($customer->kojin_birth)->toBe('//')
        ->and($body)->toContain('"customer_cd":"'.$customer->cd.'"');

    $sub = DB::table('tb_customer_sub')->where('tb_customer_cd', $customer->cd)->first();
    expect($sub->tb_search_cate_cd)->toBe(7)->and($sub->tb_search_form_cd)->toBe(10)->and($sub->form_title)->toBe('アンケート<>お住まいの形態');

    // El token se renueva: reenviar el mismo formulario no da un segundo alta
    expect($this->post('/appview/member_complete.php', ['_token' => $m[1], 'regist' => '1', 'name' => '新規 会員'])->getContent())
        ->toBe('二重投稿のため処理を中断しました。')
        ->and(DB::table('tb_customer')->where('name', '新規 会員')->count())->toBe(1);
});
