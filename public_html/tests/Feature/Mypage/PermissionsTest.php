<?php

use App\Models\Mypage\Recommendation;
use App\Models\Mypage\Recruit;
use App\Models\Mypage\RecruitCategory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

require_once __DIR__ . '/helpers.php';

/*
 * Permisos de マイページ管理 (Doc/qa/permisos-mypage.md): オススメ 1016, 求人 1023, マイコレ 1035.
 * El legacy oculta los botones sin el permiso; Laravel además responde 403 en el servidor.
 */

beforeEach(function () {
    mypageLogin();
    Http::fake();
    DB::table('tb_mobile_navi')->insert(['cd' => 9, 'name' => 'ｵｽｽﾒ', 'link' => 'recommend.php', 'disp_flg' => '1']);
    RecruitCategory::create(['cd' => 40, 'tb_shop_cd' => null, 'shokushu' => 'ホール', 'disp_flg' => '1']);
    Recruit::create(['cd' => 70, 'boshu_headline' => '旧見出し', 'tb_shop_cd' => 1, 'boshu_shokushu' => 40, 'tantou' => '佐藤', 'disp_flg' => '1']);
    Recommendation::create(['cd' => 80, 'title' => '既存セール', 'disp_date' => '2026/01/05', 'naiyou' => '本日', 'hyouji_flg' => '', 'disp_flg' => '1']);
});

/**
 * Staff no master del grupo 900 con la matriz dada [contenido => '閲覧新規編集削除'] ('1000' = solo 閲覧),
 * como el escenario qa_partial de la QA. Cierra la sesión de master y entra con él.
 */
function mypageStaffWith(array $matrix): void
{
    DB::table('tb_staff_group')->insert(['cd' => 900, 'name' => 'QA部分権限', 'disp_flg' => '1']);
    DB::table('tb_kengen_contents')->insert([
        ['cd' => 1016, 'name' => 'オススメ', 'hyouji_flg' => '1'],
        ['cd' => 1023, 'name' => '求人', 'hyouji_flg' => '1'],
        ['cd' => 1035, 'name' => 'マイコレ', 'hyouji_flg' => '1'],
    ]);
    $cd = 1;
    foreach ($matrix as $contents => $flags) {
        DB::table('tb_kengen')->insert([
            'cd' => $cd++, 'tb_staff_group_cd' => 900, 'tb_kengen_contents_cd' => $contents,
            'etsuran_flg' => $flags[0], 'shinki_flg' => $flags[1], 'shusei_flg' => $flags[2], 'sakujo_flg' => $flags[3],
        ]);
    }
    DB::table('tb_staff')->insert([
        'cd' => 10, 'office_cd' => 1, 'shop_cd' => 1, 'login_id' => 'qa_partial', 'login_pass' => 'qa12345',
        'name' => 'QA', 'disp_flg' => '1', 'tb_staff_group_cd' => 900,
    ]);

    test()->get('/system/logout.php');
    loginAs('qa_partial', 'qa12345');
}

function qaRecommendForm(array $overrides = []): array
{
    return $overrides + [
        'act' => 'new', 'tb_shop_cd' => '', 'hyouji_flg' => '', 'news_year' => '2026', 'news_month' => '02',
        'news_day' => '01', 'title' => '新セール', 'link_type' => 'http://', 'link' => '', 'news_naiyou' => '内容',
        'pic_name' => '', 'pic_link' => '', 'push' => '0',
    ];
}

it('qa_partial (solo 1016-1 y 1023-1) ve las listas sin 登録 / 修正 / 削除', function () {
    mypageStaffWith(['1016' => '1000', '1023' => '1000', '1035' => '0000']);

    $this->get(route('admin.mypage.recommendations.index'))
        ->assertOk()
        ->assertSee('既存セール')
        ->assertSee('表示中')
        ->assertDontSee('この内容で登録する')
        ->assertDontSee('alt="修正"', false)
        ->assertDontSee('alt="削除"', false)
        ->assertDontSee('削除する')
        ->assertDontSee('表示情報を更新');

    $this->get(route('admin.mypage.recommendations.index', ['cd' => 80]))
        ->assertOk()
        ->assertDontSee('この内容で修正する');

    $this->get(route('admin.mypage.recruits.index'))->assertOk()->assertSee('旧見出し');
    $this->get(route('admin.mypage.recruits.show', ['cd' => 70]))
        ->assertOk()
        ->assertSee('■求人情報 詳細')
        ->assertDontSee('修正する')
        ->assertDontSee('削除する');
});

it('qa_partial recibe 403 en los POST de guardar y borrar, y no cambia nada', function () {
    mypageStaffWith(['1016' => '1000', '1023' => '1000', '1035' => '0000']);

    // オススメ: alta, modificación, 表示情報を更新, 削除 e imagen
    $this->post(route('admin.mypage.recommendations.store'), qaRecommendForm())->assertForbidden();
    $this->post(route('admin.mypage.recommendations.store'), qaRecommendForm(['act' => 'up', 'cd' => '80']))->assertForbidden();
    $this->post(route('admin.mypage.recommendations.index'), ['update_hyouji_flg' => 'update', 'update_cd' => [80]])->assertForbidden();
    $this->post(route('admin.mypage.recommendations.destroy'), ['delete_flg' => 'del', 'cd' => 80])->assertForbidden();
    $this->post('/system/category6/user_hp/inc/upload.php', [
        'userfile' => UploadedFile::fake()->image('a.png'), 'ext' => 'png', 'time' => '1700000000001',
    ])->assertForbidden();

    // 求人: 新規, 編集, 削除, 職種 (1023-4) y las llamadas AJAX del formulario
    $this->get(route('admin.mypage.recruits.create'))->assertForbidden();
    $this->post(route('admin.mypage.recruits.store'), ['boshu_headline' => 'x'])->assertForbidden();
    $this->post(route('admin.mypage.recruits.edit', ['cd' => 70]), ['cd' => 70])->assertForbidden();
    $this->post(route('admin.mypage.recruits.update', ['cd' => 70]), ['boshu_headline' => 'x'])->assertForbidden();
    $this->post(route('admin.mypage.recruits.destroy'), ['delete_flg' => 'del', 'cd' => 70])->assertForbidden();
    $this->get(route('admin.mypage.recruit-categories.index'))->assertForbidden();
    $this->post(route('admin.mypage.recruit-categories.store'), ['act' => 'new', 'shop_cd' => '1', 'cate_name' => 'x'])->assertForbidden();
    $this->post(route('admin.mypage.recruit-categories.destroy'), ['delete_flg' => 'del', 'cd' => 40])->assertForbidden();
    $this->post('/system/category6/recruit/ajax_category.php', ['shop_cd' => '1'])->assertForbidden();

    // マイコレ sin 1035-1
    $this->get(route('admin.mypage.app-images.edit', ['section' => 'all']))->assertForbidden();
    $this->post('/system/category6/user_hp/ajax_apliFileUpload.php', ['type' => 'x'])->assertForbidden();

    expect(Recommendation::query()->pluck('title')->all())->toBe(['既存セール'])
        ->and(Recommendation::query()->find(80)->hyouji_flg)->toBe('')
        ->and(Recruit::query()->find(70)->boshu_headline)->toBe('旧見出し')
        ->and(RecruitCategory::query()->find(40))->not->toBeNull();
});

it('sin 1016-1 ni 1023-1 no abre las pantallas', function () {
    mypageStaffWith(['1016' => '0000', '1023' => '0000', '1035' => '0000']);

    $this->get(route('admin.mypage.recommendations.index'))->assertForbidden();
    $this->get(route('admin.mypage.recruits.index'))->assertForbidden();
    $this->get(route('admin.mypage.recruits.show', ['cd' => 70]))->assertForbidden();
});

it('con 1016-2 sin 1016-3 da de alta pero no modifica', function () {
    mypageStaffWith(['1016' => '1100', '1023' => '1000', '1035' => '0000']);

    $this->get(route('admin.mypage.recommendations.index'))
        ->assertSee('この内容で登録する')
        ->assertDontSee('alt="修正"', false);
    $this->get(route('admin.mypage.recommendations.index', ['cd' => 80]))->assertDontSee('この内容で修正する');

    $this->post(route('admin.mypage.recommendations.store'), qaRecommendForm())->assertOk()->assertSee('登録中です。');
    $this->post(route('admin.mypage.recommendations.store'), qaRecommendForm(['act' => 'up', 'cd' => '80']))->assertForbidden();
    expect(Recommendation::query()->count())->toBe(2);
});

it('con el permiso completo ve los botones y guarda y borra', function () {
    mypageStaffWith(['1016' => '1111', '1023' => '1111', '1035' => '1111']);

    $this->get(route('admin.mypage.recommendations.index'))
        ->assertOk()
        ->assertSee('この内容で登録する')
        ->assertSee('alt="修正"', false)
        ->assertSee('alt="削除"', false)
        ->assertSee('表示情報を更新');
    $this->get(route('admin.mypage.recommendations.index', ['cd' => 80]))->assertSee('この内容で修正する');

    $this->post(route('admin.mypage.recommendations.store'), qaRecommendForm())->assertOk()->assertSee('登録中です。');
    $this->post(route('admin.mypage.recommendations.store'), qaRecommendForm(['act' => 'up', 'cd' => '80', 'title' => '修正後']))->assertOk();
    $this->post(route('admin.mypage.recommendations.index'), ['update_hyouji_flg' => 'update', 'update_cd' => [80]])->assertOk();
    expect(Recommendation::query()->find(80)->only(['title', 'hyouji_flg']))->toBe(['title' => '修正後', 'hyouji_flg' => '9']);
    $this->post(route('admin.mypage.recommendations.destroy'), ['delete_flg' => 'del', 'cd' => 80])->assertOk();
    expect(Recommendation::query()->find(80))->toBeNull();

    $this->get(route('admin.mypage.recruits.show', ['cd' => 70]))
        ->assertSee('修正する')
        ->assertSee('削除する');
    $this->get(route('admin.mypage.recruits.create'))->assertOk();
    $this->post(route('admin.mypage.recruits.edit', ['cd' => 70]), ['cd' => 70])->assertOk();
    $this->get(route('admin.mypage.recruit-categories.index'))->assertOk();
    $this->post('/system/category6/recruit/ajax_category.php', ['shop_cd' => '1'])->assertOk()->assertSee('ホール');
    $this->get(route('admin.mypage.app-images.edit', ['section' => 'all']))->assertOk();
    $this->post(route('admin.mypage.recruits.destroy'), ['delete_flg' => 'del', 'cd' => 70])->assertOk()->assertSee('削除中です。');
    expect(Recruit::query()->find(70))->toBeNull();
});
