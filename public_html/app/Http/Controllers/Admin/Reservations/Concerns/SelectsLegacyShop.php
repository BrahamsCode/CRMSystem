<?php

namespace App\Http\Controllers\Admin\Reservations\Concerns;

use App\Models\Shop;
use App\Models\Staff;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Selección de tienda de las pantallas de configuración de 予約管理 (予約項目管理, 設備管理), como el legacy:
 * - `$_SESSION['rank'] == 'master'` → usuario `login_id = 'master'`: ve el selector de tiendas (`make_shop_options`).
 * - Si no es master, la tienda es la de su sesión (`$_SESSION['shop_cd']`).
 */
trait SelectsLegacyShop
{
    protected function isMasterUser(): bool
    {
        return (Auth::user()?->login_id ?? null) === Staff::MASTER_LOGIN_ID;
    }

    /** Tienda de la sesión del personal (`$_SESSION['shop_cd']`) */
    protected function sessionShopCd(): ?int
    {
        $shopCd = Auth::user()?->shop_cd ?? null;

        return filled($shopCd) ? (int) $shopCd : null;
    }

    /**
     * Tienda de la pantalla: la del registro que se edita, si no `?shop=`, si no (no master) la de la sesión.
     * `-1` = 全店舗 (solo en 予約項目管理).
     */
    protected function resolveShopCd(?int $recordShopCd, mixed $requestShop): ?int
    {
        if ($recordShopCd !== null) {
            return $recordShopCd;
        }
        if (filled($requestShop)) {
            return (int) $requestShop;
        }

        return $this->isMasterUser() ? null : $this->sessionShopCd();
    }

    /** Opciones de `make_shop_options()`: tiendas no borradas ordenadas por cd */
    protected function shopOptions(): Collection
    {
        return Shop::query()->orderBy('cd')->get(['cd', 'kaishamei']);
    }

    /** `getShopNameFromCd()` */
    protected function shopName(?int $shopCd): ?string
    {
        return $shopCd === null ? null : Shop::withDeleted()->whereKey($shopCd)->value('kaishamei');
    }
}
