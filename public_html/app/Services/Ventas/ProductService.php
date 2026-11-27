<?php

namespace App\Services\Ventas;

use App\Models\Sales\SalesItem;
use App\Models\Shop;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

/**
 * 商品管理 (system/category2/item): búsqueda de SalesItem::getList(), guardado de edit.php y
 * paginación de CrmSystem\Layout\Pagenation. Las imágenes van en ProductImageService.
 */
class ProductService
{
    private const DEFAULT_PAGE = 1;

    private const DEFAULT_LIMIT = 10;

    private const DEFAULT_RANGE = 3;

    public function __construct(private ProductImageService $images) {}

    /** Tiendas de las casillas y de la lista: `SELECT cd,kaishamei FROM tb_shop WHERE disp_flg != '9' ORDER BY cd` */
    public function shops(): Collection
    {
        return Shop::query()->where('disp_flg', '<>', '9')->orderBy('cd')->pluck('kaishamei', 'cd');
    }

    /**
     * SalesItem::getList(): mismas opciones del $_REQUEST (name, discription con varias palabras separadas
     * por espacio 半角/全角 en AND, available_shop_cds, is_exchangeable_of_point, number_of_points_exchanged_start/end,
     * hyouji_flg, page, limit). Null si no hay filas (el legacy muestra entonces 「見つかりませんでした」).
     *
     * @return object{data: Collection, count: int, page: int, limit: int}|null
     */
    public function search(array $option): ?object
    {
        $page = isset($option['page']) && (int) $option['page'] > 1 ? (int) $option['page'] : self::DEFAULT_PAGE;
        $limit = isset($option['limit']) && (int) $option['limit'] > 0 ? (int) $option['limit'] : self::DEFAULT_LIMIT;

        $query = SalesItem::query(); // scope global: disp_flg = 1

        foreach (['name', 'discription'] as $column) {
            if (isset($option[$column]) && is_string($option[$column]) && $option[$column] !== '') {
                // buildMultipleSearchStrings(): cada palabra con ILIKE, todas en AND
                foreach (explode(' ', str_replace('　', ' ', $option[$column])) as $word) {
                    $query->where($column, 'ILIKE', '%' . $this->escapeLike($word) . '%');
                }
            }
        }

        if (isset($option['available_shop_cds']) && $option['available_shop_cds'] !== '' && is_string($option['available_shop_cds'])) {
            // buildMultipleSearchCds(): '{1,2}' o '1,2' → alguna tienda en OR
            $cds = SalesItem::parseCds($option['available_shop_cds']);
            $query->where(function ($q) use ($cds) {
                foreach ($cds as $cd) {
                    $q->orWhereRaw('? = ANY(available_shop_cds)', [$cd]);
                }
            });
        }

        if (isset($option['is_exchangeable_of_point'])) {
            $query->where('is_exchangeable_of_point', (int) $option['is_exchangeable_of_point']);
        }
        if (isset($option['number_of_points_exchanged_start'])) {
            $query->where('number_of_points_exchanged', '>=', (int) $option['number_of_points_exchanged_start']);
        }
        if (isset($option['number_of_points_exchanged_end'])) {
            $query->where('number_of_points_exchanged', '<=', (int) $option['number_of_points_exchanged_end']);
        }
        if (isset($option['hyouji_flg'])) {
            $query->where('hyouji_flg', (int) $option['hyouji_flg']);
        }

        $count = (clone $query)->count();
        $data = $query->orderByDesc('cd')->limit($limit)->offset(max(0, ($page - 1) * $limit))->get();

        if ($data->isEmpty()) {
            return null;
        }

        return (object) ['data' => $data, 'count' => $count, 'page' => $page, 'limit' => $limit];
    }

    /**
     * Guardado de edit.php (act = add / register). El legacy toma los valores con filter_input() sin limpiarlos
     * (los 説明 son HTML de CKEditor): se guardan igual. Devuelve el texto de error del legacy ('' si no hay).
     */
    public function save(SalesItem $item, array $data, ?UploadedFile $image, bool $deleteImage): string
    {
        $item->disp_flg = 1;
        $item->hyouji_flg = $this->intOrNull($data['hyouji_flg'] ?? null);
        $item->name = $data['name'] ?? null;
        $item->display_name = $data['display_name'] ?? null;
        $item->display_title = $data['display_title'] ?? null;
        $item->discription = $data['discription'] ?? null;
        $item->discription_detail = $data['discription_detail'] ?? null;
        $item->is_exchangeable_of_point = $this->intOrNull($data['is_exchangeable_of_point'] ?? null);
        $item->setAvailableShopCds((array) ($data['available_shop_cds'] ?? []));
        // Como el legacy: la ポイント交換数 solo se toca si se canjea por puntos
        if ($item->is_exchangeable_of_point) {
            $item->number_of_points_exchanged = $this->intOrNull($data['number_of_points_exchanged'] ?? null);
        }

        if (! $item->save()) {
            return '商品登録に失敗しました';
        }

        if ($image !== null) {
            return $this->images->store($item->cd, $image);
        }
        if ($deleteImage) {
            $this->images->delete($item->cd);
        }

        return '';
    }

    /**
     * Pagenation::getPagenation(): enlaces relativos `?<consulta sin page>&page=N`, 3 páginas a cada lado.
     */
    public function pagination(object $result, array $request): string
    {
        unset($request['page']);
        $q = http_build_query($request);
        if ($q !== '') {
            $q .= '&';
        }

        $page = $result->page > 0 && $result->page <= $result->count ? (int) $result->page : 1;
        $pageMax = $result->limit ? (int) ceil($result->count / $result->limit) : 0;
        $link = fn (int $n, string $text) => '<li class="page-item"><a class="page-link" href="?' . e($q) . 'page=' . $n . '">' . $text . '</a></li>';

        $html = '<ul class="pagination pagination-sm inline">';
        if ($page > 1) {
            $html .= $link($page - 1, '＜');
        }
        for ($i = self::DEFAULT_RANGE; $i > 0; $i--) {
            if ($page - $i >= 1) {
                $html .= $link($page - $i, (string) ($page - $i));
            }
        }
        $html .= '<li class="page-item visit"><a class="page-link">' . $page . '</a></li>';
        for ($i = 1; $i <= self::DEFAULT_RANGE && $page + $i <= $pageMax; $i++) {
            $html .= $link($page + $i, (string) ($page + $i));
        }
        if ($page < $pageMax) {
            $html .= $link($page + 1, '＞');
        }

        return $html;
    }

    private function intOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    /** El legacy pasaba la palabra tal cual a ILIKE: aquí % y _ se buscan como texto */
    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
