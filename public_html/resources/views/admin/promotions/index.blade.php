@php
    $accesos = [
        ['label' => 'Nuevo envío', 'sub' => 'Email o notificación push', 'icon' => 'send', 'route' => 'admin.promotions.messages.create', 'destacado' => true],
        ['label' => 'Nueva automatización', 'sub' => 'Cumpleaños, seguimiento, recordatorios', 'icon' => 'zap', 'route' => 'admin.promotions.rules.create'],
        ['label' => 'Nuevo cupón', 'sub' => 'Descuentos y canjes', 'icon' => 'ticket', 'route' => 'admin.promotions.coupons.create'],
        ['label' => 'Nueva encuesta', 'sub' => 'Con premio por responder', 'icon' => 'clipboard', 'route' => 'admin.promotions.surveys.create'],
    ];
    $pct = fn ($v) => $v === null ? '—' : number_format($v, 1) . ' %';
@endphp

<x-layouts.modulo module="promo" title="Promociones" :crumbs="[['label' => __('promotions.modulo')]]">

    <x-ui.page-header title="Promociones"
                      description="Envíos a tus clientes, automatizaciones, cupones y fidelización con sellos y puntos." />

    <x-ui.flash />

    {{-- Lista de correo: las cifras de la portada del legacy --}}
    <section aria-label="Lista de clientes" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-ui.stat label="Clientes registrados" :value="number_format($members['total'])" icon="users" />
        <x-ui.stat label="Reciben newsletter" :value="number_format($members['reachable'])" hint="Con email y «Enviar»" icon="mail" />
        <x-ui.stat label="No entregables" :value="number_format($members['undeliverable'])" hint="3 o más correos devueltos" icon="alert" />
        <x-ui.stat label="Altas este mes" :value="number_format($members['month'])" icon="user-plus" />
        <x-ui.stat label="Altas hoy" :value="number_format($members['today'])" icon="cal" />
    </section>

    <section aria-labelledby="ult30" class="flex flex-col gap-3">
        <h2 id="ult30" class="text-xs font-bold tracking-wider text-faint uppercase">Últimos 30 días</h2>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-ui.stat label="Mensajes entregados" :value="number_format($kpis['sent'])" icon="send" />
            <x-ui.stat label="Tasa de apertura" :value="$pct($kpis['openRate'])" hint="Emails con diseño y push" icon="eye" />
            <x-ui.stat label="Tasa de clics" :value="$pct($kpis['clickRate'])" hint="Clientes que abrieron un enlace" icon="arrow-right" />
            <x-ui.stat label="Cupones usados" :value="number_format($kpis['couponsUsed'])" icon="ticket" />
        </div>
    </section>

    <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-2">
        <section aria-labelledby="accesos">
            <h2 id="accesos" class="mb-3 text-xs font-bold tracking-wider text-faint uppercase">Accesos rápidos</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($accesos as $a)
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

        <x-ui.section title="Próximos envíos" description="Programados y automatizaciones activas">
            <x-slot:actions>
                <x-ui.btn :href="route('admin.promotions.messages.index', ['tab' => 'scheduled'])" class="h-9">Ver todos</x-ui.btn>
            </x-slot:actions>
            <ul class="divide-y divide-line">
                @foreach ($scheduled as $m)
                    <li class="flex items-center gap-3 px-5 py-3">
                        <x-icon :name="$m->channel->icon()" class="text-muted" />
                        <a href="{{ route('admin.promotions.messages.edit', $m) }}" class="min-w-0 flex-1 truncate font-bold hover:text-accent-fg">{{ $m->subject }}</a>
                        <span class="tnum text-sm whitespace-nowrap text-muted">{{ $m->scheduled_at?->format('d/m H:i') }}</span>
                    </li>
                @endforeach
                @foreach ($rules as $r)
                    <li class="flex items-center gap-3 px-5 py-3">
                        <x-icon name="zap" class="text-accent-fg" />
                        <a href="{{ route('admin.promotions.rules.edit', $r) }}" class="min-w-0 flex-1 truncate font-bold hover:text-accent-fg">{{ $r->name }}</a>
                        <span class="tnum text-sm whitespace-nowrap text-muted">{{ $r->nextRunAt()?->format('d/m H:i') ?? '—' }}</span>
                    </li>
                @endforeach
                @if ($scheduled->isEmpty() && $rules->isEmpty())
                    <li><x-ui.empty icon="cal" title="Nada programado">Programa un envío o crea una automatización.</x-ui.empty></li>
                @endif
            </ul>
        </x-ui.section>
    </div>

    <x-ui.section title="Últimos envíos">
        <x-slot:actions>
            <x-ui.btn :href="route('admin.promotions.analytics')" class="h-9" icon="stats">Rendimiento</x-ui.btn>
        </x-slot:actions>
        <div class="relative overflow-x-auto">
            <table class="w-full min-w-[640px] text-sm">
                <thead>
                    <tr class="bg-surface2 text-left text-xs font-bold whitespace-nowrap text-muted">
                        <th scope="col" class="px-5 py-2.5">Envío</th>
                        <th scope="col" class="px-3 py-2.5">Fecha</th>
                        <th scope="col" class="px-3 py-2.5 text-right">Entregados</th>
                        <th scope="col" class="px-5 py-2.5 text-right">Tasa de clics</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recent as $m)
                        <tr class="border-t border-line hover:bg-surface2/60">
                            <td class="px-5 py-3">
                                <a href="{{ route('admin.promotions.messages.show', $m) }}" class="flex items-center gap-2.5 font-bold hover:text-accent-fg">
                                    <x-icon :name="$m->channel->icon()" class="text-muted" /> {{ $m->subject }}
                                </a>
                            </td>
                            <td class="tnum px-3 py-3 whitespace-nowrap text-muted">{{ $m->sent_at?->format('d/m/Y H:i') }}</td>
                            <td class="tnum px-3 py-3 text-right">{{ number_format($m->sent_count) }}</td>
                            <td class="tnum px-5 py-3 text-right font-bold">{{ $pct($m->clickRate()) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><x-ui.empty icon="send" title="Todavía no hay envíos">Crea el primero desde «Nuevo envío».</x-ui.empty></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.section>

</x-layouts.modulo>
