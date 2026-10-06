@php
    $pct = fn ($n, $d) => $d ? number_format($n / $d * 100, 1) . ' %' : '—';
    $maxDia = max(1, collect($series)->max('sent'));
    $total = $channels->sum('sent');
@endphp

<x-layouts.modulo module="promo" title="Rendimiento"
                  :crumbs="[['label' => __('promotions.modulo'), 'route' => 'admin.promotions.index'], ['label' => 'Rendimiento']]">

    <x-ui.page-header title="Rendimiento" description="Qué tal funcionan tus envíos por canal: entregas, aperturas, clics, cupones y encuestas.">
        <x-slot:actions>
            <div class="flex gap-1" role="group" aria-label="Periodo">
                @foreach ([7 => '7 días', 30 => '30 días', 90 => '90 días', 365 => '1 año'] as $d => $label)
                    <a href="{{ route('admin.promotions.analytics', ['days' => $d]) }}"
                       @class(['rounded-ctl border px-3 py-2 text-sm font-bold', 'border-accent bg-accent text-on-accent' => $days === $d, 'border-line bg-surface hover:bg-surface2' => $days !== $d])
                       @if ($days === $d) aria-current="true" @endif>{{ $label }}</a>
                @endforeach
            </div>
        </x-slot:actions>
    </x-ui.page-header>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Totales">
        <x-ui.stat label="Mensajes entregados" :value="number_format($total)" icon="send" />
        <x-ui.stat label="Tasa de clics" :value="$pct($channels->sum('clicked'), $total)" icon="arrow-right" />
        <x-ui.stat label="Cupones usados" :value="number_format($coupons['used'])" :hint="$coupons['fromMessages'] . ' llegaron en un envío · ' . $coupons['issued'] . ' entregados'" icon="ticket" />
        <x-ui.stat label="Respuestas a encuestas" :value="number_format($surveyResponses)" icon="clipboard" />
    </section>

    <x-ui.section title="Por canal">
        <div class="relative overflow-x-auto">
            <table class="w-full min-w-[560px] text-sm">
                <thead><tr class="bg-surface2 text-left text-xs font-bold text-muted">
                    <th scope="col" class="px-5 py-2.5">Canal</th><th scope="col" class="px-3 py-2.5 text-right">Entregados</th>
                    <th scope="col" class="px-3 py-2.5 text-right">Apertura</th><th scope="col" class="px-5 py-2.5 text-right">Clics</th>
                </tr></thead>
                <tbody>
                    @foreach ($channels as $c)
                        <tr class="border-t border-line">
                            <th scope="row" class="px-5 py-3 text-left font-bold"><span class="inline-flex items-center gap-2"><x-icon :name="$c['channel']->icon()" class="text-muted" />{{ $c['channel']->label() }}</span></th>
                            <td class="tnum px-3 py-3 text-right">{{ number_format($c['sent']) }}</td>
                            <td class="tnum px-3 py-3 text-right">{{ $c['channel'] === \App\Enums\MessageChannel::TextEmail ? 'No se mide' : $pct($c['opened'], $c['sent']) }}</td>
                            <td class="tnum px-5 py-3 text-right font-bold">{{ $pct($c['clicked'], $c['sent']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.section>

    <x-ui.section title="Mensajes entregados por día">
        <div class="p-5">
            <div class="flex h-44 items-end gap-0.5" role="img" aria-label="Mensajes entregados por día en los últimos {{ $days }} días">
                @foreach ($series as $p)
                    <div class="group relative flex h-full min-w-0 flex-1 items-end" tabindex="0"
                         title="{{ $p['date']->format('d/m/Y') }}: {{ $p['sent'] }} entregados, {{ $p['clicked'] }} con clic">
                        <div class="w-full rounded-t-[4px] bg-accent transition-opacity group-hover:opacity-80 group-focus:opacity-80"
                             style="height: {{ $p['sent'] ? max(2, round($p['sent'] / $maxDia * 100)) : 0 }}%"></div>
                    </div>
                @endforeach
            </div>
            <div class="mt-2 flex justify-between text-xs text-faint">
                <span>{{ $series[0]['date']->format('d/m') }}</span><span>Máx. {{ number_format($maxDia) }} al día</span><span>{{ end($series)['date']->format('d/m') }}</span>
            </div>
        </div>
    </x-ui.section>

    <x-ui.section title="Envíos con más clics">
        <ul class="divide-y divide-line text-sm">
            @forelse ($top as $m)
                <li class="flex items-center gap-3 px-5 py-3">
                    <x-icon :name="$m->channel->icon()" class="text-muted" />
                    <a href="{{ route('admin.promotions.messages.show', $m) }}" class="min-w-0 flex-1 truncate font-bold hover:text-accent-fg">{{ $m->subject }}</a>
                    <span class="tnum text-muted">{{ number_format($m->sent_count) }} entregados</span>
                    <span class="tnum w-20 text-right font-bold">{{ $pct($m->clicked_count, $m->sent_count) }}</span>
                </li>
            @empty
                <li><x-ui.empty icon="stats" title="Sin envíos en el periodo" /></li>
            @endforelse
        </ul>
    </x-ui.section>

</x-layouts.modulo>
