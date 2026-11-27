<?php

namespace App\Services\Promotions;

use App\Models\Promotions\PointRule;
use App\Models\Promotions\StampRule;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Regla por tramo que aplica a una visita: Point::getPointCount y
 * cStamp::getStampCount del legacy (system/class/Point.php, cStamp.php).
 *
 * - Primero las reglas de la tienda; si ninguna encaja, las de todas las tiendas (tb_shop_cd = -1).
 * - Prioridad: 5 fecha → 4 día del mes (primero el día exacto, luego 32 = fin de mes)
 *   → 3 periodo → 2 día de la semana + franja → 1 franja horaria.
 * - Empate: `update_date DESC`. Solo reglas con `hyouji_flg <> '9'`.
 * - Fechas (tipos 3 y 5) en epoch; franjas (1 y 2) como HHMM entero (9:05 → 905), inicio incluido y fin excluido.
 * - Puntos: si se pasa el cliente y el sistema de rangos está activo (tb_base_system_grandchild
 *   «顧客ランク（使用金額ランク）» / «…来店回数ランク…»), la regla debe tener la lista de rangos vacía
 *   o contener el rango actual del cliente (kingaku_rank_now / raiten_rank_now).
 * - Sin regla: 1. Para puntos es un multiplicador; para sellos, los sellos de la visita.
 */
class PointRuleResolver
{
    public const DEFAULT = 1;

    /**
     * @param  array|object|null  $customer  fila de tb_customer (kingaku_rank_now, raiten_rank_now)
     */
    public function points(int $shopCd, array|object|null $customer = null, ?CarbonInterface $at = null): int
    {
        return $this->resolve(PointRule::query(), 'point_count', $shopCd, $customer, $at);
    }

    public function stamps(int $shopCd, ?CarbonInterface $at = null): int
    {
        return $this->resolve(StampRule::query(), 'stamp_count', $shopCd, null, $at);
    }

    private function resolve(Builder $base, string $column, int $shopCd, array|object|null $customer, ?CarbonInterface $at): int
    {
        $at = ($at ?? now())->copy()->setTimezone(LegacyTime::ZONE);
        $epoch = $at->getTimestamp();
        $hm = (int) $at->format('G') * 100 + (int) $at->format('i');
        $isLastDay = $at->day === $at->daysInMonth;

        foreach ([$shopCd, PointRule::ALL_SHOPS] as $shop) {
            $scoped = fn () => $this->withRanks((clone $base)->where('hyouji_flg', '<>', PointRule::HIDDEN_FLAG)->where('tb_shop_cd', $shop), $customer)
                ->orderByDesc('update_date');

            $candidates = [
                fn () => $scoped()->where('type_flg', PointRule::TYPE_DATE)->where('start_time', '<=', $epoch)->where('end_time', '>', $epoch),
                fn () => $scoped()->where('type_flg', PointRule::TYPE_MONTH_DAY)->where('start_time', $at->day),
                fn () => $isLastDay ? $scoped()->where('type_flg', PointRule::TYPE_MONTH_DAY)->where('start_time', PointRule::LAST_DAY) : null,
                fn () => $scoped()->where('type_flg', PointRule::TYPE_PERIOD)->where('start_time', '<=', $epoch)->where('end_time', '>', $epoch),
                fn () => $scoped()->where('type_flg', PointRule::TYPE_WEEKDAY)->where('day_of_week', $at->dayOfWeek)->where('start_time', '<=', $hm)->where('end_time', '>', $hm),
                fn () => $scoped()->where('type_flg', PointRule::TYPE_TIME)->where('start_time', '<=', $hm)->where('end_time', '>', $hm),
            ];

            foreach ($candidates as $candidate) {
                $rule = $candidate()?->first();

                if ($rule) {
                    return (int) $rule->{$column};
                }
            }
        }

        return self::DEFAULT;
    }

    /** Filtro de rangos (solo reglas de puntos, solo si se pasa el cliente, como el legacy) */
    private function withRanks(Builder $query, array|object|null $customer): Builder
    {
        if ($customer === null || ! $query->getModel() instanceof PointRule) {
            return $query;
        }

        $customer = (array) $customer;
        [$amountEnabled, $visitEnabled] = $this->rankSystems();

        foreach ([[$amountEnabled, 'tb_kingaku_rank_master_cds', 'kingaku_rank_now'], [$visitEnabled, 'tb_raiten_rank_master_cds', 'raiten_rank_now']] as [$enabled, $listColumn, $customerColumn]) {
            if (! $enabled) {
                continue;
            }

            $rank = trim((string) ($customer[$customerColumn] ?? ''));
            $query->where(function (Builder $q) use ($listColumn, $rank) {
                $q->whereRaw("COALESCE({$listColumn}, '') = ''");
                if ($rank !== '') {
                    $q->orWhereRaw("? = ANY(STRING_TO_ARRAY({$listColumn}, ','))", [$rank]);
                }
            });
        }

        return $query;
    }

    /** @return array{0: bool, 1: bool} [使用金額ランク activo, 来店回数ランク activo] */
    private function rankSystems(): array
    {
        $names = DB::table('tb_base_system_grandchild')
            ->where('system_name', 'like', '顧客ランク%')
            ->where('system_flg', '1')
            ->pluck('system_name');

        return [
            $names->contains(fn ($n) => str_contains($n, '金額')),
            $names->contains(fn ($n) => ! str_contains($n, '金額') && str_contains($n, '来店')),
        ];
    }
}
