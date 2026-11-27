<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customers\CustomCategoryRequest;
use App\Models\Customers\CustomCategory;
use App\Services\Customers\CustomCategoryService;
use App\Services\Nucleo\LegacyUi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 顧客追加情報カテゴリ設定 (legacy `customer/search_cate_list.php`, `_new.php`, `_new_check.php`, `_new_up.php`,
 * `_update.php`, `_update_up.php`, `_delete_up.php`). Solo categorías de clientes (`category = 'category1'`).
 */
class CustomCategoryController extends Controller
{
    private const TITLE = '顧客追加情報カテゴリ設定';

    public function __construct(private CustomCategoryService $service, private LegacyUi $legacyUi) {}

    public function index(Request $request): View
    {
        $list = $this->service->paginate((int) ($request->input('page') ?: 1), (int) ($request->input('kensu') ?: 10));

        return view('admin.clientes.custom-categories.index', $list + [
            'siteName' => $this->legacyUi->siteName(),
            'kensuParam' => $request->input('kensu'),
        ]);
    }

    /** チェックした情報を更新する (act=update): vuelve a la misma página de la lista */
    public function updateFlags(Request $request): RedirectResponse
    {
        $this->service->updateFlags($request->all());

        return to_route('admin.clientes.custom-categories', $this->listParams($request));
    }

    /** ▲▼ (thisCd, thisRow = rank actual, kind = up|down) */
    public function sort(Request $request): RedirectResponse
    {
        $this->service->move((int) $request->input('thisCd'), (int) $request->input('thisRow'), (string) $request->input('kind'));

        return to_route('admin.clientes.custom-categories', $this->listParams($request));
    }

    public function create(): View
    {
        return view('admin.clientes.custom-categories.create', ['siteName' => $this->legacyUi->siteName()]);
    }

    public function store(CustomCategoryRequest $request): View
    {
        $this->service->create($request->all());

        return $this->processing('新規登録', '登録中です。', route('admin.clientes.custom-categories'));
    }

    public function edit(Request $request, CustomCategory $category): View
    {
        $this->ensureCustomers($category);

        return view('admin.clientes.custom-categories.edit', [
            'siteName' => $this->legacyUi->siteName(),
            'category' => $category,
            'page' => $request->input('page'),
            'kensu' => $request->input('kensu'),
        ]);
    }

    public function update(CustomCategoryRequest $request, CustomCategory $category): View
    {
        $this->ensureCustomers($category);
        $this->service->update($category, $request->all());

        return $this->processing('登録更新', '登録中です。', route('admin.clientes.custom-categories', $this->listParams($request)));
    }

    public function destroy(CustomCategory $category): View
    {
        $this->ensureCustomers($category);
        $this->service->delete($category);

        return $this->processing('登録削除', '削除中です。', route('admin.clientes.custom-categories'));
    }

    private function ensureCustomers(CustomCategory $category): void
    {
        abort_unless($category->category === CustomCategoryService::CATEGORY, 404);
    }

    private function listParams(Request $request): array
    {
        return array_filter(['page' => $request->input('page'), 'kensu' => $request->input('kensu')], fn ($v) => is_scalar($v) && $v !== '');
    }

    /** *_up.php: 「登録中です。」/「削除中です。」 y vuelta a la lista a los 2 s */
    private function processing(string $suffix, string $message, string $url): View
    {
        $pageTitle = self::TITLE.$suffix.' - '.$this->legacyUi->siteName();

        return view('admin.clientes.settings.processing', [
            'title' => $pageTitle,
            'heading' => '■'.$pageTitle,
            'message' => $message,
            'redirectUrl' => $url,
            'navLinks' => [
                [route('admin.clientes.custom-categories.create'), self::TITLE.'新規追加'],
                [route('admin.clientes.custom-categories', ['se_flg' => 1, 'act' => 'up']), self::TITLE.'登録一覧'],
            ],
        ]);
    }
}
