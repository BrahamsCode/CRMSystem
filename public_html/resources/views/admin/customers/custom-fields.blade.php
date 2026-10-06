@php
    use App\Enums\CustomFieldType;

    // Qué control dibuja la vista previa de cada tipo
    $preview = fn (CustomFieldType $t) => match (true) {
        $t === CustomFieldType::Textarea => 'area',
        $t === CustomFieldType::Numeric => 'num',
        $t->hasOptions() => 'opciones',
        in_array($t, [CustomFieldType::Year, CustomFieldType::YearMonth,
            CustomFieldType::YearMonthDay, CustomFieldType::MonthDay,
            CustomFieldType::ReferenceDate], true) => 'fecha',
        default => 'texto',
    };
@endphp

<x-layouts.modulo title="Campos de información adicional"
                  :crumbs="[
                      ['label' => 'Información adicional', 'route' => 'admin.customers.custom-categories'],
                      ['label' => 'Campos'],
                  ]">

    <div class="flex flex-col gap-5">

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <a href="{{ route('admin.customers.custom-categories') }}" class="text-sm font-bold text-accent-fg hover:underline">
                    &larr; Volver a categorías
                </a>
                <h1 class="mt-1.5 text-[26px] leading-tight font-extrabold">
                    Campos de «{{ $current?->name ?? '—' }}»
                </h1>
                <p class="mt-1.5 text-sm text-muted">
                    Los campos se muestran en el registro y la ficha del cliente en este orden.
                </p>
            </div>

            @if ($current)
                <x-ui.btn variant="primary" icon="plus"
                          :href="route('admin.customers.custom-fields.create', ['category' => $current->id])">
                    Nuevo campo
                </x-ui.btn>
            @endif
        </div>

        @if (session('status'))
            <div role="status" class="flex items-center gap-3 rounded-card bg-accent-soft px-5 py-3.5">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-accent text-on-accent">
                    <x-icon name="check" :size="18" />
                </span>
                <strong>{{ session('status') }}</strong>
            </div>
        @endif

        {{-- Selector de categoría: enlaces, no pestañas en memoria, para que la URL
             identifique qué se está viendo --}}
        <nav aria-label="Categoría" class="flex flex-wrap gap-1.5">
            @foreach ($categories as $category)
                <a href="{{ route('admin.customers.custom-fields', ['category' => $category->id]) }}"
                   @if ($current?->is($category)) aria-current="page" @endif
                   @class([
                       'inline-flex min-h-9.5 items-center rounded-ctl border px-3 text-sm transition-colors',
                       'border-accent bg-accent font-bold text-on-accent' => $current?->is($category),
                       'border-line bg-surface font-semibold text-ink hover:bg-surface2' => ! $current?->is($category),
                   ])>
                    {{ $category->name }}
                    <span class="ml-1.5 font-normal opacity-70">{{ $category->fields->count() }}</span>
                </a>
            @endforeach
        </nav>

        <x-ui.card :pad="false">
            @if (! $current || $current->fields->isEmpty())
                <x-ui.empty icon="plus-box" title="Esta categoría todavía no tiene campos">
                    Añade el primero con «Nuevo campo».
                    @if ($current)
                        <x-slot:action>
                            <x-ui.btn variant="primary"
                                      :href="route('admin.customers.custom-fields.create', ['category' => $current->id])">
                                Nuevo campo
                            </x-ui.btn>
                        </x-slot:action>
                    @endif
                </x-ui.empty>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1000px] text-sm">
                        <thead>
                            <tr class="bg-surface2 text-left text-xs font-bold whitespace-nowrap text-muted">
                                <th scope="col" class="w-11 px-3 py-2.5">#</th>
                                <th scope="col" class="px-3 py-2.5">Campo</th>
                                <th scope="col" class="w-36 px-3 py-2.5">Tipo</th>
                                <th scope="col" class="w-50 px-3 py-2.5">Vista previa</th>
                                <th scope="col" class="w-25 px-3 py-2.5">Obligatorio</th>
                                <th scope="col" class="w-24 px-3 py-2.5">Búsqueda</th>
                                <th scope="col" class="w-26 px-3 py-2.5">Newsletter</th>
                                <th scope="col" class="w-40 px-3 py-2.5">Mostrar en</th>
                                <th scope="col" class="w-24 px-3 py-2.5"><span class="sr-only">Acciones</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($current->fields as $field)
                                @php $preview = $preview($field->type); @endphp
                                <tr class="border-t border-line">
                                    <td class="px-3 py-3 text-muted">{{ $loop->iteration }}</td>
                                    <td class="px-3 py-3">
                                        <a href="{{ route('admin.customers.custom-fields.edit', $field) }}"
                                           class="font-bold hover:text-accent-fg">{{ $field->name }}</a>
                                    </td>
                                    <td class="px-3 py-3">
                                        <x-ui.badge>{{ $field->type->label() }}</x-ui.badge>
                                    </td>

                                    <td class="px-3 py-2">
                                        @if ($preview === 'texto')
                                            <x-ui.input class="h-8.5!" disabled aria-label="Vista previa de {{ $field->name }}" />
                                        @elseif ($preview === 'area')
                                            <x-ui.textarea :rows="2" class="resize-none py-1.5" disabled
                                                           aria-label="Vista previa de {{ $field->name }}" />
                                        @elseif ($preview === 'opciones')
                                            <x-ui.select class="h-8.5!" disabled aria-label="Vista previa de {{ $field->name }}">
                                                @forelse ($field->options as $option)
                                                    <option>{{ $option->label }}</option>
                                                @empty
                                                    <option>Sin opciones</option>
                                                @endforelse
                                            </x-ui.select>
                                        @elseif ($preview === 'num')
                                            <span class="flex w-full items-center gap-2">
                                                <x-ui.input type="number" class="h-8.5! w-27!" disabled
                                                            aria-label="Vista previa de {{ $field->name }}" />
                                                <span class="text-sm">{{ $field->unit }}</span>
                                            </span>
                                        @else
                                            <x-ui.input type="date" class="h-8.5!" disabled
                                                        aria-label="Vista previa de {{ $field->name }}" />
                                        @endif
                                    </td>

                                    <td class="px-3 py-3">
                                        <x-customers.check-mark :on="$field->required_flg === 1" />
                                    </td>
                                    <td class="px-3 py-3">
                                        <x-customers.check-mark :on="$field->search_flg === 1" />
                                    </td>
                                    <td class="px-3 py-3">
                                        <x-customers.check-mark :on="$field->mail_magazine_flg === 1" />
                                    </td>
                                    <td class="px-3 py-3 text-muted">{{ $field->display_scope->label() }}</td>
                                    <td class="px-3 py-3">
                                        <x-ui.btn :href="route('admin.customers.custom-fields.edit', $field)"
                                                  class="h-8.5 px-3">Editar</x-ui.btn>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-line px-5 py-3.5 text-sm text-muted">
                    «Newsletter» permite insertar el valor del campo en los correos (reemplazo de variables).
                    Para cambiar cualquiera de estos ajustes, abre el campo con «Editar».
                </div>
            @endif
        </x-ui.card>
    </div>

</x-layouts.modulo>
