<?php

use App\Services\Operaciones\PosGateway;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/helpers.php';

// スタッフ検索・一覧 (staff_list.php), CSV, スタッフ登録情報 (staff_detail.php), baja, restauración y カード解除

/** Campos de búsqueda/CSV de tb_staff como en la BD del legacy (cd 43, 44, 47, 48, 51, 67, 92) */
function seedStaffFields(): void
{
    DB::table('tb_koumoku')->insert([
        ['cd' => 43, 'tb_name' => 'tb_staff', 'tb_koumoku' => 'id', 'tb_koumoku_name' => 'スタッフID', 'mail_mag_select' => '1', 'koumoku_select' => '1'],
        ['cd' => 44, 'tb_name' => 'tb_staff', 'tb_koumoku' => 'shop_cd', 'tb_koumoku_name' => '登録店舗', 'mail_mag_select' => '1', 'koumoku_select' => '1'],
        ['cd' => 47, 'tb_name' => 'tb_staff', 'tb_koumoku' => 'name', 'tb_koumoku_name' => '名前', 'mail_mag_select' => '1', 'koumoku_select' => '1'],
        ['cd' => 48, 'tb_name' => 'tb_staff', 'tb_koumoku' => 'sex', 'tb_koumoku_name' => '性別', 'mail_mag_select' => '1', 'koumoku_select' => ''],
        ['cd' => 51, 'tb_name' => 'tb_staff', 'tb_koumoku' => 'age', 'tb_koumoku_name' => '年齢', 'mail_mag_select' => '1', 'koumoku_select' => ''],
        ['cd' => 67, 'tb_name' => 'tb_staff', 'tb_koumoku' => 'entranceday', 'tb_koumoku_name' => '入社日', 'mail_mag_select' => '1', 'koumoku_select' => ''],
        ['cd' => 92, 'tb_name' => 'tb_staff', 'tb_koumoku' => 'disp_flg', 'tb_koumoku_name' => '登録区分', 'mail_mag_select' => '1', 'koumoku_select' => ''],
    ]);
}

/** Empleados de prueba: 10 ホール activos (cd 10..19) + uno borrado */
function seedStaffRows(): void
{
    DB::table('tb_shop')->insert(['cd' => 2, 'kaishamei' => '支店', 'disp_flg' => '1']);
    foreach (range(10, 19) as $cd) {
        DB::table('tb_staff')->insert([
            'cd' => $cd, 'shop_cd' => $cd % 2 ? 2 : 1, 'id' => "S{$cd}", 'name' => "ホール{$cd}", 'name_kana' => "ホール{$cd}",
            'sex' => $cd < 15 ? '男性' : '女性', 'age' => $cd + 10, 'entranceday' => '2020/04/'.($cd), 'disp_flg' => '1',
        ]);
    }
    DB::table('tb_staff')->insert(['cd' => 30, 'shop_cd' => 1, 'id' => 'S30', 'name' => '退職者', 'name_kana' => 'タイショクシャ', 'email1' => 'x@example.com', 'disp_flg' => '9']);
}

/** Búsqueda con el formulario del legacy: cada campo con su _type */
function searchStaff(array $values, int $kensu = 10)
{
    $types = ['id' => 'xxx', 'shop_cd' => 'xxx', 'name' => 'xxx', 'sex' => 'xxx', 'age' => 'between', 'entranceday' => 'date_hiduke', 'disp_flg' => 'xxx'];
    $input = ['search_staff' => 'この条件で抽出する', 'act' => 'up', 'kensu' => $kensu, 'se_flg' => '1', 'disp_flg' => '1', 'entranceday' => '0'];
    foreach ($types as $key => $type) {
        $input[$key.'_type'] = $type;
    }

    return test()->post('/admin/operaciones/staff/search', $values + $input);
}

it('スタッフ一覧: sin act solo el formulario de búsqueda con los campos de tb_koumoku', function () {
    seedOperaciones();
    seedStaffFields();
    seedStaffRows();
    loginAs('master', 'secreto1');

    $this->get('/admin/operaciones/staff')
        ->assertOk()
        ->assertSee('<title>スタッフ一覧 - shop</title>', false)
        ->assertSee('■スタッフ検索')
        ->assertSee('<input type="hidden" name="age_type" value="between">', false)
        ->assertSee('<input type="hidden" name="entranceday_type" value="date_hiduke">', false)
        ->assertSee('<option value="2">支店</option>', false)
        ->assertSee('検索条件に合ったスタッフは、見つかりませんでした。再度検索してください')
        ->assertDontSee('件見つかりました')
        ->assertSee('スタッフ検索・一覧');
});

it('スタッフ一覧: busca, pagina de 10 en 10 (orden cd DESC) y conserva la búsqueda al volver', function () {
    seedOperaciones();
    seedStaffFields();
    seedStaffRows();
    loginAs('master', 'secreto1');

    searchStaff(['name' => 'ホール'], 5)->assertRedirect('/admin/operaciones/staff?act=up&kensu=5#result');

    $this->get('/admin/operaciones/staff?act=up&kensu=5')
        ->assertOk()
        ->assertSee('<span class="text3L attention">10</span>件見つかりました。', false)
        ->assertSee('この条件でCSV出力する')
        ->assertSee('1件～5件目/10件中')
        ->assertSeeInOrder(['S19', 'S18', 'S17', 'S16', 'S15'])
        ->assertDontSee('S14')
        ->assertSee('<li class="now"><b>1</b></li>', false)
        ->assertSee('onclick="ChangePage(2)"', false);

    // ページオーバー: la última página
    $this->get('/admin/operaciones/staff?act=return&kensu=5&page=9')
        ->assertSee('6件～10件目/10件中')
        ->assertSee('S10');

    // 検索条件クリア olvida los valores del formulario pero no la última búsqueda
    $this->get('/admin/operaciones/staff')->assertDontSee('件見つかりました');
    $this->get('/admin/operaciones/staff?act=return')->assertSee('10</span>件見つかりました。', false);
});

it('スタッフ一覧: filtros por tipo (=, LIKE, between, date_hiduke, 登録区分)', function () {
    seedOperaciones();
    seedStaffFields();
    seedStaffRows();
    loginAs('master', 'secreto1');

    $count = function (array $values) {
        searchStaff($values);

        return (int) preg_replace('/\D/', '', str($this->get('/admin/operaciones/staff?act=up')->getContent())->betweenFirst('<span class="text3L attention">', '</span>'));
    };

    expect($count(['shop_cd' => '2']))->toBe(5) // numérico: =
        ->and($count(['sex' => '女性']))->toBe(5) // texto: LIKE
        ->and($count(['age' => '22', 'age2' => '24']))->toBe(3) // between
        ->and($count(['age' => '27']))->toBe(3) // solo el mínimo: >=
        ->and($count(['entranceday' => '1', 'entranceday_year' => '2020', 'entranceday_month' => '04', 'entranceday_day' => '15']))->toBe(5) // >= '2020/04/15'
        ->and($count(['disp_flg' => '9']))->toBe(1) // 削除済
        ->and($count(['shop_cd' => 'x']))->toBe(11); // numérico sin dígitos: se ignora (10 + el master)
});

it('スタッフ一覧: el valor buscado se vuelve a pintar en el formulario', function () {
    seedOperaciones();
    seedStaffFields();
    seedStaffRows();
    loginAs('master', 'secreto1');

    searchStaff(['sex' => '女性', 'age' => '20', 'name' => 'ホール1']);

    $this->get('/admin/operaciones/staff?act=up')
        ->assertSee('<option selected>女性</option>', false)
        ->assertSee('name="age" id="age" class="input3S" value="20"', false)
        ->assertSee('name="name" value="ホール1"', false);
});

it('スタッフ一覧: CSV de la búsqueda en Shift_JIS, solo para el master', function () {
    seedOperaciones();
    seedStaffFields();
    seedStaffRows();
    loginAs('master', 'secreto1');
    searchStaff(['shop_cd' => '2']);

    $response = $this->post('/admin/operaciones/staff/csv', ['titleflg' => '1']);
    $response->assertOk()->assertHeader('Content-Type', 'text/x-csv; charset=Shift_JIS');
    expect($response->headers->get('Content-Disposition'))->toContain('user.csv');

    $csv = mb_convert_encoding($response->streamedContent(), 'UTF-8', 'SJIS');
    $lines = explode("\r\n", rtrim($csv, "\r\n"));
    expect($lines[0])->toBe('"スタッフID","登録店舗","名前"')
        ->and($lines)->toHaveCount(6)
        ->and($lines[1])->toBe("\"\tS11\",\"\t支店\",\"\tホール11\"");

    staffUser(2);
    auth('admin')->logout();
    $this->flushSession();
    loginAs('u2', 'p');
    $this->post('/admin/operaciones/staff/csv', ['titleflg' => '1'])->assertForbidden();
});

it('スタッフ登録情報: las 3 pestañas, tienda, tarjeta y botones', function () {
    seedOperaciones();
    seedStaffRows();
    DB::table('tb_staff')->where('cd', 11)->update(['felica_no' => '0123ABCD', 'kingaku_buai' => 12.5, 'reserve_flg' => '1', 'traf_charge' => '500', 'pref' => '大阪府']);
    loginAs('master', 'secreto1');

    $this->get('/admin/operaciones/staff/11?act=up&page=2&kensu=10')
        ->assertOk()
        ->assertSee('<title>スタッフ登録情報 - shop</title>', false)
        ->assertSee('<td>S11</td>', false)
        ->assertSee('<td>支店</td>', false)
        ->assertSee('大阪府')
        ->assertSee('no_photo.gif')
        ->assertSee('修正する')
        ->assertSee('削除する')
        ->assertDontSee('ホームページ情報')
        ->assertSee('<input type="hidden" name="page" value="2" />', false);

    $this->get('/admin/operaciones/staff/11?tab=2')
        ->assertSee('割り当て済み')
        ->assertSee('<td>0123ABCD</td>', false)
        ->assertSee('12.5%')
        ->assertSee('受ける');

    $this->get('/admin/operaciones/staff/11?tab=3')->assertSee('500')->assertSee('円');
});

it('スタッフ登録情報: un borrado ofrece 削除を取り消す; el cd 1 no se puede borrar', function () {
    seedOperaciones();
    seedStaffRows();
    loginAs('master', 'secreto1');

    $this->get('/admin/operaciones/staff/30')
        ->assertOk()
        ->assertSee('削除を取り消す')
        ->assertDontSee('修正する');

    $this->get('/admin/operaciones/staff/1')->assertOk()->assertDontSee('削除する');
    $this->delete('/admin/operaciones/staff/1')->assertForbidden();
});

it('削除する: disp_flg 9, suelta la tarjeta y avisa al POS', function () {
    seedOperaciones();
    seedStaffRows();
    DB::table('tb_staff')->where('cd', 12)->update(['felica_no' => 'F12']);
    DB::table('tb_card')->insert(['cd' => 1, 'felica_no' => 'F12', 'staff_cd' => '12', 'card_flg' => '1']);
    DB::table('tb_work_time')->insert(['cd' => 1, 'shop_cd' => 1, 'staff_cd' => 12, 'felica_no' => 'F12', 'syukkinbi' => '2026/11/01']);
    $pos = Mockery::mock(PosGateway::class);
    $pos->shouldReceive('removeStaff')->once()->with(12);
    app()->instance(PosGateway::class, $pos);
    loginAs('master', 'secreto1');

    $this->delete('/admin/operaciones/staff/12')->assertRedirect('/admin/operaciones/staff');

    $staff = DB::table('tb_staff')->where('cd', 12)->first();
    expect($staff->disp_flg)->toBe('9')
        ->and($staff->felica_no)->toBe('')
        ->and($staff->synchronize_flg)->toBe('')
        ->and(DB::table('tb_card')->count())->toBe(0)
        ->and(DB::table('tb_work_time')->where('cd', 1)->value('felica_no'))->toBe(''); // la asistencia se conserva
});

it('削除を取り消す: confirmación y 復元する; con el ID ya reutilizado no se restaura', function () {
    seedOperaciones();
    seedStaffRows();
    loginAs('master', 'secreto1');

    $this->get('/admin/operaciones/staff/30/restore')
        ->assertOk()
        ->assertSee('登録情報は以下の通りです。')
        ->assertSee('復元する')
        ->assertDontSee('既に使用されているIDです');

    $this->post('/admin/operaciones/staff/30/restore')->assertRedirect('/admin/operaciones/staff');
    expect(DB::table('tb_staff')->where('cd', 30)->value('disp_flg'))->toBe('1');

    // De nuevo borrado y otro empleado activo con su ID
    DB::table('tb_staff')->where('cd', 30)->update(['disp_flg' => '9']);
    DB::table('tb_staff')->where('cd', 10)->update(['id' => 'S30']);

    $this->get('/admin/operaciones/staff/30/restore')
        ->assertSee('※既に使用されているIDです。')
        ->assertSee('すでに再登録されたメールアドレスまたはIDが存在するため復元できません。')
        ->assertDontSee('value="復元する"', false);
    $this->post('/admin/operaciones/staff/30/restore');
    expect(DB::table('tb_staff')->where('cd', 30)->value('disp_flg'))->toBe('9');
});

it('関連付けを解除する: borra tb_card y vacía felica_no', function () {
    seedOperaciones();
    seedStaffRows();
    DB::table('tb_staff')->where('cd', 13)->update(['felica_no' => 'F13']);
    DB::table('tb_card')->insert(['cd' => 1, 'felica_no' => 'F13', 'staff_cd' => '13', 'card_flg' => '1']);
    loginAs('master', 'secreto1');

    $this->delete('/admin/operaciones/staff/13/card')->assertRedirect('/admin/operaciones/staff');

    expect(DB::table('tb_staff')->where('cd', 13)->value('felica_no'))->toBe('')
        ->and(DB::table('tb_staff')->where('cd', 13)->value('disp_flg'))->toBe('1')
        ->and(DB::table('tb_card')->count())->toBe(0);
});
