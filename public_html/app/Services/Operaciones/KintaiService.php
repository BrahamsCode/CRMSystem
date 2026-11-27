<?php

namespace App\Services\Operaciones;

use App\Models\Operaciones\KintaiConfig;
use App\Models\Operaciones\KintaiHiduke;
use App\Models\Operaciones\KintaiRecord;
use App\Models\Staff;
use App\Support\LegacyText;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * 勤怠管理 (system/category5/kintai): jornada con corte `kijun_time`, lista del día, estado actual, horas
 * «24+» de las marcas que pasan la medianoche y altas/ediciones/borrados de kintai_tb_hiduke + kintai_tb_kintai.
 *
 * Corte de jornada: el día D va de D `kijun_time` a D+1 `kijun_time` (por defecto 05:00), sobre
 * `kintai_tb_hiduke.hiduke`, que es texto con el epoch (la comparación es de texto, como el legacy).
 * Fechas: la de mktime() del legacy (el mes y el día desbordan al siguiente: 2026/02/31 = 2026/03/03).
 */
class KintaiService
{
    /** Estados de jornada sin marcas: la lista y los CSV muestran «---» o no los exportan */
    public const REST_DAYS = ['欠勤', '有給休暇', '忌引休暇', '特別有給', 'シフト休'];

    public const FLAG_LABELS = [
        KintaiRecord::CLOCK_IN => '出勤',
        KintaiRecord::CLOCK_OUT => '退勤',
        KintaiRecord::GO_OUT => '外出',
        KintaiRecord::RETURN => '戻り',
    ];

    /**
     * yy/mm/dd de la petición como el legacy: si falta alguno, hoy; año ≤ 1900 → 1900, mes/día ≤ 1 → 1.
     *
     * @return array{0: int, 1: int, 2: int}
     */
    public static function requestDate(mixed $yy, mixed $mm, mixed $dd): array
    {
        if (! self::filled($yy) || ! self::filled($mm) || ! self::filled($dd)) {
            $today = CarbonImmutable::now(LegacyMonth::TIMEZONE);

            return [$today->year, $today->month, $today->day];
        }

        // Topes altos solo para no salir del rango de fechas (el legacy no los tiene)
        return [min(max(1900, (int) $yy), 9000), min(max(1, (int) $mm), 1200), min(max(1, (int) $dd), 36600)];
    }

    /** Los tres valores son dígitos (kintai_new/kintai_update redirigen a la lista si no) */
    public static function digits(mixed ...$values): bool
    {
        foreach ($values as $value) {
            if (! is_string($value) || preg_match('/^[0-9]+$/', $value) !== 1) {
                return false;
            }
        }

        return true;
    }

    /** mktime(0, 0, 0, $mm, $dd, $yy) */
    public static function date(int $yy, int $mm, int $dd): CarbonImmutable
    {
        return CarbonImmutable::create($yy, 1, 1, 0, 0, 0, LegacyMonth::TIMEZONE)->addMonths($mm - 1)->addDays($dd - 1);
    }

    /** mktime($h, $m, 0, …) sobre el día: horas 24+ pasan al día siguiente */
    public static function at(CarbonImmutable $day, int $hour, int $minute): int
    {
        return $day->startOfDay()->addHours($hour)->addMinutes($minute)->getTimestamp();
    }

    /**
     * Jornada del día: [inicio, fin) en epoch, de `kijun_time` a `kijun_time` del día siguiente.
     *
     * @return array{0: int, 1: int}
     */
    public static function window(KintaiConfig $config, CarbonImmutable $day): array
    {
        [$hour, $minute] = KintaiConfig::splitTime($config->kijun_time);

        return [self::at($day, $hour, $minute), self::at($day->addDay(), $hour, $minute)];
    }

    /** Hora de inicio de los selectores de hora (kijun) y su fin exclusivo (kijun + 24) */
    public static function hourRange(KintaiConfig $config): array
    {
        $start = KintaiConfig::splitTime($config->kijun_time)[0];

        return [$start, $start + 24];
    }

    /** Valor del selector de hora («05»…«28») que corresponde a una hora del día, según el inicio kijun */
    public static function optionHour(int $hourOfDay, int $kijunHour): string
    {
        return sprintf('%02d', $hourOfDay >= $kijunHour ? $hourOfDay : $hourOfDay + 24);
    }

    /** «H:i», o «(H+24):i» si la marca cae en otro día del mes que el consultado */
    public static function displayTime(int $epoch, int $dayOfMonth): string
    {
        $time = CarbonImmutable::createFromTimestamp($epoch, LegacyMonth::TIMEZONE);

        return $time->day !== $dayOfMonth ? ($time->hour + 24).$time->format(':i') : $time->format('H:i');
    }

    /**
     * Duración como la calculaba el legacy (date('H:i') de la diferencia): «HH:MM» módulo 24 h y los minutos.
     *
     * @return array{0: string, 1: int}
     */
    public static function duration(int $from, int $to): array
    {
        $seconds = (($to - $from) % 86400 + 86400) % 86400;
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return [sprintf('%02d:%02d', $hours, $minutes), $hours * 60 + $minutes];
    }

    public static function isRestDay(?string $status): bool
    {
        return in_array($status, self::REST_DAYS, true);
    }

    /** Tienda pedida: el master elige (vacía = la suya); el resto, siempre la suya */
    public static function shopFor(Staff $user, mixed $requested): int
    {
        return $user->isMaster() && self::filled($requested) ? (int) $requested : (int) $user->shop_cd;
    }

    /** Empleados no borrados de la tienda, orden rank, cd (selector スタッフ名) */
    public static function staffOptions(int $shopCd): Collection
    {
        return Staff::query()->where('shop_cd', $shopCd)->orderBy('rank')->orderBy('cd')->get(['cd', 'name']);
    }

    /**
     * Jornadas del día de la tienda con sus marcas activas, en el orden de la primera marca.
     *
     * @return list<array{hiduke_cd: int, staff_cd: int, name: ?string, id: ?string, unit: ?string, section: ?string, status: ?string, bikou: ?string, hiduke: string, records: array<string, list<object>>}>
     */
    public function dayEntries(int $shopCd, int $from, int $to): array
    {
        $rows = $this->joined()
            ->where('tb1.hiduke', '>=', (string) $from)->where('tb1.hiduke', '<', (string) $to)
            ->where('tb3.shop_cd', $shopCd)
            ->orderBy('tb2.kintai_time')
            ->get();

        return $this->group($rows);
    }

    /** Jornadas de un empleado en el mes (CSV mensual), orden hiduke y hora */
    public function monthEntries(int $staffCd, int $from, int $to): array
    {
        $rows = $this->joined()
            ->where('tb1.tb_staff_cd', $staffCd)
            ->where('tb1.hiduke', '>=', (string) $from)->where('tb1.hiduke', '<', (string) $to)
            ->orderBy('tb1.hiduke')->orderBy('tb2.kintai_time')
            ->get();

        return $this->group($rows);
    }

    /**
     * Estado actual (ステータス): la última marca activa de la jornada. El legacy miraba también las borradas,
     * así que tras borrar el 退勤 seguía mostrando 退勤.
     */
    public function currentStatus(int $hidukeCd, int $staffCd): ?string
    {
        $flag = KintaiRecord::query()->where('tb_staff_cd', $staffCd)->where('kintai_tb_hiduke_cd', $hidukeCd)
            ->orderByDesc('kintai_time')->orderByDesc('cd')->value('kintai_flg');

        return self::FLAG_LABELS[$flag] ?? null;
    }

    /** Ya tiene un 出勤 activo en la jornada: devuelve el nombre del empleado, o null */
    public function clockedInName(int $staffCd, int $from, int $to): ?string
    {
        $row = $this->joined()
            ->where('tb1.hiduke', '>=', (string) $from)->where('tb1.hiduke', '<', (string) $to)
            ->where('tb2.tb_staff_cd', $staffCd)->where('tb2.kintai_flg', KintaiRecord::CLOCK_IN)
            ->orderBy('tb1.hiduke')->orderBy('tb2.kintai_time')
            ->first();

        return $row ? (string) $row->name : null;
    }

    /** Marcas activas de una jornada (pantalla de edición), orden hora */
    public function records(int $hidukeCd, int $staffCd): Collection
    {
        return KintaiRecord::query()
            ->join('kintai_tb_hiduke as h', 'h.cd', '=', 'kintai_tb_kintai.kintai_tb_hiduke_cd')
            ->join('tb_staff as s', 's.cd', '=', 'kintai_tb_kintai.tb_staff_cd')
            ->where('kintai_tb_kintai.tb_staff_cd', $staffCd)->where('kintai_tb_kintai.kintai_tb_hiduke_cd', $hidukeCd)
            ->where('h.disp_flg', '1')->where('kintai_tb_kintai.disp_flg', '1')
            ->orderBy('kintai_tb_kintai.kintai_time')
            ->get(['kintai_tb_kintai.*', 'h.kintai_flg as kinmu_status', 'h.bikou', 's.name']);
    }

    /**
     * Alta manual (kintai_new_up.php): la jornada (hiduke = hora de 出勤) y una marca por cada hora dada.
     *
     * @param  array{shukkin: int, gaishutu?: int, modori?: int, taikin?: int}  $times
     */
    public function create(Staff $staff, string $status, string $bikou, array $times): KintaiHiduke
    {
        return DB::transaction(function () use ($staff, $status, $bikou, $times) {
            $hiduke = KintaiHiduke::create([
                'hiduke' => (string) $times['shukkin'],
                'kintai_flg' => $status,
                'felica_no' => (string) $staff->felica_no,
                'tb_staff_cd' => $staff->cd,
                'bikou' => LegacyText::db($bikou),
                'disp_flg' => '1',
                'hyouji_flg' => '1',
            ]);

            $flags = ['shukkin' => KintaiRecord::CLOCK_IN, 'gaishutu' => KintaiRecord::GO_OUT, 'modori' => KintaiRecord::RETURN, 'taikin' => KintaiRecord::CLOCK_OUT];
            foreach ($flags as $key => $flag) {
                if (isset($times[$key])) {
                    $this->addRecord($hiduke->cd, $staff->cd, (string) $staff->felica_no, $flag, $times[$key]);
                }
            }

            return $hiduke;
        });
    }

    /**
     * Edición (kintai_update_up.php). Solo toca marcas de esta jornada (el legacy actualizaba cualquier cd).
     *
     * @param  array{shukkin: ?int, shukkin_cd: int, taikin: ?int, taikin_cd: int, gaishutu: array<int, int>, gaishutu_cd: list<int>, modori: array<int, int>, modori_cd: list<int>, gaishutu_new: ?int, modori_new: ?int}  $data
     */
    public function update(int $hidukeCd, int $staffCd, string $felicaNo, string $status, string $bikou, array $data): void
    {
        DB::transaction(function () use ($hidukeCd, $staffCd, $felicaNo, $status, $bikou, $data) {
            KintaiHiduke::query()->where('cd', $hidukeCd)->update(['kintai_flg' => $status, 'bikou' => LegacyText::db($bikou)]);

            $own = fn (int $cd) => KintaiRecord::query()->where('cd', $cd)->where('kintai_tb_hiduke_cd', $hidukeCd)->where('tb_staff_cd', $staffCd);

            if ($data['shukkin'] !== null) {
                $own($data['shukkin_cd'])->update(['kintai_time' => $data['shukkin']]);
            }
            if ($data['taikin'] !== null) {
                $data['taikin_cd'] > 0
                    ? $own($data['taikin_cd'])->update(['kintai_time' => $data['taikin']])
                    : $this->addRecord($hidukeCd, $staffCd, $felicaNo, KintaiRecord::CLOCK_OUT, $data['taikin']);
            }
            foreach (['gaishutu' => KintaiRecord::GO_OUT, 'modori' => KintaiRecord::RETURN] as $key => $flag) {
                foreach ($data[$key] as $i => $time) {
                    $own((int) ($data[$key.'_cd'][$i] ?? 0))->update(['kintai_time' => $time]);
                }
                if ($data[$key.'_new'] !== null) {
                    $this->addRecord($hidukeCd, $staffCd, $felicaNo, $flag, $data[$key.'_new']);
                }
            }
        });
    }

    /** 一括削除 (kintai_update_del.php): marcas elegidas de la jornada → disp_flg 9 */
    public function deleteRecords(int $hidukeCd, int $staffCd, array $cds): void
    {
        KintaiRecord::query()->whereIn('cd', $cds)->where('kintai_tb_hiduke_cd', $hidukeCd)->where('tb_staff_cd', $staffCd)
            ->update(['disp_flg' => KintaiRecord::DELETED_FLAG]);
    }

    /** 削除 de la lista (kintai_delete.php): la jornada y sus marcas del empleado → disp_flg 9 */
    public function deleteDay(int $hidukeCd, int $staffCd): void
    {
        DB::transaction(function () use ($hidukeCd, $staffCd) {
            KintaiHiduke::query()->where('cd', $hidukeCd)->update(['disp_flg' => KintaiHiduke::DELETED_FLAG]);
            KintaiRecord::query()->where('tb_staff_cd', $staffCd)->where('kintai_tb_hiduke_cd', $hidukeCd)
                ->update(['disp_flg' => KintaiRecord::DELETED_FLAG]);
        });
    }

    private function addRecord(int $hidukeCd, int $staffCd, string $felicaNo, string $flag, int $time): void
    {
        KintaiRecord::create([
            'kintai_flg' => $flag,
            'kintai_tb_hiduke_cd' => $hidukeCd,
            'kintai_time' => $time,
            'felica_no' => $felicaNo,
            'tb_staff_cd' => $staffCd,
            'disp_flg' => '1',
            'hyouji_flg' => '1',
        ]);
    }

    /** jornada ⋈ marca ⟕ empleado, solo activas */
    private function joined(): \Illuminate\Database\Query\Builder
    {
        return DB::table('kintai_tb_hiduke as tb1')
            ->join('kintai_tb_kintai as tb2', 'tb1.cd', '=', 'tb2.kintai_tb_hiduke_cd')
            ->leftJoin('tb_staff as tb3', 'tb1.tb_staff_cd', '=', 'tb3.cd')
            ->where('tb1.disp_flg', '1')->where('tb2.disp_flg', '1')
            ->select('tb1.hiduke', 'tb1.kintai_flg as kinmu_status', 'tb1.bikou', 'tb2.*', 'tb3.name', 'tb3.id',
                'tb3.belongpost as unit', 'tb3.employmentsystem as section');
    }

    private function group(Collection $rows): array
    {
        $entries = [];
        foreach ($rows as $row) {
            $cd = (int) $row->kintai_tb_hiduke_cd;
            $entries[$cd] ??= ['hiduke_cd' => $cd, 'records' => []];
            $entries[$cd]['records'][(string) $row->kintai_flg][] = $row;
            // Como el legacy: los datos de la jornada son los de su última fila
            $entries[$cd] = array_merge($entries[$cd], [
                'staff_cd' => (int) $row->tb_staff_cd, 'name' => $row->name, 'id' => $row->id, 'unit' => $row->unit,
                'section' => $row->section, 'status' => $row->kinmu_status, 'bikou' => $row->bikou, 'hiduke' => (string) $row->hiduke,
            ]);
        }

        return array_values($entries);
    }

    private static function filled(mixed $value): bool
    {
        return is_scalar($value) && (string) $value !== '' && (string) $value !== '0';
    }
}
