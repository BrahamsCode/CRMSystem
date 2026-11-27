<?php

namespace App\Http\Controllers\Admin\Mypage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mypage\RecruitCategoryRequest;
use App\Services\Mypage\MypageShop;
use App\Services\Mypage\RecruitCategoryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 求人職種管理 (system/category6/recruit/recruit_category.php, recruit_category_register.php,
 * recruit_category_delete.php).
 */
class RecruitCategoryController extends Controller
{
    public function __construct(
        private readonly RecruitCategoryService $service,
        private readonly MypageShop $shop,
    ) {}

    /** recruit_category.php: formulario (alta o ?cd= modificación) + lista */
    public function index(Request $request): View
    {
        $shops = $this->shop->selectableShops();

        return view('admin.mypage.recruit-categories.index', [
            'category' => filled($request->input('cd')) ? $this->service->find($request->input('cd')) : null,
            'editing' => filled($request->input('cd')),
            'shops' => $shops,
            'singleShop' => ($shops->count() === 1 || ! $this->shop->isMaster()) ? $shops->first() : null,
            'showAllShopsOption' => $this->shop->isMaster(),
            'shopNames' => $this->shop->shopNames(),
            'pager' => $this->service->paginate($request),
            'service' => $this->service,
        ]);
    }

    /** recruit_category_register.php */
    public function store(RecruitCategoryRequest $request): View
    {
        $category = $request->input('act') === 'up' ? $this->service->find($request->input('cd')) : null;
        abort_if($request->input('act') === 'up' && $category === null, 404);

        $this->service->save($request->shopCd($this->shop), (string) $request->input('cate_name'), $category);

        return view('admin.mypage.processing', [
            'screen' => 'recruit-category-store',
            'refreshUrl' => route('admin.mypage.recruit-categories.index'),
        ]);
    }

    /** recruit_category_delete.php */
    public function destroy(Request $request): View
    {
        if ($request->filled('delete_flg')) {
            $this->service->delete($request->input('cd'));
        }

        return view('admin.mypage.processing', [
            'screen' => 'recruit-category-destroy',
            'refreshUrl' => route('admin.mypage.recruit-categories.index'),
        ]);
    }
}
