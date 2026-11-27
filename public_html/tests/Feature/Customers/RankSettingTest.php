<?php

require_once __DIR__ . '/../Reservations/helpers.php';

use App\Models\Promotions\KingakuRankMaster;
use App\Models\Promotions\RaitenRankMaster;
use Illuminate\Support\Facades\DB;

// 顧客ランク設定 (rank/customer_rank_list.php, *_rank_new/_update.php, customer_rank_frm*.php, ajax_customer_rank_list.php)
// y 顧客ランク振り分け設定 (rank/customer_rank_schedule.php + _register.php)

/** Activa los módulos de rangos (tb_base_system_grandchild) */
function clientesActivarRangos(bool $kingaku = true, bool $raiten = true): void
{
    $rows = [];
    if ($kingaku) {
        $rows[] = ['cd' => 901, 'system_name' => '顧客ランク（使用金額ランク）', 'system_flg' => '1', 'rank' => 1];
    }
    if ($raiten) {
        $rows[] = ['cd' => 902, 'system_name' => '顧客ランク（来店回数ランク）', 'system_flg' => '1', 'rank' => 2];
    }
    DB::table('tb_base_system_grandchild')->insert($rows);
}

beforeEach(function () {
    reservasLogin();
});

it('carga la lista de rangos desde el menú con los dos tipos activos', function () {
    clientesActivarRangos();
    KingakuRankMaster::create(['name' => 'ゴールド', 'jyouken1' => 10000, 'jyouken2' => 50000, 'disp_flg' => '1', 'hyouji_flg' => '1', 'rank' => 1]);
    RaitenRankMaster::create(['name' => '常連', 'jyouken1' => 5, 'disp_flg' => '1', 'hyouji_flg' => '9', 'rank' => 1]);

    $this->get('/system/category1/rank/customer_rank_list.php')
        ->assertOk()
        ->assertSee('顧客ランク設定 - ', false)
        ->assertSee('■マイページへのランク表示設定')
        ->assertSee('両方表示する')
        ->assertSee('■使用金額ランク設定')
        ->assertSee('■来店回数ランク設定')
        ->assertSee('ゴールド')
        ->assertSee('期間内の使用金額が <span class="bold attention">10,000円</span> 以上 ～ <span class="bold attention">50,000円</span> 未満', false)
        ->assertSee('期間内の来店回数が <span class="bold attention">5回</span> 以上', false)
        ->assertSee('name="hyouji_flg_', false)
        // ランクを表示しない (customer_rank_disp_flg vacío = '9')
        ->assertSee('id="rank_disp9" name="customer_rank_disp_flg" checked', false)
        // Ordenar (sortable): la lista de tipos va sin espacios, si no el selector sería "# raitenRankList"
        ->assertSee("var baseStr = 'kingaku,raiten';", false);
});

it('sin módulos activos no muestra ninguna lista', function () {
    $this->get(route('admin.clientes.ranks'))
        ->assertOk()
        ->assertDontSee('■使用金額ランク設定')
        ->assertDontSee('両方表示する');
});

it('cambia el 表示設定 de マイページ en admin_master', function () {
    clientesActivarRangos();

    $this->post(route('admin.clientes.ranks.display'), ['customer_rank_disp_flg' => '1', 'act' => '表示を変更'])
        ->assertRedirect(route('admin.clientes.ranks'));

    expect(DB::table('admin_master')->where('cd', 1)->value('customer_rank_disp_flg'))->toBe('1');
    $this->get(route('admin.clientes.ranks'))->assertSee('表示設定を変更しました。');
});

it('da de alta y edita un rango con hatuban y form2Db', function () {
    clientesActivarRangos();

    $this->get(route('admin.clientes.ranks.create', 'kingaku'))
        ->assertOk()
        ->assertSee('使用金額ランク - ', false)
        ->assertSee('■顧客ランク 新規登録')
        ->assertSee('期間内の使用金額が')
        ->assertSee('この内容で登録する');

    $this->post(route('admin.clientes.ranks.store', 'kingaku'), [
        'hyouji_flg' => '1', 'name' => ' ｺﾞｰﾙﾄﾞ ', 'jyouken1' => '10000', 'jyouken2' => '', 'act' => 'new',
    ])->assertRedirect(route('admin.clientes.ranks'));

    $rank = KingakuRankMaster::query()->firstOrFail();
    expect($rank->name)->toBe('ゴールド')
        ->and($rank->jyouken1)->toBe(10000)
        ->and($rank->jyouken2)->toBeNull()
        ->and($rank->hyouji_flg)->toBe('1')
        ->and($rank->disp_flg)->toBe('1')
        ->and($rank->touroku_date)->not->toBeEmpty();

    $this->get(route('admin.clientes.ranks.edit', ['kingaku', $rank->cd]))
        ->assertOk()
        ->assertSee('■顧客ランク 更新')
        ->assertSee('value="ゴールド"', false);

    $this->put(route('admin.clientes.ranks.update', ['kingaku', $rank->cd]), [
        'hyouji_flg' => '9', 'name' => 'ゴールド2', 'jyouken1' => '', 'jyouken2' => '30000', 'act' => 'update',
    ])->assertRedirect(route('admin.clientes.ranks'));

    $rank->refresh();
    expect($rank->name)->toBe('ゴールド2')->and($rank->jyouken1)->toBeNull()->and($rank->jyouken2)->toBe(30000)->and($rank->hyouji_flg)->toBe('9');
});

it('valida el rango con los mensajes del legacy', function () {
    clientesActivarRangos();

    $this->from(route('admin.clientes.ranks.create', 'raiten'))
        ->post(route('admin.clientes.ranks.store', 'raiten'), ['hyouji_flg' => '1', 'name' => '', 'jyouken1' => '', 'jyouken2' => ''])
        ->assertRedirect(route('admin.clientes.ranks.create', 'raiten'))
        ->assertSessionHasErrors(['name' => '※必須入力項目です。', 'jyouken' => '※必須入力項目です。必ずどちらかに値を入力してください。']);

    $this->post(route('admin.clientes.ranks.store', 'raiten'), ['hyouji_flg' => '1', 'name' => 'A', 'jyouken1' => '10', 'jyouken2' => '5'])
        ->assertSessionHasErrors(['jyouken' => '※不正な入力です。一つ目の値が、二つ目の値を下回っています。']);

    expect(RaitenRankMaster::query()->count())->toBe(0);

    // Vuelve al formulario con la celda marcada y los valores enviados
    $this->from(route('admin.clientes.ranks.create', 'raiten'))
        ->followingRedirects()
        ->post(route('admin.clientes.ranks.store', 'raiten'), ['hyouji_flg' => '1', 'name' => 'A', 'jyouken1' => '10', 'jyouken2' => '5'])
        ->assertSee('<td class="Ng">', false)
        ->assertSee('※不正な入力です。一つ目の値が、二つ目の値を下回っています。')
        ->assertSee('value="10"', false);
});

it('el AJAX del legacy cambia 表示 y el orden; 削除 pone disp_flg = 9', function () {
    clientesActivarRangos();
    $a = KingakuRankMaster::create(['name' => 'A', 'jyouken1' => 1, 'disp_flg' => '1', 'hyouji_flg' => '1', 'rank' => 1]);
    $b = KingakuRankMaster::create(['name' => 'B', 'jyouken1' => 2, 'disp_flg' => '1', 'hyouji_flg' => '1', 'rank' => 2]);
    $c = KingakuRankMaster::create(['name' => 'C', 'jyouken1' => 3, 'disp_flg' => '1', 'hyouji_flg' => '1', 'rank' => 3]);

    $this->post('/system/category1/rank/ajax_customer_rank_list.php', ['type' => 'hyouji', 'cd' => $a->cd, 'ranktype' => 'kingaku', 'checked' => 'false'])
        ->assertOk()->assertSee('success');
    expect($a->fresh()->hyouji_flg)->toBe('9');

    // Orden nuevo: C, A (B no viene → rank null, como el legacy)
    $this->post('/system/category1/rank/ajax_customer_rank_list.php', ['type' => 'rank', 'ranktype' => 'kingaku', "r_{$c->cd}" => '1', "r_{$a->cd}" => '2'])
        ->assertOk();
    expect([$c->fresh()->rank, $a->fresh()->rank, $b->fresh()->rank])->toBe([1, 2, null]);

    $this->delete(route('admin.clientes.ranks.destroy', ['kingaku', $b->cd]))->assertRedirect(route('admin.clientes.ranks'));
    expect(DB::table('tb_kingaku_rank_master')->where('cd', $b->cd)->value('disp_flg'))->toBe('9');

    $this->get(route('admin.clientes.ranks'))->assertSeeInOrder(['>C<', '>A<'], false)->assertDontSee('>B<', false);
});

it('carga 顧客ランク振り分け設定 con el estado OFF y guarda meses/días y hora', function () {
    clientesActivarRangos();

    $this->get('/system/category1/rank/customer_rank_schedule.php')
        ->assertOk()
        ->assertSee('顧客ランク振り分け設定 - ', false)
        ->assertSee('■使用金額ランク 振り分け設定')
        ->assertSee('■来店回数ランク 振り分け設定')
        ->assertSee('現在の振り分け設定は')
        ->assertSee('>OFF<', false)
        ->assertSee('月末');

    $this->post(route('admin.clientes.rank-schedules.update'), [
        'act' => 'kingaku', 'rank_shori_kikan' => '30', 'type_flg' => '1',
        'month' => ['1', '4'], 'day' => ['1', '32'], 'hour' => '03', 'minute' => '15',
    ])->assertRedirect(route('admin.clientes.rank-schedules'));

    $master = DB::table('admin_master')->where('cd', 1)->first();
    expect($master->kingaku_rank_shori_date)->toBe('1<>1,4<>1,32')
        ->and($master->kingaku_rank_shori_time)->toBe('03:15')
        ->and($master->kingaku_rank_shori_kikan)->toBe('30');

    $this->post(route('admin.clientes.rank-schedules.update'), [
        'act' => 'raiten', 'rank_shori_kikan' => '7', 'type_flg' => '2', 'week' => ['0', '6'], 'hour' => '22', 'minute' => '00',
    ]);
    expect(DB::table('admin_master')->where('cd', 1)->value('raiten_rank_shori_date'))->toBe('2<>0,6');

    $this->get(route('admin.clientes.rank-schedules'))
        ->assertSee('>ON<', false)
        ->assertSee('id="kingaku_month_4" class="chkbox1" value="4" checked', false)
        ->assertSee('id="kingaku_day_last" class="chkbox2" value="32" checked', false)
        ->assertSee('value="03" selected', false)
        ->assertSee('id="raiten_week_6" class="chkbox3" value="6" checked', false)
        ->assertSee('id="raiten_type_flg_2" class="mR5" value="2" checked', false);

    // 設定を解除する: solo act
    $this->post(route('admin.clientes.rank-schedules.update'), ['act' => 'kingaku']);
    $master = DB::table('admin_master')->where('cd', 1)->first();
    expect($master->kingaku_rank_shori_date)->toBe('')->and($master->kingaku_rank_shori_time)->toBe('')->and($master->kingaku_rank_shori_kikan)->toBe('');
});

it('oculta el bloque de 振り分け de un módulo inactivo', function () {
    clientesActivarRangos(kingaku: false);

    $this->get(route('admin.clientes.rank-schedules'))
        ->assertOk()
        ->assertDontSee('■使用金額ランク 振り分け設定')
        ->assertSee('■来店回数ランク 振り分け設定');
});
