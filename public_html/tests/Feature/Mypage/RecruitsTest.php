<?php

use App\Models\Mypage\Recruit;
use App\Models\Mypage\RecruitCategory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

require_once __DIR__ . '/helpers.php';

/* 求人情報 (category6/recruit/recruit_*.php) y 求人職種管理 (recruit_category*.php) */

const FLOORMAP = 'hp/uploads/c7/display/floormap.xml';

beforeEach(function () {
    mypageLogin();
});

function recruitForm(array $overrides = []): array
{
    return $overrides + [
        'boshu_headline' => 'ホールスタッフ募集', 'tb_shop_cd' => '1', 'boshu_shokushu' => '', 'shigoto_naiyou' => "接客\n配膳",
        'tantou' => '山田', 'tel' => '06-0000-0000', 'pic_name1' => '', 'pic_name2' => '', 'act' => 'up',
    ];
}

it('con una sola tienda muestra su nombre y lista sus 職種 (bug del desplegable vacío)', function () {
    RecruitCategory::create(['cd' => 40, 'tb_shop_cd' => 1, 'shokushu' => 'ホール', 'disp_flg' => '1']);
    RecruitCategory::create(['cd' => 41, 'tb_shop_cd' => null, 'shokushu' => 'キッチン', 'disp_flg' => '1']);
    RecruitCategory::create(['cd' => 42, 'tb_shop_cd' => 2, 'shokushu' => '他店', 'disp_flg' => '1']);

    $this->get(route('admin.mypage.recruits.create'))
        ->assertOk()
        ->assertSee('<title>求人情報 新規登録 - shop</title>', false)
        ->assertSee('<input type="hidden" name="tb_shop_cd" value="1">', false)
        ->assertSee('<option value="40" >ホール</option>', false)
        ->assertSee('<option value="41" >キッチン</option>', false)
        ->assertDontSee('他店')
        ->assertDontSee('募集職種が登録されていません');
});

it('avisa si no hay 職種 y valida los obligatorios como el legacy', function () {
    $this->get(route('admin.mypage.recruits.create'))->assertSee('募集職種が登録されていません');

    $this->from(route('admin.mypage.recruits.create'))
        ->post(route('admin.mypage.recruits.store'), recruitForm(['boshu_headline' => '']))
        ->assertRedirect(route('admin.mypage.recruits.create'));

    $this->get(route('admin.mypage.recruits.create'))
        ->assertSee('<td class="Ng">', false)
        ->assertSee('<p class="m0">※必須項目です。</p>', false)
        ->assertSee('<p class="m0">※職種を選択して下さい。</p>', false);
    expect(Recruit::query()->count())->toBe(0);
});

it('da de alta un 求人 con imagen, escribe el floormap y muestra 完了', function () {
    RecruitCategory::create(['cd' => 40, 'tb_shop_cd' => 1, 'shokushu' => 'ホール', 'disp_flg' => '1']);
    $this->post('/system/category6/recruit/inc/upload.php', [
        'userfile' => UploadedFile::fake()->image('a.png', 600, 300), 'ext' => 'png', 'time' => '1700000000001',
    ])->assertSeeText('success');

    $response = $this->post(route('admin.mypage.recruits.store'), recruitForm(['boshu_shokushu' => '40', 'pic_name1' => '1700000000001']));

    $recruit = Recruit::query()->sole();
    $response->assertRedirect(route('admin.mypage.recruits.complete', ['cd' => $recruit->cd, 'register_type' => 'new', 'act' => 'up']));

    $disk = Storage::disk('public');
    $image = getimagesizefromstring($disk->get("hp/uploads/c6/recruit/{$recruit->cd}/1.jpg"));
    $thumb = getimagesizefromstring($disk->get("hp/uploads/c6/recruit/{$recruit->cd}/1_t.jpg"));
    expect($recruit->only(['boshu_shokushu', 'tb_shop_cd', 'pic_flg1', 'disp_flg']))->toBe(['boshu_shokushu' => 40, 'tb_shop_cd' => 1, 'pic_flg1' => '1', 'disp_flg' => '1'])
        ->and([$image[0], $image[1]])->toBe([240, 120])
        ->and([$thumb[0], $thumb[1]])->toBe([100, 50])
        ->and($disk->exists(FLOORMAP))->toBeTrue();

    $this->get(route('admin.mypage.recruits.complete', ['cd' => $recruit->cd, 'register_type' => 'new', 'act' => 'up']))
        ->assertOk()
        ->assertSee('■求人情報 修正')
        ->assertSee('続けて新規登録する')
        ->assertSee("接客<br />\n配膳", false)
        ->assertSee('ホール');
});

it('lista, muestra, modifica y borra un 求人 reescribiendo el floormap', function () {
    RecruitCategory::create(['cd' => 40, 'tb_shop_cd' => null, 'shokushu' => 'ホール', 'disp_flg' => '1']);
    $recruit = Recruit::create(['cd' => 70, 'boshu_headline' => '旧見出し', 'tb_shop_cd' => 1, 'boshu_shokushu' => 40, 'tantou' => '佐藤', 'disp_flg' => '1']);

    $this->get(route('admin.mypage.recruits.index'))
        ->assertOk()
        ->assertSee('<span class="attention">1件</span>', false)
        ->assertSee('旧見出し');

    $this->get(route('admin.mypage.recruits.show', ['cd' => 70]))
        ->assertOk()
        ->assertSee('■求人情報 詳細')
        ->assertSee('本店')
        ->assertSee('修正する')
        ->assertSee('削除する');

    $this->post(route('admin.mypage.recruits.edit', ['cd' => 70]), ['cd' => 70])
        ->assertOk()
        ->assertSee('■求人情報 修正')
        ->assertSee('value="旧見出し"', false)
        ->assertSee('<option value="40"  selected="selected">ホール</option>', false);

    $this->post(route('admin.mypage.recruits.update', ['cd' => 70]), recruitForm(['boshu_headline' => '新見出し', 'boshu_shokushu' => '40']))
        ->assertRedirect(route('admin.mypage.recruits.complete', ['cd' => 70, 'register_type' => 'update', 'act' => 'up']));
    expect($recruit->fresh()->boshu_headline)->toBe('新見出し')
        ->and(Storage::disk('public')->exists(FLOORMAP))->toBeTrue();

    Storage::disk('public')->delete(FLOORMAP);
    $this->post(route('admin.mypage.recruits.destroy'), ['delete_flg' => 'del', 'cd' => 70])
        ->assertSee('削除中です。');
    expect(Recruit::query()->withDeleted()->find(70)->disp_flg)->toBe('9')
        ->and(Storage::disk('public')->exists(FLOORMAP))->toBeTrue();
});

it('devuelve los 職種 de la tienda por AJAX (ajax_category.php)', function () {
    RecruitCategory::create(['cd' => 40, 'tb_shop_cd' => 1, 'shokushu' => 'ホール', 'disp_flg' => '1']);
    RecruitCategory::create(['cd' => 41, 'tb_shop_cd' => 2, 'shokushu' => '他店', 'disp_flg' => '1']);

    $this->post('/system/category6/recruit/ajax_category.php', ['shop_cd' => '1'])
        ->assertOk()
        ->assertSee('<option value="">▼職種選択</option>', false)
        ->assertSee('<option value="40">ホール</option>', false)
        ->assertDontSee('他店');
});

it('gestiona los 職種: alta 全店舗, modificación, nombre de tienda y borrado solo sin 求人', function () {
    DB::table('tb_shop')->insert(['cd' => 2, 'master_cd' => 1, 'kaishamei' => '二号店', 'disp_flg' => '1']);

    $this->get(route('admin.mypage.recruit-categories.index'))
        ->assertOk()
        ->assertSee('<title>職種管理 - shop</title>', false)
        ->assertSee('職種は登録されていません。');

    $this->post(route('admin.mypage.recruit-categories.store'), ['act' => 'new', 'shop_cd' => '-1', 'cate_name' => 'ホール'])
        ->assertSee('登録中です。');
    $this->post(route('admin.mypage.recruit-categories.store'), ['act' => 'new', 'shop_cd' => '2', 'cate_name' => 'キッチン']);

    $hall = RecruitCategory::query()->where('shokushu', 'ホール')->sole();
    $kitchen = RecruitCategory::query()->where('shokushu', 'キッチン')->sole();
    expect($hall->tb_shop_cd)->toBeNull()->and($kitchen->tb_shop_cd)->toBe(2);

    $this->post(route('admin.mypage.recruit-categories.store'), ['act' => 'up', 'cd' => $hall->cd, 'shop_cd' => '1', 'cate_name' => 'ホール係']);
    expect($hall->fresh()->only(['shokushu', 'tb_shop_cd']))->toBe(['shokushu' => 'ホール係', 'tb_shop_cd' => 1]);

    Recruit::create(['cd' => 80, 'boshu_headline' => 'x', 'tb_shop_cd' => 2, 'boshu_shokushu' => $kitchen->cd, 'disp_flg' => '1']);

    $html = $this->get(route('admin.mypage.recruit-categories.index'))->assertOk()->getContent();
    expect($html)->toContain('二号店')->toContain('本店')
        ->and(substr_count($html, 'value="削除する"'))->toBe(1);

    $this->post(route('admin.mypage.recruit-categories.destroy'), ['delete_flg' => 'del', 'cd' => $hall->cd])
        ->assertSee('削除中です。');
    expect($hall->fresh()->disp_flg)->toBe('9');
});

it('guarda el texto del 求人 y del 職種 como get_form (LegacyText::form)', function () {
    RecruitCategory::create(['cd' => 40, 'tb_shop_cd' => 1, 'shokushu' => 'ホール', 'disp_flg' => '1']);
    $this->post(route('admin.mypage.recruits.store'), recruitForm(['boshu_headline' => ' "急募" O\'Neil ', 'boshu_shokushu' => '40']));
    $this->post(route('admin.mypage.recruit-categories.store'), ['act' => 'new', 'shop_cd' => '1', 'cate_name' => 'A "B"']);

    expect(Recruit::query()->sole()->boshu_headline)->toBe('急募 O’Neil')
        ->and(RecruitCategory::query()->where('cd', '<>', 40)->sole()->shokushu)->toBe('A B');
});
