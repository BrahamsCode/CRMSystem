@php
    $estados = ['' => 'Todos', 'available' => 'Disponibles', 'used' => 'Usados', 'expired' => 'Vencidos'];
@endphp

<x-layouts.modulo module="promo" :title="$coupon->name"
                  :crumbs="[['label' => __('promotions.modulo'), 'route' => 'admin.promotions.index'], ['label' => 'Cupones', 'route' => 'admin.promotions.coupons.index'], ['label' => $coupon->name]]">

    <x-ui.page-header :title="$coupon->name" :description="$coupon->discountLabel() . ' · ' . $coupon->usage_type->label() . ' · ' . $coupon->validityLabel()">
        <x-slot:actions>
            @if ($coupon->usage_type === \App\Enums\CouponUsage::Newsletter)
                <x-ui.btn icon="send" :href="route('admin.promotions.messages.create', ['coupon' => $coupon->id])">Enviar con este cupón</x-ui.btn>
            @endif
            <x-ui.btn icon="pencil" :href="route('admin.promotions.coupons.edit', $coupon)">Editar</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.flash />

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Cifras">
        <x-ui.stat label="Entregados" :value="number_format($totals['issued'])" icon="gift" />
        <x-ui.stat label="Usados" :value="number_format($totals['used'])" :hint="$totals['issued'] ? round($totals['used'] / $totals['issued'] * 100) . ' % de los entregados' : null" icon="check" />
        <x-ui.stat label="Disponibles" :value="number_format($totals['available'])" icon="ticket" />
        <x-ui.stat label="Vencidos sin usar" :value="number_format($totals['expired'])" icon="clock" />
    </section>

    <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
        <x-ui.section title="Entregas">
            <x-slot:actions>
                <div class="flex gap-1">
                    @foreach ($estados as $key => $label)
                        <a href="{{ route('admin.promotions.coupons.show', [$coupon] + ($key ? ['state' => $key] : [])) }}"
                           @class(['rounded-ctl px-2.5 py-1.5 text-xs font-bold', 'bg-accent text-on-accent' => ($state ?? '') === $key, 'text-muted hover:bg-surface2' => ($state ?? '') !== $key])>{{ $label }}</a>
                    @endforeach
                </div>
            </x-slot:actions>
            <div class="relative overflow-x-auto">
                <table class="w-full min-w-[640px] text-sm">
                    <thead>
                        <tr class="bg-surface2 text-left text-xs font-bold whitespace-nowrap text-muted">
                            <th scope="col" class="px-5 py-2.5">Cliente</th>
                            <th scope="col" class="px-3 py-2.5">Entregado</th>
                            <th scope="col" class="px-3 py-2.5">Vence</th>
                            <th scope="col" class="px-3 py-2.5">Estado</th>
                            <th scope="col" class="px-5 py-2.5"><span class="sr-only">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($issued as $i)
                            <tr class="border-t border-line">
                                <td class="px-5 py-2.5">
                                    @if ($i->customer)
                                        <a href="{{ route('admin.customers.show', $i->customer) }}" class="font-bold hover:text-accent-fg">{{ $i->customer->greetingName() }}</a>
                                        <span class="tnum text-xs text-faint">{{ $i->customer->code }}</span>
                                    @endif
                                </td>
                                <td class="tnum px-3 py-2.5 text-muted">{{ $i->issued_at->format('d/m/Y') }}</td>
                                <td class="tnum px-3 py-2.5 text-muted">{{ $i->expires_at?->format('d/m/Y') ?? 'Sin vencimiento' }}</td>
                                <td class="px-3 py-2.5">
                                    <x-ui.badge :tone="$i->isUsable() ? 'accent' : 'neutral'">{{ $i->stateLabel() }}</x-ui.badge>
                                    @if ($i->used_at) <span class="tnum text-xs text-faint">{{ $i->used_at->format('d/m H:i') }} · {{ $i->usedShop?->name }}</span> @endif
                                </td>
                                <td class="px-5 py-2.5 text-right">
                                    @if ($i->isUsable())
                                        <form method="POST" action="{{ route('admin.promotions.coupons.use', $i) }}" class="inline">
                                            @csrf
                                            <x-ui.btn type="submit" class="h-8.5 px-3" onclick="return confirm('¿Marcar como usado en caja?')">Marcar usado</x-ui.btn>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><x-ui.empty icon="ticket" title="Sin entregas">Todavía nadie tiene este cupón.</x-ui.empty></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($issued->hasPages())
                <div class="border-t border-line px-4 py-3">{{ $issued->onEachSide(1)->links() }}</div>
            @endif
        </x-ui.section>

        <div class="flex flex-col gap-5">
            <x-ui.section :title="$coupon->usage_type->hasCost() ? 'Canjear para un cliente' : 'Entregar a un cliente'"
                          :description="$coupon->usage_type->hasCost() ? 'Descuenta ' . $coupon->cost . ' ' . $coupon->usage_type->costUnit() . ' de su saldo.' : 'Por ejemplo, en el mostrador.'">
                <form method="POST" action="{{ route('admin.promotions.coupons.issue', $coupon) }}" class="flex items-end gap-2 p-5">
                    @csrf
                    <x-ui.field label="Nº de socio" class="flex-1">
                        <x-ui.input name="code" :value="old('code')" inputmode="numeric" required />
                    </x-ui.field>
                    <x-ui.btn type="submit" variant="primary" icon="gift">Entregar</x-ui.btn>
                </form>
            </x-ui.section>

            <x-ui.section title="Detalle">
                <dl class="grid grid-cols-[auto_minmax(0,1fr)] gap-x-4 gap-y-2 p-5 text-sm">
                    <dt class="text-muted">Tienda</dt><dd class="font-semibold">{{ $coupon->shop?->name ?? 'Todas' }}</dd>
                    <dt class="text-muted">Estado</dt><dd class="font-semibold">{{ $coupon->status === 1 ? 'Activo' : 'Inactivo' }}{{ $coupon->isExpired() ? ' · vencido' : '' }}</dd>
                    <dt class="text-muted">Repetible</dt><dd class="font-semibold">{{ $coupon->reissue_flg ? 'Sí' : 'Una vez por cliente' }}</dd>
                    <dt class="text-muted">Mi página</dt><dd class="font-semibold">{{ $coupon->display_flg ? 'Visible' : 'Oculto' }}</dd>
                    @if ($coupon->lottery_rank) <dt class="text-muted">Sorteo</dt><dd class="font-semibold">{{ $coupon->lottery_rank->label() }}</dd> @endif
                </dl>
                @if ($coupon->description || $coupon->notes)
                    <div class="border-t border-line p-5 text-sm">
                        @if ($coupon->description) <p class="whitespace-pre-line">{{ $coupon->description }}</p> @endif
                        @if ($coupon->notes)
                            <ul class="mt-2 list-disc pl-5 text-xs text-muted">
                                @foreach ($coupon->notes as $note) <li>{{ $note }}</li> @endforeach
                            </ul>
                        @endif
                    </div>
                @endif
            </x-ui.section>
        </div>
    </div>

</x-layouts.modulo>
