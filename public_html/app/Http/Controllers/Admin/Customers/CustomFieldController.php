<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customers\CustomFieldRequest;
use App\Models\Customers\CustomCategory;
use App\Models\Customers\CustomField;
use App\Services\Customers\CustomCategoryService;
use App\Services\Customers\CustomerFormService;
use App\Services\Customers\CustomFieldService;
use App\Services\Nucleo\LegacyUi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 検索フォーム部品設定: campos de una categoría de 顧客追加情報 (legacy `customer/search_form_list.php`, `_new.php`,
 * `_new_check.php`, `_new_up.php`, `_update.php`, `_update_up.php`, `_delete_up.php`).
 */
class CustomFieldController extends Controller
{
    public function __construct(private CustomFieldService $service, private LegacyUi $legacyUi) {}

    public function index(Request $request, CustomCategory $category, CustomerFormService $form): View
    {
        $this->ensureCustomers($category);
        $list = $this->service->paginate($category, (int) ($request->input('page') ?: 1), (int) ($request->input('kensu') ?: 10));
        $staff = auth('admin')->user();
        // Vista previa del tipo メニュー項目 (makeMenuTable): menús de la tienda de la sesión si no es master
        $hasMenu = $list['items']->contains('form_type', 'メニュー項目');

        return view('admin.clientes.custom-fields.index', $list + [
            'siteName' => $this->legacyUi->siteName(),
            'category' => $category,
            'kensuParam' => $request->input('kensu'),
            'menuItems' => $hasMenu ? $form->menuItems($staff->isMaster() ? null : (int) (session('shop_cd') ?? $staff->shop_cd)) : collect(),
        ]);
    }

    /** URL legacy search_form_list.php?pcd=N: a la lista de la categoría; sin pcd numérico, a la de categorías como el legacy */
    public function legacyList(Request $request): RedirectResponse
    {
        $pcd = (string) $request->query('pcd');
        if (! ctype_digit($pcd)) {
            return to_route('admin.clientes.custom-categories');
        }

        return to_route('admin.clientes.custom-fields', [(int) $pcd] + $this->listParams($request));
    }

    /** チェックした情報を更新する (必須・検索・メルマガ置き換え・表示) */
    public function updateFlags(Request $request, CustomCategory $category): RedirectResponse
    {
        $this->ensureCustomers($category);
        $this->service->updateFlags($category, $request->all());

        return to_route('admin.clientes.custom-fields', [$category->cd] + $this->listParams($request));
    }

    /** ▲▼ (thisCd, thisRow = rank actual, kind = up|down) */
    public function sort(Request $request, CustomCategory $category): RedirectResponse
    {
        $this->ensureCustomers($category);
        $this->service->move($category, (int) $request->input('thisCd'), (int) $request->input('thisRow'), (string) $request->input('kind'));

        return to_route('admin.clientes.custom-fields', [$category->cd] + $this->listParams($request));
    }

    public function create(CustomCategory $category): View
    {
        $this->ensureCustomers($category);

        return view('admin.clientes.custom-fields.create', $this->formData($category));
    }

    public function store(CustomFieldRequest $request, CustomCategory $category): View
    {
        $this->ensureCustomers($category);
        $this->service->create($category, $request->all());

        return $this->processing($category, '新規登録', '新規登録 - '.$this->legacyUi->siteName(), '登録中です。', route('admin.clientes.custom-fields', $category->cd));
    }

    public function edit(Request $request, CustomField $field): View
    {
        $category = $this->categoryOf($field);

        return view('admin.clientes.custom-fields.edit', $this->formData($category) + [
            'field' => $field,
            'values' => $this->service->formValues($field),
            'page' => $request->input('page'),
            'kensu' => $request->input('kensu'),
        ]);
    }

    public function update(CustomFieldRequest $request, CustomField $field): View
    {
        $category = $this->categoryOf($field);
        $this->service->update($field, $request->all());

        return $this->processing($category, '登録更新', '登録更新', '登録中です。', route('admin.clientes.custom-fields', [$category->cd] + $this->listParams($request)));
    }

    public function destroy(CustomField $field): View
    {
        $category = $this->categoryOf($field);
        $this->service->delete($field);

        return $this->processing($category, '登録削除 - '.$this->legacyUi->siteName(), '登録削除 - '.$this->legacyUi->siteName(), '削除中です。', route('admin.clientes.custom-fields', $category->cd));
    }

    private function formData(CustomCategory $category): array
    {
        return [
            'siteName' => $this->legacyUi->siteName(),
            'category' => $category,
            'pageTitle' => $this->pageTitle($category),
            'formTypes' => CustomFieldService::FORM_TYPES,
            'optionTypes' => CustomFieldService::OPTION_TYPES,
            'styleClasses' => CustomFieldService::STYLE_CLASSES,
        ];
    }

    /** El legacy quería 『カテゴリー名』 (en PHP 8 salía vacío: no se reproduce) */
    private function pageTitle(CustomCategory $category): string
    {
        return '『'.html_entity_decode((string) $category->name, ENT_COMPAT | ENT_HTML401, 'UTF-8').'』 検索フォーム部品設定';
    }

    private function categoryOf(CustomField $field): CustomCategory
    {
        $category = CustomCategory::find($field->tb_search_cate_cd);
        abort_unless($category && $category->category === CustomCategoryService::CATEGORY, 404);

        return $category;
    }

    private function ensureCustomers(CustomCategory $category): void
    {
        abort_unless($category->category === CustomCategoryService::CATEGORY, 404);
    }

    private function listParams(Request $request): array
    {
        return array_filter(['page' => $request->input('page'), 'kensu' => $request->input('kensu')], fn ($v) => is_scalar($v) && $v !== '');
    }

    /** *_up.php: 「登録中です。」/「削除中です。」 y vuelta a la lista a los 2 s ($title: sufijo del <title>; $heading: del h2, como cada *_up.php) */
    private function processing(CustomCategory $category, string $title, string $heading, string $message, string $url): View
    {
        $pageTitle = $this->pageTitle($category);

        return view('admin.clientes.settings.processing', [
            'title' => $pageTitle.$title,
            'heading' => '■'.$pageTitle.$heading,
            'message' => $message,
            'redirectUrl' => $url,
            'navLinks' => [
                [route('admin.clientes.custom-fields.create', $category->cd), $pageTitle.'新規追加'],
                [route('admin.clientes.custom-fields', [$category->cd, 'se_flg' => 1, 'act' => 'up']), $pageTitle.'登録一覧'],
            ],
        ]);
    }
}
