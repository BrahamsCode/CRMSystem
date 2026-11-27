<?php

namespace App\Services\Promotions;

use App\Models\ContentsMaster;
use App\Models\Promotions\KujibikiCount;
use App\Models\Promotions\KujibikiLog;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Support\Facades\DB;

/**
 * Sorteo ガチャリン (legacy system/class/cKujibiki.php::init). Lo llama Mi página (S4).
 *
 * Configuración en contents_master, category = 'tb_c6_kujibiki' (la edita S4 en ガチャリン管理):
 *   field 'rate'        text_data = premio, float_data = peso, int_data = existencias del día
 *   field 'prize_count' text_data = premio, int_data = sorteados hoy, update_date = último premio
 *   field 'max'         int_data = intentos por día;  'limit_flg' text_data/int_data = plazo de canje
 *   field 'disp_flg'    text_data = '9' → sorteo desactivado
 *
 * 1. Intentos: tb_kujibiki_count del cliente (se crea con 0 si es socio, touroku_kubun = '2').
 * 2. Resultado: número entero al azar en [1, suma de pesos]; cada premio ocupa el tramo (a, b] en el orden de las filas.
 * 3. Existencias: si el premio ya llegó a su tope hoy, baja al siguiente (ダイヤ→ゴールド→シルバー→ブロンズ→ハズレ).
 * 4. tb_kujibiki_log con el plazo de canje (time_limit). El cupón se canjea después en Mi página.
 * El día se reinicia a las 5:00 (resetUnix del legacy).
 */
class LotteryService
{
    public const MISS = 'ハズレ';

    public const PRIZES = ['ダイヤ', 'ゴールド', 'シルバー', 'ブロンズ', self::MISS];

    public const RESET_HOUR = 5;

    /** @var Closure(int, int): int */
    private Closure $random;

    public function __construct()
    {
        $this->random = fn (int $min, int $max): int => mt_rand($min, $max);
    }

    /** Para pruebas: fija el número que sale */
    public function useRandom(Closure $random): static
    {
        $this->random = $random;

        return $this;
    }

    /**
     * @return array{status: string, name: string, count?: int, max?: int, time_limit?: ?int}
     */
    public function draw(int $customerCd, ?CarbonInterface $at = null): array
    {
        $at ??= now();

        return DB::transaction(function () use ($customerCd, $at) {
            $config = $this->config();

            if ($config === null || $config['rate'] === []) {
                return ['status' => 'ERROR', 'name' => '設定が完了していません'];
            }

            if (! $this->useTry($customerCd, $config['max'], $at)) {
                return ['status' => 'ERROR', 'name' => '一日の利用可能回数は' . $config['max'] . '回までです'];
            }

            $name = $this->pick($config['rate']);
            [$name, $count] = $this->applyStock($name, $config['count'], $at);
            $timeLimit = $this->log($customerCd, $name, $config['limitFlg'], $config['limitNum'], $at);

            return [
                'status' => 'SUCCESS',
                'name' => $name,
                'count' => $count,
                'max' => $config['count'][$name]['max'] ?? null,
                'time_limit' => $timeLimit,
            ];
        });
    }

    /** Inicio del «día» del sorteo: las 5:00 más recientes */
    public function resetTime(CarbonInterface $at): CarbonInterface
    {
        $reset = $at->copy()->setTimezone(LegacyTime::ZONE)->setTime(self::RESET_HOUR, 0);

        return $reset->greaterThan($at) ? $reset->subDay() : $reset;
    }

    /**
     * getKujibikiCouponCount(): cupones vivos (tb_mycoupon_master.disp_flg = 1) asociados a un premio en
     * contents_master (field 'coupon', int_data = cupón) o a todos si $prize es null. Mi página solo ofrece
     * canjear el premio si hay alguno.
     */
    public function couponCount(?string $prize = null): int
    {
        return DB::table('contents_master as tb1')->join('tb_mycoupon_master as tb2', 'tb1.int_data', '=', 'tb2.cd')
            ->where('tb1.category', 'tb_c6_kujibiki')->where('tb1.field', 'coupon')
            ->when($prize !== null && $prize !== '', fn ($q) => $q->where('tb1.text_data', $prize))
            ->where('tb2.disp_flg', '1')->count();
    }

    /**
     * getKujibikiCountData(): por premio, sorteados hoy (0 si el último es de otro día), tope diario y peso.
     *
     * @return array<string, array{name: string, count: int, max: ?int, rate: ?float}>
     */
    public function countData(?CarbonInterface $at = null): array
    {
        $config = $this->config();
        if ($config === null) {
            return [];
        }
        $reset = $this->resetTime($at ?? now())->getTimestamp();
        $out = [];
        foreach (self::PRIZES as $name) {
            $stats = $config['count'][$name] ?? [];
            $out[$name] = [
                'name' => $name,
                'count' => ($stats['date'] ?? 0) < $reset ? 0 : (int) ($stats['count'] ?? 0),
                'max' => isset($stats['max']) ? (int) $stats['max'] : null,
                'rate' => $config['rate'][$name] ?? null,
            ];
        }

        return $out;
    }

    /**
     * getCustomerCount(): intentos de hoy y máximo diario del cliente; crea su contador a 0 si es socio.
     * null si no es socio activo (el legacy devolvía false).
     *
     * @return array{count: int, max: int}|null
     */
    public function customerCount(int $customerCd, ?CarbonInterface $at = null): ?array
    {
        $at ??= now();

        return DB::transaction(function () use ($customerCd, $at) {
            $max = (int) ($this->config()['max'] ?? 0);
            $counter = KujibikiCount::query()->where('tb_customer_cd', $customerCd)->where('disp_flg', '1')->lockForUpdate()->first();
            if (! $counter) {
                if (! DB::table('tb_customer')->where('cd', $customerCd)->where('touroku_kubun', '2')->where('disp_flg', '1')->exists()) {
                    return null;
                }
                KujibikiCount::create(['tb_customer_cd' => $customerCd, 'int_data' => 0, 'disp_flg' => '1', 'hyouji_flg' => '1']);

                return ['count' => 0, 'max' => $max];
            }
            $count = (int) $counter->update_date < $this->resetTime($at)->getTimestamp() ? 0 : (int) $counter->int_data;

            return ['count' => $count, 'max' => $max];
        });
    }

    /** getDbRecord(): null si no hay configuración o el sorteo está desactivado */
    private function config(): ?array
    {
        $rows = ContentsMaster::query()
            ->where('category', 'tb_c6_kujibiki')
            ->where('disp_flg', '1')
            ->orderBy('cd')
            ->lockForUpdate()
            ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        $config = ['rate' => [], 'count' => [], 'max' => 0, 'limitFlg' => '', 'limitNum' => null];

        foreach ($rows as $row) {
            switch ($row->field) {
                case 'rate':
                    $config['rate'][$row->text_data] = (float) $row->float_data;
                    if ($row->text_data !== self::MISS) {
                        $config['count'][$row->text_data]['max'] = $row->int_data;
                    }
                    break;
                case 'prize_count':
                    $config['count'][$row->text_data]['count'] = (int) $row->int_data;
                    $config['count'][$row->text_data]['date'] = (int) $row->update_date;
                    break;
                case 'max':
                    $config['max'] = (int) $row->int_data;
                    break;
                case 'limit_flg':
                    $config['limitFlg'] = (string) $row->text_data;
                    $config['limitNum'] = $row->int_data;
                    break;
                case 'disp_flg':
                    if ((string) $row->text_data === '9') {
                        return null;
                    }
                    break;
            }
        }

        return $config;
    }

    /** checkCustomerKujibikiCount(): consume un intento si quedan */
    private function useTry(int $customerCd, int $dayMax, CarbonInterface $at): bool
    {
        $counter = KujibikiCount::query()->where('tb_customer_cd', $customerCd)->where('disp_flg', '1')->lockForUpdate()->first();

        if (! $counter) {
            $isMember = DB::table('tb_customer')->where('cd', $customerCd)->where('touroku_kubun', '2')->where('disp_flg', '1')->exists();
            if (! $isMember) {
                return false;
            }
            $counter = KujibikiCount::create([
                'tb_customer_cd' => $customerCd, 'int_data' => 0, 'disp_flg' => '1', 'hyouji_flg' => '1',
            ]);
        }

        $count = (int) $counter->int_data;
        if ((int) $counter->update_date < $this->resetTime($at)->getTimestamp()) {
            $count = 0;
        }

        if ($count >= $dayMax) {
            return false;
        }

        $counter->int_data = $count + 1;
        $counter->update_date = (string) $at->getTimestamp();
        $counter->save();

        return true;
    }

    /** getLotteryResult(): tramo (a, b] que contiene un entero al azar en [1, total] */
    private function pick(array $rates): string
    {
        $total = (int) array_sum($rates);
        $random = $total > 0 ? ($this->random)(1, $total) : 0;
        $a = 0.0;
        $result = self::MISS;

        foreach ($rates as $name => $rate) {
            $b = $a + $rate;
            if ($a < $random && $random <= $b) {
                $result = (string) $name;
            }
            $a = $b;
        }

        return $result;
    }

    /** checkPrizeCount(): baja de premio si se agotó hoy y cuenta el premio entregado */
    private function applyStock(string $name, array $counts, CarbonInterface $at): array
    {
        $reset = $this->resetTime($at)->getTimestamp();
        $index = array_search($name, self::PRIZES, true);

        while ($name !== self::MISS && $index !== false) {
            $stats = $counts[$name] ?? [];
            if (($stats['date'] ?? 0) < $reset) {
                break;   // contador de otro día: hoy no se ha entregado ninguno
            }
            if (($stats['count'] ?? 0) < ($stats['max'] ?? 0)) {
                break;
            }
            $name = self::PRIZES[++$index];
        }

        if ($name === self::MISS) {
            return [$name, $counts[$name]['count'] ?? null];
        }

        $stats = $counts[$name] ?? [];
        $count = ($stats['date'] ?? 0) < $reset ? 1 : (int) ($stats['count'] ?? 0) + 1;

        ContentsMaster::query()
            ->where('category', 'tb_c6_kujibiki')->where('field', 'prize_count')
            ->where('text_data', $name)->where('disp_flg', '1')
            ->update(['int_data' => $count, 'update_date' => $at->getTimestamp()]);

        return [$name, $count];
    }

    /** setKujibiLog(): tirada con el plazo de canje; solo socios */
    private function log(int $customerCd, string $name, string $limitFlg, $limitNum, CarbonInterface $at): ?int
    {
        $from = $at->getTimestamp();
        $day = 60 * 60 * 24;
        $timeLimit = match ($limitFlg) {
            '1' => $from + $day * 7,
            '2' => $from + $day * 30,
            '3' => $from + $day * 90,
            '4' => $from + $day * (int) $limitNum,
            '5' => $limitNum === null ? null : (int) $limitNum,
            default => null,
        };

        $isMember = DB::table('tb_customer')->where('cd', $customerCd)->where('touroku_kubun', '2')->where('disp_flg', '1')->exists();
        if ($isMember) {
            KujibikiLog::create([
                'tb_customer_cd' => $customerCd, 'kujibiki_name' => $name, 'disp_flg' => '1', 'hyouji_flg' => '1', 'time_limit' => $timeLimit,
            ]);
        }

        return $timeLimit;
    }
}
