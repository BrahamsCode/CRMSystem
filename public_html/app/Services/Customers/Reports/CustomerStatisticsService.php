<?php

namespace App\Services\Customers\Reports;

use App\Models\Customers\Job;
use App\Models\Customers\Prefecture;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * 顧客情報集計 (`system/category1/customer/customer_sumup_list.php` + `inc/search_set1_sumup.php`/`search_set2_sumup.php`).
 *
 * Cuenta socios de `tb_customer` (disp_flg = '1', touroku_kubun distinto de '0' y '1') por sexo y década de edad
 * (10代…80代 a partir de `kojin_birth` 'Y/m/d', como el `$sumup_select` del legacy). Personal no master: solo su tienda.
 * Cada bloque devuelve un array de `breakdown()` con los porcentajes ya calculados (floor, como el legacy).
 */
class CustomerStatisticsService
{
    /** Décadas que cuenta el legacy (10代 … 80代) */
    public const AGES = [10, 20, 30, 40, 50, 60, 70, 80];

    /** Opciones del 種別 y del periodo (search_set1_sumup.php) */
    public const KINDS = ['業種別', '県別'];

    public const PERIODS = ['一週間' => 7, '一ヶ月間' => 30];

    /**
     * Datos de la pantalla. `$shopCd` = tienda forzada (no master) o null (master: todas).
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function report(array $input, ?int $shopCd): array
    {
        $searched = ($input['act'] ?? null) === 'up';
        $kind = trim((string) ($input['sumup_kind'] ?? ''));

        $data = [
            'total' => $this->breakdown($this->count($this->base($shopCd))),
            'years' => $this->lastFiveYears($shopCd),
            'searched' => $searched,
            'kind' => $kind,
            'result' => null,
            'groups' => [],
        ];

        if (! $searched) {
            return $data;
        }

        $filtered = fn () => $this->applyPeriod($this->base($shopCd), (string) ($input['touroku_date_1'] ?? ''), (string) ($input['touroku_date_2'] ?? ''));
        $data['result'] = $this->breakdown($this->count($filtered()));

        if ($kind === '県別') {
            // tb_pref where flg = '1' order by rank; columna pref = área
            foreach (Prefecture::query()->where('flg', '1')->orderBy('rank')->get(['area']) as $pref) {
                $data['groups'][] = [
                    'label' => (string) $pref->area,
                    'stats' => $this->breakdown($this->count($filtered()->where('pref', $pref->area))),
                ];
            }
        } elseif ($kind === '業種別') {
            // tb_job where disp_flg = '1' order by rank; 「その他」 incluye job vacío o NULL
            foreach (Job::withDeleted()->where('disp_flg', '1')->orderBy('rank')->get(['name']) as $job) {
                $query = $filtered()->where(function (Builder $q) use ($job) {
                    $q->where('job', $job->name);
                    if ($job->name === 'その他') {
                        $q->orWhere('job', '')->orWhereNull('job');
                    }
                });
                $data['groups'][] = ['label' => (string) $job->name, 'stats' => $this->breakdown($this->count($query))];
            }
        }

        return $data;
    }

    /** where disp_flg = '1' and touroku_kubun <> '0' and touroku_kubun <> '1' (+ tienda del staff si no es master) */
    private function base(?int $shopCd): Builder
    {
        return DB::table('tb_customer')
            ->where('disp_flg', '1')
            ->where('touroku_kubun', '<>', '0')
            ->where('touroku_kubun', '<>', '1')
            ->when($shopCd !== null, fn (Builder $q) => $q->where('tb_shop_cd', $shopCd));
    }

    /**
     * Filtro de search_set2_sumup.php: fecha 'Y/m/d' válida → touroku_date entre ese día (00:00) y +7 o +30 días;
     * sin periodo, touroku_date > ese día. Fecha inválida: sin filtro (el legacy armaba una condición sin sentido).
     * touroku_date es texto con el epoch: se compara como texto, igual que el legacy.
     */
    private function applyPeriod(Builder $query, string $date, string $period): Builder
    {
        $start = $this->parseDate(trim($date));
        if ($start === null) {
            return $query;
        }

        $from = $start->getTimestamp();
        if (isset(self::PERIODS[$period])) {
            return $query->whereBetween('touroku_date', [(string) $from, (string) ($from + 86400 * self::PERIODS[$period])]);
        }

        return $query->where('touroku_date', '>', (string) $from);
    }

    private function parseDate(string $value): ?Carbon
    {
        $parts = explode('/', $value);
        if (count($parts) !== 3 || ! ctype_digit($parts[0]) || ! ctype_digit($parts[1]) || ! ctype_digit($parts[2])
            || ! checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
            return null;
        }

        return Carbon::create((int) $parts[0], (int) $parts[1], (int) $parts[2], 0, 0, 0, 'Asia/Tokyo');
    }

    /** 過去5年の年間登録者数: del 1/1 al 31/12 23:59:59 de cada uno de los últimos 5 años (el más antiguo primero) */
    private function lastFiveYears(?int $shopCd): array
    {
        $year = (int) $this->now()->format('Y');
        $years = [];
        for ($ago = 4; $ago >= 0; $ago--) {
            $from = Carbon::create($year - $ago, 1, 1, 0, 0, 0, 'Asia/Tokyo');
            $to = Carbon::create($year - $ago, 12, 31, 23, 59, 59, 'Asia/Tokyo');
            $years[] = [
                'label' => $from->format('Y/m/d') . '～' . $to->format('Y/m/d'),
                'stats' => $this->breakdown($this->count(
                    $this->base($shopCd)->whereBetween('touroku_date', [(string) $from->getTimestamp(), (string) $to->getTimestamp()])
                )),
            ];
        }

        return $years;
    }

    /**
     * `$sumup_select` del legacy: cnt_all, cnt_male, cnt_female y cnt_{male|female}_{NN}y, con la década NN
     * = kojin_birth entre (hoy - (NN+10) años + 1 día) y (hoy - NN años), comparado como texto 'Y/m/d'.
     *
     * @return array<string, int>
     */
    private function count(Builder $query): array
    {
        $select = ['count(*) as cnt_all'];
        $bindings = [];
        foreach (['male' => '男性', 'female' => '女性'] as $key => $sex) {
            foreach (self::AGES as $age) {
                [$from, $to] = $this->birthRange($age);
                $select[] = "count(CASE WHEN (kojin_sex = ?) and (kojin_birth between ? and ?) THEN 1 ELSE NULL END) as cnt_{$key}_{$age}y";
                array_push($bindings, $sex, $from, $to);
            }
            $select[] = "count(CASE WHEN (kojin_sex = ?) THEN 1 ELSE NULL END) as cnt_{$key}";
            $bindings[] = $sex;
        }

        $row = (array) $query->selectRaw(implode(', ', $select), $bindings)->first();

        return array_map('intval', $row);
    }

    /** @return array{0: string, 1: string} */
    private function birthRange(int $age): array
    {
        $today = $this->now()->startOfDay();

        return [
            $today->copy()->subYears($age + 10)->addDay()->format('Y/m/d'),
            $today->copy()->subYears($age)->format('Y/m/d'),
        ];
    }

    /**
     * Totales y porcentajes como los calcula la vista del legacy (floor(n / (total / 100)), 0 sin datos).
     *
     * @param  array<string, int>  $row
     * @return array<string, mixed>
     */
    public function breakdown(array $row): array
    {
        $all = $row['cnt_all'] ?? 0;
        $male = $row['cnt_male'] ?? 0;
        $female = $row['cnt_female'] ?? 0;
        $unknown = $all - $male - $female;

        return [
            'all' => $all,
            'male' => $male,
            'female' => $female,
            'unknown' => $unknown,
            'male_pct' => $this->pct($male, $all),
            'female_pct' => $this->pct($female, $all),
            'unknown_pct' => $this->pct($unknown, $all),
            'male_ages' => $this->ages($row, 'male', $male),
            'female_ages' => $this->ages($row, 'female', $female),
        ];
    }

    /** @return array{ages: array<int, array{count: int, pct: int}>, other: int, other_pct: int} */
    private function ages(array $row, string $key, int $total): array
    {
        $ages = [];
        $other = $total;
        foreach (self::AGES as $age) {
            $count = $row["cnt_{$key}_{$age}y"] ?? 0;
            $ages[$age] = ['count' => $count, 'pct' => $this->pct($count, $total)];
            $other -= $count;
        }

        return ['ages' => $ages, 'other' => $other, 'other_pct' => $this->pct($other, $total)];
    }

    private function pct(int $value, int $total): int
    {
        return $total > 0 ? (int) floor($value / ($total / 100)) : 0;
    }

    private function now(): Carbon
    {
        return Carbon::now('Asia/Tokyo');
    }
}
