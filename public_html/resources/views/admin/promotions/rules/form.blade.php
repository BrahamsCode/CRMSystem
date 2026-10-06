@php
    use App\Enums\RuleTrigger;

    $editing = $rule->exists;
    $titulo = $editing ? $rule->name : 'Nueva automatización';
    $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    $dias = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
    $chip = 'inline-flex min-h-9 cursor-pointer items-center justify-center rounded-ctl border border-line px-2.5 text-sm font-semibold has-checked:border-accent has-checked:bg-accent has-checked:text-on-accent';
    $oldMonths = array_map('intval', old('months', $rule->months ?? []));
    $oldDays = array_map('strval', old('month_days', $rule->month_days ?? []));
    $oldWeek = array_map('intval', old('weekdays', $rule->weekdays ?? []));
@endphp

<x-layouts.modulo module="promo" :title="$titulo"
                  :crumbs="[['label' => __('promotions.modulo'), 'route' => 'admin.promotions.index'], ['label' => 'Automatizaciones', 'route' => 'admin.promotions.rules.index'], ['label' => $titulo]]">

    <x-ui.page-header :title="$titulo" description="Elige cuándo se dispara, a quién llega y qué dice.">
        @if ($editing)
            <x-slot:actions>
                <form method="POST" action="{{ route('admin.promotions.rules.run', $rule) }}">
                    @csrf
                    <x-ui.btn type="submit" icon="send" onclick="return confirm('¿Ejecutar ahora para los clientes de hoy?')">Ejecutar hoy</x-ui.btn>
                </form>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    <x-ui.flash />

    <form method="POST" action="{{ $editing ? route('admin.promotions.rules.update', $rule) : route('admin.promotions.rules.store') }}"
          class="flex flex-col gap-5" x-data="{ disparador: {{ (int) old('trigger', $rule->trigger->value) }} }">
        @csrf
        @if ($editing) @method('PUT') @endif

        <x-ui.section title="1. Cuándo">
            <div class="flex flex-col gap-5 p-5">
                <x-ui.field label="Nombre" required class="max-w-xl">
                    <x-ui.input name="name" :value="old('name', $rule->name)" required placeholder="Ej. Felicitación de cumpleaños" />
                </x-ui.field>

                <x-ui.field label="Disparador" group>
                    <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-4">
                        @foreach ($triggers as $t)
                            <label class="flex cursor-pointer items-start gap-3 rounded-ctl border p-3 transition-colors"
                                   :class="disparador === {{ $t->value }} ? 'border-accent bg-accent-soft' : 'border-line hover:bg-surface2'">
                                <input type="radio" name="trigger" value="{{ $t->value }}" x-model.number="disparador" class="sr-only">
                                <x-icon :name="$t->icon()" :size="20" class="mt-0.5" />
                                <span class="flex flex-col">
                                    <span class="text-sm font-bold">{{ $t->label() }}</span>
                                    <span class="text-xs text-faint">{{ $t->description() }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </x-ui.field>

                @unless ($hasReservations)
                    <p x-show="disparador === {{ RuleTrigger::BeforeReservation->value }}" x-cloak role="note" class="rounded-ctl bg-accent-soft p-3 text-sm">
                        El módulo de reservas todavía no existe: puedes dejar el recordatorio configurado y empezará a enviarse en cuanto haya reservas.
                    </p>
                @endunless

                <div class="flex flex-wrap items-end gap-4">
                    <x-ui.field label="Días" x-show="! [{{ RuleTrigger::Dates->value }}, {{ RuleTrigger::Weekdays->value }}].includes(disparador)" class="w-32">
                        <x-ui.input type="number" name="days" min="0" max="365" :value="old('days', $rule->days)" />
                    </x-ui.field>
                    <x-ui.field label="Antes o después" x-show="disparador === {{ RuleTrigger::Anniversary->value }}" x-cloak class="w-44">
                        <x-ui.select name="timing">
                            <option value="1" @selected((int) old('timing', $rule->timing) === 1)>Antes</option>
                            <option value="2" @selected((int) old('timing', $rule->timing) === 2)>Después</option>
                        </x-ui.select>
                    </x-ui.field>
                    <x-ui.field label="Hora de envío" group>
                        <div class="flex items-center gap-1.5">
                            <x-ui.select name="run_hour" class="w-20" aria-label="Hora">
                                @for ($h = 0; $h < 24; $h++)
                                    <option value="{{ $h }}" @selected((int) old('run_hour', $rule->run_hour) === $h)>{{ sprintf('%02d', $h) }}</option>
                                @endfor
                            </x-ui.select>
                            <span>:</span>
                            <x-ui.select name="run_minute" class="w-20" aria-label="Minuto">
                                @for ($m = 0; $m < 60; $m += 5)
                                    <option value="{{ $m }}" @selected((int) old('run_minute', $rule->run_minute) === $m)>{{ sprintf('%02d', $m) }}</option>
                                @endfor
                            </x-ui.select>
                        </div>
                    </x-ui.field>
                    <x-ui.field label="Tienda" class="w-56">
                        <x-ui.select name="shop_id">
                            <option value="">Todas las tiendas</option>
                            @foreach ($shops as $shop)
                                <option value="{{ $shop->id }}" @selected((int) old('shop_id', $rule->shop_id) === $shop->id)>{{ $shop->name }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>
                    <div x-show="disparador === {{ RuleTrigger::BeforeReservation->value }}" x-cloak class="pb-2">
                        <x-ui.toggle name="mobile_only_flg" :checked="(bool) old('mobile_only_flg', $rule->mobile_only_flg)" label="Solo reservas hechas desde el móvil" />
                    </div>
                </div>

                <div x-show="disparador === {{ RuleTrigger::Dates->value }}" x-cloak class="flex flex-col gap-3">
                    <x-ui.field label="Meses" group>
                        <div class="grid grid-cols-6 gap-1.5 sm:grid-cols-12">
                            @foreach ($meses as $i => $mes)
                                <label class="{{ $chip }}"><input type="checkbox" name="months[]" value="{{ $i + 1 }}" class="sr-only" @checked(in_array($i + 1, $oldMonths, true))>{{ $mes }}</label>
                            @endforeach
                        </div>
                    </x-ui.field>
                    <x-ui.field label="Días del mes" group>
                        <div class="grid grid-cols-8 gap-1.5 sm:grid-cols-16">
                            @for ($d = 1; $d <= 31; $d++)
                                <label class="{{ $chip }}"><input type="checkbox" name="month_days[]" value="{{ $d }}" class="sr-only" @checked(in_array((string) $d, $oldDays, true))>{{ $d }}</label>
                            @endfor
                            <label class="{{ $chip }} col-span-2"><input type="checkbox" name="month_days[]" value="end" class="sr-only" @checked(in_array('end', $oldDays, true))>Fin de mes</label>
                        </div>
                    </x-ui.field>
                </div>

                <x-ui.field label="Días de la semana" group x-show="disparador === {{ RuleTrigger::Weekdays->value }}" x-cloak>
                    <div class="grid max-w-xl grid-cols-7 gap-1.5">
                        @foreach ($dias as $i => $dia)
                            <label class="{{ $chip }}"><input type="checkbox" name="weekdays[]" value="{{ $i }}" class="sr-only" @checked(in_array($i, $oldWeek, true))>{{ $dia }}</label>
                        @endforeach
                    </div>
                </x-ui.field>
            </div>
        </x-ui.section>

        <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-[380px_minmax(0,1fr)]">
            <section aria-labelledby="paso2" class="flex flex-col overflow-hidden rounded-card border border-line bg-surface">
                <div class="border-b border-line px-5 py-4">
                    <h2 id="paso2" class="text-base font-bold">2. A quién, además</h2>
                    <p class="mt-0.5 text-xs text-muted">Filtros opcionales sobre los clientes del disparador. Siempre se excluye a quien no acepta newsletter.</p>
                    @if ($todayCount !== null)
                        <p class="mt-3 rounded-ctl bg-surface2 p-3 text-sm"><strong class="tnum text-xl">{{ $todayCount }}</strong> clientes la recibirían hoy.</p>
                    @endif
                </div>
                <x-customers.search-filters prefix="filters" :filters="old('filters', $rule->filters ?? [])"
                                            :shops="$shops" :groups="$groups" :categories="$categories" :coupons="$coupons" :open="[]" />
            </section>

            <x-ui.section title="3. Mensaje">
                <div class="p-5">
                    <x-promotions.editor :channel="$rule->channel" :subject="$rule->subject" :body="$rule->body"
                                         :channels="$channels" :templates="$templates" :variables="$variables" />
                </div>
                <div class="grid gap-4 border-t border-line p-5 md:grid-cols-2">
                    <x-ui.field label="Regalar cupón" hint="Se entrega con cada mensaje. Usa {enlace_cupon} en el texto.">
                        <x-ui.select name="coupon_id">
                            <option value="">Sin cupón</option>
                            @foreach ($couponList as $c)
                                <option value="{{ $c->id }}" @selected((int) old('coupon_id', $rule->coupon_id) === $c->id)>{{ $c->name }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>
                    <div class="flex items-end pb-2">
                        <x-ui.toggle name="status" :checked="(int) old('status', $rule->status) === 1" label="Activa" />
                    </div>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line bg-surface2/50 px-5 py-3.5">
                    @if ($editing)
                        <x-ui.btn type="submit" variant="danger" icon="trash" form="borrar" onclick="return confirm('¿Eliminar esta automatización?')">Eliminar</x-ui.btn>
                    @else
                        <span></span>
                    @endif
                    <div class="flex gap-2">
                        <x-ui.btn :href="route('admin.promotions.rules.index')">Cancelar</x-ui.btn>
                        <x-ui.btn type="submit" variant="primary">{{ $editing ? 'Guardar cambios' : 'Crear automatización' }}</x-ui.btn>
                    </div>
                </div>
            </x-ui.section>
        </div>
    </form>

    @if ($editing)
        <form id="borrar" method="POST" action="{{ route('admin.promotions.rules.destroy', $rule) }}" class="hidden">@csrf @method('DELETE')</form>
    @endif

</x-layouts.modulo>
