<?php

namespace App\Services\Promotions;

use App\Models\Promotions\TouchMode;
use App\Models\Shop;
use Illuminate\Support\Collection;

/**
 * カードリーダ設定 (visit/touch_config.php + touch_config_register.php): intervalo de lectura repetida
 * de la tarjeta por tienda, en `tb_touch_mode.interval_time` (segundos, texto).
 */
class VisitTouchConfigService
{
    /** Intervalo inicial (1800 s = 30 分), como el legacy */
    public const DEFAULT_INTERVAL = 1800;

    /** Opciones del selector: 0分, 30分, 1時間 … 24時間 ([segundos => texto]) */
    public static function options(): array
    {
        $options = [0 => '0分', 1800 => '30分'];
        for ($row = 1; $row < 25; $row++) {
            $options[3600 * $row] = $row.'時間';
        }

        return $options;
    }

    /**
     * Tiendas visibles (todas para master, la propia para el resto) con su intervalo.
     * Como el legacy, la tienda sin fila en `tb_touch_mode` la recibe al abrir la pantalla (hatuban, mode '1', 1800 s).
     *
     * @return Collection<int, array{cd:int, kaishamei:string, interval:int}>
     */
    public function shops(bool $isMaster, ?int $shopCd): Collection
    {
        return Shop::query()
            ->when(! $isMaster, fn ($q) => $q->where('cd', (int) $shopCd))
            ->orderBy('cd')->get(['cd', 'kaishamei'])
            ->map(function (Shop $shop) {
                $mode = TouchMode::query()->where('tb_shop_cd', $shop->cd)->first()
                    ?? TouchMode::create(['tb_shop_cd' => $shop->cd, 'mode' => '1', 'interval_time' => (string) self::DEFAULT_INTERVAL]);
                $interval = (string) $mode->interval_time === '' ? self::DEFAULT_INTERVAL : (int) $mode->interval_time;

                return ['cd' => (int) $shop->cd, 'kaishamei' => (string) $shop->kaishamei, 'interval' => $interval];
            });
    }

    /**
     * touch_config_register.php: UPDATE interval_time por tienda.
     * Solo las tiendas que el staff puede ver (el legacy aceptaba cualquier tb_shop_cd del formulario).
     *
     * @param  array<int|string, int|string>  $intervals  [tb_shop_cd => segundos]
     */
    public function update(array $intervals, bool $isMaster, ?int $shopCd): void
    {
        foreach ($intervals as $cd => $seconds) {
            if (! $isMaster && (int) $cd !== (int) $shopCd) {
                continue;
            }
            TouchMode::query()->where('tb_shop_cd', (int) $cd)->update(['interval_time' => (string) (int) $seconds]);
        }
    }
}
