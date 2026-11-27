<?php

use App\Models\Senalizacion\SignageWebFile;
use App\Models\Senalizacion\SignageWebMovieContent;
use App\Models\Senalizacion\SignageWebMovieContentCategory;
use App\Models\Staff;
use Illuminate\Support\Facades\Gate;

require_once __DIR__.'/helpers.php';

beforeEach(function () {
    snSignageDatabase();
    s4Login();
});

it('pinta la ホーム con un iframe por terminal activo', function () {
    $f = snSignageFixture();

    $this->get(route('admin.senalizacion.index'))
        ->assertOk()
        ->assertSee('<title>管理画面</title>', false)
        ->assertSee('/signage/css/base.css', false)
        ->assertSee('サイネージ管理画面')
        ->assertSee('現在表示中のサイネージ')
        ->assertSee('端末番号 : '.$f['terminal']->cd)
        ->assertSee(url('/signage/view').'?cd='.$f['terminal']->cd, false)
        ->assertSee('width="160" height="90"', false);
});

it('pinta 端末一覧 con la lista, el formulario de cambio y la vista previa del paquete', function () {
    $f = snSignageFixture();

    $this->get(route('admin.senalizacion.terminals.index'))
        ->assertOk()
        ->assertSee('端末一覧')
        ->assertSee('入口')
        ->assertSee('ロビー')
        ->assertSee('action="'.route('admin.senalizacion.terminals.store').'"', false)
        ->assertSee("signagePost('".route('admin.senalizacion.terminals.destroy', $f['terminal']->cd)."')", false)
        ->assertSee(route('admin.senalizacion.packages.preview').'?cd='.$f['package']->cd, false);

    $this->get(route('admin.senalizacion.terminals.index', ['cd' => $f['terminal']->cd]))
        ->assertOk()
        ->assertSee('action="'.route('admin.senalizacion.terminals.update', $f['terminal']->cd).'"', false)
        ->assertSee('value="入口"', false)
        ->assertSee('value="h_1_1"  checked="checked"', false);
});

it('pinta パッケージ一覧 con el botón 詳細 hacia su primer ciclo', function () {
    $f = snSignageFixture(cycles: 2);

    $this->get(route('admin.senalizacion.packages.index'))
        ->assertOk()
        ->assertSee('パッケージ一覧')
        ->assertSee('ロビー')
        ->assertSee(route('admin.senalizacion.cycles.edit', ['packagesCd' => $f['package']->cd, 'cd' => $f['cycles'][0]->cd]), false);
});

it('pinta los ciclos con el diseño del marco (inc, inc_flame_cd, inc_js) y las imágenes', function () {
    $f = snSignageFixture();
    $cycle = $f['cycles'][0];

    $this->get(route('admin.senalizacion.cycles.edit', [$f['package']->cd, 'cd' => $cycle->cd]))
        ->assertOk()
        ->assertSee('サイクル一覧')
        ->assertSee('常に表示')
        ->assertSee('選択中')
        ->assertSee('name="flame_cd"', false)
        ->assertSee('function flameCdReload()', false)
        ->assertSee('name="action_ary[0][action]"', false)
        ->assertSee('<option value="3" selected>フェード</option>', false)
        ->assertSee('value="'.$f['content']->cd.'" selected>春のバナー</option>', false)
        ->assertSee('action="'.route('admin.senalizacion.cycles.update', [$f['package']->cd, $cycle->cd]).'"', false);
});

it('pinta 画像サイズ別一覧 con el nº de imágenes por talla', function () {
    $f = snSignageFixture();

    $this->get(route('admin.senalizacion.content-categories.index', 'image'))
        ->assertOk()
        ->assertSee('画像サイズ別一覧')
        ->assertSee('1920px × 1080px サイズ')
        ->assertSee('1 件')
        ->assertSee(route('admin.senalizacion.contents.index', ['image', $f['category']->cd]), false);

    $video = SignageWebMovieContentCategory::create(['width' => 1080, 'height' => 1920, 'flg' => 1]);
    SignageWebMovieContent::create(['contents_category_cd' => $video->cd, 'name' => 'CM', 'flg' => 1]);
    $this->get(route('admin.senalizacion.content-categories.index', 'video'))
        ->assertOk()
        ->assertSee('1080px × 1920px サイズ')
        ->assertSee('1 件');
});

it('pinta la lista de imágenes de una talla y la ventana de detalle', function () {
    $f = snSignageFixture();

    $this->get(route('admin.senalizacion.contents.index', ['image', $f['category']->cd]))
        ->assertOk()
        ->assertSee('フルHD 一覧')
        ->assertSee('新規追加')
        ->assertSee('春のバナー')
        ->assertSee(route('admin.senalizacion.contents.detail', ['image', $f['content']->cd]), false);

    $this->get(route('admin.senalizacion.contents.detail', ['image', $f['content']->cd]))
        ->assertOk()
        ->assertSee('フルHDサイズ編集')
        ->assertSee('value="春のバナー"', false)
        ->assertDontSee('id="menu"', false)
        ->assertSee(route('admin.senalizacion.contents.index', ['image', $f['category']->cd]), false);
});

it('pinta la lista de vídeos y la pantalla de archivos', function () {
    $category = SignageWebMovieContentCategory::create(['name' => '縦型', 'width' => 1080, 'height' => 1920, 'flg' => 1]);
    SignageWebMovieContent::create(['contents_category_cd' => $category->cd, 'name' => 'CM', 'movie_path' => '1.mp4', 'flg' => 1]);
    SignageWebFile::create(['color' => 'bg.png', 'flg' => 1]);

    $this->get(route('admin.senalizacion.contents.index', ['video', $category->cd]))
        ->assertOk()
        ->assertSee('縦型 一覧')
        ->assertSee('/signage/upload/movie/1080x1920/1.mp4', false);

    $this->get(route('admin.senalizacion.files.index'))
        ->assertOk()
        ->assertSee('ファイル名 : bg.png')
        ->assertSee('/signage/upload/bg.png', false);
});

it('escapa los nombres que el legacy imprimía tal cual', function () {
    $f = snSignageFixture();
    $f['terminal']->update(['name' => '<script>x</script>']);

    $this->get(route('admin.senalizacion.terminals.index'))
        ->assertOk()
        ->assertDontSee('<script>x</script>', false)
        ->assertSee('&lt;script&gt;x&lt;/script&gt;', false);
});

it('solo deja entrar al master (Gate senalizacion.manage)', function () {
    expect(Gate::forUser(Staff::where('login_id', 'master')->first())->allows('senalizacion.manage'))->toBeTrue();

    $staff = new Staff(['login_id' => 'tienda1']);
    expect(Gate::forUser($staff)->allows('senalizacion.manage'))->toBeFalse();
});
