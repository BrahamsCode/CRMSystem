<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/anketo_helpers.php';

/* F6 アンケート管理 / お客様アンケート管理: listas, cabecera (alta, 修正, acciones, 詳細) e ítems (alta, 修正, ▲▼, 削除, 詳細) */

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-11-27 10:00:00', 'Asia/Tokyo'));
    s4Login();
});

it('アンケート一覧 y 過去アンケート配信一覧 (anketo), y la URL del menú legacy', function () {
    anketoSeed('anketo');
    DB::table('tb_c3_anketo_cate')->insert(['cd' => 11, 'name' => '配信済み', 'comment' => '', 'disp_flg' => '1', 'hyouji_flg' => '1']);
    DB::table('mailmag_master')->insert(['cd' => 900, 'type_flg' => 'anketo', 'type_data_cd' => 11, '配信総数' => 5, 'search_display' => '性別：女性', 'タイトル' => 'ア']);
    DB::table('tb_c3_anketo_sub')->insert(['cd' => 900, 'tb_c3_anketo_cate_cd' => 11, 'tb_c3_anketo_form_cd' => 50, 'tb_customer_cd' => 1, 'disp_flg' => '1']);

    $this->get(route('admin.promocion.anketo.index'))
        ->assertOk()
        ->assertSee('<title>アンケート一覧 - ', false)
        ->assertSee('■アンケート一覧')
        ->assertSee('アンケート新規追加')
        ->assertSee('満足度調査')
        ->assertSee("管理用<br />\nメモ", false)
        ->assertDontSee('配信済み')
        ->assertSee(route('admin.promocion.anketo.forms.index', ['pcd' => 10, 'kensu' => 20, 'page' => 1]));

    $this->get(route('admin.promocion.anketo.index', ['which' => 'sended']))
        ->assertOk()
        ->assertSee('■過去アンケート配信一覧')
        ->assertSee('配信済み')
        ->assertDontSee('満足度調査')
        ->assertSee('性別：女性')
        ->assertSee('1 / 5 <small>(人)</small>', false)
        ->assertSee('解析');

    $this->get('/system/category3/anketo/anketo_cate_list.php?which=sended')
        ->assertRedirect(route('admin.promocion.anketo.index', ['which' => 'sended']));
});

it('お客様アンケート一覧: «選択» guarda la encuesta activa en contents_master (ajax_set_anketo.php)', function () {
    anketoSeed('anketo2');
    DB::table('tb_c3_anketo2_cate')->insert(['cd' => 12, 'name' => '登録途中', 'disp_flg' => '', 'hyouji_flg' => null]);

    $this->get(route('admin.promocion.anketo2.index', ['which' => 'sended']))
        ->assertOk()
        ->assertSee('■お客様アンケート一覧')
        ->assertSee('「お客様アンケートのURL」が挿入出来る', false)
        ->assertSee('満足度調査')
        ->assertDontSee('登録途中')
        ->assertSee('id="setAnketo_10" class="setAnketo" />', false)
        ->assertSee(route('admin.promocion.anketo2.answers', ['pcd' => 10, 'kensu2' => 20, 'page2' => 1, 'which' => 'sended']));

    $this->post('/system/category3/anketo2/ajax_set_anketo.php', ['anketo_cd' => '10'])->assertOk()->assertSee('success');
    expect(DB::table('contents_master')->where('category', 'tb_c3_anketo2')->where('field', 'set_anketo_cd')->value('int_data'))->toBe(10);

    $this->post('/system/category3/anketo2/ajax_set_anketo.php', ['anketo_cd' => 'x'])->assertSee('success');
    expect(DB::table('contents_master')->where('category', 'tb_c3_anketo2')->count())->toBe(1)
        ->and(DB::table('contents_master')->where('category', 'tb_c3_anketo2')->value('int_data'))->toBeNull();

    $this->post('/system/category3/anketo2/ajax_set_anketo.php', ['anketo_cd' => '10']);
    $this->get(route('admin.promocion.anketo2.index'))->assertSee('class="setAnketo" checked="checked"', false);

    $this->get('/system/category3/anketo2/anketo_cate_new.php')->assertRedirect(route('admin.promocion.anketo2.create'));
});

it('アンケート新規登録: comprueba nombre y fechas, registra con hatuban y pasa a los ítems', function () {
    DB::table('hatuban')->insert(['koumoku' => 'tb_c3_anketo_cate', 'kazu' => 40]);

    $this->get(route('admin.promocion.anketo.create'))
        ->assertOk()
        ->assertSee('■アンケート新規登録 - ', false)
        ->assertSee('name="start_date"', false);

    $this->post(route('admin.promocion.anketo.create'), ['act' => 'up', 'name' => '', 'start_date' => '2026/02/30', 'end_date' => '2026/11/01'])
        ->assertOk()
        ->assertSee('<p class="m0">※必須項目です。</p>', false)
        ->assertSee('※開始年月日：2026/02/30は不適切な形式です。')
        ->assertSee('※終了年月日：2026/11/01は過去の日付です。');

    $this->post(route('admin.promocion.anketo.create'), ['act' => 'up', 'name' => 'A', 'start_date' => '2026/12/10', 'end_date' => '2026/12/05'])
        ->assertSee('※終了年月日は開始年月日より前の日付です。');

    $this->post(route('admin.promocion.anketo.create'), ['act' => 'up', 'name' => ' 新しい"アンケート\' ', 'comment' => 'ﾒﾓ', 'start_date' => '2026/12/1', 'end_date' => '2026/12/31'])
        ->assertOk()
        ->assertSee('登録中です。')
        ->assertSee('content="1; URL='.route('admin.promocion.anketo.forms.index', ['pcd' => 40]), false);

    $row = DB::table('tb_c3_anketo_cate')->where('cd', 40)->first();
    expect($row->name)->toBe('新しいアンケート’')
        ->and($row->comment)->toBe('メモ')
        ->and($row->disp_flg)->toBe('1')
        ->and((int) $row->start_date)->toBe(Carbon::parse('2026-12-01 00:00:00', 'Asia/Tokyo')->getTimestamp())
        ->and((int) $row->end_date)->toBe(Carbon::parse('2026-12-31 23:59:59', 'Asia/Tokyo')->getTimestamp())
        ->and($row->touroku_date)->not->toBeNull();
});

it('アンケート修正: carga las fechas y guarda', function () {
    anketoSeed('anketo');

    $this->get(route('admin.promocion.anketo.edit', ['cd' => 10, 'page' => 2, 'kensu' => 10]))
        ->assertOk()
        ->assertSee('■アンケート修正')
        ->assertSee('value="2026/11/01"', false)
        ->assertSee('value="2026/12/31"', false)
        ->assertSee(route('admin.promocion.anketo.forms.index', ['pcd' => 10, 'page' => 2, 'kensu' => 10]));

    $this->post(route('admin.promocion.anketo.edit'), ['act' => 'up', 'cd' => 10, 'name' => '改名', 'comment' => '', 'start_date' => '2026/11/01', 'end_date' => '2027/01/31'])
        ->assertOk()->assertSee('登録中です。');
    expect(DB::table('tb_c3_anketo_cate')->where('cd', 10)->value('name'))->toBe('改名');

    $this->get(route('admin.promocion.anketo.edit', ['cd' => 999]))->assertRedirect('/system/category3/index.php');
});

it('お客様アンケート新規登録: sin fechas, queda pendiente (disp_flg vacío) hasta «アンケートを登録する»', function () {
    DB::table('hatuban')->insert(['koumoku' => 'tb_c3_anketo2_cate', 'kazu' => 7]);

    $this->get(route('admin.promocion.anketo2.create'))
        ->assertOk()
        ->assertSee('■お客様アンケート新規登録')
        ->assertDontSee('name="start_date"', false)
        ->assertSee('value="項目設定へ"', false);

    $this->post(route('admin.promocion.anketo2.create'), ['act' => 'up', 'name' => '来店アンケート'])
        ->assertRedirect(route('admin.promocion.anketo2.forms.index', ['register_type' => 'new', 'pcd' => 7]));
    expect(DB::table('tb_c3_anketo2_cate')->where('cd', 7)->value('disp_flg'))->toBe('');

    // Durante el alta: añadir, ▲▼, cancelar o registrar
    $this->get(route('admin.promocion.anketo2.forms.index', ['register_type' => 'new', 'pcd' => 7]))
        ->assertOk()
        ->assertSee('アンケート項目追加')
        ->assertSee('アンケート項目を追加してください。')
        ->assertSee('value="アンケートを登録する"', false)
        ->assertDontSee('一覧に戻る');
    // Fuera del alta no se ve hasta registrarla
    $this->get(route('admin.promocion.anketo2.forms.index', ['pcd' => 7]))->assertRedirect();

    $this->post(route('admin.promocion.anketo2.action'), ['act' => '登録', 'anketo_cate_cd' => 7])->assertOk()->assertSee('処理中です。');
    expect(DB::table('tb_c3_anketo2_cate')->where('cd', 7)->value('disp_flg'))->toBe('1');
});

it('acciones de la encuesta: 削除, 複製 (cabecera sin fechas + ítems) y 詳細', function (string $family) {
    anketoSeed($family);
    $f = \App\Services\Promotions\AnketoFamily::from($family);
    DB::table('hatuban')->insert([['koumoku' => $f->cateTable(), 'kazu' => 60], ['koumoku' => $f->formTable(), 'kazu' => 70]]);

    $this->post(route("admin.promocion.$family.action"), ['act' => '複製', 'anketo_cate_cd' => 10, 'page' => 1, 'kensu' => 20])
        ->assertOk()
        ->assertSee('処理中です。')
        ->assertSee('content="2; URL='.e(route("admin.promocion.$family.index", ['page' => 1, 'kensu' => 20])), false);
    $copy = DB::table($f->cateTable())->where('cd', 60)->first();
    expect($copy->name)->toBe('満足度調査')->and($copy->start_date)->toBeNull()->and($copy->disp_flg)->toBe('1')
        ->and(DB::table($f->formTable())->where('tb_c3_anketo_cate_cd', 60)->orderBy('rank')->pluck('name')->all())->toBe(['満足度', '来店理由', 'ご意見'])
        ->and(DB::table($f->formTable())->where('tb_c3_anketo_cate_cd', 60)->pluck('hyouji_flg')->unique()->all())->toBe(['1']);

    $this->get(route("admin.promocion.$family.show", ['cd' => 10]))
        ->assertOk()
        ->assertSee('■'.$f->title().' 詳細')
        ->assertSee('2026/12/31');

    $this->post(route("admin.promocion.$family.action"), ['act' => '削除', 'anketo_cate_cd' => 10]);
    expect(DB::table($f->cateTable())->where('cd', 10)->value('disp_flg'))->toBe('9');
    $this->get(route("admin.promocion.$family.index"))->assertDontSee('<td>10</td>', false)->assertSee('<td>60</td>', false);
})->with(['anketo', 'anketo2']);

it('詳細 + 部品一覧 (anketo): preview de los campos, 必須情報を更新, ▲▼ y 発行条件', function () {
    anketoSeed('anketo');

    $this->get(route('admin.promocion.anketo.forms.index', ['pcd' => 10, 'page' => 1, 'kensu' => 20]))
        ->assertOk()
        ->assertSee('<title>『満足度調査』 部品 一覧 - ', false)
        ->assertSee('■アンケート詳細')
        ->assertSee('icon_syosai.gif', false)
        ->assertSee('2026/11/01')
        ->assertSee('<input type="radio" name="form20" id="form200" class="checkBtn" value="良い"', false)
        ->assertSee('name="form21_2"', false)
        ->assertSee('<textarea name="form22"', false)
        ->assertSee('value="必須情報を更新"', false)
        ->assertSee('value="発行条件を選択する"', false)
        ->assertSee('<input type="hidden" name="hakkou_cd" id="hakkou_cd" value="10" />', false)
        ->assertSee(route('admin.promocion.anketo.mail.select'), false)
        ->assertSee('このアンケートを削除する');

    // 必須: el legacy también vacía serach_flg y hyouji_flg (no se envían)
    $this->post(route('admin.promocion.anketo.forms.index'), ['act' => 'update', 'update_max' => 3, 'pcd' => 10,
        'cd0' => 20, 'cd1' => 21, 'hissu1' => '1', 'cd2' => 22])->assertOk();
    expect(DB::table('tb_c3_anketo_form')->orderBy('cd')->pluck('hissu')->all())->toBe(['', '1', ''])
        ->and(DB::table('tb_c3_anketo_form')->where('cd', 21)->value('hyouji_flg'))->toBe('');

    $this->post(route('admin.promocion.anketo.forms.move'), ['pcd' => 10, 'thisCd' => 22, 'thisRow' => 3, 'kind' => 'up', 'sub_page' => 1, 'sub_kensu' => 20, 'page' => 1])
        ->assertRedirect(route('admin.promocion.anketo.forms.index', ['pcd' => 10, 'sub_kensu' => 20, 'sub_page' => 1, 'page' => 1]));
    expect(DB::table('tb_c3_anketo_form')->orderBy('rank')->pluck('cd')->all())->toBe([20, 22, 21]);

    // Fin pasado: no se puede emitir; emitida (hyouji_flg = 1): solo 複製
    DB::table('tb_c3_anketo_cate')->where('cd', 10)->update(['end_date' => Carbon::parse('2026-11-01', 'Asia/Tokyo')->getTimestamp()]);
    $this->get(route('admin.promocion.anketo.forms.index', ['pcd' => 10]))->assertSee('※終了年月日は過去の日付で発行できません');
    DB::table('tb_c3_anketo_cate')->where('cd', 10)->update(['hyouji_flg' => '1']);
    $this->get(route('admin.promocion.anketo.forms.index', ['pcd' => 10]))
        ->assertSee('このアンケートを複製する')
        ->assertDontSee('icon_syosai.gif', false)
        ->assertDontSee('必須情報を更新');

    $this->get(route('admin.promocion.anketo.forms.index', ['pcd' => 'x']))->assertRedirect(route('admin.promocion.anketo.index'));
});

it('アンケート項目一覧 (anketo2): 必須 y 回答一覧に表示 (-1)', function () {
    anketoSeed('anketo2');

    $this->get(route('admin.promocion.anketo2.forms.index', ['pcd' => 10]))
        ->assertOk()
        ->assertSee('■『満足度調査』 アンケート項目')
        ->assertSee('name="hyouji_flg0" value="-1" checked', false)
        ->assertSee('value="必須設定・回答一覧表示設定を更新"', false)
        ->assertSee('value="アンケートを削除する"', false)
        ->assertDontSee('情報修正・削除');

    $this->post(route('admin.promocion.anketo2.forms.index'), ['act' => 'update', 'update_max' => 2, 'pcd' => 10,
        'cd0' => 20, 'hissu0' => '1', 'cd1' => 21, 'hyouji_flg1' => '-1'])->assertOk();
    expect(DB::table('tb_c3_anketo2_form')->whereIn('cd', [20, 21])->orderBy('cd')->pluck('hyouji_flg')->all())->toBe(['', '-1']);
});

it('項目新規登録 / 修正 / 詳細 / 削除', function (string $family) {
    anketoSeed($family);
    $f = \App\Services\Promotions\AnketoFamily::from($family);
    DB::table('hatuban')->insert(['koumoku' => $f->formTable(), 'kazu' => 80]);

    $this->get(route("admin.promocion.$family.forms.create", ['pcd' => 10]))
        ->assertOk()
        ->assertSee('『満足度調査』')
        ->assertSee('<option value="セレクト">セレクト</option>', false)
        ->assertSee('<option value="checkBtn" >チェック・ラジオ用</option>', false);

    $this->post(route("admin.promocion.$family.forms.create"), ['act' => 'up', 'pcd' => 10, 'name' => '', 'size_h' => 'x'])
        ->assertOk()
        ->assertSee($family === 'anketo2' ? '項目タイトルは必ず入力して下さい。' : 'フォームタイトルは必ず入力して下さい。')
        ->assertSee('画像サイズ（縦）は必ず半角数字で入力して下さい。');

    $response = $this->post(route("admin.promocion.$family.forms.create"), ['act' => 'up', 'pcd' => 10, 'name' => '年代', 'unit' => '', 'comment' => '',
        'form_type' => 'セレクト', 'flds_row' => '2', 'eachform0' => '20代', 'eachsend0' => '20代', 'eachform1' => '30"代', 'eachsend1' => '30"代', 'style_class' => 'selectBox']);
    $form = DB::table($f->formTable())->where('cd', 80)->first();
    expect($form->rank)->toBe(4)
        ->and($form->titles)->toBe('20代<>30代')
        ->and($form->flds_row)->toBe(2)
        ->and($form->serach_flg)->toBe('1')
        ->and($form->size_w)->toBe('<><>')
        ->and($form->disp_flg)->toBe('1');
    if ($family === 'anketo2') {
        $response->assertRedirect(route('admin.promocion.anketo2.forms.index', ['register_type' => 'new', 'pcd' => 10]));
    } else {
        $response->assertOk()->assertSee('content="2; URL='.e(route('admin.promocion.anketo.forms.show', ['cd' => 80, 'register_type' => 'new'])), false);
    }

    $this->get(route("admin.promocion.$family.forms.show", ['cd' => 80]))
        ->assertOk()
        ->assertSee('オプション数:2')
        ->assertSee('表示値：30代')
        ->assertSee('セレクトボックス用');

    $this->get(route("admin.promocion.$family.forms.edit", ['cd' => 80, 'pcd' => 10]))
        ->assertOk()
        ->assertSee('■『満足度調査』')
        ->assertSee('<option value="セレクト" selected >セレクト</option>', false)
        ->assertSee('id="eachform1"', false)
        ->assertSee('value="30代"', false);

    $this->post(route("admin.promocion.$family.forms.edit"), ['act' => 'up', 'cd' => 80, 'pcd' => 10, 'name' => '年齢', 'form_type' => 'テキスト']);
    $form = DB::table($f->formTable())->where('cd', 80)->first();
    expect($form->name)->toBe('年齢')->and($form->flds_row)->toBeNull()->and($form->rank)->toBe(4);

    $this->post(route("admin.promocion.$family.forms.destroy"), ['cd' => 80, 'pcd' => 10]);
    expect(DB::table($f->formTable())->where('cd', 80)->exists())->toBeFalse();
})->with(['anketo', 'anketo2']);
