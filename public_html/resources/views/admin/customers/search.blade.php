@php
    $shops = $shops->pluck('name')->all();

    // El desglose sale del campo «tipo de dirección» del cliente, igual que en el
    // legacy, no de analizar el dominio del email.
    $carriers = collect(\App\Enums\AddressType::cases())
        ->map(fn ($t) => $t->label() . ' ' . ($byCarrier[$t->value] ?? 0))
        ->push('Sin identificar ' . ($byCarrier[''] ?? 0))
        ->all();
@endphp

<x-layouts.modulo title="Buscar clientes"
                  :crumbs="[['label' => __('customers.modulo'), 'route' => 'admin.customers.index'], ['label' => 'Buscar clientes']]">

    <x-ui.page-header title="Buscar clientes"
                      description="Combina filtros de perfil, visitas y ventas para encontrar y segmentar clientes.">
        <x-slot:actions>
            <x-ui.btn icon="bookmark">Búsquedas guardadas</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid items-start gap-5 xl:grid-cols-[340px_minmax(0,1fr)]"
         x-data="{
             abierto: { cliente: true, ubicacion: false, sampleform: false, ficha: false, familia: false, visitas: true, ventas: false },
             logica: 'and',
             resultado: 'coinciden',
             tiendas: [],
             sel: [],
             get todas() { return this.sel.length === {{ $customers->count() }} },
             alternarTodas() { this.sel = this.todas ? [] : @js($customers->pluck('code')->all()) },
         }">

        {{-- Panel de filtros --}}
        <form aria-label="Filtros de búsqueda" class="flex flex-col overflow-hidden rounded-card border border-line bg-surface">

            <div class="flex items-center justify-between px-5 pt-4 pb-2">
                <div class="flex items-center gap-2">
                    <x-icon name="filter" />
                    <span class="text-base font-extrabold">Filtros</span>
                    <x-ui.badge tone="accent">1</x-ui.badge>
                </div>
                <button type="reset" class="text-sm font-bold text-accent-fg hover:underline">Limpiar todo</button>
            </div>

            <div class="flex flex-col gap-3.5 px-5 pt-1 pb-4">
                <x-ui.field>
                    <span class="sr-only">Búsqueda rápida</span>
                    <x-ui.input type="search" placeholder="Nombre, nº, teléfono o email" />
                </x-ui.field>

                <x-ui.field label="Lógica de búsqueda" group>
                    <div class="flex gap-1.5">
                        @foreach (['and' => 'Y (AND)', 'or' => 'O (OR)'] as $value => $texto)
                            <button type="button" @click="logica = '{{ $value }}'" :aria-pressed="logica === '{{ $value }}'"
                                    class="flex-1 rounded-ctl border px-2.5 py-2 text-sm font-bold transition-colors"
                                    :class="logica === '{{ $value }}' ? 'border-accent bg-accent text-on-accent' : 'border-line bg-surface text-ink hover:bg-surface2'">
                                {{ $texto }}
                            </button>
                        @endforeach
                    </div>
                </x-ui.field>

                <x-ui.field label="Resultado" group>
                    <div class="flex gap-1.5">
                        @foreach (['coinciden' => 'Coinciden', 'excluir' => 'No coinciden'] as $value => $texto)
                            <button type="button" @click="resultado = '{{ $value }}'" :aria-pressed="resultado === '{{ $value }}'"
                                    class="flex-1 rounded-ctl border px-2.5 py-2 text-sm font-bold transition-colors"
                                    :class="resultado === '{{ $value }}' ? 'border-accent bg-accent text-on-accent' : 'border-line bg-surface text-ink hover:bg-surface2'">
                                {{ $texto }}
                            </button>
                        @endforeach
                    </div>
                </x-ui.field>

                <p class="text-xs leading-relaxed text-faint">
                    «Estado de registro» no se ve afectado por estas opciones: siempre se busca por coincidencia exacta.
                </p>
            </div>

            {{-- Datos del cliente --}}
            <x-customers.accordion key="cliente" title="Datos del cliente" sub="Perfil, contacto, registro y edad" :count="1">
                <div class="grid grid-cols-2 gap-3">
                    <x-ui.field label="Nº de cliente">
                        <x-ui.input inputmode="numeric" placeholder="Ej. 1100028" />
                    </x-ui.field>
                    <x-ui.field label="Teléfono">
                        <x-ui.input type="tel" />
                    </x-ui.field>
                </div>

                <x-ui.field label="Nombre" hint="Para buscar por nombre completo, separa apellido y nombre con un espacio.">
                    <x-ui.input placeholder="Apellido Nombre" />
                </x-ui.field>

                <x-ui.field label="Email 1">
                    <x-ui.input type="email" />
                </x-ui.field>

                <x-ui.field label="Tienda de registro" group>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($shops as $t)
                            <button type="button" @click="tiendas.includes(@js($t)) ? tiendas = tiendas.filter(x => x !== @js($t)) : tiendas.push(@js($t))"
                                    :aria-pressed="tiendas.includes(@js($t))"
                                    class="min-h-9.5 rounded-ctl border px-2.5 text-sm font-semibold transition-colors"
                                    :class="tiendas.includes(@js($t)) ? 'border-accent bg-accent text-on-accent' : 'border-line bg-surface text-ink hover:bg-surface2'">
                                {{ $t }}
                            </button>
                        @endforeach
                    </div>
                </x-ui.field>

                <x-ui.field label="Cumpleaños (mes / día)" group>
                    <div class="grid grid-cols-2 gap-3">
                        <x-ui.select aria-label="Mes">
                            <option>Mes</option>
                            @for ($m = 1; $m <= 12; $m++) <option>{{ $m }}</option> @endfor
                        </x-ui.select>
                        <x-ui.select aria-label="Día">
                            <option>Día</option>
                            @for ($d = 1; $d <= 31; $d++) <option>{{ $d }}</option> @endfor
                        </x-ui.select>
                    </div>
                </x-ui.field>

                <div class="grid grid-cols-2 gap-3">
                    <x-ui.field label="Sexo">
                        <x-ui.select>
                            <option>Cualquiera</option><option>Hombre</option><option>Mujer</option>
                        </x-ui.select>
                    </x-ui.field>
                    <x-ui.field label="Edad" group>
                        <div class="flex items-center gap-1.5 text-faint">
                            <x-ui.input type="number" aria-label="Edad mínima" placeholder="De" />
                            <span>–</span>
                            <x-ui.input type="number" aria-label="Edad máxima" placeholder="A" />
                        </div>
                    </x-ui.field>
                </div>

                <x-ui.field label="Fecha de alta" group>
                    <div class="flex items-center gap-1.5 text-faint">
                        <x-ui.input type="date" aria-label="Alta desde" />
                        <span>–</span>
                        <x-ui.input type="date" aria-label="Alta hasta" />
                    </div>
                </x-ui.field>

                <div class="grid grid-cols-2 gap-3">
                    <x-ui.field label="Estado de registro">
                        <x-ui.select><option>Registrado</option><option>Dado de baja</option><option>Todos</option></x-ui.select>
                    </x-ui.field>
                    <x-ui.field label="Newsletter">
                        <x-ui.select><option>Cualquiera</option><option>Enviar</option><option>No enviar</option><option>No entregable</option></x-ui.select>
                    </x-ui.field>
                    <x-ui.field label="Ocupación">
                        <x-ui.select><option>Cualquiera</option></x-ui.select>
                    </x-ui.field>
                    <x-ui.field label="ID de terminal">
                        <x-ui.select><option>Cualquiera</option></x-ui.select>
                    </x-ui.field>
                </div>

                <x-ui.field label="Correos no entregados" group>
                    <div class="flex items-center gap-1.5 text-faint">
                        <x-ui.input type="number" aria-label="Mínimo" placeholder="Mín." />
                        <span>–</span>
                        <x-ui.input type="number" aria-label="Máximo" placeholder="Máx." />
                    </div>
                </x-ui.field>
            </x-customers.accordion>

            {{-- Ubicación --}}
            <x-customers.accordion key="ubicacion" title="Ubicación" sub="Clientes que estuvieron cerca de una tienda">
                <div class="grid grid-cols-3 gap-3">
                    <x-ui.field label="En las últimas">
                        <x-ui.select><option>1 h</option><option>3 h</option><option>6 h</option><option>24 h</option></x-ui.select>
                    </x-ui.field>
                    <x-ui.field label="Desde">
                        <x-ui.select>
                            @foreach ($shops as $t) <option>{{ $t }}</option> @endforeach
                        </x-ui.select>
                    </x-ui.field>
                    <x-ui.field label="Distancia">
                        <x-ui.select><option>—</option><option>500 m</option><option>1 km</option><option>3 km</option></x-ui.select>
                    </x-ui.field>
                </div>
            </x-customers.accordion>

            {{-- Categorías personalizadas --}}
            <x-customers.accordion key="sampleform" title="sampleform" sub="Formulario personalizado">
                <div class="grid grid-cols-2 gap-3">
                    <x-ui.field label="remarks"><x-ui.input /></x-ui.field>
                    <x-ui.field label="remarks2"><x-ui.input /></x-ui.field>
                </div>
            </x-customers.accordion>

            <x-customers.accordion key="ficha" title="Ficha (カルテ)" sub="Mensajes y personal que atendió">
                <div class="grid grid-cols-2 gap-3">
                    <x-ui.field label="Contenido del mensaje"><x-ui.input /></x-ui.field>
                    <x-ui.field label="Nombre del personal"><x-ui.input /></x-ui.field>
                </div>
            </x-customers.accordion>

            <x-customers.accordion key="familia" title="Información familiar" sub="Cónyuge y aniversario de boda">
                <x-ui.field label="Cónyuge">
                    <x-ui.select><option>Selecciona…</option><option>Sí</option><option>No</option></x-ui.select>
                </x-ui.field>
                <x-ui.field label="Aniversario de boda" group>
                    <div class="flex items-center gap-1.5 text-faint">
                        <x-ui.input type="date" aria-label="Desde" />
                        <span>–</span>
                        <x-ui.input type="date" aria-label="Hasta" />
                    </div>
                </x-ui.field>
            </x-customers.accordion>

            {{-- Historial de visitas --}}
            <x-customers.accordion key="visitas" title="Historial de visitas" sub="Frecuencia, ciclo y próxima visita">
                <p role="note" class="rounded-ctl bg-accent-soft p-3 text-xs leading-relaxed">
                    <strong>Periodo</strong> y <strong>Nº de visitas</strong> son obligatorios para filtrar por historial;
                    sin ellos este bloque se ignora. La próxima visita prevista = última visita + ciclo medio de visita.
                </p>

                <div class="grid grid-cols-2 gap-3">
                    <x-ui.field label="Periodo" required>
                        <x-ui.select><option>Selecciona…</option><option>Últimos 3 meses</option><option>Últimos 6 meses</option><option>Último año</option></x-ui.select>
                    </x-ui.field>
                    <x-ui.field label="Tienda visitada">
                        <x-ui.select>
                            <option>Todas</option>
                            @foreach ($shops as $t) <option>{{ $t }}</option> @endforeach
                        </x-ui.select>
                    </x-ui.field>
                    <x-ui.field label="Día del mes">
                        <x-ui.select><option>Cualquiera</option>@for ($d = 1; $d <= 31; $d++)<option>{{ $d }}</option>@endfor</x-ui.select>
                    </x-ui.field>
                    <x-ui.field label="Día de la semana">
                        <x-ui.select>
                            <option>Cualquiera</option>
                            @foreach (['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'] as $dia)
                                <option>{{ $dia }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>
                </div>

                <x-ui.field label="Nº de visitas" required group>
                    <div class="flex items-center gap-1.5 text-faint">
                        <x-ui.input type="number" aria-label="Mínimo" placeholder="Mín." />
                        <span>–</span>
                        <x-ui.input type="number" aria-label="Máximo" placeholder="Máx." />
                        <span class="text-sm whitespace-nowrap">veces</span>
                    </div>
                </x-ui.field>

                <div class="grid grid-cols-2 gap-3">
                    <x-ui.field label="Días desde la última visita">
                        <x-ui.input type="number" />
                    </x-ui.field>
                    <x-ui.field label="Ciclo medio (días)" group>
                        <div class="flex items-center gap-1.5 text-faint">
                            <x-ui.input type="number" aria-label="Mínimo" />
                            <span>–</span>
                            <x-ui.input type="number" aria-label="Máximo" />
                        </div>
                    </x-ui.field>
                </div>

                <x-ui.field label="Próxima visita prevista" group>
                    <div class="flex items-center gap-1.5 text-faint">
                        <x-ui.input type="date" aria-label="Desde" />
                        <span>–</span>
                        <x-ui.input type="date" aria-label="Hasta" />
                    </div>
                </x-ui.field>
            </x-customers.accordion>

            {{-- Ventas --}}
            <x-customers.accordion key="ventas" title="Ventas" sub="Tienda, periodo y ticket promedio">
                <x-ui.field label="Tienda de la venta">
                    <x-ui.select>
                        <option>Todas</option>
                        @foreach ($shops as $t) <option>{{ $t }}</option> @endforeach
                    </x-ui.select>
                </x-ui.field>
                <x-ui.field label="Periodo de la venta" group>
                    <div class="flex flex-col gap-2">
                        <x-ui.input type="datetime-local" aria-label="Desde" />
                        <x-ui.input type="datetime-local" aria-label="Hasta" />
                    </div>
                </x-ui.field>
                <x-ui.field label="Ticket promedio" group>
                    <div class="flex items-center gap-1.5 text-faint">
                        <x-ui.input type="number" aria-label="Importe mínimo" placeholder="Mín." />
                        <span>–</span>
                        <x-ui.input type="number" aria-label="Importe máximo" placeholder="Máx." />
                    </div>
                </x-ui.field>
            </x-customers.accordion>

            <div class="sticky bottom-0 flex gap-2 border-t border-line bg-surface p-3.5">
                <x-ui.btn type="reset" class="flex-1">Limpiar</x-ui.btn>
                <x-ui.btn type="submit" variant="primary" class="flex-2">Buscar clientes</x-ui.btn>
            </div>
        </form>

        {{-- Resultados --}}
        <section aria-labelledby="resultados" class="flex min-w-0 flex-col gap-3.5">

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 id="resultados" class="text-lg font-bold">{{ $customers->total() }} clientes encontrados</h2>
                    <p class="mt-0.5 text-sm text-muted">
                        Mostrando {{ $customers->firstItem() ?? 0 }}–{{ $customers->lastItem() ?? 0 }} de {{ $customers->total() }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <x-ui.btn icon="mail">Enviar mensaje</x-ui.btn>
                    <x-ui.btn icon="download">Exportar CSV</x-ui.btn>
                    <x-ui.btn icon="download">CSV para correo directo</x-ui.btn>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 text-xs text-muted">
                <span class="font-bold">Por tipo de email:</span>
                @foreach ($carriers as $op)
                    <x-ui.badge tone="outline">{{ $op }}</x-ui.badge>
                @endforeach
            </div>

            <div class="flex flex-wrap gap-2">
                <x-ui.badge tone="outline" class="h-8 px-3 text-sm font-normal">
                    <span class="text-faint">Estado de registro:</span> <strong>Registrado</strong>
                </x-ui.badge>
                <template x-if="tiendas.length">
                    <span class="inline-flex h-8 items-center gap-1.5 rounded-full border border-line bg-surface px-3 text-sm">
                        <span class="text-faint">Tienda:</span> <strong x-text="tiendas.join(', ')"></strong>
                    </span>
                </template>
            </div>

            {{-- Barra de selección --}}
            <div x-show="sel.length" x-cloak x-transition
                 class="flex flex-wrap items-center gap-2.5 rounded-card bg-ink py-2 pr-2 pl-4 text-paper">
                <strong class="flex-1"><span x-text="sel.length"></span> seleccionados</strong>
                @foreach (['Añadir a grupo', 'Enviar mensaje', 'Exportar'] as $action)
                    <button type="button" class="h-9 rounded-ctl border border-current px-3 text-sm font-bold hover:bg-white/10">
                        {{ $action }}
                    </button>
                @endforeach
            </div>

            <x-ui.card :pad="false">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[664px] text-sm">
                        <thead>
                            <tr class="bg-surface2 text-left text-xs font-bold whitespace-nowrap text-muted">
                                <th scope="col" class="w-11 py-2.5 pl-4">
                                    <input type="checkbox" aria-label="Seleccionar todos" class="h-4.5 w-4.5 accent-[var(--crm-accent)]"
                                           :checked="todas" @change="alternarTodas()">
                                </th>
                                <th scope="col" class="px-3 py-2.5">Nº de socio</th>
                                <th scope="col" class="px-3 py-2.5">Nº gestión</th>
                                <th scope="col" class="px-3 py-2.5">Nombre</th>
                                <th scope="col" class="px-3 py-2.5">Terminal</th>
                                <th scope="col" class="px-3 py-2.5">Visitas</th>
                                <th scope="col" class="px-3 py-2.5"><span class="sr-only">Acciones</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($customers as $customer)
                                <tr class="border-t border-line transition-colors"
                                    :class="sel.includes(@js($customer->code)) ? 'bg-accent-soft' : 'hover:bg-surface2/60'">
                                    <td class="py-3 pl-4">
                                        <input type="checkbox" aria-label="Seleccionar {{ $customer->full_name }}"
                                               class="h-4.5 w-4.5 accent-[var(--crm-accent)]" x-model="sel" value="{{ $customer->code }}">
                                    </td>
                                    <td class="tnum px-3 py-3">{{ $customer->code }}</td>
                                    <td class="px-3 py-3 @if (! $customer->management_no) text-faint @endif">
                                        {{ $customer->management_no ?: '—' }}
                                    </td>
                                    <td class="px-3 py-3">
                                        <div class="flex items-center gap-3">
                                            <span class="flex h-8.5 w-8.5 shrink-0 items-center justify-center rounded-full bg-surface2 text-sm font-extrabold">
                                                {{ mb_substr($customer->last_name, 0, 1) }}
                                            </span>
                                            <a href="{{ route('admin.customers.show', $customer) }}" class="font-bold hover:text-accent-fg">
                                                {{ $customer->full_name }}
                                            </a>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3">
                                        <x-ui.badge>{{ $customer->terminal?->name ?? 'Sin terminal' }}</x-ui.badge>
                                    </td>
                                    <td class="px-3 py-3 font-bold whitespace-nowrap">
                                        {{ $customer->visit_count ? $customer->visit_count . ' veces' : '—' }}
                                    </td>
                                    <td class="px-3 py-3 text-right">
                                        <x-ui.btn :href="route('admin.customers.show', $customer)" class="h-8.5 px-3">Ver ficha</x-ui.btn>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">
                                        <x-ui.empty icon="search" title="Ningún cliente coincide">
                                            Prueba a quitar filtros o a registrar un cliente nuevo.
                                        </x-ui.empty>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-4 py-3">
                    <label class="flex items-center gap-2 text-sm text-muted">
                        Filas por página
                        <x-ui.select class="w-auto!"><option>10</option><option>25</option><option>50</option></x-ui.select>
                    </label>
                    {{ $customers->onEachSide(1)->links() }}
                </div>
            </x-ui.card>
        </section>
    </div>

</x-layouts.modulo>
