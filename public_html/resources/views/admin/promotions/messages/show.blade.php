@php
    $pct = fn ($n, $d) => $d ? number_format($n / $d * 100, 1) . ' %' : '—';
    $maxTiming = max(1, max($timing ?: [0]));
@endphp

<x-layouts.modulo module="promo" :title="$message->subject"
                  :crumbs="[['label' => __('promotions.modulo'), 'route' => 'admin.promotions.index'], ['label' => 'Envíos', 'route' => 'admin.promotions.messages.index'], ['label' => $message->subject]]">

    <x-ui.page-header :title="$message->subject">
        <x-slot:actions>
            <form method="POST" action="{{ route('admin.promotions.messages.duplicate', $message) }}">
                @csrf
                <x-ui.btn type="submit" icon="copy">Volver a enviar</x-ui.btn>
            </form>
            @if ($message->delivery_status === \App\Enums\DeliveryStatus::Scheduled)
                <form method="POST" action="{{ route('admin.promotions.messages.cancel', $message) }}">
                    @csrf
                    <x-ui.btn type="submit" variant="danger">Cancelar envío</x-ui.btn>
                </form>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.flash />

    <div class="flex flex-wrap items-center gap-2 text-sm text-muted">
        <x-ui.badge :tone="$message->delivery_status->tone()">{{ $message->delivery_status->label() }}</x-ui.badge>
        <span class="inline-flex items-center gap-1.5"><x-icon :name="$message->channel->icon()" :size="16" /> {{ $message->channel->label() }}</span>
        <span>·</span>
        <span>{{ $message->sent_at ? 'Enviado el ' . $message->sent_at->format('d/m/Y H:i') : ($message->scheduled_at ? 'Programado para el ' . $message->scheduled_at->format('d/m/Y H:i') : 'Sin enviar') }}</span>
        @if ($message->rule)
            <span>·</span>
            <a href="{{ route('admin.promotions.rules.edit', $message->rule) }}" class="inline-flex items-center gap-1 font-bold text-accent-fg hover:underline">
                <x-icon name="zap" :size="14" /> {{ $message->rule->name }}
            </a>
        @endif
    </div>

    @if ($message->delivery_status === \App\Enums\DeliveryStatus::Sending)
        <p role="status" class="rounded-card border border-line bg-surface px-5 py-3.5 text-sm">
            <strong>Enviando…</strong> Los lotes salen por la cola de trabajos (<code>php artisan queue:work</code>). Recarga para ver el avance.
        </p>
    @endif

    <section aria-label="Resultados" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-ui.stat label="Destinatarios" :value="number_format($totals['recipients'])" icon="users" />
        <x-ui.stat label="Entregados" :value="number_format($totals['sent'])" :hint="$totals['failed'] ? $totals['failed'] . ' fallidos' : 'Sin errores'" icon="send" />
        <x-ui.stat label="Abiertos" :value="$pct($totals['opened'], $totals['sent'])" :hint="number_format($totals['opened']) . ' clientes'" icon="eye" />
        <x-ui.stat label="Tasa de clics" :value="$pct($totals['clicked'], $totals['sent'])" :hint="number_format($totals['clicks']) . ' clics en total'" icon="arrow-right" />
        <x-ui.stat label="Cupones usados" :value="$couponsUsed === null ? '—' : number_format($couponsUsed)" :hint="$message->coupon?->name ?? 'Sin cupón adjunto'" icon="ticket" />
    </section>

    <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-[minmax(0,1fr)_420px]">
        <div class="flex min-w-0 flex-col gap-5">
            {{-- Clics por perfil (性別・年代別・職業別) --}}
            <x-ui.section title="Clics por perfil" description="Clientes que hicieron clic sobre los que recibieron el mensaje.">
                <div class="grid gap-6 p-5 lg:grid-cols-3">
                    @foreach ($breakdowns as $titulo => $filas)
                        <div>
                            <h3 class="mb-2 text-sm font-bold">{{ $titulo }}</h3>
                            <ul class="flex flex-col gap-2">
                                @foreach ($filas as $fila)
                                    @continue($fila['sent'] === 0)
                                    <li class="text-sm">
                                        <div class="flex justify-between gap-2"><span>{{ $fila['label'] }}</span>
                                            <span class="tnum text-muted">{{ $fila['clicked'] }}/{{ $fila['sent'] }}</span></div>
                                        <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-surface2">
                                            <div class="h-full rounded-full bg-accent" style="width: {{ $fila['sent'] ? round($fila['clicked'] / $fila['sent'] * 100) : 0 }}%"></div>
                                        </div>
                                    </li>
                                @endforeach
                                @if (collect($filas)->sum('sent') === 0)
                                    <li class="text-sm text-faint">Sin datos todavía.</li>
                                @endif
                            </ul>
                        </div>
                    @endforeach
                </div>
            </x-ui.section>

            <x-ui.section title="Destinatarios" :description="number_format($list->total()) . ' clientes'">
                <div class="relative overflow-x-auto">
                    <table class="w-full min-w-[560px] text-sm">
                        <thead>
                            <tr class="bg-surface2 text-left text-xs font-bold whitespace-nowrap text-muted">
                                <th scope="col" class="px-5 py-2.5">Cliente</th>
                                <th scope="col" class="px-3 py-2.5">Estado</th>
                                <th scope="col" class="px-3 py-2.5">Abierto</th>
                                <th scope="col" class="px-5 py-2.5">Clic</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($list as $r)
                                <tr class="border-t border-line">
                                    <td class="px-5 py-2.5">
                                        @if ($r->customer)
                                            <a href="{{ route('admin.customers.show', $r->customer) }}" class="font-bold hover:text-accent-fg">{{ $r->customer->greetingName() }}</a>
                                            <span class="tnum text-xs text-faint">{{ $r->customer->code }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2.5">
                                        @if ($r->failed_at)
                                            <x-ui.badge tone="outline" title="{{ $r->error }}">Fallido</x-ui.badge>
                                        @elseif ($r->sent_at)
                                            <x-ui.badge>Entregado</x-ui.badge>
                                        @else
                                            <x-ui.badge tone="accent">Pendiente</x-ui.badge>
                                        @endif
                                    </td>
                                    <td class="tnum px-3 py-2.5 whitespace-nowrap text-muted">{{ $r->opened_at?->format('d/m H:i') ?? '—' }}</td>
                                    <td class="tnum px-5 py-2.5 whitespace-nowrap text-muted">{{ $r->clicked_at ? $r->clicked_at->format('d/m H:i') . ' · ' . $r->click_count . '×' : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($list->hasPages())
                    <div class="border-t border-line px-4 py-3">{{ $list->onEachSide(1)->links() }}</div>
                @endif
            </x-ui.section>
        </div>

        <div class="flex flex-col gap-5">
            <x-ui.section title="Tiempo hasta abrirlo">
                <ul class="flex flex-col gap-2.5 p-5">
                    @foreach ($timing as $tramo => $n)
                        <li class="grid grid-cols-[124px_minmax(0,1fr)_36px] items-center gap-2 text-sm">
                            <span class="whitespace-nowrap text-muted">{{ $tramo === 'Sin abrir' || $tramo === 'Más tarde' ? $tramo : 'Antes de ' . $tramo }}</span>
                            <span class="h-2 overflow-hidden rounded-full bg-surface2">
                                <span class="block h-full rounded-full {{ $tramo === 'Sin abrir' ? 'bg-line' : 'bg-accent' }}" style="width: {{ round($n / $maxTiming * 100) }}%"></span>
                            </span>
                            <span class="tnum text-right">{{ $n }}</span>
                        </li>
                    @endforeach
                </ul>
                <p class="border-t border-line px-5 py-3 text-xs text-faint">
                    La apertura se mide en los emails con diseño (imagen invisible) y en las notificaciones push. En los emails de texto solo se ven los clics.
                </p>
            </x-ui.section>

            <x-ui.section title="Destinatarios elegidos">
                <dl class="grid grid-cols-[minmax(0,auto)_minmax(0,1fr)] gap-x-4 gap-y-2 p-5 text-sm">
                    @foreach ($summary as [$label, $value])
                        <dt class="text-muted">{{ $label }}</dt>
                        <dd class="font-semibold">{{ $value }}</dd>
                    @endforeach
                    <dt class="text-muted">Tienda</dt>
                    <dd class="font-semibold">{{ $message->shop?->name ?? 'Todas' }}</dd>
                </dl>
            </x-ui.section>

            <x-ui.section title="Contenido">
                <div class="p-5 text-sm leading-relaxed break-words">
                    @if ($message->channel === \App\Enums\MessageChannel::HtmlEmail)
                        {{-- HTML escrito por el administrador al crear el envío --}}
                        {!! $message->body !!}
                    @else
                        <p class="whitespace-pre-line">{{ $message->body }}</p>
                    @endif
                </div>
            </x-ui.section>
        </div>
    </div>

</x-layouts.modulo>
