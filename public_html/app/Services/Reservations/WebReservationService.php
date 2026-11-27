<?php

namespace App\Services\Reservations;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Reserva web pública (`r/`, alias `reserve/`): port de `r/class/Reserve.php` y `r/class/Timetable.php`.
 *
 * Las estructuras que salen en JSON (`ajax_getChoiceData.php`, `ajax_getScheduleData.php`) conservan las claves,
 * el orden y los tipos del legacy: las filas de BD van como texto (pg_fetch_assoc), `weekNum` como float (ceil()),
 * los UT como int. Fechas con Carbon Asia/Tokyo (strtotime()/date() del legacy con TZ=Asia/Tokyo).
 * Distinto del 時間選択 de la administración (AvailabilityService): aquí cuentan el aforo de la tienda
 * (`tb_yoyaku_config.setsubi_capacity`), el del empleado (`tb_yoyaku_uketsuke_day.people`), descansos,
 * 〆切 y 設備, como Timetable::getScheduleData().
 */
class WebReservationService
{
    public const YOUBI = ['日', '月', '火', '水', '木', '金', '土'];

    public const DATE_PATTERN = '/^[0-9]{4}(\/|-)[0-9]{2}(\/|-)[0-9]{2}$/';

    public const CD_PATTERN = '/^[1-9][0-9]*$/';

    // ---------------------------------------------------------------- utilidades de fecha (Reserve.php)

    /** strtotime() con TZ Asia/Tokyo (respeta Carbon::setTestNow); false si no se entiende */
    public static function ut(string $value): int|false
    {
        try {
            return Carbon::parse(str_replace('/', '-', $value), 'Asia/Tokyo')->getTimestamp();
        } catch (\Throwable) {
            return false;
        }
    }

    public static function now(): int
    {
        return Carbon::now('Asia/Tokyo')->getTimestamp();
    }

    /** date($format, $ut) en Asia/Tokyo */
    public static function date(string $format, int $ut): string
    {
        return Carbon::createFromTimestamp($ut, 'Asia/Tokyo')->format($format);
    }

    /** getRealTime(): '2016-03-24', '29:00' → epoch de 2016-03-25 05:00 */
    public function realTime(string $date, ?string $hm): int
    {
        [$h, $m] = array_pad(explode(':', (string) $hm), 2, null);

        return (int) self::ut($date) + (int) $h * 3600 + (int) $m * 60;
    }

    /** getDisplayTimeFromSecs(): 99000 → '27:30' */
    public function displayTimeFromSecs(mixed $totalSeconds): string
    {
        $totalSeconds = (int) $totalSeconds;
        $hour = intdiv($totalSeconds, 3600);
        $minute = intdiv($totalSeconds - $hour * 3600, 60);

        return sprintf('%02s', $hour).':'.sprintf('%02s', $minute);
    }

    /** getDateList(): ['Y-m-d' => ['youbi', 'dow', 'weekNum']] */
    public function dateList(string $startDate, string $endDate): array
    {
        $list = [];
        $day = Carbon::createFromTimestamp((int) self::ut($startDate), 'Asia/Tokyo');
        $end = (int) self::ut($endDate);
        do {
            $list[$day->format('Y-m-d')] = [
                'youbi' => self::YOUBI[$day->dayOfWeek],
                'dow' => strtolower($day->format('D')),
                'weekNum' => ceil($day->day / 7), // float, como el legacy
            ];
            $day = $day->addDay();
        } while ($day->getTimestamp() <= $end);

        return $list;
    }

    /** Fila de BD como la devuelve pg_fetch_assoc(): todo texto salvo null */
    public static function pgRow(object|array $row): array
    {
        return array_map(fn ($v) => match (true) {
            $v === null => null,
            is_bool($v) => $v ? 't' : 'f',
            default => (string) $v,
        }, (array) $row);
    }

    // ---------------------------------------------------------------- lecturas (Reserve.php)

    /** getConfig(): `tb_yoyaku_config` de la tienda + `admin_master.yoyaku_time_interval` ([] si no hay) */
    public function config(mixed $shopCd): array
    {
        $row = DB::selectOne('SELECT t1.*, t2.yoyaku_time_interval FROM tb_yoyaku_config t1, admin_master t2 WHERE t1.tb_shop_cd = ? AND t2.cd = 1', [(int) $shopCd]);

        return $row ? self::pgRow($row) : [];
    }

    /** `admin_master` cd 1 como array (`$crmConfigArray`) */
    public function crmConfig(): array
    {
        $row = DB::table('admin_master')->where('cd', 1)->first();

        return $row ? self::pgRow($row) : [];
    }

    /** getShopDateSchedule(): horario de la tienda (tb_yoyaku_config, por defecto 05:00-29:00) por día */
    public function shopDateSchedule(mixed $shopCd, mixed $startDate, mixed $endDate): array|false
    {
        if (! preg_match(self::CD_PATTERN, (string) $shopCd) || ! preg_match(self::DATE_PATTERN, (string) $startDate) || ! preg_match(self::DATE_PATTERN, (string) $endDate)) {
            return false;
        }
        if (self::ut($endDate) < self::ut($startDate)) {
            return false;
        }

        $config = DB::table('tb_yoyaku_config')->where('tb_shop_cd', (int) $shopCd)->first(['start_time', 'end_time']);
        $st = (string) ($config->start_time ?? '') ?: '05:00';
        $et = (string) ($config->end_time ?? '') ?: '29:00';

        $list = $this->dateList($startDate, $endDate);
        foreach ($list as $date => $detail) {
            $list[$date]['flg'] = '1'; // PROにはこの設定がないため全て営業にする
            $list[$date]['startTime'] = $st;
            $list[$date]['startTimeUT'] = $this->realTime($date, $st);
            $list[$date]['endTime'] = $et;
            $list[$date]['endTimeUT'] = $this->realTime($date, $et);
        }

        return $list;
    }

    /** getStaffDateShift(): franjas (受付日) de los empleados reservables de la tienda por día */
    public function staffDateShift(mixed $shopCd, mixed $startDate, mixed $endDate, mixed $staffCd = null): array|false
    {
        if (! preg_match(self::CD_PATTERN, (string) $shopCd) || ! preg_match(self::DATE_PATTERN, (string) $startDate) || ! preg_match(self::DATE_PATTERN, (string) $endDate)) {
            return false;
        }
        if ($staffCd && ! preg_match(self::CD_PATTERN, (string) $staffCd)) {
            return false;
        }
        if (self::ut($endDate) < self::ut($startDate)) {
            return false;
        }

        $list = $this->dateList($startDate, $endDate);
        foreach ($list as $key => $val) {
            $list[$key]['staff'] = [];
        }

        $bindings = [$startDate, $endDate, (int) $shopCd];
        $staffWhere = '';
        if ($staffCd) {
            $staffWhere = 'AND t1.tb_staff_cd = ?';
            $bindings[] = (int) $staffCd;
        }
        $rows = DB::select(<<<SQL
            SELECT t2.cd, t2.name, t2.email1, t1.cd AS tb_yoyaku_uketsuke_day_cd, t1.moushikomi_date::DATE AS moushikomi_date,
                t1.status_flg, t1.start_time, t1.end_time, t1.people, t1.start_break_time, t1.end_break_time
            FROM tb_yoyaku_uketsuke_day t1
            INNER JOIN tb_staff t2 ON t1.tb_staff_cd = t2.cd
            LEFT JOIN tb_free_yoyaku_rank t3 ON t3.tb_staff_cd = t2.cd
            WHERE t1.moushikomi_date ~ '[0-9]{4}(\-|\/)[0-9]{2}(\-|\/)[0-9]{2}'
                AND t1.moushikomi_date::DATE BETWEEN ?::DATE AND ?::DATE
                AND t1.disp_flg = '1' AND t1.hyouji_flg = '1'
                AND t2.disp_flg = '1' AND t2.reserve_flg = '1'
                AND t2.cd IN (SELECT tb_staff_cd FROM tb_staff_works WHERE disp_flg = '1')
                AND t2.shop_cd = ?
                {$staffWhere}
            ORDER BY t3.rank, COALESCE(t2.common_flg, ''), COALESCE(t2.rank, 0) DESC, t2.cd, t1.moushikomi_date::DATE
            SQL, $bindings);

        foreach ($rows as $row) {
            $row = self::pgRow($row);
            $date = $row['moushikomi_date'];
            if (! isset($list[$date]) || isset($list[$date]['staff'][$row['cd']])) {
                continue; // tb_yoyaku_uketsuke_day重複対策
            }
            $shift = [
                'name' => $row['name'],
                'email1' => $row['email1'],
                'startTime' => $this->displayTimeFromSecs($row['start_time']),
                'endTime' => $this->displayTimeFromSecs($row['end_time']),
                'capacity' => max((int) $row['people'], 1),
                'nominationFlg' => 0, // el legacy leía reserve_flg sin seleccionarlo
                'tb_yoyaku_uketsuke_day_cd' => $row['tb_yoyaku_uketsuke_day_cd'],
                'status_flg' => $row['status_flg'],
            ];
            $breakStart = explode('<>', (string) $row['start_break_time']);
            $breakEnd = explode('<>', (string) $row['end_break_time']);
            foreach ($breakStart as $i => $start) {
                if ($start && ($breakEnd[$i] ?? null)) {
                    $shift['breakTime'][] = ['start' => $start, 'end' => $breakEnd[$i]];
                }
            }
            $list[$date]['staff'][$row['cd']] = $shift;
        }

        return $list;
    }

    /** getShopStaff(): empleados reservables de la tienda (con algún 施術) */
    public function shopStaff(mixed $shopCd, mixed $staffCd = null): array|false
    {
        if (! preg_match(self::CD_PATTERN, (string) $shopCd) || ($staffCd && ! preg_match(self::CD_PATTERN, (string) $staffCd))) {
            return false;
        }

        return DB::table('tb_staff')
            ->select('cd', 'name', 'pic_name')
            ->where('shop_cd', (int) $shopCd)
            ->where('disp_flg', '1')
            ->where('reserve_flg', '1')
            ->whereIn('cd', fn ($q) => $q->select('tb_staff_cd')->from('tb_staff_works'))
            ->when($staffCd, fn ($q) => $q->where('cd', (int) $staffCd))
            ->orderByRaw("COALESCE(common_flg, ''), rank, cd")
            ->get()
            ->map(fn ($row) => self::pgRow($row))
            ->all();
    }

    /** getStaffMenu(): [staff_cd => [menu_cd => fila]] menús reservables por empleado */
    public function staffMenu(mixed $shopCd, mixed $staffCd = null): array|false
    {
        if (! preg_match(self::CD_PATTERN, (string) $shopCd) || ($staffCd && ! preg_match(self::CD_PATTERN, (string) $staffCd))) {
            return false;
        }

        $bindings = [(int) $shopCd, (int) $shopCd];
        $where = '';
        if ($staffCd) {
            $where = 'AND t1.tb_staff_cd = ?';
            $bindings[] = (int) $staffCd;
        }
        $rows = DB::select(<<<SQL
            SELECT t1.tb_staff_cd, t2.cd AS menu_cd, t1.shoyou_time AS time, t2.name AS title, t2.type_flg, t3.name AS category_name
            FROM tb_staff_works AS t1
            INNER JOIN tb_yoyaku_koumoku_master AS t2 ON t1.tb_yoyaku_koumoku_master_cd = t2.cd
            LEFT JOIN tb_yoyaku_koumoku_category AS t3 ON t2.tb_yoyaku_koumoku_category_cd = t3.cd
            INNER JOIN tb_staff AS t4 ON t4.cd = t1.tb_staff_cd
            WHERE t1.disp_flg = '1' AND t1.hyouji_flg = '1'
                AND (t1.mypage_flg IS NULL OR t1.mypage_flg = '')
                AND t2.disp_flg = '1' AND t2.hyouji_flg = '1'
                AND t2.tb_shop_cd IN (?, -1)
                AND COALESCE(t3.disp_flg, '') <> '9'
                AND (t2.tb_yoyaku_koumoku_category_cd IS NULL OR t3.hyouji_flg = '1')
                AND t4.reserve_flg = '1'
                AND t4.shop_cd = ?
                {$where}
            ORDER BY t2.type_flg, t3.rank, t3.cd, t2.tb_shop_cd DESC, t2.rank, t2.cd
            SQL, $bindings);

        $menu = [];
        foreach ($rows as $row) {
            $row = self::pgRow($row);
            $menu[$row['tb_staff_cd']][$row['menu_cd']] = $row;
        }

        return $menu;
    }

    /** getStaffReservationDateList(): ['Y-m-d' => [staff_cd => [reserva…]]] */
    public function staffReservationDateList(mixed $shopCd, mixed $startDate, mixed $endDate): array|false
    {
        if (! preg_match(self::CD_PATTERN, (string) $shopCd) || ! preg_match(self::DATE_PATTERN, (string) $startDate) || ! preg_match(self::DATE_PATTERN, (string) $endDate)) {
            return false;
        }

        $list = [];
        $reserves = $this->reserves('tb3.shop_cd = ? AND tb2.moushikomi_date::DATE BETWEEN ?::DATE AND ?::DATE', [(int) $shopCd, $startDate, $endDate]);
        foreach ($reserves as $row) {
            $row['menu'] = array_filter(explode(',', $row['base_tb_yoyaku_koumoku_master_cd'].','.$row['option_tb_yoyaku_koumoku_master_cd']));
            $list[$row['reservation_date']][$row['tb_staff_cd']][] = $row;
        }

        return $list;
    }

    /** getCustomerActiveReserves(): reservas vigentes del socio desde ahora */
    public function customerActiveReserves(mixed $customerCd): array
    {
        if (! preg_match('/^[0-9]+$/', (string) $customerCd)) {
            return []; // el legacy fallaba la consulta (sin socio) y devolvía []
        }
        $now = self::now();

        return array_values(array_filter(
            $this->reserves('tb2.moushikomi_date::DATE >= ?::DATE AND tb1.tb_customer_cd = ?', [self::date('Y-m-d', $now), (int) $customerCd]),
            fn ($row) => $row['realStartTimeUT'] > $now,
        ));
    }

    /** getReserveByCd() */
    public function reserveByCd(mixed $reserveCd): array|false
    {
        if (! preg_match(self::CD_PATTERN, (string) $reserveCd)) {
            return false;
        }

        return $this->reserves('tb1.cd = ?', [(int) $reserveCd])[0] ?? false;
    }

    /** _getReserves(): reservas vigentes (disp_flg = '1') con franja, empleado, tienda y socio */
    private function reserves(string $where, array $bindings): array
    {
        $rows = DB::select(<<<SQL
            SELECT tb1.cd, tb1.tb_customer_cd, tb3.shop_cd, tb2.moushikomi_date::DATE AS reservation_date,
                CASE WHEN tb1.tb_customer_cd > 0 THEN tb5.name ELSE tb1.name END AS customer_name,
                CASE WHEN tb1.tb_customer_cd > 0 THEN tb5.name_kana ELSE tb1.name_kana END AS customer_name_kana,
                CASE WHEN tb1.tb_customer_cd > 0 AND COALESCE(tb1.tel, '') = '' THEN tb5.tel ELSE tb1.tel END AS customer_tel,
                CASE WHEN tb1.tb_customer_cd > 0 THEN tb5.email1 ELSE tb1.mail END AS customer_mail,
                tb1.people, tb1.free_koumoku, tb2.tb_staff_cd, tb3.name AS staff_name, tb3.email1 AS staff_mail,
                tb1.start_time, tb1.end_time, tb1.base_tb_yoyaku_koumoku_master_cd, tb1.option_tb_yoyaku_koumoku_master_cd,
                tb4.kaishamei AS shop_name, tb4.tel AS shop_tel, tb1.entry_type
            FROM tb_yoyaku tb1
            INNER JOIN tb_yoyaku_uketsuke_day tb2 ON tb1.tb_yoyaku_uketsuke_day_cd = tb2.cd
            INNER JOIN tb_staff tb3 ON tb2.tb_staff_cd = tb3.cd
            INNER JOIN tb_shop tb4 ON tb3.shop_cd = tb4.cd
            LEFT JOIN tb_customer tb5 ON tb1.tb_customer_cd = tb5.cd
            WHERE tb1.disp_flg = '1'
                AND tb2.moushikomi_date ~ '[0-9]{4}(\-|\/)[0-9]{2}(\-|\/)[0-9]{2}'
                AND {$where}
            ORDER BY reservation_date, start_time
            SQL, $bindings);

        return array_map(function ($row) {
            $row = self::pgRow($row);
            $day = (int) self::ut($row['reservation_date']);
            $row['youbi'] = self::YOUBI[(int) self::date('w', $day)];
            $row['realStartTimeUT'] = $day + (int) $row['start_time'];
            $row['realEndTimeUT'] = $day + (int) $row['end_time'];
            $row['displayStartTime'] = $this->displayTimeFromSecs($row['start_time']);
            $row['displayEndTime'] = $this->displayTimeFromSecs($row['end_time']);
            $row['requiredTime'] = ((int) $row['end_time'] - (int) $row['start_time']) / 60;

            return $row;
        }, $rows);
    }

    // ---------------------------------------------------------------- huecos (Timetable.php)

    /** calcFrameObjects(): máximo de reservas simultáneas en [start, end) y equipos (設備) en uso */
    public function calcFrameObjects(int $startUT, int $endUT, array $reservationList, array $equipmentWorks): array
    {
        $resList = $equipList = $equipmentInUse = [];
        $maxPeople = 0;
        usort($reservationList, fn ($a, $b) => strcmp((string) $a['realStartTimeUT'], (string) $b['realStartTimeUT']));
        foreach ($reservationList as $reserve) {
            $sUT = $reserve['realStartTimeUT'];
            $eUT = $reserve['realEndTimeUT'];
            if ($eUT <= $startUT || $sUT >= $endUT) {
                continue; // 範囲外の予約を無視する
            }

            $row = 0;
            do {
                $ok = true;
                foreach ($resList[$row] ?? [] as $ob) {
                    if ($ob['sUT'] < $eUT && $ob['eUT'] > $sUT) {
                        $ok = false;
                        $row++;
                        break;
                    }
                }
            } while (! $ok);
            $resList[$row][] = ['sUT' => $sUT, 'eUT' => $eUT];

            foreach ((array) ($reserve['menu'] ?? []) as $m) {
                $equip = $equipmentWorks[$m]['tb_setsubi_cd'] ?? null;
                if (! $equip) {
                    continue; // 設備不要なメニュー
                }
                if (! in_array($equip, $equipList[$row] ?? [])) {
                    $equipList[$row][] = $equip;
                    $equipmentInUse[$equip] = ($equipmentInUse[$equip] ?? 0) + 1;
                }
            }

            $maxPeople = max($maxPeople, $row + 1);
        }

        return ['maxPeople' => $maxPeople, 'equipmentInUse' => $equipmentInUse];
    }

    /**
     * getScheduleData(): huecos reservables del periodo.
     * $params: shop_cd, start_date, end_date, staff_cd?, menu_cd[]?, listCandidates ('1' = lista de empleados por hueco).
     *
     * @return array{schedule: array|false, requiredTime: ?array, untilReserveDate: string}
     */
    public function scheduleData(array $params, array $crmConfig): array
    {
        $now = self::now();
        $shopCd = $params['shop_cd'] ?? null;
        $staffCd = $params['staff_cd'] ?? null;
        $menuCds = $params['menu_cd'] ?? null;
        $hasMenu = isset($params['menu_cd']);
        $config = $this->config($shopCd);

        // WEB予約可能上限（日数）
        $untilReserveDate = self::date('Y-m-d', $now + 365 * 86400);

        $increment = (int) ($config['yoyaku_time_interval'] ?? 0);
        if ($increment <= 0) {
            $increment = 30;
        }
        $shopCapacity = (int) ($config['setsubi_capacity'] ?? 0);

        // 設備
        $equipmentCapacity = $equipmentWorks = [];
        $checkEquipment = (int) ($crmConfig['yoyaku_setsubi_flg'] ?? 0) === 1;
        if ($checkEquipment) {
            $equipmentCapacity = DB::table('tb_setsubi')->where('disp_flg', '1')->where('tb_shop_cd', (int) $shopCd)->pluck('quantity', 'cd')->all();
            foreach (DB::table('tb_yoyaku_koumoku_master')->where('disp_flg', '1')->whereIn('tb_shop_cd', [(int) $shopCd, -1])
                ->get(['cd', 'tb_setsubi_cd', 'menu_end_time_flg', 'menu_end_time']) as $row) {
                $equipmentWorks[$row->cd] = self::pgRow($row);
            }
        }

        // 予約可能開始日時（〆切日・〆切り時間）
        $minReserveUT = (int) ($config['shimekiri'] ?? 0) > 0
            ? (int) self::ut(self::date('Y-m-d', $now + 86400 * (int) $config['shimekiri']))
            : $now + (int) ($config['shimekiri_time'] ?? 0) * 3600;

        $shopDateList = $this->shopDateSchedule($shopCd, $params['start_date'] ?? null, $params['end_date'] ?? null);

        $startTime = $endTime = null;
        foreach ($shopDateList ?: [] as $dateList) {
            if (! $startTime || $startTime > $dateList['startTime']) {
                $startTime = $dateList['startTime'];
            }
            if (! $endTime || $endTime < $dateList['endTime']) {
                $endTime = $dateList['endTime'];
            }
        }

        // タイムラインのテンプレート
        $st = (string) $startTime;
        $timetable = [$st];
        do {
            $t = array_pad(explode(':', $st), 2, 0);
            $mm = (int) $t[1] + $increment;
            $st = sprintf('%02s', (int) $t[0] + intdiv($mm, 60)).':'.sprintf('%02s', $mm % 60);
            $timetable[] = $st;
        } while ($st < (string) $endTime);

        $staffDateList = $this->staffDateShift($shopCd, $params['start_date'] ?? null, $params['end_date'] ?? null, $staffCd) ?: [];
        $staffMenu = $this->staffMenu($shopCd, $staffCd) ?: [];

        // 所要時間
        $staffReqTime = [];
        if ($hasMenu) {
            foreach ($staffMenu as $sCd => $menus) {
                $reqTime = 0;
                foreach ((array) $menuCds as $val) {
                    if (! isset($menus[$val])) {
                        continue 2; // スタッフができないメニューならば次のスタッフ
                    }
                    $reqTime += (int) $menus[$val]['time'];
                }
                $staffReqTime[$sCd] = $reqTime;
            }
        }

        $staffReservationDateList = $this->staffReservationDateList($shopCd, $params['start_date'] ?? null, $params['end_date'] ?? null) ?: [];
        $reservationDateList = [];
        foreach ($staffReservationDateList as $date => $staffReservations) {
            foreach ($staffReservations as $res) {
                foreach ($res as $val) {
                    $reservationDateList[$date][] = $val;
                }
            }
        }

        // 予約するメニュー: 設備と最終受付時刻
        $requiredEquipment = [];
        $maxMenuTime = null;
        if ($checkEquipment && $hasMenu) {
            foreach ((array) $menuCds as $val) {
                $work = $equipmentWorks[$val] ?? [];
                if (($work['tb_setsubi_cd'] ?? null) && ! in_array($work['tb_setsubi_cd'], $requiredEquipment)) {
                    $requiredEquipment[] = $work['tb_setsubi_cd'];
                }
                if (($work['menu_end_time_flg'] ?? null) == '1' && ($work['menu_end_time'] ?? null)) {
                    $maxMenuTime = $maxMenuTime ? min($maxMenuTime, $work['menu_end_time']) : $work['menu_end_time'];
                }
            }
        }

        $dayTimetable = [];
        foreach ($timetable as $val) {
            $dayTimetable[$val] = ['available' => false];
        }

        $reqTimeList = null;
        $listCandidates = ($params['listCandidates'] ?? null) == '1';
        $minReserveDate = self::date('Y-m-d', $minReserveUT);

        foreach ($shopDateList ?: [] as $date => $daySchedule) {
            $shopDateList[$date]['timetable'] = $dayTimetable;

            if ($date < $minReserveDate || $date > $untilReserveDate || $daySchedule['flg'] != 1) {
                continue;
            }

            $staffList = $staffDateList[$date]['staff'] ?? [];
            $reservationList = $reservationDateList[$date] ?? [];
            $maxReserveUT = $maxMenuTime ? (int) self::ut($date) + (int) $maxMenuTime : null;

            foreach ($timetable as $st) {
                $startUT = $this->realTime($date, $st);
                $endUT = $startUT + $increment * 60;

                if ($startUT < $daySchedule['startTimeUT']) {
                    continue;
                }
                if ($endUT > $daySchedule['endTimeUT']) {
                    break;
                }
                if ($startUT < $minReserveUT) {
                    continue;
                }
                if ($maxReserveUT && $startUT > $maxReserveUT) {
                    break;
                }

                foreach ($staffList as $sCd => $staffData) {
                    // 状況: '' 受付中, '3' 締切間近 (el '0' que guarda el admin nuevo también es 受付中)
                    if (! in_array((string) $staffData['status_flg'], ['', '0', '3'], true)) {
                        continue;
                    }

                    $staffStartUT = $this->realTime($date, $staffData['startTime']);
                    $staffEndUT = $this->realTime($date, $staffData['endTime']);
                    if ($startUT < $staffStartUT) {
                        continue;
                    }
                    if ($hasMenu && empty($staffReqTime[$sCd])) {
                        continue; // 選択されたメニュはスタッフがされない
                    }
                    $reqTime = (int) (ceil(($staffReqTime[$sCd] ?? 0) / $increment) * $increment);

                    $staffEndThisUT = $startUT + $reqTime * 60;
                    if ($staffEndThisUT > $daySchedule['endTimeUT'] || $staffEndThisUT > $staffEndUT) {
                        continue;
                    }

                    // 店舗キャパ
                    $frame = $this->calcFrameObjects($startUT, $staffEndThisUT, $reservationList, $equipmentWorks);
                    if ($frame['maxPeople'] >= $shopCapacity) {
                        continue;
                    }

                    // 設備キャパ
                    if ($requiredEquipment && $frame['equipmentInUse']) {
                        foreach ($requiredEquipment as $equipmentCd) {
                            if (isset($frame['equipmentInUse'][$equipmentCd]) && $frame['equipmentInUse'][$equipmentCd] >= ($equipmentCapacity[$equipmentCd] ?? 0)) {
                                continue 2;
                            }
                        }
                    }

                    // スタッフ休憩
                    foreach ($staffData['breakTime'] ?? [] as $break) {
                        if ($this->realTime($date, $break['start']) < $staffEndThisUT && $this->realTime($date, $break['end']) > $startUT) {
                            continue 2;
                        }
                    }

                    // スタッフキャパ
                    if (isset($staffReservationDateList[$date][$sCd])) {
                        $frame = $this->calcFrameObjects($startUT, $staffEndThisUT, $staffReservationDateList[$date][$sCd], $equipmentWorks);
                        if ($frame['maxPeople'] >= $staffData['capacity']) {
                            continue;
                        }
                    }

                    $shopDateList[$date]['timetable'][$st]['available'] = true;
                    if (! isset($reqTimeList[$sCd])) {
                        $reqTimeList[$sCd] = $staffReqTime[$sCd] ?? null;
                    }
                    if (! $listCandidates) {
                        break; // 一人でもできるならばこの枠をtrueにして次へ
                    }
                    $shopDateList[$date]['timetable'][$st]['candidates'][] = $sCd;
                }
            }
        }

        return ['schedule' => $shopDateList, 'requiredTime' => $reqTimeList, 'untilReserveDate' => $untilReserveDate];
    }

    /**
     * QuickReservation::getReservationWaitTime() (r/getReservationWaitTime.php y appview クイック予約):
     * primer hueco de hoy (hasta las 5:00 cuenta como el día anterior) con el menú 0.
     * El legacy calculaba la hora con strtotime('Y-m-d HH:MM') y perdía los huecos de 24:00 en adelante;
     * aquí se usa la hora real.
     *
     * @return array{waitTime: int, minReservationUT: int, candidates: ?array}|false
     */
    public function waitTime(mixed $shopCd, mixed $staffCd = null): array|false
    {
        if (! preg_match(self::CD_PATTERN, (string) $shopCd) || ($staffCd && ! preg_match(self::CD_PATTERN, (string) $staffCd))) {
            return false;
        }

        $now = self::now();
        $target = (int) self::date('G', $now) < 5 ? $now - 86400 : $now;
        $today = self::date('Y-m-d', $target);
        $data = $this->scheduleData([
            'shop_cd' => $shopCd, 'staff_cd' => $staffCd, 'start_date' => $today, 'end_date' => $today,
            'menu_cd' => [0], 'listCandidates' => '1',
        ], []);

        $minReservationUT = 0;
        $candidates = null;
        foreach ($data['schedule'][$today]['timetable'] ?? [] as $time => $val) {
            if ($val['available']) {
                $minReservationUT = $this->realTime($today, (string) $time);
                $candidates = $val['candidates'] ?? null;
                break;
            }
        }

        if (! $minReservationUT) {
            return false;
        }

        return ['waitTime' => (int) (($minReservationUT - self::now()) / 60), 'minReservationUT' => $minReservationUT, 'candidates' => $candidates];
    }

    // ---------------------------------------------------------------- imágenes (ajax_getChoiceData.php)

    /** ¿Existe el archivo subido (disco public, hp/uploads/…)? y su fecha de modificación (0 si no) */
    public function uploadInfo(string $relative): array
    {
        $disk = Storage::disk('public');
        if (! $disk->exists($relative)) {
            return [false, 0];
        }

        return [true, (int) $disk->lastModified($relative)];
    }
}
