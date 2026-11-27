<?php

use App\Services\Operaciones\PosGateway;
use App\Services\Operaciones\StaffPhotoStore;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

require_once __DIR__.'/helpers.php';

// スタッフ新規登録 / 修正: staff_entry.php → staff_entry_confirm.php → staff_entry_register.php → staff_entry_complete.php

/** Campos mínimos válidos del formulario */
function staffEntryInput(array $values = []): array
{
    return $values + [
        'act' => 'new', 'id' => '0110002', 'shop_cd' => '1', 'name' => '山田 太郎', 'name_kana' => 'ヤマダ タロウ',
        'sex' => '男性', 'tel' => '06-1234-5678', 'login_pass' => 'pass123', 'reserve_flg' => '0', 'common_flg' => '',
        'kingaku_buai' => '', 'rank' => '', 'tb_staff_group_cd' => '', 'pic_name' => '',
    ];
}

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
});

it('スタッフ新規登録: formulario con スタッフID provisional (createID) y los desplegables del legacy', function () {
    seedOperaciones();
    DB::table('tb_belongpost')->insert(['cd' => 1, 'belongpost' => 'ホール']);
    DB::table('tb_staff_group')->insert(['cd' => 5, 'name' => '早番', 'rank' => 1, 'disp_flg' => '1']);
    loginAs('master', 'secreto1');

    $this->get('/admin/operaciones/staff/create')
        ->assertOk()
        ->assertSee('<title>スタッフ新規登録 - shop</title>', false)
        ->assertSee('■スタッフ新規登録')
        ->assertSee('<input type="hidden" name="id" id="id" class="inputS" value="1100002"', false) // tienda 1 + '1' + 00002
        ->assertSee('<option value="ホール" >ホール</option>', false)
        ->assertSee("<th>ログインID</th>\n\t<td>1100002</td>", false) // ログインID = スタッフID
        ->assertSee('<option value="5" >早番</option>', false)
        ->assertSee('<option value="">▼都道府県選択</option>', false)
        ->assertSee('スタッフ新規登録'); // menú
});

it('この内容で確認する: con errores vuelve al formulario con class="Ng" y los mensajes del legacy', function () {
    seedOperaciones();
    loginAs('master', 'secreto1');

    $this->post('/admin/operaciones/staff/confirm', staffEntryInput(['name' => '', 'name_kana' => 'やまだ', 'age' => 'x', 'login_pass' => 'パス']))
        ->assertOk()
        ->assertSee('■スタッフ新規登録 確認')
        ->assertSee('<td colspan="3" class="Ng"><input type="text" name="name"', false)
        ->assertSee('※必須入力項目です。')
        ->assertSee('※全角カタカナで入力して下さい。')
        ->assertSee('※半角英数字で入力して下さい。')
        ->assertSee('value="この内容で確認する"', false);
});

it('alta completa: confirmación, registro con createHatsuban/createID, 所属部署 nuevo, POS y completado', function () {
    seedOperaciones();
    DB::table('hatuban')->insert(['koumoku' => 'tb_staff', 'kazu' => 7]);
    DB::table('hatuban')->insert(['koumoku' => 'belongpost', 'kazu' => 1]);
    $pos = Mockery::mock(PosGateway::class);
    $pos->shouldReceive('syncStaff')->once();
    app()->instance(PosGateway::class, $pos);
    loginAs('master', 'secreto1');

    $input = staffEntryInput(['remarks' => '<b>注意</b>', 'belongpost1' => 'キッチン', 'age' => '30']);

    $this->post('/admin/operaciones/staff/confirm', $input)
        ->assertOk()
        ->assertSee('■スタッフ新規登録 確認')
        ->assertSee('<td>0612345678</td>', false) // replace_tel
        // ログインID = スタッフID (regresión: «管理者用ID@else» no compilaba la directiva y la celda salía vacía)
        ->assertSee("<th>ログインID</th>\n\t<td>0110002</td>", false)
        ->assertSee('&lt;b&gt;注意&lt;/b&gt;', false)
        ->assertSee('value="書き直す"', false)
        ->assertSee('value="この内容で登録する"', false);

    $response = $this->post('/admin/operaciones/staff/store', $input);
    $staff = DB::table('tb_staff')->where('name', '山田 太郎')->first();
    $response->assertRedirect("/admin/operaciones/staff/{$staff->cd}/complete");

    expect((string) $staff->cd)->toMatch('/^\d{10}000\d{6}$/') // createHatsuban('')
        ->and($staff->id)->toBe('1100002')
        ->and($staff->login_id)->toBe('1100002')
        ->and($staff->login_pass)->toBe('pass123')
        ->and($staff->tel)->toBe('0612345678')
        ->and($staff->remarks)->toBe('&lt;b&gt;注意&lt;/b&gt;') // get_form2
        ->and($staff->belongpost)->toBe('キッチン')
        ->and($staff->age)->toBe(30)
        ->and($staff->rank)->toBeNull()
        ->and($staff->pos_rank)->toBe(10)
        ->and($staff->office_cd)->toBe(1)
        ->and($staff->disp_flg)->toBe('1')
        ->and(DB::table('tb_belongpost')->where('belongpost', 'キッチン')->exists())->toBeTrue()
        ->and(DB::table('hatuban')->where('koumoku', 'tb_staff')->value('kazu'))->toBe(8);

    $this->get("/admin/operaciones/staff/{$staff->cd}/complete")
        ->assertOk()
        ->assertSee('■スタッフ登録情報')
        ->assertSee('続けて新規登録する')
        ->assertSee('<td colspan="3">本店</td>', false)
        ->assertSee('&lt;b&gt;注意&lt;/b&gt;', false)
        ->assertSee('キッチン');
});

it('書き直す: vuelve al formulario con lo escrito', function () {
    seedOperaciones();
    loginAs('master', 'secreto1');

    $this->post('/admin/operaciones/staff/rewrite', staffEntryInput(['name' => 'O\'Neil "X"']))
        ->assertOk()
        ->assertSee('value="O&#039;Neil &quot;X&quot;"', false)
        ->assertDontSee('class="Ng"', false);
});

it('修正: carga tb_staff des-escapado, conserva el ID y actualiza sin tocar la contraseña del master', function () {
    seedOperaciones();
    staffUser(2, ['id' => '110001', 'name' => '鈴木', 'remarks' => '&lt;i&gt;', 'tb_staff_group_cd' => 3, 'age' => 20]);
    loginAs('master', 'secreto1');

    $this->get('/admin/operaciones/staff/2/edit')
        ->assertOk()
        ->assertSee('■スタッフ登録情報 修正')
        ->assertSee('<textarea name="remarks" id="remarks" class="writeArea" rows="3" onkeyup="resize_textarea(event)">&lt;i&gt;</textarea>', false);

    $this->post('/admin/operaciones/staff/store', staffEntryInput(['act' => 'up', 'cd' => '2', 'id' => '999999', 'name' => '鈴木 一郎', 'remarks' => '<i>', 'age' => '']))
        ->assertRedirect('/admin/operaciones/staff/2/complete');

    $staff = DB::table('tb_staff')->where('cd', 2)->first();
    expect($staff->name)->toBe('鈴木 一郎')
        ->and($staff->id)->toBe('110001') // el ID no cambia al editar
        ->and($staff->remarks)->toBe('&lt;i&gt;')
        ->and($staff->age)->toBe(20) // edad vacía: se conserva
        ->and($staff->tb_staff_group_cd)->toBeNull()
        ->and($staff->login_pass)->toBe('pass123');

    // master: 管理者用パスワード, sin grupo; su contraseña no cambia
    $this->get('/admin/operaciones/staff/1/edit')->assertSee('管理者用パスワード')->assertDontSee('▼グループ選択');
    $this->post('/admin/operaciones/staff/store', staffEntryInput(['act' => 'up', 'cd' => '1', 'name' => '管理者', 'login_pass' => 'dummy']));
    expect(DB::table('tb_staff')->where('cd', 1)->value('login_pass'))->toBe('secreto1');
});

it('ID duplicado: ※既に使用されているIDです。', function () {
    seedOperaciones();
    staffUser(2, ['id' => '0110002']);
    loginAs('master', 'secreto1');

    $this->post('/admin/operaciones/staff/confirm', staffEntryInput())
        ->assertSee('※既に使用されているIDです。');
});

it('foto: subida AJAX a temporal (máx. 200 px de ancho) y, al registrar, a hp/uploads/c5/staff/<cd>/<cd>.jpg', function () {
    seedOperaciones();
    staffUser(2, ['id' => '110001']);
    loginAs('master', 'secreto1');

    $token = $this->post('/admin/operaciones/staff/photo', ['userfile' => UploadedFile::fake()->image('foto.png', 400, 300)])
        ->assertOk()->getContent();

    expect($token)->toMatch('/^\d+$/');
    [$width, $height] = getimagesizefromstring(Storage::disk('local')->get("operaciones/staff-temp/{$token}.jpg"));
    expect([$width, $height])->toBe([200, 150]);

    $this->get("/admin/operaciones/staff/photo/{$token}")->assertOk();
    $this->post('/admin/operaciones/staff/photo', ['userfile' => UploadedFile::fake()->create('x.txt', 1)])->assertSee('error');

    $this->post('/admin/operaciones/staff/store', staffEntryInput(['act' => 'up', 'cd' => '2', 'pic_name' => $token]));

    expect(DB::table('tb_staff')->where('cd', 2)->value('pic_name'))->toBe('1');
    Storage::disk('public')->assertExists(StaffPhotoStore::staffPath(2));
    Storage::disk('local')->assertMissing("operaciones/staff-temp/{$token}.jpg");

    // 画像解除
    $this->post('/admin/operaciones/staff/store', staffEntryInput(['act' => 'up', 'cd' => '2', 'pic_name' => 'delete']));
    expect(DB::table('tb_staff')->where('cd', 2)->value('pic_name'))->toBe('');
});

it('quien no es master registra solo en su tienda', function () {
    seedOperaciones();
    DB::table('tb_shop')->insert(['cd' => 2, 'kaishamei' => '支店', 'disp_flg' => '1']);
    staffUser(2);
    loginAs('u2', 'p');

    $this->post('/admin/operaciones/staff/store', staffEntryInput(['shop_cd' => '2', 'name' => '新人']))->assertRedirect();

    expect(DB::table('tb_staff')->where('name', '新人')->value('shop_cd'))->toBe(1);
});
