<?php

namespace App\Services\Customers;

use App\Models\Customers\Customer;
use App\Models\Customers\Visit;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Registro de visitas (来店処理). Reproduce `cUtility::raitenFunc` + `cRaiten::pushRaitenRireki`:
 *
 * 1. Intervalo mínimo entre visitas del socio: `tb_touch_mode.interval_time` (tienda, `mode = '1'`, socios).
 *    Si la última visita (`MAX(raiten_time)`) es más reciente, no registra nada (devuelve null).
 * 2. Inserta `tb_raiten_rireki` (`hatuban` 'raiten_rireki') con el día de negocio (corte a las 05:00),
 *    los sellos de la visita y edad/sexo/ocupación del socio en ese momento.
 * 3. Recalcula `tb_customer.shuki_heikin` (ciclo medio de las últimas 11 fechas distintas) y `last_raiten_date`.
 * 4. Delega sellos y puntos en VisitLoyalty (servicio de S3; hasta entonces, implementación nula).
 *
 * Lo usan 来店処理 (S2), `tool/api`, `mpa_setraiten` y CTI (S5).
 */
class VisitService
{
    public const ORIGIN_ADMIN = 'admin';         // 来店処理 de la administración

    public const ORIGIN_MANUAL = 'manual';       // alta manual de un historial (manual_flg = '1')

    public const ORIGIN_TERMINAL = 'terminal';   // lector, tool/api, mpa_setraiten

    /** Hora de corte del día de negocio (antes de las 05:00 cuenta como el día anterior) */
    public const BUSINESS_DAY_START_HOUR = 5;

    public function __construct(private VisitLoyalty $loyalty) {}

    /**
     * Registra la visita del socio en la tienda. Devuelve la visita creada, o null si no se
     * registró (dentro del intervalo mínimo), igual que `pushRaitenRireki` devolvía ''.
     *
     * $stampFlag: como `$raiten_log_stamp_flg` del legacy: '' visita y sellos, '1' solo visita,
     * '2' nada.
     */
    public function register(
        Customer $customer,
        int $shopCd,
        string $origin = self::ORIGIN_ADMIN,
        ?int $terminalCd = null,
        ?int $staffCd = null,
        string $stampFlag = '',
        ?CarbonInterface $now = null,
    ): ?Visit {
        if ($stampFlag === '2') {
            return null;
        }

        $now = ($now ?? now())->copy()->setTimezone('Asia/Tokyo');

        $visit = DB::transaction(function () use ($customer, $shopCd, $origin, $now) {
            if ($this->withinInterval($customer, $shopCd, $now)) {
                return null;
            }

            $businessDay = $this->businessDay($now);

            $visit = Visit::create([
                'user_id' => $customer->id,
                'user_cd' => $customer->cd,
                'shop_cd' => $shopCd,
                'raiten_time' => (string) $now->getTimestamp(),
                'raiten_hiduke' => $businessDay,
                'stamp' => $this->loyalty->stampsForVisit($shopCd),
                'customer_age' => $customer->ageAt($now),
                'customer_sex' => $customer->kojin_sex,
                'customer_job' => $customer->job,
                'manual_flg' => $origin === self::ORIGIN_MANUAL ? '1' : null,
            ]);

            $this->refreshVisitStats($customer, $businessDay);

            return $visit;
        });

        // Sellos y puntos (S3): el legacy los procesa aunque la visita caiga en el intervalo
        // (los puntos solo si hubo visita; los sellos tienen su propio intervalo).
        $this->loyalty->afterVisit($customer->fresh() ?? $customer, $shopCd, $visit, $terminalCd, $staffCd, $stampFlag);

        return $visit;
    }

    /** Día de negocio 'Y/m/d': antes de las 05:00 es el día anterior */
    public function businessDay(CarbonInterface $at): string
    {
        $at = $at->copy()->setTimezone('Asia/Tokyo');

        return ($at->hour < self::BUSINESS_DAY_START_HOUR ? $at->subDay() : $at)->format('Y/m/d');
    }

    /** ¿La última visita del socio está dentro del intervalo mínimo de la tienda? */
    public function withinInterval(Customer $customer, int $shopCd, CarbonInterface $now): bool
    {
        $interval = (int) DB::table('tb_touch_mode')
            ->where('tb_shop_cd', $shopCd)
            ->where('mode', '1')
            ->value('interval_time');

        // Como el legacy: MAX(raiten_time) sobre todas las filas del socio, borradas incluidas
        $last = (int) DB::table('tb_raiten_rireki')->where('user_cd', $customer->cd)->max(DB::raw('CAST(raiten_time AS bigint)'));

        return $last + $interval >= $now->getTimestamp();
    }

    /**
     * `shuki_heikin` = media de días entre las últimas 11 fechas de visita distintas
     * (mínimo 2) y `last_raiten_date` = día de negocio de la última visita.
     */
    public function refreshVisitStats(Customer $customer, string $lastBusinessDay): void
    {
        $dates = Visit::query()
            ->where('user_cd', $customer->cd)
            ->distinct()
            ->orderByDesc('raiten_hiduke')
            ->limit(11)
            ->pluck('raiten_hiduke')
            ->map(fn ($d) => strtotime((string) $d))
            ->filter()
            ->values();

        $values = ['last_raiten_date' => $lastBusinessDay];
        if ($dates->count() > 1) {
            $total = 0;
            for ($i = 1; $i < $dates->count(); $i++) {
                $total += ($dates[$i - 1] - $dates[$i]) / 86400;
            }
            $values['shuki_heikin'] = (int) round($total / ($dates->count() - 1));
        }

        // Sin tocar update_date, como el UPDATE del legacy
        Customer::withDeleted()->whereKey($customer->cd)->toBase()->update($values);
    }

    /**
     * `changeCustomerRaitenData()` de `inc/raiten_function.php` (tras el alta manual, la corrección o el borrado de
     * visitas): `last_raiten_date` = MAX(raiten_hiduke) de las visitas visibles y `shuki_heikin` = ciclo medio de las
     * últimas 11 fechas distintas, o NULL si hay menos de 2.
     */
    public function syncVisitStats(int $customerCd): void
    {
        $dates = Visit::query()
            ->where('user_cd', $customerCd)
            ->distinct()
            ->orderByDesc('raiten_hiduke')
            ->limit(11)
            ->pluck('raiten_hiduke')
            ->values();

        $shuki = null;
        if ($dates->count() > 1) {
            $total = 0;
            for ($i = 1; $i < $dates->count(); $i++) {
                $total += (strtotime((string) $dates[$i - 1]) - strtotime((string) $dates[$i])) / 86400;
            }
            $shuki = (int) round($total / ($dates->count() - 1));
        }

        Customer::withDeleted()->whereKey($customerCd)->toBase()->update([
            'shuki_heikin' => $shuki,
            'last_raiten_date' => $dates->first() !== null ? (string) $dates->first() : '',
        ]);
    }

    /**
     * Alta manual desde la pestaña 来店履歴 de la ficha (`inc/raiten_register.php`, 「来店履歴処理実行」):
     * $count visitas (一括登録, mínimo 1) en la fecha indicada, con manual_flg '1', sin sellos (stamp 0)
     * ni puntos, y recalcula las estadísticas. Devuelve los cd creados.
     *
     * @return list<int>
     */
    public function registerManual(Customer $customer, int $shopCd, CarbonInterface $at, int $count = 1, ?int $pointLogCd = null, ?int $stampLogCd = null): array
    {
        $at = $at->copy()->setTimezone('Asia/Tokyo');

        $cds = DB::transaction(function () use ($customer, $shopCd, $at, $count, $pointLogCd, $stampLogCd) {
            $cds = [];
            for ($i = 0; $i < max(1, $count); $i++) {
                $cds[] = Visit::create([
                    'user_id' => $customer->id,
                    'user_cd' => $customer->cd,
                    'shop_cd' => $shopCd,
                    'raiten_time' => (string) $at->getTimestamp(),
                    'raiten_hiduke' => $this->businessDay($at),
                    'stamp' => 0,
                    'customer_age' => $customer->ageAt($at),
                    'customer_sex' => $customer->kojin_sex,
                    'customer_job' => $customer->job,
                    'manual_flg' => '1',
                    'tb_point_log_cd' => $pointLogCd,
                    'tb_stamp_log_cd' => $stampLogCd,
                ])->cd;
            }

            return $cds;
        });

        $this->syncVisitStats((int) $customer->cd);

        return $cds;
    }

    /**
     * 「来店履歴削除」 de la pestaña 来店履歴 (`inc/raiten_register.php`): disp_flg '9' en la visita y en sus campos
     * 関連情報 (tb_customer_sub del mismo category_inner_group); no toca puntos ni sellos. Recalcula las estadísticas.
     */
    public function deleteManual(Customer $customer, int $visitCd): void
    {
        DB::transaction(function () use ($customer, $visitCd) {
            $visit = DB::table('tb_raiten_rireki')->where('user_cd', $customer->cd)->where('cd', $visitCd)->first();
            if (! $visit) {
                return;
            }

            if ($visit->tb_search_cate_cd !== null) {
                DB::table('tb_customer_sub')
                    ->where('tb_search_cate_cd', $visit->tb_search_cate_cd)
                    ->where('tb_customer_cd', $customer->cd)
                    ->where('category_inner_group', (string) $visit->tb_customer_sub_inner_group)
                    ->update(['disp_flg' => '9']);
            }

            DB::table('tb_raiten_rireki')->where('cd', $visitCd)->where('user_cd', $customer->cd)->update(['disp_flg' => '9']);
        });

        $this->syncVisitStats((int) $customer->cd);
    }
}
