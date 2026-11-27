<?php

namespace App\Http\Controllers\Admin\Senalizacion;

use App\Services\Senalizacion\SignageContentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * signage/list/contents.php + contents_detail.php (画像, kind=image) y movie_contents*.php (動画, kind=video):
 * lista de la talla, alta/cambio con archivo, borrado y la ventana de detalle.
 */
class ContentController extends SignageController
{
    public function index(Request $request, string $kind, int $categoryCd, SignageContentService $contents): Response
    {
        $category = $contents->categoryModel($kind)::query()->toBase()->where('cd', $categoryCd)->first();
        $cd = (int) $request->query('cd');
        $detail = $cd > 0 ? $contents->model($kind)::query()->toBase()->where('cd', $cd)->first() : null;

        return $this->legacyView->screen($request, $kind === 'image' ? 'contents' : 'movie-contents', [
            '_kind' => $kind,
            '_request' => ['contents_category_cd' => $categoryCd] + $request->query(),
            '_contents_category_data' => $category ? (array) $category : [],
            'data' => $contents->model($kind)::query()->toBase()->where('contents_category_cd', $categoryCd)->orderBy('cd')->get()
                ->map(fn ($r) => (array) $r)->all(),
            '_detail_data' => $detail ? (array) $detail : ['name' => '', $contents->column($kind) => ''],
            '_form_action' => $detail
                ? route('admin.senalizacion.contents.update', [$kind, $cd])
                : route('admin.senalizacion.contents.store', [$kind, $categoryCd]),
        ]);
    }

    public function store(Request $request, string $kind, int $categoryCd, SignageContentService $contents): RedirectResponse
    {
        $category = $contents->categoryModel($kind)::findOrFail($categoryCd);
        $back = route('admin.senalizacion.contents.index', [$kind, $categoryCd]);
        if (($file = $request->file('upfile')) && ! $contents->extension($kind, $file)) {
            return $this->failed($back, SignageContentService::INVALID);
        }
        $contents->save($kind, $category, $request->post(), $file);

        return $this->done($back, self::SAVED);
    }

    /** Cambio desde la lista (vuelve a la lista) o desde la ventana de detalle (vuelve al detalle) */
    public function update(Request $request, string $kind, int $cd, SignageContentService $contents): RedirectResponse
    {
        $content = $contents->model($kind)::findOrFail($cd);
        $category = $contents->categoryModel($kind)::findOrFail($content->contents_category_cd);
        $back = $request->routeIs('admin.senalizacion.contents.detail.update')
            ? route('admin.senalizacion.contents.detail', [$kind, $cd])
            : route('admin.senalizacion.contents.index', [$kind, $category->cd, 'cd' => $cd]);
        if (($file = $request->file('upfile')) && ! $contents->extension($kind, $file)) {
            return $this->failed($back, SignageContentService::INVALID);
        }
        $contents->save($kind, $category, $request->post(), $file, $content);

        return $this->done($back, self::SAVED);
    }

    public function destroy(string $kind, int $cd, SignageContentService $contents): RedirectResponse
    {
        $content = $contents->model($kind)::findOrFail($cd);
        $categoryCd = $content->contents_category_cd;
        $contents->delete($kind, $content);

        return $this->done(route('admin.senalizacion.contents.index', [$kind, $categoryCd]), self::DELETED);
    }

    /** contents_detail.php: ventana emergente (window.open) con el nombre y el archivo */
    public function detail(Request $request, string $kind, int $cd, SignageContentService $contents): Response
    {
        $detail = (array) $contents->model($kind)::query()->toBase()->where('cd', $cd)->first();
        abort_if($detail === [], 404);
        $category = $contents->categoryModel($kind)::query()->toBase()->where('cd', $detail['contents_category_cd'])->first();

        return $this->legacyView->screen($request, $kind === 'image' ? 'contents-detail' : 'movie-contents-detail', [
            '_kind' => $kind,
            '_request' => ['contents_category_cd' => $detail['contents_category_cd'], 'cd' => $cd] + $request->query(),
            '_contents_category_data' => $category ? (array) $category : [],
            '_detail_data' => $detail,
            '_form_action' => route('admin.senalizacion.contents.detail.update', [$kind, $cd]),
        ], 'popup');
    }
}
