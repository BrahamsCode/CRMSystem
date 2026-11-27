<?php

namespace App\Http\Controllers\Admin\Reservations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reservations\ReservationMenuCategoryRequest;
use App\Models\Reservations\ReservationMenuCategory;
use App\Services\Reservations\ReservationMenuCategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 予約項目カテゴリ管理 (legacy: category9/yoyaku/yoyaku_koumoku_category.php, yoyaku_koumoku_category_register.php
 * y ajax_yoyaku_koumoku_category.php, que solo usa esta pantalla).
 * Las acciones de la lista (▲▼, 表示, 削除) responden con la lista actualizada, como el AJAX del legacy.
 */
class ReservationMenuCategoryController extends Controller
{
    public function __construct(private ReservationMenuCategoryService $categories) {}

    /** Formulario (alta, o edición con `?cd=`) y contenedor de la lista */
    public function index(Request $request): View
    {
        return view('admin.reservas.menu_categories', [
            'category' => filled($request->query('cd')) ? ReservationMenuCategory::query()->find((int) $request->query('cd')) : null,
        ]);
    }

    public function store(ReservationMenuCategoryRequest $request): RedirectResponse
    {
        $this->categories->save(new ReservationMenuCategory, $request->validated('name'));

        return redirect()->route('admin.reservas.menu-categories.index');
    }

    public function update(ReservationMenuCategoryRequest $request, ReservationMenuCategory $category): RedirectResponse
    {
        $this->categories->save($category, $request->validated('name'));

        return redirect()->route('admin.reservas.menu-categories.index');
    }

    /** 登録カテゴリ一覧 (lo que devolvía el AJAX sin `send_type`) */
    public function list(): View
    {
        return $this->renderList();
    }

    /** ▲▼: `send_type` = rankup / rankdown */
    public function sort(Request $request, ReservationMenuCategory $category): View
    {
        $request->validate(['send_type' => ['required', 'in:rankup,rankdown']]);
        $this->categories->move($category, $request->input('send_type'));

        return $this->renderList();
    }

    /** 表示 (checkbox) */
    public function toggle(ReservationMenuCategory $category): View
    {
        $this->categories->toggleVisibility($category);

        return $this->renderList();
    }

    public function destroy(ReservationMenuCategory $category): View
    {
        $this->categories->delete($category);

        return $this->renderList();
    }

    private function renderList(): View
    {
        return view('admin.reservas.partials.menu_category_list', [
            'categories' => ReservationMenuCategory::query()
                ->where('disp_flg', ReservationMenuCategory::ACTIVE_FLAG)
                ->orderBy('rank')
                ->orderBy('cd')
                ->get(),
        ]);
    }
}
