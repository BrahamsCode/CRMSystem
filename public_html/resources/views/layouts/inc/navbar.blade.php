<header class="flex h-14 items-center justify-between border-b border-gray-200 bg-white px-4">
    <div class="flex items-center gap-3">
        <button type="button" @click="sidebarOpen = !sidebarOpen" class="rounded p-2 text-gray-600 hover:bg-gray-100 lg:hidden" aria-label="Menú">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <span class="font-semibold">{{ config('app.name') }}</span>
    </div>

    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
        <button type="button" @click="open = !open" class="flex items-center gap-2 rounded px-2 py-1 text-sm hover:bg-gray-100">
            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 font-semibold text-white">
                {{ strtoupper(substr(auth('admin')->user()->name ?? 'A', 0, 1)) }}
            </span>
            <span class="hidden sm:inline">{{ auth('admin')->user()->name ?? '' }}</span>
        </button>
        <div x-show="open" x-cloak x-transition class="absolute right-0 z-20 mt-2 w-44 rounded-lg bg-white py-1 shadow-lg ring-1 ring-gray-200">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100">Cerrar sesión</button>
            </form>
        </div>
    </div>
</header>
