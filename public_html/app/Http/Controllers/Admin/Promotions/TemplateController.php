<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Enums\TemplateCategory;
use App\Models\MessageTemplate;
use App\Services\Promotions\MessageRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;

/** Plantillas de mensajes (テンプレート管理) */
class TemplateController extends ModuleController
{
    public function index(Request $request): View
    {
        $category = TemplateCategory::tryFrom($request->integer('category'));

        return view('admin.promotions.templates.index', [
            'templates' => MessageTemplate::when($category, fn ($q) => $q->where('category', $category))->ordered()->get(),
            'category' => $category,
        ]);
    }

    public function create(): View
    {
        return view('admin.promotions.templates.form', [
            'template' => new MessageTemplate(['category' => TemplateCategory::Newsletter, 'html_flg' => 0]),
            'variables' => MessageRenderer::variables(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $template = MessageTemplate::create($data + ['sort' => (int) MessageTemplate::max('sort') + 1]);

        return redirect()->route('admin.promotions.templates.index')->with('status', "Plantilla «{$template->name}» creada.");
    }

    public function edit(MessageTemplate $template): View
    {
        return view('admin.promotions.templates.form', [
            'template' => $template,
            'variables' => MessageRenderer::variables(),
        ]);
    }

    public function update(Request $request, MessageTemplate $template): RedirectResponse
    {
        $template->update($this->validated($request));

        return redirect()->route('admin.promotions.templates.index')->with('status', "Plantilla «{$template->name}» guardada.");
    }

    public function destroy(MessageTemplate $template): RedirectResponse
    {
        $template->delete();

        return redirect()->route('admin.promotions.templates.index')->with('status', "Plantilla «{$template->name}» eliminada.");
    }

    /** Para cargar la plantilla en el editor de un envío */
    public function json(MessageTemplate $template): JsonResponse
    {
        return response()->json($template->only(['subject', 'body', 'html_flg']));
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', new Enum(TemplateCategory::class)],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
            'html_flg' => ['nullable', 'in:0,1'],
            'shop_id' => ['nullable', 'exists:shops,id'],
        ], [], ['name' => 'nombre', 'body' => 'contenido', 'category' => 'categoría']);

        return ['html_flg' => (int) ($data['html_flg'] ?? 0)] + $data;
    }
}
