@php
    // Todo viene calculado del controlador; aquí solo se escala el gráfico.
    $maxValue = max(array_column($months, 'valor') ?: [0]);
    $maxValue = $maxValue > 0 ? (int) (ceil($maxValue / 5) * 5) : 5;
    $chartHeight = 260;
    // La etiqueta fija marca el mes con más altas, no una posición fija
    $peakIndex = array_search($maxValue > 0 ? max(array_column($months, 'valor')) : 0, array_column($months, 'valor'), true);
@endphp

<x-layouts.modulo title="Estadísticas de clientes"
                  :crumbs="[['label' => __('customers.modulo'), 'route' => 'admin.customers.index'], ['label' => 'Estadísticas']]">

    <x-ui.page-header title="Estadísticas de clientes"
                      description="Elige una herramienta de análisis, define el periodo y genera el informe.">
        <x-slot:actions>
            <x-ui.btn icon="download">Exportar CSV</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <fieldset class="m-0 min-w-0 border-0 p-0">
        <legend class="mb-2.5 text-xs font-bold tracking-wider text-muted uppercase">Herramienta</legend>
        <label class="flex max-w-90 cursor-pointer items-start gap-3.5 rounded-card border-2 border-accent bg-surface p-4">
            <input type="radio" name="herramienta" checked class="mt-0.5 h-4.5 w-4.5 accent-[var(--crm-accent)]">
            <span class="flex flex-col gap-1">
                <span class="text-[15px] font-extrabold">Altas de miembros</span>
                <span class="text-sm leading-relaxed text-muted">Análisis a partir de los datos de registro de miembros.</span>
            </span>
        </label>
    </fieldset>

    {{-- Parámetros del informe --}}
    <x-ui.card class="flex flex-wrap items-end gap-4 px-5 py-4.5"
               x-data="{ agrupar: 'Mes' }">
        <x-ui.field label="Periodo" group class="flex-2 basis-80">
            <div class="flex items-center gap-1.5">
                <x-ui.input type="date" aria-label="Desde" value="{{ $from->toDateString() }}" />
                <span class="text-faint">–</span>
                <x-ui.input type="date" aria-label="Hasta" value="{{ $to->toDateString() }}" />
            </div>
        </x-ui.field>

        <x-ui.field label="Agrupar por" group class="flex-1 basis-55">
            <div class="flex gap-1.5">
                @foreach (['Día', 'Semana', 'Mes'] as $g)
                    <button type="button" @click="agrupar = @js($g)" :aria-pressed="agrupar === @js($g)"
                            class="min-h-9.5 flex-1 rounded-ctl border px-2.5 text-sm transition-colors"
                            :class="agrupar === @js($g)
                                ? 'border-accent bg-accent font-bold text-on-accent'
                                : 'border-line bg-surface font-semibold text-ink hover:bg-surface2'">
                        {{ $g }}
                    </button>
                @endforeach
            </div>
        </x-ui.field>

        <x-ui.field label="Desglosar por" class="flex-1 basis-50">
            <x-ui.select>
                <option>Tienda de registro</option><option>Sexo</option><option>Rango de edad</option><option>Ocupación</option>
            </x-ui.select>
        </x-ui.field>

        <x-ui.field label="Tienda" class="flex-1 basis-45">
            <x-ui.select>
                <option>Todas</option>
                @foreach ($shops as $shop)
                    <option>{{ $shop->name }}</option>
                @endforeach
            </x-ui.select>
        </x-ui.field>

        <x-ui.btn variant="primary">Generar informe</x-ui.btn>
    </x-ui.card>

    {{-- Indicadores --}}
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($kpis as [$label, $value, $pie])
            <x-ui.card class="px-5 py-4.5">
                <div class="text-sm font-semibold text-muted">{{ $label }}</div>
                <div class="mt-1.5 text-[32px] leading-none font-extrabold">{{ $value }}</div>
                <div class="mt-1 text-sm text-muted">{{ $pie }}</div>
            </x-ui.card>
        @endforeach
    </div>

    <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,2fr)_minmax(300px,1fr)]">

        {{-- Gráfico de altas por mes --}}
        <x-ui.card x-data="{ vista: 'grafico', sobre: null }">
            <div class="mb-4.5 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <h2 class="text-base font-bold">Altas por mes</h2>
                    <x-ui.badge>Últimos 12 meses</x-ui.badge>
                </div>
                <div class="flex gap-1.5">
                    @foreach (['grafico' => 'Gráfico', 'tabla' => 'Tabla'] as $v => $texto)
                        <button type="button" @click="vista = '{{ $v }}'" :aria-pressed="vista === '{{ $v }}'"
                                class="min-h-9.5 rounded-ctl border px-3 text-sm transition-colors"
                                :class="vista === '{{ $v }}'
                                    ? 'border-accent bg-accent font-bold text-on-accent'
                                    : 'border-line bg-surface font-semibold text-ink hover:bg-surface2'">
                            {{ $texto }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div x-show="vista === 'grafico'" class="flex gap-2.5">
                <div aria-hidden="true" class="flex h-65 w-7 flex-col justify-between text-right text-[11px] text-faint">
                    <span>{{ $maxValue }}</span><span>{{ (int) round($maxValue * 2 / 3) }}</span><span>{{ (int) round($maxValue / 3) }}</span><span>0</span>
                </div>

                <div class="min-w-0 flex-1">
                    <div role="img"
                         aria-label="Altas mensuales de octubre 2025 a septiembre 2026; pico en marzo 2026 con 162"
                         class="relative flex h-65 items-end gap-2 border-b border-line">
                        {{-- Rejilla de referencia --}}
                        <div aria-hidden="true" class="pointer-events-none absolute inset-0 flex flex-col justify-between">
                            <div class="border-t border-dashed border-line"></div>
                            <div class="border-t border-dashed border-line"></div>
                            <div class="border-t border-dashed border-line"></div>
                            <div></div>
                        </div>

                        @foreach ($months as $i => $mes)
                            @php [$abrev, $completo, $value] = [$mes['short'], $mes['full'], $mes['value']]; @endphp
                            <div class="relative flex h-full flex-1 flex-col items-center justify-end"
                                 @mouseenter="sobre = {{ $i }}" @mouseleave="sobre = null">
                                {{-- Etiqueta directa: solo el pico, o la barra con el cursor encima --}}
                                <div x-show="sobre === {{ $i }} || (sobre === null && {{ $i }} === {{ (int) $peakIndex }})"
                                     class="mb-1.5 rounded-md px-2 py-1 text-xs font-bold whitespace-nowrap"
                                     :class="sobre === {{ $i }} ? 'bg-ink text-paper' : 'text-ink'">
                                    {{ $value }}
                                </div>
                                <div class="w-full max-w-10 rounded-t transition-colors"
                                     style="height: {{ $maxValue > 0 ? round($value / $maxValue * $chartHeight) : 0 }}px"
                                     :class="sobre === {{ $i }} ? 'bg-ink' : 'bg-accent'"></div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-2 flex gap-2">
                        @foreach ($months as $mes)
                            @php $abrev = $mes['short']; @endphp
                            <div class="flex-1 text-center text-[11px] text-faint">{{ $abrev }}</div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Vista de tabla equivalente --}}
            <div x-show="vista === 'tabla'" x-cloak class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-surface2 text-left text-xs font-bold text-muted">
                            <th scope="col" class="px-4 py-2.5">Mes</th>
                            <th scope="col" class="px-4 py-2.5 text-right">Altas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($months as $mes)
                            @php [$completo, $value] = [$mes['full'], $mes['value']]; @endphp
                            <tr class="border-t border-line">
                                <td class="px-4 py-2.5">{{ $completo }}</td>
                                <td class="tnum px-4 py-2.5 text-right font-bold">{{ $value }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>

        {{-- Desglose por tienda --}}
        <x-ui.card>
            <h2 class="text-base font-bold">Por tienda de registro</h2>
            <p class="mt-1 mb-4 text-sm text-muted">Total del periodo: {{ number_format($total, 0, ',', '.') }} altas</p>

            <div class="flex flex-col gap-3.5">
                @foreach ($byShop as $row)
                    @php [$name, $value, $pct] = [$row['name'], $row['value'], $row['pct']]; @endphp
                    <div>
                        <div class="mb-1.5 flex justify-between text-sm">
                            <span class="font-bold">{{ $name }}</span>
                            <span><strong class="tnum">{{ $value }}</strong> <span class="text-faint">· {{ $pct }} %</span></span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-surface2">
                            <div class="h-full rounded-full bg-accent" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-ui.card>
    </div>

</x-layouts.modulo>
