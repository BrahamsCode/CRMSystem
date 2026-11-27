<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\StoreProductRequest;
use App\Models\Sales\SalesItem;
use App\Models\Shop;
use App\Services\Sales\ProductImageStore;
use App\Services\Sales\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 商品管理 (legacy: system/category2/item/list.php, edit.php, ajax_edit.php, ajax_list_delete.php).
 */
class ProductController extends Controller
{
    public function __construct(private ProductService $products, private ProductImageStore $images) {}

    /** 商品検索・一覧 */
    public function index(Request $request): View
    {
        $paginator = $this->products->search($request->query());

        return view('admin.ventas.products.index', [
            'paginator' => $paginator,
            'shopNames' => Shop::query()->orderBy('cd')->pluck('kaishamei', 'cd'),
            'images' => $paginator->getCollection()->mapWithKeys(fn (SalesItem $item) => [$item->cd => $this->images->smallUrl($item->cd)]),
            'query' => $request->query(),
        ]);
    }

    /** 新規登録 */
    public function create(Request $request): View
    {
        return $this->form($request, new SalesItem);
    }

    /** 編集 (?cd= del legacy) */
    public function edit(Request $request, int $cd): View
    {
        return $this->form($request, SalesItem::query()->findOrFail($cd));
    }

    /** edit.php act=add: vuelve a edit.php?cd= del nuevo producto */
    public function store(StoreProductRequest $request): RedirectResponse
    {
        return $this->save($request, new SalesItem);
    }

    /** edit.php act=register */
    public function update(StoreProductRequest $request, int $cd): RedirectResponse
    {
        return $this->save($request, SalesItem::query()->findOrFail($cd));
    }

    /** ajax_edit.php: {"status":"success"} o {"status":"error","errorMsg":{campo:[mensajes]}} */
    public function validateForm(Request $request): JsonResponse
    {
        $validator = StoreProductRequest::makeValidator($request->all());

        return response()->json($validator->fails()
            ? ['errorMsg' => $validator->errors()->toArray(), 'status' => 'error']
            : ['status' => 'success']);
    }

    /** ajax_list_delete.php: disp_flg = 0 y {"status":"success"} */
    public function destroy(Request $request): JsonResponse
    {
        $item = SalesItem::query()->find((int) $request->input('cd'));

        return response()->json(['status' => $item && $this->products->delete($item) ? 'success' : 'error']);
    }

    /** edit.php por la URL del legacy: GET ?cd= edita, sin cd da de alta */
    public function legacyEdit(Request $request): View
    {
        return filled($request->query('cd')) ? $this->edit($request, (int) $request->query('cd')) : $this->create($request);
    }

    /** edit.php por POST (act=add | register) */
    public function legacySave(StoreProductRequest $request): RedirectResponse
    {
        return $request->input('act') === 'register'
            ? $this->update($request, (int) $request->input('cd'))
            : $this->store($request);
    }

    private function form(Request $request, SalesItem $item): View
    {
        return view('admin.ventas.products.edit', [
            'item' => $item,
            'shops' => Shop::query()->orderBy('cd')->get(['cd', 'kaishamei']),
            'availableShopCds' => $item->exists ? $item->availableShopCds() : [],
            'imageUrl' => $item->exists ? $this->images->previewUrl($item->cd) : '',
            // Volver a la lista con la búsqueda: el legacy solo conservaba name y discription
            'backQuery' => $request->query() ? ['name' => (string) $request->query('name'), 'discription' => (string) $request->query('discription')] : [],
        ]);
    }

    private function save(StoreProductRequest $request, SalesItem $item): RedirectResponse
    {
        $ok = $this->products->save($item, $request->all(), $request->file('myImage'), (bool) $request->input('img_delete'));

        $redirect = redirect()->route('admin.ventas.products.edit', $item->cd);

        return $ok ? $redirect : $redirect->with('product_error', __('ventas.product.upload_failed', [], 'ja'));
    }
}
