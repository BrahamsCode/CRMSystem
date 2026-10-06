<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('layouts.inc.head')
</head>
<body class="bg-gray-100 text-gray-900 antialiased" x-data="{ sidebarOpen: false }">
    <div class="flex min-h-screen">
        @include('layouts.inc.sidebar')

        <div class="flex min-w-0 flex-1 flex-col">
            @include('layouts.inc.navbar')

            <main class="flex-1 p-6">
                @yield('content')
            </main>

            @include('layouts.inc.foot')
        </div>
    </div>
</body>
</html>
