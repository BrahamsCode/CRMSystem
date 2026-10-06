@php
    $editing = $template->exists;
    $titulo = $editing ? $template->name : 'Nueva plantilla';
@endphp

<x-layouts.modulo module="promo" :title="$titulo"
                  :crumbs="[['label' => __('promotions.modulo'), 'route' => 'admin.promotions.index'], ['label' => 'Plantillas', 'route' => 'admin.promotions.templates.index'], ['label' => $titulo]]">

    <div class="flex max-w-[900px] flex-col gap-5">
        <x-ui.page-header :title="$titulo" />
        <x-ui.flash />

        <form method="POST" action="{{ $editing ? route('admin.promotions.templates.update', $template) : route('admin.promotions.templates.store') }}">
            @csrf
            @if ($editing) @method('PUT') @endif

            <x-ui.section>
                <div class="grid gap-4 p-5 md:grid-cols-2">
                    <x-ui.field label="Nombre" required>
                        <x-ui.input name="name" :value="old('name', $template->name)" required />
                    </x-ui.field>
                    <x-ui.field label="Categoría" required>
                        <x-ui.select name="category">
                            @foreach (\App\Enums\TemplateCategory::cases() as $c)
                                <option value="{{ $c->value }}" @selected((int) old('category', $template->category?->value) === $c->value)>{{ $c->label() }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>
                    <x-ui.field label="Título" class="md:col-span-2">
                        <x-ui.input name="subject" :value="old('subject', $template->subject)" />
                    </x-ui.field>
                    <x-ui.field label="Contenido" required class="md:col-span-2">
                        <x-ui.textarea name="body" :rows="12" required>{{ old('body', $template->body) }}</x-ui.textarea>
                    </x-ui.field>
                    <div class="md:col-span-2">
                        <x-ui.toggle name="html_flg" :checked="(bool) old('html_flg', $template->html_flg)" label="Contenido en HTML (email con diseño)" />
                    </div>
                    <p class="text-xs text-faint md:col-span-2">
                        Variables: @foreach ($variables as $token => $label)<code class="mr-1.5" title="{{ $label }}">{{ $token }}</code>@endforeach
                    </p>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-5 py-3.5">
                    @if ($editing)
                        <x-ui.btn type="submit" variant="danger" icon="trash" form="borrar" onclick="return confirm('¿Eliminar la plantilla?')">Eliminar</x-ui.btn>
                    @else <span></span> @endif
                    <div class="flex gap-2">
                        <x-ui.btn :href="route('admin.promotions.templates.index')">Cancelar</x-ui.btn>
                        <x-ui.btn type="submit" variant="primary">Guardar</x-ui.btn>
                    </div>
                </div>
            </x-ui.section>
        </form>

        @if ($editing)
            <form id="borrar" method="POST" action="{{ route('admin.promotions.templates.destroy', $template) }}" class="hidden">@csrf @method('DELETE')</form>
        @endif
    </div>

</x-layouts.modulo>
