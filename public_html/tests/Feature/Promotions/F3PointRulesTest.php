<?php

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

/*
 * F3: ポイント発行ルール (point_rules_list / _new / _update / _errchk / _register / _delete).
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
    $this->travelTo(Carbon::parse('2026-11-26 10:00:00', 'Asia/Tokyo'));

    DB::table('tb_shop')->insert([['cd' => 1, 'kaishamei' => '本店', 'disp_flg' => '1'], ['cd' => 2, 'kaishamei' => '支店', 'disp_flg' => '1']]);
    DB::table('hatuban')->insert(['koumoku' => 'tb_point_rules', 'kazu' => 10]);
    DB::table('tb_point_rules')->insert([
        ['cd' => 1, 'tb_shop_cd' => -1, 'type_flg' => '1', 'start_time' => 0, 'end_time' => 1200, 'day_of_week' => null, 'point_count' => 2, 'hyouji_flg' => '1', 'update_date' => 100],
        ['cd' => 2, 'tb_shop_cd' => 1, 'type_flg' => '4', 'start_time' => 32, 'end_time' => null, 'day_of_week' => null, 'point_count' => 3, 'hyouji_flg' => '9', 'update_date' => 100],
        ['cd' => 3, 'tb_shop_cd' => 1, 'type_flg' => '2', 'start_time' => 900, 'end_time' => 1800, 'day_of_week' => 6, 'point_count' => 5, 'hyouji_flg' => '1', 'update_date' => 200],
    ]);
});

it('lista por tienda en orden de prioridad y actualiza 適用', function () {
    $this->get(route('admin.promocion.points.rules.index'))->assertOk()
        ->assertSeeInOrder(['本店のポイントルール', '毎月&nbsp;末日', '毎週土曜日&nbsp;9:00時 から 18:00時 まで', '全店舗のポイントルール', '毎日&nbsp;0:00時 から 12:00時 まで'], false)
        ->assertSee('1件～3件目/3件中');

    $this->post(route('admin.promocion.points.rules.index'), ['update_hyouji_flg' => 'update', 'update_cd' => ['1', '2', '3'], 'hyouji_flg_array' => ['2' => 'on']])->assertOk();
    expect(DB::table('tb_point_rules')->orderBy('cd')->pluck('hyouji_flg')->all())->toBe(['9', '', '9']);
});

it('valida como point_rules_errchk', function () {
    $this->post(route('admin.promocion.points.rules.create'), ['act' => 'up', 'tb_shop_cd' => '', 'type_flg' => '0', 'point_count' => '1'])->assertOk()
        ->assertSee('※店舗を選択して下さい。')->assertSee('※ルール区分を選択して下さい。');
    $this->post(route('admin.promocion.points.rules.create'), ['act' => 'up', 'tb_shop_cd' => '1', 'type_flg' => '1', 'start_time' => '1500', 'end_time' => '900'])
        ->assertSee('※終了時間は開始時間より前の時間です。');
    $this->post(route('admin.promocion.points.rules.create'), ['act' => 'up', 'tb_shop_cd' => '1', 'type_flg' => '3',
        'start_year' => '2026', 'start_month' => '02', 'start_day' => '30', 'start_time' => '0:00',
        'end_year' => '2026', 'end_month' => '12', 'end_day' => '01', 'end_time' => '0:00'])
        ->assertSee('※開始日付：2026/02/30は不適切な形式です。');
    $this->post(route('admin.promocion.points.rules.create'), ['act' => 'up', 'tb_shop_cd' => '1', 'type_flg' => '3',
        'start_year' => '2026', 'start_month' => '12', 'start_day' => '10', 'start_time' => '0:00',
        'end_year' => '2026', 'end_month' => '12', 'end_day' => '01', 'end_time' => '0:00'])
        ->assertSee('※終了日時は開始日時より前の日付です。');
    expect(DB::table('tb_point_rules')->count())->toBe(3);
});

it('da de alta con hatuban, epoch y rangos y modifica', function () {
    DB::table('tb_base_system_grandchild')->insert(['cd' => 5, 'system_name' => '顧客ランク(使用金額)', 'system_flg' => '1']);
    DB::table('tb_kingaku_rank_master')->insert([['cd' => 7, 'name' => 'ゴールド', 'disp_flg' => '1', 'rank' => 1], ['cd' => 8, 'name' => 'シルバー', 'disp_flg' => '1', 'rank' => 2]]);
    $this->get(route('admin.promocion.points.rules.create'))->assertOk()->assertSee('使用金額ランク')->assertDontSee('来店回数ランク')->assertSee('<option value="-1" >全店舗</option>', false);

    $this->post(route('admin.promocion.points.rules.create'), ['act' => 'up', 'tb_shop_cd' => '2', 'type_flg' => '3',
        'start_year' => '2026', 'start_month' => '12', 'start_day' => '01', 'start_time' => '9:00',
        'end_year' => '2026', 'end_month' => '12', 'end_day' => '24', 'end_time' => '21:00', 'point_count' => '4', 'tb_kingaku_rank_master_cds' => ['7', '8']])
        ->assertRedirect(route('admin.promocion.points.rules.registered', ['mode' => 'new']));
    $rule = DB::table('tb_point_rules')->where('cd', 10)->first();
    expect([(int) $rule->tb_shop_cd, $rule->type_flg, (int) $rule->point_count, $rule->hyouji_flg, $rule->tb_kingaku_rank_master_cds, $rule->tb_raiten_rank_master_cds])
        ->toBe([2, '3', 4, '1', '7,8', null])
        ->and((int) $rule->start_time)->toBe(Carbon::parse('2026-12-01 09:00', 'Asia/Tokyo')->getTimestamp())
        ->and((int) $rule->end_time)->toBe(Carbon::parse('2026-12-24 21:00', 'Asia/Tokyo')->getTimestamp());

    // El formulario de modificación reconstruye fecha y hora
    $this->get(route('admin.promocion.points.rules.edit', ['cd' => 10]))->assertOk()
        ->assertSee('<option value="24" selected>24</option>', false)->assertSee('<option value="21:00" selected>21:00</option>', false)
        ->assertSee('id="kingaku_rank8" value="8" checked', false);

    $this->post(route('admin.promocion.points.rules.edit'), ['act' => 'up', 'cd' => '10', 'tb_shop_cd' => '2', 'type_flg' => '5',
        'start_year' => '2027', 'start_month' => '01', 'start_day' => '01', 'point_count' => '2'])
        ->assertRedirect(route('admin.promocion.points.rules.registered', ['mode' => 'update']));
    $rule = DB::table('tb_point_rules')->where('cd', 10)->first();
    expect([$rule->type_flg, (int) $rule->end_time - (int) $rule->start_time, $rule->tb_kingaku_rank_master_cds, $rule->hyouji_flg])->toBe(['5', 86399, null, '1'])
        ->and(DB::table('hatuban')->where('koumoku', 'tb_point_rules')->value('kazu'))->toBe(11);

    $this->get(route('admin.promocion.points.rules.registered', ['mode' => 'update']))->assertSee('■ポイント発行ルール 修正')->assertSee('登録中です。');
});

it('borra físicamente y vuelve a la lista con page y kensu', function () {
    $this->post(route('admin.promocion.points.rules.destroy'), ['cd' => '3', 'page' => '1', 'kensu' => '10'])->assertOk()
        ->assertSee('削除中です。')->assertSee('point_rules_list?page=1&amp;kensu=10', false);
    expect(DB::table('tb_point_rules')->where('cd', 3)->exists())->toBeFalse();
});
