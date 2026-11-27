<?php

namespace App\Services\Terminales;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Paginación de system/category7/inc/page_control.php: `kensu` filas por página (10 por
 * defecto) y `page`; una página mayor que la última se lleva a la última.
 */
class LegacyListPaginator
{
    public const DEFAULT_KENSU = 10;

    public static function kensu(Request $request): int
    {
        $kensu = (int) $request->input('kensu');

        return $kensu > 0 ? $kensu : self::DEFAULT_KENSU;
    }

    public function paginate(Builder $query, Request $request, string $path): LengthAwarePaginator
    {
        $kensu = self::kensu($request);
        // getCountForPagination quita el ORDER BY (PostgreSQL no acepta count(*) … ORDER BY rank)
        $total = $query->toBase()->getCountForPagination();
        $maxPage = (int) ceil($total / $kensu);

        $page = (int) $request->input('page') ?: 1;
        $page = max(1, min($page, $maxPage));

        return new LengthAwarePaginator(
            $query->forPage($page, $kensu)->get(),
            $total,
            $kensu,
            $page,
            ['path' => $path],
        );
    }
}
