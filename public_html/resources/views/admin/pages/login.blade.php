<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100 text-gray-900 antialiased">
    <main class="flex min-h-screen items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <div class="mb-8 text-center">
                <h1 class="text-2xl font-bold tracking-tight">{{ config('app.name') }}</h1>
                <p class="mt-1 text-sm text-gray-500">Inicia sesión para continuar</p>
            </div>

            <div class="rounded-2xl bg-white p-8 shadow-lg ring-1 ring-gray-200">
                @if (session('status'))
                    <div class="mb-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-5" x-data="{ show: false }">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Correo electrónico</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}"
                               required autofocus autocomplete="username"
                               class="mt-1 block w-full rounded-lg border px-3 py-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('email') border-red-400 @else border-gray-300 @enderror">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Contraseña</label>
                        <div class="relative mt-1">
                            <input id="password" name="password" x-bind:type="show ? 'text' : 'password'"
                                   required autocomplete="current-password"
                                   class="block w-full rounded-lg border border-gray-300 px-3 py-2 pr-16 shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <button type="button" @click="show = !show"
                                    class="absolute inset-y-0 right-0 px-3 text-sm text-gray-500 hover:text-gray-700"
                                    x-text="show ? 'Ocultar' : 'Mostrar'"></button>
                        </div>
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 text-sm text-gray-600">
                            <input type="checkbox" name="remember" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            Recordarme
                        </label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-sm text-indigo-600 hover:underline">
                                ¿Olvidaste tu contraseña?
                            </a>
                        @endif
                    </div>

                    <button type="submit"
                            class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 font-semibold text-white shadow hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Iniciar sesión
                    </button>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
