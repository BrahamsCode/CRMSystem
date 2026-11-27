<?php

use Illuminate\Support\Facades\DB;

require_once __DIR__ . '/../Nucleo/helpers.php';

// 機能設定 (system/category0/config/features_config.php)

const FEATURES_URL = '/system/category0/config/features_config.php';

beforeEach(function () {
    seedNucleo();
    loginAs('master', 'secreto1');
    DB::table('hatuban')->insert(['koumoku' => 'contents_master', 'kazu' => 1]);
});

it('pinta los bloques con los valores por defecto del legacy', function () {
    $this->get(FEATURES_URL)->assertOk()
        ->assertSee('<title>機能設定 -総合業務管理システム-</title>', false)
        ->assertSee('■機能設定')
        ->assertSee('id="name_input_mode_flg1" value="" checked="checked"', false)
        ->assertSee('<option value="2" selected >sound3</option>', false)
        ->assertSee('■SMTP設定')
        ->assertDontSee('クーポンのご利用ありがとうございます')
        ->assertDontSee('最終来店日から来店周期日数後のフォローメール')
        ->assertDontSee('■業務用端末設定')
        ->assertDontSee('モバイルショップ向け設定')
        ->assertDontSee('マイコレ プッシュ配信時');
});

it('guarda las banderas de admin_master', function () {
    $this->post(FEATURES_URL, ['act' => 'change_terminal_sound', 'sound_select' => [0 => '1', 1 => '5', 2 => '4']])->assertOk();
    $this->post(FEATURES_URL, ['act' => 'change_mail_footer', 'mail_footer' => [0 => '1', 2 => '1']]);
    $this->post(FEATURES_URL, ['act' => 'change_shop_category_flg', 'shop_category_flg' => '1']);
    $this->post(FEATURES_URL, ['act' => 'change_mail_send_filter', 'mail_send_filter' => '1']);
    $this->post(FEATURES_URL, ['act' => 'change_id_smtp', 'smtp_domain' => ' smtp.example.jp ', 'smtp_port' => '587', 'smtp_login_id' => 'u', 'smtp_login_pass' => 'p']);

    $admin = DB::table('admin_master')->where('cd', 1)->first();
    expect($admin->sound_select)->toBe('1<>5<>4')
        ->and($admin->mail_footer)->toBe('<>9<><>9')
        ->and($admin->shop_category_flg)->toBe(1)
        ->and($admin->mail_send_filter)->toBe('1')
        ->and($admin->smtp_domain)->toBe('smtp.example.jp');

    $this->get(FEATURES_URL)->assertSee('value="smtp.example.jp"', false)->assertSee('<option value="5" selected >いらっしゃいませ</option>', false);
});

it('crea y actualiza los valores de contents_master', function () {
    $this->post(FEATURES_URL, ['act' => 'change_name_input_mode_flg', 'name_input_mode_flg' => '1']);
    $this->post(FEATURES_URL, ['act' => 'change_name_input_mode_flg', 'name_input_mode_flg' => '1']);
    $this->post(FEATURES_URL, ['act' => 'privacy_policy_flg', 'privacy_policy_text' => "第1条\nﾃｽﾄ"]);
    $this->post(FEATURES_URL, ['act' => 'mail_send_status', 'mail_send_status' => '1']);

    expect(DB::table('contents_master')->where('field', 'name_input_mode_flg')->count())->toBe(1)
        ->and(DB::table('contents_master')->where('field', 'privacy_policy_text')->value('text_data'))->toBe("第1条\nテスト");

    $this->get(FEATURES_URL)
        ->assertSee('id="name_input_mode_flg2" value="1" checked="checked"', false)
        ->assertSee("第1条\nテスト</textarea>", false)
        ->assertSee('name="mail_send_status" value="1" checked="checked"', false);
});

it('ajusta el ciclo de la フォローメール y reescribe el filtro de los envíos', function () {
    DB::table('tb_base_system_child')->insert(['cd' => 31, 'tb_base_system_cd' => 3, 'system_name' => '来店履歴管理', 'system_flg' => '1']);
    DB::table('mailmag_master')->insert([
        ['cd' => 1, 'type_flg' => 'follow1', 'send_parameter' => '1<>4', '振分配信' => "AND sex = '1' AND shuki_heikin >= 20 AND shuki_heikin <= 100"],
        ['cd' => 2, 'type_flg' => 'follow2', 'send_parameter' => '1<>4', '振分配信' => "AND sex = '2'"],
        ['cd' => 3, 'type_flg' => 'follow3', 'send_parameter' => '1<>3', '振分配信' => 'x'],
    ]);

    $this->get(FEATURES_URL)->assertSee('name="shuki_limit1" id="shuki_limit1" class="input2S" value="20"', false);
    $this->post(FEATURES_URL, ['act' => 'change_shuki_limit', 'shuki_limit1' => '0', 'shuki_limit2' => '60']);

    expect(DB::table('admin_master')->value('shuki_limit'))->toBe('1<>60')
        ->and(DB::table('mailmag_master')->orderBy('cd')->pluck('振分配信')->all())->toBe([
            "AND sex = '1' AND shuki_heikin >= 1 AND shuki_heikin <= 60",
            "AND sex = '2' AND shuki_heikin >= 1 AND shuki_heikin <= 60",
            'x',
        ]);
});

it('reinicia los 不達配信 de los socios activos', function () {
    DB::table('tb_customer')->insert([
        ['cd' => 1, 'touroku_kubun' => '2', 'disp_flg' => '1', 'error_send_cnt' => 3, 'mailmaga_flg' => '9'],
        ['cd' => 2, 'touroku_kubun' => '1', 'disp_flg' => '1', 'error_send_cnt' => 3, 'mailmaga_flg' => '9'],
    ]);

    $this->post(FEATURES_URL, ['act' => 'reset_error_send_cnt'])->assertOk();

    expect(DB::table('tb_customer')->orderBy('cd')->get(['error_send_cnt', 'mailmaga_flg'])->map(fn ($r) => (array) $r)->all())
        ->toBe([['error_send_cnt' => 0, 'mailmaga_flg' => '1'], ['error_send_cnt' => 3, 'mailmaga_flg' => '9']]);
});

it('guarda las パスコード de モバイルショップ solo cuando cambian', function () {
    DB::table('contents_master')->insert(['cd' => 900, 'category' => 'mycolle', 'field' => 'app_layout_mode', 'text_data' => '1', 'disp_flg' => '1', 'hyouji_flg' => '1']);
    DB::table('tb_shop')->insert(['cd' => 2, 'master_cd' => 1, 'kaishamei' => '二号店', 'disp_flg' => '1']);

    $this->get(FEATURES_URL)->assertSee('モバイルショップ向け設定')->assertSee('二号店');

    $this->post(FEATURES_URL, ['act' => 'mobileshop_auth_passcode', 'shop_cd' => ['1', '2'], 'mobileshop_auth_passcode' => ['a"b', '']]);
    expect(DB::table('contents_master')->where('field', 'mobileshop_auth_passcode')->pluck('text_data', 'int_data')->all())->toBe([1 => 'a&quot;b']);

    $this->post(FEATURES_URL, ['act' => 'mobileshop_auth_passcode', 'shop_cd' => ['1', '2'], 'mobileshop_auth_passcode' => ['', '']]);
    expect(DB::table('contents_master')->where('field', 'mobileshop_auth_passcode')->pluck('text_data', 'int_data')->all())->toBe([1 => '']);
});

it('la ログ de メルマガ solo se ve y se guarda desde las IP de desarrollo', function () {
    $this->get(FEATURES_URL)->assertDontSee('メルマガ配信時のログ');
    $this->post(FEATURES_URL, ['act' => 'change_sended_log', 'sendedLogFlg' => '1', 'maxSendedLog' => '9999']);
    expect(DB::table('admin_master')->value('mailmag_sended_log'))->toBeNull();

    config(['crm.developer_ips' => ['127.0.0.1']]);
    $this->get(FEATURES_URL)->assertSee('メルマガ配信時のログ');
    $this->post(FEATURES_URL, ['act' => 'change_sended_log', 'sendedLogFlg' => '1', 'maxSendedLog' => '9999']);
    expect(DB::table('admin_master')->value('mailmag_sended_log'))->toBe('1<>1500');
});
