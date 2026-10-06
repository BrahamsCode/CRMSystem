<div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 z-30 bg-black/40 lg:hidden"></div>

<aside class="fixed inset-y-0 left-0 z-40 w-60 -translate-x-full transform border-r border-gray-200 bg-white transition-transform lg:static lg:translate-x-0"
       :class="{ 'translate-x-0': sidebarOpen }">
    <div class="flex h-14 items-center border-b border-gray-200 px-4 font-semibold">{{ config('app.name') }}</div>
    <nav class="space-y-1 p-3 text-sm">
        <a href="{{ route('admin.home') }}"
           class="block rounded-lg px-3 py-2 {{ request()->routeIs('admin.home') ? 'bg-indigo-50 font-medium text-indigo-700' : 'text-gray-700 hover:bg-gray-100' }}">
            Inicio
        </a>
    </nav>
</aside>
