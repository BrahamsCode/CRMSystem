<?php

namespace App\Services\Promotions;

use App\Models\Shop;
use Illuminate\Support\Collection;

/**
 * Datos del personal conectado que usan las pantallas de promoción, como `$_SESSION['rank']`
 * y `$_SESSION['shop_cd']` del legacy. El login lo hace S1 (guard `admin`); sin usuario
 * (consola, pruebas) se comporta como master.
 */
class StaffContext
{
    public function user(): ?object
    {
        return auth('admin')->user();
    }

    /** `$_SESSION['rank'] == 'master'` */
    public function isMaster(): bool
    {
        $user = $this->user();

        return $user === null || (method_exists($user, 'isMaster') ? $user->isMaster() : (($user->rank ?? null) === 'master'));
    }

    /** `$_SESSION['shop_cd']` */
    public function shopCd(): ?int
    {
        $cd = $this->user()?->shop_cd ?? null;

        return $cd === null || $cd === '' ? null : (int) $cd;
    }

    /** Tiendas del selector (make_shop_options): todas para master, solo la propia para el resto */
    public function shops(): Collection
    {
        return Shop::query()
            ->when(! $this->isMaster(), fn ($q) => $q->where('cd', $this->shopCd()))
            ->orderBy('cd')
            ->get();
    }
}
