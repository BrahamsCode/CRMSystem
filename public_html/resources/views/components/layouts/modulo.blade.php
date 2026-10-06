@props([
    'title' => '',
    'crumbs' => [],
    // Clave del módulo en config('crm.modulos'): decide el menú lateral y su archivo de idioma
    'module' => 'clientes',
])

@php
    $current = collect(config('crm.modulos'))->firstWhere('key', $module);
    $lang = $current['lang'];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title . ' | ' : '' }}{{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="min-h-screen bg-paper text-ink antialiased" x-data="{ nav: false }">

<div class="flex min-h-screen">

    {{-- Fondo del drawer en móvil --}}
    <div x-show="nav" x-cloak @click="nav = false" x-transition.opacity
         class="fixed inset-0 z-30 bg-black/50 lg:hidden"></div>

    {{-- Barra de módulos --}}
    <nav aria-label="Módulos"
         class="fixed inset-y-0 left-0 z-40 flex w-18 shrink-0 -translate-x-full flex-col items-center gap-1 bg-nav py-4 transition-transform lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"
         :class="nav && '!translate-x-0'">
        <a href="{{ route('admin.customers.index') }}"
           class="mb-4 flex h-10 w-10 items-center justify-center rounded-ctl bg-accent text-xs font-extrabold text-on-accent">
            CRM
        </a>

        @foreach (config('crm.modulos') as $mod)
            @php $activo = $mod['key'] === $module; @endphp
            <a href="{{ $mod['route'] ? route($mod['route']) : '#' }}"
               title="{{ $mod['label'] }}" aria-label="{{ $mod['label'] }}"
               @class([
                   'flex h-11 w-11 items-center justify-center rounded-ctl transition-colors',
                   'bg-nav-active text-white' => $activo,
                   'text-nav-fg hover:bg-nav-active/60 hover:text-white' => ! $activo,
               ])>
                <x-icon :name="$mod['icon']" :size="20" />
            </a>
        @endforeach
    </nav>

    {{-- Menú del módulo --}}
    <aside aria-label="{{ __($lang . '.modulo_sub') }}"
           {{-- Arranca en left-18 (tras la barra de módulos), así que debe desplazarse 72px + 252px para salir del todo --}}
           class="fixed inset-y-0 left-18 z-40 flex w-63 shrink-0 -translate-x-81 flex-col gap-0.5 overflow-y-auto border-r border-line bg-surface px-3 py-5 transition-transform lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"
           :class="nav && '!translate-x-0'">
        <div class="px-3 pb-2">
            <div class="text-[15px] font-extrabold">{{ __($lang . '.modulo') }}</div>
            <div class="text-xs text-faint">{{ __($lang . '.modulo_sub') }}</div>
        </div>

        @foreach (config('crm.' . $current['menu']) as $item)
            @if (isset($item['head']))
                <div class="mt-4 px-3 pb-1 text-[11px] font-extrabold tracking-wider text-faint uppercase first:mt-0">
                    {{ $item['head'] === 'modulo' ? __($lang . '.modulo') : __($lang . '.menu.' . $item['head']) }}
                </div>
            @else
                {{-- «active» permite marcar la entrada también en sus pantallas hijas (crear, editar, detalle) --}}
                @php $activo = request()->routeIs($item['active'] ?? $item['route']); @endphp
                <a href="{{ route($item['route']) }}"
                   @if ($activo) aria-current="page" @endif
                   @class([
                       'flex items-center gap-2.5 rounded-ctl px-3 py-2 text-sm transition-colors',
                       'bg-accent-soft font-bold text-accent-fg' => $activo,
                       'font-medium text-muted hover:bg-surface2 hover:text-ink' => ! $activo,
                   ])>
                    <x-icon :name="$item['icon']" />
                    {{ __($lang . '.menu.' . $item['label']) }}
                </a>
            @endif
        @endforeach
    </aside>

    {{-- Columna de contenido --}}
    <div class="flex min-w-0 flex-1 flex-col">

        <header class="sticky top-0 z-20 flex h-16 items-center gap-2.5 border-b border-line bg-surface px-4 sm:px-6">
            <button type="button" @click="nav = true"
                    class="-ml-2 flex h-11 w-11 items-center justify-center rounded-ctl text-muted hover:bg-surface2 lg:hidden"
                    aria-label="{{ __('customers.comun.abrir_menu') }}">
                <x-icon name="menu" :size="20" />
            </button>

            <nav aria-label="{{ __('customers.comun.ruta') }}" class="flex min-w-0 items-center overflow-hidden text-sm whitespace-nowrap">
                <a href="{{ route('admin.home') }}" class="text-faint hover:text-ink">{{ __('customers.comun.inicio') }}</a>
                @foreach ($crumbs as $crumb)
                    <span aria-hidden="true" class="mx-2 text-faint">/</span>
                    @if (! empty($crumb['route']) && ! $loop->last)
                        <a href="{{ route($crumb['route']) }}" class="text-faint hover:text-ink">{{ $crumb['label'] }}</a>
                    @else
                        <span class="truncate font-bold">{{ $crumb['label'] }}</span>
                    @endif
                @endforeach
            </nav>

            <div class="flex-1"></div>

            <form method="GET" action="{{ route('admin.customers.search') }}" role="search" class="hidden w-64 md:block">
                <label class="block">
                    <span class="sr-only">{{ __('customers.comun.buscar_cliente') }}</span>
                    <input type="search" name="q" value="{{ request()->routeIs('admin.customers.search') ? request('q') : '' }}"
                           placeholder="{{ __('customers.comun.buscar_cliente') }}"
                           class="h-10 w-full rounded-ctl border border-line bg-surface px-3 text-sm placeholder:text-faint focus:border-accent focus:ring-2 focus:ring-accent/20 focus:outline-none">
                </label>
            </form>

            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-ink text-xs font-bold text-paper">
                {{ strtoupper(mb_substr(auth('admin')->user()->name ?? 'A', 0, 1)) }}
            </span>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" aria-label="{{ __('customers.comun.cerrar_sesion') }}" title="{{ __('customers.comun.cerrar_sesion') }}"
                        class="flex h-11 w-11 items-center justify-center rounded-ctl text-muted hover:bg-surface2 hover:text-ink">
                    <x-icon name="logout" :size="20" />
                </button>
            </form>
        </header>

        <main class="mx-auto flex w-full max-w-[1600px] flex-1 flex-col gap-5 p-4 sm:p-6 lg:p-8">
            {{ $slot }}
        </main>
    </div>
</div>

@stack('scripts')
</body>
</html>
