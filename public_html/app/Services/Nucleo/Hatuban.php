<?php

namespace App\Services\Nucleo;

use Illuminate\Support\Facades\DB;

/**
 * Numeración del legacy (tabla `hatuban`, clase `cHatuban`): un contador por `koumoku`
 * (normalmente el nombre de la tabla). `kazu` guarda el PRÓXIMO número libre.
 *
 * - peek('tb_shop'): el número que saldrá, sin consumirlo (`view_hatuban`; 0 si no hay fila).
 * - next('tb_shop'): devuelve ese número y suma 1 (`set_hatuban`); sin fila, la crea con kazu=2 y devuelve 1.
 */
class Hatuban
{
    public function peek(string $koumoku): int
    {
        return (int) DB::table('hatuban')->where('koumoku', $koumoku)->value('kazu');
    }

    public function next(string $koumoku): int
    {
        // FOR UPDATE dentro de una transacción, como el legacy (BEGIN; SELECT … FOR UPDATE; UPDATE; COMMIT)
        return DB::transaction(function () use ($koumoku) {
            $kazu = (int) DB::table('hatuban')->where('koumoku', $koumoku)->lockForUpdate()->value('kazu');

            if ($kazu === 0) {
                // Como set_hatuban: sin contador (o en 0) se empieza en 1 y queda kazu = 2
                DB::table('hatuban')->where('koumoku', $koumoku)->delete();
                DB::table('hatuban')->insert(['koumoku' => $koumoku, 'kazu' => 2]);

                return 1;
            }

            DB::table('hatuban')->where('koumoku', $koumoku)->update(['kazu' => $kazu + 1]);

            return $kazu;
        });
    }
}
