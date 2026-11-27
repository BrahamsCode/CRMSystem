<?php

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

/*
 * M9: 新着情報 (category3/digital_signage: digital_signage_entry / _register, inc/upload, inc/disp_pic).
 */
beforeEach(function () {
    if (! View::exists('layouts.legacy')) {
        $dir = storage_path('framework/testing/promocion-views/layouts');
        File::ensureDirectoryExists($dir);
        $stub = "<html><head><title>@yield('title')</title>@yield('css')@yield('js')</head><body>@yield('content')</body></html>";
        File::put("$dir/legacy.blade.php", $stub);
        File::put("$dir/legacy-popup.blade.php", $stub);
        View::addLocation(dirname($dir));
    }
    $this->withoutMiddleware(Authenticate::class);
    $this->travelTo(Carbon::parse('2026-11-27 10:00:00', 'Asia/Tokyo'));
    Storage::fake('public');

    $rows = [];
    for ($i = 1; $i <= 12; $i++) {
        $rows[] = ['cd' => $i, 'title' => "お知らせ{$i}", 'play_time' => 10, 'rank' => $i, 'update' => (string) (1700000000 + $i), 'disp_flg' => '1'];
    }
    DB::table('tb_digital_signage')->insert($rows);
});

it('lista los 10 últimos por update y solo muestra el formulario con ?cd=', function () {
    Storage::disk('public')->put('hp/uploads/c3/digital_signage/12/12.swf', 'x');

    $this->get('/admin/promocion/digital_signage/digital_signage_entry')
        ->assertOk()
        ->assertSee('■新着情報一覧')
        ->assertSee('最新から10件の該当結果を表示しています。')
        ->assertSeeInOrder(['お知らせ12', 'お知らせ11', 'お知らせ3'])
        ->assertDontSee('お知らせ2<')
        ->assertDontSee('name="form1"', false)
        ->assertSee('hp/uploads/c3/digital_signage/12/12.swf', false)
        ->assertDontSee('hp/uploads/c3/digital_signage/11/11.swf', false);

    $this->get('/admin/promocion/digital_signage/digital_signage_entry?cd=5')
        ->assertOk()
        ->assertSee('■新着情報管理')
        ->assertSee('value="お知らせ5"', false)
        ->assertSee('この内容で修正する');

    // URL legacy
    $this->get('/system/category3/digital_signage/digital_signage_entry.php?cd=5')
        ->assertRedirect(route('admin.promocion.news.index', ['cd' => 5]));
});

it('sube el archivo, lo publica al guardar y cambia título, update y disp_flg', function () {
    $this->post('/admin/promocion/digital_signage/inc/upload.php', [
        'userfile' => UploadedFile::fake()->create('banner.swf', 4), 'ext' => 'swf', 'time' => '1764205200000',
    ])->assertOk()->assertSeeText('success');
    Storage::disk('public')->assertExists('system/category3/digital_signage/uploads/temp/1764205200000.swf');

    $this->get('/admin/promocion/digital_signage/inc/disp_pic.php?ext=swf&t=1764205200000')->assertSee('1764205200000.swf');
    $this->post('/admin/promocion/digital_signage/inc/upload.php', [
        'userfile' => UploadedFile::fake()->create('a.exe', 1), 'ext' => 'exe', 'time' => '1',
    ])->assertSeeText('error');

    DB::table('tb_digital_signage')->where('cd', 3)->update(['disp_flg' => null]);
    $this->post('/admin/promocion/digital_signage/digital_signage_register', [
        'act' => 'up', 'cd' => 3, 'title' => ' 新しいタイトル ', 'pic_name' => '1764205200000',
    ])->assertOk()->assertSee('登録中です。')->assertSee('URL='.route('admin.promocion.news.index'), false);

    $row = DB::table('tb_digital_signage')->where('cd', 3)->first();
    expect($row->title)->toBe('新しいタイトル')
        ->and($row->update)->toBe((string) now()->getTimestamp())
        ->and($row->disp_flg)->toBe('1');
    Storage::disk('public')->assertExists('hp/uploads/c3/digital_signage/3/3.swf');
    Storage::disk('public')->assertMissing('system/category3/digital_signage/uploads/temp/1764205200000.swf');

    // Sin cd existente no crea nada (el alta del legacy estaba comentada)
    $this->post('/admin/promocion/digital_signage/digital_signage_register', ['act' => 'new', 'title' => 'x'])->assertOk();
    expect(DB::table('tb_digital_signage')->count())->toBe(12);
});

it('borra la noticia (disp_flg = 9) y deja de listarla', function () {
    $this->post('/admin/promocion/digital_signage/digital_signage_delete', ['cd' => 12])
        ->assertRedirect(route('admin.promocion.news.index'));

    expect(DB::table('tb_digital_signage')->where('cd', 12)->value('disp_flg'))->toBe('9');
    $this->get('/admin/promocion/digital_signage/digital_signage_entry')
        ->assertDontSee('お知らせ12')
        ->assertSee('お知らせ2<', false);
});
