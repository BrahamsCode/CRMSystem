@props([
    // Filtros actuales (mismo formato que CustomerSearch)
    'filters' => [],
    'shops',
    'groups',
    // Categorías de información adicional con search_flg = 1 y sus campos
    'categories',
    'coupons' => collect(),
    // Prefijo de los nombres: '' en Buscar clientes, 'filters' dentro del formulario de un envío
    'prefix' => '',
    // Bloques abiertos al cargar
    'open' => ['cliente'],
])

@php
    use App\Enums\AddressType;
    use App\Enums\MailMagazine;
    use App\Enums\Occupation;
    use App\Enums\Sex;
    use App\Services\Customers\CustomerSearch;

    $f = CustomerSearch::clean($filters);
    // name="shop_ids[]" → "filters[shop_ids][]"; name="custom[a][b]" → "filters[custom][a][b]"
    $name = function (string $key) use ($prefix) {
        if ($prefix === '') {
            return $key;
        }
        $first = strtok($key, '[');
        $rest = substr($key, strlen($first));

        return "{$prefix}[{$first}]{$rest}";
    };
    $v = fn (string $key) => $f[$key] ?? '';
    $count = fn (array $keys) => count(array_intersect_key($f, array_flip($keys)));

    $bloques = [
        'cliente' => $count(['q', 'code', 'tel', 'name', 'mail', 'shop_ids', 'birth_month', 'birth_day', 'sex', 'age_min', 'age_max', 'joined_from', 'joined_to', 'mail_magazine', 'occupation', 'address_type', 'bounce_min', 'bounce_max', 'group_ids', 'has_terminal', 'coupon_id']),
        'familia' => $count(['spouse', 'wedding_from', 'wedding_to']),
        'ubicacion' => $count(['near_shop_id']),
        'visitas' => $count(['visit_period', 'visit_shop_id', 'visit_day', 'visit_dow', 'visits_min', 'visits_max', 'last_visit_days', 'cycle_min', 'cycle_max', 'next_visit_from', 'next_visit_to']),
        'ventas' => $count(['sale_shop_id', 'sale_from', 'sale_to', 'avg_amount_min', 'avg_amount_max']),
    ];
    foreach ($categories as $category) {
        $bloques['custom_' . $category->slug] = count($f['custom'][$category->slug] ?? []);
    }
    // Se abren los bloques pedidos y los que ya tienen filtros
    $abiertos = collect($bloques)->mapWithKeys(fn ($c, $k) => [$k => in_array($k, $open, true) || $c > 0])->all();

    $dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    $range = 'flex items-center gap-1.5 text-faint';
@endphp

<div {{ $attributes->class('flex flex-col') }}
     x-data="{ abierto: @js($abiertos), logica: @js($f['logic'] ?? 'and'), negar: @js($f['negate'] ?? '0') }">

    <div class="flex flex-col gap-3.5 px-5 pt-1 pb-4">
        <x-ui.field>
            <span class="sr-only">Búsqueda rápida</span>
            <x-ui.input type="search" :name="$name('q')" :value="$v('q')" placeholder="Nombre, nº, teléfono o email" />
        </x-ui.field>

        <div class="grid grid-cols-2 gap-3">
            <x-ui.field label="Cumplen los filtros" group>
                <input type="hidden" name="{{ $name('logic') }}" :value="logica">
                <div class="flex gap-1">
                    @foreach (['and' => 'Todos', 'or' => 'Alguno'] as $value => $texto)
                        <button type="button" @click="logica = '{{ $value }}'" :aria-pressed="logica === '{{ $value }}'"
                                class="flex-1 rounded-ctl border px-2 py-2 text-xs font-bold transition-colors"
                                :class="logica === '{{ $value }}' ? 'border-accent bg-accent text-on-accent' : 'border-line bg-surface text-ink hover:bg-surface2'">
                            {{ $texto }}
                        </button>
                    @endforeach
                </div>
            </x-ui.field>
            <x-ui.field label="Resultado" group>
                <input type="hidden" name="{{ $name('negate') }}" :value="negar">
                <div class="flex gap-1">
                    @foreach (['0' => 'Coinciden', '1' => 'Excluirlos'] as $value => $texto)
                        <button type="button" @click="negar = '{{ $value }}'" :aria-pressed="negar === '{{ $value }}'"
                                class="flex-1 rounded-ctl border px-2 py-2 text-xs font-bold transition-colors"
                                :class="negar === '{{ $value }}' ? 'border-accent bg-accent text-on-accent' : 'border-line bg-surface text-ink hover:bg-surface2'">
                            {{ $texto }}
                        </button>
                    @endforeach
                </div>
            </x-ui.field>
        </div>

        <x-ui.field label="Estado de registro" hint="Siempre se aplica tal cual, sin importar la lógica ni el resultado.">
            <x-ui.select :name="$name('status')">
                @foreach (['1' => 'Registrado', '0' => 'Dado de baja', 'deleted' => 'Eliminado', 'all' => 'Todos'] as $value => $texto)
                    <option value="{{ $value }}" @selected(($f['status'] ?? '1') === (string) $value)>{{ $texto }}</option>
                @endforeach
            </x-ui.select>
        </x-ui.field>
    </div>

    {{-- Datos del cliente --}}
    <x-customers.accordion key="cliente" title="Datos del cliente" sub="Perfil, contacto, registro y edad" :count="$bloques['cliente'] ?: null">
        <div class="grid grid-cols-2 gap-3">
            <x-ui.field label="Nº de socio">
                <x-ui.input :name="$name('code')" :value="$v('code')" inputmode="numeric" placeholder="Ej. 1100028" />
            </x-ui.field>
            <x-ui.field label="Teléfono">
                <x-ui.input type="tel" :name="$name('tel')" :value="$v('tel')" />
            </x-ui.field>
        </div>

        <x-ui.field label="Nombre" hint="Separa apellido y nombre con un espacio para buscar el nombre completo.">
            <x-ui.input :name="$name('name')" :value="$v('name')" placeholder="Apellido Nombre" />
        </x-ui.field>

        <x-ui.field label="Email">
            <x-ui.input :name="$name('mail')" :value="$v('mail')" />
        </x-ui.field>

        @if ($shops->count() > 1)
            <x-ui.field label="Tienda de registro" group>
                <div class="flex flex-wrap gap-2">
                    @foreach ($shops as $shop)
                        <label class="inline-flex min-h-9.5 cursor-pointer items-center gap-2 rounded-ctl border border-line px-2.5 text-sm font-semibold has-checked:border-accent has-checked:bg-accent-soft">
                            <input type="checkbox" name="{{ $name('shop_ids[]') }}" value="{{ $shop->id }}"
                                   @checked(in_array($shop->id, array_map('intval', $f['shop_ids'] ?? []), true))
                                   class="accent-[var(--crm-accent)]">
                            {{ $shop->name }}
                        </label>
                    @endforeach
                </div>
            </x-ui.field>
        @endif

        <x-ui.field label="Cumpleaños (mes / día)" group>
            <div class="grid grid-cols-2 gap-3">
                <x-ui.select :name="$name('birth_month')" aria-label="Mes de cumpleaños">
                    <option value="">Mes</option>
                    @for ($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" @selected((int) $v('birth_month') === $m)>{{ ucfirst(\Illuminate\Support\Carbon::create(null, $m, 1)->translatedFormat('F')) }}</option>
                    @endfor
                </x-ui.select>
                <x-ui.select :name="$name('birth_day')" aria-label="Día de cumpleaños">
                    <option value="">Día</option>
                    @for ($d = 1; $d <= 31; $d++)
                        <option value="{{ $d }}" @selected((int) $v('birth_day') === $d)>{{ $d }}</option>
                    @endfor
                </x-ui.select>
            </div>
        </x-ui.field>

        <div class="grid grid-cols-2 gap-3">
            <x-ui.field label="Sexo">
                <x-ui.select :name="$name('sex')">
                    <option value="">Cualquiera</option>
                    @foreach (Sex::cases() as $sex)
                        <option value="{{ $sex->value }}" @selected($v('sex') !== '' && (int) $v('sex') === $sex->value)>{{ $sex->label() }}</option>
                    @endforeach
                    <option value="0" @selected($v('sex') !== '' && (int) $v('sex') === 0)>Sin especificar</option>
                </x-ui.select>
            </x-ui.field>
            <x-ui.field label="Edad" group>
                <div class="{{ $range }}">
                    <x-ui.input type="number" min="0" :name="$name('age_min')" :value="$v('age_min')" aria-label="Edad mínima" placeholder="De" />
                    <span>–</span>
                    <x-ui.input type="number" min="0" :name="$name('age_max')" :value="$v('age_max')" aria-label="Edad máxima" placeholder="A" />
                </div>
            </x-ui.field>
        </div>

        <x-ui.field label="Fecha de alta" group>
            <div class="{{ $range }}">
                <x-ui.input type="date" :name="$name('joined_from')" :value="$v('joined_from')" aria-label="Alta desde" />
                <span>–</span>
                <x-ui.input type="date" :name="$name('joined_to')" :value="$v('joined_to')" aria-label="Alta hasta" />
            </div>
        </x-ui.field>

        <div class="grid grid-cols-2 gap-3">
            <x-ui.field label="Newsletter">
                <x-ui.select :name="$name('mail_magazine')">
                    <option value="">Cualquiera</option>
                    @foreach (MailMagazine::cases() as $m)
                        <option value="{{ $m->value }}" @selected((int) $v('mail_magazine') === $m->value)>{{ $m->label() }}</option>
                    @endforeach
                </x-ui.select>
            </x-ui.field>
            <x-ui.field label="Tipo de dirección">
                <x-ui.select :name="$name('address_type')">
                    <option value="">Cualquiera</option>
                    @foreach (AddressType::cases() as $t)
                        <option value="{{ $t->value }}" @selected((int) $v('address_type') === $t->value)>{{ $t->label() }}</option>
                    @endforeach
                </x-ui.select>
            </x-ui.field>
            <x-ui.field label="Ocupación">
                <x-ui.select :name="$name('occupation')">
                    <option value="">Cualquiera</option>
                    @foreach (Occupation::cases() as $o)
                        <option value="{{ $o->value }}" @selected((int) $v('occupation') === $o->value)>{{ $o->label() }}</option>
                    @endforeach
                </x-ui.select>
            </x-ui.field>
            <x-ui.field label="Terminal">
                <x-ui.select :name="$name('has_terminal')">
                    <option value="">Cualquiera</option>
                    <option value="1" @selected($v('has_terminal') === '1')>Con terminal</option>
                    <option value="0" @selected($v('has_terminal') === '0')>Sin terminal</option>
                </x-ui.select>
            </x-ui.field>
        </div>

        @if ($groups->isNotEmpty())
            <x-ui.field label="Grupo de cliente" group>
                <div class="flex flex-wrap gap-2">
                    @foreach ($groups as $group)
                        <label class="inline-flex min-h-9.5 cursor-pointer items-center gap-2 rounded-ctl border border-line px-2.5 text-sm font-semibold has-checked:border-accent has-checked:bg-accent-soft">
                            <input type="checkbox" name="{{ $name('group_ids[]') }}" value="{{ $group->id }}"
                                   @checked(in_array($group->id, array_map('intval', $f['group_ids'] ?? []), true))
                                   class="accent-[var(--crm-accent)]">
                            {{ $group->name }}
                        </label>
                    @endforeach
                </div>
            </x-ui.field>
        @endif

        <x-ui.field label="Correos no entregados" group>
            <div class="{{ $range }}">
                <x-ui.input type="number" min="0" :name="$name('bounce_min')" :value="$v('bounce_min')" aria-label="Mínimo" placeholder="Mín." />
                <span>–</span>
                <x-ui.input type="number" min="0" :name="$name('bounce_max')" :value="$v('bounce_max')" aria-label="Máximo" placeholder="Máx." />
            </div>
        </x-ui.field>

        @if ($coupons->isNotEmpty())
            <x-ui.field label="Recibió el cupón">
                <x-ui.select :name="$name('coupon_id')">
                    <option value="">Cualquiera</option>
                    @foreach ($coupons as $coupon)
                        <option value="{{ $coupon->id }}" @selected((int) $v('coupon_id') === $coupon->id)>{{ $coupon->name }}</option>
                    @endforeach
                </x-ui.select>
            </x-ui.field>
        @endif
    </x-customers.accordion>

    {{-- Información familiar: columnas propias de customers --}}
    <x-customers.accordion key="familia" title="Información familiar" sub="Cónyuge y aniversario de boda" :count="$bloques['familia'] ?: null">
        <x-ui.field label="Cónyuge">
            <x-ui.select :name="$name('spouse')">
                <option value="">Cualquiera</option>
                <option value="1" @selected($v('spouse') === '1')>Sí</option>
                <option value="0" @selected($v('spouse') === '0')>No</option>
            </x-ui.select>
        </x-ui.field>
        <x-ui.field label="Aniversario de boda" group>
            <div class="{{ $range }}">
                <x-ui.input type="date" :name="$name('wedding_from')" :value="$v('wedding_from')" aria-label="Desde" />
                <span>–</span>
                <x-ui.input type="date" :name="$name('wedding_to')" :value="$v('wedding_to')" aria-label="Hasta" />
            </div>
        </x-ui.field>
    </x-customers.accordion>

    {{-- Información adicional --}}
    @foreach ($categories as $category)
        <x-customers.accordion :key="'custom_' . $category->slug" :title="$category->name" sub="Información adicional"
                               :count="$bloques['custom_' . $category->slug] ?: null">
            <div class="grid grid-cols-2 gap-3">
                @foreach ($category->fields as $field)
                    @php
                        $fieldName = $name('custom[' . $category->slug . '][' . $field->slug . ']');
                        $current = $f['custom'][$category->slug][$field->slug] ?? '';
                    @endphp
                    <x-ui.field :label="$field->name">
                        @if ($field->type->hasOptions() && $field->options->isNotEmpty())
                            <x-ui.select :name="$fieldName">
                                <option value="">Cualquiera</option>
                                @foreach ($field->options as $option)
                                    <option value="{{ $option->value }}" @selected($current === $option->value)>{{ $option->label }}</option>
                                @endforeach
                            </x-ui.select>
                        @else
                            <x-ui.input :name="$fieldName" :value="is_array($current) ? '' : $current" />
                        @endif
                    </x-ui.field>
                @endforeach
            </div>
        </x-customers.accordion>
    @endforeach

    {{-- Ubicación --}}
    <x-customers.accordion key="ubicacion" title="Ubicación" sub="Estuvo cerca de una tienda (app de Mi página)" :count="$bloques['ubicacion'] ?: null">
        <div class="grid grid-cols-3 gap-3">
            <x-ui.field label="Tienda">
                <x-ui.select :name="$name('near_shop_id')">
                    <option value="">—</option>
                    @foreach ($shops as $shop)
                        <option value="{{ $shop->id }}" @selected((int) $v('near_shop_id') === $shop->id)>{{ $shop->name }}</option>
                    @endforeach
                </x-ui.select>
            </x-ui.field>
            <x-ui.field label="Distancia">
                <x-ui.select :name="$name('near_km')">
                    @foreach (['0.5' => '500 m', '1' => '1 km', '2' => '2 km', '3' => '3 km', '5' => '5 km', '7.5' => '7,5 km', '10' => '10 km'] as $km => $texto)
                        <option value="{{ $km }}" @selected((string) ($f['near_km'] ?? '1') === (string) $km)>{{ $texto }}</option>
                    @endforeach
                </x-ui.select>
            </x-ui.field>
            <x-ui.field label="En las últimas">
                <x-ui.select :name="$name('near_hours')">
                    @foreach ([1, 3, 6, 12, 24, 48, 168] as $h)
                        <option value="{{ $h }}" @selected((int) ($f['near_hours'] ?? 24) === $h)>{{ $h < 48 ? $h . ' h' : ($h / 24) . ' días' }}</option>
                    @endforeach
                </x-ui.select>
            </x-ui.field>
        </div>
    </x-customers.accordion>

    {{-- Historial de visitas --}}
    <x-customers.accordion key="visitas" title="Historial de visitas" sub="Frecuencia, ciclo y próxima visita" :count="$bloques['visitas'] ?: null">
        <p role="note" class="rounded-ctl bg-accent-soft p-3 text-xs leading-relaxed">
            Elige un <strong>periodo</strong> para filtrar por visitas. Sin mínimo ni máximo cuenta quien vino al menos una vez.
        </p>

        <div class="grid grid-cols-2 gap-3" x-data="{ periodo: @js($f['visit_period'] ?? '') }">
            <x-ui.field label="Periodo">
                <x-ui.select :name="$name('visit_period')" x-model="periodo">
                    <option value="">Sin filtrar</option>
                    @foreach (['7' => 'Última semana', '30' => 'Último mes', '90' => 'Últimos 3 meses', '180' => 'Últimos 6 meses', '365' => 'Último año', 'all' => 'Todo el historial', 'custom' => 'Fechas concretas'] as $value => $texto)
                        <option value="{{ $value }}" @selected(($f['visit_period'] ?? '') === (string) $value)>{{ $texto }}</option>
                    @endforeach
                </x-ui.select>
            </x-ui.field>
            <x-ui.field label="Tienda visitada">
                <x-ui.select :name="$name('visit_shop_id')">
                    <option value="">Todas</option>
                    @foreach ($shops as $shop)
                        <option value="{{ $shop->id }}" @selected((int) $v('visit_shop_id') === $shop->id)>{{ $shop->name }}</option>
                    @endforeach
                </x-ui.select>
            </x-ui.field>
            <div class="col-span-2 {{ $range }}" x-show="periodo === 'custom'" x-cloak>
                <x-ui.input type="date" :name="$name('visit_from')" :value="$v('visit_from')" aria-label="Visitas desde" />
                <span>–</span>
                <x-ui.input type="date" :name="$name('visit_to')" :value="$v('visit_to')" aria-label="Visitas hasta" />
            </div>
            <x-ui.field label="Día del mes">
                <x-ui.select :name="$name('visit_day')">
                    <option value="">Cualquiera</option>
                    @for ($d = 1; $d <= 31; $d++)
                        <option value="{{ $d }}" @selected((int) $v('visit_day') === $d)>{{ $d }}</option>
                    @endfor
                </x-ui.select>
            </x-ui.field>
            <x-ui.field label="Día de la semana">
                <x-ui.select :name="$name('visit_dow')">
                    <option value="">Cualquiera</option>
                    @foreach ($dias as $i => $dia)
                        <option value="{{ $i }}" @selected($v('visit_dow') !== '' && (int) $v('visit_dow') === $i)>{{ $dia }}</option>
                    @endforeach
                </x-ui.select>
            </x-ui.field>
        </div>

        <x-ui.field label="Nº de visitas en el periodo" group>
            <div class="{{ $range }}">
                <x-ui.input type="number" min="0" :name="$name('visits_min')" :value="$v('visits_min')" aria-label="Mínimo" placeholder="Mín." />
                <span>–</span>
                <x-ui.input type="number" min="0" :name="$name('visits_max')" :value="$v('visits_max')" aria-label="Máximo" placeholder="Máx." />
                <span class="text-sm whitespace-nowrap">veces</span>
            </div>
        </x-ui.field>

        <div class="grid grid-cols-2 gap-3">
            <x-ui.field label="Sin venir desde hace (días)">
                <x-ui.input type="number" min="0" :name="$name('last_visit_days')" :value="$v('last_visit_days')" />
            </x-ui.field>
            <x-ui.field label="Ciclo medio (días)" group>
                <div class="{{ $range }}">
                    <x-ui.input type="number" min="0" :name="$name('cycle_min')" :value="$v('cycle_min')" aria-label="Ciclo mínimo" />
                    <span>–</span>
                    <x-ui.input type="number" min="0" :name="$name('cycle_max')" :value="$v('cycle_max')" aria-label="Ciclo máximo" />
                </div>
            </x-ui.field>
        </div>

        <x-ui.field label="Próxima visita prevista" hint="Última visita + ciclo medio." group>
            <div class="{{ $range }}">
                <x-ui.input type="date" :name="$name('next_visit_from')" :value="$v('next_visit_from')" aria-label="Desde" />
                <span>–</span>
                <x-ui.input type="date" :name="$name('next_visit_to')" :value="$v('next_visit_to')" aria-label="Hasta" />
            </div>
        </x-ui.field>
    </x-customers.accordion>

    {{-- Ventas --}}
    <x-customers.accordion key="ventas" title="Ventas" sub="Tienda, periodo y ticket promedio" :count="$bloques['ventas'] ?: null">
        <x-ui.field label="Tienda de la venta">
            <x-ui.select :name="$name('sale_shop_id')">
                <option value="">Todas</option>
                @foreach ($shops as $shop)
                    <option value="{{ $shop->id }}" @selected((int) $v('sale_shop_id') === $shop->id)>{{ $shop->name }}</option>
                @endforeach
            </x-ui.select>
        </x-ui.field>
        <x-ui.field label="Periodo de la venta" group>
            <div class="flex flex-col gap-2">
                <x-ui.input type="datetime-local" :name="$name('sale_from')" :value="$v('sale_from')" aria-label="Desde" />
                <x-ui.input type="datetime-local" :name="$name('sale_to')" :value="$v('sale_to')" aria-label="Hasta" />
            </div>
        </x-ui.field>
        <x-ui.field label="Ticket promedio" group>
            <div class="{{ $range }}">
                <x-ui.input type="number" min="0" :name="$name('avg_amount_min')" :value="$v('avg_amount_min')" aria-label="Importe mínimo" placeholder="Mín." />
                <span>–</span>
                <x-ui.input type="number" min="0" :name="$name('avg_amount_max')" :value="$v('avg_amount_max')" aria-label="Importe máximo" placeholder="Máx." />
            </div>
        </x-ui.field>
    </x-customers.accordion>
</div>
