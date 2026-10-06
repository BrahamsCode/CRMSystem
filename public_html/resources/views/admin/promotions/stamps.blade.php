@php
    $design = $setting?->design ?? ['icon' => 'star', 'color' => '#c8343a'];
    $intervalos = [0 => 'Sin intervalo', 1800 => '30 minutos'] + collect(range(1, 24))->mapWithKeys(fn ($h) => [$h * 3600 => $h === 1 ? '1 hora' : "{$h} horas"])->all();
@endphp

<x-layouts.modulo module="promo" title="Tarjeta de sellos"
                  :crumbs="[['label' => __('promotions.modulo'), 'route' => 'admin.promotions.index'], ['label' => 'Tarjeta de sellos']]">

    <x-ui.page-header title="Tarjeta de sellos" :description="'Un sello por visita, premios al completar la tarjeta y vencimiento. Tienda: ' . ($shop?->name ?? '—')" />
    <x-ui.flash />

    @if (! $shop)
        <x-ui.card><x-ui.empty icon="star" title="No hay tiendas activas">Da de alta una tienda para configurar su tarjeta.</x-ui.empty></x-ui.card>
    @else
    <section class="grid gap-4 sm:grid-cols-3" aria-label="Cifras">
        <x-ui.stat label="Clientes con sellos" :value="number_format($totals['customers'])" icon="users" />
        <x-ui.stat label="Sellos en circulación" :value="number_format($totals['stamps'])" icon="star" />
        <x-ui.stat label="Sellos dados este mes" :value="number_format($totals['month'])" icon="cal" />
    </section>

    <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-[minmax(0,1fr)_400px]">
        <form method="POST" action="{{ route('admin.promotions.stamps.update') }}" class="flex flex-col gap-5"
              x-data="{ icono: @js(old('icon', $design['icon'])), color: @js(old('color', $design['color'])), tam: {{ (int) old('card_size', $setting->card_size ?? 0) }}, vence: {{ (int) old('expiry_flg', $setting->expiry_flg) }}, aviso: {{ (int) old('notice_flg', $setting->notice_flg) }} }">
            @csrf @method('PUT')

            <x-ui.section title="Tarjeta">
                <div class="grid gap-5 p-5 lg:grid-cols-[minmax(0,1fr)_260px]">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.field label="Sellos por tarjeta">
                            <x-ui.select name="card_size" x-model.number="tam">
                                @foreach ([5, 10, 20, 30, 40, 50, 60, 75, 100, 200, 300, 400, 500] as $n)
                                    <option value="{{ $n }}">{{ $n }}</option>
                                @endforeach
                                <option value="0">Sin límite</option>
                            </x-ui.select>
                        </x-ui.field>
                        <x-ui.field label="Sellos de regalo al darse de alta">
                            <x-ui.select name="signup_bonus">
                                @for ($n = 0; $n <= 10; $n++)
                                    <option value="{{ $n }}" @selected((int) old('signup_bonus', $setting->signup_bonus) === $n)>{{ $n }}</option>
                                @endfor
                            </x-ui.select>
                        </x-ui.field>
                        <x-ui.field label="Tiempo mínimo entre sellos" hint="Evita dos sellos si se lee la tarjeta dos veces.">
                            <x-ui.select name="interval_seconds">
                                @foreach ($intervalos as $s => $label)
                                    <option value="{{ $s }}" @selected((int) old('interval_seconds', $setting->interval_seconds) === $s)>{{ $label }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>
                        <x-ui.field label="Cupones de canje en Mi página">
                            <x-ui.select name="display_mode">
                                @foreach ($modes as $m)
                                    <option value="{{ $m->value }}" @selected((int) old('display_mode', $setting->display_mode?->value) === $m->value)>{{ $m->label() }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>
                        <div class="sm:col-span-2">
                            <x-ui.toggle name="visit_stamp_flg" :checked="(bool) old('visit_stamp_flg', $setting->visit_stamp_flg)" label="Dar un sello en cada visita" />
                        </div>
                        <x-ui.field label="Diseño" group class="sm:col-span-2">
                            <div class="flex flex-wrap items-center gap-2">
                                @foreach ($icons as $icon)
                                    <label class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-ctl border"
                                           :class="icono === '{{ $icon }}' ? 'border-accent bg-accent-soft' : 'border-line'">
                                        <input type="radio" name="icon" value="{{ $icon }}" x-model="icono" class="sr-only"><x-icon :name="$icon" />
                                    </label>
                                @endforeach
                                <input type="color" name="color" x-model="color" aria-label="Color" class="h-10 w-14 cursor-pointer rounded-ctl border border-line bg-surface">
                            </div>
                        </x-ui.field>
                    </div>

                    {{-- Vista previa de la tarjeta --}}
                    <div aria-hidden="true" class="self-start rounded-card p-4 text-white shadow-sm" :style="'background:' + color">
                        <div class="text-sm font-bold">{{ $shop->name }}</div>
                        <div class="text-xs opacity-80">Tarjeta de sellos</div>
                        <div class="mt-3 grid grid-cols-5 gap-2">
                            <template x-for="n in Math.min(tam || 10, 20)">
                                <span class="flex aspect-square items-center justify-center rounded-full border border-white/50" :class="n <= 3 && 'bg-white/90'">
                                    <span x-show="n <= 3" :style="'color:' + color">
                                        @foreach ($icons as $icon)
                                            <span x-show="icono === '{{ $icon }}'"><x-icon :name="$icon" :size="16" /></span>
                                        @endforeach
                                    </span>
                                </span>
                            </template>
                        </div>
                        <div class="mt-2 text-right text-xs opacity-80" x-text="tam ? '3 / ' + tam : '3 sellos'"></div>
                    </div>
                </div>
            </x-ui.section>

            <x-ui.section title="Vencimiento">
                <div class="flex flex-col gap-4 p-5">
                    <x-ui.toggle name="expiry_flg" :checked="(bool) old('expiry_flg', $setting->expiry_flg)" label="Los sellos vencen" x-on:change="vence = $event.target.checked ? 1 : 0" />
                    <div x-show="vence" x-cloak class="flex flex-wrap items-end gap-4">
                        <x-ui.field label="Validez (días)" class="w-36"><x-ui.input type="number" name="expiry_days" min="1" max="999" :value="old('expiry_days', $setting->expiry_days)" /></x-ui.field>
                        <x-ui.field label="Hora del vencimiento" group>
                            <div class="flex items-center gap-1.5">
                                <x-ui.select name="expiry_hour" class="w-20">@for ($h = 0; $h < 24; $h++)<option value="{{ $h }}" @selected((int) old('expiry_hour', $setting->expiry_hour ?? 3) === $h)>{{ sprintf('%02d', $h) }}</option>@endfor</x-ui.select>
                                <span>:</span>
                                <x-ui.select name="expiry_minute" class="w-20">@foreach ([0, 30] as $m)<option value="{{ $m }}" @selected((int) old('expiry_minute', $setting->expiry_minute) === $m)>{{ sprintf('%02d', $m) }}</option>@endforeach</x-ui.select>
                            </div>
                        </x-ui.field>
                    </div>
                    <div x-show="vence" x-cloak class="flex flex-col gap-3 border-t border-line pt-4">
                        <x-ui.toggle name="notice_flg" :checked="(bool) old('notice_flg', $setting->notice_flg)" label="Avisar antes de que venzan" x-on:change="aviso = $event.target.checked ? 1 : 0" />
                        <div x-show="aviso" x-cloak class="flex flex-wrap items-end gap-4">
                            <x-ui.field label="Días antes" class="w-36"><x-ui.input type="number" name="notice_days" min="1" :value="old('notice_days', $setting->notice_days)" /></x-ui.field>
                            <x-ui.field label="Hora del aviso" group>
                                <div class="flex items-center gap-1.5">
                                    <x-ui.select name="notice_hour" class="w-20">@for ($h = 0; $h < 24; $h++)<option value="{{ $h }}" @selected((int) old('notice_hour', $setting->notice_hour ?? 10) === $h)>{{ sprintf('%02d', $h) }}</option>@endfor</x-ui.select>
                                    <span>:</span>
                                    <x-ui.select name="notice_minute" class="w-20">@foreach ([0, 30] as $m)<option value="{{ $m }}" @selected((int) old('notice_minute', $setting->notice_minute) === $m)>{{ sprintf('%02d', $m) }}</option>@endforeach</x-ui.select>
                                </div>
                            </x-ui.field>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end border-t border-line px-5 py-3.5">
                    <x-ui.btn type="submit" variant="primary">Guardar tarjeta</x-ui.btn>
                </div>
            </x-ui.section>
        </form>

        <div class="flex flex-col gap-5">
            <x-ui.section title="Premios" description="Al llegar a N sellos: cupón y aviso al cliente.">
                <ul class="divide-y divide-line">
                    @forelse ($rules as $rule)
                        <li class="flex items-center gap-3 px-5 py-3">
                            <span class="tnum flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-accent-soft font-extrabold text-accent-fg">{{ $rule->stamp_count }}</span>
                            <span class="flex min-w-0 flex-1 flex-col text-sm">
                                <span class="font-bold">{{ $rule->coupon?->name ?? 'Solo aviso' }}</span>
                                <span class="text-xs text-faint">{{ $rule->notify_flg ? 'Avisa al cliente' : 'Sin aviso' }}{{ $rule->reset_flg ? ' · tarjeta nueva' : '' }}</span>
                            </span>
                            <form method="POST" action="{{ route('admin.promotions.stamps.rules.destroy', $rule) }}">@csrf @method('DELETE')
                                <button type="submit" class="flex h-9 w-9 items-center justify-center rounded-ctl text-danger hover:bg-danger/10" aria-label="Quitar regla"><x-icon name="trash" :size="16" /></button>
                            </form>
                        </li>
                    @empty
                        <li class="px-5 py-4 text-sm text-faint">Sin premios configurados.</li>
                    @endforelse
                </ul>
                <form method="POST" action="{{ route('admin.promotions.stamps.rules.store') }}" class="flex flex-col gap-3 border-t border-line p-5">
                    @csrf
                    <div class="grid grid-cols-[100px_minmax(0,1fr)] gap-3">
                        <x-ui.field label="Sellos"><x-ui.input type="number" name="stamp_count" min="1" required /></x-ui.field>
                        <x-ui.field label="Cupón">
                            <x-ui.select name="coupon_id">
                                <option value="">Ninguno</option>
                                @foreach ($allCoupons as $c) <option value="{{ $c->id }}">{{ $c->name }}</option> @endforeach
                            </x-ui.select>
                        </x-ui.field>
                    </div>
                    <x-ui.field label="Texto del aviso (opcional)"><x-ui.input name="message" placeholder="Por defecto: «Ya tienes N sellos…»" /></x-ui.field>
                    <x-ui.toggle name="notify_flg" :checked="true" label="Avisar al cliente" />
                    <x-ui.toggle name="reset_flg" :checked="false" label="Empezar tarjeta nueva al llegar" />
                    <x-ui.btn type="submit" icon="plus">Añadir premio</x-ui.btn>
                </form>
            </x-ui.section>

            <x-ui.section title="Ajuste manual">
                <form method="POST" action="{{ route('admin.promotions.stamps.adjust') }}" class="flex flex-col gap-3 p-5">
                    @csrf
                    <div class="grid grid-cols-2 gap-3">
                        <x-ui.field label="Nº de socio"><x-ui.input name="code" required inputmode="numeric" /></x-ui.field>
                        <x-ui.field label="Sellos" hint="Negativo para quitar."><x-ui.input type="number" name="quantity" required /></x-ui.field>
                    </div>
                    <x-ui.field label="Motivo"><x-ui.input name="note" /></x-ui.field>
                    <x-ui.btn type="submit">Aplicar</x-ui.btn>
                </form>
            </x-ui.section>

            @if ($coupons->isNotEmpty())
                <x-ui.section title="Cupones de canje" description="Los que el cliente puede pedir con sus sellos.">
                    <ul class="divide-y divide-line text-sm">
                        @foreach ($coupons as $c)
                            <li class="flex justify-between gap-2 px-5 py-2.5"><a href="{{ route('admin.promotions.coupons.show', $c) }}" class="font-bold hover:text-accent-fg">{{ $c->name }}</a><span class="tnum text-muted">{{ $c->cost }} sellos</span></li>
                        @endforeach
                    </ul>
                </x-ui.section>
            @endif
        </div>
    </div>

    <x-ui.section title="Últimos movimientos">
        <ul class="divide-y divide-line text-sm">
            @forelse ($recent as $m)
                <li class="flex items-center gap-3 px-5 py-2.5">
                    <span class="tnum w-14 text-right font-bold {{ $m->quantity > 0 ? 'text-accent-fg' : 'text-muted' }}">{{ $m->quantity > 0 ? '+' : '' }}{{ $m->quantity }}</span>
                    <span class="min-w-0 flex-1 truncate">{{ $m->customer?->greetingName() }} · {{ $m->type->label() }}{{ $m->note ? ' · ' . $m->note : '' }}</span>
                    <span class="tnum text-xs text-faint">{{ $m->created_at->format('d/m H:i') }}</span>
                </li>
            @empty
                <li class="px-5 py-4 text-faint">Sin movimientos.</li>
            @endforelse
        </ul>
    </x-ui.section>
    @endif

</x-layouts.modulo>
