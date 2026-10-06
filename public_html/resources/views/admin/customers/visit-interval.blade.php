<x-layouts.modulo title="Intervalo entre visitas"
                  :crumbs="[['label' => __('customers.modulo'), 'route' => 'admin.customers.index'], ['label' => 'Intervalo entre visitas']]">

    <div class="flex max-w-[860px] flex-col gap-5">

        <x-ui.page-header title="Intervalo entre visitas"
                          description="Tiempo que debe pasar antes de poder volver a registrar la visita del mismo cliente. Evita que una lectura repetida de la tarjeta cuente dos veces." />

        @if (session('status'))
            <div role="status" class="flex items-center gap-3 rounded-card bg-accent-soft px-5 py-3.5">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-accent text-on-accent">
                    <x-icon name="check" :size="18" />
                </span>
                <strong>{{ session('status') }}</strong>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.customers.visit-interval.update') }}">
            @csrf
            @method('PUT')

            <x-ui.section title="Por tienda"
                          description="Cada tienda puede tener su propio intervalo.">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-120 text-sm">
                        <thead>
                            <tr class="bg-surface2 text-left text-xs font-bold whitespace-nowrap text-muted">
                                <th scope="col" class="px-5 py-2.5">Tienda</th>
                                <th scope="col" class="w-56 px-5 py-2.5">Intervalo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($shops as $shop)
                                <tr class="border-t border-line">
                                    <th scope="row" class="px-5 py-3 text-left font-bold">{{ $shop->name }}</th>
                                    <td class="px-5 py-3">
                                        <x-ui.select :name="'intervals[' . $shop->id . ']'"
                                                     :aria-label="'Intervalo de ' . $shop->name">
                                            @foreach ($options as $seconds => $label)
                                                <option value="{{ $seconds }}"
                                                        @selected($shop->visit_interval_seconds === $seconds)>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </x-ui.select>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2">
                                        <x-ui.empty icon="cal" title="No hay tiendas activas">
                                            Da de alta una tienda para poder configurar su intervalo.
                                        </x-ui.empty>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($shops->isNotEmpty())
                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-5 py-3.5">
                        <span class="text-sm text-muted">
                            Con «Sin intervalo» se puede registrar la misma visita tantas veces como se quiera.
                        </span>
                        <x-ui.btn type="submit" variant="primary">Guardar cambios</x-ui.btn>
                    </div>
                @endif
            </x-ui.section>
        </form>
    </div>

</x-layouts.modulo>
