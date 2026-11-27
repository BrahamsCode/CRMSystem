<?php

use App\Mail\Promotions\PromotionMail;
use App\Services\Promotions\MailRenderer;
use App\Services\Promotions\MailSender;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

/*
 * M2: motor de envío (cMailsoushin::sendMailFromMailmagMaster) y promocion:send-mail (mail_sche_send.php).
 */
beforeEach(function () {
    Mail::fake();
    Http::fake();
    $this->travelTo(Carbon::parse('2026-11-26 10:00:00', 'Asia/Tokyo'));

    DB::table('admin_master')->insert(['cd' => 1, 'domain' => 'shop.crm-s.net', 'mail_footer' => '', 'mail_send_filter' => '', 'mailmag_sended_log' => '1<>1', 'system_mail' => 'eigyo@example.com', 'application_use_flg' => 1, 'push_api_host' => '1']);
    DB::table('tb_office')->insert(['cd' => 1, 'kaishamei' => '本社', 'email' => 'office@example.com', 'tel' => '06-0000-0000']);
    DB::table('tb_shop')->insert(['cd' => 1, 'kaishamei' => '本店', 'email' => 'shop@example.com', 'tel' => '06-1111-1111', 'add' => '大阪市', 'disp_flg' => '1']);
    DB::table('tb_customer')->insert([
        ['cd' => 1, 'id' => 'A001', 'name' => '山田 太郎', 'email1' => 'taro@example.com', 'mailmaga_flg' => '1', 'mailmaga_email_type' => 'パソコン', 'login_id' => 'taro', 'login_pass' => 'pw', 'kojin_birth' => '1990/04/05', 'touroku_date' => '2', 'disp_flg' => '1', 'error_send_cnt' => 0],
        ['cd' => 2, 'id' => 'A002', 'name' => '佐藤 花子', 'email1' => 'hanako@example.com', 'mailmaga_flg' => '1', 'mailmaga_email_type' => 'DoCoMo', 'login_id' => '', 'login_pass' => '', 'kojin_birth' => '', 'touroku_date' => '1', 'disp_flg' => '1', 'error_send_cnt' => 4],
        ['cd' => 3, 'id' => 'A003', 'name' => '拒否', 'email1' => 'no@example.com', 'mailmaga_flg' => '9', 'mailmaga_email_type' => 'パソコン', 'login_id' => '', 'login_pass' => '', 'kojin_birth' => '', 'touroku_date' => '3', 'disp_flg' => '1', 'error_send_cnt' => 0],
        ['cd' => 4, 'id' => 'A004', 'name' => 'メールなし', 'email1' => '', 'mailmaga_flg' => '1', 'mailmaga_email_type' => '', 'login_id' => '', 'login_pass' => '', 'kojin_birth' => '', 'touroku_date' => '4', 'disp_flg' => '1', 'error_send_cnt' => 0],
    ]);
});

function mailmag(array $values = []): int
{
    $row = array_merge([
        'cd' => 100, '店id' => '1', '登録者' => '1', 'タイトル' => '{名前}様へ', 'pc内容' => "{名前}様\n誕生日{誕生月日}\nID:{ID}",
        '配信日' => '2026/11/26', '配信日時' => '2026/11/26 10:00', '配信状況' => '1', '画像有無' => '0',
        '振分配信' => "WHERE disp_flg = '1' and mailmaga_flg = '1'", 'type_flg' => '', 'search_display' => '全員',
    ], $values);
    DB::table('mailmag_master')->insert($row);

    return $row['cd'];
}

describe('MailSender', function () {
    it('envía a los clientes que aceptan correos, con etiquetas, pie y enlace a Mi página', function () {
        $counts = app(MailSender::class)->send(mailmag());

        expect($counts)->toMatchArray(['total' => 2, 'pc' => 1, 'docomo' => 1]);

        Mail::assertSent(PromotionMail::class, fn (PromotionMail $m) => $m->hasTo('taro@example.com')
            && $m->mailSubject === '山田 太郎様へ'
            && str_contains($m->body, "山田 太郎様\n誕生日4月5日\nID:A001")
            && str_contains($m->body, 'http://shop.crm-s.net/hp/user/login.php?login_id='.md5('taro').'&login_pass='.md5('pw').'&mmcd='.md5('100'))
            && str_contains($m->body, "本店\n大阪市\nTEL：06-1111-1111\nE-Mail：shop@example.com")
            && $m->fromAddress === 'mail_mag@shop.crm-s.net' && $m->fromName === '本店' && ! $m->deco);
        Mail::assertSent(PromotionMail::class, fn (PromotionMail $m) => $m->hasTo('hanako@example.com') && ! str_contains($m->body, 'login.php'));
        Mail::assertNotSent(PromotionMail::class, fn (PromotionMail $m) => $m->hasTo('no@example.com'));
        // Copia a la empresa sin sustituir
        Mail::assertSent(PromotionMail::class, fn (PromotionMail $m) => $m->hasTo('eigyo@example.com') && $m->mailSubject === '{名前}様へ');

        $row = DB::table('mailmag_master')->where('cd', 100)->first();
        expect($row->{'配信状況'})->toBe('2')->and($row->{'配信総数'})->toBe(2)->and($row->{'pc配信数'})->toBe(1)
            ->and($row->send_count)->toBe(1)->and($row->last_send_date)->toBe(Carbon::now()->getTimestamp());

        // Registro de enviados hasta el tope (1)
        expect(DB::table('mailmag_sended_log')->count())->toBe(1);

        Http::assertSent(fn ($request) => $request->url() === 'http://b.g-api.net/api/mpa/pushMessage?domain=shop.crm-s.net&type=mailmaga&cd=100');
    });

    it('sin filtro de envío también llega a quien no acepta correos', function () {
        DB::table('admin_master')->update(['mail_send_filter' => '1']);

        expect(app(MailSender::class)->send(mailmag()))->toMatchArray(['total' => 3]);
    });

    it('entrega el cupón a cada destinatario con vencimiento desde el fin del día', function () {
        DB::table('hatuban')->insert(['koumoku' => 'tb_mycoupon', 'kazu' => 50]);
        DB::table('tb_mycoupon_master')->insert(['cd' => 7, 'tb_shop_cd' => 1, 'coupon_kubun' => '1', 'limit_flg' => '1', 'disp_flg' => '1']);

        app(MailSender::class)->send(mailmag(['type_flg' => 'coupon', 'type_data_cd' => 7]));

        $coupons = DB::table('tb_mycoupon')->orderBy('cd')->get();
        expect($coupons)->toHaveCount(2)
            ->and($coupons[0]->tb_mycoupon_master_cd)->toBe(7)
            ->and((int) $coupons[0]->time_limit)->toBe(Carbon::parse('2026-12-03 23:59:59', 'Asia/Tokyo')->getTimestamp());
    });

    it('envía la デコメール en HTML con el pie HTML y sin aviso al push', function () {
        app(MailSender::class)->send(mailmag(['type_flg' => 'deco', 'pc内容' => '<p style="text-align: center;">{名前}様</p><span style="color: rgb(255, 0, 0);">赤</span>']));

        Mail::assertSent(PromotionMail::class, fn (PromotionMail $m) => $m->hasTo('taro@example.com') && $m->deco
            && str_contains($m->body, '<div align="center">山田 太郎様</div><font color="#FF0000">赤</font>')
            && str_contains($m->body, '<a href="tel:06-1111-1111">'));
        Http::assertNothingSent();
    });

    it('cuenta los errores y da de baja al quinto', function () {
        $sender = new class(...array_map(fn ($c) => app($c), [MailRenderer::class, App\Services\Promotions\SystemDomain::class, App\Services\Promotions\CouponService::class, App\Services\Promotions\PosGateway::class, App\Services\Promotions\PushTrigger::class])) extends MailSender
        {
            protected function deliver(PromotionMail $mail, string $to): bool
            {
                return false;
            }
        };

        $sender->send(mailmag());

        expect(DB::table('tb_customer')->where('cd', 1)->first())->error_send_cnt->toBe(1)->mailmaga_flg->toBe('1')
            ->and(DB::table('tb_customer')->where('cd', 2)->first())->error_send_cnt->toBe(5)->mailmaga_flg->toBe('9');
        expect(DB::table('mailmag_sended_log')->count())->toBe(0);
    });

    it('sin destinatarios solo suma el envío y avisa al push', function () {
        expect(app(MailSender::class)->send(mailmag(['振分配信' => "WHERE cd = -1"])))->toBeFalse();

        expect(DB::table('mailmag_master')->where('cd', 100)->value('send_count'))->toBe(1);
        Mail::assertNothingSent();
        Http::assertSentCount(1);
    });
});

describe('MailRenderer', function () {
    it('sustituye dirección, puntos, rangos, campos personalizados y fechas relativas', function () {
        DB::table('tb_customer_point')->insert(['cd' => 1, 'tb_customer_cd' => 1, 'service_point' => 120]);
        DB::table('tb_raiten_rank_master')->insert(['cd' => 2, 'name' => 'ゴールド', 'disp_flg' => '1']);
        DB::table('tb_search_cate')->insert(['cd' => 1, 'record_type' => null, 'disp_flg' => '1', 'hyouji_flg' => '1']);
        DB::table('tb_search_form')->insert(['cd' => 9, 'tb_search_cate_cd' => 1, 'name' => '趣味', 'form_type' => 'チェックボックス', 'mail_word_flg' => '1', 'disp_flg' => '1', 'hyouji_flg' => '1', 'rank' => 1]);
        DB::table('tb_customer_sub')->insert(['tb_customer_cd' => 1, 'tb_search_form_cd' => 9, 'text_data' => '釣り<>読書', 'disp_flg' => '1']);

        $renderer = app(MailRenderer::class)->forSender('-1');
        [$subject, $body] = $renderer->replace('{苗字}さん', '{住所}/{ポイント数}/{来店回数ランク}/{趣味}/{日後:7}/{予約時間}/{苗字}', [
            'cd' => 1, 'name' => '山田 太郎', 'zip' => '530-0001', 'pref' => '大阪府', 'add' => '北区', 'raiten_rank_now' => '2', 'start_time' => 90000,
        ], ['配信日' => '2026/11/26']);

        expect($subject)->toBe('山田さん')
            ->and($body)->toBe('〒530-0001 大阪府北区/120/ゴールド/釣り　読書/2026/12/03/25:00/山田')
            ->and($renderer->senderName())->toBe('本社');
    });
});

describe('promocion:send-mail', function () {
    it('envía los únicos que ya tocan, borra su programación y deja los futuros', function () {
        mailmag(['cd' => 100, '配信日時' => '2026/11/26 09:59']);
        mailmag(['cd' => 101, '配信日時' => '2026/11/26 10:30']);
        DB::table('send_schedule')->insert([
            ['cd' => 1, 'mag_cd' => 100, '配信時間' => '2026/11/26 09:59', 'flg' => '1', '店id' => '1'],
            ['cd' => 2, 'mag_cd' => 101, '配信時間' => '2026/11/26 10:30', 'flg' => '1', '店id' => '1'],
        ]);

        $this->artisan('promocion:send-mail')->assertSuccessful();

        expect(DB::table('send_schedule')->pluck('mag_cd')->all())->toBe([101])
            ->and(DB::table('mailmag_master')->where('cd', 100)->value('配信状況'))->toBe('2')
            ->and(DB::table('mailmag_master')->where('cd', 101)->value('配信状況'))->toBe('1');

        // El siguiente minuto no lo repite
        $this->artisan('promocion:send-mail');
        expect(DB::table('mailmag_master')->where('cd', 100)->value('send_count'))->toBe(1);
    });

    it('envía los automáticos en su hora y día sin cerrar la programación', function () {
        mailmag(['cd' => 102, 'type_flg' => 'auto', '配信日時' => '10:00', 'send_parameter' => '2<>4']);
        DB::table('send_schedule')->insert(['cd' => 3, 'mag_cd' => 102, '配信時間' => '10:00', 'flg' => '1', '店id' => '1']);

        $this->artisan('promocion:send-mail')->assertSuccessful();

        expect(DB::table('mailmag_master')->where('cd', 102)->first())->send_count->toBe(1)->{'配信状況'}->toBe('1')
            ->and(DB::table('send_schedule')->count())->toBe(1);
    });
});
