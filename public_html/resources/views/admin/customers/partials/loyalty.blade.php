{{-- Pestañas Mis cupones / Puntos / Sellos de la ficha: datos del módulo de promociones --}}
@if ($tipo === 'cupones')
    <x-ui.section title="Cupones" :description="$customer->coupons->count() . ' entregados'">
        <x-slot:actions>
            <x-ui.btn class="h-9" icon="ticket" :href="route('admin.promotions.coupons.index')">Entregar un cupón</x-ui.btn>
        </x-slot:actions>
        <ul class="divide-y divide-line text-sm">
            @forelse ($customer->coupons as $i)
                <li class="flex flex-wrap items-center gap-3 px-5 py-3">
                    <x-icon name="ticket" class="text-muted" />
                    <a href="{{ route('admin.promotions.coupons.show', $i->coupon) }}" class="min-w-0 flex-1 truncate font-bold hover:text-accent-fg">{{ $i->coupon?->name }}</a>
                    <span class="tnum text-xs text-faint">{{ $i->issued_at->format('d/m/Y') }}{{ $i->expires_at ? ' → ' . $i->expires_at->format('d/m/Y') : '' }}</span>
                    <x-ui.badge :tone="$i->isUsable() ? 'accent' : 'neutral'">{{ $i->stateLabel() }}</x-ui.badge>
                </li>
            @empty
                <li><x-ui.empty icon="ticket" title="Sin cupones">Este cliente todavía no tiene cupones.</x-ui.empty></li>
            @endforelse
        </ul>
    </x-ui.section>
@else
    @php
        $esPuntos = $tipo === 'puntos';
        $movs = $esPuntos ? $customer->points : $customer->stamps;
    @endphp
    <x-ui.section :title="$esPuntos ? 'Puntos' : 'Sellos'"
                  :description="'Saldo actual: ' . number_format($esPuntos ? $customer->point_balance : $customer->stamp_balance)">
        <x-slot:actions>
            <x-ui.btn class="h-9" :href="route($esPuntos ? 'admin.promotions.points' : 'admin.promotions.stamps')">Ajustar</x-ui.btn>
        </x-slot:actions>
        <ul class="divide-y divide-line text-sm">
            @forelse ($movs as $m)
                @php $n = $esPuntos ? $m->points : $m->quantity; @endphp
                <li class="flex items-center gap-3 px-5 py-2.5">
                    <span class="tnum w-16 text-right font-bold {{ $n > 0 ? 'text-accent-fg' : 'text-muted' }}">{{ $n > 0 ? '+' : '' }}{{ number_format($n) }}</span>
                    <span class="min-w-0 flex-1 truncate">{{ $m->type->label() }}{{ $m->note ? ' · ' . $m->note : '' }}</span>
                    @if ($m->expires_at && $n > 0) <span class="text-xs text-faint">vence {{ $m->expires_at->format('d/m/Y') }}</span> @endif
                    <span class="tnum text-xs text-faint">{{ $m->created_at->format('d/m/Y H:i') }}</span>
                </li>
            @empty
                <li><x-ui.empty :icon="$esPuntos ? 'coin' : 'star'" title="Sin movimientos">Todavía no hay movimientos.</x-ui.empty></li>
            @endforelse
        </ul>
    </x-ui.section>
@endif
