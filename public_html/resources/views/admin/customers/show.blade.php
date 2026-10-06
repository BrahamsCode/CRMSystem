@php
    $dash = '—';
    $c = $customer ?? null;

    $tabs = [
        'info' => 'Información',
        'visits' => 'Historial de visitas',
        'cupones' => 'Mis cupones',
        'album' => 'Mi álbum',
        'puntos' => 'Puntos',
        'sellos' => 'Sellos',
        'sampleform' => 'sampleform',
        'karte' => 'Ficha',
        'familyFields' => 'Familia',
        'mascota' => 'Mascota',
        'reservas' => 'Reservas',
        'referidos' => 'Referidos',
    ];

    $fecha = fn (?object $d, string $f = 'Y/m/d') => $d?->format($f);
    $mascota = $c?->custom_data['mascota'] ?? null;
    $co = $c?->company;
    $empresa = $c?->isCompany() ?? false;

    // [etiqueta, valor, ancho_completo, nota, es_contraseña]
    $content = [
        'info' => [
            ['titulo' => 'Datos del cliente', 'items' => [
                ['Nº de gestión', $c?->management_no],
                ['Tienda de registro', $c?->shop?->name],
                ['Nombre (fonético)', $c?->full_name_kana],
                ['Nombre', $c?->full_name],
                // El código postal se guarda solo con dígitos; se muestra como 〒530-0001
                ['Dirección', trim(implode(' ', array_filter([
                    $c?->zip ? '〒' . preg_replace('/^(\d{3})(\d{4})$/', '$1-$2', $c->zip) : null,
                    $c?->pref, $c?->city, $c?->street_address, $c?->building,
                ]))), true],
                ['Dirección (fonético)', trim(implode(' ', array_filter([$c?->city_kana, $c?->building_kana]))), true],
                ['Email', $c?->mail1],
                ['Teléfono', $c?->tel1],
                ['Fax', $c?->tel3],
                ['Persona / empresa', $c?->type?->label()],
                ['Personal asignado', null],
                ['Referido por', $c?->referrer?->full_name],
                ['Rango por importe', 'Actual ' . ($c?->amountRank?->name ?: $dash) . ' · Anterior ' . $dash],
                ['Rango por visitas', 'Actual ' . ($c?->visitRank?->name ?: $dash) . ' · Anterior ' . $dash],
            ]],
            ['titulo' => 'Registro y acceso', 'items' => [
                ['Fecha de alta', $fecha($c?->created_at, 'Y/m/d H:i')],
                ['Última actualización', $fecha($c?->updated_at, 'Y/m/d H:i')],
                ['ID de acceso', $c?->code],
                ['Contraseña', $c?->getAttributes()['password'] ?? null, false, null, true],
                ['Registro de terminal', $c ? ($c->hasTerminal() ? 'Terminal registrado' : 'Sin terminal registrado') : null, true],
                ['Dispositivo', $c ? trim(($c->device_type ?: $dash) . ' · Inicio de sesión rápido: ' . ($c->easy_login ?: 'no configurado')) : null, false,
                    'Se actualiza cuando el cliente se registra, cambia sus datos o entra en Mi página.'],
            ]],
            // Persona y empresa tienen secciones distintas, como en el legacy
            ...($empresa ? [['titulo' => 'Datos de empresa', 'items' => [
                ['Fecha de fundación', $fecha($co?->founded_on)],
                ['Capital social', $co?->capital !== null ? \App\Support\Money::format($co->capital) : null],
                ['Rubro', $co?->industry?->label()],
                ['Departamento', $co?->department],
                ['Representante', $co ? trim($co->representativeName() . ($co->representative_last_name_kana ? ' (' . trim($co->representative_last_name_kana . ' ' . $co->representative_first_name_kana) . ')' : '')) : null],
                ['Nacimiento del representante', $fecha($co?->representative_birth_date)],
                ['Persona de contacto', $co?->contactName()],
                ['Contacto: teléfono / fax', $co ? trim(implode(' / ', array_filter([$co->contact_tel1, $co->contact_tel3]))) : null],
                ['Contacto: email', $co?->contact_mail],
            ]]] : [['titulo' => 'Datos personales', 'items' => [
                ['Fecha de nacimiento', $c?->birth_date ? $fecha($c->birth_date) . ' (' . $c->age() . ' años)' : null],
                ['Sexo', $c?->sex?->label()],
                ['Ocupación', $c?->occupation?->label()],
                ['Grupo sanguíneo', $c?->blood_type],
                ['Email personal', $c?->mail3],
                ['Teléfono móvil', $c?->tel2],
                ['Lugar de trabajo', trim(($co?->name ?? '') . ($co?->name_kana ? ' (' . $co->name_kana . ')' : ''))],
                ['Rubro del trabajo', $co?->industry?->label()],
                ['Teléfono del trabajo', $co?->tel1],
                ['Fax del trabajo', $co?->tel3],
            ]]]),
            ['titulo' => 'Información de promoción', 'items' => [
                ['Newsletter', $c?->mail_magazine?->label()],
                ['Tipo de dirección', $c?->address_type?->label()],
                ['Correos no entregados', $c ? (string) $c->bounce_count : null, false,
                    'Tras 3 errores de envío la dirección pasa a «no entregable». El contador también depende de la red y del operador: es solo orientativo.'],
                ['Recordatorio de reservas', $c ? ($c->reservation_reminder_flg ? 'Suscrito' : 'No suscrito') : null],
                ['Grupo de cliente', $c?->group?->name],
                ['Motivo de primera visita', $c?->visitMotive?->name],
                ['Notas', $c?->note, true],
            ]],
        ],
        'familyFields' => [
            ['titulo' => 'Información familiar', 'items' => [
                ['Cónyuge', $c ? ($c->spouse_flg ? 'Sí' : 'No') : null],
                ['Aniversario de boda', $fecha($c?->wedding_date)],
            ]],
        ],
        'mascota' => $mascota ? [
            ['titulo' => 'Mascota', 'items' => [
                ['Nombre', $mascota['name'] ?? null],
                ['Tipo', $mascota['type'] ?? null],
                ['Peso', isset($mascota['peso']) ? $mascota['peso'] . ' Kg' : null],
            ]],
        ] : [],
    ];

    $title = $c?->full_name ?: 'Ficha del cliente';
    $initials = $c ? mb_substr($c->last_name, 0, 2) : '—';
@endphp

<x-layouts.modulo :title="$title"
                  :crumbs="[
                      ['label' => __('customers.modulo'), 'route' => 'admin.customers.index'],
                      ['label' => 'Buscar clientes', 'route' => 'admin.customers.search'],
                      ['label' => $c ? 'Ficha ' . $c->code : 'Ficha'],
                  ]">

    <div x-data="{ tab: 'info', verClave: false, confirmarBorrado: false }" class="flex flex-col gap-5">

        @if (session('status'))
            <div role="status" class="flex flex-wrap items-center gap-3.5 rounded-card bg-accent-soft px-5 py-3.5">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-accent text-on-accent">
                    <x-icon name="check" />
                </span>
                <strong class="flex-1">{{ session('status') }}</strong>
            </div>
        @endif

        <a href="{{ route('admin.customers.search') }}" class="text-sm font-bold text-accent-fg hover:underline">
            &larr; Volver a los resultados
        </a>

        @if (! $c)
            <x-ui.card>
                <x-ui.empty icon="users" title="No hay ningún cliente seleccionado">
                    Entra a una ficha desde los resultados de búsqueda.
                    <x-slot:action>
                        <x-ui.btn :href="route('admin.customers.search')">Buscar clientes</x-ui.btn>
                    </x-slot:action>
                </x-ui.empty>
            </x-ui.card>
        @else

        {{-- Cabecera del cliente --}}
        <x-ui.card class="flex flex-wrap items-center gap-4.5 p-5.5">
            <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-accent-soft text-xl font-extrabold text-accent-fg">
                {{ $initials }}
            </span>

            <div class="min-w-55 flex-1">
                <div class="text-xs text-faint">{{ $c->full_name_kana ?: $dash }}</div>
                <h1 class="mt-0.5 text-[26px] leading-tight font-extrabold">{{ $c->full_name }}</h1>
                <div class="mt-2 flex flex-wrap gap-2">
                    <x-ui.badge>Nº de socio {{ $c->code }}</x-ui.badge>
                    <x-ui.badge class="font-normal">{{ $c->type->label() }}</x-ui.badge>
                    <x-ui.badge class="font-normal">Tienda: {{ $c->shop?->name ?? $dash }}</x-ui.badge>
                    <x-ui.badge tone="accent">{{ $c->status->label() }}</x-ui.badge>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <x-ui.btn variant="danger" @click="confirmarBorrado = true">Eliminar cliente</x-ui.btn>
                <x-ui.btn variant="primary" :href="route('admin.customers.create')">Editar cliente</x-ui.btn>
            </div>
        </x-ui.card>

        <div x-show="confirmarBorrado" x-cloak x-collapse role="alertdialog" aria-label="Confirmar eliminación">
            <div class="flex flex-wrap items-center gap-3 rounded-card bg-danger/10 px-5 py-3.5 text-danger">
                <strong class="flex-1">
                    ¿Eliminar a este cliente? Se borrarán también su historial y sus datos adicionales.
                </strong>
                <x-ui.btn @click="confirmarBorrado = false">Cancelar</x-ui.btn>
                <x-ui.btn :href="route('admin.customers.search')"
                          class="border-danger! bg-danger! text-white! hover:opacity-90">Sí, eliminar</x-ui.btn>
            </div>
        </div>

        {{-- Pestañas --}}
        <div role="tablist" aria-label="Secciones de la ficha" class="flex flex-wrap gap-0.5 overflow-x-auto border-b border-line">
            @foreach ($tabs as $clave => $texto)
                <button type="button" role="tab" @click="tab = '{{ $clave }}'"
                        :aria-selected="tab === '{{ $clave }}'"
                        class="min-h-11 px-3.5 text-sm whitespace-nowrap transition-colors"
                        :class="tab === '{{ $clave }}'
                            ? 'font-bold text-ink shadow-[inset_0_-2px_0_var(--crm-accent)]'
                            : 'font-medium text-muted hover:text-ink'">
                    {{ $texto }}
                </button>
            @endforeach
        </div>

        @foreach ($tabs as $clave => $texto)
            <div x-show="tab === '{{ $clave }}'" x-cloak class="flex flex-col gap-5">
                @if (in_array($clave, ['cupones', 'puntos', 'sellos'], true))
                    {{-- Datos del módulo de promociones --}}
                    @include('admin.customers.partials.loyalty', ['tipo' => $clave])
                    @continue
                @endif
                @forelse ($content[$clave] ?? [] as $seccion)
                    <x-ui.section :title="$seccion['titulo']">
                        <dl class="m-0 grid sm:grid-cols-2">
                            @foreach ($seccion['items'] as $item)
                                @php
                                    [$label, $value] = [$item[0], $item[1] ?? null];
                                    $ancho = $item[2] ?? false;
                                    $nota = $item[3] ?? null;
                                    $esClave = $item[4] ?? false;
                                @endphp
                                <div @class([
                                    'grid grid-cols-[130px_minmax(0,1fr)] border-t border-line sm:grid-cols-[170px_minmax(0,1fr)]',
                                    'sm:col-span-full' => $ancho,
                                ])>
                                    <dt class="bg-surface2 px-4 py-3 text-sm font-bold text-muted">{{ $label }}</dt>
                                    <dd class="m-0 flex min-w-0 flex-col gap-1.5 px-4 py-3 text-sm">
                                        @if ($esClave && $value)
                                            <span x-text="verClave ? '••••••' : '••••'"></span>
                                            <span class="text-xs text-faint">
                                                Guardada cifrada: no se puede mostrar, solo restablecer.
                                            </span>
                                        @else
                                            <span @class(['text-faint' => ! filled($value)])>{{ filled($value) ? $value : $dash }}</span>
                                        @endif

                                        @if ($nota)
                                            <span class="text-xs leading-relaxed text-faint">{{ $nota }}</span>
                                        @endif
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    </x-ui.section>
                @empty
                    <x-ui.card>
                        <x-ui.empty :title="$texto">No hay registros para este cliente.</x-ui.empty>
                    </x-ui.card>
                @endforelse
            </div>
        @endforeach

        <div class="flex justify-center">
            <x-ui.btn :href="route('admin.customers.search')">Cerrar ficha</x-ui.btn>
        </div>
        @endif
    </div>

</x-layouts.modulo>
