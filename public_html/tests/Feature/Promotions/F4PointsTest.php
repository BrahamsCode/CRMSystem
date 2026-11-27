<?php

use App\Console\Commands\Promotions\GrantPaidMemberPointsCommand;
use App\Console\Commands\Promotions\GrantRegisterPointsCommand;
use App\Services\Integraciones\MobileShop\MobileShopClient;
use App\Services\Promotions\PushTrigger;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

/*
 * F4 (S2): ポイント集計, ポイント一括付与 (CSV) y los comandos de puntos de registro y de socios de pago.
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
    $this->withoutMiddleware(Authenticate::class);
    $this->travelTo(Carbon::parse('2026-11-26 10:00:00', 'Asia/Tokyo'));

    DB::table('admin_master')->insert(['cd' => 1, 'point_shikkou_kikan' => '30', 'application_use_flg' => '1']);
    DB::table('tb_shop')->insert([['cd' => 1, 'kaishamei' => '本店', 'disp_flg' => '1'], ['cd' => 2, 'kaishamei' => '支店', 'disp_flg' => '1']]);
    DB::table('hatuban')->insert([['koumoku' => 'tb_point_log', 'kazu' => 100], ['koumoku' => 'push_master', 'kazu' => 50]]);

    $this->pushCalls = [];
    $calls = &$this->pushCalls;
    app()->instance(PushTrigger::class, new class($calls) implements PushTrigger {
        public function __construct(private array &$calls) {}

        public function trigger(string $type, int $masterCd): void
        {
            $this->calls[] = [$type, $masterCd];
        }
    });
});

function f4Customer(int $cd, string $id, array $extra = []): void
{
    DB::table('tb_customer')->insert($extra + ['cd' => $cd, 'id' => $id, 'name' => "客$cd", 'disp_flg' => '1', 'touroku_kubun' => '2', 'tb_shop_cd' => 1]);
}

function f4Csv(string $utf8): UploadedFile
{
    return UploadedFile::fake()->createWithContent('grant.csv', mb_convert_encoding($utf8, 'SJIS-win', 'UTF-8'));
}

it('abre ポイント集計 y redirige la URL del legacy', function () {
    $this->get(route('admin.promocion.points.aggregate'))->assertOk()
        ->assertSee('■ポイント集計')->assertSee('/system/category3/point/ajax_point_aggregate.php', false);
    $this->get('/system/category3/point/point_aggregate.php')->assertRedirect(route('admin.promocion.points.aggregate'));
});

it('agrega tb_point_log por tienda y clase como ajax_point_aggregate.php', function () {
    $t = Carbon::parse('2026-11-10 12:00', 'Asia/Tokyo')->getTimestamp();
    DB::table('tb_point_log')->insert([
        ['cd' => 1, 'tb_shop_cd' => 1, 'action_flg' => 'crm:newRegist', 'sounyu_point' => 10, 'shouhi_point' => null, 'jidou_point' => null, 'kaikei' => null, 'push_master_cd' => null, 'touroku_date' => $t],
        ['cd' => 2, 'tb_shop_cd' => 1, 'action_flg' => null, 'sounyu_point' => null, 'shouhi_point' => 3, 'jidou_point' => 7, 'kaikei' => 1000, 'push_master_cd' => null, 'touroku_date' => $t],
        ['cd' => 3, 'tb_shop_cd' => 1, 'action_flg' => null, 'sounyu_point' => -4, 'shouhi_point' => null, 'jidou_point' => null, 'kaikei' => null, 'push_master_cd' => null, 'touroku_date' => $t],
        ['cd' => 4, 'tb_shop_cd' => -1, 'action_flg' => 'mycolle:couponChange', 'sounyu_point' => null, 'shouhi_point' => 5, 'jidou_point' => null, 'kaikei' => null, 'push_master_cd' => null, 'touroku_date' => $t],
        ['cd' => 5, 'tb_shop_cd' => 2, 'action_flg' => 'x', 'sounyu_point' => 2, 'shouhi_point' => null, 'jidou_point' => null, 'kaikei' => null, 'push_master_cd' => 0, 'touroku_date' => $t],
        // Fuera del periodo
        ['cd' => 6, 'tb_shop_cd' => 1, 'action_flg' => 'crm:newRegist', 'sounyu_point' => 99, 'shouhi_point' => null, 'jidou_point' => null, 'kaikei' => null, 'push_master_cd' => null, 'touroku_date' => $t + 86400 * 30],
    ]);

    $json = $this->post('/system/category3/point/ajax_point_aggregate.php', ['startDate' => '2026/11/01', 'endDate' => '2026-11-10'])->assertOk()->json();

    expect($json['status'])->toBe('ok')
        ->and($json['data']['startDate'])->toBe('2026/11/01')
        ->and(array_column($json['data']['result'], 'name'))->toBe(['管理画面', '本店', '支店'])
        ->and($json['data']['result'][1]['point'])->toEqual([
            'acquire' => ['accounting' => 7, 'adjust' => 0, 'crm:newRegist' => 10],
            'use' => ['accounting' => 3, 'adjust' => 4],
        ])
        ->and($json['data']['result'][0]['point'])->toEqual(['use' => ['mycolle:couponChange' => 5], 'acquire' => ['mycolle:couponChange' => 0, 'adjust' => 0]])
        ->and($json['data']['result'][2]['point']['acquire'])->toBe(['mycolle:dailyLogin' => 2]);
});

it('responde los errores de fecha y sin movimientos no pone `point`', function () {
    $this->post(route('admin.promocion.points.aggregate.ajax'), ['startDate' => '2026/02/30'])
        ->assertOk()->assertExactJson(['status' => 'error', 'errorMsg' => '期間開始日付は不適切な形式です。']);
    $this->post(route('admin.promocion.points.aggregate.ajax'), ['endDate' => 'x'])
        ->assertJson(['status' => 'error', 'errorMsg' => '期間終了日付は不適切な形式です。']);

    $json = $this->post(route('admin.promocion.points.aggregate.ajax'), [])->json();
    expect($json['data']['result'][1])->toBe(['cd' => '1', 'name' => '本店']);
});

it('descarga la ひな形 en SJIS y redirige la URL del legacy', function () {
    $res = $this->get(route('admin.promocion.points.grant-csv', ['mode' => 'base_csv']))->assertOk()
        ->assertHeader('Content-Disposition', 'attachment; filename=base.csv');
    expect(mb_convert_encoding($res->getContent(), 'UTF-8', 'SJIS'))->toBe("顧客番号,付与ポイント\n");

    $this->get('/system/category3/point/csv_grant_points.php?mode=base_csv')
        ->assertRedirect(route('admin.promocion.points.grant-csv', ['mode' => 'base_csv']));
    $this->get(route('admin.promocion.points.grant-csv'))->assertOk()->assertSee('■ポイント一括付与')->assertDontSee('ポイント付与しました');
});

it('da los puntos del CSV con PointService y lista los que no pudo', function () {
    f4Customer(11, '1001');
    f4Customer(12, '1002', ['touroku_kubun' => '1']);
    DB::table('tb_customer_point')->insert(['cd' => 11, 'tb_customer_cd' => 11, 'service_point' => 5, 'point_yukoukigen' => 0]);

    $csv = "顧客番号,付与ポイント\r\n１００１,２０\r\n\r\n1002,3\r\n9999,1\r\n";
    $this->post(route('admin.promocion.points.grant-csv.store'), ['act' => 'csvUpload', 'csvFile' => f4Csv($csv)])->assertOk()
        ->assertSee('下記の顧客ID（1件）にポイント付与しました。')->assertSee('>1001</textarea>', false)
        ->assertSee('下記の顧客ID（2件）に ポイント付与できませんでした。')->assertSee('1002,9999');

    expect((int) DB::table('tb_customer_point')->where('tb_customer_cd', 11)->value('service_point'))->toBe(25);
    $log = DB::table('tb_point_log')->where('tb_customer_cd', 11)->first();
    expect([(int) $log->sounyu_point, (int) $log->shori_mae_point, (int) $log->tb_staff_cd, (int) $log->tb_shop_cd, (int) $log->tb_terminal_cd])->toBe([20, 5, 1, 0, -1]);
});

it('no da ningún punto si una fila es inválida (transacción) y valida el archivo', function () {
    f4Customer(11, '1001');
    $this->post(route('admin.promocion.points.grant-csv.store'), ['csvFile' => f4Csv("顧客番号,付与ポイント\n1001,10\n1001,abc\n")])
        ->assertSee('3行目に異常があります。')->assertDontSee('ポイント付与しました');
    expect(DB::table('tb_point_log')->count())->toBe(0)->and(DB::table('tb_customer_point')->count())->toBe(0);

    $this->post(route('admin.promocion.points.grant-csv.store'), [])->assertSee('ファイルを選択して下さい。');
    $this->post(route('admin.promocion.points.grant-csv.store'), ['csvFile' => UploadedFile::fake()->image('a.png')])->assertSee('ファイルの形式が異常です。');
});

it('promocion:register-point da los puntos de registro en la ventana de 5 minutos', function () {
    DB::table('tb_shop')->where('cd', 1)->update(['customer_registration_settings' => serialize(['registPoints' => 30, 'days' => 2, 'hours' => '9', 'minutes' => '58'])]);
    DB::table('tb_shop')->where('cd', 2)->update(['customer_registration_settings' => serialize(['registPoints' => 30, 'days' => 2, 'hours' => '9', 'minutes' => '50'])]);
    $day = Carbon::parse('2026-11-24 15:00', 'Asia/Tokyo')->getTimestamp();
    f4Customer(21, '2001', ['touroku_date' => (string) $day, 'mycolle_touroku_date' => '123']);
    f4Customer(22, '2002', ['touroku_date' => (string) $day]);
    f4Customer(23, '2003', ['touroku_date' => (string) ($day + 86400)]);
    f4Customer(24, '2004', ['touroku_date' => (string) $day]);
    f4Customer(25, '2005', ['touroku_date' => (string) $day, 'tb_shop_cd' => 2]);
    DB::table('tb_point_log')->insert(['cd' => 1, 'tb_customer_cd' => 24, 'tb_shop_cd' => 1, 'action_flg' => 'crm:newRegist', 'sounyu_point' => 30]);

    $this->artisan('promocion:register-point')->assertSuccessful()->expectsOutputToContain('21【2001】 <== mycolle:newRegist');

    $logs = DB::table('tb_point_log')->where('cd', '>=', 100)->orderBy('tb_customer_cd')->get();
    expect($logs->map(fn ($l) => [(int) $l->tb_customer_cd, $l->action_flg, (int) $l->sounyu_point, (int) $l->tb_terminal_cd, (int) $l->tb_staff_cd])->all())
        ->toBe([[21, 'mycolle:newRegist', 30, -100, 1], [22, 'crm:newRegist', 30, -100, 1]]);

    // Una segunda pasada no repite
    $this->artisan('promocion:register-point')->assertSuccessful();
    expect(DB::table('tb_point_log')->count())->toBe(3);
});

it('promocion:paid-member-point da los puntos y el push el día configurado', function () {
    DB::table('tb_base_system_grandchild')->insert(['cd' => 1, 'system_name' => 'ポイント管理', 'system_flg' => '1']);
    DB::table('contents_master')->insert(['cd' => 1, 'category' => 'mycolle', 'field' => 'app_layout_mode', 'disp_flg' => '1', 'text_data' => '1']);
    DB::table('admin_master')->where('cd', 1)->update(['paid_member_settings' => serialize(['points' => 1000, 'months' => 12, 'day' => 26])]);
    $inRange = Carbon::parse('2025-11-15', 'Asia/Tokyo')->getTimestamp();
    f4Customer(31, '3001', ['paid_member_flg' => 2, 'paid_member_service_start_date' => $inRange]);
    f4Customer(32, '3002', ['paid_member_flg' => 1, 'paid_member_service_start_date' => $inRange]);
    f4Customer(33, '3003', ['paid_member_flg' => 3, 'paid_member_service_start_date' => Carbon::parse('2025-12-01', 'Asia/Tokyo')->getTimestamp()]);

    $refreshed = new ArrayObject;
    app()->instance(MobileShopClient::class, new class($refreshed) implements MobileShopClient {
        public function __construct(private ArrayObject $seen) {}

        public function fetchToken(): string|false { return 'jwt'; }

        public function fetchPaidMemberFlg(string|false $token, mixed $customerId): object|false
        {
            $this->seen[] = $customerId;

            return false;
        }

        public function sendUnpaidMemberStatus(string|false $token, mixed $customerId): int|false { return 1; }

        public function buildUserId(mixed $customerId): string { return (string) $customerId; }
    });

    $this->artisan('promocion:paid-member-point')->assertSuccessful();

    expect($refreshed->getArrayCopy())->toEqualCanonicalizing(['3001', '3002', '3003']);
    $log = DB::table('tb_point_log')->get();
    expect($log)->toHaveCount(1)
        ->and([(int) $log[0]->tb_customer_cd, $log[0]->action_flg, (int) $log[0]->sounyu_point, (int) $log[0]->tb_shop_cd, (int) $log[0]->tb_terminal_cd])
        ->toBe([31, 'paidMemberPoint:2026-11', 1000, -1, -100]);

    $push = DB::table('push_master')->where('cd', 50)->first();
    expect($push->title)->toBe('モバイルステーションからのお知らせ')
        ->and($push->message)->toContain('1,000円分のポイントを贈呈致します。')
        ->and($push->wherestr)->toStartWith('SELECT * FROM tb_customer WHERE paid_member_flg IN (2, 3) AND paid_member_service_start_date BETWEEN '.Carbon::parse('2025-11-01', 'Asia/Tokyo')->getTimestamp())
        ->and((int) $push->tb_shop_cd)->toBe(-1)
        ->and($push->extra_data)->toBe(GrantPaidMemberPointsCommand::PUSH_EXTRA_DATA)
        ->and($this->pushCalls)->toBe([['push', 50]]);

    // Otro día no hace nada
    $this->travelTo(Carbon::parse('2026-11-27 10:00:00', 'Asia/Tokyo'));
    $this->artisan('promocion:paid-member-point')->assertSuccessful();
    expect(DB::table('tb_point_log')->count())->toBe(1);
});

it('programa los comandos con el horario y las condiciones del crontab del legacy', function () {
    $events = collect(app(Schedule::class)->events());
    $register = $events->first(fn ($e) => str_contains($e->command, 'promocion:register-point'));
    $paid = $events->first(fn ($e) => str_contains($e->command, 'promocion:paid-member-point'));

    expect($register->expression)->toBe('*/5 * * * *')
        ->and($paid->expression)->toBe('0 10 * * *')
        ->and($paid->timezone)->toBe('Asia/Tokyo')
        // application_use_flg = 1 pero sin ポイント管理 activo
        ->and(GrantRegisterPointsCommand::shouldSchedule())->toBeFalse()
        ->and(GrantPaidMemberPointsCommand::shouldSchedule())->toBeFalse();

    DB::table('tb_base_system_grandchild')->insert(['cd' => 1, 'system_name' => 'ポイント管理', 'system_flg' => '1']);
    expect(GrantRegisterPointsCommand::shouldSchedule())->toBeTrue();
});
