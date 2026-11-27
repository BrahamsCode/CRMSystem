<?php

namespace App\Services\Customers;

use App\Models\Customers\SchoolClassConfig;
use App\Models\Customers\SchoolYearConfig;
use App\Models\LegacyModel;
use App\Services\Nucleo\Hatuban;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * 学年設定 y クラス設定 (legacy `system/category1/config/schoolyear_*.php` y `schoolclass_*.php`, idénticos
 * salvo título y tabla): `cate_name` con get_form2(), `cate_order` = cd al dar de alta, borrado disp_flg = '9'.
 */
class SchoolConfigService
{
    /** kind => [modelo, $title del legacy, prefijo de ruta, URL legacy de la lista] */
    public const KINDS = [
        'years' => [SchoolYearConfig::class, '学年', 'school-years', 'schoolyear_config.php'],
        'classes' => [SchoolClassConfig::class, 'クラス', 'school-classes', 'schoolclass_config.php'],
    ];

    public function __construct(private Hatuban $hatuban) {}

    public function title(string $kind): string
    {
        return $this->kind($kind)[1];
    }

    public function routePrefix(string $kind): string
    {
        return 'admin.clientes.' . $this->kind($kind)[2];
    }

    /** Lista: disp_flg = '1' ORDER BY cate_order */
    public function list(string $kind): Collection
    {
        return $this->query($kind)->where('disp_flg', '1')->orderBy('cate_order')->orderBy('cd')->get();
    }

    /** El que se edita (?cd=, disp_flg = '1') o null */
    public function find(string $kind, int $cd): ?LegacyModel
    {
        return $this->query($kind)->where('disp_flg', '1')->find($cd);
    }

    /** act=new: cd con hatuban y cate_order = cd; act=up: solo cate_name. Siempre disp_flg = '1' */
    public function save(string $kind, ?LegacyModel $item, string $name): LegacyModel
    {
        return DB::transaction(function () use ($kind, $item, $name) {
            if (! $item) {
                $model = $this->kind($kind)[0];
                $item = new $model;
                $item->cd = $this->hatuban->next($item->hatubanKoumoku());
                $item->cate_order = $item->cd;
            }
            $item->cate_name = $name;
            $item->disp_flg = '1';
            $item->save();

            return $item;
        });
    }

    /** *_delete.php: disp_flg = '9' (sin comprobar que exista, como el legacy) */
    public function delete(string $kind, int $cd): void
    {
        $model = $this->kind($kind)[0];
        $model::withDeleted()->whereKey($cd)->update(['disp_flg' => '9']);
    }

    private function query(string $kind)
    {
        return $this->kind($kind)[0]::query();
    }

    private function kind(string $kind): array
    {
        return self::KINDS[$kind] ?? throw new InvalidArgumentException("Tipo no válido: {$kind}");
    }
}
