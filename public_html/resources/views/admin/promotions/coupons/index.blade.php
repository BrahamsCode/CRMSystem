<x-layouts.modulo module="promo" title="Cupones"
                  :crumbs="[['label' => __('promotions.modulo'), 'route' => 'admin.promotions.index'], ['label' => 'Cupones']]">

    <x-ui.page-header title="Cupones" description="Descuentos para enviar, canjear por sellos o puntos, o regalar al darse de alta, referir o responder una encuesta.">
        <x-slot:actions>
            <x-ui.btn icon="stats" :href="route('admin.promotions.coupons.stats')">Estadísticas de uso</x-ui.btn>
            <x-ui.btn variant="primary" icon="plus" :href="route('admin.promotions.coupons.create')">Nuevo cupón</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.flash />

    <form method="GET" class="flex flex-wrap items-end gap-3">
        <x-ui.field label="Uso" class="w-60">
            <x-ui.select name="usage" onchange="this.form.submit()">
                <option value="">Todos</option>
                @foreach (\App\Enums\CouponUsage::cases() as $u)
                    <option value="{{ $u->value }}" @selected($usage === $u)>{{ $u->label() }}</option>
                @endforeach
            </x-ui.select>
        </x-ui.field>
        @if ($shops->count() > 1)
            <x-ui.field label="Tienda" class="w-52">
                <x-ui.select name="shop" onchange="this.form.submit()">
                    <option value="">Todas</option>
                    @foreach ($shops as $shop)
                        <option value="{{ $shop->id }}" @selected($shopId === $shop->id)>{{ $shop->name }}</option>
                    @endforeach
                </x-ui.select>
            </x-ui.field>
        @endif
    </form>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($coupons as $c)
            <a href="{{ route('admin.promotions.coupons.show', $c) }}"
               @class(['group relative flex flex-col overflow-hidden rounded-card border bg-surface transition-colors hover:border-accent/40',
                   'border-line' => $c->status === 1, 'border-dashed border-line opacity-70' => $c->status !== 1])>
                <div class="flex items-center gap-4 border-b border-dashed border-line p-5">
                    <span class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-ctl bg-accent-soft text-accent-fg">
                        <span class="tnum text-lg leading-none font-extrabold">{{ $c->type === \App\Enums\CouponDiscountType::Percentage ? $c->value . '%' : '' }}</span>
                        @if ($c->type !== \App\Enums\CouponDiscountType::Percentage) <x-icon name="ticket" :size="22" /> @endif
                    </span>
                    <span class="flex min-w-0 flex-col">
                        <span class="truncate font-bold group-hover:text-accent-fg">{{ $c->name }}</span>
                        <span class="text-sm text-muted">{{ $c->discountLabel() }}</span>
                    </span>
                </div>
                <div class="flex flex-wrap items-center gap-2 px-5 py-3 text-xs">
                    <x-ui.badge>{{ $c->usage_type->label() }}</x-ui.badge>
                    @if ($c->usage_type->hasCost()) <x-ui.badge tone="outline">{{ $c->cost }} {{ $c->usage_type->costUnit() }}</x-ui.badge> @endif
                    @if ($c->status !== 1) <x-ui.badge tone="outline">Inactivo</x-ui.badge> @endif
                    @if ($c->isExpired()) <x-ui.badge tone="outline">Vencido</x-ui.badge> @endif
                </div>
                <div class="mt-auto flex justify-between gap-2 px-5 pb-4 text-xs text-faint">
                    <span>{{ $c->validityLabel() }} · {{ $c->shop?->name ?? 'Todas las tiendas' }}</span>
                    <span class="tnum font-bold text-ink">{{ $c->used_count }}/{{ $c->issued_count }} usados</span>
                </div>
            </a>
        @empty
            <x-ui.card class="md:col-span-2 xl:col-span-3">
                <x-ui.empty icon="ticket" title="No hay cupones">
                    Crea un cupón para adjuntarlo a un envío o para el canje de sellos y puntos.
                    <x-slot:action><x-ui.btn variant="primary" icon="plus" :href="route('admin.promotions.coupons.create')">Nuevo cupón</x-ui.btn></x-slot:action>
                </x-ui.empty>
            </x-ui.card>
        @endforelse
    </div>

    @if ($coupons->hasPages())
        {{ $coupons->onEachSide(1)->links() }}
    @endif

</x-layouts.modulo>
