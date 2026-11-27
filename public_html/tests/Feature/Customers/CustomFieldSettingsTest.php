<?php

use Illuminate\Support\Facades\DB;

require_once __DIR__ . '/../Reservations/helpers.php';

/*
 * C2-a: 表示・検索・CSV項目設定 (form_config.php) y 顧客追加情報設定 (search_cate_*.php, search_form_*.php).
 */

function clientesConfCategoria(int $cd, array $extra = []): void
{
    DB::table('tb_search_cate')->insert($extra + [
        'cd' => $cd, 'name' => "カテゴリ{$cd}", 'category' => 'category1', 'disp_flg' => '1', 'hyouji_flg' => '1',
        'serach_flg' => '1', 'record_type' => '', 'disp_column' => '1', 'rank' => $cd,
    ]);
}

function clientesConfCampo(int $cd, int $cate, array $extra = []): void
{
    DB::table('tb_search_form')->insert($extra + [
        'cd' => $cd, 'tb_search_cate_cd' => $cate, 'category' => 'category1', 'name' => "項目{$cd}", 'form_type' => 'テキスト',
        'disp_flg' => '1', 'hyouji_flg' => '1', 'serach_flg' => '1', 'hissu' => '', 'csv_flg' => '', 'rank' => $cd,
    ]);
}

beforeEach(function () {
    reservasLogin();
});

// ── 表示・検索・CSV項目設定 ────────────────────────────────────────────────

it('muestra la matriz de 表示・検索・CSV項目設定 con tb_koumoku y los campos adicionales', function () {
    DB::table('tb_koumoku')->insert([
        ['cd' => 1, 'tb_name' => 'tb_customer', 'tb_koumoku' => 'name', 'tb_koumoku_name' => '名前', 'koumoku_disp' => '1', 'koumoku_check' => '1', 'mail_mag_select' => '', 'koumoku_select' => '1', 'rank' => 1],
        ['cd' => 2, 'tb_name' => 'tb_customer', 'tb_koumoku' => 'id', 'tb_koumoku_name' => '顧客番号', 'koumoku_disp' => '', 'koumoku_check' => '', 'mail_mag_select' => '1', 'koumoku_select' => '', 'rank' => 2],
        ['cd' => 3, 'tb_name' => 'tb_staff', 'tb_koumoku' => 'x', 'tb_koumoku_name' => 'スタッフ項目', 'koumoku_disp' => '', 'koumoku_check' => '', 'mail_mag_select' => '', 'koumoku_select' => '', 'rank' => 1],
    ]);
    clientesConfCategoria(50, ['name' => '趣味情報']);
    clientesConfCategoria(51, ['name' => '複数カテゴリ', 'record_type' => '1']);
    clientesConfCampo(60, 50, ['name' => '好きな色', 'hissu' => '1', 'hyouji_flg' => '2']);

    $this->get(route('admin.clientes.field-settings'))
        ->assertOk()
        ->assertSee('■表示・検索・CSV項目設定', false)
        ->assertSee('<small>名前</small>', false)
        ->assertSee('<small>顧客番号</small>', false)
        ->assertDontSee('スタッフ項目')
        ->assertSee('趣味情報')
        ->assertDontSee('複数カテゴリ')
        ->assertSee('好きな色')
        ->assertSee('name="tb_search_form_hissu_0" id="tb_search_form_hissu_0" value="1" checked="checked"', false)
        ->assertSee('<option value="2" selected >管理画面のみ</option>', false)
        ->assertSee('name="item_max_no" value="2"', false)
        ->assertSee('この内容で変更する');

    // URL del menú lateral del legacy
    $this->get('/system/category1/customer/form_config.php')->assertOk()->assertSee('■表示・検索・CSV項目設定', false);
});

it('guarda la matriz como form_config_register.php y muestra 登録中です。', function () {
    DB::table('tb_koumoku')->insert([
        'cd' => 1, 'tb_name' => 'tb_customer', 'tb_koumoku' => 'name', 'tb_koumoku_name' => '名前',
        'koumoku_disp' => '1', 'koumoku_check' => '1', 'mail_mag_select' => '', 'koumoku_select' => '', 'rank' => 1,
    ]);
    clientesConfCategoria(50);
    clientesConfCampo(60, 50);

    $this->post(route('admin.clientes.field-settings.update'), [
        'item_max_no' => 1, 'item_cd_1' => 1, 'item_cd1_1' => '', 'item_cd2_1' => '', 'item_cd3_1' => '1', 'item_cd4_1' => '1',
        'send_search_form_max' => 1, 'tb_search_form_cd_0' => 60, 'tb_search_form_disp_0' => '2', 'tb_search_form_hissu_0' => '1',
        'tb_search_form_csv_0' => '1', 'act' => 'up',
    ])->assertOk()
        ->assertSee('登録中です。')
        ->assertSee('content="2; URL=' . route('admin.clientes.field-settings') . '"', false);

    $koumoku = DB::table('tb_koumoku')->where('cd', 1)->first();
    expect([$koumoku->koumoku_disp, $koumoku->koumoku_check, $koumoku->mail_mag_select, $koumoku->koumoku_select])->toBe(['', '', '1', '1']);
    $field = DB::table('tb_search_form')->where('cd', 60)->first();
    expect([$field->hyouji_flg, $field->hissu, $field->serach_flg, $field->csv_flg])->toBe(['2', '1', '', '1']);
});

// ── 顧客追加情報カテゴリ設定 ───────────────────────────────────────────────

it('lista las categorías de clientes con paginación y la URL del menú legacy', function () {
    clientesConfCategoria(1, ['name' => '来店情報']);
    clientesConfCategoria(2, ['name' => '削除済み', 'disp_flg' => '9']);
    clientesConfCategoria(3, ['name' => '他モジュール', 'category' => 'category3']);

    $this->get(route('admin.clientes.custom-categories'))
        ->assertOk()
        ->assertSee('■顧客追加情報カテゴリ設定一覧', false)
        ->assertSee('来店情報')
        ->assertDontSee('削除済み')
        ->assertDontSee('他モジュール')
        ->assertSee('1件～1件目/1件中')
        ->assertSee('チェックした情報を更新する');

    $this->get('/system/category1/customer/search_cate_list.php')->assertOk()->assertSee('来店情報');
});

it('da de alta una categoría con hatuban, get_form2() y rank al final', function () {
    clientesConfCategoria(1);
    DB::table('hatuban')->insert(['koumoku' => 'tb_search_cate', 'kazu' => 7]);

    $this->get(route('admin.clientes.custom-categories.create'))->assertOk()->assertSee('顧客追加情報カテゴリ設定登録');

    $this->post(route('admin.clientes.custom-categories.store'), [
        'name' => ' 　家族 & "情報"　', 'comment' => '<b>メモ</b>', 'disp_column' => '2', 'record_type' => '1', 'act' => 'new',
    ])->assertOk()->assertSee('登録中です。');

    $row = DB::table('tb_search_cate')->where('cd', 7)->first();
    expect($row->name)->toBe('家族 &amp; &quot;情報&quot;')
        ->and($row->comment)->toBe('&lt;b&gt;メモ&lt;/b&gt;')
        ->and($row->category)->toBe('category1')
        ->and($row->disp_column)->toBe('2')
        ->and($row->record_type)->toBe('1')
        ->and($row->rank)->toBe(2)
        ->and($row->disp_flg)->toBe('1');
    expect(DB::table('hatuban')->where('koumoku', 'tb_search_cate')->value('kazu'))->toBe(8);
});

it('exige カテゴリー名 como search_cate_new_check.php', function () {
    $this->from(route('admin.clientes.custom-categories.create'))
        ->post(route('admin.clientes.custom-categories.store'), ['name' => '　', 'act' => 'new'])
        ->assertRedirect(route('admin.clientes.custom-categories.create'));

    $this->get(route('admin.clientes.custom-categories.create'))
        ->assertSee('<p class="m0">カテゴリー名は必ず入力して下さい。</p>', false)
        ->assertSee('class="Ng"', false);
    expect(DB::table('tb_search_cate')->count())->toBe(0);
});

it('edita una categoría (pasa al final como el legacy) y actualiza 検索/表示 de la lista', function () {
    clientesConfCategoria(1, ['name' => 'A&amp;B']);
    clientesConfCategoria(2);

    $this->get(route('admin.clientes.custom-categories.edit', 1))
        ->assertOk()
        ->assertSee('value="A&amp;B"', false)
        ->assertSee('削除する');

    $this->put(route('admin.clientes.custom-categories.update', 1), ['name' => '新名称', 'comment' => '', 'disp_column' => '3', 'record_type' => ''])
        ->assertOk()->assertSee('登録中です。');
    $row = DB::table('tb_search_cate')->where('cd', 1)->first();
    expect($row->name)->toBe('新名称')->and($row->disp_column)->toBe('3')->and($row->rank)->toBe(3);

    $this->post(route('admin.clientes.custom-categories.flags'), [
        'act' => 'update', 'update_max' => 2, 'cd0' => 2, 'serach_flg0' => '1', 'cd1' => 1, 'hyouji_flg1' => '1', 'page' => 1,
    ])->assertRedirect(route('admin.clientes.custom-categories', ['page' => 1]));
    expect(DB::table('tb_search_cate')->where('cd', 2)->value('hyouji_flg'))->toBe('')
        ->and(DB::table('tb_search_cate')->where('cd', 1)->value('serach_flg'))->toBe('')
        ->and(DB::table('tb_search_cate')->where('cd', 1)->value('hyouji_flg'))->toBe('1');
});

it('ordena categorías con ▲▼ y renumera', function () {
    clientesConfCategoria(1, ['rank' => 1]);
    clientesConfCategoria(2, ['rank' => 5]);
    clientesConfCategoria(3, ['rank' => 9]);

    $this->post(route('admin.clientes.custom-categories.sort'), ['thisCd' => 3, 'thisRow' => 9, 'kind' => 'up'])
        ->assertRedirect(route('admin.clientes.custom-categories'));

    expect(DB::table('tb_search_cate')->orderBy('rank')->pluck('rank', 'cd')->all())->toBe([1 => 1, 3 => 2, 2 => 3]);
});

it('borra una categoría con sus campos y valores de clientes (disp_flg = 9) y renumera', function () {
    clientesConfCategoria(1, ['rank' => 1]);
    clientesConfCategoria(2, ['rank' => 2]);
    clientesConfCategoria(3, ['rank' => 3]);
    clientesConfCampo(60, 2);
    DB::table('tb_customer_sub')->insert(['cd' => 900, 'tb_customer_cd' => 1, 'tb_search_cate_cd' => 2, 'tb_search_form_cd' => 60, 'text_data' => 'x', 'disp_flg' => '1']);

    $this->delete(route('admin.clientes.custom-categories.destroy', 2))->assertOk()->assertSee('削除中です。');

    expect(DB::table('tb_search_cate')->where('cd', 2)->value('disp_flg'))->toBe('9')
        ->and(DB::table('tb_search_form')->where('cd', 60)->value('disp_flg'))->toBe('9')
        ->and(DB::table('tb_customer_sub')->where('cd', 900)->value('disp_flg'))->toBe('9')
        ->and(DB::table('tb_search_cate')->where('cd', 3)->value('rank'))->toBe(2);
});

it('no abre categorías de otros módulos', function () {
    clientesConfCategoria(3, ['category' => 'category3']);

    $this->get(route('admin.clientes.custom-categories.edit', 3))->assertNotFound();
    $this->get(route('admin.clientes.custom-fields', 3))->assertNotFound();
});

// ── 検索フォーム部品設定 (campos) ──────────────────────────────────────────

it('lista los campos de una categoría con la vista previa y la columna メルマガ置き換え', function () {
    clientesConfCategoria(50, ['name' => '趣味情報']);
    clientesConfCampo(60, 50, ['name' => '好きな色', 'form_type' => 'セレクト', 'flds_row' => 2, 'titles' => '赤<>青', 'data' => 'red<>blue', 'comment' => "一行目\n二行目"]);
    clientesConfCampo(61, 50, ['name' => '削除済み項目', 'disp_flg' => '9']);

    $this->get(route('admin.clientes.custom-fields', 50))
        ->assertOk()
        ->assertSee('■『趣味情報』 検索フォーム部品設定一覧', false)
        ->assertSee('好きな色')
        ->assertDontSee('削除済み項目')
        ->assertSee('<option value="red">赤</option>', false)
        ->assertSee('一行目<br />', false)
        ->assertSee('メルマガ<br/>置き換え', false)
        ->assertSee('カテゴリ登録一覧へ戻る');

    $this->get('/system/category1/customer/search_form_list.php?pcd=50')->assertRedirect(route('admin.clientes.custom-fields', 50));
    $this->get('/system/category1/customer/search_form_list.php')->assertRedirect(route('admin.clientes.custom-categories'));
});

it('da de alta un campo チェックボックス con opciones titles/data <> como search_form_new_up.php', function () {
    clientesConfCategoria(50);
    clientesConfCampo(60, 50, ['rank' => 4]);
    DB::table('hatuban')->insert(['koumoku' => 'tb_search_form', 'kazu' => 70]);

    $this->get(route('admin.clientes.custom-fields.create', 50))
        ->assertOk()
        ->assertSee('フォームタイトル')
        ->assertSee('<option value="メニュー項目">メニュー項目</option>', false)
        ->assertSee('▼スタイル選択');

    $this->post(route('admin.clientes.custom-fields.store', 50), [
        'name' => '興味', 'unit' => '', 'comment' => '', 'form_type' => 'チェックボックス', 'flds_row' => '2',
        'eachform0' => '映画', 'eachsend0' => 'movie', 'eachform1' => '音楽 & 本', 'eachsend1' => 'music',
        'style_class' => 'checkBtn', 'act' => 'new', 'pcd' => 50,
    ])->assertOk()->assertSee('登録中です。');

    $row = DB::table('tb_search_form')->where('cd', 70)->first();
    expect($row->tb_search_cate_cd)->toBe(50)
        ->and($row->category)->toBe('category1')
        ->and($row->titles)->toBe('映画<>音楽 &amp; 本')
        ->and($row->data)->toBe('movie<>music')
        ->and($row->flds_row)->toBe(2)
        ->and($row->style_class)->toBe('checkBtn')
        ->and($row->serach_flg)->toBe('1')
        ->and($row->rank)->toBe(5)
        ->and($row->disp_flg)->toBe('1');
});

it('guarda los 単位 de テーブル unidos con <> y exige フォームタイトル', function () {
    clientesConfCategoria(50);

    $this->from(route('admin.clientes.custom-fields.create', 50))
        ->post(route('admin.clientes.custom-fields.store', 50), ['name' => '', 'form_type' => 'テーブル'])
        ->assertRedirect(route('admin.clientes.custom-fields.create', 50));
    $this->get(route('admin.clientes.custom-fields.create', 50))->assertSee('フォームタイトルは必ず入力して下さい。');

    $this->post(route('admin.clientes.custom-fields.store', 50), [
        'name' => '測定', 'form_type' => 'テーブル', 'flds_col' => '3', 'flds_row' => '2',
        'eachform0' => '身長', 'eachsend0' => 'inputS', 'eachunit0' => 'cm', 'eachform1' => '体重', 'eachsend1' => 'inputS', 'eachunit1' => 'kg',
    ])->assertOk();

    $row = DB::table('tb_search_form')->where('name', '測定')->first();
    expect($row->unit)->toBe('cm<>kg')->and($row->flds_col)->toBe(3)->and($row->data)->toBe('inputS<>inputS');
});

it('edita un campo con sus opciones, actualiza las banderas y lo ordena', function () {
    clientesConfCategoria(50);
    clientesConfCampo(60, 50, ['form_type' => 'ラジオボタン', 'flds_row' => 2, 'titles' => 'はい<>いいえ', 'data' => 'yes<>no', 'rank' => 1]);
    clientesConfCampo(61, 50, ['rank' => 2]);

    $this->get(route('admin.clientes.custom-fields.edit', 60))
        ->assertOk()
        ->assertSee('登録更新')
        ->assertSee('id="eachform1" onchange="document.getElementById(\'eachsend1\').value=this.value" class="inputM" value="いいえ"', false)
        ->assertSee('<option value="ラジオボタン" selected >ラジオボタン</option>', false);

    $this->put(route('admin.clientes.custom-fields.update', 60), [
        'name' => '同意', 'form_type' => 'テキスト', 'unit' => '件', 'comment' => '', 'style_class' => '', 'pcd' => 50,
    ])->assertOk()->assertSee('登録中です。');
    $row = DB::table('tb_search_form')->where('cd', 60)->first();
    // Como el legacy: sin opciones se vacían titles/data pero flds_row se conserva
    expect($row->name)->toBe('同意')->and($row->titles)->toBe('')->and($row->flds_row)->toBe(2)->and($row->unit)->toBe('件')->and($row->rank)->toBe(1);

    $this->post(route('admin.clientes.custom-fields.flags', 50), [
        'act' => 'update', 'update_max' => 1, 'cd0' => 61, 'hissu0' => '1', 'mail_word_flg0' => '1', 'hyouji_flg0' => '2',
    ])->assertRedirect(route('admin.clientes.custom-fields', 50));
    $flags = DB::table('tb_search_form')->where('cd', 61)->first();
    expect([$flags->hissu, $flags->serach_flg, $flags->mail_word_flg, $flags->hyouji_flg])->toBe(['1', '', '1', '2']);

    $this->post(route('admin.clientes.custom-fields.sort', 50), ['thisCd' => 61, 'thisRow' => 2, 'kind' => 'up'])
        ->assertRedirect(route('admin.clientes.custom-fields', 50));
    expect(DB::table('tb_search_form')->orderBy('rank')->pluck('cd')->all())->toBe([61, 60]);
});

it('borra un campo y sus valores de clientes (disp_flg = 9)', function () {
    clientesConfCategoria(50);
    clientesConfCampo(60, 50);
    DB::table('tb_customer_sub')->insert(['cd' => 900, 'tb_customer_cd' => 1, 'tb_search_cate_cd' => 50, 'tb_search_form_cd' => 60, 'text_data' => 'x', 'disp_flg' => '1']);

    $this->delete(route('admin.clientes.custom-fields.destroy', 60))
        ->assertOk()
        ->assertSee('削除中です。')
        ->assertSee('content="2; URL=' . route('admin.clientes.custom-fields', 50) . '"', false);

    expect(DB::table('tb_search_form')->where('cd', 60)->value('disp_flg'))->toBe('9')
        ->and(DB::table('tb_customer_sub')->where('cd', 900)->value('disp_flg'))->toBe('9');
});
