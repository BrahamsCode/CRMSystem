<?php

namespace App\Services\Promotions;

use App\Models\Promotions\MailmagMaster;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Decide si a un envío (`send_schedule ⨝ mailmag_master`) le toca salir en este minuto.
 * Reemplaza las líneas de crontab que generaba system/category3/class/cSendSchedule.php::createSchedule().
 *
 * `配信時間` y `send_parameter` según type_flg:
 * - follow, followdeco, yoyaku, remdeco: `HH:MM`, cada día (el desfase en días se evalúa al enviar).
 * - auto, autodeco: `HH:MM` y `send_parameter` = `1<>meses<>días` (32 = fin de mes) o `2<>días de la semana` (0 = domingo).
 * - el resto (envío único): `配信時間` = `Y/m/d H:i[:s]`; sale cuando la hora ya llegó (también si el minuto se perdió).
 * - posyoyaku, posremdeco: nunca (POS fuera de alcance).
 */
class SendSchedulePlanner
{
    public const POS_TYPES = ['posyoyaku', 'posremdeco'];

    public function isDue(string $typeFlg, ?string $sendTime, ?string $sendParameter, CarbonInterface $now): bool
    {
        $typeFlg = trim($typeFlg);
        $now = $now->copy()->setTimezone(LegacyTime::ZONE);

        if (in_array($typeFlg, self::POS_TYPES, true)) {
            return false;
        }

        if (in_array($typeFlg, MailmagMaster::RECURRING_TYPES, true)) {
            if (! $this->atTime($sendTime, $now)) {
                return false;
            }

            return str_starts_with($typeFlg, 'auto') ? $this->autoMatches((string) $sendParameter, $now) : true;
        }

        $at = $this->oneOffTime($sendTime);

        return $at !== null && $at->lessThanOrEqualTo($now);
    }

    public function isDueMail(MailmagMaster $mail, CarbonInterface $now): bool
    {
        return $this->isDue((string) $mail->type_flg, $mail->getAttribute('配信時間'), $mail->send_parameter, $now);
    }

    /** `HH:MM` coincide con la hora y el minuto actuales */
    private function atTime(?string $sendTime, CarbonInterface $now): bool
    {
        if (! preg_match('/(\d{1,2}):(\d{1,2})/', (string) $sendTime, $m)) {
            return false;
        }

        return (int) $m[1] === $now->hour && (int) $m[2] === $now->minute;
    }

    private function autoMatches(string $sendParameter, CarbonInterface $now): bool
    {
        $parts = array_map('trim', explode('<>', $sendParameter));

        if (($parts[0] ?? '') === '1') {
            $months = $this->numbers($parts[1] ?? '');
            $days = $this->numbers($parts[2] ?? '');
            $isLastDay = $now->day === $now->daysInMonth;

            return in_array($now->month, $months, true)
                && (in_array($now->day, $days, true) || ($isLastDay && in_array(32, $days, true)));
        }

        if (($parts[0] ?? '') === '2') {
            return in_array($now->dayOfWeek, $this->numbers($parts[1] ?? ''), true);
        }

        return false;
    }

    /** `03,09` → [3, 9] */
    private function numbers(string $csv): array
    {
        return array_values(array_map('intval', array_filter(array_map('trim', explode(',', $csv)), fn ($v) => $v !== '' && ctype_digit($v))));
    }

    /** `2026/11/26 10:30` o `2026/11/26 10:30:00` (el legacy guarda la fecha con barras) */
    private function oneOffTime(?string $sendTime): ?Carbon
    {
        $value = trim((string) $sendTime);
        if ($value === '' || ! preg_match('#^\d{4}[/-]\d{1,2}[/-]\d{1,2} \d{1,2}:\d{1,2}#', $value)) {
            return null;
        }

        try {
            return Carbon::parse(str_replace('/', '-', $value), LegacyTime::ZONE);
        } catch (\Throwable) {
            return null;
        }
    }
}
