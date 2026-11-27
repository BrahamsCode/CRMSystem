<?php

namespace App\Services\Mypage;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Paginación de system/category1/inc/page_control.php (オススメ, 求人職種) y la propia de
 * recruit_list.php: `kensu` filas por página y `page`; una página mayor que la última se lleva a la
 * última. Devuelve los datos con los que las vistas arman los enlaces del legacy.
 *
 * @return array{items: \Illuminate\Support\Collection, total: int, kensu: int, page: int, maxPage: int, from: int}
 */
class LegacyPageControl
{
    public function paginate(Builder $query, Request $request, int $defaultKensu): array
    {
        $kensu = (int) $request->input('kensu');
        $kensu = $kensu > 0 ? $kensu : $defaultKensu;

        // getCountForPagination quita el ORDER BY (PostgreSQL no lo acepta en el count)
        $total = $query->toBase()->getCountForPagination();
        $maxPage = (int) ceil($total / $kensu);
        $page = max(1, min((int) $request->input('page') ?: 1, max(1, $maxPage)));
        $from = ($page - 1) * $kensu;

        return [
            'items' => $total > 0 ? $query->skip($from)->take($kensu)->get() : collect(),
            'total' => $total,
            'kensu' => $kensu,
            'page' => $page,
            'maxPage' => $maxPage,
            'from' => $from,
        ];
    }
}
