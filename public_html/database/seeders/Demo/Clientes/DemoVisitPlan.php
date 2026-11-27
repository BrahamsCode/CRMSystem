<?php

namespace Database\Seeders\Demo\Clientes;

use App\Models\Sales\Discount;
use App\Models\Sales\ServiceMenu;
use App\Services\Sales\SaleService;
use Illuminate\Support\Collection;

/**
 * Qué se cobró en cada visita demo (contrato entre SociosDemoSeeder y VentasDemoSeeder).
 *
 * El legacy no guarda el menú en el 来店 ni en la ポイント: solo el importe (`tb_point_log.kaikei`). Para que la venta
 * (D2) y la ポイント (D1) de una misma visita cuadren, las dos partes piden el plan a esta clase con el `cd` de la
 * visita y su tienda: el resultado es determinista (no usa Faker ni mt_rand, solo el cd) y depende únicamente del
 * catálogo de la tienda (`tb_karute_menu`, `tb_karute_waribiki`), así que da lo mismo en procesos distintos.
 *
 * - `total` = SaleService::calculate(...)['total'] = `tb_point_log.kaikei` de la visita.
 * - Casos límite: cd % 509 = 0 → importe 0 (カット con el 割引 de 100 % del catálogo, 全額サービス; sin él, その他の割引
 *   por el mismo importe); cd % 701 = 350 → venta grande: ブライダルプラン ×2 + 前撮りヘアメイク ×2 + 縮毛矯正
 *   (302.500円 con el catálogo de CatalogoDemoSeeder; sin esos menús, uno sin cd de 300.000円).
 * - Al azar nunca se elige un 割引 de 100 %.
 */
class DemoVisitPlan
{
    /** Venta grande: [nombre, veces] del catálogo de CatalogoDemoSeeder */
    private const LARGE_MENUS = [['ブライダルプラン', 2], ['前撮りヘアメイク', 2], ['縮毛矯正', 1]];

    public const LARGE_MENU = 'ブライダルトータルプラン';

    public const LARGE_PRICE = 300000;

    /** Menús de respaldo si la tienda no tiene catálogo: [nombre, precio, type] */
    private const FALLBACK_MENUS = [
        ['カット', 4950, '1'], ['カラー', 6600, '1'], ['パーマ', 8800, '1'], ['縮毛矯正', 16500, '1'],
        ['トリートメント', 3300, '1'], ['ヘッドスパ', 4400, '1'], ['前髪カット', 1100, '1'],
    ];

    /** Menú principal y su peso (los nombres de CatalogoDemoSeeder::MENUS) */
    private const MAIN_MENUS = ['カット' => 45, 'カラー' => 18, 'リタッチカラー' => 8, 'パーマ' => 8, 'デジタルパーマ' => 4,
        '縮毛矯正' => 5, 'キッズカット' => 4, '前髪カット' => 4, 'ブロー' => 4];

    /** Opciones: nombre => probabilidad en % */
    private const OPTIONS = ['トリートメント' => 30, 'ヘッドスパ' => 15, '指名料' => 35];

    /** @var array<int, array{menus: Collection, discounts: Collection}> */
    private array $catalog = [];

    private int $state = 0;

    /**
     * @return array{kind: string, menus: Collection<int, ServiceMenu>, discounts: Collection<int, Discount>,
     *               other_value: int, other_type: string, amounts: array, total: int}
     */
    public function for(int $visitCd, int $shopCd): array
    {
        $this->state = crc32('demo-visit-' . $visitCd) & 0x7FFFFFFF;
        ['menus' => $menus, 'discounts' => $discounts] = $this->catalog($shopCd);
        $byName = $menus->keyBy('name');
        $services = $menus->where('type', ServiceMenu::TYPE_SERVICE)->values();

        $kind = match (true) {
            $visitCd % 509 === 0 => 'zero',
            $visitCd % 701 === 350 => 'large',
            default => 'normal',
        };

        $picked = collect();
        $chosenDiscounts = collect();
        $otherValue = 0;

        $fullDiscount = $discounts->first(fn ($d) => (string) $d->type === '1' && (int) $d->value >= 100);
        $randomDiscounts = $discounts->reject(fn ($d) => (string) $d->type === '1' && (int) $d->value >= 100)->values();

        if ($kind === 'large') {
            if (isset($byName['ブライダルプラン'], $byName['前撮りヘアメイク'])) {
                foreach (self::LARGE_MENUS as [$name, $times]) {
                    for ($i = 0; $i < $times && isset($byName[$name]); $i++) {
                        $picked->push($byName[$name]);
                    }
                }
            } else {
                $picked->push($this->transientMenu(self::LARGE_MENU, self::LARGE_PRICE, ServiceMenu::TYPE_OTHER));
            }
        } else {
            $weights = [];
            foreach (self::MAIN_MENUS as $name => $weight) {
                if (isset($byName[$name])) {
                    $weights[$name] = $weight;
                }
            }
            $main = $weights ? $byName[$this->weighted($weights)] : ($byName['カット'] ?? $services->first() ?? $menus->first());
            $picked->push($kind === 'zero' ? ($byName['カット'] ?? $main) : $main);

            if ($kind === 'normal') {
                foreach (self::OPTIONS as $name => $chance) {
                    if (isset($byName[$name]) && $this->chance($chance)) {
                        $picked->push($byName[$name]);
                    }
                }
                $retail = $menus->where('type', ServiceMenu::TYPE_RETAIL)->values();
                if ($retail->isNotEmpty() && $this->chance(15)) {
                    $picked->push($retail[$this->next() % $retail->count()]);
                }
                if ($randomDiscounts->isNotEmpty() && $this->chance(20)) {
                    $chosenDiscounts->push($randomDiscounts[$this->next() % $randomDiscounts->count()]);
                }
                $otherValue = $this->chance(5) ? 500 : 0;
            }
        }

        $sales = app(SaleService::class);
        if ($kind === 'zero') {
            // 全額サービス del catálogo; sin él, el mismo importe como その他の割引 (円)
            if ($fullDiscount) {
                $chosenDiscounts->push($fullDiscount);
            } else {
                $otherValue = (int) $picked->sum(fn ($m) => (int) $m->kingaku);
            }
        }
        $amounts = $sales->calculate($picked, $chosenDiscounts, $otherValue, '2');
        if ($amounts['total_float'] < 0) {
            // Como VentasDemoSeeder: sin descuentos si el total saliera negativo
            $chosenDiscounts = collect();
            $otherValue = 0;
            $amounts = $sales->calculate($picked, $chosenDiscounts, 0, '2');
        }

        return [
            'kind' => $kind,
            'menus' => $picked,
            'discounts' => $chosenDiscounts,
            'other_value' => $otherValue,
            'other_type' => '2',
            'amounts' => $amounts,
            'total' => $amounts['total'],
        ];
    }

    /** Menús y descuentos activos de la tienda (orden rank, cd); sin catálogo, los de respaldo sin cd */
    private function catalog(int $shopCd): array
    {
        return $this->catalog[$shopCd] ??= (function () use ($shopCd) {
            $menus = ServiceMenu::query()->where('tb_shop_cd', $shopCd)->where('disp_flg', '1')->orderBy('rank')->orderBy('cd')->get();
            if ($menus->isEmpty()) {
                $menus = collect(self::FALLBACK_MENUS)->map(fn ($m) => $this->transientMenu(...$m));
            }

            return [
                'menus' => $menus->values(),
                'discounts' => Discount::query()->where('tb_shop_cd', $shopCd)->where('disp_flg', '1')->orderBy('rank')->orderBy('cd')->get()->values(),
            ];
        })();
    }

    private function transientMenu(string $name, int $price, string $type): ServiceMenu
    {
        return (new ServiceMenu)->forceFill(['cd' => null, 'name' => $name, 'kingaku' => $price, 'type' => $type]);
    }

    /** Generador congruencial propio (no toca el estado global de mt_rand ni de Faker) */
    private function next(): int
    {
        $this->state = ($this->state * 1103515245 + 12345) & 0x7FFFFFFF;

        return $this->state >> 8;
    }

    private function chance(int $percent): bool
    {
        return $this->next() % 100 < $percent;
    }

    private function weighted(array $weights): string
    {
        $roll = $this->next() % array_sum($weights);
        foreach ($weights as $name => $weight) {
            if (($roll -= $weight) < 0) {
                return $name;
            }
        }

        return array_key_first($weights);
    }
}
