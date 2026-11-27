<?php

require_once __DIR__ . '/helpers.php';

use App\Models\ContentsMaster;
use Illuminate\Support\Facades\DB;
use Tests\Unit\Customers\LegacyFixtures;

/*
 * フリー予約設定 (free_yoyaku.php, free_yoyaku_register.php y ajax_free_yoyaku_rank.php).
 */

beforeEach(function () {
    reservasLogin();
});

function freeSetting(string $field, int $shopCd = 1): ?string
{
    return ContentsMaster::query()->entry('tb_c9_yoyaku', $field)->where('int_data', $shopCd)->value('text_data');
}

/** Empleados 11 y 12 en la tienda 1 con menús asignados (10 viene de reservasLogin) */
function freeStaff(): void
{
    LegacyFixtures::staff(11, 1, '佐藤');
    LegacyFixtures::staff(12, 1, '鈴木');
    LegacyFixtures::staff(13, 1, '未割当'); // sin menús: no aparece
    foreach ([10, 11, 12] as $i => $staffCd) {
        DB::table('tb_staff_works')->insert(['cd' => $i + 1, 'tb_staff_cd' => $staffCd, 'tb_yoyaku_koumoku_master_cd' => 100, 'disp_flg' => '1', 'hyouji_flg' => '1']);
    }
}

function freeRanks(): array
{
    $ranks = DB::table('tb_free_yoyaku_rank')->where('disp_flg', '1')->pluck('rank', 'tb_staff_cd')->all();
    ksort($ranks);

    return $ranks;
}

it('sin tienda elegida, master solo ve el selector', function () {
    $this->get(route('admin.reservas.free'))
        ->assertOk()
        ->assertSee('※登録・閲覧したい店舗を選択してください。')
        ->assertSee('本店')
        ->assertDontSee('フリー予約の使用');
});

it('muestra el formulario de la tienda con los valores guardados', function () {
    ContentsMaster::create(['category' => 'tb_c9_yoyaku', 'field' => 'free_yoyaku_disp_flg', 'int_data' => 1, 'text_data' => '1', 'disp_flg' => '1', 'hyouji_flg' => '1']);
    ContentsMaster::create(['category' => 'tb_c9_yoyaku', 'field' => 'free_yoyaku_name', 'int_data' => 1, 'text_data' => 'おまかせ', 'disp_flg' => '1', 'hyouji_flg' => '1']);

    $html = $this->get(route('admin.reservas.free', ['shop' => 1]))
        ->assertOk()
        ->assertSee('■本店')
        ->assertSee('フリー予約設定')
        ->assertSee('タイムラインのスタッフ名表示')
        ->assertSee('value="おまかせ"', false)
        ->assertSee(route('admin.reservas.free.ranks'), false)
        ->getContent();

    expect($html)->toContain('id="hyouji_flg2" class="checkBtn" checked="checked" value="1"')
        ->toContain('id="staff_select_flg1" class="checkBtn" checked="checked" value=""');
});

it('crea y luego actualiza los ajustes en contents_master', function () {
    $this->post(route('admin.reservas.free.update'), [
        'free_shop_cd' => 1, 'act' => 'new', 'hyouji_flg' => '1', 'name' => '指名なし予約', 'staff_select_flg' => '', 'timeline_staff_flg' => '1',
    ])->assertRedirect(route('admin.reservas.free', ['shop' => 1]));

    expect(freeSetting('free_yoyaku_disp_flg'))->toBe('1')
        ->and(freeSetting('free_yoyaku_name'))->toBe('指名なし予約')
        ->and(freeSetting('free_yoyaku_staff_select'))->toBe('')
        ->and(freeSetting('free_yoyaku_timeline_staff'))->toBe('1');

    $this->post(route('admin.reservas.free.update'), [
        'free_shop_cd' => 1, 'act' => 'new', 'hyouji_flg' => '', 'name' => '', 'staff_select_flg' => '1', 'timeline_staff_flg' => '',
    ])->assertRedirect();

    expect(ContentsMaster::query()->where('category', 'tb_c9_yoyaku')->count())->toBe(4)
        ->and(freeSetting('free_yoyaku_disp_flg'))->toBe('')
        ->and(freeSetting('free_yoyaku_name'))->toBe('')
        ->and(freeSetting('free_yoyaku_staff_select'))->toBe('1');
});

it('lista el personal con menús y crea su orden la primera vez', function () {
    freeStaff();

    $this->post(route('admin.reservas.free.ranks'), ['shop_cd' => 1])
        ->assertOk()
        ->assertSee('フリー予約の割り当て優先順位')
        ->assertSeeInOrder(['山田', '佐藤', '鈴木'])
        ->assertDontSee('未割当')
        ->assertSee("statusControl('1', 'rankdown', '11', '10', '1')", false);

    expect(freeRanks())->toBe([10 => 1, 11 => 2, 12 => 3]);
});

it('▲▼ intercambia el orden', function () {
    freeStaff();
    $this->post(route('admin.reservas.free.ranks'), ['shop_cd' => 1]);

    // ▼ en 山田 (10): pasa detrás de 佐藤 (11)
    $this->post(route('admin.reservas.free.ranks'), [
        'shop_cd' => 1, 'send_type' => 'rankdown', 'send_targetCd' => 10, 'send_prevCd' => '', 'send_vl' => '1',
    ])->assertOk()->assertSeeInOrder(['佐藤', '山田', '鈴木']);
    expect(freeRanks())->toBe([10 => 2, 11 => 1, 12 => 3]);

    // ▲ en 鈴木 (12), cuya fila anterior es 山田 (10)
    $this->post(route('admin.reservas.free.ranks'), [
        'shop_cd' => 1, 'send_type' => 'rankup', 'send_targetCd' => 12, 'send_prevCd' => 10, 'send_vl' => '3',
    ])->assertOk()->assertSeeInOrder(['佐藤', '鈴木', '山田']);
    expect(freeRanks())->toBe([10 => 3, 11 => 1, 12 => 2]);

    // ▲ en la primera fila (sin anterior): no cambia nada
    $this->post(route('admin.reservas.free.ranks'), [
        'shop_cd' => 1, 'send_type' => 'rankup', 'send_targetCd' => 11, 'send_prevCd' => '', 'send_vl' => '1',
    ])->assertOk()->assertSeeInOrder(['佐藤', '鈴木', '山田']);
});
