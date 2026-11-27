<?php

use App\Models\Operaciones\KintaiConfig;
use App\Services\Operaciones\KintaiService;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

require_once __DIR__.'/helpers.php';

// 勤怠管理 (system/category5/kintai)

/** Hora de Tokio del 2026-11-05 (h ≥ 24 = día siguiente) */
function kt(int $hour, int $minute = 0, int $day = 5): int
{
    return CarbonImmutable::create(2026, 11, $day, 0, 0, 0, 'Asia/Tokyo')->addHours($hour)->addMinutes($minute)->getTimestamp();
}

function seedKintai(): void
{
    seedOperaciones();
    DB::table('tb_base_system_child')->insert(['cd' => 51, 'tb_base_system_cd' => 5, 'system_name' => '勤怠管理', 'system_flg' => '1', 'rank' => 2]);
    DB::table('tb_shop')->insert(['cd' => 2, 'kaishamei' => '支店', 'disp_flg' => '1']);
    DB::table('kintai_tb_config')->insert(['cd' => 1, 'tb_shop_cd' => 1, 'kijun_time' => '05<>00', 'start_time' => '09<>00', 'end_time' => '18<>00', 'password' => '9999']);
    DB::table('tb_staff')->insert([
        ['cd' => 10, 'shop_cd' => 1, 'name' => '山田', 'id' => 'E10', 'belongpost' => '営業', 'employmentsystem' => '正社員', 'felica_no' => 'F10', 'rank' => 2, 'disp_flg' => '1'],
        ['cd' => 11, 'shop_cd' => 1, 'name' => '佐藤', 'id' => 'E11', 'belongpost' => '', 'employmentsystem' => '', 'felica_no' => '', 'rank' => 1, 'disp_flg' => '1'],
        ['cd' => 12, 'shop_cd' => 2, 'name' => '支店員', 'id' => 'E12', 'belongpost' => '', 'employmentsystem' => '', 'felica_no' => '', 'rank' => 1, 'disp_flg' => '1'],
    ]);
    // 山田: 09:00 出勤, 12:00-13:00 外出/戻り, 26:30 (02:30 del día 6) 退勤; 佐藤: 有給休暇
    DB::table('kintai_tb_hiduke')->insert([
        ['cd' => 100, 'hiduke' => (string) kt(9), 'kintai_flg' => '出勤', 'tb_staff_cd' => 10, 'disp_flg' => '1', 'bikou' => "遅番<b>\n交代"],
        ['cd' => 101, 'hiduke' => (string) (kt(29) - 1), 'kintai_flg' => '有給休暇', 'tb_staff_cd' => 11, 'disp_flg' => '1', 'bikou' => ''],
    ]);
    DB::table('kintai_tb_kintai')->insert([
        ['cd' => 200, 'kintai_flg' => '1', 'kintai_time' => kt(9), 'tb_staff_cd' => 10, 'kintai_tb_hiduke_cd' => 100, 'felica_no' => 'F10', 'disp_flg' => '1'],
        ['cd' => 201, 'kintai_flg' => '3', 'kintai_time' => kt(12), 'tb_staff_cd' => 10, 'kintai_tb_hiduke_cd' => 100, 'felica_no' => 'F10', 'disp_flg' => '1'],
        ['cd' => 202, 'kintai_flg' => '4', 'kintai_time' => kt(13), 'tb_staff_cd' => 10, 'kintai_tb_hiduke_cd' => 100, 'felica_no' => 'F10', 'disp_flg' => '1'],
        ['cd' => 203, 'kintai_flg' => '2', 'kintai_time' => kt(26, 30), 'tb_staff_cd' => 10, 'kintai_tb_hiduke_cd' => 100, 'felica_no' => 'F10', 'disp_flg' => '1'],
        ['cd' => 204, 'kintai_flg' => '1', 'kintai_time' => kt(29) - 1, 'tb_staff_cd' => 11, 'kintai_tb_hiduke_cd' => 101, 'felica_no' => '', 'disp_flg' => '1'],
    ]);
}

const KT_DAY = ['yy' => '2026', 'mm' => '11', 'dd' => '05', 'shop' => '1'];

it('KintaiService: fecha como mktime, jornada con corte, horas 24+ y duración', function () {
    $config = new KintaiConfig(['kijun_time' => '05<>00']);

    expect(KintaiService::date(2026, 2, 31)->format('Y-m-d'))->toBe('2026-03-03')
        ->and(KintaiService::requestDate('1800', '0', '5'))->toBe([now('Asia/Tokyo')->year, now('Asia/Tokyo')->month, now('Asia/Tokyo')->day]) // «0» = falta
        ->and(KintaiService::requestDate('1800', '00', '05'))->toBe([1900, 1, 5])
        ->and(KintaiService::window($config, KintaiService::date(2026, 11, 5)))->toBe([kt(5), kt(29)])
        ->and(KintaiService::displayTime(kt(26, 30), 5))->toBe('26:30')
        ->and(KintaiService::displayTime(kt(9, 5), 5))->toBe('09:05')
        ->and(KintaiService::duration(kt(9), kt(26, 30)))->toBe(['17:30', 1050])
        ->and(KintaiService::duration(kt(9), kt(9) + 25 * 3600 + 59))->toBe(['01:00', 60]) // módulo 24 h, sin segundos
        ->and(KintaiService::optionHour(2, 5))->toBe('26')
        ->and(KintaiService::optionHour(5, 5))->toBe('05');
});

it('日付別勤怠データ一覧: jornadas del día, descanso con ---, horas 24+ y botones del master', function () {
    seedKintai();
    loginAs('master', 'secreto1');

    $this->get('/admin/operaciones/kintai?'.http_build_query(KT_DAY))
        ->assertOk()
        ->assertSee('<title>日付別勤怠データ一覧 - shop - </title>', false)
        ->assertSee('■<span>2026/11/05</span> 勤怠データ', false)
        ->assertSeeInOrder(['山田', '出勤', '退勤', '09:00', '12:00<br />', '13:00<br />', '26:30<br />', '遅番&lt;b&gt;<br />'], false)
        ->assertSeeInOrder(['佐藤', '有給休暇', '<td align="center" valign="middle" colspan="5">---</td>'], false)
        ->assertSee('value="編集"', false)
        ->assertSee('/admin/operaciones/kintai/100/edit?staff=10&amp;yy=2026&amp;mm=11&amp;dd=05&amp;shop=1', false)
        ->assertSee('/admin/operaciones/kintai/create?yy=2026&amp;mm=11&amp;dd=05&amp;shop=1', false)
        ->assertSee('勤怠データ一覧') // menú 勤怠管理
        ->assertSee('端末パスワード変更');

    // Otro día: vacío; la tienda 2 sin configuración la crea con los valores del legacy
    $this->get('/admin/operaciones/kintai?yy=2026&mm=11&dd=06&shop=2')->assertOk()->assertSee('勤怠データがありません。');
    expect(KintaiConfig::query()->where('tb_shop_cd', 2)->first()->only(['kijun_time', 'start_time', 'end_time', 'password']))
        ->toBe(['kijun_time' => '05<>00', 'start_time' => '08<>00', 'end_time' => '10<>00', 'password' => '9999']);
});

it('一覧: el no master ve solo su tienda, sin 編集/削除 ni los enlaces de master; editar le da 403', function () {
    seedKintai();
    staffUser(2);
    loginAs('u2', 'p');

    $this->get('/admin/operaciones/kintai?'.http_build_query(['shop' => '2'] + KT_DAY))
        ->assertOk()
        ->assertSee('本店<input type="hidden" name="shop" id="shop" value="1" />', false)
        ->assertSee('山田')
        ->assertDontSee('value="編集"', false)
        ->assertDontSee('端末パスワード変更');

    $this->get('/admin/operaciones/kintai/100/edit?staff=10&'.http_build_query(KT_DAY))->assertForbidden();
    $this->get('/admin/operaciones/kintai/config')->assertForbidden();
});

it('勤怠データ新規追加: errores del legacy, 出勤 duplicado y alta con sus marcas', function () {
    seedKintai();
    loginAs('master', 'secreto1');
    // 佐藤 sin su jornada de 有給休暇 (si no, todo error se reemplaza por «既に出勤しています»)
    DB::table('kintai_tb_hiduke')->where('cd', 101)->update(['disp_flg' => '9']);
    DB::table('kintai_tb_kintai')->where('cd', 204)->update(['disp_flg' => '9']);
    $base = KT_DAY + ['status' => '出勤', 'shukkin_h' => '', 'shukkin_m' => '', 'gaishutu_h' => '', 'gaishutu_m' => '',
        'modori_h' => '', 'modori_m' => '', 'taikin_h' => '', 'taikin_m' => '', 'bikou' => '', 'act' => '登録'];

    $this->get('/admin/operaciones/kintai/create?'.http_build_query(KT_DAY))
        ->assertOk()
        ->assertSeeInOrder(['<option value="11">佐藤</option>', '<option value="10">山田</option>'], false) // rank, cd
        ->assertDontSee('支店員')
        ->assertSeeInOrder(['<option value="05">05</option>', '<option value="23">23</option>', '<option value="24">00</option>', '<option value="28">04</option>'], false);
    $this->get('/admin/operaciones/kintai/create?yy=2026&mm=x&dd=05')->assertRedirect('/admin/operaciones/kintai');

    $this->post('/admin/operaciones/kintai', $base)->assertOk()->assertSee('・「スタッフ名」を選択してください。<br />', false);
    $this->post('/admin/operaciones/kintai', ['user' => '11', 'shukkin_m' => '30', 'gaishutu_h' => '12'] + $base)
        ->assertOk()
        ->assertSee('・「出勤時間（時）」を選択してください。<br />・「外出時間（分）」を選択してください。<br />', false);
    $this->post('/admin/operaciones/kintai', ['user' => '11', 'shukkin_h' => '10', 'shukkin_m' => '00', 'taikin_h' => '09', 'taikin_m' => '00',
        'modori_h' => '08', 'modori_m' => '00'] + $base)
        ->assertOk()
        ->assertSee('・「出勤時間」が不正な値です。（「退勤時間」を上回っています）<br />・「外出時間」を入力してください。<br />', false)
        ->assertSee('<option value="11" selected="selected">佐藤</option>', false);
    // 山田 ya fichó: el mensaje reemplaza a los demás
    $this->post('/admin/operaciones/kintai', ['user' => '10', 'shukkin_h' => '10', 'shukkin_m' => '00'] + $base)
        ->assertOk()
        ->assertSee('<p class="errorText">・山田さんは既に出勤しています。<br />&emsp;登録済みの出勤データを編集するか、一度削除してください。<br /></p>', false);
    // Empleado de otra tienda
    $this->post('/admin/operaciones/kintai', ['user' => '12', 'shukkin_h' => '10', 'shukkin_m' => '00'] + $base)
        ->assertSee('・「スタッフ名」を選択してください。', false);

    $this->post('/admin/operaciones/kintai', ['user' => '11', 'status' => '遅刻', 'shukkin_h' => '10', 'shukkin_m' => '15',
        'gaishutu_h' => '12', 'gaishutu_m' => '00', 'modori_h' => '12', 'modori_m' => '45', 'taikin_h' => '25', 'taikin_m' => '05', 'bikou' => ' ｶﾞｲｼｭﾂ '] + $base)
        ->assertOk()
        ->assertSee('<p>勤怠データを新規追加しました。</p>', false)
        ->assertSee('<input type="hidden" name="dd" value="05" />', false);

    $hiduke = DB::table('kintai_tb_hiduke')->where('tb_staff_cd', 11)->where('disp_flg', '1')->first();
    expect($hiduke->hiduke)->toBe((string) kt(10, 15))
        ->and($hiduke->kintai_flg)->toBe('遅刻')
        ->and($hiduke->bikou)->toBe('ガイシュツ')
        ->and(DB::table('kintai_tb_kintai')->where('kintai_tb_hiduke_cd', $hiduke->cd)->orderBy('kintai_time')->pluck('kintai_time', 'kintai_flg')->all())
        ->toBe(['1' => kt(10, 15), '3' => kt(12), '4' => kt(12, 45), '2' => kt(25, 5)]);
});

it('新規追加: con 勤務状況 de descanso y sin 出勤, la marca va al último segundo de la jornada', function () {
    seedKintai();
    loginAs('master', 'secreto1');
    DB::table('kintai_tb_hiduke')->where('cd', 101)->delete();

    $this->post('/admin/operaciones/kintai', KT_DAY + ['user' => '11', 'status' => '欠勤', 'act' => '登録'])->assertOk()->assertSee('勤怠データを新規追加しました。');

    expect(DB::table('kintai_tb_kintai')->where('tb_staff_cd', 11)->where('disp_flg', '1')->value('kintai_time'))->toBe(kt(29) - 1);
});

it('勤怠データ編集: pantalla, errores, horas modificadas, nueva pareja 外出/戻り y 退勤 nuevo', function () {
    seedKintai();
    loginAs('master', 'secreto1');
    $url = '/admin/operaciones/kintai/100/edit?staff=10&'.http_build_query(KT_DAY);

    $this->get($url)
        ->assertOk()
        ->assertSee('<span>2026/11/05</span> 勤怠データ', false)
        ->assertSee('<span id="userStatus1">退勤</span>', false)
        ->assertSee('<option value="出勤" selected="selected">出勤</option>', false)
        ->assertSee('<option value="26" selected="selected">02</option>', false) // 退勤 02:30 del día 6
        ->assertSee('<input type="hidden" name="gaishutu_cd[]" value="201" />', false)
        ->assertSee('id="gaishutu_h_new"', false)
        ->assertSee("遅番&lt;b&gt;\n交代</textarea>", false);
    $this->get('/admin/operaciones/kintai/100/edit?staff=11&'.http_build_query(KT_DAY))->assertOk()->assertSee('<p>勤怠データがありません。</p>', false);

    $form = KT_DAY + ['act' => 'up', 'staff' => '10', 'status' => '出勤', 'bikou' => '修正',
        'shukkin_h' => '09', 'shukkin_m' => '30', 'shukkin_cd' => '200',
        'gaishutu_h' => ['12'], 'gaishutu_m' => ['00'], 'gaishutu_cd' => ['201'],
        'modori_h' => ['13'], 'modori_m' => ['00'], 'modori_cd' => ['202'],
        'gaishutu_h_new' => '15', 'gaishutu_m_new' => '00', 'modori_h_new' => '15', 'modori_m_new' => '30',
        'taikin_h' => '26', 'taikin_m' => '00', 'taikin_cd' => '203'];

    // 新規 外出 sin su 戻り (con 退勤) y 外出 después de su 戻り
    $this->put('/admin/operaciones/kintai/100', ['modori_h_new' => '', 'modori_m_new' => ''] + $form)
        ->assertOk()
        ->assertSee('<p class="errorText">・「戻り時間」を入力してください。<br /></p>', false)
        ->assertSee('<option value="30" selected="selected">30</option>', false); // vuelve con lo enviado
    $this->put('/admin/operaciones/kintai/100', ['gaishutu_h' => ['14']] + $form)
        ->assertOk()
        ->assertSee('・「外出時間（1）」が不正な値です。（「戻り時間（1）」を上回っています）<br />', false);

    $this->put('/admin/operaciones/kintai/100', $form)->assertOk()->assertSee('<p>勤怠データを更新しました。</p>', false);

    expect(DB::table('kintai_tb_kintai')->where('kintai_tb_hiduke_cd', 100)->orderBy('kintai_time')->get(['kintai_flg', 'kintai_time', 'felica_no'])
        ->map(fn ($r) => [$r->kintai_flg, $r->kintai_time, $r->felica_no])->all())
        ->toBe([['1', kt(9, 30), 'F10'], ['3', kt(12), 'F10'], ['4', kt(13), 'F10'], ['3', kt(15), 'F10'], ['4', kt(15, 30), 'F10'], ['2', kt(26), 'F10']])
        ->and(DB::table('kintai_tb_hiduke')->where('cd', 100)->value('bikou'))->toBe('修正');

    // Sin 退勤: se crea uno nuevo
    DB::table('kintai_tb_kintai')->where('cd', 203)->delete();
    $this->put('/admin/operaciones/kintai/100', ['taikin_cd' => '', 'gaishutu_h_new' => '', 'gaishutu_m_new' => '', 'modori_h_new' => '', 'modori_m_new' => '', 'taikin_h' => '20'] + $form)
        ->assertSee('勤怠データを更新しました。');
    expect(DB::table('kintai_tb_kintai')->where('kintai_tb_hiduke_cd', 100)->where('kintai_flg', '2')->value('kintai_time'))->toBe(kt(20));
});

it('編集: un cd de otra jornada no se toca', function () {
    seedKintai();
    loginAs('master', 'secreto1');

    $this->put('/admin/operaciones/kintai/100', KT_DAY + ['staff' => '10', 'status' => '出勤', 'shukkin_h' => '08', 'shukkin_m' => '00', 'shukkin_cd' => '204'])
        ->assertSee('勤怠データを更新しました。');

    expect(DB::table('kintai_tb_kintai')->where('cd', 204)->value('kintai_time'))->toBe(kt(29) - 1);
});

it('一括削除 de marcas y 削除 de la jornada', function () {
    seedKintai();
    loginAs('master', 'secreto1');

    $this->delete('/admin/operaciones/kintai/100/records', KT_DAY + ['staff' => '10', 'cd' => ['201', '202', '204']])
        ->assertRedirect('/admin/operaciones/kintai/100/edit?staff=10&yy=2026&mm=11&dd=05&shop=1');
    expect(DB::table('kintai_tb_kintai')->where('disp_flg', '9')->orderBy('cd')->pluck('cd')->all())->toBe([201, 202]);

    // La ステータス ya no mira las marcas borradas
    DB::table('kintai_tb_kintai')->where('cd', 203)->update(['disp_flg' => '9']);
    $this->get('/admin/operaciones/kintai/100/edit?staff=10&'.http_build_query(KT_DAY))->assertSee('<span id="userStatus1">出勤</span>', false);

    $this->delete('/admin/operaciones/kintai/100', KT_DAY + ['staff' => '10'])
        ->assertRedirect('/admin/operaciones/kintai?yy=2026&mm=11&dd=05&shop=1');
    expect(DB::table('kintai_tb_hiduke')->where('cd', 100)->value('disp_flg'))->toBe('9')
        ->and(DB::table('kintai_tb_kintai')->where('kintai_tb_hiduke_cd', 100)->where('disp_flg', '1')->count())->toBe(0);
});

it('端末パスワード変更: mensajes del legacy y cambio', function () {
    seedKintai();
    loginAs('master', 'secreto1');

    $this->get('/admin/operaciones/kintai/password')->assertOk()->assertSee('端末の設定画面にはいるためのパスワードの変更ができます。');
    $this->post('/admin/operaciones/kintai/password', ['shop' => '1', 'passBefore' => ''])->assertSee('現在のパスワードを入力してください。');
    $this->post('/admin/operaciones/kintai/password', ['shop' => '1', 'passBefore' => 'あ'])->assertSee('現在のパスワードは半角英数字で入力してください。');
    $this->post('/admin/operaciones/kintai/password', ['shop' => '1', 'passBefore' => '1111'])->assertSee('現在のパスワードが正しくありません。');
    $this->post('/admin/operaciones/kintai/password', ['shop' => '1', 'passBefore' => '9999', 'passAfter' => ''])->assertSee('新しいパスワードを入力してください。');
    $this->post('/admin/operaciones/kintai/password', ['shop' => '1', 'passBefore' => '9999', 'passAfter' => 'a-b'])->assertSee('新しいパスワードは半角英数字で入力してください。');

    $this->post('/admin/operaciones/kintai/password', ['shop' => '1', 'passBefore' => '9999', 'passAfter' => 'abc123'])
        ->assertRedirect('/admin/operaciones/kintai/password/complete?shop=1');
    $this->get('/admin/operaciones/kintai/password/complete?shop=1')->assertSee('端末パスワード変更しました。');
    expect(DB::table('kintai_tb_config')->where('tb_shop_cd', 1)->value('password'))->toBe('abc123');
});

it('各種設定: horas, imagen de fondo y tipos no admitidos', function () {
    seedKintai();
    Storage::fake('public');
    loginAs('master', 'secreto1');

    $this->get('/admin/operaciones/kintai/config?shop=1')
        ->assertOk()
        ->assertSee('<title>各種設定データ - shop - </title>', false)
        ->assertSee('<option value="09" selected="selected">09</option>', false)
        ->assertSee('<option value="11">佐藤</option>', false)
        ->assertSee('/admin/operaciones/shift-kinds', false)
        ->assertDontSee('現在表示中の背景画像');

    $this->post('/admin/operaciones/kintai/config', ['shop' => '1', 'kijun_time_h' => '04', 'kijun_time_m' => '30', 'start_time_h' => '10',
        'start_time_m' => '00', 'end_time_h' => '19', 'end_time_m' => '15', 'act' => '登録', 'upfile' => UploadedFile::fake()->image('bg.png', 2048, 600)])
        ->assertRedirect('/admin/operaciones/kintai/config?shop=1');

    expect(DB::table('kintai_tb_config')->where('tb_shop_cd', 1)->first())
        ->kijun_time->toBe('04<>30')->start_time->toBe('10<>00')->end_time->toBe('19<>15')->pic_name->toBe('0.png');
    Storage::disk('public')->assertExists(['hp/uploads/c5/kintai/1/moto.png', 'hp/uploads/c5/kintai/1/thum.jpg', 'hp/uploads/c5/kintai/1/0.png']);
    expect(getimagesizefromstring(Storage::disk('public')->get('hp/uploads/c5/kintai/1/0.png'))[0])->toBe(1024)
        ->and(getimagesizefromstring(Storage::disk('public')->get('hp/uploads/c5/kintai/1/thum.jpg'))[0])->toBe(150);
    $this->get('/admin/operaciones/kintai/config?shop=1')->assertSee('width="150" height="44" alt="現在表示中の背景画像"', false);

    $this->post('/admin/operaciones/kintai/config', ['shop' => '1', 'upfile' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')]);
    $this->get('/admin/operaciones/kintai/config?shop=1')->assertSee('※jpg,bmp,gif,png以外の画像ファイルはアップロード出来ません。<br />', false);
});

it('CSV diario y mensual en SJIS como el legacy', function () {
    seedKintai();
    loginAs('master', 'secreto1');

    $daily = $this->get('/admin/operaciones/kintai/csv?'.http_build_query(KT_DAY));
    expect($daily->headers->get('Content-Type'))->toStartWith('text/x-csv');
    $daily->assertOk()->assertDownload('kintai20261105.csv');
    expect(mb_convert_encoding($daily->streamedContent(), 'UTF-8', 'SJIS-win'))->toBe(implode("\r\n", [
        '"2026/11/05の勤怠情報"',
        '"日付","名前","社員番号","出勤","外出","戻り","退勤","備考","部署","区分","総時間","分換算"',
        "\"11/05\",\"\t山田\",\"\tE10\",\"\t09:00\",\"\",\"\",\"\t26:30\",\"\t遅番<b>\n交代\",\"\t営業\",\"\t正社員\",\"17:30\",\"1050\"",
        "\"11/05\",\"\t山田\",\"\tE10\",\"\",\"\t12:00\",\"\t13:00\",\"\",\"\",\"\t営業\",\"\t正社員\",\"01:00\",\"60\"",
    ])."\r\n");

    // Día sin jornadas: archivo vacío, como el legacy
    expect($this->get('/admin/operaciones/kintai/csv?yy=2026&mm=11&dd=20&shop=1')->streamedContent())->toBe('');

    $monthly = $this->get('/admin/operaciones/kintai/csv/monthly?staff=10&yy=2026&mm=11');
    $monthly->assertOk()->assertDownload('kintai202611.csv');
    expect(mb_convert_encoding($monthly->streamedContent(), 'UTF-8', 'SJIS-win'))->toBe(implode("\r\n", [
        '"年月日","社員番号","名前","出勤","外出","戻り","退勤","備考","年","月","日","総時間","分換算"',
        "\"2026/11/05\",\"\tE10\",\"\t山田\",\"\t09:00\",\"\",\"\",\"\t26:30\",\"\t遅番<b>\n交代\",\"2026\",\"11\",\"05\",\"17:30\",\"1050\"",
        "\"\",\"\tE10\",\"\t山田\",\"\",\"\t12:00\",\"\t13:00\",\"\",\"\",\"2026\",\"11\",\"05\",\"01:00\",\"60\"",
    ])."\r\n");
});
