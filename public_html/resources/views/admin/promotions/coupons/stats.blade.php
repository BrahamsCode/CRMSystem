@php
    $periodos = ['today' => 'Hoy', 'week' => '7 días', 'month' => '30 días', 'year' => '1 año', 'custom' => 'Fechas'];
    $maxDia = max(1, $byDay->max() ?? 0);
@endphp

<x-layouts.modulo module="promo" title="Estadísticas de cupones"
                  :crumbs="[['label' => __('promotions.modulo'), 'route' => 'admin.promotions.index'], ['label' => 'Cupones', 'route' => 'admin.promotions.coupons.index'], ['label' => 'Estadísticas']]">

    <x-ui.page-header title="Estadísticas de cupones" description="Cupones usados en caja por periodo, tienda y tipo, y los más usados." />

    <form method="GET" class="flex flex-wrap items-end gap-3" x-data="{ periodo: @js($period) }">
        <x-ui.field label="Periodo" group>
            <div class="flex flex-wrap gap-1">
                @foreach ($periodos as $key => $label)
                    <label class="cursor-pointer rounded-ctl border px-3 py-2 text-sm font-bold"
                           :class="periodo === '{{ $key }}' ? 'border-accent bg-accent text-on-accent' : 'border-line bg-surface hover:bg-surface2'">
                        <input type="radio" name="period" value="{{ $key }}" x-model="periodo" class="sr-only" @change="if (periodo !== 'custom') $el.form.submit()">{{ $label }}
                    </label>
                @endforeach
            </div>
        </x-ui.field>
        <x-ui.field label="Desde" x-show="periodo === 'custom'" x-cloak><x-ui.input type="date" name="from" :value="$from->toDateString()" /></x-ui.field>
        <x-ui.field label="Hasta" x-show="periodo === 'custom'" x-cloak><x-ui.input type="date" name="to" :value="$to->toDateString()" /></x-ui.field>
        <x-ui.field label="Uso" class="w-56">
            <x-ui.select name="usage">
                <option value="">Todos</option>
                @foreach (\App\Enums\CouponUsage::cases() as $u)
                    <option value="{{ $u->value }}" @selected($usage === $u)>{{ $u->label() }}</option>
                @endforeach
            </x-ui.select>
        </x-ui.field>
        <x-ui.field label="Tienda donde se usó" class="w-52">
            <x-ui.select name="shop">
                <option value="">Todas</option>
                @foreach ($shops as $shop)
                    <option value="{{ $shop->id }}" @selected($shopId === $shop->id)>{{ $shop->name }}</option>
                @endforeach
            </x-ui.select>
        </x-ui.field>
        <x-ui.btn type="submit" icon="filter">Aplicar</x-ui.btn>
    </form>

    <section class="grid gap-4 sm:grid-cols-3" aria-label="Totales">
        <x-ui.stat label="Cupones usados" :value="number_format($totalUsed)" :hint="$from->format('d/m/Y') . ' – ' . $to->format('d/m/Y')" icon="check" />
        <x-ui.stat label="Cupones entregados" :value="number_format($totalIssued)" icon="gift" />
        <x-ui.stat label="Tasa de uso" :value="$totalIssued ? number_format($totalUsed / $totalIssued * 100, 1) . ' %' : '—'" icon="stats" />
    </section>

    <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-2">
        <x-ui.section title="Ranking de uso" description="使用数ランキング">
            <ol class="divide-y divide-line">
                @forelse ($ranking as $i => $r)
                    <li class="flex items-center gap-3 px-5 py-3">
                        <span class="tnum flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $i < 3 ? 'bg-accent text-on-accent' : 'bg-surface2' }} text-sm font-extrabold">{{ $i + 1 }}</span>
                        <a href="{{ route('admin.promotions.coupons.show', \App\Models\Coupon::withTrashed()->find($r->id)) }}" class="min-w-0 flex-1 truncate font-bold hover:text-accent-fg">{{ $r->name }}</a>
                        <x-ui.badge>{{ \App\Enums\CouponUsage::from((int) $r->usage_type)->label() }}</x-ui.badge>
                        <span class="tnum w-16 text-right font-bold">{{ $r->uses }}</span>
                    </li>
                @empty
                    <li><x-ui.empty icon="ticket" title="Ningún cupón usado en el periodo" /></li>
                @endforelse
            </ol>
        </x-ui.section>

        <x-ui.section title="Uso por día">
            <div class="p-5">
                @if ($byDay->isEmpty())
                    <p class="text-sm text-faint">Sin usos en el periodo.</p>
                @else
                    <ul class="flex flex-col gap-1.5">
                        @foreach ($byDay as $dia => $total)
                            <li class="grid grid-cols-[90px_minmax(0,1fr)_40px] items-center gap-2 text-sm">
                                <span class="tnum text-muted">{{ \Illuminate\Support\Carbon::parse($dia)->format('d/m/Y') }}</span>
                                <span class="h-2 overflow-hidden rounded-full bg-surface2"><span class="block h-full rounded-full bg-accent" style="width: {{ round($total / $maxDia * 100) }}%"></span></span>
                                <span class="tnum text-right font-bold">{{ $total }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </x-ui.section>
    </div>

</x-layouts.modulo>
