@php
    // El día del legacy empieza a las 5:00
    $horas = array_merge(range(5, 23), range(0, 4));
    $dias = [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 0 => 'Dom'];
    $maxHora = max(1, max($byHour));
@endphp

<x-layouts.modulo module="promo" title="Historial de visitas"
                  :crumbs="[['label' => __('promotions.modulo'), 'route' => 'admin.promotions.index'], ['label' => 'Historial de visitas']]">

    <x-ui.page-header title="Historial de visitas" :description="number_format($total) . ' visitas · cuándo vienen tus clientes, por hora y día de la semana.'">
        <x-slot:actions>
            <x-ui.btn icon="clock" :href="route('admin.customers.visit-interval')">Intervalo entre lecturas</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <form method="GET" class="flex flex-wrap items-end gap-3">
        <div class="flex gap-1" role="group" aria-label="Periodo">
            @foreach ($periods as $key => $label)
                <button type="submit" name="period" value="{{ $key }}"
                        @class(['rounded-ctl border px-3 py-2 text-sm font-bold', 'border-accent bg-accent text-on-accent' => $period === $key, 'border-line bg-surface hover:bg-surface2' => $period !== $key])>{{ $label }}</button>
            @endforeach
        </div>
        @if ($shops->count() > 1)
            <x-ui.field label="Tienda" class="w-52">
                <x-ui.select name="shop" onchange="this.form.submit()">
                    @foreach ($shops as $shop) <option value="{{ $shop->id }}" @selected($shopId === $shop->id)>{{ $shop->name }}</option> @endforeach
                </x-ui.select>
            </x-ui.field>
            <input type="hidden" name="period" value="{{ $period }}">
        @endif
    </form>

    <x-ui.section title="Visitas por hora">
        <div class="relative overflow-x-auto p-5">
            <div class="min-w-[720px]">
                <div class="flex h-36 items-end gap-1" role="img" aria-label="Visitas por hora del día">
                    @foreach ($horas as $h)
                        <div class="flex h-full flex-1 items-end" title="{{ $h }}:00 – {{ $h }}:59 · {{ $byHour[$h] }} visitas" tabindex="0">
                            <div class="w-full rounded-t-[4px] bg-accent" style="height: {{ $byHour[$h] ? max(3, round($byHour[$h] / $maxHora * 100)) : 0 }}%"></div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-1 flex gap-1 text-center text-[11px] text-faint">
                    @foreach ($horas as $h) <span class="flex-1">{{ $h }}</span> @endforeach
                </div>

                {{-- Mapa de calor día × hora: un solo tono, más intenso = más visitas --}}
                <table class="mt-6 w-full table-fixed border-separate border-spacing-0.5 text-[11px]">
                    <caption class="sr-only">Visitas por día de la semana y hora</caption>
                    <thead><tr><th scope="col" class="w-10"><span class="sr-only">Día</span></th>@foreach ($horas as $h)<th scope="col" class="font-normal text-faint">{{ $h }}</th>@endforeach</tr></thead>
                    <tbody>
                        @foreach ($dias as $dow => $dia)
                            <tr>
                                <th scope="row" class="pr-2 text-right font-bold text-muted">{{ $dia }}</th>
                                @foreach ($horas as $h)
                                    @php
                                        $n = $matrix[$dow][$h] ?? 0;
                                        $tono = $n ? 25 + (int) round($n / max(1, $max) * 75) : 0;
                                    @endphp
                                    {{-- Texto oscuro sobre los tonos claros, blanco sobre los intensos --}}
                                    <td class="h-6 rounded-[3px] text-center {{ ! $n ? 'bg-surface2' : ($tono > 60 ? 'text-on-accent' : 'text-ink') }}"
                                        @if ($n) style="background: color-mix(in oklab, var(--crm-accent) {{ $tono }}%, var(--crm-surface))" @endif
                                        title="{{ $dia }} {{ $h }}:00 · {{ $n }} visitas">{{ $n ?: '' }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </x-ui.section>

    <x-ui.section title="Clientes que vinieron" :description="number_format($customers->total()) . ' clientes en el periodo'">
        <div class="relative overflow-x-auto">
            <table class="w-full min-w-[600px] text-sm">
                <thead><tr class="bg-surface2 text-left text-xs font-bold text-muted">
                    <th scope="col" class="px-5 py-2.5">Nº de socio</th><th scope="col" class="px-3 py-2.5">Nº gestión</th><th scope="col" class="px-3 py-2.5">Nombre</th>
                    <th scope="col" class="px-3 py-2.5">Última visita</th><th scope="col" class="px-5 py-2.5 text-right">Visitas</th>
                </tr></thead>
                <tbody>
                    @forelse ($customers as $c)
                        <tr class="border-t border-line">
                            <td class="tnum px-5 py-2.5">{{ $c->code }}</td>
                            <td class="px-3 py-2.5 text-muted">{{ $c->management_no ?: '—' }}</td>
                            <td class="px-3 py-2.5"><a href="{{ route('admin.customers.show', $c) }}" class="font-bold hover:text-accent-fg">{{ $c->greetingName() }}</a></td>
                            <td class="tnum px-3 py-2.5 text-muted">{{ \Illuminate\Support\Carbon::parse($c->ultima)->format('d/m/Y H:i') }}</td>
                            <td class="tnum px-5 py-2.5 text-right font-bold">{{ $c->visitas }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-ui.empty icon="visit" title="Sin visitas en el periodo" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($customers->hasPages())<div class="border-t border-line px-4 py-3">{{ $customers->onEachSide(1)->links() }}</div>@endif
    </x-ui.section>

</x-layouts.modulo>
