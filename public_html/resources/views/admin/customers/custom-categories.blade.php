<x-layouts.modulo title="Información adicional"
                  :crumbs="[['label' => __('customers.modulo'), 'route' => 'admin.customers.index'], ['label' => 'Información adicional']]">

    <div class="flex flex-col gap-5">

        <x-ui.page-header title="Información adicional"
                          description="Formularios personalizados que se añaden a la ficha del cliente (por ejemplo, Mascota). Cada categoría tiene sus propios campos.">
            <x-slot:actions>
                <x-ui.btn variant="primary" icon="plus" :href="route('admin.customers.custom-categories.create')">
                    Nueva categoría
                </x-ui.btn>
            </x-slot:actions>
        </x-ui.page-header>

        @if (session('status'))
            <div role="status" class="flex items-center gap-3 rounded-card bg-accent-soft px-5 py-3.5">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-accent text-on-accent">
                    <x-icon name="check" :size="18" />
                </span>
                <strong>{{ session('status') }}</strong>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.customers.custom-categories.flags') }}">
            @csrf

            <x-ui.section title="Categorías">
                <x-slot:actions>
                    <span class="text-sm text-muted">{{ $categories->count() }} categorías</span>
                </x-slot:actions>

                @if ($categories->isEmpty())
                    <x-ui.empty icon="plus-box" title="Todavía no hay categorías">
                        Crea la primera para añadir campos propios a la ficha del cliente.
                        <x-slot:action>
                            <x-ui.btn variant="primary" :href="route('admin.customers.custom-categories.create')">
                                Nueva categoría
                            </x-ui.btn>
                        </x-slot:action>
                    </x-ui.empty>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[760px] text-sm">
                            <thead>
                                <tr class="bg-surface2 text-left text-xs font-bold whitespace-nowrap text-muted">
                                    <th scope="col" class="w-14 px-4 py-2.5">#</th>
                                    <th scope="col" class="px-4 py-2.5">Categoría</th>
                                    <th scope="col" class="w-28 px-4 py-2.5">Tipo</th>
                                    <th scope="col" class="w-40 px-4 py-2.5">Campos</th>
                                    <th scope="col" class="w-32 px-4 py-2.5">Ajustes</th>
                                    <th scope="col" class="w-28 px-4 py-2.5">Búsqueda</th>
                                    <th scope="col" class="w-28 px-4 py-2.5">Mostrar</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($categories as $category)
                                    <tr class="border-t border-line">
                                        <td class="px-4 py-3 text-muted">{{ $loop->iteration }}</td>
                                        <td class="px-4 py-3">
                                            <a href="{{ route('admin.customers.custom-categories.edit', $category) }}"
                                               class="font-bold hover:text-accent-fg">{{ $category->name }}</a>
                                            <input type="hidden" name="order[]" value="{{ $category->id }}">
                                        </td>
                                        <td class="px-4 py-3">
                                            <x-ui.badge>{{ $category->type->label() }}</x-ui.badge>
                                        </td>
                                        <td class="px-4 py-3">
                                            <x-ui.btn :href="route('admin.customers.custom-fields', ['category' => $category->id])"
                                                      class="h-8.5 px-3">
                                                Editar campos
                                                <span class="font-normal text-muted">({{ $category->fields_count }})</span>
                                            </x-ui.btn>
                                        </td>
                                        <td class="px-4 py-3">
                                            <x-ui.btn :href="route('admin.customers.custom-categories.edit', $category)" class="h-8.5 px-3">
                                                Configurar
                                            </x-ui.btn>
                                        </td>
                                        <td class="px-4 py-3">
                                            <input type="hidden" name="categories[{{ $category->id }}][search_flg]" value="0">
                                            <input type="checkbox" name="categories[{{ $category->id }}][search_flg]" value="1"
                                                   @checked($category->search_flg === 1)
                                                   aria-label="{{ $category->name }}: usar en búsqueda"
                                                   class="h-5 w-5 accent-[var(--crm-accent)]">
                                        </td>
                                        <td class="px-4 py-3">
                                            <input type="hidden" name="categories[{{ $category->id }}][display_flg]" value="0">
                                            <input type="checkbox" name="categories[{{ $category->id }}][display_flg]" value="1"
                                                   @checked($category->display_flg === 1)
                                                   aria-label="{{ $category->name }}: mostrar"
                                                   class="h-5 w-5 accent-[var(--crm-accent)]">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-5 py-3.5">
                        <span class="text-sm text-muted">
                            «Búsqueda» muestra la categoría como filtro en Buscar clientes.
                            «Mostrar» la activa en la ficha y el registro.
                        </span>
                        <x-ui.btn type="submit" variant="primary">Guardar cambios</x-ui.btn>
                    </div>
                @endif
            </x-ui.section>
        </form>
    </div>

</x-layouts.modulo>
