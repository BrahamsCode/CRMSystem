<?php

namespace App\Services\Ventas;

use App\Models\Shop;
use App\Models\Staff;
use Illuminate\Support\Collection;

/**
 * Tienda de trabajo de las pantallas de ventas internas (karute_menu/*). Como el legacy:
 * - master elige la tienda con `?shop=` (`make_shop_options`);
 * - el resto usa siempre la de su sesión (`$_SESSION['shop_cd']`), diga lo que diga la petición.
 */
class VentasShop
{
    public function staff(): ?Staff
    {
        return auth('admin')->user();
    }

    public function isMaster(): bool
    {
        return (bool) $this->staff()?->isMaster();
    }

    /** shop_cd de la sesión (S1 lo pone al entrar; si falta, el del staff) */
    public function sessionShop(): ?int
    {
        $cd = session('shop_cd', $this->staff()?->shop_cd);

        return is_numeric($cd) ? (int) $cd : null;
    }

    /**
     * karute_menu.php, karute_waribiki.php, karute_new*.php, karute_delete_list.php:
     * master → `shop` de la petición (puede quedar vacío: la pantalla pide elegir); resto → la de la sesión.
     */
    public function resolve(mixed $requested): ?int
    {
        if (! $this->isMaster()) {
            return $this->sessionShop();
        }

        return is_numeric($requested) && (int) $requested !== 0 ? (int) $requested : null;
    }

    /** keihi.php, staff_total.php, report.php: `if (!shop || rank != master) shop = $_SESSION['shop_cd']` */
    public function resolveOrSession(mixed $requested): ?int
    {
        return $this->resolve($requested) ?? $this->sessionShop();
    }

    /** Opciones de make_shop_options(): tiendas no borradas por cd (no master: solo la suya) */
    public function options(): Collection
    {
        return Shop::query()
            ->when(! $this->isMaster(), fn ($q) => $q->where('cd', $this->sessionShop()))
            ->orderBy('cd')
            ->get(['cd', 'kaishamei']);
    }

    /** getShopNameFromCd(): nombre de la tienda; -1 = 全店舗用 */
    public function name(?int $shopCd): string
    {
        if ($shopCd === null) {
            return '';
        }
        if ($shopCd === -1) {
            return '全店舗用';
        }

        return (string) Shop::withDeleted()->whereKey($shopCd)->value('kaishamei');
    }

    /** Un no master solo puede tocar datos de su tienda */
    public function canAccess(?int $shopCd): bool
    {
        return $this->isMaster() || ($shopCd !== null && $shopCd === $this->sessionShop());
    }
}
