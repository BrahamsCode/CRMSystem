@php
    use App\Enums\CustomCategoryType;

    $editing = (bool) $category;
    $title = $editing ? "Configurar «{$category->name}»" : 'Nueva categoría';
    $action = $editing
        ? route('admin.customers.custom-categories.update', $category)
        : route('admin.customers.custom-categories.store');
@endphp

<x-layouts.modulo :title="$title"
                  :crumbs="[
                      ['label' => 'Información adicional', 'route' => 'admin.customers.custom-categories'],
                      ['label' => $editing ? $category->name : 'Nueva categoría'],
                  ]">

    <div class="flex max-w-[760px] flex-col gap-5">

        <div>
            <a href="{{ route('admin.customers.custom-categories') }}" class="text-sm font-bold text-accent-fg hover:underline">
                &larr; Volver a categorías
            </a>
            <h1 class="mt-1.5 text-[26px] leading-tight font-extrabold">{{ $title }}</h1>
            <p class="mt-1.5 text-sm text-muted">
                Una categoría agrupa campos que se añaden a la ficha del cliente, como «Mascota».
            </p>
        </div>

        @if ($errors->any())
            <div role="alert" class="rounded-card border border-danger/30 bg-danger/10 px-5 py-4 text-sm">
                <strong class="block text-danger">Revisa los datos:</strong>
                <ul class="mt-2 list-inside list-disc text-danger">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ $action }}" class="flex flex-col gap-5">
            @csrf
            @if ($editing) @method('PUT') @endif
            <input type="hidden" name="shop_id" value="{{ $shop?->id }}">

            <x-ui.card class="flex flex-col gap-4 p-5.5">
                <x-ui.field label="Nombre de la categoría" required>
                    <x-ui.input name="name" required autofocus
                                :value="old('name', $category?->name)" />
                </x-ui.field>

                <x-ui.field label="Comentario" hint="Para quien configure la categoría más adelante.">
                    <x-ui.textarea name="note" :rows="3">{{ old('note', $category?->note) }}</x-ui.textarea>
                </x-ui.field>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.field label="Columnas al mostrar">
                        <x-ui.select name="columns">
                            @foreach ([1, 2, 3] as $n)
                                <option value="{{ $n }}" @selected(old('columns', $category?->columns ?? 1) == $n)>
                                    {{ $n }} {{ $n === 1 ? 'columna' : 'columnas' }}
                                </option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>

                    <x-ui.field label="Tipo de registro"
                                hint="«Normal» guarda un registro por cliente; «Múltiple» permite varios, por ejemplo varias mascotas.">
                        <x-ui.select name="type">
                            @foreach (CustomCategoryType::cases() as $type)
                                <option value="{{ $type->value }}"
                                        @selected(old('type', $category?->type?->value ?? CustomCategoryType::Normal->value) == $type->value)>
                                    {{ $type->label() }}
                                </option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>
                </div>

                <fieldset class="m-0 flex flex-col gap-2.5 border-0 p-0">
                    <legend class="mb-1.5 text-xs font-bold text-muted">Dónde aparece</legend>

                    <label class="flex cursor-pointer items-start gap-2.5">
                        <input type="checkbox" name="search_flg" value="1"
                               @checked(old('search_flg', $category?->search_flg ?? 1))
                               class="mt-0.5 h-4.5 w-4.5 shrink-0 accent-[var(--crm-accent)]">
                        <span>
                            <span class="block text-sm font-bold">Usar como filtro de búsqueda</span>
                            <span class="mt-1 block text-xs text-faint">
                                La categoría aparece como bloque plegable en «Buscar clientes».
                            </span>
                        </span>
                    </label>

                    <label class="flex cursor-pointer items-start gap-2.5">
                        <input type="checkbox" name="display_flg" value="1"
                               @checked(old('display_flg', $category?->display_flg ?? 1))
                               class="mt-0.5 h-4.5 w-4.5 shrink-0 accent-[var(--crm-accent)]">
                        <span>
                            <span class="block text-sm font-bold">Mostrar en la ficha y el registro</span>
                            <span class="mt-1 block text-xs text-faint">
                                Si se desactiva, la categoría se conserva pero deja de verse.
                            </span>
                        </span>
                    </label>
                </fieldset>
            </x-ui.card>

            <div class="flex flex-wrap items-center justify-end gap-2">
                @if ($editing)
                    <x-ui.btn :href="route('admin.customers.custom-fields', ['category' => $category->id])"
                              class="mr-auto">
                        Editar sus campos ({{ $category->fields()->count() }})
                    </x-ui.btn>
                @endif
                <x-ui.btn :href="route('admin.customers.custom-categories')">Cancelar</x-ui.btn>
                <x-ui.btn type="submit" variant="primary">
                    {{ $editing ? 'Guardar' : 'Crear categoría' }}
                </x-ui.btn>
            </div>
        </form>

        @if ($editing)
            <x-ui.card class="flex flex-wrap items-center gap-3 border-danger/30 p-4.5">
                <span class="flex-1 text-sm">
                    <strong class="block">Eliminar esta categoría</strong>
                    <span class="text-muted">
                        Se borran también sus {{ $category->fields()->count() }} campos.
                        Los valores ya guardados en las fichas dejan de mostrarse.
                    </span>
                </span>
                <form method="POST" action="{{ route('admin.customers.custom-categories.destroy', $category) }}"
                      onsubmit="return confirm('¿Eliminar la categoría «{{ $category->name }}» y sus campos?')">
                    @csrf
                    @method('DELETE')
                    <x-ui.btn type="submit" variant="danger">Eliminar categoría</x-ui.btn>
                </form>
            </x-ui.card>
        @endif
    </div>

</x-layouts.modulo>
