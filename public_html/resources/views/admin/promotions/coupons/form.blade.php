@php
    $editing = $coupon->exists;
    $titulo = $editing ? 'Editar cupón' : 'Nuevo cupón';
    $notes = old('notes', $coupon->notes ?? []);
@endphp

<x-layouts.modulo module="promo" :title="$titulo"
                  :crumbs="[['label' => __('promotions.modulo'), 'route' => 'admin.promotions.index'], ['label' => 'Cupones', 'route' => 'admin.promotions.coupons.index'], ['label' => $titulo]]">

    <div class="flex max-w-[1000px] flex-col gap-5">
        <x-ui.page-header :title="$titulo" />
        <x-ui.flash />

        <form method="POST" action="{{ $editing ? route('admin.promotions.coupons.update', $coupon) : route('admin.promotions.coupons.store') }}"
              x-data="{
                  uso: {{ (int) old('usage_type', $coupon->usage_type?->value) }},
                  validez: {{ (int) old('validity_type', $coupon->validity_type?->value) }},
                  tipo: {{ (int) old('type', $coupon->type?->value) }},
                  conCosto() { return [2, 6].includes(this.uso) },
              }" class="flex flex-col gap-5">
            @csrf
            @if ($editing) @method('PUT') @endif

            <x-ui.section title="Cupón">
                <div class="grid gap-4 p-5 md:grid-cols-2">
                    <x-ui.field label="Título" required class="md:col-span-2">
                        <x-ui.input name="name" :value="old('name', $coupon->name)" required placeholder="Ej. 10 % en tu próxima visita" />
                    </x-ui.field>
                    <x-ui.field label="Contenido" hint="Lo que ve el cliente en el cupón." class="md:col-span-2">
                        <x-ui.textarea name="description" :rows="3">{{ old('description', $coupon->description) }}</x-ui.textarea>
                    </x-ui.field>

                    <x-ui.field label="Descuento" required group>
                        <div class="flex gap-2">
                            <x-ui.select name="type" x-model.number="tipo" class="w-40">
                                @foreach ($types as $t)
                                    <option value="{{ $t->value }}">{{ $t->label() }}</option>
                                @endforeach
                            </x-ui.select>
                            <div class="relative flex-1">
                                <x-ui.input type="number" name="value" min="1" :value="old('value', $coupon->value)" required class="pr-10" />
                                <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-faint"
                                      x-text="tipo === 1 ? '%' : @js(config('crm.currency.symbol'))"></span>
                            </div>
                        </div>
                    </x-ui.field>
                    <x-ui.field label="Tienda">
                        <x-ui.select name="shop_id">
                            <option value="">Todas las tiendas</option>
                            @foreach ($shops as $shop)
                                <option value="{{ $shop->id }}" @selected((int) old('shop_id', $coupon->shop_id) === $shop->id)>{{ $shop->name }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>
                </div>
            </x-ui.section>

            <x-ui.section title="Cómo llega al cliente">
                <div class="flex flex-col gap-4 p-5">
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($usages as $u)
                            <label class="flex cursor-pointer flex-col gap-0.5 rounded-ctl border p-3 transition-colors"
                                   :class="uso === {{ $u->value }} ? 'border-accent bg-accent-soft' : 'border-line hover:bg-surface2'">
                                <input type="radio" name="usage_type" value="{{ $u->value }}" x-model.number="uso" class="sr-only">
                                <span class="text-sm font-bold">{{ $u->label() }}</span>
                                <span class="text-xs text-faint">
                                    @switch($u)
                                        @case(\App\Enums\CouponUsage::Newsletter) Se adjunta a un envío o automatización @break
                                        @case(\App\Enums\CouponUsage::StampExchange) El cliente lo canjea con sellos @break
                                        @case(\App\Enums\CouponUsage::SurveyReward) Premio al responder una encuesta @break
                                        @case(\App\Enums\CouponUsage::Signup) Se entrega solo al darse de alta @break
                                        @case(\App\Enums\CouponUsage::Referral) Para quien trae a un amigo @break
                                        @default El cliente lo canjea con puntos
                                    @endswitch
                                </span>
                            </label>
                        @endforeach
                    </div>

                    <x-ui.field x-show="conCosto()" x-cloak label="Costo del canje" class="max-w-xs">
                        <div class="relative">
                            <x-ui.input type="number" name="cost" min="1" :value="old('cost', $coupon->cost ?: null)" class="pr-20" />
                            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-faint" x-text="uso === 2 ? 'sellos' : 'puntos'"></span>
                        </div>
                    </x-ui.field>

                    <div class="grid gap-4 md:grid-cols-3">
                        <x-ui.field label="Vigencia" required>
                            <x-ui.select name="validity_type" x-model.number="validez">
                                @foreach ($validities as $v)
                                    <option value="{{ $v->value }}">{{ $v->label() }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>
                        <x-ui.field label="Días" x-show="validez === 4" x-cloak>
                            <x-ui.input type="number" name="validity_days" min="1" :value="old('validity_days', $coupon->validity_days)" />
                        </x-ui.field>
                        <x-ui.field label="Válido hasta" x-show="validez === 5" x-cloak>
                            <x-ui.input type="date" name="valid_until" :value="old('valid_until', $coupon->valid_until?->toDateString())" />
                        </x-ui.field>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <x-ui.field label="Premio del sorteo (ガチャ)" hint="Para los sorteos de Mi página.">
                            <x-ui.select name="lottery_rank">
                                <option value="">No participa</option>
                                @foreach ($ranks as $r)
                                    <option value="{{ $r->value }}" @selected((int) old('lottery_rank', $coupon->lottery_rank?->value) === $r->value)>{{ $r->label() }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>
                        <div class="flex flex-col justify-end gap-3 pb-1 md:col-span-2">
                            <x-ui.toggle name="reissue_flg" :checked="(bool) old('reissue_flg', $coupon->reissue_flg)" label="Se puede entregar más de una vez al mismo cliente" />
                            <x-ui.toggle name="display_flg" :checked="(bool) old('display_flg', $coupon->display_flg)" label="Visible en Mi página" />
                            <x-ui.toggle name="status" :checked="(int) old('status', $coupon->status ?? 1) === 1" label="Activo" />
                        </div>
                    </div>
                </div>
            </x-ui.section>

            <x-ui.section title="Condiciones de uso" description="Hasta 5 avisos que se muestran en el cupón (注意事項).">
                <div class="grid gap-3 p-5">
                    @for ($i = 0; $i < 5; $i++)
                        <x-ui.input :name="'notes[' . $i . ']'" :value="$notes[$i] ?? ''" :aria-label="'Condición ' . ($i + 1)"
                                    :placeholder="$i === 0 ? 'Ej. No acumulable con otras promociones' : ''" />
                    @endfor
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-5 py-3.5">
                    @if ($editing)
                        <x-ui.btn type="submit" variant="danger" icon="trash" form="borrar" onclick="return confirm('¿Eliminar el cupón? Los ya entregados siguen siendo válidos.')">Eliminar</x-ui.btn>
                    @else <span></span> @endif
                    <div class="flex gap-2">
                        <x-ui.btn :href="$editing ? route('admin.promotions.coupons.show', $coupon) : route('admin.promotions.coupons.index')">Cancelar</x-ui.btn>
                        <x-ui.btn type="submit" variant="primary">{{ $editing ? 'Guardar cambios' : 'Crear cupón' }}</x-ui.btn>
                    </div>
                </div>
            </x-ui.section>
        </form>

        @if ($editing)
            <form id="borrar" method="POST" action="{{ route('admin.promotions.coupons.destroy', $coupon) }}" class="hidden">@csrf @method('DELETE')</form>
        @endif
    </div>

</x-layouts.modulo>
