<?php

require_once __DIR__ . '/../Reservations/helpers.php';

use App\Models\ContentsMaster;
use App\Models\Customers\PointSetting;
use App\Services\Customers\PointSettingImageStore;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

// ポイント管理 (category1/customer/point_setting*.php) y su ajax_apliFileUpload.php

beforeEach(function () {
    reservasLogin();
    Storage::fake('public');
    // isExtShopDomain(): el menú y las rutas solo existen con mycolle/app_layout_mode = '1'
    ContentsMaster::create(['category' => 'mycolle', 'field' => 'app_layout_mode', 'text_data' => '1', 'disp_flg' => '1', 'hyouji_flg' => '1']);
});

function clientesPointSettingConfig(): array
{
    $raw = ContentsMaster::query()->where('category', 'tb_c1_point_setting')->where('field', 'mypage_config')->value('text_data');

    return unserialize(str_replace("'", '"', (string) $raw));
}

it('carga ポイント管理 desde la URL del menú legacy con imágenes, formulario y lista vacía', function () {
    $this->get('/system/category1/customer/point_setting.php')
        ->assertOk()
        ->assertSee('ポイント管理管理 - ', false)
        ->assertSee('『ポイントについて』用画像')
        ->assertSee('【全店舗用】')
        ->assertSee('【本店】')
        ->assertSee('全店舗画像を利用する')
        ->assertSee('■ポイント管理 登録')
        ->assertSee('ポイント名')
        ->assertSee('ポイント数')
        ->assertSee('この内容で登録する')
        ->assertSee('■ポイント管理一覧')
        ->assertSee('ポイント管理は登録されていません。')
        ->assertSee(route('legacy.clientes.apli-file-upload'), false);
});

it('sin isExtShopDomain() responde 403', function () {
    ContentsMaster::query()->where('category', 'mycolle')->delete();

    $this->get('/system/category1/customer/point_setting.php')->assertForbidden();
    $this->get(route('admin.clientes.point-settings'))->assertForbidden();
    $this->post('/system/category1/customer/ajax_apliFileUpload.php', ['type' => 'delMain'])->assertForbidden();
});

it('da de alta con hatuban, rank consecutivo y form2Db; edita con ?cd= y borra lógicamente', function () {
    $this->post(route('admin.clientes.point-settings.store'), ['name' => ' 来店ﾎﾟｲﾝﾄ ', 'point' => '100', 'act' => 'new'])
        ->assertRedirect(route('admin.clientes.point-settings'));
    $this->post(route('admin.clientes.point-settings.store'), ['name' => '紹介', 'point' => '50']);

    $rows = PointSetting::query()->orderBy('rank')->get();
    expect($rows)->toHaveCount(2)
        ->and($rows[0]->name)->toBe('来店ポイント')
        ->and($rows[0]->point)->toBe(100)
        ->and($rows[0]->rank)->toBe(1)
        ->and($rows[1]->rank)->toBe(2)
        ->and($rows[0]->disp_flg)->toBe('1')
        ->and($rows[0]->touroku_date)->not->toBeEmpty()
        ->and((int) DB::table('hatuban')->where('koumoku', 'tb_point_setting')->value('kazu'))->toBeGreaterThan($rows[1]->cd);

    $this->get(route('admin.clientes.point-settings', ['cd' => $rows[1]->cd]))
        ->assertOk()
        ->assertSee('この内容で修正する')
        ->assertSee('value="紹介"', false)
        ->assertSee('value="50"', false);

    $this->put(route('admin.clientes.point-settings.update', $rows[1]->cd), ['name' => '紹介P', 'point' => '70'])
        ->assertRedirect(route('admin.clientes.point-settings'));
    expect($rows[1]->fresh()->point)->toBe(70)->and($rows[1]->fresh()->name)->toBe('紹介P');

    $this->delete(route('admin.clientes.point-settings.destroy', $rows[0]->cd))->assertRedirect();
    expect(PointSetting::withDeleted()->find($rows[0]->cd)->disp_flg)->toBe('9');

    $this->get(route('admin.clientes.point-settings'))
        ->assertOk()
        ->assertSee('1件')
        ->assertSee('紹介P')
        ->assertDontSee('来店ポイント');
});

it('no guarda una ポイント数 vacía o no numérica', function () {
    $this->post(route('admin.clientes.point-settings.store'), ['name' => 'X', 'point' => 'abc'])->assertRedirect();
    $this->post(route('admin.clientes.point-settings.store'), ['name' => '', 'point' => '1'])->assertRedirect();

    expect(PointSetting::query()->count())->toBe(0);
});

it('el POST a la URL legacy acepta act = new | up | del', function () {
    $this->post('/system/category1/customer/point_setting.php', ['act' => 'new', 'name' => 'A', 'point' => '10'])->assertRedirect();
    $row = PointSetting::query()->firstOrFail();

    $this->post('/system/category1/customer/point_setting.php', ['act' => 'up', 'cd' => $row->cd, 'name' => 'B', 'point' => '20']);
    expect($row->fresh()->name)->toBe('B');

    $this->post('/system/category1/customer/point_setting.php', ['act' => 'del', 'cd' => $row->cd]);
    expect(PointSetting::query()->count())->toBe(0);
});

it('▲▼ intercambia el rank con el vecino y renumera', function () {
    $a = PointSetting::create(['name' => 'A', 'point' => 1, 'rank' => 1, 'disp_flg' => '1']);
    $b = PointSetting::create(['name' => 'B', 'point' => 2, 'rank' => 3, 'disp_flg' => '1']);
    $c = PointSetting::create(['name' => 'C', 'point' => 3, 'rank' => 5, 'disp_flg' => '1']);

    $this->post(route('admin.clientes.point-settings.sort', ['cd' => $c->cd, 'kind' => 'up']))->assertRedirect();
    expect([$a->fresh()->rank, $b->fresh()->rank, $c->fresh()->rank])->toBe([1, 3, 2]);

    $this->post(route('admin.clientes.point-settings.sort', ['cd' => $a->cd, 'kind' => 'down']));
    expect([$a->fresh()->rank, $b->fresh()->rank, $c->fresh()->rank])->toBe([2, 3, 1]);
});

it('sube, guarda (con miniatura _s y mypage_config) y borra las imágenes 『ポイントについて』', function () {
    // Subida temporal: recorte 800×600
    $json = $this->post('/system/category1/customer/ajax_apliFileUpload.php', [
        'file' => UploadedFile::fake()->image('foto.jpg', 40, 20),
        'type' => 'picMainUpload',
    ])->assertOk()->assertJson(['status' => 'ok'])->json();
    $temp = PointSettingImageStore::TEMP_DIR . '/' . $json['imageName'];
    Storage::disk('public')->assertExists($temp);
    expect(getimagesizefromstring(Storage::disk('public')->get($temp))[0])->toBe(800);

    $this->get('/system/category1/customer/uploads/temp/' . $json['imageName'])->assertOk();

    // Extensión no permitida
    $this->post('/system/category1/customer/ajax_apliFileUpload.php', [
        'file' => UploadedFile::fake()->create('a.txt', 1), 'type' => 'picMainUpload',
    ])->assertJson(['status' => 'ng', 'errMsg' => '許可されているファイル拡張子ではありません。']);

    // アップロード: pic[1] = temporal (tienda 1), casilla 全店舗画像を利用する de la tienda 1
    $this->post('/system/category1/customer/ajax_apliFileUpload.php', [
        'pic' => [1 => $json['imageName']],
        'mypage_config' => ['home_image_flg' => [-1 => '1', 1 => '1']],
        'type' => 'sendMainData',
        'dir' => 'point_setting',
    ])->assertJson(['status' => 'ok', 'result' => '1枚目アップロード成功。']);

    Storage::disk('public')->assertExists('hp/uploads/c1/point_setting/1.jpg');
    Storage::disk('public')->assertExists('hp/uploads/c1/point_setting/1_s.jpg');
    expect(clientesPointSettingConfig()['home_image_flg'])->toBe([-1 => '1', 1 => '1'])
        ->and(ContentsMaster::query()->where('category', 'tb_c1_point_setting')->where('field', 'point_setting')->count())->toBe(1);

    $this->get('/system/category1/customer/point_setting.php')
        ->assertOk()
        ->assertSee('hp/uploads/c1/point_setting/1.jpg?t=', false)
        ->assertSee('画像を削除する')
        ->assertSee('id="mypage_config_home_image_flg_1" value="1" checked', false);

    // Nombre temporal inválido: no sale de la carpeta
    $this->post('/system/category1/customer/ajax_apliFileUpload.php', [
        'pic' => [0 => '../../.env'], 'type' => 'sendMainData', 'dir' => 'point_setting',
    ])->assertJson(['result' => '1枚目アップロード失敗。']);

    // dir fuera de las carpetas de la pantalla
    $this->post('/system/category1/customer/ajax_apliFileUpload.php', ['type' => 'delMain', 'dir' => '../../x', 'num' => 1])
        ->assertJson(['status' => 'ng']);

    // 画像を削除する
    $this->post('/system/category1/customer/ajax_apliFileUpload.php', ['type' => 'delMain', 'dir' => 'point_setting', 'num' => 1])
        ->assertJson(['status' => 'ok', 'res' => '削除しました。']);
    Storage::disk('public')->assertMissing('hp/uploads/c1/point_setting/1.jpg');
    Storage::disk('public')->assertMissing('hp/uploads/c1/point_setting/1_s.jpg');
});
