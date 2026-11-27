<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Http\Controllers\Controller;
use App\Models\Customers\ServiceCategory;
use App\Models\Staff;
use App\Services\Customers\ServiceCategoryService;
use App\Services\Nucleo\LegacyUi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 予約カテゴリー管理 / 施術カテゴリー de 美容 (legacy `system/category1/biyou/sejutsu_cate.php`,
 * `sejutsu_cate_register.php`, `sejutsu_cate_delete.php`; sin enlace en el menú).
 */
class ServiceCategoryController extends Controller
{
    public function __construct(private ServiceCategoryService $categories) {}

    /** Formulario (alta, o edición con ?cd=) y 施術カテゴリー一覧 */
    public function index(Request $request): View
    {
        $staff = $this->staff($request);
        $shopCd = $this->sessionShopCd($request);
        $item = filled($request->query('cd')) ? $this->categories->find($staff, $shopCd, (int) $request->query('cd')) : null;

        return view('admin.clientes.service-categories.index', [
            'siteName' => app(LegacyUi::class)->siteName(),
            'item' => $item,
            'items' => $this->categories->list($staff, $shopCd),
            'shops' => $this->categories->shops($staff, $shopCd),
            'isMaster' => $staff->isMaster(),
        ]);
    }

    /** act=new */
    public function store(Request $request): View|RedirectResponse
    {
        return $this->save($request, new ServiceCategory);
    }

    /** act=up */
    public function update(Request $request, int $cd): View|RedirectResponse
    {
        return $this->save($request, $this->find($request, $cd));
    }

    /** 削除 (disp_flg = '9') */
    public function destroy(Request $request, int $cd): View
    {
        $this->categories->delete($this->find($request, $cd));

        return $this->processing('施術カテゴリー削除', '■施術カテゴリー削除', '削除中です。');
    }

    // ---------------------------------------------------------------- auxiliares

    private function save(Request $request, ServiceCategory $category): View|RedirectResponse
    {
        $shopCd = (string) $request->input('shop_cd', '');
        if (! $this->staff($request)->isMaster()) {
            $shopCd = (string) $this->sessionShopCd($request); // sin master el legacy la ponía en un campo oculto
        }
        $name = (string) $request->input('cate_name', '');
        // submitChk() del legacy (solo JS): sin tienda o sin nombre no se registra
        if ($shopCd === '' || trim($name) === '') {
            return redirect()->route('admin.clientes.service-categories.index', $category->exists ? ['cd' => $category->cd] : []);
        }

        $this->categories->save($category, $shopCd, $name);

        return $this->processing('施術カテゴリー登録', '■施術カテゴリー登録', '登録中です。');
    }

    /** Pantalla 「登録中です。」/「削除中です。」 con meta Refresh de 1 s a la lista */
    private function processing(string $title, string $heading, string $message): View
    {
        return view('admin.clientes.settings.processing', [
            'title' => $title . ' ' . app(LegacyUi::class)->siteName(),
            'heading' => $heading,
            'message' => $message,
            'redirectUrl' => route('admin.clientes.service-categories.index'),
            'refresh' => 1,
        ]);
    }

    private function find(Request $request, int $cd): ServiceCategory
    {
        $category = $this->categories->find($this->staff($request), $this->sessionShopCd($request), $cd);
        abort_if($category === null, 404);

        return $category;
    }

    private function staff(Request $request): Staff
    {
        return $request->user('admin');
    }

    private function sessionShopCd(Request $request): int
    {
        return (int) (session('shop_cd') ?? $this->staff($request)->shop_cd);
    }
}
