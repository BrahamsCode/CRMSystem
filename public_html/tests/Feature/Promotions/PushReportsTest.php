<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

require_once __DIR__.'/push_helpers.php';

/*
 * M5 プッシュ通知: 予定/フォロー/自動の一覧, 過去配信内容一覧 y 詳細 (gráficos y clientes por porción),
 * プッシュ集計 y su detalle con CSV.
 */
beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-11-27 10:00:00', 'Asia/Tokyo'));
    s4Login();
    pushSeed();
    Storage::fake('public');
});

/** Dos push enviados: 700 (puntos, con imagen y enlace) abierto por 101 a los 30 min; 701 sin abrir */
function pushSent(): void
{
    $at = Carbon::parse('2026-11-20 12:00:00', 'Asia/Tokyo')->getTimestamp();
    DB::table('push_master')->insert([
        ['cd' => 700, 'tb_shop_cd' => -1, 'title' => '冬のセール', 'message' => serialize(['本文<b>', 'foto.jpg']), 'pic_link' => serialize([1 => 'https://example.com/s']),
            'img_flg' => '1', 'wherestr' => "WHERE disp_flg = '1' AND cd IN (101, 102)", 'search_display' => '全員', 'disp_flg' => '1', 'touroku_date' => $at,
            'update_date' => $at, 'version' => 2, 'post_type' => '2', 'post_point' => 50, 'point_expiration_date' => $at + 86400, 'type_flg' => null],
        ['cd' => 701, 'tb_shop_cd' => 1, 'title' => '通常のお知らせ', 'message' => serialize(['こんにちは']), 'pic_link' => serialize([]),
            'img_flg' => '1', 'wherestr' => 'WHERE cd = 103', 'search_display' => 'テスト配信', 'disp_flg' => '1', 'touroku_date' => $at - 86400,
            'update_date' => $at, 'version' => 2, 'post_type' => '1', 'post_point' => null, 'point_expiration_date' => null, 'type_flg' => null],
    ]);
    DB::table('push_notifications')->insert([
        ['cd' => 1, 'type' => 'push', 'type_master_cd' => 700, 'tb_customer_cd' => 101, 'registration_id' => 'r1', 'touroku_date' => $at, 'open_cnt' => 2, 'open_date' => $at + 1800],
        ['cd' => 2, 'type' => 'push', 'type_master_cd' => 700, 'tb_customer_cd' => 102, 'registration_id' => 'r2', 'touroku_date' => $at, 'open_cnt' => null, 'open_date' => null],
        ['cd' => 3, 'type' => 'push', 'type_master_cd' => 701, 'tb_customer_cd' => 103, 'registration_id' => 'r3', 'touroku_date' => $at, 'open_cnt' => null, 'open_date' => null],
    ]);
    Storage::disk('public')->put('hp/uploads/c3/push/700/foto.jpg', 'x');
}

it('予定/フォロー/自動通知設定一覧: reglas activas y paradas de su tipo, con 停止/起動/修正 solo para master', function () {
    pushFollow(['cd' => 1, 'type_flg' => 'scheduled', 'title' => '予定A', 'send_parameter' => 'send_date=2026/12/01,point_expiration_date=', 'send_time' => '09:30',
        'next_send_date' => Carbon::parse('2026-12-01 09:30', 'Asia/Tokyo')->getTimestamp()]);
    pushFollow(['cd' => 2, 'type_flg' => 'scheduled', 'title' => '予定B', 'disp_flg' => '7']);
    pushFollow(['cd' => 3, 'type_flg' => 'scheduled', 'title' => '予定C 解除済', 'disp_flg' => '9']);
    pushFollow(['cd' => 4, 'type_flg' => 'auto', 'title' => '自動A', 'send_parameter' => '1<>01,03<>05,32']);
    pushFollow(['cd' => 5, 'type_flg' => 'follow', 'title' => 'フォローA']);

    $this->get('/system/category3/push/push_follow_list.php?type_flg=scheduled')
        ->assertRedirect(route('admin.promocion.push.follow-list').'?type_flg=scheduled');

    $this->get(route('admin.promocion.push.follow-list', ['type_flg' => 'scheduled']))
        ->assertOk()
        ->assertSee('<title>予定通知設定一覧 - ', false)
        ->assertSee('■予定通知設定一覧')
        ->assertSee('1件～2件目/2件中')
        ->assertSeeInOrder(['2026/12/01<br/>09:30', '予定A', '2026-12-01 09:30', '起動中', '停止する', '予定B', '停止中', '起動する'], false)
        ->assertSee(e(route('admin.promocion.push.create', ['type_flg' => 'scheduled', 'cd' => 1])), false)
        ->assertDontSee('予定C');

    $this->get(route('admin.promocion.push.follow-list', ['type_flg' => 'auto']))
        ->assertOk()->assertSee('■自動通知設定一覧')->assertSee('自動A')->assertSee('配信月：01月 03月　・　配信日：05日 月末')->assertDontSee('フォローA');
    $this->get(route('admin.promocion.push.follow-list', ['type_flg' => 'follow']))
        ->assertOk()->assertSee('■フォロー通知設定一覧')->assertSee('フォローA');
    $this->get(route('admin.promocion.push.follow-list', ['type_flg' => 'otro']))->assertNotFound();
});

it('予定通知設定一覧: un usuario de tienda solo ve las de su tienda y sin botones (el legacy no definía $kengenNo)', function () {
    DB::table('tb_staff')->insert(['cd' => 2, 'office_cd' => 1, 'shop_cd' => 1, 'login_id' => 'tienda', 'login_pass' => 'secreto2', 'name' => '店長', 'disp_flg' => '1']);
    pushFollow(['cd' => 1, 'type_flg' => 'scheduled', 'title' => 'Empresa', 'tb_shop_cd' => -1]);
    pushFollow(['cd' => 2, 'type_flg' => 'scheduled', 'title' => 'Tienda 1', 'tb_shop_cd' => 1]);
    $this->get('/system/logout.php');
    loginAs('tienda', 'secreto2');

    $this->get(route('admin.promocion.push.follow-list', ['type_flg' => 'scheduled']))
        ->assertOk()->assertSee('Tienda 1')->assertDontSee('Empresa')->assertDontSee('value="停止する"', false)->assertDontSee('修正する');
});

it('過去配信内容一覧: búsqueda, tipo, contadores de apertura y enlace al detalle', function () {
    pushSent();
    $this->get('/system/category3/push/push_past.php')->assertRedirect(route('admin.promocion.push.past'));

    $this->get(route('admin.promocion.push.past'))
        ->assertOk()
        ->assertSee('<title>過去配信内容一覧一覧 - ', false)
        ->assertSee('■過去配信検索')
        ->assertSee('1件～2件目/2件中')
        ->assertSeeInOrder(['700', 'l_e_point_40.png', '2026年11月20日 12:00:00', '冬のセール', '50%', '総配信数:2', '閲覧のべ人数:2', '閲覧実人数:1', '701', '通常のお知らせ', '0%'], false)
        ->assertSee(route('admin.promocion.push.show').'?pushcd=700', false);

    $this->post(route('admin.promocion.push.past'), ['texto' => '通常'])->assertSee('通常のお知らせ')->assertDontSee('冬のセール');
    $this->post(route('admin.promocion.push.past'), ['point_add_flg' => '1'])->assertSee('冬のセール')->assertDontSee('通常のお知らせ');
    $this->post(route('admin.promocion.push.past'), ['etc_flg' => '1'])->assertSee('通常のお知らせ')->assertDontSee('冬のセール');
    $this->post(route('admin.promocion.push.past'), ['from_year' => '2026', 'from_month' => '11', 'from_day' => '20'])
        ->assertSee('冬のセール')->assertDontSee('通常のお知らせ');
    // Orden por 総配信数 (desc): 700 (2) antes que 701 (1); por 閲覧実人数 igual
    $this->post(route('admin.promocion.push.past'), ['sort_value' => 'total_send'])->assertSeeInOrder(['冬のセール', '通常のお知らせ']);
    $this->get(route('admin.promocion.push.past', ['sort_value' => 'opne_rate']))->assertOk()->assertSeeInOrder(['冬のセール', '通常のお知らせ']);
});

it('過去プッシュ配信詳細: datos, bloques con imagen y enlace, gráficos y clientes por porción', function () {
    pushSent();
    $url = route('admin.promocion.push.show', ['pushcd' => 700, 'page' => 1]);

    $this->get($url)
        ->assertOk()
        ->assertSee('■過去プッシュ配信詳細')
        ->assertSee('ポイントチケット配信')
        ->assertSee('50ポイント')
        ->assertSee('本文&lt;b&gt;', false)
        ->assertSee('<a href="https://example.com/s" target="_blank"><img width="100%" style="max-width:320px" src="/hp/uploads/c3/push/700/foto.jpg" ></a>', false)
        ->assertSee('テスト株式会社')
        ->assertSee('2026年11月20日 12:00:00')
        ->assertSee('時間別集計')
        ->assertSee('性別集計')
        ->assertSee('年代別集計')
        ->assertSee('"label":"1時間以内","index":"+1 hour"', false)
        ->assertSee('job_index', false);

    // Porciones: 1時間以内 (101), 未読 (102), 男性 (101), ocupación 会社員 (101), 30～39歳 (101)
    $this->get($url.'&calc_index=%2B1+hour')->assertSee('■集計グラフ - 時間別集計 - 1時間以内')->assertSee('山田太郎')->assertDontSee('佐藤花子');
    $this->get($url.'&calc_index=')->assertSee('時間別集計 - 未読')->assertSee('佐藤花子')->assertDontSee('山田太郎');
    $this->get($url.'&sex_index=man')->assertSee('性別集計 - 男性')->assertSee('山田太郎');
    $this->get($url.'&job_index='.urlencode('会社員'))->assertSee('職業集計 - 会社員')->assertSee('山田太郎');
    $this->get($url.'&age_index=30')->assertSee('年代集計 - 30～39歳')->assertSee('山田太郎')->assertSee('<td>36</td>', false);

    $this->get(route('admin.promocion.push.show', ['pushcd' => 999]))->assertNotFound();
    $this->get('/system/category3/push/push_past_shousai.php?pushcd=700')->assertRedirect(route('admin.promocion.push.show').'?pushcd=700');
});

it('プッシュ集計 y su detalle: contadores, 会員件数 del wherestr y CSV de los destinatarios', function () {
    pushSent();
    $this->get('/system/category3/push/push_history.php')->assertRedirect(route('admin.promocion.push.history'));

    $this->get(route('admin.promocion.push.history'))
        ->assertOk()
        ->assertSee('■プッシュ集計')
        ->assertSeeInOrder(['11月20日', '金', '12:00', '全員', '冬のセール', '本文&lt;b&gt;', '/hp/uploads/c3/push/700/foto.jpg', '<td>2</td>', '>2</a>', '>1</a>', '<td>1</td>', '<td>50.0</td>',
            '11月19日', '木', '通常のお知らせ'], false);

    $detail = route('admin.promocion.push.history.show', ['list_page' => 1, 'list_kensu' => 20, 'master_cd' => 700, 'type' => 'haishin']);
    $this->get($detail)->assertOk()->assertSee('■プッシュ集計 - 詳細')->assertSee('山田太郎')->assertSee('佐藤花子')->assertSee('2025年04月01日');
    $this->get(route('admin.promocion.push.history.show', ['master_cd' => 700, 'type' => 'eturan']))->assertSee('山田太郎')->assertDontSee('佐藤花子');

    $csv = $this->get($detail.'&csv_print=1')->assertOk();
    expect($csv->headers->get('content-disposition'))->toContain('attachment')
        ->and(mb_convert_encoding($csv->streamedContent(), 'UTF-8', 'SJIS-win'))
        ->toContain("顧客名,電話番号,登録店舗,年齢,性別,職業,会員登録日\n")
        ->toContain('山田太郎,0311112222,本店,36,男性,会社員,2025年04月01日');
});
