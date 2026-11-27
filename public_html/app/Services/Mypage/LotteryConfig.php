<?php

namespace App\Services\Mypage;

use App\Models\ContentsMaster;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Configuración de ガチャリン (system/category6/kujibiki/kujibiki_config.php) en contents_master,
 * category = 'tb_c6_kujibiki', una fila por campo (solo las de disp_flg = '1'):
 *
 *   field 'disp_flg'     text_data '1' = 使用する, '9' o vacío = 使用しない
 *   field 'max'          int_data  = 1日の利用可能回数
 *   field 'limit_flg'    text_data = plazo de canje (0..5, ver LIMIT_OPTIONS);
 *                        int_data  = días (4) o epoch del último segundo del día (5); NULL en el resto
 *   field 'rate'         text_data = ダイヤ / ゴールド / シルバー / ブロンズ / ハズレ,
 *                        float_data = 抽選確率(％), int_data = 1日の当選本数 (NULL en ハズレ)
 *   field 'prize_count'  text_data = premio, int_data = sorteados hoy (lo actualiza el sorteo)
 *
 * Son los nombres que lee el sorteo (cKujibiki::getDbRecord, App\Services\Promotions\LotteryService de S3).
 * El sorteo recorre las filas 'rate' por cd, así que las filas nuevas se numeran como createHatsuban()
 * del legacy (epoch + tienda + aleatorio): quedan después de las existentes, igual que en el legacy.
 */
class LotteryConfig
{
    public const CATEGORY = 'tb_c6_kujibiki';

    public const MISS = 'ハズレ';

    /** Premio => prefijo de los campos del formulario (kujibiki_<prefijo>_rate / _num), en orden */
    public const PRIZES = ['ダイヤ' => 'dia', 'ゴールド' => 'gold', 'シルバー' => 'silver', 'ブロンズ' => 'bronze'];

    /** text_data de limit_flg: 4 = días (int_data), 5 = fecha (int_data epoch 23:59:59) */
    public const LIMIT_DAYS = '4';

    public const LIMIT_DATE = '5';

    public function __construct(private readonly MypageShop $shop) {}

    /**
     * Configuración actual.
     *
     * @return array{disp_flg: ?string, max: ?int, limit_flg: ?string, limit_num: ?int,
     *               prizes: array<string, array{rate: ?float, num: ?int}>, miss_rate: float}
     */
    public function read(): array
    {
        $config = ['disp_flg' => null, 'max' => null, 'limit_flg' => null, 'limit_num' => null, 'prizes' => [], 'miss_rate' => 100.0];
        foreach (array_keys(self::PRIZES) as $name) {
            $config['prizes'][$name] = ['rate' => null, 'num' => null];
        }

        // Como el legacy: ORDER BY cd y la última fila de cada campo gana
        foreach ($this->rows() as $row) {
            switch ($row->field) {
                case 'disp_flg':
                    $config['disp_flg'] = $row->text_data;
                    break;
                case 'max':
                    $config['max'] = $row->int_data;
                    break;
                case 'limit_flg':
                    $config['limit_flg'] = $row->text_data;
                    $config['limit_num'] = $row->int_data;
                    break;
                case 'rate':
                    if ($row->text_data === self::MISS) {
                        $config['miss_rate'] = (float) $row->float_data;
                    } else {
                        $config['prizes'][$row->text_data] = ['rate' => $row->float_data, 'num' => $row->int_data];
                    }
                    break;
            }
        }

        return $config;
    }

    /** act=change_kujibiki_disp_flg */
    public function saveEnabled(?string $flag): void
    {
        $this->ensureRow('disp_flg');
        $this->update('disp_flg', null, ['text_data' => self::form2Db($flag)]);
    }

    /** act=change_kujibiki_max ('' → 0) */
    public function saveDailyMax(mixed $max): void
    {
        $this->ensureRow('max');
        $this->update('max', null, ['int_data' => (int) self::form2Db($max)]);
    }

    /** act=change_kujibiki_limit_flg: $timeLimit = días (4) o 'Y/m/d' (5) */
    public function saveLimit(?string $flag, ?string $timeLimit): void
    {
        $this->ensureRow('limit_flg');
        $flag = self::form2Db($flag);
        $timeLimit = self::form2Db($timeLimit);

        $num = match ($flag) {
            self::LIMIT_DAYS => (int) $timeLimit,
            self::LIMIT_DATE => $this->endOfDay($timeLimit),
            default => null,
        };
        $this->update('limit_flg', null, ['text_data' => $flag, 'int_data' => $num]);
    }

    /**
     * act=change_kujibiki_rate: $prizes = [premio => ['rate' => %, 'num' => 本数]] ('' → 0).
     * ハズレ = 100 − suma de los cuatro porcentajes (enteros, como el legacy).
     */
    public function saveRates(array $prizes): void
    {
        $sum = 0;
        foreach (array_keys(self::PRIZES) as $name) {
            $rate = self::form2Db($prizes[$name]['rate'] ?? 0);
            $num = self::form2Db($prizes[$name]['num'] ?? 0);

            $this->ensureRow('rate', $name);
            $this->ensureRow('prize_count', $name, ['int_data' => 0]);
            $this->update('rate', $name, ['int_data' => (int) $num, 'float_data' => (float) $rate]);
            $sum += (int) $rate;
        }

        $this->ensureRow('rate', self::MISS);
        $this->update('rate', self::MISS, ['int_data' => null, 'float_data' => 100 - $sum]);
    }

    /**
     * form2Db() de inc/db.php, que kujibiki_config.php aplica a todo el $_REQUEST:
     * mb_convert_kana('KV') (半角カナ → 全角) y recorte de espacios 半角/全角 (el pg_escape_string lo hace PDO).
     */
    public static function form2Db(mixed $value): string
    {
        if (is_array($value) || $value === null) {
            return '';
        }

        return (string) preg_replace('/^[ 　]*(.*?)[ 　]*$/u', '$1', mb_convert_kana((string) $value, 'KV'));
    }

    private function rows(): Collection
    {
        return ContentsMaster::query()
            ->where('category', self::CATEGORY)
            ->where('disp_flg', '1')
            ->orderBy('cd')
            ->get();
    }

    /** insertNewRecord(): crea la fila del campo (y text_data) si no hay ninguna activa */
    private function ensureRow(string $field, ?string $text = null, array $extra = []): void
    {
        $query = ContentsMaster::query()->where('category', self::CATEGORY)->where('field', $field)->where('disp_flg', '1');
        if ($text !== null) {
            $query->where('text_data', $text);
        }
        if ($query->exists()) {
            return;
        }

        ContentsMaster::create([
            'cd' => $this->legacyCd(),
            'category' => self::CATEGORY,
            'field' => $field,
            'text_data' => $text,
            'disp_flg' => '1',
            'hyouji_flg' => '1',
        ] + $extra);
    }

    /** UPDATE de todas las filas activas del campo (y text_data), con update_date */
    private function update(string $field, ?string $text, array $values): void
    {
        $query = ContentsMaster::query()->where('category', self::CATEGORY)->where('field', $field)->where('disp_flg', '1');
        if ($text !== null) {
            $query->where('text_data', $text);
        }
        $query->update($values + ['update_date' => time()]);
    }

    /**
     * Como createHatsuban($shopCd) de system/inc/function.php (epoch + tienda en 3 cifras + 6 cifras), pero
     * siempre mayor que el último cd de la categoría: el legacy usaba 6 cifras al azar y las filas creadas en
     * el mismo segundo (ダイヤ…ハズレ) podían quedar desordenadas para el sorteo, que las recorre por cd.
     */
    private function legacyCd(): int
    {
        $candidate = (int) (time() . sprintf('%03s', (int) $this->shop->current()) . '000000');
        $last = (int) ContentsMaster::query()->where('category', self::CATEGORY)->max('cd');

        return max($candidate, $last + 1);
    }

    /** strtotime("<fecha> 23:59:59"); null si la fecha no se entiende (el legacy rompía el UPDATE) */
    private function endOfDay(?string $date): ?int
    {
        if (blank($date)) {
            return null;
        }
        try {
            return Carbon::parse(str_replace('/', '-', $date) . ' 23:59:59', config('app.timezone'))->getTimestamp();
        } catch (\Throwable) {
            return null;
        }
    }
}
