<?php

require_once __DIR__ . '/../Reservations/helpers.php';

use App\Models\Customers\Customer;
use App\Services\Customers\CustomerPhotoImageStore;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

// C5: limpieza programada de temporales de category1/index.php, CSVインポート (csv_import.php),
// su plantilla (csv_download_template.php) y CSVエクスポート de maestras (csv_export.php)

beforeEach(function () {
    reservasLogin();
    Carbon::setTestNow(Carbon::parse('2026-05-20 12:00', 'Asia/Tokyo'));
});

afterEach(fn () => Carbon::setTestNow());

/** CSV de importación en SJIS-win con la cabecera de la plantilla (4 filas) y las filas de datos */
function clientesImportCsv(array $rows, ?string $header = null, ?string $types = null): UploadedFile
{
    $header ??= 'kanri_id,name,name_kana,kojin_birth,kojin_sex,kojin_bloodtype,tel,kojin_mobile,email1,email2,zip,pref,add,add_number,add_build,mailmaga_flg,staff_id';
    $types ??= implode(',', array_fill(0, count(explode(',', $header)), '文字列'));
    $csv = $header."\r\n".'títulos'."\r\n".$types."\r\n".'↑は編集しないでください。'."\r\n".implode("\r\n", $rows)."\r\n";

    return UploadedFile::fake()->createWithContent('customer.csv', mb_convert_encoding($csv, 'SJIS-win', 'UTF-8'));
}

// ---------------------------------------------------------------- limpieza de temporales (la portada es del núcleo)

it('programa la limpieza de temporales de subida y borra solo los de más de un día', function () {
    Storage::fake('public');
    Storage::disk('public')->put(CustomerPhotoImageStore::TEMP_DIR.'/viejo.jpg', 'x');
    Storage::disk('public')->put(CustomerPhotoImageStore::TEMP_DIR.'/nuevo.jpg', 'x');
    touch(Storage::disk('public')->path(CustomerPhotoImageStore::TEMP_DIR.'/viejo.jpg'), now()->subDays(2)->getTimestamp());

    $event = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
        ->first(fn ($e) => $e->description === 'clientes:limpiar-temporales');
    expect($event)->not->toBeNull();
    $event->run(app());

    Storage::disk('public')->assertMissing(CustomerPhotoImageStore::TEMP_DIR.'/viejo.jpg');
    Storage::disk('public')->assertExists(CustomerPhotoImageStore::TEMP_DIR.'/nuevo.jpg');
});

// ---------------------------------------------------------------- CSVインポート

it('CSVインポート: solo para mantenimiento (desarrollo o support_login)', function () {
    config(['crm.stage' => '']);
    $this->get(route('admin.clientes.import'))->assertForbidden();
    $this->post(route('admin.clientes.import.store'))->assertForbidden();
    $this->get('/system/category1/customer/csv_download_template.php')->assertForbidden();

    session(['nucleo.support_login' => true]);
    $this->get(route('admin.clientes.import'))->assertOk()
        ->assertSee('■CSVインポート')
        ->assertSee('value="テンプレートファイルをダウンロード"', false)
        ->assertSee('value="インポートを実行する"', false)
        ->assertSee('name="csvFile"', false);
    $this->get('/system/category1/customer/csv_import.php')->assertOk()->assertSee('■CSVインポート');
});

it('descarga la plantilla en SJIS como csv_download_template.php', function () {
    session(['nucleo.support_login' => true]);

    $response = $this->post('/system/category1/customer/csv_download_template.php');
    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('inputCustomerData.csv');

    $csv = mb_convert_encoding($response->streamedContent(), 'UTF-8', 'SJIS-win');
    $lines = explode("\r\n", $csv);
    expect($lines[0])->toStartWith('kanri_id,name,name_kana,kojin_birth')->toEndWith(',mailmaga_flg,staff_id')
        ->and($lines[1])->toContain('生年月日（○○/○○/○○形式で入力。例：1970/11/19　年がない場合：/10/13）')
        ->and($lines[2])->toBe(implode(',', array_fill(0, 17, '文字列')))
        ->and($lines[3])->toBe('↑は編集しないでください。追加する商品データは↓から記述してください。');

    $this->get(route('admin.clientes.export', ['format' => 'template']))->assertOk();
});

it('importa altas con las conversiones del legacy, id/login_id/login_pass y los errores por fila', function () {
    session(['nucleo.support_login' => true]);
    DB::table('tb_staff')->where('cd', 10)->update(['id' => '77']);
    $before = DB::table('tb_customer')->count();

    $file = clientesImportCsv([
        'K-1,山本 太郎,ﾔﾏﾓﾄ ﾀﾛｳ,１９８０/2/3,男,ab,０９０１２３４５６７８,,ｔａｒｏ@example.com,mal correo,1500001,東京都,渋谷区,1-2-3,ビル,１,77',
        ',,ナシ,,,,,,,,,,,,,,',
        'K-3,花子,ハナコ,/10/13,女性,X,03-1234,,,,,,,,,2,999',
    ]);

    $this->post(route('admin.clientes.import.store'), ['csvFile' => $file, 'act' => 'import'])
        ->assertRedirect(route('admin.clientes.import'));
    $this->get(route('admin.clientes.import'))
        ->assertSee('2件 追加しました。')
        ->assertSee('ERROR... 2番目：名前を入力してください。');

    expect(DB::table('tb_customer')->count())->toBe($before + 2);

    $taro = DB::table('tb_customer')->where('kanri_id', 'K-1')->first();
    expect($taro->name)->toBe('山本 太郎')
        ->and($taro->name_kana)->toBe('ヤマモト タロウ')
        ->and($taro->kojin_birth)->toBe('1980/02/03')
        ->and((int) $taro->kojin_birth_date)->toBe(Carbon::create(1980, 2, 3, 0, 0, 0, 'Asia/Tokyo')->getTimestamp())
        ->and($taro->kojin_sex)->toBe('男性')
        ->and($taro->kojin_bloodtype)->toBe('AB')
        ->and($taro->tel)->toBe('09012345678')
        ->and($taro->email1)->toBe('taro@example.com')
        ->and($taro->email2)->toBe('')
        ->and($taro->pref)->toBe('東京都')
        ->and($taro->mailmaga_flg)->toBe('1')
        ->and((int) $taro->tb_staff_cd)->toBe(10)
        ->and((int) $taro->tb_shop_cd)->toBe(1)
        ->and($taro->id)->toStartWith('11')
        ->and($taro->login_id)->toBe($taro->id)
        ->and($taro->login_pass)->toMatch('/^\d{4}$/')
        ->and($taro->disp_flg)->toBe('1')
        ->and($taro->touroku_kubun)->toBe('2')
        ->and($taro->touroku_date)->toBe((string) now()->getTimestamp());

    $hanako = DB::table('tb_customer')->where('kanri_id', 'K-3')->first();
    expect($hanako->kojin_birth)->toBe('/10/13')
        ->and($hanako->kojin_birth_date)->toBeNull()
        ->and($hanako->kojin_sex)->toBe('女性')
        ->and($hanako->kojin_bloodtype)->toBe('')
        ->and($hanako->tel)->toBe('')
        ->and($hanako->mailmaga_flg)->toBe('')
        ->and($hanako->tb_staff_cd)->toBeNull()
        ->and($hanako->id)->not->toBe($taro->id);
});

it('con cd en la 1.ª columna actualiza el cliente de la tienda', function () {
    session(['nucleo.support_login' => true]);
    $customer = Customer::factory()->create(['tb_shop_cd' => 1, 'name' => '旧名', 'tel' => '000']);
    $other = Customer::factory()->create(['tb_shop_cd' => 2, 'name' => '他店']);

    $file = clientesImportCsv(
        ["{$customer->cd},新名,0311112222", "{$other->cd},乗っ取り,1"],
        'cd,name,tel',
        '数字,文字列,文字列',
    );
    $this->post(route('admin.clientes.import.store'), ['csvFile' => $file]);

    $this->get(route('admin.clientes.import'))->assertSee('1件 更新しました。')->assertDontSee('追加しました');
    expect(DB::table('tb_customer')->where('cd', $customer->cd)->value('name'))->toBe('新名')
        ->and(DB::table('tb_customer')->where('cd', $customer->cd)->value('tel'))->toBe('0311112222')
        ->and(DB::table('tb_customer')->where('cd', $other->cd)->value('name'))->toBe('他店');
});

it('sin archivo o con un formato que no sirve da los mensajes del legacy', function () {
    session(['nucleo.support_login' => true]);
    $before = DB::table('tb_customer')->count();

    $this->post(route('admin.clientes.import.store'), ['act' => 'import']);
    $this->get(route('admin.clientes.import'))->assertSee('ファイルを選択して下さい。');

    // Columna que no existe: la fila falla como el SQL del legacy
    $this->post(route('admin.clientes.import.store'), ['csvFile' => clientesImportCsv(['太郎,x'], 'name,no_existe')]);
    $this->get(route('admin.clientes.import'))->assertSee('アップロードされたファイルは不適切な形式です。');

    expect(DB::table('tb_customer')->count())->toBe($before);
});

// ---------------------------------------------------------------- CSVエクスポート

it('CSVエクスポート: vuelca la tabla elegida en SJIS con el escape del legacy', function () {
    session(['nucleo.support_login' => true]);
    DB::table('tb_customer_group')->insert(['cd' => 5, 'name' => "グループ\"A\"\r\n改行"]);

    $this->get('/system/category1/customer/csv_export.php')->assertOk()
        ->assertSee('■CSVエクスポート')->assertSee('顧客グループマスタ')->assertSee('スタッフグループマスタ');

    $response = $this->post(route('admin.clientes.table-export.download'), ['act' => 'export', 'table' => 'tb_customer_group']);
    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('tb_customer_group_20260520120000.csv');
    $csv = mb_convert_encoding($response->streamedContent(), 'UTF-8', 'SJIS-win');
    expect($csv)->toStartWith('"cd",')->toContain('"5"')->toContain('"グループ\\"A\\"\\r\\n改行"');

    $this->post(route('admin.clientes.table-export.download'), ['act' => 'export', 'table' => 'tb_staff_group'])
        ->assertRedirect(route('admin.clientes.table-export'));
    $this->get(route('admin.clientes.table-export'))->assertSee('データがありません。');

    $this->post(route('admin.clientes.table-export.download'), ['act' => 'export', 'table' => 'tb_office']);
    $this->get(route('admin.clientes.table-export'))->assertSee('不正アクセス！！！');

    config(['crm.stage' => '']);
    session()->forget('nucleo.support_login');
    $this->get(route('admin.clientes.table-export'))->assertForbidden();
});
