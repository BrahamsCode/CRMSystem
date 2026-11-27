<?php

namespace App\Services\Customers;

use App\Models\AdminMaster;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Reparto de rangos de cliente (legacy: category1/rank/setCustomerKingakuRank.php y setCustomerRaitenRank.php,
 * que el crontab lanzaba cada día a `<tipo>_rank_shori_time`).
 *
 * - Solo actúa los días configurados en `<tipo>_rank_shori_date` (meses × días con 32 = 月末, o días de la semana).
 * - Copia el rango actual a `<tipo>_rank_pre` y recorre los rangos `disp_flg = '1'` y `hyouji_flg = '1'` por
 *   `rank DESC` (el de arriba de la lista, que se aplica el último, gana): `jyouken1 <= x < jyouken2`.
 * - kingaku: x = suma de `kaikei` de `tb_point_log` (y, como el legacy, en UNION la de `tb_stamp_log`) con
 *   `touroku_date` dentro de los últimos `kikan` días; raiten: x = nº de visitas de `tb_raiten_rireki`.
 * - Solo `jyouken2` (sin mínimo): los clientes que NO llegan a `jyouken2`, también los que no tienen registros.
 * - Los clientes que no entran en ningún rango conservan el que tenían.
 */
class RankAssigner
{
    public function __construct(private RankService $ranks) {}

    /** ¿Toca hoy? (comprobación de fecha de los scripts del legacy). Lanza RankScheduleException si la configuración no sirve */
    public function isDueOn(string $type, CarbonInterface $day): bool
    {
        $shoriDate = explode('<>', (string) AdminMaster::current()->getAttribute("{$type}_rank_shori_date"));

        switch ($shoriDate[0]) {
            case '1': // 設定月の同日
                if (count($shoriDate) !== 3) {
                    throw new RankScheduleException('Configuración de fecha no válida (meses/días)');
                }
                foreach (explode(',', $shoriDate[1]) as $month) {
                    if ($month === '' || (int) $month !== $day->month) {
                        continue;
                    }
                    foreach (explode(',', $shoriDate[2]) as $d) {
                        if ($d === '') {
                            continue;
                        }
                        $target = (int) $d === 32 ? $day->daysInMonth : (int) $d;
                        if ($target === $day->day) {
                            return true;
                        }
                    }
                }

                return false;

            case '2': // 毎曜日
                if (count($shoriDate) !== 2) {
                    throw new RankScheduleException('Configuración de fecha no válida (días de la semana)');
                }
                foreach (explode(',', $shoriDate[1]) as $dow) {
                    if ($dow !== '' && (int) $dow === $day->dayOfWeek) {
                        return true;
                    }
                }

                return false;

            default:
                throw new RankScheduleException('Configuración de fecha no válida');
        }
    }

    /** ¿Es la hora de la tarea? (la línea `min hora * * *` que cSendSchedule escribía en el crontab) */
    public function isScheduledAt(string $type, CarbonInterface $now): bool
    {
        $time = (string) AdminMaster::current()->getAttribute("{$type}_rank_shori_time");
        if ($time === '') {
            return false;
        }
        [$hour, $minute] = array_pad(explode(':', $time), 2, '');

        return $hour !== '' && $minute !== '' && (int) $hour === $now->hour && (int) $minute === $now->minute;
    }

    /**
     * Reparte los rangos del tipo contando desde `$today` (sin comprobar la fecha).
     *
     * @return array<int, array{cd: int, name: string, count: int}> clientes asignados por rango, en el orden procesado
     */
    public function assign(string $type, CarbonInterface $today): array
    {
        $master = AdminMaster::current();

        if ($type === RankService::KINGAKU) {
            // 関連データ設定: el legacy se detiene si no es ポイント (1) o スタンプ (2), aunque luego cuenta ambas tablas
            $related = explode('<>', (string) $master->related_features);
            if (! isset($related[1])) {
                throw new RankScheduleException('関連データ (related_features) sin configurar');
            }
            if (! in_array($related[1], ['1', '2'], true)) {
                throw new RankScheduleException('関連データ (related_features) no válido');
            }
        }

        $shoriTime = explode(':', (string) $master->getAttribute("{$type}_rank_shori_time"));
        $kikan = (int) $master->getAttribute("{$type}_rank_shori_kikan");
        if ((int) $shoriTime[0] < 5) {
            $kikan++;
        }
        // timestamp 'TODAY' - interval 'N days'
        $since = $today->copy()->setTimezone(config('app.timezone'))->startOfDay()->subDays($kikan);

        return DB::transaction(function () use ($type, $since) {
            DB::table('tb_customer')->where('disp_flg', '1')
                ->update(["{$type}_rank_pre" => DB::raw("{$type}_rank_now")]);

            $result = [];
            $ranks = $this->ranks->modelClass($type)::query()
                ->where('disp_flg', '1')->where('hyouji_flg', '1')
                ->orderByDesc('rank')->orderBy('cd')
                ->get();

            foreach ($ranks as $rank) {
                $count = $this->assignRank($type, (int) $rank->cd, (int) $rank->jyouken1, (int) $rank->jyouken2, $since);
                if ($count !== null) {
                    $result[] = ['cd' => (int) $rank->cd, 'name' => (string) $rank->name, 'count' => $count];
                }
            }

            return $result;
        });
    }

    /** Un rango: UPDATE tb_customer SET <tipo>_rank_now = cd WHERE cd [NOT] IN (subconsulta con HAVING) */
    private function assignRank(string $type, int $rankCd, int $min, int $max, CarbonInterface $since): ?int
    {
        $aggregate = $type === RankService::KINGAKU ? 'sum(coalesce(kaikei,0))' : 'count(*)';
        $notIn = false;

        if ($min > 0 && $max > 0) {
            $having = ["{$aggregate} >= ? and {$aggregate} < ?", [$min, $max]];
        } elseif ($min > 0) {
            $having = ["{$aggregate} >= ?", [$min]];
        } elseif ($max > 0) {
            $notIn = true;
            $having = ["{$aggregate} >= ?", [$max]];
        } else {
            // Sin condiciones el legacy generaba un HAVING vacío (error de SQL): no asigna a nadie
            return null;
        }

        $subquery = $type === RankService::KINGAKU
            ? $this->logTotals('tb_point_log', $since, $having)->union($this->logTotals('tb_stamp_log', $since, $having))
            : DB::table('tb_raiten_rireki')
                ->select('user_cd')
                ->whereNotNull('user_cd')
                ->whereRaw("NULLIF(raiten_hiduke, '')::date >= ?", [$since->format('Y-m-d')])
                ->whereRaw("coalesce(disp_flg, '') <> '9'")
                ->groupBy('user_cd')
                ->havingRaw($having[0], $having[1]);

        $query = DB::table('tb_customer')->where('disp_flg', '1');
        $notIn ? $query->whereNotIn('cd', $subquery) : $query->whereIn('cd', $subquery);

        return $query->update(["{$type}_rank_now" => $rankCd]);
    }

    private function logTotals(string $table, CarbonInterface $since, array $having): Builder
    {
        return DB::table($table)
            ->select('tb_customer_cd')
            ->whereNotNull('tb_customer_cd')
            ->where('touroku_date', '>=', $since->getTimestamp())
            ->whereRaw("coalesce(disp_flg, '') <> '9'")
            ->groupBy('tb_customer_cd')
            ->havingRaw($having[0], $having[1]);
    }
}
