@php
    // El catálogo y la configuración guardada ya vienen combinados del controlador.
    $defaults = [];

    foreach ($groups as $rows) {
        foreach ($rows as $row) {
            foreach ($row['cells'] as $cell) {
                if ($cell['type'] === 'check') {
                    $defaults[$cell['key']] = $cell['on'];
                }
            }
        }
    }

    foreach ($familyFields as $row) {
        foreach ($row['cells'] as $cell) {
            $defaults[$cell['key']] = $cell['on'];
        }
    }

    foreach ($categories as $category) {
        foreach ($category->fields as $field) {
            $defaults["cf{$field->id}:0"] = $field->required_flg === 1;
            $defaults["cf{$field->id}:1"] = $field->search_flg === 1;
            $defaults["cf{$field->id}:2"] = $field->mail_magazine_flg === 1;
        }
    }

    $extraLabels = ['Obligatorio', 'Filtro de búsqueda', 'Exportar CSV'];
@endphp

<x-layouts.modulo title="Campos de registro, búsqueda y CSV"
                  :crumbs="[['label' => __('customers.modulo'), 'route' => 'admin.customers.index'], ['label' => 'Campos y CSV']]">

    <div x-data="{
             defectos: @js($defaults),
             valores: Object.assign({}, @js($defaults)),
             get pendientes() {
                 return Object.keys(this.defectos).filter(k => this.valores[k] !== this.defectos[k]).length;
             },
             activos(indice) {
                 return Object.keys(this.valores).filter(k => k.endsWith(':' + indice) && this.valores[k]).length;
             },
             descartar() { this.valores = Object.assign({}, this.defectos) },
         }"
         class="flex flex-col gap-5">

        <x-ui.page-header title="Campos de registro, búsqueda y CSV"
                          description="Decide qué datos pide el formulario de registro, cuáles son obligatorios, cuáles aparecen como filtro en «Buscar clientes» y cuáles se incluyen al exportar a CSV." />

        <div role="note" class="flex items-start gap-2.5 rounded-ctl bg-amber-50 px-3.5 py-3 text-sm leading-relaxed text-amber-900">
            <x-icon name="alert" class="mt-px" />
            <span>
                Mostrar muchos campos en el registro móvil puede desajustar la pantalla en algunos teléfonos.
                Recomendamos mantenerlo en lo esencial.
            </span>
        </div>

        <nav aria-label="Secciones" class="flex flex-wrap gap-1.5">
            <x-ui.btn href="#basica">Información básica</x-ui.btn>
            <x-ui.btn href="#familia">Información familiar</x-ui.btn>
            @foreach ($categories as $category)
                <x-ui.btn href="#cat-{{ $category->id }}">{{ $category->name }}</x-ui.btn>
            @endforeach
            <x-ui.btn :href="route('admin.customers.custom-categories')" class="text-accent-fg!">
                + Nuevo formulario personalizado
            </x-ui.btn>
        </nav>

        {{-- Información básica --}}
        <x-ui.section id="basica" title="Información básica" class="scroll-mt-20"
                      description="Las dos primeras columnas aplican al registro desde el móvil del cliente. «—» indica que la opción no aplica a ese campo.">
            <div class="relative overflow-x-auto">
                <table class="w-full min-w-[880px] text-sm">
                    <thead>
                        <tr class="border-b border-line bg-surface2">
                            <th scope="col" class="px-5 py-3.5 text-left text-xs font-bold text-muted">Campo</th>
                            @foreach ($columns as $i => $columna)
                                <th scope="col" class="w-40 px-2 py-3.5 text-center align-bottom">
                                    <span class="block text-[13px] font-extrabold">{{ $columna[0] }}</span>
                                    <span class="mt-0.5 block text-[11px] font-normal text-faint">{{ $columna[1] }}</span>
                                    <span class="mt-1.5 inline-block rounded-full bg-paper px-2 py-px text-[11px] font-bold text-muted">
                                        <span x-text="activos({{ $i }})"></span> activos
                                    </span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>

                    @foreach ($groups as $title => $rows)
                        <tbody>
                            <tr>
                                <th scope="colgroup" colspan="5"
                                    class="px-5 pt-4.5 pb-2 text-left text-[11px] font-bold tracking-widest text-faint uppercase">
                                    {{ $title }}
                                </th>
                            </tr>

                            @foreach ($rows as $row)
                                <tr>
                                    <th scope="row" class="min-h-12 border-t border-line px-5 py-3 text-left font-semibold">
                                        {{ $row['label'] }}
                                    </th>
                                    @foreach ($row['cells'] as $i => $cell)
                                        <td class="border-t border-line px-2 py-3 text-center">
                                            @if ($cell['type'] === 'na')
                                                <span title="No aplica" class="font-bold text-faint" aria-hidden="true">&mdash;</span>
                                                <span class="sr-only">No aplica</span>
                                            @elseif ($cell['type'] === 'fijo')
                                                <span class="rounded-full bg-ink px-2.5 py-0.5 text-xs font-bold text-paper">Siempre</span>
                                            @else
                                                <input type="checkbox" x-model="valores['{{ $cell['key'] }}']"
                                                       aria-label="{{ $row['label'] }}: {{ $columns[$i][0] }}"
                                                       class="h-5 w-5 cursor-pointer accent-[var(--crm-accent)]">
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    @endforeach
                </table>
            </div>
        </x-ui.section>

        {{-- Información familiar --}}
        <x-ui.section id="familia" title="Información familiar" class="scroll-mt-20"
                      description="Datos del cónyuge y fechas familiares.">
            <x-customers.field-table :rows="$familyFields" :labels="$extraLabels" />
        </x-ui.section>

        {{-- Categorías personalizadas --}}
        @foreach ($categories as $category)
            @php
                $categoryRows = $category->fields->map(fn ($field) => [
                    'label' => $field->name,
                    'solo_admin' => $field->display_scope === \App\Enums\DisplayScope::AdminOnly,
                    'cells' => [
                        ['key' => "cf{$field->id}:0"],
                        ['key' => "cf{$field->id}:1"],
                        ['key' => "cf{$field->id}:2"],
                    ],
                ])->all();
            @endphp

            <x-ui.section id="cat-{{ $category->id }}" class="scroll-mt-20">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-line px-5 py-4">
                    <div>
                        <div class="flex items-center gap-2.5">
                            <h2 class="text-[17px] font-bold">{{ $category->name }}</h2>
                            <x-ui.badge tone="accent">Formulario personalizado</x-ui.badge>
                        </div>
                        <p class="mt-1 max-w-160 text-sm text-muted">
                            Creado en «Información adicional». Sus campos se definen allí.
                        </p>
                    </div>
                    <x-ui.btn :href="route('admin.customers.custom-fields')">Editar campos del formulario</x-ui.btn>
                </div>

                @if ($category->fields->isEmpty())
                    <x-ui.empty icon="plus-box" title="Esta categoría no tiene campos">
                        Añádelos desde «Información adicional».
                    </x-ui.empty>
                @else
                    <x-customers.field-table :rows="$categoryRows" :labels="$extraLabels" />
                @endif
            </x-ui.section>
        @endforeach

        {{-- Barra de guardado --}}
        <div class="sticky bottom-4 z-10 flex flex-wrap items-center gap-3 self-center rounded-card border border-line bg-surface py-2.5 pr-2.5 pl-4.5 shadow-lg">
            <span class="font-bold"
                  x-text="pendientes === 0
                      ? 'Sin cambios pendientes'
                      : (pendientes === 1 ? '1 cambio sin guardar' : pendientes + ' cambios sin guardar')"></span>
            <x-ui.btn @click="descartar()" ::disabled="! pendientes"
                      class="disabled:cursor-default disabled:opacity-50">Descartar</x-ui.btn>
            <x-ui.btn variant="primary" ::disabled="! pendientes"
                      class="disabled:cursor-default disabled:opacity-50">Guardar cambios</x-ui.btn>
        </div>
    </div>

</x-layouts.modulo>
