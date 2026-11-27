<?php

use App\Mail\Senalizacion\ContactReceivedMail;
use App\Models\Senalizacion\SignageWebContact;
use App\Models\Senalizacion\SignageWebCustomer;
use Illuminate\Support\Facades\Mail;

require_once __DIR__.'/helpers.php';

/* GoTel (PROVISIONAL): signage/mail/index.php お問い合わせ y signage/pdf/*.php 請求書/領収書 */

beforeEach(function () {
    snSignageDatabase();
    s4Login();
});

it('お問い合わせ: asunto por defecto sin cliente y 【内線追加】 con el cliente, en la URL del legacy', function () {
    $this->get('/signage/mail/index.php')->assertOk()
        ->assertSee('<h1>GoTel管理</h1>', false)
        ->assertSee('value="【お問い合わせ】"', false)
        ->assertSee('name="confirm" value="1"', false);

    $customer = SignageWebCustomer::create(['name' => '大阪商事', 'flg' => 1]);
    $this->get('/signage/mail/index.php?customer_cd='.$customer->cd.'&app_num=3')->assertOk()
        ->assertSee('【内線追加】顧客番号:'.$customer->cd.'の内線追加に関しまして')
        ->assertSee('value="大阪商事"', false)
        ->assertSee("顧客番号 　　　: {$customer->cd}\n問い合わせ内容 : \n3台の内線を追加ください。", false);
});

it('お問い合わせ: 確認 escapa el 本文 y 送信 lo guarda en contact sin correo si no hay destinatario', function () {
    Mail::fake();
    config(['senalizacion.contact_notify' => '']);

    $this->post(route('admin.senalizacion.contact'), ['subject' => '件名', 'name' => '山田', 'message' => "1行目\n<b>2</b>", 'confirm' => 1])
        ->assertOk()->assertSee('1行目<br />'."\n".'&lt;b&gt;2&lt;/b&gt;', false)->assertSee('name="send" value="1"', false);

    $this->post(route('admin.senalizacion.contact'), ['subject' => '件名', 'name' => '山田', 'message' => '本文', 'send' => 1])
        ->assertOk()->assertSee('お問い合わせを受け付けました。');

    $row = SignageWebContact::query()->sole();
    expect($row->only(['subject', 'name', 'message']))->toBe(['subject' => '件名', 'name' => '山田', 'message' => '本文'])
        ->and($row->insert_date)->not->toBeNull();
    Mail::assertNothingOutgoing();
});

it('お問い合わせ: con destinatario configurado encola ContactReceivedMail', function () {
    Mail::fake();
    config(['senalizacion.contact_notify' => 'soporte@example.test']);

    $this->post('/signage/mail/index.php', ['subject' => '件名', 'name' => '山田', 'message' => '本文', 'send' => 1])->assertOk();

    Mail::assertQueued(ContactReceivedMail::class, fn ($m) => $m->hasTo('soporte@example.test') && $m->contact->subject === '件名');
});

it('請求書 y 領収書: HTML con ?mode y PDF descargable sin él, con o sin cliente', function () {
    $customer = SignageWebCustomer::create(['name' => '大阪商事', 'flg' => 1]);

    $this->get('/signage/pdf/invoice.php?cd='.$customer->cd.'&mode=edit')->assertOk()
        ->assertSee('<h1>請求書</h1>', false)
        ->assertSee(sprintf('%04d', $customer->cd))
        ->assertSee('大阪商事')
        ->assertSee('/signage/css/pdf.css', false);
    $this->get(route('admin.senalizacion.receipt', ['cd' => 999, 'mode' => 'edit']))->assertOk()
        ->assertSee('<h1>領収書</h1>', false)->assertSee('0999');

    $pdf = $this->get('/signage/pdf/receipt.php?cd='.$customer->cd);
    $pdf->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect($pdf->headers->get('Content-Disposition'))->toMatch('/attachment; filename=receipt_\d{14}\.pdf/')
        ->and($pdf->streamedContent())->toStartWith('%PDF');
});
