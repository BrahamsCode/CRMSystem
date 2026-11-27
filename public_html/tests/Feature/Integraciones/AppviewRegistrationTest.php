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

/*
 * member_regist.php / customerUpdate.php sobre los datos de las fixtures de appview (campos de tb_koumoku,
 * personalizados y bonificaciones de la tienda 1).
 */
function seedAppviewFixtures(): void
{
    DB::table('tb_shop')->delete();
    foreach (['legacy-base.sql', 'legacy-seed.sql', 'appview-seed.sql'] as $file) {
        DB::unprepared(file_get_contents(__DIR__.'/../../Fixtures/integraciones/'.$file));
    }
}

const REGIST_POST = [
    'domain' => 'test.crm-s.net', 'shop_cd' => '1', 'introducer_cd' => '1609897927001809158', 'name_1' => '新規', 'name_2' => '太郎',
    'name_kana_1' => 'ｼﾝｷ', 'name_kana_2' => 'タロウ', 'email1' => 'new@example.test', 'kojin_birth_y' => '1995', 'kojin_birth_m' => '4',
    'kojin_birth_d' => '7', 'zip' => '530-0001', 'ext_colum' => ['201' => '読書', '202' => ['音楽'], '203_y' => '2020', '203_m' => '5', '203_d' => '6'],
];

it('member_regist: alta con campos personalizados, puntos de presentación y de registro (PointService) y push (AppviewPush)', function () {
    seedAppviewFixtures();
    $push = Mockery::mock(App\Services\Integraciones\Appview\AppviewPush::class);
    $push->shouldReceive('send')->once()->withArgs(fn ($id, $shop, $title, $message, $extra, $point) => $id === '1000001'
        && $title === 'お友達紹介ポイント' && $message === '新規 太郎様 の会員登録が完了しました為50ポイントを差し上げます。' && $point === 50
        && $extra === ['system' => 'mpa', 'file' => 'signUp', 'action' => 'introduce']);
    $push->shouldReceive('send')->once()->withArgs(fn ($id, $shop, $title, $message, $extra, $point, $limit, $notification) => $id === '1100005'
        && $title === 'ご登録ありがとうございます' && $message === 'ようこそ' && $point === 30 && $notification['cd'] > 0);
    app()->instance(App\Services\Integraciones\Appview\AppviewPush::class, $push);

    $body = $this->post('/appview/member_regist.php', REGIST_POST)->assertOk()->getContent();

    $customer = DB::table('tb_customer')->where('email1', 'new@example.test')->first();
    expect($body)->toContain('会員登録が完了しました')->toContain("myAlert('ありがとうございました、100ポイントをゲットしました。'")
        ->and($customer->id)->toBe('1100005')
        ->and($customer->name)->toBe('新規 太郎')
        ->and($customer->name_kana)->toBe('シンキ タロウ')
        ->and($customer->zip)->toBe('5300001')
        ->and($customer->kojin_birth)->toBe('1995/04/07')
        ->and($customer->tb_customer_group_cd)->toBe(3)
        ->and($customer->shoukai_tb_customer_cd)->toBe(1609897927001809158)
        ->and($customer->mailmaga_flg)->toBe('1')
        ->and(DB::table('tb_customer_sub')->where('tb_customer_cd', $customer->cd)->orderBy('tb_search_form_cd')->pluck('text_data', 'tb_search_form_cd')->all())
        ->toBe([201 => '読書', 202 => '音楽<>', 203 => '2020/5/6'])
        ->and(DB::table('tb_point_log')->where('tb_customer_cd', $customer->cd)->orderBy('action_flg')->pluck('sounyu_point', 'action_flg')->all())
        ->toBe(['mycolle:newRegist' => 30, 'mycolle:signUp' => 100])
        ->and(DB::table('tb_customer_point')->where('tb_customer_cd', $customer->cd)->value('service_point'))->toBe(130);
});

it('member_regist: sellos de bienvenida (el legacy daba un fatal) y correo de confirmación con touroku_kubun = 1', function () {
    seedAppviewFixtures();
    config(['mail.default' => 'array']);
    DB::table('tb_stamp_config')->where('cd', 1)->update(['touroku_bonus' => 2]);

    $this->post('/appview/member_regist.php', ['touroku_kubun' => '1'] + REGIST_POST)->assertOk();

    $customer = DB::table('tb_customer')->where('email1', 'new@example.test')->first();
    expect($customer->touroku_kubun)->toBe('1')
        ->and($customer->stamp_count)->toBe(2)
        ->and((int) $customer->stamp_yukoukigen)->toBe(now()->startOfDay()->addDays(365)->getTimestamp() - 1)
        ->and(DB::table('tb_stamp_log')->where('tb_customer_cd', $customer->cd)->value('sounyu_stamp'))->toBe(2);
    $messages = app('mailer')->getSymfonyTransport()->messages();
    expect($messages)->toHaveCount(1);
    $mail = $messages[0]->getOriginalMessage();
    expect($mail->getSubject())->toBe('会員登録確認メール')
        ->and($mail->getFrom()[0]->getAddress())->toBe('mail_mag@test.crm-s.net')
        ->and($mail->getTextBody())->toContain('http://test.crm-s.net/appview/member_comp.php?shopid=1&kaiincd='.$customer->cd);
});

it('customerUpdate: guarda los cambios del socio y sus campos personalizados', function () {
    seedAppviewFixtures();

    $body = $this->post('/appview/customerUpdate.php', [
        'domain' => 'test.crm-s.net', 'id' => '1000001', 'shop_cd' => '1', 'name_1' => '山田', 'name_2' => '一郎', 'email1' => 'taro@example.test',
        'ext_colum' => ['201' => '釣り', '203_y' => '2021', '203_m' => '2', '203_d' => '3'],
    ])->assertOk()->getContent();

    expect($body)->toContain('変更が完了しました')
        ->and(DB::table('tb_customer')->where('id', '1000001')->value('name'))->toBe('山田 一郎')
        ->and(DB::table('tb_customer_sub')->where('tb_customer_cd', 1609897927001809158)->where('tb_search_form_cd', 201)->value('text_data'))->toBe('釣り');
});
