@php
    $notices = old('notices', $setting?->notices ?? []);
@endphp

<x-layouts.modulo module="promo" title="Puntos"
                  :crumbs="[['label' => __('promotions.modulo'), 'route' => 'admin.promotions.index'], ['label' => 'Puntos']]">

    <x-ui.page-header title="Puntos" :description="'Cuánto se acumula, cuándo vence y los premios por alta y por referir. Tienda: ' . ($shop?->name ?? '—')" />
    <x-ui.flash />

    @if (! $shop)
        <x-ui.card><x-ui.empty icon="coin" title="No hay tiendas activas" /></x-ui.card>
    @else
    <section class="grid gap-4 sm:grid-cols-3" aria-label="Cifras">
        <x-ui.stat label="Clientes con puntos" :value="number_format($totals['customers'])" icon="users" />
        <x-ui.stat label="Puntos en circulación" :value="number_format($totals['points'])" icon="coin" />
        <x-ui.stat label="Puntos dados este mes" :value="number_format($totals['month'])" icon="cal" />
    </section>

    <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-[minmax(0,1fr)_380px]">
        <form method="POST" action="{{ route('admin.promotions.points.update') }}" class="flex flex-col gap-5"
              x-data="{ vence: {{ (int) old('expiry_flg', $setting->expiry_flg) }}, avisos: {{ max(1, count($notices)) }} }">
            @csrf @method('PUT')

            <x-ui.section title="Acumulación" description="Puntos por cada compra (importe de la visita).">
                <div class="grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-4">
                    <x-ui.field label="% en efectivo" hint="Ej. 1 = 1 punto por cada 100.">
                        <x-ui.input type="number" step="0.01" min="0" max="100" name="cash_rate" :value="old('cash_rate', $setting->cash_rate)" required />
                    </x-ui.field>
                    <x-ui.field label="% con tarjeta">
                        <x-ui.input type="number" step="0.01" min="0" max="100" name="card_rate" :value="old('card_rate', $setting->card_rate)" required />
                    </x-ui.field>
                    <x-ui.field label="Máximo por uso" hint="Vacío = sin límite.">
                        <x-ui.input type="number" min="1" name="use_limit" :value="old('use_limit', $setting->use_limit)" />
                    </x-ui.field>
                    <div class="flex items-end pb-2">
                        <x-ui.toggle name="include_used_flg" :checked="(bool) old('include_used_flg', $setting->include_used_flg)" label="Lo pagado con puntos también suma" />
                    </div>
                </div>
            </x-ui.section>

            <x-ui.section title="Vencimiento">
                <div class="flex flex-col gap-4 p-5">
                    <x-ui.toggle name="expiry_flg" :checked="(bool) old('expiry_flg', $setting->expiry_flg)" label="Los puntos vencen" x-on:change="vence = $event.target.checked ? 1 : 0" />
                    <div x-show="vence" x-cloak class="flex flex-col gap-4">
                        <div class="flex flex-wrap items-end gap-4">
                            <x-ui.field label="Validez (días)" class="w-36"><x-ui.input type="number" name="expiry_days" min="0" max="999" :value="old('expiry_days', $setting->expiry_days)" /></x-ui.field>
                            <x-ui.field label="Hora del vencimiento" group>
                                <div class="flex items-center gap-1.5">
                                    <x-ui.select name="expiry_hour" class="w-20">@for ($h = 0; $h < 24; $h++)<option value="{{ $h }}" @selected((int) old('expiry_hour', $setting->expiry_hour ?? 3) === $h)>{{ sprintf('%02d', $h) }}</option>@endfor</x-ui.select>
                                    <span>:</span>
                                    <x-ui.select name="expiry_minute" class="w-20">@foreach ([0, 30] as $m)<option value="{{ $m }}" @selected((int) old('expiry_minute', $setting->expiry_minute) === $m)>{{ sprintf('%02d', $m) }}</option>@endforeach</x-ui.select>
                                </div>
                            </x-ui.field>
                            <x-ui.field label="Renueva la validez" class="w-80">
                                <x-ui.select name="extend_mode">
                                    @foreach ($extendModes as $mode)
                                        <option value="{{ $mode->value }}" @selected((int) old('extend_mode', $setting->extend_mode?->value) === $mode->value)>{{ $mode->label() }}</option>
                                    @endforeach
                                </x-ui.select>
                            </x-ui.field>
                        </div>

                        <x-ui.field label="Avisos antes de vencer" group>
                            <div class="flex flex-col gap-2">
                                @for ($i = 0; $i < 5; $i++)
                                    <div class="flex flex-wrap items-center gap-2 text-sm" x-show="avisos > {{ $i }}" @if ($i >= max(1, count($notices))) x-cloak @endif>
                                        <x-ui.input type="number" :name="'notices[' . $i . '][days]'" min="1" class="w-20" :value="$notices[$i]['days'] ?? ''" :aria-label="'Días antes, aviso ' . ($i + 1)" />
                                        <span>días antes, a las</span>
                                        <x-ui.select :name="'notices[' . $i . '][hour]'" class="w-20">@for ($h = 0; $h < 24; $h++)<option value="{{ $h }}" @selected((int) ($notices[$i]['hour'] ?? 10) === $h)>{{ sprintf('%02d', $h) }}</option>@endfor</x-ui.select>
                                        <span>:</span>
                                        <x-ui.select :name="'notices[' . $i . '][minute]'" class="w-20">@foreach ([0, 30] as $m)<option value="{{ $m }}" @selected((int) ($notices[$i]['minute'] ?? 0) === $m)>{{ sprintf('%02d', $m) }}</option>@endforeach</x-ui.select>
                                    </div>
                                @endfor
                                <button type="button" x-show="avisos < 5" @click="avisos++" class="self-start text-sm font-bold text-accent-fg hover:underline">+ Añadir aviso</button>
                            </div>
                        </x-ui.field>
                    </div>
                </div>
            </x-ui.section>

            <x-ui.section title="Alta y referidos">
                <div class="grid gap-4 p-5 md:grid-cols-3">
                    <x-ui.field label="Puntos de alta"><x-ui.input type="number" min="0" name="signup_points" :value="old('signup_points', $setting->signup_points)" required /></x-ui.field>
                    <x-ui.field label="Se entregan">
                        <x-ui.select name="signup_timing">
                            @foreach ($timings as $t)
                                <option value="{{ $t->value }}" @selected((int) old('signup_timing', $setting->signup_timing?->value) === $t->value)>{{ $t->label() }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>
                    <x-ui.field label="Comentario del alta"><x-ui.input name="signup_comment" :value="old('signup_comment', $setting->signup_comment)" /></x-ui.field>
                    <x-ui.field label="Para quien refiere"><x-ui.input type="number" min="0" name="referral_points" :value="old('referral_points', $setting->referral_points)" required /></x-ui.field>
                    <x-ui.field label="Máximo de referidos con premio" hint="Vacío = sin límite."><x-ui.input type="number" min="1" name="referral_limit" :value="old('referral_limit', $setting->referral_limit)" /></x-ui.field>
                    <x-ui.field label="Para el referido"><x-ui.input type="number" min="0" name="referee_points" :value="old('referee_points', $setting->referee_points)" required /></x-ui.field>
                    <x-ui.field label="Comentario de referidos" class="md:col-span-3"><x-ui.input name="referral_comment" :value="old('referral_comment', $setting->referral_comment)" /></x-ui.field>
                </div>
            </x-ui.section>

            @if ($groups->isNotEmpty())
                <x-ui.section title="Por grupo de cliente" description="Un grupo puede tener su propio % o no usar puntos.">
                    <div class="relative overflow-x-auto">
                        <table class="w-full min-w-120 text-sm">
                            <thead><tr class="bg-surface2 text-left text-xs font-bold text-muted">
                                <th scope="col" class="px-5 py-2.5">Grupo</th><th scope="col" class="px-3 py-2.5">Usa puntos</th><th scope="col" class="px-3 py-2.5">Ve sus puntos</th><th scope="col" class="px-5 py-2.5">% propio</th>
                            </tr></thead>
                            <tbody>
                                @foreach ($groups as $g)
                                    @php $r = $groupRules->get($g->id); @endphp
                                    <tr class="border-t border-line">
                                        <th scope="row" class="px-5 py-2.5 text-left font-bold">{{ $g->name }}</th>
                                        <td class="px-3 py-2.5"><x-ui.toggle :name="'groups[' . $g->id . '][use_flg]'" :checked="$r ? (bool) $r->use_flg : true" label="" /></td>
                                        <td class="px-3 py-2.5"><x-ui.toggle :name="'groups[' . $g->id . '][display_flg]'" :checked="$r ? (bool) $r->display_flg : true" label="" /></td>
                                        <td class="px-5 py-2.5"><x-ui.input type="number" step="0.01" min="0" max="100" :name="'groups[' . $g->id . '][rate]'" :value="$r?->rate" placeholder="De la tienda" class="w-32" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-ui.section>
            @endif

            <div class="flex justify-end"><x-ui.btn type="submit" variant="primary">Guardar reglas de puntos</x-ui.btn></div>
        </form>

        <div class="flex flex-col gap-5">
            <x-ui.section title="Ajuste o uso en caja">
                <form method="POST" action="{{ route('admin.promotions.points.adjust') }}" class="flex flex-col gap-3 p-5">
                    @csrf
                    <div class="grid grid-cols-2 gap-3">
                        <x-ui.field label="Nº de socio"><x-ui.input name="code" required inputmode="numeric" :value="old('code')" /></x-ui.field>
                        <x-ui.field label="Puntos"><x-ui.input type="number" name="points" required :value="old('points')" /></x-ui.field>
                    </div>
                    <x-ui.field label="Tipo">
                        <x-ui.select name="type">
                            <option value="{{ \App\Enums\PointMovement::Use->value }}">Uso en caja (resta)</option>
                            <option value="{{ \App\Enums\PointMovement::Adjustment->value }}">Ajuste manual (+ o −)</option>
                        </x-ui.select>
                    </x-ui.field>
                    <x-ui.field label="Motivo"><x-ui.input name="note" /></x-ui.field>
                    <x-ui.btn type="submit">Aplicar</x-ui.btn>
                </form>
            </x-ui.section>

            <x-ui.section title="Últimos movimientos">
                <ul class="divide-y divide-line text-sm">
                    @forelse ($recent as $m)
                        <li class="flex items-center gap-3 px-5 py-2.5">
                            <span class="tnum w-16 text-right font-bold {{ $m->points > 0 ? 'text-accent-fg' : 'text-muted' }}">{{ $m->points > 0 ? '+' : '' }}{{ number_format($m->points) }}</span>
                            <span class="min-w-0 flex-1 truncate">{{ $m->customer?->greetingName() }} · {{ $m->type->label() }}</span>
                            <span class="tnum text-xs text-faint">{{ $m->created_at->format('d/m H:i') }}</span>
                        </li>
                    @empty
                        <li class="px-5 py-4 text-faint">Sin movimientos.</li>
                    @endforelse
                </ul>
            </x-ui.section>
        </div>
    </div>
    @endif

</x-layouts.modulo>
