<?php

use App\Models\Promotions;
use App\Services\Nucleo\Hatuban;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Modelos de S3 sobre las tablas del legacy (esquema database/schema/pgsql-schema.sql)

dataset('promocionModels', [
    Promotions\MailmagMaster::class, Promotions\SendSchedule::class, Promotions\MailmagAnalyze::class,
    Promotions\MailTemplate::class, Promotions\MailTemplateCategory::class, Promotions\TestMailAddress::class,
    Promotions\PushMaster::class, Promotions\PushFollowMaster::class, Promotions\PushNotification::class,
    Promotions\DigitalSignageNews::class,
    Promotions\AnketoCate::class, Promotions\AnketoForm::class, Promotions\AnketoSub::class,
    Promotions\Anketo2Cate::class, Promotions\Anketo2Form::class, Promotions\Anketo2Sub::class,
    Promotions\MycouponMaster::class, Promotions\Mycoupon::class, Promotions\MycouponUsed::class,
    Promotions\PointRule::class, Promotions\PointLog::class, Promotions\CustomerPoint::class, Promotions\PointShikkouAlert::class,
    Promotions\KingakuRankMaster::class, Promotions\RaitenRankMaster::class, Promotions\CustomerGroup::class,
    Promotions\StampConfig::class, Promotions\StampRule::class, Promotions\StampPattern::class, Promotions\StampLog::class,
    Promotions\StampOshiraseCoupon::class, Promotions\KujibikiCount::class, Promotions\KujibikiLog::class,
    Promotions\TouchMode::class, Promotions\SmatopiMessage::class,
]);

it('lee su tabla del legacy por cd', function (string $class) {
    $model = new $class;
    $row = ['cd' => 7];
    if (Schema::hasColumn($model->getTable(), 'disp_flg')) {
        $row['disp_flg'] = '1';
    }
    DB::table($model->getTable())->insert($row);

    expect($class::find(7))->not->toBeNull()->and($class::find(7)->cd)->toBe(7);
})->with('promocionModels');

it('lee mailmag_sended_log, que no tiene clave propia', function () {
    DB::table('mailmag_master')->insert(['cd' => 3, 'タイトル' => 'お知らせ', 'type_flg' => '', '配信状況' => '2']);
    DB::table('mailmag_sended_log')->insert(['mailmag_master_cd' => 3, 'tb_customer_cd' => 11, 'email' => 'a@example.com']);

    $mail = Promotions\MailmagMaster::find(3);

    expect($mail->getAttribute('タイトル'))->toBe('お知らせ')
        ->and($mail->sendedLogs)->toHaveCount(1)
        ->and($mail->sendedLogs->first()->email)->toBe('a@example.com')
        ->and($mail->isRecurring())->toBeFalse();
});

it('relaciona cupón, entregados, usados y envíos', function () {
    DB::table('tb_mycoupon_master')->insert(['cd' => 5, 'title' => '10%OFF', 'disp_flg' => '1']);
    DB::table('tb_mycoupon')->insert(['cd' => 50, 'tb_mycoupon_master_cd' => 5, 'tb_customer_cd' => 1]);
    DB::table('tb_mycoupon_used')->insert(['cd' => 51, 'tb_mycoupon_master_cd' => 5, 'tb_customer_cd' => 2]);
    DB::table('mailmag_master')->insert(['cd' => 9, 'type_flg' => 'coupon', 'type_data_cd' => 5]);

    $coupon = Promotions\MycouponMaster::find(5);

    expect($coupon->issued->pluck('cd')->all())->toBe([50])
        ->and($coupon->used->pluck('cd')->all())->toBe([51])
        ->and(Promotions\MailmagMaster::find(9)->coupon->title)->toBe('10%OFF')
        ->and(Promotions\Mycoupon::find(50)->master->cd)->toBe(5);
});

it('relaciona las dos familias de encuestas con sus propias tablas', function () {
    DB::table('tb_c3_anketo_cate')->insert(['cd' => 1, 'name' => 'A', 'disp_flg' => '1']);
    DB::table('tb_c3_anketo_form')->insert(['cd' => 10, 'tb_c3_anketo_cate_cd' => 1, 'name' => 'Q1', 'disp_flg' => '1', 'rank' => 1]);
    DB::table('tb_c3_anketo2_cate')->insert(['cd' => 1, 'name' => 'B', 'disp_flg' => '1']);
    DB::table('tb_c3_anketo2_form')->insert(['cd' => 20, 'tb_c3_anketo_cate_cd' => 1, 'name' => 'Q2', 'disp_flg' => '1']);
    DB::table('tb_c3_anketo2_sub')->insert(['cd' => 30, 'tb_c3_anketo_cate_cd' => 1, 'tb_c3_anketo_form_cd' => 20, 'text_data' => 'はい', 'disp_flg' => '1']);

    expect(Promotions\AnketoCate::find(1)->forms->pluck('name')->all())->toBe(['Q1'])
        ->and(Promotions\Anketo2Cate::find(1)->forms->pluck('name')->all())->toBe(['Q2'])
        ->and(Promotions\Anketo2Form::find(20)->answers->first()->text_data)->toBe('はい')
        ->and(Promotions\Anketo2Sub::find(30)->survey->name)->toBe('B');
});

it('oculta disp_flg = 9 y borra con el borrado lógico del legacy', function () {
    DB::table('tb_mail_template')->insert([
        ['cd' => 1, 'title' => 'visible', 'disp_flg' => '1'],
        ['cd' => 2, 'title' => 'borrada', 'disp_flg' => '9'],
    ]);

    expect(Promotions\MailTemplate::pluck('title')->all())->toBe(['visible']);

    Promotions\MailTemplate::find(1)->delete();

    expect(DB::table('tb_mail_template')->where('cd', 1)->value('disp_flg'))->toBe('9')
        ->and(Promotions\MailTemplate::count())->toBe(0)
        ->and(Promotions\MailTemplate::withDeleted()->count())->toBe(2);
});

it('numera con hatuban al crear y pone las fechas del legacy', function () {
    DB::table('hatuban')->insert(['koumoku' => 'tb_mail_template_category', 'kazu' => 12]);

    $category = Promotions\MailTemplateCategory::create(['name' => 'お知らせ', 'hyouji_flg' => '1', 'disp_flg' => '1', 'rank' => 1]);

    expect($category->cd)->toBe(12)
        ->and(app(Hatuban::class)->peek('tb_mail_template_category'))->toBe(13)
        ->and(DB::table('tb_mail_template_category')->where('cd', 12)->value('touroku_date'))->toBe((string) now()->timestamp);
});

it('la cola de envío apunta a su envío y el push a sus entregas', function () {
    DB::table('mailmag_master')->insert(['cd' => 4, 'type_flg' => 'follow']);
    DB::table('send_schedule')->insert(['cd' => 1, 'mag_cd' => 4, '配信時間' => '10:00', 'flg' => '1']);
    DB::table('push_master')->insert(['cd' => 8, 'title' => 'セール', 'disp_flg' => '1']);
    DB::table('push_notifications')->insert([
        ['cd' => 1, 'type' => 'push', 'type_master_cd' => 8, 'tb_customer_cd' => 1],
        ['cd' => 2, 'type' => 'mailmaga', 'type_master_cd' => 8, 'tb_customer_cd' => 1],
    ]);

    expect(Promotions\SendSchedule::find(1)->mail->isRecurring())->toBeTrue()
        ->and(Promotions\MailmagMaster::find(4)->schedule->cd)->toBe(1)
        ->and(Promotions\PushMaster::find(8)->notifications->pluck('cd')->all())->toBe([1]);
});
