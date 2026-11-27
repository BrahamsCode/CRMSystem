<?php

namespace App\Services\Reservations;

use App\Models\AdminMaster;
use App\Models\Reservations\ReservationSlot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * 時間選択 (category9/yoyaku/yoyaku_calendar.php): franjas del día de un empleado con su ocupación.
 *
 * Como el legacy: las filas van de la hora de apertura de la tienda (`tb_shop.start_work_time` 'H<>M') al final
 * de la franja del empleado, cada `admin_master.yoyaku_time_interval` minutos. Cada fila es
 * '-' (fuera de horario), 休憩・外出, 設備数オーバー/店舗キャパオーバー (con 設備 activado), 予約不可 o 予約可,
 * según los `people` reservados durante el tiempo de los menús elegidos frente al máximo de la franja.
 */
class AvailabilityService
{
    public function __construct(private readonly ReservationSettingService $settings) {}

    /**
     * @param  int  $kijunTime  día mostrado (epoch; `kijun_time` del legacy)
     * @param  array<int>  $menuCds  menús elegidos (base + opción)
     * @return array{
     *     date: string, slot: ?ReservationSlot, interval: int, timeCount: int, totalMinutes: int,
     *     rows: list<array{n: int, time: string, hhmm: string, plus: bool, status: string, people: int, error: string}>
     * }
     */
    public function day(int $shopCd, int $staffCd, int $kijunTime, array $menuCds, int $totalMinutes, mixed $requestedPeople): array
    {
        $day = Carbon::createFromTimestamp($kijunTime, 'Asia/Tokyo');
        $date = $day->format('Y/m/d');
        $adminMaster = AdminMaster::current();
        $interval = $this->settings->timeInterval($adminMaster);

        // Apertura de la tienda en segundos desde las 00:00
        $parts = explode('<>', (string) DB::table('tb_shop')->where('cd', $shopCd)->value('start_work_time'));
        $openSeconds = (int) ($parts[0] ?? 0) * 3600 + (int) ($parts[1] ?? 0) * 60;

        $slot = ReservationSlot::withDeleted()
            ->where('tb_staff_cd', $staffCd)
            ->where('moushikomi_date', $date)
            ->first();

        // Sin franja, el legacy pintaba una sola fila (la de apertura) vacía
        $timeCount = 0;
        $rows = [];
        if ($slot !== null) {
            $timeCount = (int) floor((60 / $interval) * (($slot->end_time - $openSeconds) / 3600));
            $grid = $this->grid($slot, $shopCd, $date, $openSeconds, $interval, $timeCount, $menuCds);
            $rows = $this->rows($slot, $grid, $openSeconds, $interval, $timeCount, $totalMinutes, $requestedPeople);
        } else {
            $rows[] = ['n' => 0, 'time' => ReservationSlotService::clock($openSeconds), 'hhmm' => str_replace(':', '', ReservationSlotService::clock($openSeconds)), 'plus' => false, 'status' => 'none', 'people' => 0, 'error' => ''];
        }

        return compact('date', 'slot', 'interval', 'timeCount', 'totalMinutes', 'rows') + ['day' => $day];
    }

    /**
     * Estado de cada paso: ['state' => true|false|'休憩', 'people' => int, 'setsubi' => ['accepted' => bool, 'error' => string]]
     */
    private function grid(ReservationSlot $slot, int $shopCd, string $date, int $openSeconds, int $interval, int $timeCount, array $menuCds): array
    {
        [$breakStarts, $breakEnds] = $this->breaks($slot);
        $setsubi = $this->facilityContext($shopCd, $menuCds);

        $grid = [];
        for ($n = 0; $n <= $timeCount; $n++) {
            $time = $openSeconds + $n * $interval * 60;

            foreach ($breakStarts as $i => $start) {
                if ($time >= $start && $time < $breakEnds[$i]) {
                    $grid[$n] = ['state' => '休憩', 'people' => 9999, 'setsubi' => null];

                    continue 2;
                }
            }

            $grid[$n] = [
                'state' => ! ($time < $slot->start_time || $time >= $slot->end_time),
                'people' => (int) DB::table('tb_yoyaku')
                    ->where('disp_flg', '1')
                    ->where('tb_yoyaku_uketsuke_day_cd', $slot->cd)
                    ->where('start_time', '<=', $time)
                    ->where('end_time', '>', $time)
                    ->sum('people'),
                'setsubi' => $setsubi === null ? null : $this->facilityState($setsubi, $shopCd, $date, $time),
            ];
        }

        return $grid;
    }

    private function rows(ReservationSlot $slot, array $grid, int $openSeconds, int $interval, int $timeCount, int $totalMinutes, mixed $requestedPeople): array
    {
        $people = is_numeric($requestedPeople) ? (float) $requestedPeople : 0;
        $steps = (int) ceil($totalMinutes / $interval);
        $rows = [];

        for ($n = 0; $n <= $timeCount; $n++) {
            $time = $openSeconds + $n * $interval * 60;
            $clock = ReservationSlotService::clock($time);
            $row = ['n' => $n, 'time' => $clock, 'hhmm' => str_replace(':', '', $clock), 'plus' => $time >= 86400, 'people' => $grid[$n]['people'], 'error' => ''];

            if ($grid[$n]['state'] === false) {
                $rows[] = $row + ['status' => 'closed'];

                continue;
            }

            $overflowPeople = $overflowSetsubi = false;
            for ($x = 0; $x < $steps; $x++) {
                $check = $n + $x;
                if ($check > $timeCount) {
                    break;
                }
                if ($people + $grid[$check]['people'] > (int) $slot->people || ! $grid[$check]['state']) {
                    $overflowPeople = true;
                    break;
                }
                if (($grid[$check]['setsubi']['accepted'] ?? true) === false) {
                    $overflowSetsubi = true;
                    $row['error'] = $grid[$check]['setsubi']['error'];
                    break;
                }
            }

            $row['status'] = match (true) {
                $grid[$n]['state'] === '休憩' => 'break',
                $overflowSetsubi => 'setsubi',
                $overflowPeople => 'full',
                default => 'open',
            };
            $rows[] = $row;
        }

        return $rows;
    }

    /** Descansos de la franja ('HH:MM' unidos por '<>') en segundos */
    private function breaks(ReservationSlot $slot): array
    {
        $starts = $ends = [];
        if ((string) $slot->start_break_time === '') {
            return [$starts, $ends];
        }
        $endList = explode('<>', (string) $slot->end_break_time);
        foreach (explode('<>', (string) $slot->start_break_time) as $i => $start) {
            $s = array_pad(explode(':', $start), 2, 0);
            $e = array_pad(explode(':', $endList[$i] ?? ''), 2, 0);
            $starts[] = (int) $s[0] * 3600 + (int) $s[1] * 60;
            $ends[] = (int) $e[0] * 3600 + (int) $e[1] * 60;
        }

        return [$starts, $ends];
    }

    /** 予約設備 (admin_master.yoyaku_setsubi_flg = '1'): aforo de la tienda, existencias y equipo de cada menú */
    private function facilityContext(int $shopCd, array $menuCds): ?array
    {
        if ((string) AdminMaster::current()->yoyaku_setsubi_flg !== '1') {
            return null;
        }

        $menuFacility = DB::table('tb_yoyaku_koumoku_master')->where('tb_shop_cd', $shopCd)->orderBy('cd')->pluck('tb_setsubi_cd', 'cd')->all();
        $mine = [];
        foreach ($menuCds as $cd) {
            $facility = $menuFacility[$cd] ?? null;
            if ($cd && $facility && ! in_array($facility, $mine)) {
                $mine[] = $facility; // varios menús con el mismo equipo cuentan 1
            }
        }

        return [
            'capacity' => (int) DB::table('tb_yoyaku_config')->where('tb_shop_cd', $shopCd)->value('setsubi_capacity'),
            'quantity' => DB::table('tb_setsubi')->where('tb_shop_cd', $shopCd)->where('disp_flg', '1')->orderBy('cd')->pluck('quantity', 'cd')->all(),
            'menuFacility' => $menuFacility,
            'mine' => $mine,
        ];
    }

    /** Estado del 設備 a una hora: reservas de la tienda ese día en curso a esa hora */
    private function facilityState(array $ctx, int $shopCd, string $date, int $time): array
    {
        $reservations = DB::table('tb_yoyaku as tb1')
            ->join('tb_yoyaku_uketsuke_day as tb2', 'tb1.tb_yoyaku_uketsuke_day_cd', '=', 'tb2.cd')
            ->join('tb_staff as tb3', 'tb2.tb_staff_cd', '=', 'tb3.cd')
            ->where('tb1.disp_flg', '1')
            ->where('tb1.start_time', '<=', $time)
            ->where('tb1.end_time', '>', $time)
            ->where('tb2.moushikomi_date', $date)
            ->where('tb3.shop_cd', $shopCd)
            ->get(['tb1.tb_customer_cd', 'tb1.base_tb_yoyaku_koumoku_master_cd', 'tb1.option_tb_yoyaku_koumoku_master_cd']);

        if ($reservations->count() >= $ctx['capacity']) {
            return ['accepted' => false, 'error' => '店舗キャパオーバー'];
        }

        $used = [];
        $customerFacility = []; // el mismo socio con el mismo equipo en varias reservas cuenta 1
        foreach ($reservations as $reservation) {
            $menus = array_merge(
                $reservation->base_tb_yoyaku_koumoku_master_cd ? explode(',', $reservation->base_tb_yoyaku_koumoku_master_cd) : [],
                $reservation->option_tb_yoyaku_koumoku_master_cd ? explode(',', $reservation->option_tb_yoyaku_koumoku_master_cd) : [],
            );
            $thisLoop = [];
            foreach ($menus as $menuCd) {
                $facility = $ctx['menuFacility'][$menuCd] ?? null;
                if ($menuCd && $facility && ! in_array($facility, $thisLoop)
                    && ($customerFacility[$reservation->tb_customer_cd][$facility] ?? '') !== '1') {
                    $thisLoop[] = $facility;
                    if ((string) $reservation->tb_customer_cd !== '') {
                        $customerFacility[$reservation->tb_customer_cd][$facility] = '1';
                    }
                }
            }
            foreach ($thisLoop as $facility) {
                $used[$facility] = ($used[$facility] ?? 0) + 1;
            }
        }

        foreach ($used as $facility => $count) {
            if (in_array($facility, $ctx['mine']) && $count + 1 > (int) ($ctx['quantity'][$facility] ?? 0)) {
                return ['accepted' => false, 'error' => '設備数オーバー'];
            }
        }

        return ['accepted' => true, 'error' => ''];
    }
}
