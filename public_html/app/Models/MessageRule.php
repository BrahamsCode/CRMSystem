<?php

namespace App\Models;

use App\Enums\MessageChannel;
use App\Enums\RuleTrigger;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/** Envío automático: seguimiento, programado o recordatorio */
class MessageRule extends BaseModel
{
    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'trigger' => RuleTrigger::class,
            'channel' => MessageChannel::class,
            'months' => 'array',
            'month_days' => 'array',
            'weekdays' => 'array',
            'filters' => 'array',
            'mobile_only_flg' => 'integer',
            'last_run_at' => 'datetime',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function runTime(): string
    {
        return sprintf('%02d:%02d', $this->run_hour, $this->run_minute);
    }

    /** ¿Toca ejecutarla el día $day? (para los disparadores con calendario) */
    public function runsOn(Carbon $day): bool
    {
        return match ($this->trigger) {
            RuleTrigger::Dates => in_array($day->month, array_map('intval', $this->months ?? []), true)
                && (in_array((string) $day->day, array_map('strval', $this->month_days ?? []), true)
                    || (in_array('end', $this->month_days ?? [], true) && $day->isLastOfMonth())),
            RuleTrigger::Weekdays => in_array($day->dayOfWeek, array_map('intval', $this->weekdays ?? []), true),
            default => true,
        };
    }

    /** Próxima ejecución, para la lista */
    public function nextRunAt(?Carbon $from = null): ?Carbon
    {
        if ($this->status !== 1) {
            return null;
        }

        $from ??= now();

        for ($i = 0; $i <= 400; $i++) {
            $day = $from->copy()->startOfDay()->addDays($i)->setTime($this->run_hour, $this->run_minute);

            if ($day->lessThan($from) || ($this->last_run_at && $this->last_run_at->isSameDay($day))) {
                continue;
            }
            if ($this->runsOn($day)) {
                return $day;
            }
        }

        return null;
    }

    /** Descripción corta de cuándo envía: «3 días antes del cumpleaños · 10:00» */
    public function scheduleLabel(): string
    {
        $dias = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
        $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $n = (int) $this->days;

        $cuando = match ($this->trigger) {
            RuleTrigger::AfterVisit => "{$n} días tras la última visita",
            RuleTrigger::AfterSignup => "{$n} días tras el alta",
            RuleTrigger::BeforeBirthday => $n ? "{$n} días antes del cumpleaños" : 'El día del cumpleaños',
            RuleTrigger::VisitCycle => 'Al cumplirse su ciclo de visita' . ($n ? " (+{$n} días)" : ''),
            RuleTrigger::Anniversary => $n ? "{$n} días " . ($this->timing === 2 ? 'después' : 'antes') . ' del aniversario' : 'El día del aniversario',
            RuleTrigger::Dates => collect($this->months ?? [])->map(fn ($m) => $meses[(int) $m - 1])->join(', ')
                . ' · días ' . collect($this->month_days ?? [])->map(fn ($d) => $d === 'end' ? 'fin de mes' : $d)->join(', '),
            RuleTrigger::Weekdays => 'Cada ' . collect($this->weekdays ?? [])->map(fn ($d) => $dias[(int) $d])->join(', '),
            RuleTrigger::BeforeReservation => "{$n} días antes de la reserva",
        };

        return $cuando . ' · ' . $this->runTime();
    }
}
