<?php

use App\Jobs\Promotions\SendMailmagJob;
use App\Mail\Promotions\PromotionMail;
use App\Services\Promotions\MailSender;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

require_once __DIR__.'/anketo_helpers.php';

/*
 * F6: envío por correo con el motor de S3.
 * アンケート発行 (anketo): 発行条件選択 → 内容設定 → 確認 → 登録 (type_flg = anketo y encuesta 発行済) → MailSender.
 * 返信メール (anketo2): 回答一覧 → 内容設定 → 確認 → 登録 a los socios marcados.
 */
beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-11-27 10:00:00', 'Asia/Tokyo'));
    s4Login();
    Mail::fake();
    DB::table('admin_master')->where('cd', 1)->update(['domain' => 'shop.crm-s.net', 'mail_footer' => '', 'mail_send_filter' => '1', 'application_use_flg' => 0]);
    DB::table('hatuban')->insert([['koumoku' => 'mailmag', 'kazu' => 500], ['koumoku' => 'send_schedule', 'kazu' => 70]]);
});

function anketoUniqid($response): string
{
    preg_match('/name="search_uniqid" value="(\w{16})"/', $response->getContent(), $m);

    return $m[1] ?? '';
}

it('アンケート発行: del 発行条件選択 al registro con el motor de S3', function () {
    Bus::fake();
    anketoSeed('anketo');
    DB::table('tb_customer')->where('cd', 101)->update(['login_id' => 'taro', 'login_pass' => 'pw']);
    DB::table('tb_mycoupon_master')->insert(['cd' => 7, 'tb_shop_cd' => -1, 'title' => '回答特典', 'comment' => '', 'coupon_kubun' => '3', 'disp_flg' => '1']);

    $this->post(route('admin.promocion.anketo.mail.select'), ['cd' => 10, 'hakkou_cd' => 10])
        ->assertOk()
        ->assertSee('<title>アンケート 発行条件選択 - ', false)
        ->assertSee('■振分配信条件抽出')
        ->assertSee('action="'.route('admin.promocion.anketo.mail.select').'"', false)
        ->assertSee('name="hakkou_cd" value="10"', false)
        ->assertSee('$(".ac8").accordion', false);

    $select = $this->post(route('admin.promocion.anketo.mail.select'), ['act' => 'up', 'hakkou_cd' => 10])
        ->assertOk()
        ->assertSee('<span class="text3L attention">2</span>件見つかりました', false)
        ->assertSee('action="'.route('admin.promocion.anketo.mail.create').'"', false);
    $uniqid = anketoUniqid($select);
    expect($uniqid)->not->toBe('');

    $this->post(route('admin.promocion.anketo.mail.create'), ['search_uniqid' => $uniqid, 'hakkou_cd' => 10, 'act' => 'up'])
        ->assertOk()
        ->assertSee('■メール内容設定')
        ->assertSee('action="'.route('admin.promocion.anketo.mail.confirm').'"', false)
        ->assertSee('name="hakkou_cd" value="10"', false)
        ->assertSee('name="mail_type" value="anketo"', false);

    $form = ['search_uniqid' => $uniqid, 'hakkou_cd' => 10, 'title' => 'アンケートのお願い', 'naiyou' => '{名前}様 ご協力ください', 'tourokusya' => '1',
        'bikou' => '', 'reigai_kaiin_cds' => '', 'send_flg' => '1', 'get_coupon_cd' => '7', 'get_coupon_text' => '回答特典'];
    $this->post(route('admin.promocion.anketo.mail.confirm'), $form)
        ->assertOk()
        ->assertSee('以下の内容で配信してもよろしいですか？')
        ->assertSee('action="'.route('admin.promocion.anketo.mail.store').'"', false)
        ->assertSee('action="'.route('admin.promocion.anketo.mail.create').'"', false)
        ->assertSee('name="testType" id="testType" value="anketo"', false);

    $this->post(route('admin.promocion.anketo.mail.store'), $form + ['jikan' => '10', 'fun' => '00', 'mail_send_verify' => '55'])
        ->assertOk()
        ->assertSee('以下の内容で配信いたしました。')
        ->assertSee('総数：2');

    $mail = DB::table('mailmag_master')->where('cd', 500)->first();
    $cate = DB::table('tb_c3_anketo_cate')->where('cd', 10)->first();
    expect($mail->type_flg)->toBe('anketo')
        ->and($mail->type_data_cd)->toBe(10)
        ->and($mail->{'配信状況'})->toBe('2')
        ->and($cate->hyouji_flg)->toBe('1')
        ->and($cate->attached_cd)->toBe(7)
        // MailRegistrar (S3) usa time() como el legacy: el reloj congelado de Carbon no le afecta
        ->and($cate->hakkou_date)->toMatch('/^\d{10}$/');
    Bus::assertDispatched(SendMailmagJob::class, fn ($job) => $job->mailmagCd === 500);

    // Ya en 過去アンケート配信
    $this->get(route('admin.promocion.anketo.index', ['which' => 'sended']))->assertSee('満足度調査')->assertSee('2 / 2 <small>(人)</small>', false);
    $this->get(route('admin.promocion.anketo.forms.index', ['pcd' => 10, 'which' => 'sended']))
        ->assertOk()
        ->assertSee('アンケートのお願い')
        ->assertSee('回答特典')
        ->assertSee('このアンケートメールのクリックカウンタ')
        ->assertSee('このアンケートを複製する');

    // El motor de S3 envía con el enlace de la encuesta
    app(MailSender::class)->send(500);
    Mail::assertSent(PromotionMail::class, fn ($m) => $m->hasTo('taro@example.com') && str_contains($m->body, '山田太郎様 ご協力ください')
        && str_contains($m->body, '※アンケートフォームはこちら。'));
});

it('アンケート発行: sin búsqueda vuelve atrás como el motor', function () {
    $this->post(route('admin.promocion.anketo.mail.create'), ['search_uniqid' => 'nope'])->assertRedirect();
    $this->post(route('admin.promocion.anketo.mail.confirm'), ['search_uniqid' => 'nope'])->assertRedirect();
});

it('返信メール (anketo2): de 回答一覧 al registro para los socios marcados', function () {
    Bus::fake();
    anketoSeed('anketo2');

    $create = $this->post(route('admin.promocion.anketo2.reply.create'), ['pcd' => 10, 'page' => '', 'kensu' => '', 'which' => 'sended',
        'reply_mail' => ['301' => 'taro@example.com', '303' => 'taro@example.com']])
        ->assertOk()
        ->assertSee('■メール内容設定')
        ->assertSee('メールアドレス1：taro@example.com')
        ->assertSee('action="'.route('admin.promocion.anketo2.reply.confirm').'"', false)
        ->assertSee('$(".ac109").accordion', false);
    $uniqid = anketoUniqid($create);

    $form = ['search_uniqid' => $uniqid, 'pcd' => 10, 'title' => 'ご回答ありがとうございます', 'naiyou' => '{名前}様', 'tourokusya' => '1', 'bikou' => '',
        'reigai_kaiin_cds' => '', 'send_flg' => '1'];
    $this->post(route('admin.promocion.anketo2.reply.confirm'), $form)
        ->assertOk()
        ->assertSee('山田太郎様')
        ->assertSee('action="'.route('admin.promocion.anketo2.reply.store').'"', false)
        ->assertSee('action="'.route('admin.promocion.anketo2.reply.select').'"', false);

    $this->post(route('admin.promocion.anketo2.reply.store'), $form + ['jikan' => '10', 'fun' => '00', 'mail_send_verify' => '77'])
        ->assertOk()
        ->assertSee('以下の内容で配信いたしました。')
        ->assertSee('総数：1');

    $mail = DB::table('mailmag_master')->where('cd', 500)->first();
    expect($mail->type_flg)->toBe('')
        ->and($mail->{'振分配信'})->toBe("WHERE disp_flg = '1' AND touroku_kubun = '2' AND cd IN (101)")
        ->and($mail->search_display)->toBe('メールアドレス1：taro@example.com');

    app(MailSender::class)->send(500);
    Mail::assertSent(PromotionMail::class, fn ($m) => $m->hasTo('taro@example.com'));
    Mail::assertNotSent(PromotionMail::class, fn ($m) => $m->hasTo('hanako@example.com'));
});

it('返信メール: «条件を選び直す» vuelve a 回答一覧 con las casillas marcadas', function () {
    anketoSeed('anketo2');
    $this->post(route('admin.promocion.anketo2.reply.create'), ['pcd' => 10, 'reply_mail' => ['306' => 'hanako@example.com']])->assertOk();

    $response = $this->post(route('admin.promocion.anketo2.reply.select'), ['pcd' => 10, 'kensu2' => '20']);
    $response->assertRedirect(route('admin.promocion.anketo2.answers', ['pcd' => 10, 'kensu2' => '20', 'reply_mail' => ['306' => 'hanako@example.com']]));

    $this->get($response->headers->get('Location'))
        ->assertOk()
        ->assertSee('name="reply_mail[306]" value="hanako@example.com" checked="checked"', false);
});
