<?php

namespace App\Services\Customers;

use App\Models\Customers\Customer;
use App\Services\Operaciones\LegacyMonth;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * 入退室一覧 (legacy `system/category1/entrance/work_list.php`): entradas y salidas de un día por socio,
 * leídas de `tb_play_time` (tabla de S5: una fila por socio y mes, `syukkinbi` = epoch del día 1 a las 0:00,
 * columnas `syukkin_time_N`, `taikin_time_N`, `syukkin_flg_N`, `syukkin_kubun_N` por día).
 * Sin mktime(): Carbon en Asia/Tokyo.
 */
class EntryService
{
    /** 入室状況 / 退出状況 que ofrece el editor (más el valor actual) */
    public const ENTRY_STATUSES = ['入室', '遅刻'];

    public const EXIT_STATUSES = ['早退', '退室'];

    /** El día de `dd` (Y/n/j); null si no es una fecha válida */
    public function parseDate(mixed $dd): ?CarbonImmutable
    {
        if (! is_string($dd) || ! preg_match('#^(\d{4})/(\d{1,2})/(\d{1,2})$#', trim($dd), $m)) {
            return null;
        }
        if (! checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return null;
        }

        try {
            return LegacyMonth::of((int) $m[1], (int) $m[2])->day((int) $m[3]);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * Socios (disp_flg <> 9, ORDER BY touroku_date DESC) paginados como inc/page_control.php, cada uno con
     * su registro del día: `entry` = ['in' => 'H:i'|'', 'out' => …, 'in_h', 'in_m', 'out_h', 'out_m',
     * 'status', 'exit_status'] o null si no tiene fila de tb_play_time ese mes.
     * `shop_cd` filtra por la tienda del socio (el legacy usaba una columna `shop_cd` que tb_customer no tiene).
     */
    public function list(CarbonImmutable $date, ?int $shopCd, int $page, int $kensu): LengthAwarePaginator
    {
        $query = Customer::query()
            ->when($shopCd, fn ($q) => $q->where('tb_shop_cd', $shopCd))
            ->orderByDesc('touroku_date')
            ->orderByDesc('cd');

        $kensu = max(1, $kensu);
        $total = (clone $query)->toBase()->getCountForPagination();
        $page = min(max(1, $page), max(1, (int) ceil($total / $kensu)));
        $customers = $total > 0 ? (clone $query)->forPage($page, $kensu)->get() : collect();

        $day = $date->day;
        $rows = DB::table('tb_play_time')
            ->where('syukkinbi', $this->monthEpoch($date))
            ->whereIn('staff_cd', $customers->pluck('cd')->all())
            ->orderBy('cd')
            ->get()
            ->unique('staff_cd')
            ->keyBy('staff_cd');

        $items = $customers->map(function (Customer $customer) use ($rows, $day) {
            $row = $rows->get($customer->cd);

            return ['customer' => $customer, 'entry' => $row ? $this->entry((array) $row, $day) : null];
        });

        return new LengthAwarePaginator($items, $total, $kensu, $page);
    }

    /**
     * Corrección del día (el 決定 del editor): hora de entrada/salida (vacía si falta la hora o el minuto;
     * antes de las 5:00 cuenta como del día siguiente) y los dos estados. Solo actualiza la fila existente,
     * como el UPDATE del legacy.
     */
    public function update(CarbonImmutable $date, int $customerCd, array $input): bool
    {
        $day = $date->day;
        $values = [
            "syukkin_time_{$day}" => $this->time($date, $input['sh'] ?? '', $input['sm'] ?? ''),
            "taikin_time_{$day}" => $this->time($date, $input['th'] ?? '', $input['tm'] ?? ''),
            "syukkin_flg_{$day}" => (string) ($input['sf'] ?? ''),
            "syukkin_kubun_{$day}" => (string) ($input['sk'] ?? ''),
        ];

        return DB::table('tb_play_time')
            ->where('syukkinbi', $this->monthEpoch($date))
            ->where('staff_cd', $customerCd)
            ->update($values) > 0;
    }

    /** `syukkinbi` de tb_play_time: epoch del día 1 del mes a las 0:00 (Japón) */
    public function monthEpoch(CarbonImmutable $date): string
    {
        return LegacyMonth::of($date->year, $date->month)->epoch();
    }

    private function entry(array $row, int $day): array
    {
        $in = $this->clock($row["syukkin_time_{$day}"] ?? null);
        $out = $this->clock($row["taikin_time_{$day}"] ?? null);

        return [
            'in' => $in?->format('H:i') ?? '',
            'in_h' => $in?->format('H') ?? '',
            'in_m' => $in?->format('i') ?? '',
            'out' => $out?->format('H:i') ?? '',
            'out_h' => $out?->format('H') ?? '',
            'out_m' => $out?->format('i') ?? '',
            'status' => (string) ($row["syukkin_flg_{$day}"] ?? ''),
            'exit_status' => (string) ($row["syukkin_kubun_{$day}"] ?? ''),
        ];
    }

    /** Epoch guardado → hora de Japón; vacío o no numérico = sin hora */
    private function clock(mixed $epoch): ?CarbonImmutable
    {
        $epoch = trim((string) $epoch);

        return $epoch !== '' && ctype_digit($epoch)
            ? CarbonImmutable::createFromTimestamp((int) $epoch, LegacyMonth::TIMEZONE)
            : null;
    }

    /** Epoch de la hora H:M del día (antes de las 5 = día siguiente); '' si falta algo o no es una hora */
    private function time(CarbonImmutable $date, mixed $hour, mixed $minute): string
    {
        $hour = (string) $hour;
        $minute = (string) $minute;
        if ($hour === '' || $minute === '' || ! ctype_digit($hour) || ! ctype_digit($minute) || (int) $hour > 23 || (int) $minute > 59) {
            return '';
        }

        $at = $date->setTime((int) $hour, (int) $minute);
        if ((int) $hour < 5) {
            $at = $at->addDay(); // 調整(５時以下なら翌日)
        }

        return (string) $at->getTimestamp();
    }
}
