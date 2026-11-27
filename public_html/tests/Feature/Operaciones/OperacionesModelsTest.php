<?php

use App\Models\Operaciones\BelongPost;
use App\Models\Operaciones\EmploymentSystem;
use App\Models\Operaciones\FelicaMode;
use App\Models\Operaciones\Information;
use App\Models\Operaciones\KintaiConfig;
use App\Models\Operaciones\KintaiHiduke;
use App\Models\Operaciones\KintaiRecord;
use App\Models\Operaciones\OfficialPosition;
use App\Models\Operaciones\ShopWork;
use App\Models\Operaciones\StaffCard;
use App\Models\Operaciones\StaffGroup;
use App\Models\Operaciones\StaffShift;
use App\Models\Operaciones\StaffShiftKind;
use App\Models\Operaciones\WorkTime;
use App\Models\Integraciones\UrlTransferData;
use App\Models\Shop;
use App\Models\Staff;
use App\Services\Integraciones\NullPosGateway as NullIntegracionesPosGateway;
use App\Services\Integraciones\PosGateway as IntegracionesPosGateway;
use App\Services\Operaciones\LegacyMonth;
use App\Services\Operaciones\NullPosGateway as NullOperacionesPosGateway;
use App\Services\Operaciones\PosGateway as OperacionesPosGateway;
use Illuminate\Support\Facades\DB;

// Modelos de S5 sobre las tablas del legacy (esquema de database/schema/pgsql-schema.sql)

function shopForTest(int $cd = 1): Shop
{
    DB::table('tb_shop')->insert(['cd' => $cd, 'kaishamei' => "店舗{$cd}", 'disp_flg' => '1']);

    return Shop::findOrFail($cd);
}

function staffForTest(int $cd, ?int $groupCd = null, int $shopCd = 1): Staff
{
    DB::table('tb_staff')->insert([
        'cd' => $cd, 'shop_cd' => $shopCd, 'id' => sprintf('%06d', $cd), 'name' => "社員{$cd}",
        'login_id' => "u{$cd}", 'disp_flg' => '1', 'tb_staff_group_cd' => $groupCd,
    ]);

    return Staff::findOrFail($cd);
}

it('enlaza cada PosGateway a su implementación nula (los providers se registran solos)', function () {
    expect(app(OperacionesPosGateway::class))->toBeInstanceOf(NullOperacionesPosGateway::class)
        ->and(app(IntegracionesPosGateway::class))->toBeInstanceOf(NullIntegracionesPosGateway::class);

    app(OperacionesPosGateway::class)->syncStaff(1);
    app(IntegracionesPosGateway::class)->setShopDomain(1, 'shop1', null);
});

it('スタッフグループ: numera con hatuban, guarda epoch y borra con disp_flg = 9', function () {
    $first = StaffGroup::create(['name' => 'ホール', 'rank' => 2, 'disp_flg' => '1']);
    $second = StaffGroup::create(['name' => 'キッチン', 'rank' => 1, 'disp_flg' => '1']);

    expect($second->cd)->toBe($first->cd + 1)
        ->and($first->touroku_date)->toMatch('/^\d{10}$/')
        ->and(StaffGroup::ordered()->pluck('name')->all())->toBe(['キッチン', 'ホール'])
        ->and((int) DB::table('hatuban')->where('koumoku', 'tb_staff_group')->value('kazu'))->toBe($second->cd + 1);

    $first->delete();

    expect(StaffGroup::pluck('cd')->all())->toBe([$second->cd])
        ->and(DB::table('tb_staff_group')->where('cd', $first->cd)->value('disp_flg'))->toBe('9');
});

it('una スタッフグループ tiene su personal (tb_staff.tb_staff_group_cd)', function () {
    shopForTest();
    $group = StaffGroup::create(['name' => 'ホール', 'disp_flg' => '1']);
    staffForTest(10, $group->cd);
    staffForTest(11);

    expect($group->staff()->pluck('cd')->all())->toBe([10]);
});

it('los maestros de cargo, 所属 y 雇用形態 exponen su texto como name', function () {
    $position = OfficialPosition::create(['officialposition' => '店長']);
    $post = BelongPost::findOrCreateByName('営業部');
    DB::table('tb_employmentsystem')->insert(['cd' => 1, 'employmentsystem' => '正社員']);

    expect($position->name)->toBe('店長')
        ->and(BelongPost::findOrCreateByName('営業部')->cd)->toBe($post->cd)
        ->and(DB::table('hatuban')->where('koumoku', 'belongpost')->exists())->toBeTrue()
        ->and(EmploymentSystem::find(1)->name)->toBe('正社員');
});

it('インフォメーション: update en epoch, cargos por espacio y visibles por tienda', function () {
    shopForTest(1);
    shopForTest(2);
    $all = Information::create(['comment' => '全店舗', 'officialposition' => '店長 チーフ', 'disp_flg' => '1']);
    $shop1 = Information::create(['shop_cd' => 1, 'comment' => '本店', 'disp_flg' => '1']);
    Information::create(['shop_cd' => 2, 'comment' => '支店', 'disp_flg' => '1']);

    expect($all->update)->toMatch('/^\d{10}$/')
        ->and($all->touroku_date ?? null)->toBeNull()
        ->and($all->officialPositions())->toBe(['店長', 'チーフ'])
        ->and(Information::visibleForShop(1)->orderBy('cd')->pluck('comment')->all())->toBe(['全店舗', '本店'])
        ->and($shop1->shop->cd)->toBe(1);
});

it('カード: libre hasta asignarla; カード解除 la vuelve a dejar libre', function () {
    shopForTest();
    $staff = staffForTest(20);
    $card = StaffCard::create(['card_no' => '1000001', 'felica_no' => '0123456789ABCDEF', 'staff_cd' => '', 'card_flg' => StaffCard::UNASSIGNED]);

    expect(StaffCard::unassigned()->count())->toBe(1);

    $card->assignTo($staff);

    expect($card->fresh()->isAssigned())->toBeTrue()
        ->and($card->fresh()->staff->cd)->toBe(20)
        ->and(StaffCard::unassigned()->count())->toBe(0);

    $card->release();

    expect($card->fresh()->staff_cd)->toBe('')
        ->and($card->fresh()->isAssigned())->toBeFalse();
});

it('el lector FeliCa usa la fila cd = 1 y arranca en 運用', function () {
    $reader = FelicaMode::reader();

    expect($reader->cd)->toBe(1)
        ->and($reader->mode)->toBe(FelicaMode::MODE_OPERATION)
        ->and(FelicaMode::MODES[$reader->mode])->toBe('運用モード');

    $reader->update(['mode' => FelicaMode::MODE_REGISTER]);

    expect(FelicaMode::reader()->isRegisterMode())->toBeTrue()
        ->and(FelicaMode::reader()->update)->toMatch('/^\d{10}$/');
});

it('営業日設定: una fila por tienda y mes con syukkinbi Y/n', function () {
    shopForTest();
    $month = LegacyMonth::of(2026, 2);

    $work = ShopWork::forMonthOrNew(1, $month);
    $work->setOpen(1, false)->setOpen(28, true)->save();

    $saved = ShopWork::forMonth(1, $month)->firstOrFail();

    expect($saved->syukkinbi)->toBe('2026/2')
        ->and($saved->syukkin_time_1)->toBe('0')
        ->and($saved->isOpen(1))->toBeFalse()
        ->and($saved->isOpen(2))->toBeTrue()
        ->and($saved->openDays())->toHaveCount(27)
        ->and($saved->shop->cd)->toBe(1)
        ->and(ShopWork::forMonthOrNew(1, $month)->cd)->toBe($saved->cd);
});

it('営業日設定 no acepta días que el mes no tiene', function () {
    ShopWork::forMonthOrNew(1, LegacyMonth::of(2026, 2))->setOpen(30, true);
})->throws(InvalidArgumentException::class);

it('turnos: varios por día en listas paralelas <>', function () {
    shopForTest();
    staffForTest(30);
    $kind = StaffShiftKind::create(['name' => '早番', 'color' => '#ff0000', 'rank' => 1, 'disp_flg' => '1']);
    $month = LegacyMonth::of(2026, 11);
    $nine = $month->day(27)->setTime(9, 0)->getTimestamp();
    $eighteen = $month->day(27)->setTime(18, 0)->getTimestamp();
    $lateEnd = $month->day(28)->setTime(2, 0)->getTimestamp();

    $shift = new StaffShift(['shop_cd' => 1, 'staff_cd' => 30, 'syukkinbi' => $month->epoch()]);
    $shift->setShifts(27, [
        ['start' => $nine, 'end' => $eighteen, 'kind_cd' => $kind->cd, 'comment' => "O'Neil"],
        ['start' => $eighteen, 'end' => $lateEnd, 'kind_cd' => null, 'comment' => ''],
    ])->save();

    $saved = StaffShift::forMonth($month)->where('staff_cd', 30)->firstOrFail();

    expect($saved->gaisyutu_time_27)->toBe("{$nine}<>{$eighteen}")
        ->and($saved->shifts(27))->toBe([
            ['start' => $nine, 'end' => $eighteen, 'kind_cd' => $kind->cd, 'comment' => "O'Neil"],
            ['start' => $eighteen, 'end' => $lateEnd, 'kind_cd' => null, 'comment' => ''],
        ])
        ->and($saved->shifts(26))->toBe([])
        ->and($saved->staff->cd)->toBe(30)
        ->and($saved->month()->slash())->toBe('2026/11');
});

it('タイムカード: las columnas de un día de tb_work_time', function () {
    shopForTest();
    staffForTest(40);
    $month = LegacyMonth::of(2026, 11);

    $work = new WorkTime(['shop_cd' => 1, 'staff_cd' => 40, 'syukkinbi' => $month->epoch()]);
    $work->setDayValue('syukkin_time', 3, '1764720000')->setDayValue('syukkin_flg', 3, '遅刻')->save();

    $day = WorkTime::forMonth($month)->where('staff_cd', 40)->firstOrFail()->day(3);

    expect($day['syukkin_time'])->toBe('1764720000')
        ->and($day['syukkin_flg'])->toBe('遅刻')
        ->and($day['taikin_time'])->toBeNull()
        ->and(array_keys($day))->toBe(WorkTime::DAY_PREFIXES);
});

it('勤怠: la configuración de la tienda se crea con los valores del legacy', function () {
    shopForTest();
    $config = KintaiConfig::forShop(1);

    expect($config->kijun_time)->toBe('05<>00')
        ->and(KintaiConfig::splitTime($config->kijun_time))->toBe([5, 0])
        ->and($config->toArray())->not->toHaveKey('password')
        ->and(KintaiConfig::forShop(1)->cd)->toBe($config->cd);
});

it('勤怠: una jornada con sus marcas ordenadas', function () {
    shopForTest();
    staffForTest(50);
    $day = KintaiHiduke::create(['tb_staff_cd' => 50, 'hiduke' => '1764720000', 'kintai_flg' => '出勤', 'disp_flg' => '1', 'hyouji_flg' => '1']);
    KintaiRecord::create(['kintai_tb_hiduke_cd' => $day->cd, 'tb_staff_cd' => 50, 'kintai_flg' => KintaiRecord::CLOCK_OUT, 'kintai_time' => 1764752400, 'disp_flg' => '1']);
    KintaiRecord::create(['kintai_tb_hiduke_cd' => $day->cd, 'tb_staff_cd' => 50, 'kintai_flg' => KintaiRecord::CLOCK_IN, 'kintai_time' => 1764720000, 'disp_flg' => '1']);

    expect($day->records->pluck('kintai_flg')->all())->toBe(['1', '2'])
        ->and($day->staff->cd)->toBe(50)
        ->and(in_array('有給休暇', KintaiHiduke::DAY_TYPES, true))->toBeTrue();
});

it('ptag: registra accesos en url_transfer_data, tabla sin clave primaria', function () {
    UrlTransferData::record('CRM000000001', null);
    UrlTransferData::record('CRM000000001', 1609897927001809158);

    $rows = UrlTransferData::query()->orderBy('tb_customer_cd')->get();

    expect($rows)->toHaveCount(2)
        ->and($rows->first()->data_id)->toBe('CRM000000001')
        ->and($rows->pluck('tb_customer_cd')->all())->toContain(1609897927001809158);
});
