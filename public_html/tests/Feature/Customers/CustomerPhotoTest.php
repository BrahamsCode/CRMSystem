<?php

require_once __DIR__ . '/../Reservations/helpers.php';

use App\Models\Customers\Customer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

// C4-c: マイアルバム (inc/photoalbum_new/_update/_frm/_register/_delete.php) y la subida inc/ajax_picupload.php

beforeEach(function () {
    reservasLogin();
    Storage::fake('public');
    Carbon::setTestNow(Carbon::parse('2026-05-20 12:00', 'Asia/Tokyo'));
});

afterEach(fn () => Carbon::setTestNow());

/** Sube una imagen por la URL legacy del AjaxUpload y devuelve el pic_name que responde */
function clientesPhotoUpload($test, string $temp = 'abc123pic0', int $width = 400, int $height = 300): string
{
    return $test->post('/system/category1/inc/ajax_picupload.php?up=true', [
        'file' => 'picfile_0',
        'ext' => 'jpg',
        'tempfilename' => $temp,
        'picfile_0' => UploadedFile::fake()->image('a.jpg', $width, $height),
    ])->assertOk()->getContent();
}

/** Fila de tb_photo_album del cliente */
function clientesPhotoAlbum(int $customerCd, array $attributes = []): int
{
    DB::table('tb_photo_album')->insert($attributes + [
        'cd' => 70, 'tb_customer_cd' => $customerCd, 'comment' => '前回の写真', 'disp_date' => (string) Carbon::create(2026, 4, 1, 0, 0, 0, 'Asia/Tokyo')->getTimestamp(),
        'pic_flg' => '1', 'hyouji_flg' => '1', 'disp_flg' => '1', 'touroku_date' => 1, 'update_date' => 1,
    ]);

    return $attributes['cd'] ?? 70;
}

it('carga 新規登録 con los textos del legacy y la fecha de hoy', function () {
    $customer = Customer::factory()->create(['name' => '山田 花子', 'id' => '1100123']);

    $this->get(route('admin.clientes.photos.create', [$customer->cd, 'tab' => 7]))
        ->assertOk()
        ->assertSee('■フォトアルバム 新規登録')
        ->assertSee('顧客ID')
        ->assertSee('1100123')
        ->assertSee('山田 花子')
        ->assertSee('value="2026/05/20"', false)
        ->assertSee('(例: 2009/12/01)')
        ->assertSee('value="この内容で登録する"', false)
        ->assertSee('legacy/system/category1/inc/js/pic_upload.js', false);
});

it('ajax_picupload.php guarda el temporal y su miniatura, y responde el <img> con ?f=', function () {
    $name = clientesPhotoUpload($this);

    expect($name)->toBe('abc123pic0.jpg');
    Storage::disk('public')->assertExists('system/category1/customer/uploads/abc123pic0.jpg');
    Storage::disk('public')->assertExists('system/category1/customer/uploads/abc123pic0th.jpg');
    // '240x>': la miniatura baja a 240 de ancho
    expect(getimagesizefromstring(Storage::disk('public')->get('system/category1/customer/uploads/abc123pic0th.jpg'))[0])->toBe(240);

    $this->get('/system/category1/inc/ajax_picupload.php?f=abc123pic0.jpg')
        ->assertOk()->assertSee('<img src="/system/category1/customer/uploads/abc123pic0th.jpg?t=', false);
    $this->get('/system/category1/customer/uploads/abc123pic0th.jpg')->assertOk();
    $this->get('/system/category1/customer/uploads/nada.jpg')->assertNotFound();
});

it('ajax_picupload.php responde error: sin nombre temporal o si no es una imagen', function () {
    $this->post('/system/category1/inc/ajax_picupload.php?up=true', ['file' => 'picfile_0', 'tempfilename' => ''])
        ->assertSee('error:エラーが発生しました。');
    $this->post('/system/category1/inc/ajax_picupload.php?up=true', [
        'file' => 'picfile_0', 'ext' => 'jpg', 'tempfilename' => 'xyz',
        'picfile_0' => UploadedFile::fake()->create('a.jpg', 1, 'text/plain'),
    ])->assertSee('error:エラーが発生しました。もう一度アップロードしてください。');
    Storage::disk('public')->assertMissing('system/category1/customer/uploads/xyz.jpg');
});

it('valida como el legacy: imagen obligatoria y fecha válida', function () {
    $customer = Customer::factory()->create();

    $this->post(route('admin.clientes.photos.store', $customer->cd), ['kaiin_cd' => $customer->cd, 'hiduke' => '', 'pic_name' => ['']])
        ->assertOk()->assertSee('※画像は必須です。')->assertSee('※必須入力項目です。');

    $name = clientesPhotoUpload($this);
    $this->post(route('admin.clientes.photos.store', $customer->cd), ['kaiin_cd' => $customer->cd, 'hiduke' => '2026/13/01', 'pic_name' => [$name]])
        ->assertOk()->assertSee('※2026/13/01は不適切な形式です。')->assertDontSee('※画像は必須です。')
        // La foto subida se conserva al volver al formulario
        ->assertSee('/system/category1/customer/uploads/abc123pic0th.jpg', false);

    // pic_name que no es un temporal subido (p. ej. ../) cuenta como vacío
    $this->post(route('admin.clientes.photos.store', $customer->cd), ['kaiin_cd' => $customer->cd, 'hiduke' => '2026/05/01', 'pic_name' => ['../../.env']])
        ->assertSee('※画像は必須です。');
    expect(DB::table('tb_photo_album')->count())->toBe(0);
});

it('registra la foto: fila con hatuban, tamaños en hp/uploads/c1/photoalbum y vuelta a la ficha', function () {
    $customer = Customer::factory()->create();
    $name = clientesPhotoUpload($this);

    $this->post(route('admin.clientes.photos.store', $customer->cd), [
        'kaiin_cd' => $customer->cd, 'tab' => '7', 'hiduke' => '2026/05/01', 'comment' => " 誕生日の\"写真\" ", 'pic_name' => [$name], 'add_record_verify' => '111',
    ])->assertOk()->assertSee('■フォトアルバム 新規登録')->assertSee('登録中です。')->assertSee('photoalbum_cd=', false);

    $row = DB::table('tb_photo_album')->where('tb_customer_cd', $customer->cd)->first();
    expect($row->pic_flg)->toBe('1')
        ->and($row->disp_flg)->toBe('1')
        ->and($row->hyouji_flg)->toBe('1')
        ->and($row->comment)->toBe('誕生日の写真') // get_form(): quita las comillas dobles
        ->and((int) $row->disp_date)->toBe(Carbon::create(2026, 5, 1, 0, 0, 0, 'Asia/Tokyo')->getTimestamp())
        ->and(DB::table('hatuban')->where('koumoku', 'tb_photo_album')->exists())->toBeTrue();

    $disk = Storage::disk('public');
    $dir = "hp/uploads/c1/photoalbum/{$row->cd}";
    foreach (['moto', 'l', 'm', 's', 'ss'] as $suffix) {
        $disk->assertExists("{$dir}/0_{$suffix}.jpg");
    }
    $size = fn (string $suffix) => array_slice(getimagesizefromstring($disk->get("{$dir}/0_{$suffix}.jpg")), 0, 2);
    expect($size('l'))->toBe([400, 300])   // '480x>': no amplía
        ->and($size('m'))->toBe([240, 180])
        ->and($size('s'))->toBe([150, 113])
        ->and($size('ss'))->toBe([75, 56]);
    $disk->assertMissing('system/category1/customer/uploads/abc123pic0.jpg');

    // El mismo add_record_verify no registra dos veces
    clientesPhotoUpload($this, 'otra');
    $this->post(route('admin.clientes.photos.store', $customer->cd), [
        'kaiin_cd' => $customer->cd, 'hiduke' => '2026/05/01', 'pic_name' => ['otra.jpg'], 'add_record_verify' => '111',
    ])->assertSee('登録中です。');
    expect(DB::table('tb_photo_album')->count())->toBe(1);

    // La pestaña マイアルバム muestra la foto y enlaza a sus pantallas
    $this->get(route('admin.clientes.show', [$customer->cd, 'tab' => 7, 'photoalbum_cd' => $row->cd]))
        ->assertOk()
        ->assertSee("/hp/uploads/c1/photoalbum/{$row->cd}/0_l.jpg", false)
        ->assertSee(route('admin.clientes.photos.create', $customer->cd), false)
        ->assertSee(route('admin.clientes.photos.edit', [$customer->cd, $row->cd]), false);
    $this->get("/hp/uploads/c1/photoalbum/{$row->cd}/0_ss.jpg")->assertOk();
});

it('modifica: carga la fila, conserva la foto si no se sube otra y la reemplaza si se sube', function () {
    $customer = Customer::factory()->create();
    $cd = clientesPhotoAlbum($customer->cd);

    $this->get(route('admin.clientes.photos.edit', [$customer->cd, $cd]))
        ->assertOk()->assertSee('■フォトアルバム 更新')->assertSee('value="2026/04/01"', false)
        ->assertSee('前回の写真')->assertSee("/hp/uploads/c1/photoalbum/{$cd}/0_m.jpg", false);

    $this->put(route('admin.clientes.photos.update', [$customer->cd, $cd]), [
        'kaiin_cd' => $customer->cd, 'hiduke' => '2026/04/02', 'comment' => '修正', 'pic_name' => [''], 'add_record_verify' => '1',
    ])->assertOk()->assertSee('■フォトアルバム 更新')->assertSee("photoalbum_cd={$cd}", false);
    $row = DB::table('tb_photo_album')->where('cd', $cd)->first();
    expect($row->pic_flg)->toBe('1')->and($row->comment)->toBe('修正')
        ->and((int) $row->disp_date)->toBe(Carbon::create(2026, 4, 2, 0, 0, 0, 'Asia/Tokyo')->getTimestamp());

    $name = clientesPhotoUpload($this, 'nueva', 100, 50);
    $this->put(route('admin.clientes.photos.update', [$customer->cd, $cd]), [
        'kaiin_cd' => $customer->cd, 'hiduke' => '2026/04/02', 'pic_name' => [$name], 'add_record_verify' => '2',
    ])->assertOk();
    // '150x150' amplía para caber en la caja
    expect(array_slice(getimagesizefromstring(Storage::disk('public')->get("hp/uploads/c1/photoalbum/{$cd}/0_s.jpg")), 0, 2))->toBe([150, 75]);

    // Una entrada de otro cliente no se abre
    $other = Customer::factory()->create();
    $this->get(route('admin.clientes.photos.edit', [$other->cd, $cd]))->assertNotFound();
});

it('borra: disp_flg = 9 y pantalla 「削除中です。」', function () {
    $customer = Customer::factory()->create();
    $cd = clientesPhotoAlbum($customer->cd);

    $this->delete(route('admin.clientes.photos.destroy', [$customer->cd, $cd]), ['kaiin_cd' => $customer->cd, 'tab' => '7'])
        ->assertOk()->assertSee('■顧客登録情報')->assertSee('削除中です。')->assertSee('content="2; URL=', false);

    expect(DB::table('tb_photo_album')->where('cd', $cd)->value('disp_flg'))->toBe('9');
});
