@php
    $last = session('visita');
@endphp

<x-layouts.modulo title="Registrar visita"
                  :crumbs="[['label' => __('customers.modulo'), 'route' => 'admin.customers.index'], ['label' => 'Registrar visita']]">

    <div class="flex max-w-[1100px] flex-col gap-5">

        <x-ui.page-header title="Registrar visita"
                          description="Introduce el número de socio del cliente para registrar su visita de hoy. El importe de consumo llega desde el punto de venta, no se captura aquí." />

        <x-ui.card class="p-7">
            <form method="POST" action="{{ route('admin.customers.visits.store') }}"
                  class="flex flex-wrap items-end gap-3">
                @csrf

                <x-ui.field label="Nº de socio" class="flex-1 basis-80">
                    <x-ui.input name="code" inputmode="numeric" autocomplete="off" required autofocus
                                placeholder="Ej. 1100009" :value="old('code')"
                                class="h-15! text-2xl font-bold tracking-wide" />
                </x-ui.field>

                <x-ui.btn type="submit" variant="primary" icon="visit" class="h-15! px-7 text-[17px]">
                    Registrar visita
                </x-ui.btn>
            </form>

            @error('code')
                <p role="alert" class="mt-3 text-sm font-bold text-danger">{{ $message }}</p>
            @enderror

            @if ($last)
                <div role="status"
                     class="mt-5 flex flex-wrap items-center gap-3.5 rounded-card bg-accent-soft px-4.5 py-4">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-accent text-on-accent">
                        <x-icon name="check" :size="20" />
                    </span>
                    <span class="flex flex-1 flex-col gap-0.5">
                        <strong class="text-base">Visita registrada · {{ $last['name'] }}</strong>
                        <span class="text-sm text-muted">
                            Nº de socio {{ $last['code'] }} · {{ $last['visits'] }} visitas
                        </span>
                    </span>
                    <x-ui.btn :href="route('admin.customers.show', $last['uid'])">Ver ficha</x-ui.btn>
                </div>
            @endif
        </x-ui.card>

        <x-ui.section title="Visitas registradas hoy">
            <x-slot:actions>
                <span class="text-sm text-muted">
                    {{ $todayVisits->count() }} {{ $todayVisits->count() === 1 ? 'visita' : 'visitas' }}
                </span>
            </x-slot:actions>

            @forelse ($todayVisits as $visit)
                @if ($loop->first)
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-120 text-sm">
                            <thead>
                                <tr class="bg-surface2 text-left text-xs font-bold whitespace-nowrap text-muted">
                                    <th scope="col" class="px-4 py-2.5">Hora</th>
                                    <th scope="col" class="px-4 py-2.5">Nº de socio</th>
                                    <th scope="col" class="px-4 py-2.5">Nombre</th>
                                    <th scope="col" class="px-4 py-2.5">Visitas</th>
                                </tr>
                            </thead>
                            <tbody>
                @endif

                                <tr class="border-t border-line">
                                    <td class="tnum px-4 py-3">{{ $visit->visited_at->format('H:i') }}</td>
                                    <td class="tnum px-4 py-3">{{ $visit->customer?->code ?? '—' }}</td>
                                    <td class="px-4 py-3 font-bold">
                                        @if ($visit->customer)
                                            <a href="{{ route('admin.customers.show', $visit->customer) }}"
                                               class="hover:text-accent-fg">{{ $visit->customer->full_name }}</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">{{ $visit->customer?->visit_count ?? 0 }}</td>
                                </tr>

                @if ($loop->last)
                            </tbody>
                        </table>
                    </div>
                @endif
            @empty
                <x-ui.empty icon="visit" title="Todavía no hay visitas hoy">
                    Las visitas que registres aparecerán aquí.
                </x-ui.empty>
            @endforelse
        </x-ui.section>
    </div>

</x-layouts.modulo>
