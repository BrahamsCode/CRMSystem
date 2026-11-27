<?php

namespace App\Http\Controllers\Admin\Ventas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ventas\StoreProductRequest;
use App\Models\Sales\SalesItem;
use App\Services\Ventas\ProductImageService;
use App\Services\Ventas\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** 商品管理 (system/category2/item/*.php) */
class ProductController extends Controller
{
    public function __construct(private ProductService $products, private ProductImageService $images) {}

    /** 商品検索・一覧 (list.php) */
    public function index(Request $request): View
    {
        $query = $request->query();
        $result = $this->products->search($query);

        return view('admin.ventas.products.index', [
            'result' => $result,
            'pagination' => $result ? $this->products->pagination($result, $query) : '',
            'shops' => $this->products->shops(),
            'images' => $this->images,
            'queryString' => (string) $request->getQueryString(),
        ]);
    }

    /** 商品新規登録 (edit.php sin cd) */
    public function create(Request $request): View
    {
        return $this->form($request, new SalesItem, 'add', '新規登録');
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $item = new SalesItem;
        $error = $this->products->save($item, $request->all(), $request->file('myImage'), $request->boolean('img_delete'));

        // Sin error el legacy va a edit.php?cd=; con error de imagen se queda en el producto ya creado
        return redirect()->route('admin.ventas.products.edit', $item->cd)->with('error', $error);
    }

    /** 商品編集 (edit.php?cd=) */
    public function edit(Request $request, int $cd): View
    {
        return $this->form($request, SalesItem::findOrFail($cd), 'register', '編集');
    }

    public function update(StoreProductRequest $request, int $cd): RedirectResponse
    {
        $item = SalesItem::findOrFail($cd);
        $error = $this->products->save($item, $request->all(), $request->file('myImage'), $request->boolean('img_delete'));

        // El legacy vuelve a mostrar edit.php con la misma consulta (la acción del formulario es "")
        return redirect()->to(route('admin.ventas.products.edit', $item->cd) . $this->queryString($request))->with('error', $error);
    }

    /** ajax_edit.php: validación previa que espera el JS de edit.php */
    public function validateForm(Request $request): JsonResponse
    {
        $validator = StoreProductRequest::validatorFor($request->all());

        return response()->json($validator->fails()
            ? ['errorMsg' => $validator->errors()->toArray(), 'status' => 'error']
            : ['status' => 'success']);
    }

    /** 削除 (ajax_list_delete.php?cd=): disp_flg = 0 */
    public function destroy(Request $request, ?int $cd = null): JsonResponse
    {
        $item = SalesItem::find($cd ?? (int) $request->input('cd'));

        return response()->json(['status' => $item?->markDeleted() ? 'success' : 'error']);
    }

    /** list.php del legacy (menú, marcadores) → lista nueva con la misma consulta */
    public function legacyList(Request $request): RedirectResponse
    {
        return redirect()->route('admin.ventas.products.index', $request->query());
    }

    /** edit.php del legacy → edición (con cd) o alta */
    public function legacyEdit(Request $request): RedirectResponse
    {
        $query = $request->query();
        $cd = (int) ($query['cd'] ?? 0);
        unset($query['cd']);

        return $cd > 0
            ? redirect()->route('admin.ventas.products.edit', ['cd' => $cd] + $query)
            : redirect()->route('admin.ventas.products.create', $query);
    }

    private function form(Request $request, SalesItem $item, string $act, string $title): View
    {
        return view('admin.ventas.products.edit', [
            'item' => $item,
            'act' => $act,
            'title' => $title,
            'shops' => $this->products->shops(),
            'imageUrl' => $item->exists ? $this->images->previewUrl($item->cd) : null,
            'formAction' => $item->exists
                ? route('admin.ventas.products.update', $item->cd) . $this->queryString($request)
                : route('admin.ventas.products.store'),
            // 一覧に戻る: el legacy conserva solo name y discription si había consulta
            'backUrl' => route('admin.ventas.products.index') . ($request->query() ? '?name=' . rawurlencode((string) $request->query('name')) . '&discription=' . rawurlencode((string) $request->query('discription')) : ''),
        ]);
    }

    private function queryString(Request $request): string
    {
        $qs = (string) $request->getQueryString();

        return $qs !== '' ? '?' . $qs : '';
    }
}
