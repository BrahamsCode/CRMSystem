<?php

namespace App\Services\Sales;

use App\Models\Sales\SalesItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * 商品管理 (legacy: src/Sales/SalesItem.php getList() y category2/item/edit.php).
 * HTML de discription / discription_detail: se guarda tal cual, como el legacy (el núcleo no ofrece un purificador
 * de HTML; pedirlo a S1 si se quiere sanear).
 */
class ProductService
{
    public const DEFAULT_LIMIT = 10;

    public function __construct(private ProductImageStore $images) {}

    /**
     * getList(): disp_flg = 1 (scope del modelo), palabras de name / discription (ILIKE, todas), tiendas
     * (available_shop_cds '1,2' o '{1,2}', basta una), banderas y rango de puntos; orden cd descendente.
     */
    public function search(array $option): LengthAwarePaginator
    {
        $page = max(1, (int) ($option['page'] ?? 1));
        $limit = (int) ($option['limit'] ?? 0) > 0 ? (int) $option['limit'] : self::DEFAULT_LIMIT;

        $query = SalesItem::query()
            ->when(($option['name'] ?? '') !== '', fn (Builder $q) => $this->words($q, 'name', (string) $option['name']))
            ->when(($option['discription'] ?? '') !== '', fn (Builder $q) => $this->words($q, 'discription', (string) $option['discription']))
            ->when(($option['available_shop_cds'] ?? '') !== '', function (Builder $q) use ($option) {
                $cds = explode(',', str_replace(['"', '{', '}', ' '], '', (string) $option['available_shop_cds']));
                $q->where(function (Builder $w) use ($cds) {
                    foreach ($cds as $cd) {
                        $w->orWhereRaw('? = ANY(available_shop_cds)', [(int) $cd]);
                    }
                });
            })
            ->when(isset($option['is_exchangeable_of_point']), fn (Builder $q) => $q->where('is_exchangeable_of_point', (int) $option['is_exchangeable_of_point']))
            ->when(isset($option['number_of_points_exchanged_start']), fn (Builder $q) => $q->where('number_of_points_exchanged', '>=', (int) $option['number_of_points_exchanged_start']))
            ->when(isset($option['number_of_points_exchanged_end']), fn (Builder $q) => $q->where('number_of_points_exchanged', '<=', (int) $option['number_of_points_exchanged_end']))
            ->when(isset($option['hyouji_flg']), fn (Builder $q) => $q->where('hyouji_flg', (int) $option['hyouji_flg']))
            ->orderByDesc('cd');

        $total = (clone $query)->reorder()->count();

        return new LengthAwarePaginator($query->forPage($page, $limit)->get(), $total, $limit, $page);
    }

    /** edit.php (act add / register): columnas del formulario tal cual (filter_input) e imagen */
    public function save(SalesItem $item, array $data, ?UploadedFile $image, bool $deleteImage): bool
    {
        $item->forceFill([
            'disp_flg' => 1,
            'hyouji_flg' => $data['hyouji_flg'] ?? null,
            'name' => $data['name'] ?? null,
            'display_name' => $data['display_name'] ?? null,
            'display_title' => $data['display_title'] ?? null,
            'discription' => $data['discription'] ?? null,
            'discription_detail' => $data['discription_detail'] ?? null,
            'is_exchangeable_of_point' => $data['is_exchangeable_of_point'] ?? null,
        ]);
        $item->setAvailableShopCds(array_map('intval', (array) ($data['available_shop_cds'] ?? [])));
        if (! empty($data['is_exchangeable_of_point'])) {
            $item->number_of_points_exchanged = $data['number_of_points_exchanged'] ?? null;
        }
        $item->save();

        if ($image) {
            return $this->images->store($item->cd, $image);
        }
        if ($deleteImage) {
            $this->images->delete($item->cd);
        }

        return true;
    }

    public function delete(SalesItem $item): bool
    {
        return $item->markDeleted();
    }

    /** buildMultipleSearchStrings(): cada palabra (espacio 半角/全角) con ILIKE, todas obligatorias */
    private function words(Builder $query, string $column, string $text): void
    {
        foreach (explode(' ', str_replace('　', ' ', $text)) as $word) {
            $query->where($column, 'ilike', '%'.$word.'%');
        }
    }
}
