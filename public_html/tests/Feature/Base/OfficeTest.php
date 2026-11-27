<?php

use Illuminate\Support\Facades\DB;

require_once __DIR__ . '/../Nucleo/helpers.php';

// 基本情報確認・変更 (category0/config/config*.php) y 基本情報登録 de primer acceso (system/config*.php)

function officeForm(array $overrides = []): array
{
    return $overrides + [
        'kaishamei_kana' => 'テスト', 'kaishamei' => 'テスト株式会社', 'daihyoushamei_kana' => '', 'daihyoushamei' => '',
        'toukibi' => '', 'gyoushu' => '小売業', 'zip' => '', 'pref' => '大阪府', 'add_kana' => '', 'add' => '', 'add_number' => '',
        'add_build_kana' => '', 'add_build' => '', 'tel' => '06-1111-2222', 'fax' => '', 'kyuyo_marume' => '15',
        'start_work_time1' => '10', 'start_work_time2' => '00', 'break_time' => '60', 'hp_hyouji_name' => 'shop',
        'hp_hyouji_tel' => '', 'shop_url' => '', 'email' => 'info@example.com', 'tantou' => '担当', 'tantou_tel' => '',
        'tantou_fax' => '', 'tantou_email' => 'tantou@example.com', 'pass' => 'abc123',
    ];
}

it('edita los datos de la empresa sin tocar la clave de master', function () {
    seedNucleo();
    loginAs('master', 'secreto1');

    $this->get('/system/category0/config/config.php')->assertOk()->assertSee('■基本情報確認・変更')->assertSee('value="テスト株式会社"', false);

    $this->post('/system/category0/config/config_confirm.php', officeForm(['kaishamei' => '', 'pass' => 'ñ!', 'toukibi' => '2020/1/1']))
        ->assertOk()
        ->assertSee('※必須入力項目です。')
        ->assertSee('※半角英数字で入力して下さい。')
        ->assertSee('※入力内容が不適切な形式です。');

    $this->post('/system/category0/config/config_confirm.php', officeForm())
        ->assertOk()
        ->assertSee('value="この内容で登録する"', false)
        ->assertSee('<input type="hidden" name="tel" value="0611112222">', false);

    $this->post('/system/category0/config/config_register.php', officeForm(['tel' => '0611112222', 'act' => 'up', 'hp_hyouji_name' => '梅田']))
        ->assertOk()->assertSee('登録中です。');

    $office = DB::table('tb_office')->where('cd', 1)->first();
    expect($office->tel)->toBe('0611112222')
        ->and($office->hp_hyouji_name)->toBe('梅田')
        ->and($office->pass)->toBe('abc123')
        ->and($office->start_work_time)->toBe('10<>00')
        ->and(DB::table('tb_staff')->where('cd', 1)->value('login_pass'))->toBe('secreto1')
        ->and(DB::table('tb_staff')->where('cd', 1)->value('name'))->toBe('担当');

    // La sesión sigue válida (la clave de master no cambió)
    $this->get('/system/select.php')->assertOk();
});

it('manda al asistente de primer acceso si la empresa no tiene nombre, y allí fija la clave de master', function () {
    seedNucleo();
    DB::table('tb_office')->where('cd', 1)->update(['kaishamei' => '']);

    loginAs('master', 'secreto1')->assertRedirect('/system/config.php');

    $this->get('/system/config.php')->assertOk()->assertSee('■基本情報登録')->assertDontSee('ホームページ情報')->assertDontSee('<p class="loginName0">', false);

    $this->post('/system/config_register.php', officeForm(['act' => 'up', 'kaishamei' => '新会社', 'pass' => 'nueva1']))
        ->assertOk()->assertSee('URL=/system/select.php', false);

    expect(DB::table('tb_office')->where('cd', 1)->value('kaishamei'))->toBe('新会社')
        ->and(DB::table('tb_shop')->where('cd', 1)->value('kaishamei'))->toBe('新会社')
        ->and(DB::table('tb_staff')->where('cd', 1)->value('login_pass'))->toBe('nueva1');

    app('auth')->forgetGuards();
    $this->get('/system/select.php')->assertOk();
});
