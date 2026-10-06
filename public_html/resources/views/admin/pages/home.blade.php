<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inicio | {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-paper text-ink antialiased">
    <header class="flex h-16 items-center gap-3 border-b border-line bg-surface px-4 sm:px-6">
        <span class="flex h-10 w-10 items-center justify-center rounded-ctl bg-accent text-xs font-extrabold text-on-accent">CRM</span>
        <span class="font-extrabold">{{ config('app.name') }}</span>
        <div class="flex-1"></div>
        <form method="GET" action="{{ route('admin.customers.search') }}" role="search" class="hidden w-64 md:block">
            <label class="block">
                <span class="sr-only">{{ __('customers.comun.buscar_cliente') }}</span>
                <input type="search" name="q" placeholder="{{ __('customers.comun.buscar_cliente') }}"
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

    <main class="mx-auto flex w-full max-w-[1200px] flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <div>
            <h1 class="text-[26px] leading-tight font-extrabold">Hola, {{ auth('admin')->user()->name }}</h1>
            <p class="mt-1.5 text-sm text-muted">Elige el módulo con el que vas a trabajar.</p>
        </div>

        <section aria-label="Hoy" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-ui.stat label="Clientes registrados" :value="number_format($today['customers'])" icon="users" />
            <x-ui.stat label="Altas de hoy" :value="number_format($today['signups'])" icon="user-plus" />
            <x-ui.stat label="Visitas de hoy" :value="number_format($today['visits'])" icon="visit" />
            <x-ui.stat label="Envíos programados" :value="number_format($today['scheduled'])" icon="send" />
        </section>

        <section aria-labelledby="modulos" class="flex flex-col gap-3">
            <h2 id="modulos" class="text-xs font-bold tracking-wider text-faint uppercase">Módulos</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($modules as $mod)
                    @if ($mod['route'])
                        <a href="{{ route($mod['route']) }}"
                           class="group flex flex-col gap-3 rounded-card border border-line bg-surface p-5 transition-colors hover:border-accent/40 hover:bg-surface2">
                            <span class="flex h-11 w-11 items-center justify-center rounded-ctl bg-accent-soft text-accent-fg">
                                <x-icon :name="$mod['icon']" :size="22" />
                            </span>
                            <span class="flex flex-col gap-1">
                                <span class="font-bold group-hover:text-accent-fg">{{ __($mod['lang'] . '.modulo') }}</span>
                                <span class="text-xs leading-relaxed text-muted">{{ $mod['description'] ?? '' }}</span>
                            </span>
                        </a>
                    @else
                        <div class="flex flex-col gap-3 rounded-card border border-dashed border-line bg-surface/60 p-5" aria-disabled="true">
                            <span class="flex h-11 w-11 items-center justify-center rounded-ctl bg-surface2 text-faint">
                                <x-icon :name="$mod['icon']" :size="22" />
                            </span>
                            <span class="flex flex-col gap-1">
                                <span class="flex flex-wrap items-center gap-x-2 gap-y-1 font-bold text-muted">{{ $mod['label'] }} <x-ui.badge tone="outline">Próximamente</x-ui.badge></span>
                                <span class="text-xs leading-relaxed text-faint">{{ $mod['description'] ?? '' }}</span>
                            </span>
                        </div>
                    @endif
                @endforeach
            </div>
        </section>
    </main>
</body>
</html>
