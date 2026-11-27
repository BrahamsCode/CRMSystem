<?php

namespace App\Services\Senalizacion;

use App\Models\Senalizacion\SignageWebCycle;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Database\Query\Builder;

/**
 * Ciclos que se muestran ahora (view/index.php, view/packages.php, view/ajax.php del legacy):
 * activos (flg = 1, status = 1) y dentro de su 表示方法:
 *  1 常に表示 siempre; 2 毎日特定の時間帯のみ cada día entre la hora de inicio y la de fin;
 *  3 特定の期間のみ entre las dos fechas (start < ahora < end, como el legacy).
 *
 * Corrección del tipo 2: el legacy comparaba to_char(…, 'hh24:mm'), y `mm` en PostgreSQL es el MES,
 * así que comparaba «hora:mes» y la franja casi nunca coincidía. Aquí se compara la hora y el minuto
 * (HH24:MI) con el inicio incluido y el fin excluido. Igual que el legacy, una franja que cruza la
 * medianoche (inicio > fin) no se muestra nunca.
 * La hora la pone PHP (now(), zona de la app) y no NOW() de la BD, para que la fije Carbon en las pruebas.
 */
class SignageScheduleResolver
{
    /** @param string $prefix 'cycle.' cuando la consulta tiene JOIN */
    public function whereActive(Builder $query, string $prefix = '', ?CarbonInterface $now = null): Builder
    {
        $now ??= now();
        $hm = $now->format('H:i');
        $at = $now->format('Y-m-d H:i:s');
        $col = fn (string $name) => $prefix.$name;

        return $query
            ->where($col('flg'), 1)
            ->where($col('status'), 1)
            ->where(function ($q) use ($col, $hm, $at) {
                $q->where($col('schedule_repeat_type'), SignageWebCycle::ALWAYS)
                    ->orWhere(function ($q) use ($col, $hm) {
                        $q->where($col('schedule_repeat_type'), SignageWebCycle::DAILY)
                            ->whereRaw("to_char({$col('schedule_start_time')}, 'HH24:MI') <= ?", [$hm])
                            ->whereRaw("? < to_char({$col('schedule_end_time')}, 'HH24:MI')", [$hm]);
                    })
                    ->orWhere(function ($q) use ($col, $at) {
                        $q->where($col('schedule_repeat_type'), SignageWebCycle::PERIOD)
                            ->where($col('schedule_start_time'), '<', $at)
                            ->where($col('schedule_end_time'), '>', $at);
                    });
            });
    }
}
