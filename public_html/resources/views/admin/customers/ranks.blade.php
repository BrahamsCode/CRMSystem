@php
    $blocks = [
        [
            'titulo' => 'Rangos por importe de compra',
            'accion' => 'Añadir rango por importe',
            'rangos' => $amountRanks,
            'unidad' => fn ($v) => number_format($v, 0, ',', '.') . ' ¥',
        ],
        [
            'titulo' => 'Rangos por número de visitas',
            'accion' => 'Añadir rango por visitas',
            'rangos' => $visitRanks,
            'unidad' => fn ($v) => $v . ' visitas',
        ],
    ];
@endphp

<x-layouts.modulo title="Rangos de clientes"
                  :crumbs="[['label' => __('customers.modulo'), 'route' => 'admin.customers.index'], ['label' => 'Rangos de clientes']]">

    <x-ui.page-header title="Rangos de clientes">
        <x-slot:description>
            Define los rangos por importe de compra y por número de visitas. La asignación automática se configura en
            <a href="{{ route('admin.customers.rank-schedules') }}" class="font-bold text-accent-fg hover:underline">Asignación de rangos</a>.
        </x-slot:description>
    </x-ui.page-header>

    <x-ui.section title="Rango visible en Mi página"
                  description="Elige qué rango actual ve el cliente en su Mi página.">
        <div x-data="{ visible: 'visitas' }"
             class="flex flex-wrap items-center justify-between gap-4 px-5 py-5">
            <fieldset class="m-0 min-w-0 border-0 p-0">
                <legend class="sr-only">Rango visible en Mi página</legend>
                <div class="flex flex-wrap gap-2">
                    @foreach ([
                        'ambos' => 'Mostrar ambos',
                        'importe' => 'Rango por importe',
                        'visits' => 'Rango por visitas',
                        'ninguno' => 'No mostrar rangos',
                    ] as $value => $texto)
                        <label class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-ctl border bg-surface px-3.5 text-sm font-semibold transition-colors"
                               :class="visible === '{{ $value }}' ? 'border-accent' : 'border-line'">
                            <input type="radio" name="rango_visible" value="{{ $value }}" x-model="visible"
                                   class="accent-[var(--crm-accent)]">
                            {{ $texto }}
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <x-ui.btn variant="primary">Guardar</x-ui.btn>
        </div>
    </x-ui.section>

    @foreach ($blocks as $b)
        <x-ui.section :title="$b['titulo']"
                      description="Arrastra para cambiar el orden. Si las condiciones se solapan, gana el rango que esté más arriba.">
            <x-slot:actions>
                <x-ui.btn variant="primary" icon="plus">{{ $b['accion'] }}</x-ui.btn>
            </x-slot:actions>

            @forelse ($b['rangos'] as $rank)
                <div class="flex flex-wrap items-center gap-3 border-t border-line px-5 py-3.5">
                    <button type="button" aria-label="Reordenar {{ $rank->name }}"
                            class="cursor-grab text-faint hover:text-ink">
                        <x-icon name="grip" />
                    </button>
                    <span class="min-w-40 flex-1 font-bold">{{ $rank->name }}</span>
                    <span class="tnum text-sm text-muted">
                        {{ ($b['unidad'])($rank->min_value) }}
                        &ndash;
                        {{ $rank->max_value === null ? 'sin tope' : ($b['unidad'])($rank->max_value) }}
                    </span>
                    <x-ui.btn class="h-8.5 px-3">Editar</x-ui.btn>
                    <x-ui.btn variant="danger" class="h-8.5 px-3">Eliminar</x-ui.btn>
                </div>
            @empty
                <x-ui.empty icon="award" title="No hay rangos registrados">
                    Añade el primer rango para empezar a clasificar clientes.
                </x-ui.empty>
            @endforelse
        </x-ui.section>
    @endforeach

</x-layouts.modulo>
