<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

require_once __DIR__.'/push_helpers.php';

/*
 * M5 プッシュ通知: 発行条件選択 → 新規作成 (bloques texto + imagen con enlace, puntos) → ajaxSavePush (push_master +
 * aviso a g-api), prueba a un socio, programado / フォロー / 自動 (push_follow_master), edición, 停止 / 起動 / 解除.
 */
beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-11-27 10:00:00', 'Asia/Tokyo'));
    s4Login();
    pushSeed();
    Storage::fake('public');
    Http::fake();
    config(['crm.gapi.enabled' => true]); // las pruebas comprueban la llamada a g-api
});

function pushAudience($test): string
{
    $select = $test->post(route('admin.promocion.push.select'), ['act' => 'up'])->assertOk();

    return pushUniqid($select);
}

it('発行条件選択: el motor común con el título, el menú y el paso siguiente de push', function () {
    $this->get(route('admin.promocion.push.select'))
        ->assertOk()
        ->assertSee('<title>プッシュ通知 発行条件選択 - ', false)
        ->assertSee('■振分配信条件抽出')
        ->assertSee('action="'.route('admin.promocion.push.select').'"', false)
        ->assertSee('$(".ac12").accordion', false);

    $select = $this->post(route('admin.promocion.push.select'), ['act' => 'up'])
        ->assertOk()
        ->assertSee('<span class="text3L attention">3</span>件見つかりました', false)
        ->assertSee('action="'.route('admin.promocion.push.create').'"', false);
    expect(pushUniqid($select))->not->toBe('');

    // URL del menú del legacy
    $this->get('/system/category3/push/push_customer_select.php')->assertRedirect(route('admin.promocion.push.select'));
});

it('新規作成: sin búsqueda vuelve a la portada; con búsqueda pinta el formulario del legacy', function () {
    $this->post(route('admin.promocion.push.create'), [])->assertRedirect('/system/category3/index.php');

    $uniqid = pushAudience($this);
    $this->post(route('admin.promocion.push.create'), ['search_uniqid' => $uniqid, 'act' => 'up'])
        ->assertOk()
        ->assertSee('<title>プッシュ通知 - ', false)
        ->assertSee('■プッシュ通知')
        ->assertSee('value="{名前}"', false)
        ->assertSee('id="scheduled_flg1"', false)
        ->assertSee('name="search_uniqid" value="'.$uniqid.'"', false)
        ->assertSee('/system/category3/push/push_new.js', false)
        ->assertSee(route('legacy.promocion.push.ajax-save'), false)
        ->assertSee('setTextTag();', false)
        ->assertSee('setImgTag();', false);
});

it('ajaxSavePush: push inmediato con texto, imagen con enlace y puntos; imagen en hp/uploads y aviso a g-api', function () {
    $uniqid = pushAudience($this);

    // Subida del bloque de imagen (push_new.js → /system/inc/ajax_picupload.php?up=true)
    $name = $this->post('/system/inc/ajax_picupload.php?up=true', [
        'file' => 'picfile_1', 'ext' => 'png', 'tempfilename' => 'abc123pic1',
        'picfile_1' => UploadedFile::fake()->image('foto.png', 800, 400),
    ])->assertOk()->getContent();
    expect($name)->toBe('abc123pic1.jpg');
    Storage::disk('public')->assertExists(['system/uploads/abc123pic1.jpg', 'system/uploads/abc123pic1th.jpg']);
    $this->get('/system/inc/ajax_picupload.php?f=abc123pic1.jpg')->assertSee('src="/system/uploads/abc123pic1th.jpg?t=', false);
    $this->get('/system/uploads/abc123pic1.jpg')->assertOk();

    $this->post(route('legacy.promocion.push.ajax-save'), [
        'act' => 'sendPush', 'search_uniqid' => $uniqid, 'scheduled_flg' => '0', 'send_hour' => '10', 'send_min' => '00',
        'post_type' => '2', 'post_point' => '50', 'point_expiration_date' => '2026/12/31',
        'title' => '冬のセール', 'contents' => [0 => '{名前}様 セール開催', 1 => 'abc123pic1.jpg', 2 => ''],
        'registeredFlg' => [1 => ''], 'pic_link' => [1 => 'https://example.com/sale'],
        'type_flg' => '', 'cd' => '', 'send_push' => 'send',
    ])->assertOk()->assertExactJson(['status' => 'ok']);

    $push = DB::table('push_master')->where('cd', 700)->first();
    expect($push->title)->toBe('冬のセール')
        ->and(unserialize($push->message))->toBe(['{名前}様 セール開催', 'abc123pic1.jpg'])
        ->and(unserialize($push->pic_link))->toBe([1 => 'https://example.com/sale'])
        ->and($push->post_type)->toBe('2')
        ->and($push->post_point)->toBe(50)
        ->and($push->point_expiration_date)->toBe(Carbon::parse('2026-12-31 23:59:59', 'Asia/Tokyo')->getTimestamp())
        ->and($push->tb_shop_cd)->toBe(-1)
        ->and($push->version)->toBe(2)
        ->and($push->img_flg)->toBe('1')
        ->and($push->disp_flg)->toBe('1')
        ->and($push->wherestr)->toStartWith('WHERE cd IN (')
        ->and((int) $push->touroku_date)->toBe(Carbon::now()->getTimestamp());

    Storage::disk('public')->assertExists(['hp/uploads/c3/push/700/abc123pic1.jpg', 'hp/uploads/c3/push/700/abc123pic1_th.jpg']);
    expect(getimagesize(Storage::disk('public')->path('hp/uploads/c3/push/700/abc123pic1.jpg'))[0])->toBe(500)
        ->and(getimagesize(Storage::disk('public')->path('hp/uploads/c3/push/700/abc123pic1_th.jpg'))[0])->toBe(75);

    Http::assertSent(fn ($r) => str_starts_with($r->url(), 'http://b.g-api.net/api/mpa/pushMessage')
        && $r['domain'] === 'shop.crm-s.net' && $r['type'] === 'push' && (int) $r['cd'] === 700);
    expect(DB::table('push_follow_master')->count())->toBe(0);
});

it('ajaxSavePush: errores del legacy en JSON', function () {
    $uniqid = pushAudience($this);
    $base = ['act' => 'sendPush', 'search_uniqid' => $uniqid, 'send_hour' => '10', 'send_min' => '00', 'contents' => ['hola']];

    $this->post(route('legacy.promocion.push.ajax-save'), $base + ['post_type' => '2', 'post_point' => '0', 'point_expiration_date' => '2026/11/01', 'title' => ''])
        ->assertExactJson(['status' => 'error', 'isFatal' => false,
            'errorMsg' => '通知タイトルを入力してください。<br/>正しい付与ポイント数を入力してください。<br/>正しいポイント獲得の有効期限を選択してください。<br/>']);
    $this->post(route('legacy.promocion.push.ajax-save'), $base + ['post_type' => '1', 'title' => 'x', 'scheduled_flg' => '1', 'send_date' => '2026/11/26'])
        ->assertJson(['status' => 'error', 'errorMsg' => '配信時間は過去です。']);
    $this->post(route('legacy.promocion.push.ajax-save'), ['post_type' => '1', 'title' => 'x'])->assertJson(['errorMsg' => '不正アクセス']);
    $this->post(route('legacy.promocion.push.ajax-save'), $base + ['title' => 'x', 'type_flg' => 'otro'])->assertJson(['errorMsg' => '不正パラメーター']);
    // Sin búsqueda (sesión caducada): el legacy registraba un push sin destinatarios
    $this->post(route('legacy.promocion.push.ajax-save'), ['act' => 'sendPush', 'search_uniqid' => 'nada', 'title' => 'x', 'post_type' => '1'])
        ->assertJson(['status' => 'error', 'isFatal' => true]);

    expect(DB::table('push_master')->count())->toBe(0);
    Http::assertNothingSent();
});

it('テスト配信: busca un socio con la app y le envía el push (WHERE cd = socio)', function () {
    $this->post(route('legacy.promocion.push.ajax-member'), ['w' => 'ヤマダ'])
        ->assertOk()
        ->assertJson(['status' => 'ok', 'data' => [['cd' => 101, 'name' => '山田太郎', 'id' => 'K101']]])
        ->assertJsonCount(1, 'data');
    $this->post(route('legacy.promocion.push.ajax-member'), ['w' => 'nadie'])->assertExactJson(['status' => 'error', 'errorMsg' => 'データがありません。']);

    $uniqid = pushAudience($this);
    $this->post(route('legacy.promocion.push.ajax-save'), [
        'act' => 'sendPush', 'search_uniqid' => $uniqid, 'testMemberCd' => '101', 'scheduled_flg' => '1', 'send_date' => '2026/12/01',
        'send_hour' => '09', 'send_min' => '30', 'post_type' => '1', 'title' => 'テスト', 'contents' => ['本文'],
    ])->assertExactJson(['status' => 'ok']);

    // La prueba siempre es un push inmediato, aunque esté marcado 時間指定
    $push = DB::table('push_master')->where('cd', 700)->first();
    expect($push->wherestr)->toBe('WHERE cd = 101')
        ->and($push->search_display)->toBe('テスト配信')
        ->and($push->post_point)->toBeNull()
        ->and(DB::table('push_follow_master')->count())->toBe(0);
    Http::assertSentCount(1);
});

it('時間指定: va a push_follow_master (scheduled) con su next_send_date y sin aviso inmediato', function () {
    $uniqid = pushAudience($this);
    $this->post(route('legacy.promocion.push.ajax-save'), [
        'act' => 'sendPush', 'search_uniqid' => $uniqid, 'scheduled_flg' => '1', 'send_date' => '2026/12/01', 'send_hour' => '09', 'send_min' => '30',
        'post_type' => '2', 'post_point' => '10', 'point_expiration_date' => '2026/12/31', 'title' => '予定', 'contents' => ['本文'],
    ])->assertExactJson(['status' => 'ok']);

    $follow = DB::table('push_follow_master')->where('cd', 50)->first();
    expect($follow->type_flg)->toBe('scheduled')
        ->and($follow->send_parameter)->toBe('send_date=2026/12/01,point_expiration_date=2026/12/31')
        ->and($follow->send_time)->toBe('09:30')
        ->and($follow->next_send_date)->toBe(Carbon::parse('2026-12-01 09:30', 'Asia/Tokyo')->getTimestamp())
        ->and($follow->post_point)->toBe(10)
        ->and($follow->tb_shop_cd)->toBe(-1)
        ->and($follow->disp_flg)->toBe('1');
    expect(DB::table('push_master')->count())->toBe(0);
    Http::assertNothingSent();
});

it('フォロー: llega de follow_new_confirm (POST a push_new.php) con step*, y se registra con su send_parameter', function () {
    $uniqid = app(\App\Services\Promotions\MailAudience::class)->storeRaw("WHERE disp_flg = '1' AND touroku_kubun = '2' AND cd IN (101)", '', '最終来店日から3日後');

    // El POST a la URL del legacy se reenvía con 307 (conserva el cuerpo)
    $this->post('/system/category3/push/push_new.php', ['search_uniqid' => $uniqid])
        ->assertStatus(307)->assertRedirect(route('admin.promocion.push.create'));

    $this->post(route('admin.promocion.push.create'), ['search_uniqid' => $uniqid, 'type_flg' => 'follow', 'shop_cd' => '-1', 'step1' => '1',
        'step2_1' => '3', 'step2_jikan' => '11', 'step2_fun' => '15'])
        ->assertOk()
        ->assertSee('最終来店日から3日後')
        ->assertSee('name="sendParameter" value="-1&lt;&gt;1&lt;&gt;3"', false)
        ->assertSee('<option value="11" selected="selected">11時</option>', false)
        ->assertSee('<option value="15" selected="selected">15分</option>', false)
        ->assertSee('id="point_expiration_days"', false)
        ->assertDontSee('id="scheduled_flg1"', false);

    $this->post(route('legacy.promocion.push.ajax-save'), [
        'act' => 'sendPush', 'search_uniqid' => $uniqid, 'type_flg' => 'follow', 'sendParameter' => '-1<>1<>3', 'send_hour' => '11', 'send_min' => '15',
        'post_type' => '2', 'post_point' => '5', 'point_expiration_days' => '7', 'title' => 'またのお越しを', 'contents' => ['ありがとうございました'],
    ])->assertExactJson(['status' => 'ok']);

    $follow = DB::table('push_follow_master')->where('cd', 50)->first();
    expect($follow->type_flg)->toBe('follow')
        ->and($follow->send_parameter)->toBe('-1<>1<>3')
        ->and($follow->wherestr)->toBe("WHERE disp_flg = '1' AND touroku_kubun = '2' AND cd IN (101)")
        ->and($follow->point_expiration_days)->toBe(7)
        ->and($follow->next_send_date)->toBe(Carbon::parse('2026-11-27 11:15', 'Asia/Tokyo')->getTimestamp());
});

it('修正: abre la regla con sus bloques; guardar actualiza sin tocar los destinatarios; la prueba copia las imágenes', function () {
    pushFollow(['cd' => 60, 'type_flg' => 'auto', 'send_parameter' => '2<>1,3', 'send_time' => '08:00', 'post_point' => 3, 'point_expiration_days' => 2,
        'message' => serialize(['línea 1', 'foto.jpg']), 'pic_link' => serialize([1 => 'https://example.com'])]);
    Storage::disk('public')->put('hp/uploads/c3/push_follow/60/foto.jpg', 'x');

    $this->get(route('admin.promocion.push.create', ['type_flg' => 'auto', 'cd' => 60]))
        ->assertOk()
        ->assertSee('配信曜日：月曜日 水曜日')
        ->assertSee('setTextTag("línea 1");', false)
        ->assertSee('setImgTag("foto.jpg", "1");', false)
        ->assertSee('value="予定解除する"', false)
        ->assertSee('<option value="08" selected="selected">08時</option>', false);
    // Regla inexistente: a su lista
    $this->get(route('admin.promocion.push.create', ['type_flg' => 'auto', 'cd' => 999]))
        ->assertRedirect(route('admin.promocion.push.follow-list', ['type_flg' => 'auto']));

    $this->post(route('legacy.promocion.push.ajax-save'), [
        'act' => 'sendPush', 'type_flg' => 'auto', 'cd' => '60', 'sendParameter' => '2<>1,3', 'send_hour' => '09', 'send_min' => '00',
        'post_type' => '1', 'title' => '新タイトル', 'contents' => ['línea 2', 'foto.jpg'], 'registeredFlg' => [1 => '1'], 'send_push' => 'send',
    ])->assertExactJson(['status' => 'ok']);
    $follow = DB::table('push_follow_master')->where('cd', 60)->first();
    expect($follow->title)->toBe('新タイトル')
        ->and($follow->send_time)->toBe('09:00')
        ->and($follow->post_point)->toBeNull()
        ->and($follow->wherestr)->toBe("WHERE disp_flg = '1'")
        ->and(unserialize($follow->message))->toBe(['línea 2', 'foto.jpg']);

    // テスト配信 de la regla: push normal con la imagen registrada copiada a push/<cd>
    $this->post(route('legacy.promocion.push.ajax-save'), [
        'act' => 'sendPush', 'type_flg' => 'auto', 'cd' => '60', 'testMemberCd' => '102', 'send_hour' => '09', 'send_min' => '00',
        'post_type' => '2', 'post_point' => '3', 'point_expiration_days' => '2', 'title' => 'prueba', 'contents' => ['foto.jpg'], 'registeredFlg' => ['1'],
    ])->assertExactJson(['status' => 'ok']);
    $push = DB::table('push_master')->where('cd', 700)->first();
    expect($push->wherestr)->toBe('WHERE cd = 102')
        ->and($push->type_flg)->toBeNull()
        ->and($push->point_expiration_date)->toBe(Carbon::parse('2026-11-29 23:59:59', 'Asia/Tokyo')->getTimestamp());
    Storage::disk('public')->assertExists('hp/uploads/c3/push/700/foto.jpg');
});

it('ajaxChangeStatus: 停止, 起動 (recalcula el próximo envío) y 解除', function () {
    pushFollow(['cd' => 61, 'type_flg' => 'follow', 'send_time' => '09:00', 'next_send_date' => 123]);

    $this->post(route('legacy.promocion.push.ajax-status'), ['cd' => '61', 'st' => '停止'])->assertExactJson(['status' => 'ok']);
    expect(DB::table('push_follow_master')->where('cd', 61)->first())->disp_flg->toBe('7')->next_send_date->toBeNull();

    $this->post(route('legacy.promocion.push.ajax-status'), ['cd' => '61', 'st' => '起動'])->assertExactJson(['status' => 'ok']);
    expect(DB::table('push_follow_master')->where('cd', 61)->first())->disp_flg->toBe('1')
        ->next_send_date->toBe(Carbon::parse('2026-11-28 09:00', 'Asia/Tokyo')->getTimestamp());

    $this->post(route('legacy.promocion.push.ajax-status'), ['cd' => '61', 'st' => '解除'])->assertExactJson(['status' => 'ok']);
    expect(DB::table('push_follow_master')->where('cd', 61)->value('disp_flg'))->toBe('9');

    $this->post(route('legacy.promocion.push.ajax-status'), ['cd' => '61', 'st' => '起動'])->assertExactJson(['status' => 'error', 'errorMsg' => 'データがありません']);
    $this->post(route('legacy.promocion.push.ajax-status'), ['cd' => 'x', 'st' => '起動'])->assertJson(['errorMsg' => '不正パラメーター']);
});

it('完了: mensaje y vuelta a la lista de su tipo', function () {
    $this->get(route('admin.promocion.push.complete', ['type_flg' => 'follow']))
        ->assertOk()
        ->assertSee('完了しました。')
        ->assertSee('URL='.route('admin.promocion.push.follow-list', ['type_flg' => 'follow']), false);
});
