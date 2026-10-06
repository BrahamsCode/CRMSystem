@php
    $editing = $message->exists;
    $titulo = $editing ? 'Editar envío' : 'Nuevo envío';
    $cuando = old('scheduled_at', $message->scheduled_at?->format('Y-m-d\TH:i'));
@endphp

<x-layouts.modulo module="promo" :title="$titulo"
                  :crumbs="[['label' => __('promotions.modulo'), 'route' => 'admin.promotions.index'], ['label' => 'Envíos', 'route' => 'admin.promotions.messages.index'], ['label' => $titulo]]">

    <x-ui.page-header :title="$titulo" description="Elige a quién, escribe el mensaje y decide cuándo sale.">
        @if ($editing)
            <x-slot:actions>
                <x-ui.badge :tone="$message->delivery_status->tone()" class="h-9 px-3.5">{{ $message->delivery_status->label() }}</x-ui.badge>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    <x-ui.flash />

    <form method="POST" action="{{ $editing ? route('admin.promotions.messages.update', $message) : route('admin.promotions.messages.store') }}"
          class="flex flex-col gap-5"
          x-data="{
              cuenta: @js($preview),
              resumen: [],
              cargando: false,
              cuando: @js($cuando ? 'schedule' : 'now'),
              temporizador: null,
              recontar() {
                  clearTimeout(this.temporizador);
                  this.temporizador = setTimeout(async () => {
                      this.cargando = true;
                      const datos = new FormData(this.$root);
                      datos.delete('_method');
                      try {
                          const r = await fetch(@js(route('admin.promotions.messages.audience')), { method: 'POST', body: datos, headers: { 'Accept': 'application/json' } });
                          if (r.ok) { const j = await r.json(); this.cuenta = j; this.resumen = j.summary; }
                      } finally { this.cargando = false; }
                  }, 400);
              },
              canal: {{ (int) old('channel', $message->channel?->value ?? 1) }},
              alcanzables() { return this.canal === 3 ? this.cuenta.push : this.cuenta.email },
          }"
          @change="if ($event.target.name === 'channel') canal = Number($event.target.value); if ($event.target.closest('[data-audiencia]')) recontar()"
          @input.debounce.600ms="if ($event.target.closest('[data-audiencia]')) recontar()">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-[380px_minmax(0,1fr)]">

            {{-- 1. Destinatarios --}}
            <section aria-labelledby="paso1" data-audiencia
                     class="flex flex-col overflow-hidden rounded-card border border-line bg-surface xl:sticky xl:top-20 xl:max-h-[calc(100vh-6rem)]">
                <div class="border-b border-line px-5 py-4">
                    <h2 id="paso1" class="text-base font-bold">1. Destinatarios</h2>
                    <p class="mt-0.5 text-xs text-muted">Los mismos filtros de «Buscar clientes».</p>

                    <div class="mt-3 rounded-ctl bg-surface2 p-3" aria-live="polite">
                        <div class="flex items-baseline gap-2">
                            <span class="tnum text-3xl font-extrabold" x-text="alcanzables().toLocaleString()">{{ $preview['email'] }}</span>
                            <span class="text-sm text-muted">pueden recibirlo</span>
                            <span x-show="cargando" x-cloak class="ml-auto text-xs text-faint">Contando…</span>
                        </div>
                        <p class="mt-1 text-xs text-faint">
                            De <span class="tnum" x-text="cuenta.total.toLocaleString()">{{ $preview['total'] }}</span> clientes que cumplen los filtros:
                            <span class="tnum" x-text="cuenta.email.toLocaleString()"></span> con email y newsletter activa,
                            <span class="tnum" x-text="cuenta.push.toLocaleString()"></span> con la app.
                        </p>
                    </div>

                    <x-ui.field label="Tienda que publica" class="mt-3"
                                hint="Con una tienda elegida, solo se envía a los clientes registrados en ella.">
                        <x-ui.select name="shop_id">
                            <option value="">Todas las tiendas</option>
                            @foreach ($shops as $shop)
                                <option value="{{ $shop->id }}" @selected((int) old('shop_id', $message->shop_id) === $shop->id)>{{ $shop->name }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto">
                    <x-customers.search-filters prefix="filters" :filters="old('filters', $message->filters ?? [])"
                                                :shops="$shops" :groups="$groups" :categories="$categories" :coupons="$coupons" :open="[]" />
                </div>
            </section>

            <div class="flex min-w-0 flex-col gap-5">
                {{-- 2. Contenido --}}
                <x-ui.section title="2. Contenido">
                    <div class="p-5">
                        <x-promotions.editor :channel="$message->channel" :subject="$message->subject" :body="$message->body"
                                             :channels="$channels" :templates="$templates" :variables="$variables" />
                    </div>

                    <div class="grid gap-4 border-t border-line p-5 md:grid-cols-3">
                        <x-ui.field label="Adjuntar cupón" hint="Se entrega a cada destinatario. Usa {enlace_cupon} en el texto.">
                            <x-ui.select name="coupon_id">
                                <option value="">Sin cupón</option>
                                @foreach ($couponList as $c)
                                    <option value="{{ $c->id }}" @selected((int) old('coupon_id', $message->coupon_id) === $c->id)>{{ $c->name }} · {{ $c->usage_type->label() }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>
                        <x-ui.field label="Enlazar encuesta" hint="Usa {encuesta} en el texto.">
                            <x-ui.select name="survey_id">
                                <option value="">Sin encuesta</option>
                                @foreach ($surveys as $s)
                                    <option value="{{ $s->id }}" @selected((int) old('survey_id', $message->survey_id) === $s->id)>{{ $s->name }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>
                        <x-ui.field label="Tienda del pie del mensaje" hint="Su nombre, dirección y teléfono al final del email.">
                            <x-ui.select name="footer_shop_id">
                                <option value="">Sin pie</option>
                                @foreach ($shops as $shop)
                                    <option value="{{ $shop->id }}" @selected((int) old('footer_shop_id', $message->footer_shop_id) === $shop->id)>{{ $shop->name }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>
                        <x-ui.field label="Nota interna" class="md:col-span-3">
                            <x-ui.input name="note" :value="old('note', $message->note)" placeholder="Solo la ve el equipo" />
                        </x-ui.field>
                    </div>
                </x-ui.section>

                {{-- 3. Envío --}}
                <x-ui.section title="3. Envío">
                    <div class="flex flex-col gap-4 p-5">
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach (['now' => ['Enviar ahora', 'Sale en cuanto confirmes.', 'send'], 'schedule' => ['Programar', 'Elige el día y la hora.', 'clock']] as $value => [$label, $sub, $icon])
                                <label class="flex cursor-pointer items-start gap-3 rounded-ctl border p-3 transition-colors"
                                       :class="cuando === '{{ $value }}' ? 'border-accent bg-accent-soft' : 'border-line hover:bg-surface2'">
                                    <input type="radio" x-model="cuando" value="{{ $value }}" class="sr-only">
                                    <x-icon :name="$icon" :size="20" class="mt-0.5" />
                                    <span class="flex flex-col"><span class="text-sm font-bold">{{ $label }}</span><span class="text-xs text-faint">{{ $sub }}</span></span>
                                </label>
                            @endforeach
                        </div>

                        <x-ui.field label="Fecha y hora" x-show="cuando === 'schedule'" x-cloak class="max-w-xs">
                            <x-ui.input type="datetime-local" name="scheduled_at" :value="$cuando" :min="now()->addMinutes(5)->format('Y-m-d\TH:i')" />
                        </x-ui.field>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line bg-surface2/50 px-5 py-3.5">
                        <div class="flex flex-wrap gap-2">
                            <x-ui.btn type="submit" name="action" value="draft">Guardar borrador</x-ui.btn>
                            <x-ui.btn type="submit" name="action" value="test" icon="flask">Enviar prueba</x-ui.btn>
                            @if ($editing)
                                <x-ui.btn type="submit" variant="danger" icon="trash" form="borrar"
                                          onclick="return confirm('¿Eliminar este envío?')">Eliminar</x-ui.btn>
                            @endif
                        </div>
                        <div class="flex gap-2">
                            <x-ui.btn type="submit" name="action" value="schedule" variant="primary" icon="clock" x-show="cuando === 'schedule'" x-cloak>Programar envío</x-ui.btn>
                            <x-ui.btn type="submit" name="action" value="now" variant="primary" icon="send" x-show="cuando === 'now'"
                                      x-on:click="if (! confirm('¿Enviar ahora a ' + alcanzables().toLocaleString() + ' clientes?')) $event.preventDefault()">
                                Enviar ahora
                            </x-ui.btn>
                        </div>
                    </div>
                </x-ui.section>
            </div>
        </div>
    </form>

    @if ($editing)
        <form id="borrar" method="POST" action="{{ route('admin.promotions.messages.destroy', $message) }}" class="hidden">
            @csrf @method('DELETE')
        </form>
    @endif

</x-layouts.modulo>
