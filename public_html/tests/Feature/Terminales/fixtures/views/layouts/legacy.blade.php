{{-- Layout mínimo SOLO para pruebas de S4 mientras no exista el layouts.legacy de S1. Mismas secciones del contrato. --}}
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title')</title>
@yield('css')
@yield('js')
</head>
<body data-menu="@yield('menu')">
<div class="main">
@yield('content')
</div>
</body>
</html>
