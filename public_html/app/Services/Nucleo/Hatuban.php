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
    /** Estado del modo de prueba de legacyCd(): [epoch fijo, siguiente secuencia] o null */
    private static ?array $fake = null;

    /**
     * createHatsuban($shopCd) de inc/function.php: epoch + tienda (3 cifras) + aleatorio (6 cifras), en texto.
     * Lo usan las tablas que no tienen contador en `hatuban` (contents_master, tb_customer, tb_staff…).
     * Con fake() el epoch es fijo y la parte aleatoria es una secuencia creciente: los cd salen en orden de alta.
     */
    public static function legacyCd(int|string $shopCd = ''): string
    {
        if (self::$fake !== null) {
            return self::$fake[0] . sprintf('%03s', $shopCd) . sprintf('%06d', self::$fake[1]++);
        }

        return time() . sprintf('%03s', $shopCd) . sprintf('%06s', random_int(0, 999999));
    }

    /**
     * Solo pruebas: legacyCd() determinista (epoch `$epoch`, secuencia desde `$start`). Se deshace con unfake()
     * (tests/TestCase.php lo llama al terminar cada prueba).
     */
    public static function fake(int $epoch = 1700000000, int $start = 1): void
    {
        self::$fake = [$epoch, $start];
    }

    public static function unfake(): void
    {
        self::$fake = null;
    }

    /**
     * Fija contadores de `hatuban` (`kazu` = próximo número que dará next()). Pensado para pruebas y siembras;
     * dentro de RefreshDatabase se deshace solo al terminar la prueba.
     *
     * @param  array<string, int>  $counters  [koumoku => kazu]
     */
    public static function fixCounters(array $counters): void
    {
        foreach ($counters as $koumoku => $kazu) {
            DB::table('hatuban')->updateOrInsert(['koumoku' => $koumoku], ['kazu' => $kazu]);
        }
    }

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
