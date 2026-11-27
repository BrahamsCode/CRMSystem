{{-- Layout de PRUEBA (S2) mientras S1 no publica layouts.legacy. Mismas secciones que el contrato (CONVENCIONES §4). --}}
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>@yield('title')</title>
@yield('css')
</head>
<body>
<div id="container"><div class="wrap"><div class="main">
<nav data-menu="@yield('menu')"></nav>
@yield('content')
</div></div></div>
@yield('js')
</body>
</html>
