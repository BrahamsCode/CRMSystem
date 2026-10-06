@php
    $today = now();

    $periods = [
        [
            'titulo' => 'Total registrado', 'hint' => 'Histórico',
            'altas' => $totalAltas, 'bajas' => $bajasTotal,
            'pie_altas' => 'Estado: registrado', 'pie_bajas' => 'Estado: baja',
        ],
        [
            'titulo' => 'Este mes', 'hint' => $today->translatedFormat('F'),
            'altas' => $altasMes, 'bajas' => $bajasMes,
            'pie_altas' => 'Alta desde ' . $today->copy()->startOfMonth()->format('d/m'),
            'pie_bajas' => 'Baja desde ' . $today->copy()->startOfMonth()->format('d/m'),
        ],
        [
            'titulo' => 'Hoy', 'hint' => $today->translatedFormat('j \d\e F'),
            'altas' => $altasHoy, 'bajas' => $bajasHoy,
            'pie_altas' => 'Alta desde ' . $today->format('d/m'),
            'pie_bajas' => 'Baja desde ' . $today->format('d/m'),
        ],
    ];

    $shortcuts = [
        ['label' => 'Nuevo cliente', 'sub' => 'Registrar un miembro', 'icon' => 'user-plus', 'route' => 'admin.customers.create', 'destacado' => true],
        ['label' => 'Buscar clientes', 'sub' => 'Filtrar y segmentar', 'icon' => 'search', 'route' => 'admin.customers.search'],
        ['label' => 'Registrar visita', 'sub' => 'Visita en tienda', 'icon' => 'visit', 'route' => 'admin.customers.visits'],
        ['label' => 'Estadísticas', 'sub' => 'Altas por periodo', 'icon' => 'stats', 'route' => 'admin.customers.statistics'],
    ];
@endphp

<x-layouts.modulo title="Gestión de clientes" :crumbs="[['label' => __('customers.modulo')]]">

    <x-ui.page-header title="Gestión de clientes"
                      description="Resumen de altas y bajas de miembros. Pulsa una cifra para ver la lista de clientes.">
        <x-slot:actions>
            <x-ui.badge tone="outline" class="h-9 px-3.5">
                <x-icon name="cal" :size="16" />
                Hoy: {{ $today->translatedFormat('j \d\e F') }}
            </x-ui.badge>
        </x-slot:actions>
    </x-ui.page-header>

    <section aria-label="Altas y bajas" class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($periods as $p)
            <x-ui.card :pad="false" class="p-2">
                <div class="flex items-center justify-between px-3 pt-3 pb-1">
                    <h2 class="text-sm font-extrabold">{{ $p['titulo'] }}</h2>
                    <span class="text-xs font-semibold text-faint">{{ $p['hint'] }}</span>
                </div>

                <div class="grid grid-cols-2 gap-1">
                    @foreach ([['Altas', $p['altas'], $p['pie_altas']], ['Bajas', $p['bajas'], $p['pie_bajas']]] as [$label, $value, $pie])
                        <a href="{{ route('admin.customers.search') }}"
                           aria-label="{{ $p['titulo'] }}: {{ $value }} {{ mb_strtolower($label) }}, ver lista"
                           class="group flex flex-col gap-0.5 rounded-ctl px-5 py-4 transition-colors hover:bg-surface2">
                            <span class="text-xs font-bold text-muted">{{ $label }}</span>
                            <span class="tnum text-4xl leading-tight font-extrabold">{{ $value }}</span>
                            <span class="text-xs text-faint">{{ $pie }}</span>
                            <span class="text-xs font-bold text-accent-fg">Ver lista &rarr;</span>
                        </a>
                    @endforeach
                </div>
            </x-ui.card>
        @endforeach
    </section>

    <div class="grid items-start gap-5 xl:grid-cols-2">

        <section aria-labelledby="accesos">
            <h2 id="accesos" class="mb-3 text-xs font-bold tracking-wider text-faint uppercase">Accesos rápidos</h2>

            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($shortcuts as $a)
                    <a href="{{ route($a['route']) }}"
                       class="flex items-center gap-3.5 rounded-card border border-line bg-surface p-4 transition-colors hover:border-accent/40 hover:bg-surface2">
                        <span @class([
                            'flex h-10 w-10 shrink-0 items-center justify-center rounded-ctl',
                            'bg-accent-soft text-accent-fg' => $a['destacado'] ?? false,
                            'bg-surface2 text-ink' => ! ($a['destacado'] ?? false),
                        ])>
                            <x-icon :name="$a['icon']" :size="20" />
                        </span>
                        <span class="flex flex-col">
                            <span class="font-bold">{{ $a['label'] }}</span>
                            <span class="text-xs text-muted">{{ $a['sub'] }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>

        <x-ui.card>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-base font-bold">Altas de hoy</h2>
                <a href="{{ route('admin.customers.search') }}" class="text-sm font-bold text-accent-fg hover:underline">
                    Ver todos los clientes
                </a>
            </div>

            @forelse ($todayCustomers as $customer)
                <a href="{{ route('admin.customers.show', $customer) }}"
                   class="-mx-2 flex items-center gap-3 rounded-ctl px-2 py-2.5 transition-colors first:mt-3 hover:bg-surface2">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-surface2 text-sm font-extrabold">
                        {{ mb_substr($customer->last_name, 0, 1) }}
                    </span>
                    <span class="flex min-w-0 flex-1 flex-col">
                        <span class="truncate font-bold">{{ $customer->full_name }}</span>
                        <span class="text-xs text-muted">Nº de socio {{ $customer->code }}</span>
                    </span>
                    <span class="text-xs text-faint">{{ $customer->created_at->format('H:i') }}</span>
                </a>
            @empty
                <x-ui.empty icon="user-plus" title="Todavía no hay altas hoy">
                    Los clientes que se registren hoy, en tienda o desde el móvil, aparecerán aquí.

                    <x-slot:action>
                        <x-ui.btn :href="route('admin.customers.create')">Registrar nuevo cliente</x-ui.btn>
                    </x-slot:action>
                </x-ui.empty>
            @endforelse
        </x-ui.card>
    </div>

</x-layouts.modulo>
