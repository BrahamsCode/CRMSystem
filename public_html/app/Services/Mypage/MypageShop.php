<?php

namespace App\Services\Mypage;

use App\Models\Shop;
use App\Models\Staff;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * Sesión del personal para las pantallas de マイページ ($_SESSION del legacy), sobre el login de S1:
 * el staff es auth('admin')->user(); la tienda, session('shop_cd') (la pone S1 al entrar);
 * マスター = Staff::isMaster(); los permisos de cKengen, el Gate 'kengen' (NUCLEO.md §4).
 */
class MypageShop
{
    /** Niveles de cKengen: 2 = 新規, 3 = 編集, 4 = 削除 */
    public const CREATE = 2;

    public const EDIT = 3;

    public const DELETE = 4;

    public function staff(): ?Staff
    {
        $user = auth('admin')->user();

        return $user instanceof Staff ? $user : null;
    }

    public function current(): ?int
    {
        $cd = session('shop_cd', $this->staff()?->shop_cd);

        return filled($cd) ? (int) $cd : null;
    }

    /** $_SESSION['shop_name']: nombre de la tienda de la sesión */
    public function name(): string
    {
        return $this->shopName($this->current());
    }

    public function isMaster(): bool
    {
        return (bool) $this->staff()?->isMaster();
    }

    /** `$kengen->myKengen[$feature][$level] == 1 || $_SESSION['rank'] == 'master'` */
    public function can(int $feature, int $level): bool
    {
        $staff = $this->staff();

        return $staff !== null && Gate::forUser($staff)->allows('kengen', [$feature, $level]);
    }

    /** tb_shop con disp_flg <> '9' ORDER BY cd; si no es master, solo la tienda de la sesión */
    public function selectableShops(): Collection
    {
        $query = Shop::query()->withDeleted()->where('disp_flg', '<>', '9')->orderBy('cd');
        if (! $this->isMaster()) {
            $query->where('cd', (int) $this->current());
        }

        return $query->get();
    }

    /** [cd => kaishamei] de las tiendas con disp_flg <> '9' */
    public function shopNames(): Collection
    {
        return Shop::query()->withDeleted()->where('disp_flg', '<>', '9')->pluck('kaishamei', 'cd');
    }

    /** Nombre de la tienda (getShopNameFromCd del legacy): '' si no existe */
    public function shopName(mixed $cd): string
    {
        if ($cd === null || $cd === '') {
            return '';
        }

        return (string) Shop::query()->withDeleted()->whereKey((int) $cd)->value('kaishamei');
    }
}
