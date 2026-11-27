<?php

require_once __DIR__ . '/helpers.php';

use App\Models\Reservations\ReservationConfig;
use Illuminate\Support\Facades\DB;

/*
 * 予約設定 (yoyaku_config.php, yoyaku_form_config.php, yoyaku_kinou_config.php, yoyaku_comment_config.php).
 */

beforeEach(function () {
    reservasLogin();
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
});

function shopSettingInput(array $overrides = []): array
{
    return array_merge([
        'shop_cd' => '1', 'start_time1' => '10', 'start_time2' => '00', 'end_time1' => '20', 'end_time2' => '30',
        'shimekiri' => '0', 'shimekiri_time' => '3', 'uketuke_type' => '1', 'timeline_sort_type' => '',
        'setsubi_capacity' => '5', 'act' => 'reg', 'enter' => '確定',
    ], $overrides);
}

// ---------------------------------------------------------------- 各店舗設定

it('muestra 各店舗設定 con las tiendas', function () {
    DB::table('admin_master')->where('cd', 1)->update(['yoyaku_setsubi_flg' => '1']);

    $this->get(route('admin.reservas.settings.shop'))
        ->assertOk()
        ->assertSee('各店舗設定')
        ->assertSee('店舗受付開始時間')
        ->assertSee('本店')
        ->assertSee('キャパ')
        ->assertSee('reservas\/settings\/shop\/data', false);
});

it('crea la configuración de la tienda con hatuban y la devuelve por AJAX', function () {
    $this->post(route('admin.reservas.settings.shop.update'), shopSettingInput())
        ->assertRedirect(route('admin.reservas.settings.shop', ['shop_cd' => 1]))
        ->assertSessionHas('saved', true);

    $config = ReservationConfig::query()->where('tb_shop_cd', 1)->sole();
    expect($config->cd)->toBe(1)
        ->and($config->start_time)->toBe('10:00')
        ->and($config->end_time)->toBe('20:30')
        ->and($config->shimekiri)->toBe('0')
        ->and($config->shimekiri_time)->toBe('3')
        ->and($config->uketuke_type)->toBe('1')
        ->and($config->timeline_sort_type)->toBe('')
        ->and($config->automail_time)->toBe(':')
        ->and($config->setsubi_capacity)->toBe(5)
        ->and($config->touroku_date)->not->toBeEmpty();
    expect(DB::table('hatuban')->where('koumoku', 'tb_yoyaku_config')->value('kazu'))->toBe(2);

    $this->getJson(route('admin.reservas.settings.shop.data', ['shop_cd' => 1]))
        ->assertOk()
        ->assertExactJson([
            'start_time' => '10:00', 'end_time' => '20:30', 'shimekiri' => '0', 'shimekiri_time' => '3',
            'automail' => '', 'automail_time' => ':', 'uketuke_type' => '1', 'timeline_sort_type' => '',
            'setsubi_capacity' => '5',
        ]);

    // Segunda vez: actualiza la misma fila; キャパ 0 → NULL
    $this->post(route('admin.reservas.settings.shop.update'), shopSettingInput(['start_time1' => '09', 'setsubi_capacity' => '0']));
    expect(ReservationConfig::query()->count())->toBe(1)
        ->and(ReservationConfig::query()->find(1)->start_time)->toBe('09:00')
        ->and(ReservationConfig::query()->find(1)->setsubi_capacity)->toBeNull();

    $this->get(route('admin.reservas.settings.shop', ['shop_cd' => 1]))->assertSee('設定しました。');
});

it('rechaza horas incoherentes con los textos del legacy', function () {
    $this->post(route('admin.reservas.settings.shop.update'), shopSettingInput(['start_time1' => '']))
        ->assertSessionHasErrors(['start_time1' => '開始時間を選択して下さい！']);

    $this->post(route('admin.reservas.settings.shop.update'), shopSettingInput(['start_time1' => '21']))
        ->assertSessionHasErrors(['end_time1' => '時間を確認して下さい！']);

    expect(ReservationConfig::query()->count())->toBe(0);
});

it('devuelve campos vacíos por AJAX si la tienda no tiene configuración', function () {
    $this->getJson(route('admin.reservas.settings.shop.data', ['shop_cd' => 1]))
        ->assertOk()
        ->assertJson(['start_time' => '', 'end_time' => '', 'automail_time' => '', 'setsubi_capacity' => '']);
});

// ---------------------------------------------------------------- 予約フォーム設定

it('muestra y guarda 予約フォーム設定 (tb_koumoku de tb_yoyaku)', function () {
    $rows = [
        [1, 'name', '名前', '1', '1', '1', 1],
        [2, 'name_kana', 'フリガナ', '1', '', '', 2],
        [3, 'tel', '電話番号', '1', '1', '', 3],
        [4, 'mail', 'メールアドレス', '', '', '', 4],
        [5, 'free_koumoku_1', '備考', '', '', '', 1],
    ];
    foreach ($rows as [$cd, $koumoku, $label, $disp, $check, $select, $rank]) {
        DB::table('tb_koumoku')->insert([
            'cd' => $cd, 'system_cate' => '9', 'tb_name' => 'tb_yoyaku', 'tb_koumoku' => $koumoku,
            'tb_koumoku_name' => $label, 'koumoku_disp' => $disp, 'koumoku_check' => $check,
            'koumoku_select' => $select, 'rank' => $rank,
        ]);
    }
    // Campo libre de otra tabla: no se toca aunque llegue su cd
    DB::table('tb_koumoku')->insert(['cd' => 99, 'system_cate' => '1', 'tb_name' => 'tb_customer', 'tb_koumoku' => 'free_koumoku_1', 'tb_koumoku_name' => '顧客']);

    $this->get(route('admin.reservas.settings.form'))
        ->assertOk()
        ->assertSee('基本項目設定')
        ->assertSee('フリー項目設定')
        ->assertSee('フリガナ')
        ->assertSee('name="free_koumoku_name_5" value="備考"', false);

    $this->post(route('admin.reservas.settings.form.update'), [
        'koumoku_disp_name_kana' => '1', 'koumoku_check_name_kana' => '1',
        'koumoku_disp_mail' => '1', 'koumoku_select_mail' => '1',
        'free_koumoku_cd' => ['5', '99'],
        'free_koumoku_name_5' => '要望', 'free_koumoku_disp_5' => '1',
        'free_koumoku_name_99' => '改ざん',
        'act' => 'reg', 'enter' => '確定',
    ])->assertRedirect(route('admin.reservas.settings.form'))->assertSessionHas('saved', true);

    $get = fn ($cd) => DB::table('tb_koumoku')->where('cd', $cd)->first();
    expect([$get(1)->koumoku_disp, $get(1)->koumoku_check, $get(1)->koumoku_select])->toBe(['1', '1', '1'])
        ->and([$get(2)->koumoku_disp, $get(2)->koumoku_check, $get(2)->koumoku_select])->toBe(['1', '1', ''])
        ->and([$get(3)->koumoku_disp, $get(3)->koumoku_check, $get(3)->koumoku_select])->toBe(['', '', ''])
        ->and([$get(4)->koumoku_disp, $get(4)->koumoku_check, $get(4)->koumoku_select])->toBe(['1', '', '1'])
        ->and([$get(5)->tb_koumoku_name, $get(5)->koumoku_disp, $get(5)->koumoku_check])->toBe(['要望', '1', ''])
        ->and($get(99)->tb_koumoku_name)->toBe('顧客');
});

// ---------------------------------------------------------------- 機能設定

it('muestra y guarda 機能設定 en admin_master', function () {
    DB::table('admin_master')->where('cd', 1)->update(['yoyaku_time_interval' => '7', 'meyasu_jikan_flg' => '']);

    // 7 no es un intervalo permitido: se muestra 30
    $this->get(route('admin.reservas.settings.features'))
        ->assertOk()
        ->assertSee('予約時間間隔')
        ->assertSee('<option value="30" selected>30</option>', false);

    $this->post(route('admin.reservas.settings.features.update'), ['time_interval' => '40', 'meyasu_jikan_flg' => '9', 'act' => 'reg'])
        ->assertRedirect(route('admin.reservas.settings.features'));

    $row = DB::table('admin_master')->where('cd', 1)->first();
    expect($row->yoyaku_time_interval)->toBe('40')->and($row->meyasu_jikan_flg)->toBe('9');

    $this->post(route('admin.reservas.settings.features.update'), ['time_interval' => '15', 'meyasu_jikan_flg' => '']);
    $row = DB::table('admin_master')->where('cd', 1)->first();
    expect($row->yoyaku_time_interval)->toBe('15')->and($row->meyasu_jikan_flg)->toBe('');

    $this->get(route('admin.reservas.settings.features'))->assertSee('設定しました。');
});

// ---------------------------------------------------------------- 注釈設定

it('muestra y guarda 注釈設定 en tb_manage_words', function () {
    DB::table('tb_manage_words')->insert(['cd' => 9, 'content_name' => 'mobile_yoyaku[店舗選択_注釈_上]', 'naiyou' => '旧テキスト', 'hyouji_flg' => '1']);

    $this->get(route('admin.reservas.settings.comment'))
        ->assertOk()
        ->assertSee('注釈設定')
        ->assertSee('V2予約の顧客情報入力箇所')
        ->assertSee('旧テキスト');

    $this->post(route('admin.reservas.settings.comment.update'), [
        'comment_9' => '', 'comment_26' => 'スタッフを選んでください', 'act' => 'reg', 'enter' => '確定',
    ])->assertRedirect(route('admin.reservas.settings.comment'));

    $words = DB::table('tb_manage_words')->get()->keyBy('content_name');
    expect($words)->toHaveCount(2)
        ->and($words['mobile_yoyaku[店舗選択_注釈_上]']->naiyou)->toBe('')
        ->and($words['mobile_yoyaku[V2予約のスタッフ選択箇所]']->cd)->toBe(26)
        ->and($words['mobile_yoyaku[V2予約のスタッフ選択箇所]']->naiyou)->toBe('スタッフを選んでください')
        ->and($words['mobile_yoyaku[V2予約のスタッフ選択箇所]']->hyouji_flg)->toBe('1')
        ->and($words['mobile_yoyaku[V2予約のスタッフ選択箇所]']->update_date)->not->toBeEmpty();
});

it('guarda el nombre de la フリー項目 con form2Db() como yoyaku_form_config.php y lo imprime escapado', function () {
    DB::table('tb_koumoku')->insert([
        'cd' => 5, 'system_cate' => '9', 'tb_name' => 'tb_yoyaku', 'tb_koumoku' => 'free_koumoku_1',
        'tb_koumoku_name' => '備考', 'koumoku_disp' => '', 'koumoku_check' => '', 'koumoku_select' => '', 'rank' => 1,
    ]);

    $this->post(route('admin.reservas.settings.form.update'), [
        'free_koumoku_cd' => ['5'], 'free_koumoku_name_5' => "　ﾋﾞｺｳ <b>\"A\" 'B'</b>　", 'act' => 'reg', 'enter' => '確定',
    ])->assertRedirect();

    expect(DB::table('tb_koumoku')->where('cd', 5)->value('tb_koumoku_name'))->toBe("ビコウ <b>\"A\" 'B'</b>");

    $this->get(route('admin.reservas.settings.form'))
        ->assertOk()
        ->assertSee('name="free_koumoku_name_5" value="ビコウ &lt;b&gt;&quot;A&quot; &#039;B&#039;&lt;/b&gt;"', false)
        ->assertDontSee('<b>"A"', false);
});

it('guarda el 注釈 con form2Db() como yoyaku_comment_config.php y lo imprime escapado', function () {
    $this->post(route('admin.reservas.settings.comment.update'), [
        'comment_9' => "　ﾃﾝﾎﾟ <b>\"A\" 'B'</b>　", 'act' => 'reg', 'enter' => '確定',
    ])->assertRedirect();

    expect(DB::table('tb_manage_words')->where('cd', 9)->value('naiyou'))->toBe("テンポ <b>\"A\" 'B'</b>");

    $this->get(route('admin.reservas.settings.comment'))
        ->assertOk()
        ->assertSee('rows="3">テンポ &lt;b&gt;&quot;A&quot; &#039;B&#039;&lt;/b&gt;</textarea>', false)
        ->assertDontSee('<b>"A"', false);
});
