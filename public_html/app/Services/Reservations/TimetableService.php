<?php

namespace App\Services\Reservations;

use App\Models\Reservations\ReservationConfig;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * 予約表 (category9/yoyaku/time_table.php, time_table_staff.php y makeYoyakuTableRow() de time_table_function.php).
 *
 * Las horas de `tb_yoyaku`, `tb_yoyaku_uketsuke_day` y `tb_yoyaku_config` se tratan como segundos desde las 00:00
 * del día de la franja (pueden pasar de 86400). El legacy restaba 9 h a todo para usar date() sobre epoch UTC;
 * aquí se trabaja directamente en segundos: 1 px = 1 minuto, posición 0 = hora de apertura de `tb_yoyaku_config`.
 */
class TimetableService
{
    /** 状況 (status_flg) en el orden del <select> del legacy */
    public const STATUS_OPTIONS = [0 => '受付中', 1 => '電話受付', 3 => '締切間近', 2 => '受付終了'];

    public function __construct(private readonly ReservationFlowService $flow) {}

    // ---------------------------------------------------------------- configuración y cabecera

    /** Fila de `tb_yoyaku_config` de la tienda (null: 予約設定は未設定) */
    public function config(int $shopCd): ?ReservationConfig
    {
        return ReservationConfig::query()->where('tb_shop_cd', $shopCd)->first();
    }

    /**
     * Horario de la tienda y cabecera de horas: apertura y cierre en segundos, franjas de 30 min,
     * textos 'H:i' (vuelven a 00:00 tras medianoche, como date('H:i') del legacy).
     *
     * @return array{start: int, end: int, timeCount: int|float, timeLabels: list<string>, startsAtHalfHour: bool}
     */
    public function geometry(ReservationConfig $config): array
    {
        $startParts = explode(':', (string) $config->start_time);
        $endParts = explode(':', (string) $config->end_time);
        $start = (int) $startParts[0] * 3600 + (int) ($startParts[1] ?? 0) * 60;
        $end = (int) $endParts[0] * 3600 + (int) ($endParts[1] ?? 0) * 60;

        // 1時間に2個×時間数 (puede no ser entero si el horario no cae en media hora)
        $timeCount = ($end - $start) / 1800;
        $labels = [];
        for ($i = 0; $i <= $timeCount; $i++) {
            $labels[] = ReservationSlotService::clock($start + $i * 1800);
        }

        return [
            'start' => $start,
            'end' => $end,
            'timeCount' => $timeCount,
            'timeLabels' => $labels,
            'startsAtHalfHour' => ($startParts[1] ?? '') !== '00',
        ];
    }

    /** Segundos transcurridos hoy desde la apertura (barra .timeZone; puede ser negativo) */
    public function keikaByou(int $openSeconds, ?Carbon $now = null): int
    {
        $now ??= now('Asia/Tokyo');

        return $now->getTimestamp() - $now->copy()->startOfDay()->getTimestamp() - $openSeconds;
    }

    // ---------------------------------------------------------------- personal

    /**
     * Staff::getList() del 予約表: personal activo de la tienda con algún 施術 asignado (tb_staff_works),
     * orden COALESCE(common_flg, ''), rank, cd. Con `timeline_sort_type = '1'` primero los que tienen franja
     * vigente el día `$sortDate` ('Y/m/d').
     */
    public function staffList(int $shopCd, ?string $sortDate = null): Collection
    {
        $query = DB::table('tb_staff as st')
            ->select('st.cd', 'st.name')
            ->where('st.disp_flg', '1')
            ->where('st.shop_cd', $shopCd)
            ->whereIn('st.cd', fn ($sub) => $sub->select('tb_staff_cd')->from('tb_staff_works')->groupBy('tb_staff_cd'));

        if ($sortDate !== null) {
            $query->orderByRaw(
                "case when (select disp_flg from tb_yoyaku_uketsuke_day where tb_staff_cd = st.cd and disp_flg = '1' and moushikomi_date = ? group by 1) = '1' then 1 else 0 end desc",
                [$sortDate]
            );
        }

        return $query->orderByRaw("COALESCE(st.common_flg, '')")->orderBy('st.rank')->orderBy('st.cd')->get();
    }

    // ---------------------------------------------------------------- filas del 予約表

    /** `$menuData` del legacy: nombre de los 予約項目 activos de la tienda y comunes (-1), por cd */
    public function menuNames(int $shopCd): array
    {
        return DB::table('tb_yoyaku_koumoku_master')
            ->whereIn('tb_shop_cd', [$shopCd, -1])
            ->where('disp_flg', '1')
            ->orderBy('cd')
            ->pluck('name', 'cd')
            ->mapWithKeys(fn ($name, $cd) => [(string) $cd => (string) $name])
            ->all();
    }

    /**
     * Franjas (`tb_yoyaku_uketsuke_day`, sin mirar disp_flg como el legacy) de los empleados en esas fechas,
     * indexadas [fecha 'Y/m/d'][tb_staff_cd].
     *
     * @param  list<int>  $staffCds
     * @param  list<string>  $dates
     */
    public function slots(array $staffCds, array $dates): array
    {
        $list = [];
        if ($staffCds === [] || $dates === []) {
            return $list;
        }
        $rows = DB::table('tb_yoyaku_uketsuke_day')
            ->whereIn('tb_staff_cd', $staffCds)
            ->whereIn('moushikomi_date', $dates)
            ->orderBy('moushikomi_date')
            ->orderBy('cd')
            ->get();
        foreach ($rows as $row) {
            // Si hubiera dos del mismo día gana la última, como el while del legacy
            $list[(string) $row->moushikomi_date][(int) $row->tb_staff_cd] = $row;
        }

        return $list;
    }

    /**
     * Reservas vigentes (`disp_flg = '1'`) de esas franjas por fecha y empleado, orden start_time, cd.
     *
     * @param  list<int>  $staffCds
     * @param  list<string>  $dates
     * @return array<string, array<int, list<object>>>
     */
    public function reservations(array $staffCds, array $dates): array
    {
        $list = [];
        if ($staffCds === [] || $dates === []) {
            return $list;
        }
        $rows = DB::table('tb_yoyaku as tb1')
            ->join('tb_yoyaku_uketsuke_day as tb2', 'tb1.tb_yoyaku_uketsuke_day_cd', '=', 'tb2.cd')
            ->select('tb1.*', 'tb2.tb_staff_cd', 'tb2.moushikomi_date')
            ->where('tb1.disp_flg', '1')
            ->whereIn('tb2.tb_staff_cd', $staffCds)
            ->whereIn('tb2.moushikomi_date', $dates)
            ->orderBy('tb1.start_time')
            ->orderBy('tb1.cd')
            ->get();
        foreach ($rows as $row) {
            $list[(string) $row->moushikomi_date][(int) $row->tb_staff_cd][] = $row;
        }

        return $list;
    }

    /**
     * Celda de una franja: grises fuera de horario, 休憩 y reservas (makeYoyakuTableRow()).
     * Forma documentada en resources/views/admin/reservas/timetable.blade.php (`slot`).
     *
     * @param  list<object>  $reservations
     */
    public function slotCell(object $slot, array $reservations, array $geometry, array $menuNames, array $customers): array
    {
        $bars = $this->bars($reservations, $geometry['start'], $menuNames, $customers);

        return [
            'cd' => (int) $slot->cd,
            'status_flg' => $slot->status_flg,
            'noWorkLeft' => ((int) $slot->start_time - $geometry['start']) / 60,
            'noWorkRight' => ($geometry['end'] - (int) $slot->end_time) / 60 + 30,
            'breaks' => $this->breaks($slot, $geometry['start'], $geometry['end']),
            'bars' => $bars['bars'],
            'fillerHeight' => $bars['fillerHeight'],
        ];
    }

    /**
     * 休憩・外出 de la franja ('HH:MM' unidos por '<>') recortados al horario de la tienda; se saltan los de ancho <= 0.
     *
     * @return list<array{left: int|float, width: int|float}>
     */
    public function breaks(object $slot, int $open, int $close): array
    {
        if ((string) $slot->start_break_time === '') {
            return [];
        }
        $ends = explode('<>', (string) $slot->end_break_time);
        $breaks = [];
        foreach (explode('<>', (string) $slot->start_break_time) as $i => $startText) {
            [$sh, $sm] = array_pad(explode(':', $startText), 2, null);
            [$eh, $em] = array_pad(explode(':', $ends[$i] ?? ''), 2, null);
            $start = max((int) $sh * 3600 + (int) $sm * 60, $open);
            $end = min((int) $eh * 3600 + (int) $em * 60, $close);

            $width = ($end - $start) / 60;
            if ($width <= 0) {
                continue;
            }
            $breaks[] = ['left' => ($start - $open) / 60, 'width' => $width];
        }

        return $breaks;
    }

    /**
     * makeYoyakuTableRow(): barras de las reservas de una franja con su fila (top) cuando se solapan.
     * Forma de cada barra: resources/views/admin/reservas/partials/customer_bar.blade.php.
     *
     * @param  list<object>  $reservations  orden start_time, cd
     * @param  array<int, object>  $customers  tb_customer (name, tel) por cd
     * @return array{bars: list<array<string, mixed>>, fillerHeight: ?int}
     */
    public function bars(array $reservations, int $open, array $menuNames, array $customers): array
    {
        $freeFields = array_keys($this->flow->freeFields());
        $posArray = [];       // fin de cada fila ocupada del grupo que se solapa
        $posCntMax = 0;       // filas máximas (alto del div final)
        $preEndPos = 0;
        $bars = [];

        foreach ($reservations as $row) {
            $people = (int) $row->people;
            $startPos = ((int) $row->start_time - $open) / 60;
            $endPos = ((int) $row->end_time - $open) / 60;

            $top = 24 * count($posArray);
            if ($startPos >= $preEndPos) {
                $top = 0;
                $posArray = [];
            }
            $rowsUsed = $people < 2 ? 1 : 2;
            $posCntMax = max($posCntMax, count($posArray) + $rowsUsed);
            for ($m = 0; $m < $rowsUsed; $m++) {
                $posArray[] = $endPos;
            }

            // Socio: nombre de tb_customer; el teléfono de la reserva salvo que el socio no tenga
            $isKaiin = (bool) $row->tb_customer_cd;
            $name = (string) $row->name;
            $tel = (string) $row->tel;
            if ($isKaiin) {
                $customer = $customers[(int) $row->tb_customer_cd] ?? null;
                $name = (string) ($customer->name ?? '');
                if ((string) ($customer->tel ?? '') === '') {
                    $tel = 'データがありません。';
                }
            }

            $menus = [];
            foreach (['base_tb_yoyaku_koumoku_master_cd', 'option_tb_yoyaku_koumoku_master_cd'] as $column) {
                if ((string) $row->{$column} !== '') {
                    foreach (explode(',', (string) $row->{$column}) as $menuCd) {
                        $menus[] = $menuNames[$menuCd] ?? null;
                    }
                }
            }

            $values = explode('<>', (string) $row->free_koumoku);
            $free = array_map(fn (int $n) => rawurldecode($values[$n - 1] ?? ''), $freeFields);

            $bars[] = [
                'left' => $startPos,
                'top' => $top,
                'width' => ((int) $row->end_time - (int) $row->start_time) / 60 - 2,
                'height' => $people <= 1 ? 20 : 40,
                'isKaiin' => $isKaiin,
                'cd' => (int) $row->cd,
                'staff' => (int) $row->tb_staff_cd,
                'people' => max($people, 1),
                'peopleRaw' => $people,
                'mark' => (string) $row->mark,
                'entryType' => (string) $row->entry_type,
                'name' => $name,
                'tel' => $tel,
                'startTime' => ReservationSlotService::clock((int) $row->start_time),
                'endTime' => ReservationSlotService::clock((int) $row->end_time),
                'comment' => (string) $row->comment,
                'menuNames' => $menus,
                'freeKoumoku' => $free,
            ];

            if ($preEndPos < $endPos) {
                $preEndPos = $endPos;
            }
        }

        return ['bars' => $bars, 'fillerHeight' => $bars === [] ? null : $posCntMax * 24 + 2];
    }

    /**
     * `tb_customer` (name, tel) de los socios de las reservas (el legacy no mira disp_flg del socio).
     *
     * @param  array<string, array<int, list<object>>>  $reservations
     * @return array<int, object>
     */
    public function customersOf(array $reservations): array
    {
        $cds = [];
        foreach ($reservations as $byStaff) {
            foreach ($byStaff as $rows) {
                foreach ($rows as $row) {
                    if ($row->tb_customer_cd) {
                        $cds[(int) $row->tb_customer_cd] = true;
                    }
                }
            }
        }
        if ($cds === []) {
            return [];
        }

        return DB::table('tb_customer')->whereIn('cd', array_keys($cds))->get(['cd', 'name', 'tel'])
            ->keyBy(fn ($row) => (int) $row->cd)->all();
    }

    /**
     * Días del 予約表 (vista día, 週間 o 月間), con una fila por empleado.
     * Forma documentada en resources/views/admin/reservas/timetable.blade.php (`$days`).
     *
     * @param  list<Carbon>  $dates
     * @return array{days: list<array<string, mixed>>, totalYoyakuCnt: int, totalPeopleCnt: int}
     */
    public function days(int $shopCd, array $dates, Collection $staff, array $geometry, ?Carbon $today = null): array
    {
        $today = ($today ?? now('Asia/Tokyo'))->copy()->startOfDay();
        $keys = array_map(fn (Carbon $date) => $date->format('Y/m/d'), $dates);
        $staffCds = $staff->pluck('cd')->map(fn ($cd) => (int) $cd)->all();

        $slots = $this->slots($staffCds, $keys);
        $reservations = $this->reservations($staffCds, $keys);
        $customers = $this->customersOf($reservations);
        $menuNames = $this->menuNames($shopCd);
        $youbi = ['日', '月', '火', '水', '木', '金', '土'];

        $days = [];
        $totalYoyaku = $totalPeople = 0;
        foreach ($dates as $date) {
            $key = $date->format('Y/m/d');
            $yoyakuCnt = $peopleCnt = 0;
            $rows = [];
            foreach ($staff->values() as $n => $member) {
                $cd = (int) $member->cd;
                $slot = $slots[$key][$cd] ?? null;
                $cell = null;
                if ($slot !== null) {
                    $list = $reservations[$key][$cd] ?? [];
                    $yoyakuCnt += count($list);
                    foreach ($list as $row) {
                        $peopleCnt += (int) $row->people;
                    }
                    $cell = $this->slotCell($slot, $list, $geometry, $menuNames, $customers);
                }
                $rows[] = ['cd' => $cd, 'name' => (string) $member->name, 'bg' => $n % 2 === 1 ? 'bg' : '', 'slot' => $cell];
            }

            $days[] = [
                'key' => $date->format('Ymd'),
                'header' => $date->format('Y年m月d日') . '(' . $youbi[$date->dayOfWeek] . ') ',
                'valid' => $date->copy()->startOfDay()->greaterThanOrEqualTo($today),
                'yoyakuCnt' => $yoyakuCnt,
                'peopleCnt' => $peopleCnt,
                'rows' => $rows,
            ];
            $totalYoyaku += $yoyakuCnt;
            $totalPeople += $peopleCnt;
        }

        return ['days' => $days, 'totalYoyakuCnt' => $totalYoyaku, 'totalPeopleCnt' => $totalPeople];
    }

    /**
     * スタッフ別予約表: un día por fila del mes de `$month`. Cada fila: día, 曜日, color, fondo, si se puede
     * cambiar (>= hoy) y la celda de la franja (misma forma que `slot` del 予約表) o null.
     *
     * @return list<array<string, mixed>>
     */
    public function staffMonth(int $shopCd, int $staffCd, Carbon $month, array $geometry, ?Carbon $today = null): array
    {
        $today = ($today ?? now('Asia/Tokyo'))->copy()->startOfDay();
        $first = $month->copy()->startOfMonth();
        $prefix = $first->format('Y/m/');

        // El legacy busca las franjas con LIKE 'Y/m/%'
        $slots = [];
        $rows = DB::table('tb_yoyaku_uketsuke_day')
            ->where('tb_staff_cd', $staffCd)
            ->where('moushikomi_date', 'like', $prefix . '%')
            ->orderBy('cd')
            ->get();
        foreach ($rows as $row) {
            $slots[(string) $row->moushikomi_date] = $row;
        }
        $dates = array_keys($slots);
        $reservations = $this->reservations([$staffCd], $dates);
        $customers = $this->customersOf($reservations);
        $menuNames = $this->menuNames($shopCd);
        $youbi = ['日', '月', '火', '水', '木', '金', '土'];

        $days = [];
        for ($day = 1; $day <= $first->daysInMonth; $day++) {
            $date = $first->copy()->day($day);
            $key = $date->format('Y/m/d');
            $dow = $date->dayOfWeek;
            $slot = $slots[$key] ?? null;
            $days[] = [
                'day' => $day,
                'ymd' => $date->format('Ymd'),
                'value' => $key,
                'dow' => $dow,
                'dowDisp' => $youbi[$dow],
                'dowColor' => match ($dow) { 0 => '#FF3333', 6 => '#3333FF', default => '#000000' },
                'bg' => $day % 2 === 1 ? 'bg' : '',
                'header' => $day === 1 || $dow === 1,
                'valid' => $date->greaterThanOrEqualTo($today),
                'slot' => $slot ? $this->slotCell($slot, $reservations[$key][$staffCd] ?? [], $geometry, $menuNames, $customers) : null,
            ];
        }

        return $days;
    }

    /** time_table_staff.php: el empleado si es activo, de esa tienda y con 施術 asignado (si no, null) */
    public function staffOfShop(int $staffCd, int $shopCd): ?object
    {
        return DB::table('tb_staff')
            ->where('cd', $staffCd)
            ->where('shop_cd', $shopCd)
            ->where('disp_flg', '1')
            ->whereIn('cd', fn ($sub) => $sub->select('tb_staff_cd')->from('tb_staff_works')->groupBy('tb_staff_cd'))
            ->first(['cd', 'name']);
    }

    /** Etiquetas de los campos libres visibles (popup del 予約表) */
    public function freeKoumokuLabels(): array
    {
        return array_values(array_map(fn (array $row) => (string) $row['tb_koumoku_name'], $this->flow->freeFields()));
    }

    // ---------------------------------------------------------------- listas laterales

    /** ajax_loadNewYoyakuList.php: las 10 últimas reservas registradas de la tienda */
    public function recentReservations(int $shopCd): Collection
    {
        return DB::table('tb_yoyaku as tb1')
            ->join('tb_yoyaku_uketsuke_day as tb2', 'tb1.tb_yoyaku_uketsuke_day_cd', '=', 'tb2.cd')
            ->join('tb_staff as tb3', 'tb2.tb_staff_cd', '=', 'tb3.cd')
            ->leftJoin('tb_customer as tb4', 'tb1.tb_customer_cd', '=', 'tb4.cd')
            ->selectRaw('tb1.cd, tb1.tb_customer_cd, tb1.touroku_date, CASE WHEN tb1.tb_customer_cd IS NULL THEN tb1.name ELSE tb4.name END AS customer_name, tb2.moushikomi_date, tb3.name AS staff_name')
            ->where('tb3.shop_cd', $shopCd)
            ->where('tb1.disp_flg', '1')
            ->whereRaw("CASE WHEN tb1.tb_customer_cd IS NOT NULL THEN tb4.touroku_kubun = '2' ELSE tb1.name <> '' END")
            ->where('tb1.hyouji_flg', '1')
            ->orderByDesc('tb1.touroku_date')
            ->limit(10)
            ->get();
    }

    /** ajax_loadNewRaitenList.php: los 20 últimos 来店 de la tienda en el día */
    public function visits(int $shopCd, Carbon $day): Collection
    {
        return DB::table('tb_raiten_rireki as tb1')
            ->join('tb_customer as tb2', 'tb1.user_cd', '=', 'tb2.cd')
            ->select('tb1.*', 'tb2.name')
            ->whereRaw("coalesce(tb1.disp_flg, '') <> '9'")
            ->where('tb1.shop_cd', $shopCd)
            ->where('tb1.raiten_hiduke', $day->format('Y/m/d'))
            ->orderByDesc('tb1.raiten_time')
            ->limit(20)
            ->get();
    }

    // ---------------------------------------------------------------- popups del 予約表

    /** Reserva con su franja y la tienda del empleado (consulta común de ajax_time_table / ajax_getYoyakuDatetimeForm) */
    public function reservationWithSlot(int $cd): ?object
    {
        return DB::table('tb_yoyaku as tb1')
            ->join('tb_yoyaku_uketsuke_day as tb2', 'tb1.tb_yoyaku_uketsuke_day_cd', '=', 'tb2.cd')
            ->join('tb_staff as tb3', 'tb2.tb_staff_cd', '=', 'tb3.cd')
            ->select('tb1.*', 'tb2.tb_staff_cd', 'tb2.moushikomi_date', 'tb2.start_time as staff_start_time', 'tb2.end_time as staff_end_time', 'tb3.shop_cd')
            ->where('tb1.cd', $cd)
            ->first();
    }

    /** Hora de un tiempo en segundos como el legacy: 'HH' de date('H') +24 si pasa de medianoche, y 'MM' */
    public static function hourMin(int $seconds): array
    {
        [$hour, $min] = explode(':', ReservationSlotService::clock($seconds));
        if ($seconds >= 86400) {
            $hour = (string) ((int) $hour + 24);
        }

        return [$hour, $min];
    }

    /**
     * ajax_time_table.php: datos del formulario de edición de la reserva (popup del 予約表).
     */
    public function popupData(object $row, bool $isIpad): array
    {
        [$staffStartHour, $staffStartMin] = self::hourMin((int) $row->staff_start_time);
        [$staffEndHour, $staffEndMin] = self::hourMin((int) $row->staff_end_time);
        [$startHour, $startMin] = self::hourMin((int) $row->start_time);
        [$endHour, $endMin] = self::hourMin((int) $row->end_time);

        // 予約可能時間: la franja recortada al horario de la tienda
        $config = $this->config((int) $row->shop_cd);
        if ($config) {
            [$shopStartHour, $shopStartMin] = array_pad(explode(':', (string) $config->start_time), 2, null);
            [$shopEndHour, $shopEndMin] = array_pad(explode(':', (string) $config->end_time), 2, null);
            if ((int) $shopStartHour > (int) $staffStartHour) {
                $staffStartHour = $shopStartHour;
                $staffStartMin = $shopStartMin;
            } elseif ((int) $shopStartHour === (int) $staffStartHour && (int) $shopStartMin > (int) $staffStartMin) {
                $staffStartMin = $shopStartMin;
            }
            if ((int) $shopEndHour < (int) $staffEndHour) {
                $staffEndHour = $shopEndHour;
                $staffEndMin = $shopEndMin;
            } elseif ((int) $shopEndHour === (int) $staffEndHour && (int) $shopEndMin < (int) $staffEndMin) {
                $staffEndMin = $shopEndMin;
            }
        }

        $customer = null;
        $karte = null;
        $candidate = null;
        if ((string) $row->tb_customer_cd !== '') {
            $customer = DB::table('tb_customer')->where('cd', (int) $row->tb_customer_cd)->first(['id', 'name', 'tel', 'email1', 'kanri_id']);
            if ($this->karteToolCategory() > 0) {
                $karte = DB::table('tb_raiten_rireki')
                    ->where('raiten_hiduke', $row->moushikomi_date)
                    ->whereRaw("coalesce(disp_flg, '') <> '9'")
                    ->where('user_cd', (int) $row->tb_customer_cd)
                    ->orderByRaw("coalesce(tb_customer_sub_inner_group, '') desc")
                    ->first(['cd', 'tb_search_cate_cd']) ?? false;
            }
        } else {
            $candidate = $this->similarCustomer($row);
        }

        // Menús del empleado (base type_flg = '1' y opción), con la categoría
        $menus = DB::table('tb_staff_works as tb1')
            ->join('tb_yoyaku_koumoku_master as tb2', 'tb1.tb_yoyaku_koumoku_master_cd', '=', 'tb2.cd')
            ->leftJoin('tb_yoyaku_koumoku_category as tb3', 'tb2.tb_yoyaku_koumoku_category_cd', '=', 'tb3.cd')
            ->selectRaw("tb2.cd, tb1.shoyou_time, tb2.name, tb2.type_flg, CASE WHEN tb2.tb_yoyaku_koumoku_category_cd IS NULL THEN 'カテゴリ無し' ELSE tb3.name END AS cate_name")
            ->where('tb1.tb_staff_cd', (int) $row->tb_staff_cd)
            ->whereIn('tb2.tb_shop_cd', [(int) $row->shop_cd, -1])
            ->where('tb1.disp_flg', '1')
            ->where('tb2.disp_flg', '1')
            ->where('tb1.hyouji_flg', '1')
            ->where('tb2.hyouji_flg', '1')
            ->orderBy('tb2.type_flg')
            ->orderBy('tb2.tb_yoyaku_koumoku_category_cd')
            ->orderBy('tb2.rank')
            ->get();

        $values = explode('<>', (string) $row->free_koumoku);
        $freeFields = [];
        foreach ($this->flow->freeFields() as $n => $field) {
            $freeFields[] = ['n' => $n, 'label' => (string) $field['tb_koumoku_name'], 'value' => rawurldecode($values[$n - 1] ?? '')];
        }

        $mailSendStatus = DB::table('contents_master')
            ->where('category', 'tb_c9_yoyaku')
            ->where('field', 'mail_send_status')
            ->orderByDesc('update_date')
            ->value('text_data');

        $staff = $isIpad ? $this->staffList((int) $row->shop_cd) : collect();

        return [
            'yoyaku' => $row,
            'customer' => $customer,
            'karte' => $karte,
            'candidate' => $candidate,
            'shows' => fn (string $field) => $this->flow->shows($field),
            'freeFields' => $freeFields,
            'hourRange' => self::hourRange((int) $staffStartHour, (int) $staffEndHour),
            'startHour' => $startHour, 'startMin' => $startMin, 'endHour' => $endHour, 'endMin' => $endMin,
            'staffStart' => $staffStartHour . ':' . $staffStartMin,
            'staffEnd' => $staffEndHour . ':' . $staffEndMin,
            'myMenu' => array_map('strval', explode(',', $row->base_tb_yoyaku_koumoku_master_cd . ',' . $row->option_tb_yoyaku_koumoku_master_cd)),
            'baseMenus' => $menus->where('type_flg', '1')->values(),
            'optionMenus' => $menus->where('type_flg', '!=', '1')->values(),
            'mailSendStatus' => $mailSendStatus,
            'receiptDay' => str_replace('/', '-', (string) $row->moushikomi_date),
            'staffList' => $staff,
            'karteWord' => $this->flow->manageWord('カルテ', 'c1_customer_detail[カルテ]'),
        ];
    }

    /**
     * ajax_getYoyakuDatetimeForm.php: opciones del cambio de fecha y hora.
     *
     * @return array{years: list<int>, year: string, month: string, day: string, hourRange: list<string>, startHour: string, startMin: string}
     */
    public function datetimeFormData(?object $row): array
    {
        [$year, $month, $day] = array_pad(explode('/', (string) ($row->moushikomi_date ?? '')), 3, null);
        [$startHour, $startMin] = self::hourMin((int) ($row->start_time ?? 0));

        $hourRange = [];
        $config = $row ? $this->config((int) $row->shop_cd) : null;
        if ($config) {
            $hourRange = self::hourRange((int) explode(':', (string) $config->start_time)[0], (int) explode(':', (string) $config->end_time)[0]);
        }

        // 「年」: del año de la reserva al año siguiente al actual
        $years = [];
        for ($i = (int) $year; $i <= (int) now('Asia/Tokyo')->format('Y') + 1; $i++) {
            $years[] = $i;
        }

        return [
            'years' => $years,
            'year' => (string) $year,
            'month' => (string) $month,
            'day' => (string) $day,
            'hourRange' => $hourRange,
            'startHour' => $startHour,
            'startMin' => $startMin,
        ];
    }

    /** 'HH' de $from a $to (sprintf('%02s')) */
    public static function hourRange(int $from, int $to): array
    {
        $hours = [];
        for ($i = $from; $i <= $to; $i++) {
            $hours[] = sprintf('%02d', $i);
        }

        return $hours;
    }

    /** karteToolCheck(): cd de la categoría de カルテ si `admin_master.related_features` la tiene activa; si no, 0 */
    public function karteToolCategory(): int
    {
        $flags = explode('<>', (string) DB::table('admin_master')->where('cd', 1)->value('related_features'));

        return ($flags[0] ?? '') === '1' && ($flags[2] ?? '') !== '' ? (int) $flags[2] : 0;
    }

    /** もしかして検索: primer socio (touroku_kubun '2', activo) con el mismo correo, teléfono, kana o nombre */
    private function similarCustomer(object $row): ?object
    {
        $conditions = [];
        if ((string) $row->mail !== '') {
            $conditions[] = ['email1', $row->mail];
        }
        if ((string) $row->tel !== '') {
            $conditions[] = ['tel', $row->tel];
            $conditions[] = ['kojin_mobile', $row->tel];
        }
        if ((string) $row->name_kana !== '') {
            $conditions[] = ['name_kana', $row->name_kana];
        }
        if ((string) $row->name !== '') {
            $conditions[] = ['name', $row->name];
        }
        if ($conditions === []) {
            return null;
        }

        return DB::table('tb_customer')
            ->where(function ($query) use ($conditions) {
                foreach ($conditions as [$column, $value]) {
                    $query->orWhere($column, 'like', (string) $value);
                }
            })
            ->where('touroku_kubun', '2')
            ->where('disp_flg', '1')
            ->first(['cd', 'name']);
    }
}
