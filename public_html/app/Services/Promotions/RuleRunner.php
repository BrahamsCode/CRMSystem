<?php

namespace App\Services\Promotions;

use App\Enums\DeliveryStatus;
use App\Enums\RuleTrigger;
use App\Models\Message;
use App\Models\MessageRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * Ejecuta los envíos automáticos.
 *
 * Cada ejecución crea un mensaje normal (con message_rule_id) para que su
 * resultado se vea en el historial y en las estadísticas como cualquier otro.
 */
class RuleRunner
{
    public function __construct(private MessageDispatcher $dispatcher) {}

    /** Reglas activas a las que les toca su hora; devuelve cuántas se ejecutaron */
    public function runDue(?Carbon $now = null): int
    {
        $now ??= now();
        $ran = 0;

        foreach (MessageRule::active()->get() as $rule) {
            $due = $now->copy()->setTime($rule->run_hour, $rule->run_minute);

            $yaHoy = $rule->last_run_at && $rule->last_run_at->greaterThanOrEqualTo($due);

            if ($now->lessThan($due) || $yaHoy || ! $rule->runsOn($now)) {
                continue;
            }

            $this->run($rule, $now);
            $ran++;
        }

        return $ran;
    }

    /** Ejecuta la regla para el día $now y devuelve el mensaje creado (o null si no había a quién) */
    public function run(MessageRule $rule, ?Carbon $now = null): ?Message
    {
        $now ??= now();
        $rule->update(['last_run_at' => $now]);

        $query = $this->targets($rule, $now->copy()->startOfDay());

        if ($query === null || ! (clone $query)->exists()) {
            return null;
        }

        $message = Message::create([
            'shop_id' => $rule->shop_id,
            'footer_shop_id' => $rule->shop_id,
            'message_rule_id' => $rule->id,
            'coupon_id' => $rule->coupon_id,
            'channel' => $rule->channel,
            'subject' => $rule->subject ?: $rule->name,
            'body' => $rule->body,
            'filters' => $rule->filters ?? [],
            'note' => $rule->name,
            'delivery_status' => DeliveryStatus::Draft,
        ]);

        $this->dispatcher->deliver($message, $query);

        return $message;
    }

    /** Cuántos clientes recibirían la regla hoy (vista previa en el formulario) */
    public function previewCount(MessageRule $rule, ?Carbon $day = null): ?int
    {
        $query = $this->targets($rule, ($day ?? now())->copy()->startOfDay());

        return $query?->count();
    }

    /**
     * Clientes del disparador para el día $day, ya restringidos al canal.
     * null = el disparador no se puede evaluar (falta el módulo de reservas).
     */
    public function targets(MessageRule $rule, Carbon $day): ?Builder
    {
        $query = $this->dispatcher->audience($rule->filters ?? [], $rule->channel, $rule->shop_id);
        $n = (int) $rule->days;

        switch ($rule->trigger) {
            case RuleTrigger::AfterVisit:
                $query->whereDate('last_visit_date', $day->copy()->subDays($n));
                break;

            case RuleTrigger::AfterSignup:
                $query->whereDate('created_at', $day->copy()->subDays($n));
                break;

            case RuleTrigger::BeforeBirthday:
                $this->sameMonthDay($query, 'birth_date', $day->copy()->addDays($n));
                break;

            case RuleTrigger::VisitCycle:
                // Última visita + ciclo medio + margen = hoy
                $query->whereNotNull('average_visit_cycle')
                    ->whereRaw("last_visit_date + ((average_visit_cycle + ?) * interval '1 day') = ?::date", [$n, $day->toDateString()]);
                break;

            case RuleTrigger::Anniversary:
                $target = $rule->timing === 2 ? $day->copy()->subDays($n) : $day->copy()->addDays($n);
                $this->sameMonthDay($query, 'wedding_date', $target);
                break;

            case RuleTrigger::Dates:
            case RuleTrigger::Weekdays:
                // Todos los clientes de los filtros, en los días elegidos
                break;

            case RuleTrigger::BeforeReservation:
                if (! Schema::hasTable('reservations')) {
                    return null;
                }
                $query->whereExists(fn ($s) => $s->from('reservations')
                    ->whereColumn('reservations.customer_id', 'customers.id')
                    ->whereDate('reservations.reserved_at', $day->copy()->addDays($n))
                    ->when($rule->mobile_only_flg, fn ($w) => $w->where('reservations.mobile_flg', 1)));
                break;
        }

        return $query;
    }

    /**
     * Mismo mes y día sin importar el año. Quien nació un 29 de febrero recibe
     * el mensaje el 28 en los años no bisiestos.
     */
    private function sameMonthDay(Builder $query, string $column, Carbon $target): void
    {
        $query->where(function (Builder $q) use ($column, $target) {
            $q->where(fn (Builder $w) => $w
                ->whereRaw("extract(month from {$column}) = ?", [$target->month])
                ->whereRaw("extract(day from {$column}) = ?", [$target->day]));

            if ($target->month === 2 && $target->day === 28 && ! $target->isLeapYear()) {
                $q->orWhere(fn (Builder $w) => $w
                    ->whereRaw("extract(month from {$column}) = 2")
                    ->whereRaw("extract(day from {$column}) = 29"));
            }
        });
    }
}
