<?php

use App\Console\Commands\Promotions\CleanTempCommand;
use App\Models\Promotions\Mycoupon;
use App\Models\Promotions\MycouponMaster;
use App\Providers\PromocionServiceProvider;
use App\Services\Promotions\CouponService;
use App\Services\Promotions\LotteryService;
use App\Services\Promotions\NullPosGateway;
use App\Services\Promotions\PointRuleResolver;
use App\Services\Promotions\PosGateway;
use App\Services\Promotions\SendSchedulePlanner;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** Hora del legacy (Asia/Tokyo) */
function jst(string $time): Carbon
{
    return Carbon::parse($time, 'Asia/Tokyo');
}

// ---------------------------------------------------------------------------
// PointRuleResolver (Point::getPointCount / cStamp::getStampCount)
// ---------------------------------------------------------------------------

describe('PointRuleResolver', function () {
    // Miércoles 30 de septiembre de 2026, 11:00 JST: último día del mes
    beforeEach(function () {
        $this->at = jst('2026-09-30 11:00');
        $this->resolver = app(PointRuleResolver::class);
        $this->cd = 0;
        $this->rule = function (array $row, string $table = 'tb_point_rules') {
            DB::table($table)->insert($row + ['cd' => ++$this->cd, 'tb_shop_cd' => 1, 'hyouji_flg' => '1', 'update_date' => 100]);
        };
    });

    it('devuelve 1 sin reglas', function () {
        expect($this->resolver->points(1, null, $this->at))->toBe(1)
            ->and($this->resolver->stamps(1, $this->at))->toBe(1);
    });

    it('respeta la prioridad 5 → 4 → 3 → 2 → 1', function () {
        $epoch = $this->at->getTimestamp();

        ($this->rule)(['type_flg' => '1', 'start_time' => 1000, 'end_time' => 1200, 'point_count' => 1]);
        expect($this->resolver->points(1, null, $this->at))->toBe(1);

        ($this->rule)(['type_flg' => '2', 'day_of_week' => 3, 'start_time' => 0, 'end_time' => 2359, 'point_count' => 2]);
        expect($this->resolver->points(1, null, $this->at))->toBe(2);

        ($this->rule)(['type_flg' => '3', 'start_time' => $epoch - 3600, 'end_time' => $epoch + 3600, 'point_count' => 3]);
        expect($this->resolver->points(1, null, $this->at))->toBe(3);

        ($this->rule)(['type_flg' => '4', 'start_time' => 32, 'point_count' => 4]);
        expect($this->resolver->points(1, null, $this->at))->toBe(4);

        ($this->rule)(['type_flg' => '4', 'start_time' => 30, 'point_count' => 5]);
        expect($this->resolver->points(1, null, $this->at))->toBe(5);   // el día exacto gana a fin de mes

        ($this->rule)(['type_flg' => '5', 'start_time' => $epoch - 60, 'end_time' => $epoch + 60, 'point_count' => 6]);
        expect($this->resolver->points(1, null, $this->at))->toBe(6);
    });

    it('lee las franjas como HHMM con el inicio incluido y el final excluido', function () {
        ($this->rule)(['type_flg' => '1', 'start_time' => 1100, 'end_time' => 1200, 'point_count' => 3]);

        expect($this->resolver->points(1, null, $this->at))->toBe(3)
            ->and($this->resolver->points(1, null, jst('2026-09-30 12:00')))->toBe(1)
            ->and($this->resolver->points(1, null, jst('2026-09-30 10:59')))->toBe(1);
    });

    it('prefiere la tienda a todas las tiendas aunque la general tenga más prioridad', function () {
        $epoch = $this->at->getTimestamp();
        ($this->rule)(['tb_shop_cd' => -1, 'type_flg' => '5', 'start_time' => $epoch - 60, 'end_time' => $epoch + 60, 'point_count' => 9]);
        expect($this->resolver->points(1, null, $this->at))->toBe(9);

        ($this->rule)(['type_flg' => '1', 'start_time' => 0, 'end_time' => 2359, 'point_count' => 2]);
        expect($this->resolver->points(1, null, $this->at))->toBe(2)
            ->and($this->resolver->points(2, null, $this->at))->toBe(9);
    });

    it('en empate gana la de update_date más reciente e ignora hyouji_flg = 9', function () {
        ($this->rule)(['type_flg' => '1', 'start_time' => 0, 'end_time' => 2359, 'point_count' => 2, 'update_date' => 100]);
        ($this->rule)(['type_flg' => '1', 'start_time' => 0, 'end_time' => 2359, 'point_count' => 3, 'update_date' => 200]);
        ($this->rule)(['type_flg' => '1', 'start_time' => 0, 'end_time' => 2359, 'point_count' => 8, 'update_date' => 300, 'hyouji_flg' => '9']);

        expect($this->resolver->points(1, null, $this->at))->toBe(3);
    });

    it('filtra por rango solo si el sistema de rangos está activo y se pasa el cliente', function () {
        ($this->rule)(['type_flg' => '1', 'start_time' => 0, 'end_time' => 2359, 'point_count' => 5, 'tb_kingaku_rank_master_cds' => '2,3']);
        $gold = ['kingaku_rank_now' => '3', 'raiten_rank_now' => ''];
        $none = ['kingaku_rank_now' => '', 'raiten_rank_now' => ''];

        // Sin el sistema activo la lista de rangos no filtra
        expect($this->resolver->points(1, $none, $this->at))->toBe(5);

        DB::table('tb_base_system_grandchild')->insert(['cd' => 1, 'system_name' => '顧客ランク（使用金額ランク）', 'system_flg' => '1']);

        expect($this->resolver->points(1, $gold, $this->at))->toBe(5)
            ->and($this->resolver->points(1, $none, $this->at))->toBe(1)
            ->and($this->resolver->points(1, null, $this->at))->toBe(5);   // sin cliente, como el legacy
    });

    it('resuelve también las reglas de sellos', function () {
        ($this->rule)(['type_flg' => '4', 'start_time' => 30, 'stamp_count' => 3], 'tb_stamp_rules');

        expect($this->resolver->stamps(1, $this->at))->toBe(3);
    });
});

// ---------------------------------------------------------------------------
// CouponService (mycoupon_hakkou.php, appview/coupon_control.php, digitalsignage)
// ---------------------------------------------------------------------------

describe('CouponService', function () {
    beforeEach(function () {
        DB::table('tb_mycoupon_master')->insert(['cd' => 5, 'tb_shop_cd' => 2, 'title' => '10%OFF', 'coupon_kubun' => '', 'limit_flg' => '1', 'disp_flg' => '1']);
        $this->service = app(CouponService::class);
        $this->master = MycouponMaster::find(5);
    });

    it('calcula el vencimiento por limit_flg', function () {
        $at = jst('2026-01-01 10:00');
        $day = 86400;
        $limit = fn (array $a) => $this->service->timeLimit(new MycouponMaster($a), $at);

        expect($limit(['limit_flg' => '1']))->toBe($at->getTimestamp() + 7 * $day)
            ->and($limit(['limit_flg' => '2']))->toBe($at->getTimestamp() + 30 * $day)
            ->and($limit(['limit_flg' => '3']))->toBe($at->getTimestamp() + 90 * $day)
            ->and($limit(['limit_flg' => '4', 'time_limit' => '10']))->toBe($at->getTimestamp() + 10 * $day)
            ->and($limit(['limit_flg' => '5', 'time_limit' => '1800000000']))->toBe(1800000000)
            ->and($limit(['limit_flg' => '']))->toBeNull();
    });

    it('entrega el cupón con cd de hatuban, tienda y tipo del maestro', function () {
        DB::table('hatuban')->insert(['koumoku' => 'tb_mycoupon', 'kazu' => 100]);

        $issued = $this->service->issue($this->master, 77, jst('2026-01-01 10:00'));
        $row = DB::table('tb_mycoupon')->where('cd', 100)->first();

        expect($issued->cd)->toBe(100)
            ->and($row->tb_customer_cd)->toBe(77)
            ->and($row->tb_shop_cd)->toBe(2)
            ->and($row->tb_mycoupon_master_cd)->toBe(5)
            ->and($row->touroku_date)->toBe((string) jst('2026-01-01 10:00')->getTimestamp())
            ->and($row->time_limit)->toBe((string) (jst('2026-01-01 10:00')->getTimestamp() + 7 * 86400));
    });

    it('al usarlo lo pasa a tb_mycoupon_used con la tienda y la hora', function () {
        $issued = $this->service->issue($this->master, 77);
        $used = $this->service->markUsed($issued, 3, jst('2026-02-01 12:00'));

        expect(DB::table('tb_mycoupon')->where('cd', $issued->cd)->exists())->toBeFalse()
            ->and($used->cd)->toBe($issued->cd)
            ->and(DB::table('tb_mycoupon_used')->where('cd', $issued->cd)->value('tb_shop_cd'))->toBe(3)
            ->and(DB::table('tb_mycoupon_used')->where('cd', $issued->cd)->value('used_date'))->toBe((string) jst('2026-02-01 12:00')->getTimestamp())
            ->and(DB::table('tb_mycoupon_used')->where('cd', $issued->cd)->value('tb_customer_cd'))->toBe(77);
    });

    it('deja cupones pendientes de imprimir por tienda (print_flg)', function () {
        $a = $this->service->issue($this->master, 77);
        $b = $this->service->issue($this->master, 77);
        $this->service->markForPrint($a, 4);

        expect($this->service->printableFor(77, 4)->pluck('cd')->all())->toBe([$a->cd])
            ->and($this->service->printableFor(77, 9))->toBeEmpty()
            ->and(Mycoupon::find($b->cd)->print_flg)->toBeNull();
    });
});

// ---------------------------------------------------------------------------
// LotteryService (cKujibiki)
// ---------------------------------------------------------------------------

describe('LotteryService', function () {
    beforeEach(function () {
        Carbon::setTestNow(jst('2026-03-10 12:00'));
        DB::table('tb_customer')->insert(['cd' => 10, 'touroku_kubun' => '2', 'disp_flg' => '1']);
        $cd = 100;
        $row = function (string $field, array $data) use (&$cd) {
            DB::table('contents_master')->insert($data + ['cd' => $cd++, 'category' => 'tb_c6_kujibiki', 'field' => $field, 'disp_flg' => '1']);
        };
        // Pesos: ダイヤ 10, ゴールド 20, ハズレ 70 → tramos (0,10], (10,30], (30,100]
        $row('rate', ['text_data' => 'ダイヤ', 'float_data' => 10, 'int_data' => 1]);
        $row('rate', ['text_data' => 'ゴールド', 'float_data' => 20, 'int_data' => 5]);
        $row('rate', ['text_data' => 'ハズレ', 'float_data' => 70]);
        $row('prize_count', ['text_data' => 'ダイヤ', 'int_data' => 0, 'update_date' => 0]);
        $row('prize_count', ['text_data' => 'ゴールド', 'int_data' => 0, 'update_date' => 0]);
        $row('max', ['int_data' => 2]);
        $row('limit_flg', ['text_data' => '1']);
        $this->lottery = fn (int $n) => app(LotteryService::class)->useRandom(fn () => $n);
    });

    afterEach(fn () => Carbon::setTestNow());

    it('sortea por tramos, cuenta el premio del día y deja la tirada con su plazo', function () {
        $result = ($this->lottery)(15)->draw(10);

        expect($result['status'])->toBe('SUCCESS')
            ->and($result['name'])->toBe('ゴールド')
            ->and($result['count'])->toBe(1)
            ->and($result['time_limit'])->toBe(now()->getTimestamp() + 7 * 86400)
            ->and(DB::table('contents_master')->where('field', 'prize_count')->where('text_data', 'ゴールド')->value('int_data'))->toBe(1)
            ->and(DB::table('tb_kujibiki_log')->where('tb_customer_cd', 10)->value('kujibiki_name'))->toBe('ゴールド')
            ->and(DB::table('tb_kujibiki_count')->where('tb_customer_cd', 10)->value('int_data'))->toBe(1);
    });

    it('si el premio se agotó hoy baja al siguiente', function () {
        DB::table('contents_master')->where('field', 'prize_count')->where('text_data', 'ダイヤ')
            ->update(['int_data' => 1, 'update_date' => now()->getTimestamp()]);

        expect(($this->lottery)(5)->draw(10)['name'])->toBe('ゴールド');
    });

    it('las existencias de ayer no cuentan: el día empieza a las 5:00', function () {
        DB::table('contents_master')->where('field', 'prize_count')->where('text_data', 'ダイヤ')
            ->update(['int_data' => 1, 'update_date' => jst('2026-03-10 04:59')->getTimestamp()]);

        $result = ($this->lottery)(5)->draw(10);

        expect($result['name'])->toBe('ダイヤ')->and($result['count'])->toBe(1);
    });

    it('respeta los intentos por día y se renuevan a las 5:00 del día siguiente', function () {
        $draw = ($this->lottery)(50);

        expect($draw->draw(10)['status'])->toBe('SUCCESS')
            ->and($draw->draw(10)['status'])->toBe('SUCCESS')
            ->and($draw->draw(10))->toBe(['status' => 'ERROR', 'name' => '一日の利用可能回数は2回までです']);

        Carbon::setTestNow(jst('2026-03-11 04:59'));
        expect($draw->draw(10)['status'])->toBe('ERROR');

        Carbon::setTestNow(jst('2026-03-11 05:00'));
        expect($draw->draw(10)['status'])->toBe('SUCCESS');
    });

    it('ハズレ no gasta existencias', function () {
        $result = ($this->lottery)(80)->draw(10);

        expect($result['name'])->toBe('ハズレ')
            ->and(DB::table('contents_master')->where('field', 'prize_count')->sum('int_data'))->toEqual(0);
    });

    it('sin configuración o desactivado no sortea', function () {
        DB::table('contents_master')->insert(['cd' => 999, 'category' => 'tb_c6_kujibiki', 'field' => 'disp_flg', 'text_data' => '9', 'disp_flg' => '1']);

        expect(($this->lottery)(15)->draw(10))->toBe(['status' => 'ERROR', 'name' => '設定が完了していません']);
    });

    it('quien no es socio no tiene intentos', function () {
        DB::table('tb_customer')->insert(['cd' => 11, 'touroku_kubun' => '1', 'disp_flg' => '1']);

        expect(($this->lottery)(15)->draw(11)['status'])->toBe('ERROR');
    });

    it('F1b: intentos del cliente, existencias del día y cupones de cada premio para Mi página', function () {
        $lottery = ($this->lottery)(15);
        expect($lottery->customerCount(10))->toBe(['count' => 0, 'max' => 2])
            ->and(DB::table('tb_kujibiki_count')->where('tb_customer_cd', 10)->exists())->toBeTrue()
            ->and($lottery->customerCount(99))->toBeNull();

        $lottery->draw(10);
        expect($lottery->customerCount(10))->toBe(['count' => 1, 'max' => 2])
            ->and($lottery->countData()['ゴールド'])->toBe(['name' => 'ゴールド', 'count' => 1, 'max' => 5, 'rate' => 20.0])
            ->and($lottery->countData()['ハズレ'])->toBe(['name' => 'ハズレ', 'count' => 0, 'max' => null, 'rate' => 70.0]);

        Carbon::setTestNow(jst('2026-03-11 06:00'));
        expect($lottery->customerCount(10)['count'])->toBe(0)->and($lottery->countData()['ゴールド']['count'])->toBe(0);

        DB::table('tb_mycoupon_master')->insert([['cd' => 1, 'title' => 'A', 'disp_flg' => '1'], ['cd' => 2, 'title' => 'B', 'disp_flg' => '9']]);
        DB::table('contents_master')->insert([
            ['cd' => 500, 'category' => 'tb_c6_kujibiki', 'field' => 'coupon', 'text_data' => 'ゴールド', 'int_data' => 1, 'disp_flg' => '1'],
            ['cd' => 501, 'category' => 'tb_c6_kujibiki', 'field' => 'coupon', 'text_data' => 'ダイヤ', 'int_data' => 2, 'disp_flg' => '1'],
        ]);
        expect($lottery->couponCount('ゴールド'))->toBe(1)->and($lottery->couponCount('ダイヤ'))->toBe(0)->and($lottery->couponCount())->toBe(1);
    });
});

// ---------------------------------------------------------------------------
// SendSchedulePlanner (cSendSchedule)
// ---------------------------------------------------------------------------

describe('SendSchedulePlanner', function () {
    beforeEach(fn () => $this->planner = app(SendSchedulePlanner::class));

    it('envía los únicos cuando llega su hora, aunque se pierda el minuto', function () {
        expect($this->planner->isDue('', '2026/03/10 10:00:00', '', jst('2026-03-10 09:59')))->toBeFalse()
            ->and($this->planner->isDue('', '2026/03/10 10:00:00', '', jst('2026-03-10 10:00')))->toBeTrue()
            ->and($this->planner->isDue('deco', '2026/03/10 10:00', '', jst('2026-03-10 10:07')))->toBeTrue()
            ->and($this->planner->isDue('', '', '', jst('2026-03-10 10:00')))->toBeFalse();
    });

    it('envía follow y recordatorios cada día a su HH:MM', function () {
        expect($this->planner->isDue('follow', '10:30', '-1<>1<>5', jst('2026-03-10 10:30')))->toBeTrue()
            ->and($this->planner->isDue('remdeco', '10:30', '1<>1<>2', jst('2026-03-11 10:30')))->toBeTrue()
            ->and($this->planner->isDue('follow', '10:30', '-1<>1<>5', jst('2026-03-10 10:31')))->toBeFalse();
    });

    it('envía auto en los meses y días elegidos (32 = fin de mes) o en los días de la semana', function () {
        expect($this->planner->isDue('auto', '08:00', '1<>03,09<>05,32', jst('2026-03-05 08:00')))->toBeTrue()
            ->and($this->planner->isDue('auto', '08:00', '1<>03,09<>05,32', jst('2026-03-31 08:00')))->toBeTrue()
            ->and($this->planner->isDue('auto', '08:00', '1<>03,09<>05,32', jst('2026-04-05 08:00')))->toBeFalse()
            ->and($this->planner->isDue('autodeco', '08:00', '2<>0,3', jst('2026-03-11 08:00')))->toBeTrue()   // miércoles
            ->and($this->planner->isDue('autodeco', '08:00', '2<>0,3', jst('2026-03-12 08:00')))->toBeFalse();
    });

    it('nunca envía las variantes POS', function () {
        expect($this->planner->isDue('posyoyaku', '10:00', '1<>1<>1', jst('2026-03-10 10:00')))->toBeFalse();
    });

    it('interpreta las horas en la zona del legacy (Asia/Tokyo)', function () {
        expect($this->planner->isDue('follow', '10:00', '', Carbon::parse('2026-03-10 01:00', 'UTC')))->toBeTrue();
    });
});

// ---------------------------------------------------------------------------
// Provider, POS y temporales
// ---------------------------------------------------------------------------

it('enlaza el POS a la implementación nula', function () {
    expect(app(PosGateway::class))->toBeInstanceOf(NullPosGateway::class)
        ->and(app(PosGateway::class)->searchCustomerCds(1, 'SELECT 1'))->toBe([]);
});

it('registra en el Scheduler las tareas del módulo que ya existen', function () {
    $commands = collect(app(Schedule::class)->events())->map(fn ($e) => $e->command)->implode("\n");

    expect($commands)->toContain('promocion:clean-temp');

    foreach (array_keys(PromocionServiceProvider::TASKS) as $class) {
        if (! class_exists($class)) {
            expect($commands)->not->toContain(class_basename($class));
        }
    }
});

it('borra los temporales de subida de más de un día', function () {
    Storage::fake('public');
    $disk = Storage::disk('public');
    $old = CleanTempCommand::DIRECTORIES[0] . '/viejo.jpg';
    $new = CleanTempCommand::DIRECTORIES[1] . '/nuevo.jpg';
    $disk->put($old, 'x');
    $disk->put($new, 'x');
    touch($disk->path($old), now()->subDays(2)->getTimestamp());

    $this->artisan('promocion:clean-temp')->assertSuccessful();

    expect($disk->exists($old))->toBeFalse()->and($disk->exists($new))->toBeTrue();
});
