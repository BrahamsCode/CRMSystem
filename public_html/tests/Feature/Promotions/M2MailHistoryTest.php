<?php

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

/*
 * M2c: 予定配信 (lista, 修正, 予定解除) y 過去配信 (lista, borrado, 詳細 con クリックカウンタ, 配信先 y CSV).
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
    if (! Route::has('legacy.promocion.inc.ajax_mail_delete')) {
        Route::middleware('web')->name('legacy.')->group(base_path('routes/legacy/promocion.php'));
        app('router')->getRoutes()->refreshNameLookups();
    }
    $this->withoutMiddleware(Authenticate::class);
    $this->travelTo(Carbon::parse('2026-11-26 10:00:00', 'Asia/Tokyo'));

    DB::table('admin_master')->insert(['cd' => 1, 'application_use_flg' => 0]);
    DB::table('tb_office')->insert(['cd' => 1, 'kaishamei' => '本社']);
    DB::table('tb_shop')->insert(['cd' => 1, 'kaishamei' => '本店', 'disp_flg' => '1']);
    DB::table('hatuban')->insert(['koumoku' => 'send_schedule', 'kazu' => 1]);
    DB::table('tb_customer')->insert([
        ['cd' => 1, 'id' => 'A001', 'name' => '山田', 'email1' => 'a@example.com', 'mailmaga_email_type' => 'パソコン', 'touroku_kubun' => '2', 'disp_flg' => '1', 'kojin_birth' => '1990/01/02', 'kanri_id' => 'K1'],
        ['cd' => 2, 'id' => 'A002', 'name' => '佐藤', 'email1' => 'b@example.com', 'mailmaga_email_type' => 'AU', 'touroku_kubun' => '2', 'disp_flg' => '1', 'kojin_birth' => '', 'kanri_id' => ''],
    ]);
    $where = "WHERE cd IN (select cd from tb_customer where disp_flg = '1')";
    DB::table('mailmag_master')->insert([
        ['cd' => 10, '店id' => '1', '登録者' => '1', 'タイトル' => '予定', 'pc内容' => '本文', '配信日時' => '2026/12/01 09:30:00', '配信日' => '2026/12/01', '配信時間' => '09:30:00',
            '配信状況' => '1', 'type_flg' => '', '受付メールアドレス' => 'なし', '振分配信' => $where.' AND cd NOT IN (2)', '振分条件' => '2', 'search_display' => '全員', '配信総数' => 1, '画像有無' => '0'],
        ['cd' => 11, '店id' => '1', '登録者' => '1', 'タイトル' => '済み', 'pc内容' => '過去の本文', '配信日時' => '2026/11/01 09:00:00', '配信日' => '2026/11/01', '配信時間' => '09:00:00',
            '配信状況' => '2', 'type_flg' => '', '受付メールアドレス' => 'なし', '振分配信' => $where, '振分条件' => '', 'search_display' => '全員', '配信総数' => 2, '画像有無' => '0'],
    ]);
    DB::table('send_schedule')->insert(['cd' => 9, 'mag_cd' => 10, '配信時間' => '2026/12/01 09:30:00', 'flg' => '1', '店id' => '1']);
});

it('lista los programados y abre su edición con la búsqueda guardada y los datos', function () {
    $this->get(route('admin.promocion.mail.future'))->assertOk()->assertSee('■予約メール配信')->assertSee('予定')->assertDontSee('済み')
        ->assertSee(route('admin.promocion.mail.edit') . '?mailmagcd=10', false);

    $this->get(route('admin.promocion.mail.edit', ['mailmagcd' => 11]))->assertRedirect(route('admin.promocion.mail.future'));

    $this->get(route('admin.promocion.mail.edit', ['mailmagcd' => 10]))->assertOk()
        ->assertSee('value="予定"', false)
        ->assertSee('<option value="12" selected>12月</option>', false)
        ->assertSee('<option value="30" selected>30分</option>', false)
        ->assertSee('name="reigai_kaiin_cds" value="2"', false)
        ->assertSee('予定解除する')
        ->assertDontSee('即時配信する')
        ->assertSee(route('admin.promocion.mail.edit-confirm'), false);
});

it('confirma y guarda la edición manteniendo los 例外顧客 y reprogramando', function () {
    $response = $this->get(route('admin.promocion.mail.edit', ['mailmagcd' => 10]));
    preg_match('/name="search_uniqid" value="(\w{16})"/', $response->getContent(), $m);
    $form = ['search_uniqid' => $m[1], 'mailmagcd' => '10', 'title' => '変更', 'naiyou' => '新しい本文', 'tourokusya' => '1', 'bikou' => '',
        'reigai_kaiin_cds' => '2', 'send_flg' => '0', 'nen' => '2026', 'tuki' => '12', 'hi' => '02', 'jikan' => '08', 'fun' => '00'];

    $this->post(route('admin.promocion.mail.edit-confirm'), $form)->assertOk()
        ->assertSee('戻　　る')->assertDontSee('抽出しなおす')->assertSee(route('admin.promocion.mail.update'), false);

    $this->post(route('admin.promocion.mail.update'), $form + ['mail_send_verify' => '77'])->assertOk()->assertSee('以下の内容で予約いたしました。');

    $mail = DB::table('mailmag_master')->where('cd', 10)->first();
    expect($mail->{'タイトル'})->toBe('変更')
        ->and($mail->{'配信日時'})->toBe('2026/12/02 08:00:00')
        ->and($mail->{'配信総数'})->toBe(1)
        ->and($mail->{'振分配信'})->toBe("WHERE cd IN (select cd from tb_customer where disp_flg = '1') AND cd NOT IN (2)")
        ->and($mail->search_display)->toBe('全員');
    expect(DB::table('send_schedule')->where('mag_cd', 10)->value('配信時間'))->toBe('2026/12/02 08:00:00');
});

it('pide confirmación y borra el programado con 予定解除', function () {
    $this->post(route('admin.promocion.mail.cancel'), ['mailmagcd' => 10])->assertOk()->assertSee('「<font color="red">2026/12/01 09:30:00</font>」予定配信を解除致します。', false);
    expect(DB::table('mailmag_master')->where('cd', 10)->exists())->toBeTrue();

    $this->post(route('admin.promocion.mail.cancel'), ['mailmagcd' => 10, 'dele' => '1'])->assertOk()->assertSee('配信予定を解除致しました。');
    expect(DB::table('mailmag_master')->where('cd', 10)->exists())->toBeFalse()
        ->and(DB::table('send_schedule')->count())->toBe(0);
});

it('lista los pasados con búsqueda, tasa de clics y borrado lógico', function () {
    DB::table('tb_mailmag_analyze')->insert(['mailmag_master_cd' => 11, 'tb_customer_cd' => 1, 'touroku_date' => 1764000000, 'customer_sex' => '男性', 'customer_age' => 35, 'customer_job' => '会社員']);

    $this->get(route('admin.promocion.mail.past'))->assertOk()->assertSee('済み')->assertDontSee('<td>予定</td>', false)
        ->assertSee('50.00%')->assertSee('(1件/2件)');
    $this->get(route('admin.promocion.mail.past', ['texto' => '存在しない']))->assertOk()->assertSee('現在過去メール配信はありません');
    $this->get(route('admin.promocion.mail.past', ['from_year' => '2026', 'from_month' => '11', 'from_day' => '02']))->assertOk()->assertDontSee('<td>済み</td>', false);

    $this->post(route('legacy.promocion.inc.ajax_mail_delete'), ['dataCd' => '11'])->assertOk();
    expect(DB::table('mailmag_master')->where('cd', 11)->value('disp_flg'))->toBe('9');
});

it('muestra el detalle con la クリックカウンタ y descarga sus clics en CSV', function () {
    DB::table('tb_mailmag_analyze')->insert([
        ['mailmag_master_cd' => 11, 'tb_customer_cd' => 1, 'touroku_date' => 1764000000, 'customer_sex' => '男性', 'customer_age' => 35, 'customer_job' => '会社員'],
        ['mailmag_master_cd' => 11, 'tb_customer_cd' => 2, 'touroku_date' => 1764000100, 'customer_sex' => '女性', 'customer_age' => null, 'customer_job' => ''],
    ]);

    $this->get(route('admin.promocion.mail.show', ['mailmagcd' => 11]))->assertOk()
        ->assertSee('■過去メール配信詳細')
        ->assertSee('["男性", 1]', false)
        ->assertSee('["30代", 1]', false)
        ->assertSee('["その他", 1, "#CCCCCC"]', false)
        ->assertSee('["会社員",1,"#1829CE"]', false)
        ->assertSee('同じ内容で新規にメール配信する');

    $csv = $this->post(route('admin.promocion.mail.clicks-csv'), ['mailmagcd' => 11])->assertOk()->streamedContent();
    $text = mb_convert_encoding($csv, 'UTF-8', 'SJIS-win');
    expect($text)->toContain('"普通メールマガ"')->toContain('"メールCD: 11"')->toContain("\"\t1\",\"\t".date('Y/m/d H:i', 1764000000)."\",\"\t35\",\"\t男性\",\"\t会社員\"");
});

it('lista los destinatarios con su apertura y los exporta en CSV', function () {
    DB::table('mailmag_sended_log')->insert([
        ['mailmag_master_cd' => 11, 'tb_customer_cd' => 1, 'email' => 'a@example.com'],
        ['mailmag_master_cd' => 11, 'tb_customer_cd' => 2, 'email' => 'b@example.com'],
    ]);
    DB::table('tb_mailmag_analyze')->insert(['mailmag_master_cd' => 11, 'tb_customer_cd' => 2, 'touroku_date' => 1764000000]);

    $this->get(route('admin.promocion.mail.recipients', ['mailmagcd' => 11]))->assertOk()
        ->assertSeeInOrder(['b@example.com', '○', 'a@example.com'])->assertSee('1件～2件目/2件中');

    $csv = mb_convert_encoding($this->post(route('admin.promocion.mail.recipients'), ['mailmagcd' => 11, 'csvFlg' => '1'])->streamedContent(), 'UTF-8', 'SJIS-win');
    expect($csv)->toContain("2026/11/01 09:00:00配信メール　(ID: 11)\r\n顧客ID,管理番号,顧客名,配信先メールアドレス,生年月日,開封\r\nA002,\t,佐藤,b@example.com,\t,○\r\nA001,\tK1,山田,a@example.com,\t1990/01/02,\r\n");

    $this->get(route('admin.promocion.mail.recipients', ['mailmagcd' => 999]))->assertSee('不正アクセス！！！');
});
