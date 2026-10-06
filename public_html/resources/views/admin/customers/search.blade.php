@php
    // El desglose sale del campo «tipo de dirección» del cliente, igual que en el
    // legacy, no de analizar el dominio del email.
    $carriers = collect(\App\Enums\AddressType::cases())
        ->map(fn ($t) => [$t->label(), $byCarrier[$t->value] ?? 0])
        ->push(['Sin identificar', $byCarrier[''] ?? 0]);

    $query = request()->query();
    unset($query['page']);
@endphp

<x-layouts.modulo title="Buscar clientes"
                  :crumbs="[['label' => __('customers.modulo'), 'route' => 'admin.customers.index'], ['label' => 'Buscar clientes']]">

    <x-ui.page-header title="Buscar clientes"
                      description="Combina filtros de perfil, visitas y ventas para encontrar y segmentar clientes." />

    <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-[360px_minmax(0,1fr)]"
         x-data="{
             sel: [],
             visibles: @js($customers->pluck('code')->all()),
             get todas() { return this.visibles.length > 0 && this.visibles.every(c => this.sel.includes(c)) },
             alternarTodas() { this.sel = this.todas ? [] : [...this.visibles] },
             enlace(base) { const p = new URLSearchParams(); this.sel.forEach(c => p.append('codes[]', c)); return base + '?' + p.toString() },
         }">

        {{-- Panel de filtros --}}
        <form method="GET" action="{{ route('admin.customers.search') }}" aria-label="Filtros de búsqueda"
              class="flex flex-col overflow-hidden rounded-card border border-line bg-surface xl:sticky xl:top-20 xl:max-h-[calc(100vh-6rem)]">

            <div class="flex items-center justify-between px-5 pt-4 pb-2">
                <div class="flex items-center gap-2">
                    <x-icon name="filter" />
                    <span class="text-base font-extrabold">Filtros</span>
                    @if ($activeCount)
                        <x-ui.badge tone="accent">{{ $activeCount }}</x-ui.badge>
                    @endif
                </div>
                <a href="{{ route('admin.customers.search') }}" class="text-sm font-bold text-accent-fg hover:underline">Limpiar todo</a>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto">
                <x-customers.search-filters :filters="$filters" :shops="$shops" :groups="$groups"
                                            :categories="$categories" :coupons="$coupons" />
            </div>

            <input type="hidden" name="per_page" value="{{ $perPage }}">

            <div class="flex gap-2 border-t border-line bg-surface p-3.5">
                <x-ui.btn :href="route('admin.customers.search')" class="flex-1">Limpiar</x-ui.btn>
                <x-ui.btn type="submit" variant="primary" icon="search" class="flex-2">Buscar clientes</x-ui.btn>
            </div>
        </form>

        {{-- Resultados --}}
        <section aria-labelledby="resultados" class="flex min-w-0 flex-col gap-3.5">

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 id="resultados" class="text-lg font-bold">{{ number_format($customers->total()) }} clientes encontrados</h2>
                    <p class="mt-0.5 text-sm text-muted">
                        Mostrando {{ $customers->firstItem() ?? 0 }}–{{ $customers->lastItem() ?? 0 }} de {{ $customers->total() }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if (Route::has('admin.promotions.messages.create'))
                        <x-ui.btn icon="send" variant="primary" :href="route('admin.promotions.messages.create', $query)">Enviar mensaje</x-ui.btn>
                    @endif
                    <x-ui.btn icon="download" :href="route('admin.customers.export', $query)">Exportar CSV</x-ui.btn>
                    <x-ui.btn icon="download" :href="route('admin.customers.export', $query + ['format' => 'mailing'])">CSV para correo postal</x-ui.btn>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 text-xs text-muted">
                <span class="font-bold">Por tipo de email:</span>
                @foreach ($carriers as [$label, $total])
                    <x-ui.badge tone="outline">{{ $label }} <strong class="tnum text-ink">{{ $total }}</strong></x-ui.badge>
                @endforeach
            </div>

            <div class="flex flex-wrap gap-2">
                @foreach ($summary as [$label, $value])
                    <span class="inline-flex h-8 items-center gap-1.5 rounded-full border border-line bg-surface px-3 text-sm">
                        <span class="text-faint">{{ $label }}:</span> <strong>{{ $value }}</strong>
                    </span>
                @endforeach
            </div>

            {{-- Barra de selección --}}
            <div x-show="sel.length" x-cloak x-transition
                 class="flex flex-wrap items-center gap-2.5 rounded-card bg-ink py-2 pr-2 pl-4 text-paper">
                <strong class="flex-1"><span x-text="sel.length"></span> seleccionados</strong>
                @if (Route::has('admin.promotions.messages.create'))
                    <a :href="enlace(@js(route('admin.promotions.messages.create')))"
                       class="inline-flex h-9 items-center rounded-ctl border border-current px-3 text-sm font-bold hover:bg-white/10">
                        Enviar mensaje
                    </a>
                @endif
                <a :href="enlace(@js(route('admin.customers.export')))"
                   class="inline-flex h-9 items-center rounded-ctl border border-current px-3 text-sm font-bold hover:bg-white/10">
                    Exportar
                </a>
                <button type="button" @click="sel = []" class="h-9 rounded-ctl px-3 text-sm font-bold hover:bg-white/10">Quitar selección</button>
            </div>

            <x-ui.card :pad="false">
                <div class="relative overflow-x-auto">
                    <table class="w-full min-w-[720px] text-sm">
                        <thead>
                            <tr class="bg-surface2 text-left text-xs font-bold whitespace-nowrap text-muted">
                                <th scope="col" class="w-11 py-2.5 pl-4">
                                    <input type="checkbox" aria-label="Seleccionar todos" class="h-4.5 w-4.5 accent-[var(--crm-accent)]"
                                           :checked="todas" @change="alternarTodas()">
                                </th>
                                <th scope="col" class="px-3 py-2.5">Nº de socio</th>
                                <th scope="col" class="px-3 py-2.5">Nº gestión</th>
                                <th scope="col" class="px-3 py-2.5">Nombre</th>
                                <th scope="col" class="px-3 py-2.5">Tienda</th>
                                <th scope="col" class="px-3 py-2.5">Última visita</th>
                                <th scope="col" class="px-3 py-2.5">Visitas</th>
                                <th scope="col" class="px-3 py-2.5"><span class="sr-only">Acciones</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($customers as $customer)
                                <tr class="border-t border-line transition-colors"
                                    :class="sel.includes(@js($customer->code)) ? 'bg-accent-soft' : 'hover:bg-surface2/60'">
                                    <td class="py-3 pl-4">
                                        <input type="checkbox" aria-label="Seleccionar {{ $customer->full_name }}"
                                               class="h-4.5 w-4.5 accent-[var(--crm-accent)]" x-model="sel" value="{{ $customer->code }}">
                                    </td>
                                    <td class="tnum px-3 py-3">{{ $customer->code }}</td>
                                    <td class="px-3 py-3 @if (! $customer->management_no) text-faint @endif">
                                        {{ $customer->management_no ?: '—' }}
                                    </td>
                                    <td class="px-3 py-3">
                                        <div class="flex items-center gap-3">
                                            <span class="flex h-8.5 w-8.5 shrink-0 items-center justify-center rounded-full bg-surface2 text-sm font-extrabold">
                                                {{ mb_substr($customer->last_name, 0, 1) }}
                                            </span>
                                            <span class="flex min-w-0 flex-col">
                                                <a href="{{ route('admin.customers.show', $customer) }}" class="font-bold hover:text-accent-fg">
                                                    {{ $customer->full_name }}
                                                </a>
                                                @if ($customer->full_name_kana)
                                                    <span class="text-xs text-faint">{{ $customer->full_name_kana }}</span>
                                                @endif
                                            </span>
                                            @if ($customer->trashed())
                                                <x-ui.badge>Eliminado</x-ui.badge>
                                            @elseif ($customer->status?->value === 0)
                                                <x-ui.badge>Baja</x-ui.badge>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 text-muted">{{ $customer->shop?->name }}</td>
                                    <td class="tnum px-3 py-3 whitespace-nowrap text-muted">{{ $customer->last_visit_date?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="px-3 py-3 font-bold whitespace-nowrap">
                                        {{ $customer->visit_count ? $customer->visit_count . ' veces' : '—' }}
                                    </td>
                                    <td class="px-3 py-3 text-right">
                                        <x-ui.btn :href="route('admin.customers.show', $customer)" class="h-8.5 px-3">Ver ficha</x-ui.btn>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8">
                                        <x-ui.empty icon="search" title="Ningún cliente coincide">
                                            Prueba a quitar filtros o a registrar un cliente nuevo.
                                        </x-ui.empty>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-4 py-3">
                    <form method="GET" action="{{ route('admin.customers.search') }}" class="flex items-center gap-2 text-sm text-muted">
                        @foreach (\Illuminate\Support\Arr::dot(\Illuminate\Support\Arr::except($query, ['per_page'])) as $key => $value)
                            @php
                                // shop_ids.0 → shop_ids[0]; custom.mascota.tipo → custom[mascota][tipo]
                                $parts = explode('.', $key);
                                $inputName = array_shift($parts) . implode('', array_map(fn ($p) => "[{$p}]", $parts));
                            @endphp
                            <input type="hidden" name="{{ $inputName }}" value="{{ $value }}">
                        @endforeach
                        <label for="per_page">Filas por página</label>
                        <x-ui.select id="per_page" name="per_page" class="w-auto!" onchange="this.form.submit()">
                            @foreach ([10, 25, 50, 100] as $n)
                                <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }}</option>
                            @endforeach
                        </x-ui.select>
                    </form>
                    {{ $customers->onEachSide(1)->links() }}
                </div>
            </x-ui.card>
        </section>
    </div>

</x-layouts.modulo>
