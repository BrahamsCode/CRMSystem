<?php

namespace App\Http\Controllers\Admin\Senalizacion;

use App\Services\Senalizacion\SignageContentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * signage/list/contents_category.php (画像サイズ別一覧, kind=image) y movie_contents_category.php (kind=video).
 * El formulario de alta está comentado en el HTML del legacy (las tallas se daban de alta en la BD);
 * se conserva así, pero las acciones de alta y borrado que el PHP sí tenía existen.
 * Correcciones: el alta guarda también `name` y el lado de vídeo usa `movie_contents_category`
 * (el legacy listaba, daba de alta y borraba en `contents_category`).
 */
class ContentCategoryController extends SignageController
{
    public function index(Request $request, string $kind, SignageContentService $contents): Response
    {
        $categories = $contents->categoryModel($kind)::query()->toBase()->orderBy('cd')->get()->map(fn ($r) => (array) $r)->all();
        $counts = $contents->model($kind)::query()->toBase()
            ->selectRaw('contents_category_cd, count(*) as total')
            ->groupBy('contents_category_cd')
            ->pluck('total', 'contents_category_cd')
            ->all();

        return $this->legacyView->screen($request, $kind === 'image' ? 'contents-category' : 'movie-contents-category', [
            '_kind' => $kind,
            'data' => $categories,
            '_counts' => $counts,
            '_form_action' => route('admin.senalizacion.content-categories.store', $kind),
        ]);
    }

    public function store(Request $request, string $kind, SignageContentService $contents): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'width' => ['required', 'integer', 'min:1', 'max:100000'],
            'height' => ['required', 'integer', 'min:1', 'max:100000'],
        ]);
        $contents->categoryModel($kind)::create($data + ['flg' => (int) $request->input('flg') === 1 ? 1 : 0]);

        return $this->done(route('admin.senalizacion.content-categories.index', $kind), self::SAVED);
    }

    public function destroy(string $kind, int $cd, SignageContentService $contents): RedirectResponse
    {
        $contents->categoryModel($kind)::findOrFail($cd)->delete();

        return $this->done(route('admin.senalizacion.content-categories.index', $kind), self::DELETED);
    }
}
