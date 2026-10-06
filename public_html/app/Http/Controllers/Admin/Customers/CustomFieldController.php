<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Enums\CustomFieldType;
use App\Enums\DisplayScope;
use App\Enums\FieldSize;
use App\Models\CustomCategory;
use App\Models\CustomField;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;

/** Campos de las categorías de información adicional */
class CustomFieldController extends ModuleController
{
    public function index(Request $request): View
    {
        $categories = CustomCategory::with('fields.options')
            ->where('shop_id', $this->shop()?->id)
            ->ordered()
            ->get();

        $current = $categories->firstWhere('id', $request->integer('categoria'))
            ?? $categories->firstWhere('slug', 'mascota')
            ?? $categories->first();

        return view('admin.customers.custom-fields', [
            'categories' => $categories,
            'current' => $current,
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.customers.custom-field-form', [
            'field' => null,
            'category' => $this->categoriaDe($request->integer('categoria')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $category = $this->categoriaDe($request->integer('custom_category_id'));
        $data = $this->validar($request, $category);

        $field = $category->fields()->create(
            $data + [
                'slug' => $this->slug($data['name'], $category),
                'sort' => (int) $category->fields()->max('sort') + 1,
            ]
        );

        $this->guardarOpciones($field, $request->input('opciones', []));

        return redirect()
            ->route('admin.customers.custom-fields', ['category' => $category->id])
            ->with('status', "Campo «{$field->name}» creado.");
    }

    public function edit(CustomField $field): View
    {
        return view('admin.customers.custom-field-form', [
            'field' => $field->load('options'),
            'category' => $field->category,
        ]);
    }

    public function update(Request $request, CustomField $field): RedirectResponse
    {
        $field->update($this->validar($request, $field->category, $field));
        $this->guardarOpciones($field, $request->input('opciones', []));

        return redirect()
            ->route('admin.customers.custom-fields', ['category' => $field->custom_category_id])
            ->with('status', "Campo «{$field->name}» actualizado.");
    }

    public function destroy(CustomField $field): RedirectResponse
    {
        $categoriaId = $field->custom_category_id;
        $name = $field->name;
        $field->delete();

        return redirect()
            ->route('admin.customers.custom-fields', ['category' => $categoriaId])
            ->with('status', "Campo «{$name}» eliminado.");
    }

    private function validar(Request $request, CustomCategory $category, ?CustomField $field = null): array
    {
        return $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('custom_fields', 'name')
                    ->where('custom_category_id', $category->id)
                    ->ignore($field?->id)
                    ->whereNull('deleted_at'),
            ],
            'type' => ['required', new Enum(CustomFieldType::class)],
            'unit' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string'],
            'size' => ['nullable', new Enum(FieldSize::class)],
            'display_scope' => ['required', new Enum(DisplayScope::class)],
            'required_flg' => ['required', 'in:0,1'],
            'search_flg' => ['required', 'in:0,1'],
            'mail_magazine_flg' => ['required', 'in:0,1'],
        ], [], [
            'name' => 'título del campo',
            'type' => 'tipo de campo',
            'display_scope' => 'dónde se muestra',
        ]);
    }

    /** Reemplaza las opciones del campo; solo aplica a select, radio y casillas */
    private function guardarOpciones(CustomField $field, array $options): void
    {
        if (! $field->usesOptions()) {
            $field->options()->delete();

            return;
        }

        $field->options()->delete();

        $orden = 0;

        foreach ($options as $option) {
            $label = trim((string) ($option['label'] ?? ''));

            if ($label === '') {
                continue;
            }

            $field->options()->create([
                'label' => $label,
                'value' => trim((string) ($option['value'] ?? '')) ?: Str::slug($label),
                'sort' => ++$orden,
            ]);
        }
    }

    private function categoriaDe(?int $id): CustomCategory
    {
        return CustomCategory::where('shop_id', $this->shop()?->id)
            ->findOrFail($id);
    }

    private function slug(string $name, CustomCategory $category): string
    {
        $base = Str::slug($name) ?: 'campo';
        $slug = $base;
        $n = 2;

        while ($category->fields()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $n++;
        }

        return $slug;
    }
}
