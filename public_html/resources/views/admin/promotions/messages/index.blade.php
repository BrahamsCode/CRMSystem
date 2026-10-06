@php
    $pct = fn ($v) => $v === null ? '—' : number_format($v, 1) . ' %';
    $tabUrl = fn ($key) => route('admin.promotions.messages.index', ['tab' => $key]);
@endphp

<x-layouts.modulo module="promo" title="Envíos"
                  :crumbs="[['label' => __('promotions.modulo'), 'route' => 'admin.promotions.index'], ['label' => 'Envíos']]">

    <x-ui.page-header title="Envíos" description="Newsletter, emails con diseño y notificaciones push: programados, enviados y borradores.">
        <x-slot:actions>
            <x-ui.btn variant="primary" icon="plus" :href="route('admin.promotions.messages.create')">Nuevo envío</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.flash />

    <x-ui.tabs :items="$tabs" :current="$tab" :href="$tabUrl" :counts="$counts" />

    <form method="GET" class="flex flex-wrap items-end gap-3">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <x-ui.field label="Buscar" class="w-64">
            <x-ui.input type="search" name="q" :value="request('q')" placeholder="Título o contenido" />
        </x-ui.field>
        <x-ui.field label="Canal" class="w-48">
            <x-ui.select name="channel">
                <option value="">Todos</option>
                @foreach (\App\Enums\MessageChannel::cases() as $c)
                    <option value="{{ $c->value }}" @selected(request()->integer('channel') === $c->value)>{{ $c->label() }}</option>
                @endforeach
            </x-ui.select>
        </x-ui.field>
        <x-ui.field label="Desde" class="w-44"><x-ui.input type="date" name="from" :value="request('from')" /></x-ui.field>
        <x-ui.field label="Hasta" class="w-44"><x-ui.input type="date" name="to" :value="request('to')" /></x-ui.field>
        <x-ui.btn type="submit" icon="search">Filtrar</x-ui.btn>
    </form>

    <x-ui.card :pad="false">
        <div class="relative overflow-x-auto">
            <table class="w-full min-w-[860px] text-sm">
                <thead>
                    <tr class="bg-surface2 text-left text-xs font-bold whitespace-nowrap text-muted">
                        <th scope="col" class="px-5 py-2.5">Envío</th>
                        <th scope="col" class="px-3 py-2.5">{{ $tab === 'scheduled' ? 'Se envía' : ($tab === 'drafts' ? 'Modificado' : 'Enviado') }}</th>
                        <th scope="col" class="px-3 py-2.5">Destinatarios</th>
                        @if (in_array($tab, ['sent', 'auto'], true))
                            <th scope="col" class="px-3 py-2.5 text-right">Entregados</th>
                            <th scope="col" class="px-3 py-2.5 text-right">Apertura</th>
                            <th scope="col" class="px-3 py-2.5 text-right">Clics</th>
                        @endif
                        <th scope="col" class="px-5 py-2.5"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($messages as $m)
                        @php $abrir = $m->delivery_status->isPending() || $m->delivery_status === \App\Enums\DeliveryStatus::Cancelled ? route('admin.promotions.messages.edit', $m) : route('admin.promotions.messages.show', $m); @endphp
                        <tr class="border-t border-line align-top hover:bg-surface2/60">
                            <td class="px-5 py-3">
                                <a href="{{ $abrir }}" class="flex items-start gap-2.5">
                                    <span class="mt-0.5 text-muted"><x-icon :name="$m->channel->icon()" /></span>
                                    <span class="flex min-w-0 flex-col">
                                        <span class="font-bold hover:text-accent-fg">{{ $m->subject }}</span>
                                        <span class="text-xs text-faint">
                                            {{ $m->channel->label() }}
                                            @if ($m->rule) · <x-icon name="zap" :size="12" class="inline" /> {{ $m->rule->name }} @endif
                                            @if ($m->coupon) · <x-icon name="ticket" :size="12" class="inline" /> {{ $m->coupon->name }} @endif
                                        </span>
                                    </span>
                                </a>
                            </td>
                            <td class="tnum px-3 py-3 whitespace-nowrap text-muted">
                                @if ($tab === 'scheduled') {{ $m->scheduled_at?->format('d/m/Y H:i') }}
                                @elseif ($tab === 'drafts') {{ $m->updated_at?->format('d/m/Y H:i') }}
                                @else {{ $m->sent_at?->format('d/m/Y H:i') ?? '—' }}
                                @endif
                                <div><x-ui.badge :tone="$m->delivery_status->tone()">{{ $m->delivery_status->label() }}</x-ui.badge></div>
                            </td>
                            <td class="max-w-80 px-3 py-3 text-xs text-muted">
                                {{ collect($search->summary($m->filters ?? []))->map(fn ($p) => $p[0] . ': ' . $p[1])->join(' · ') }}
                            </td>
                            @if (in_array($tab, ['sent', 'auto'], true))
                                <td class="tnum px-3 py-3 text-right">{{ number_format($m->sent_count) }}</td>
                                <td class="tnum px-3 py-3 text-right">{{ $pct($m->sent_count ? $m->opened_count / $m->sent_count * 100 : null) }}</td>
                                <td class="tnum px-3 py-3 text-right font-bold">{{ $pct($m->clickRate()) }}</td>
                            @endif
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <x-ui.btn :href="$abrir" class="h-8.5 px-3">{{ $m->delivery_status->isPending() ? 'Editar' : 'Ver' }}</x-ui.btn>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <x-ui.empty icon="send" title="No hay envíos en esta sección">
                                    Crea un envío nuevo o cambia los filtros.
                                    <x-slot:action>
                                        <x-ui.btn variant="primary" icon="plus" :href="route('admin.promotions.messages.create')">Nuevo envío</x-ui.btn>
                                    </x-slot:action>
                                </x-ui.empty>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($messages->hasPages())
            <div class="border-t border-line px-4 py-3">{{ $messages->onEachSide(1)->links() }}</div>
        @endif
    </x-ui.card>

</x-layouts.modulo>
