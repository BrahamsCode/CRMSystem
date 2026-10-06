<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Http\Requests\Admin\StoreCustomCategoryRequest;
use App\Models\CustomCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Categorías de información adicional */
class CustomCategoryController extends ModuleController
{
    public function index(): View
    {
        return view('admin.customers.custom-categories', [
            'categories' => CustomCategory::withCount('fields')
                ->where('shop_id', $this->shop()?->id)
                ->ordered()
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.customers.custom-category-form', [
            'category' => null,
            'shop' => $this->shop(),
        ]);
    }

    public function store(StoreCustomCategoryRequest $request): RedirectResponse
    {
        $category = CustomCategory::create(
            $request->datos() + [
                'shop_id' => $this->shop()?->id,
                'sort' => (int) CustomCategory::where('shop_id', $this->shop()?->id)->max('sort') + 1,
            ]
        );

        return redirect()
            ->route('admin.customers.custom-categories')
            ->with('status', "Categoría «{$category->name}» creada.");
    }

    public function edit(CustomCategory $category): View
    {
        return view('admin.customers.custom-category-form', [
            'category' => $category,
            'shop' => $this->shop(),
        ]);
    }

    public function update(StoreCustomCategoryRequest $request, CustomCategory $category): RedirectResponse
    {
        $category->update($request->datos());

        return redirect()
            ->route('admin.customers.custom-categories')
            ->with('status', "Categoría «{$category->name}» actualizada.");
    }

    public function destroy(CustomCategory $category): RedirectResponse
    {
        $name = $category->name;
        $category->delete();

        return redirect()
            ->route('admin.customers.custom-categories')
            ->with('status', "Categoría «{$name}» eliminada.");
    }

    /** Guarda los interruptores de búsqueda y visualización de la tabla */
    public function updateFlags(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'categories' => ['array'],
            'categorias.*.search_flg' => ['nullable', 'in:0,1'],
            'categorias.*.display_flg' => ['nullable', 'in:0,1'],
            'order' => ['array'],
        ]);

        foreach ($data['categories'] ?? [] as $id => $flags) {
            CustomCategory::where('shop_id', $this->shop()?->id)
                ->where('id', $id)
                ->update([
                    'search_flg' => (int) ($flags['search_flg'] ?? 0),
                    'display_flg' => (int) ($flags['display_flg'] ?? 0),
                ]);
        }

        foreach (array_values($data['order'] ?? []) as $posicion => $id) {
            CustomCategory::where('shop_id', $this->shop()?->id)
                ->where('id', $id)
                ->update(['sort' => $posicion + 1]);
        }

        return redirect()
            ->route('admin.customers.custom-categories')
            ->with('status', 'Cambios guardados.');
    }
}
