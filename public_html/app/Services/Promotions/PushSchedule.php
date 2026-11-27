<?php

namespace App\Services\Promotions;

use Illuminate\Support\Carbon;

/**
 * Calendario de las プッシュ programadas / フォロー / 自動 (legacy system/class/Push.php:
 * calculateNextSendDate, parseSendParameter, splitParameters). Las horas se interpretan en Asia/Tokyo.
 *
 * `send_parameter` de push_follow_master:
 *   - scheduled: `send_date=2026/12/01,point_expiration_date=2026/12/31`
 *   - follow:    `<tienda><><step1><><días>` (lo evalúa el WHERE guardado; aquí solo cuenta la hora diaria)
 *   - auto:      `1<>01,03<>05,32` (meses y días; 32 = fin de mes) o `2<>0,3` (días de la semana, 0 = domingo)
 */
class PushSchedule
{
    public const TYPES = ['auto', 'follow', 'scheduled'];

    private const DOW = ['日曜日', '月曜日', '火曜日', '水曜日', '木曜日', '金曜日', '土曜日'];

    /** Próximo envío (epoch) o null si no hay (fecha pasada, hora o parámetros inválidos) */
    public function nextSendDate(string $typeFlg, string $sendTime, ?string $sendParams = null, ?Carbon $now = null): ?int
    {
        if (! in_array($typeFlg, self::TYPES, true) || ! preg_match('/^([0-1][0-9]|2[0-3]):([0-5][0-9])$/', $sendTime, $hm)) {
            return null;
        }
        if ($typeFlg === 'auto' && (string) $sendParams === '') {
            return null;
        }
        $now = ($now ?? Carbon::now())->copy()->setTimezone(LegacyTime::ZONE);
        [$hour, $minute] = [(int) $hm[1], (int) $hm[2]];

        return match ($typeFlg) {
            'scheduled' => $this->scheduled((string) $sendParams, $sendTime, $now),
            'follow' => $this->daily($now, $hour, $minute),
            default => $this->auto((string) $sendParams, $hour, $minute, $now),
        };
    }

    private function scheduled(string $sendParams, string $sendTime, Carbon $now): ?int
    {
        $date = self::split($sendParams)['send_date'] ?? '';
        $at = self::parseDate($date.' '.$sendTime);

        return $at !== null && $at > $now->getTimestamp() ? $at : null;
    }

    /** フォロー: hoy a esa hora o, si ya pasó, mañana */
    private function daily(Carbon $now, int $hour, int $minute): int
    {
        $at = $now->copy()->setTime($hour, $minute);

        return $at->getTimestamp() < $now->getTimestamp() ? $at->getTimestamp() + 86400 : $at->getTimestamp();
    }

    private function auto(string $sendParams, int $hour, int $minute, Carbon $now): ?int
    {
        $p = explode('<>', $sendParams);

        if ($p[0] === '1') {
            // 設定月の同日: el primer mes/día del año en curso que aún no pasó; si no, el primero del año siguiente
            $months = explode(',', $p[1] ?? '');
            $days = explode(',', $p[2] ?? '');
            sort($months);
            sort($days);
            $year = $now->year;
            foreach ($months as $month) {
                foreach ($days as $day) {
                    $at = $this->monthDay($year, (int) $month, (int) $day, $hour, $minute);
                    if ($at > $now->getTimestamp()) {
                        return $at;
                    }
                }
            }

            return $this->monthDay($year + 1, (int) $months[0], (int) $days[0], $hour, $minute);
        }

        if ($p[0] === '2') {
            // 毎曜日: el primer día de la semana (de hoy en adelante) cuya hora no pasó; si no, el primero de la semana siguiente
            $dows = array_map('intval', explode(',', $p[1] ?? ''));
            sort($dows);
            $today = $now->copy()->setTime($hour, $minute);
            $todayDow = (int) $now->dayOfWeek;
            foreach ($dows as $dow) {
                if ($dow >= $todayDow) {
                    $at = $today->getTimestamp() + ($dow - $todayDow) * 86400;
                    if ($at > $now->getTimestamp()) {
                        return $at;
                    }
                }
            }

            return $today->getTimestamp() + ($dows[0] + 7 - $todayDow) * 86400;
        }

        return null;
    }

    /** Día 32 = último día del mes (el legacy usaba date("t")) */
    private function monthDay(int $year, int $month, int $day, int $hour, int $minute): int
    {
        $base = Carbon::create($year, $month, 1, $hour, $minute, 0, LegacyTime::ZONE);
        if ($day === 32) {
            $day = $base->daysInMonth;
        }

        return $base->addDays($day - 1)->getTimestamp();
    }

    /** parseSendParameter(): descripción del envío automático para las listas y el formulario */
    public function describe(?string $sendParameter): string
    {
        $p = explode('<>', (string) $sendParameter);

        return match ($p[0]) {
            '1' => '配信月：'.implode('月 ', explode(',', $p[1] ?? '')).'月'
                .'　・　配信日：'.str_replace('32日', '月末', implode('日 ', explode(',', $p[2] ?? '')).'日'),
            '2' => '配信曜日：'.implode(' ', array_map(fn ($d) => self::DOW[(int) $d] ?? '', explode(',', $p[1] ?? ''))),
            default => '',
        };
    }

    /** splitParameters(): `a=1,b=2` → ['a' => '1', 'b' => '2'] */
    public static function split(?string $sendParams): array
    {
        $out = [];
        foreach (explode(',', (string) $sendParams) as $pair) {
            if ($pair !== '') {
                [$key, $value] = array_pad(explode('=', $pair, 2), 2, null);
                $out[$key] = $value;
            }
        }

        return $out;
    }

    /** strtotime() en Asia/Tokyo; null si no es una fecha */
    public static function parseDate(string $value): ?int
    {
        $tz = date_default_timezone_get();
        date_default_timezone_set(LegacyTime::ZONE);
        try {
            $ts = strtotime(trim($value));
        } finally {
            date_default_timezone_set($tz);
        }

        return $ts === false ? null : $ts;
    }
}
