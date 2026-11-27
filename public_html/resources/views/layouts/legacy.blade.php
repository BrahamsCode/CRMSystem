{{--
    Layout del sistema del personal, idéntico al legacy (inc/head.php + header.php + left_navi<N>.php + footer.php).
    Contrato (CONVENCIONES §4, NUCLEO.md):
      @extends('layouts.legacy')
      @section('title', '顧客検索 -総合業務管理システム-')   título completo, como el <title> del legacy
      @section('menu', 'clientes')                         menú lateral: config/menu/clientes.php (sin menú = una columna)
      @section('css') … @endsection                        <link>/<style> propios de la pantalla
      @section('js') … @endsection                         <script> propios (después de jQuery y del CSRF)
      @section('content') … @endsection                    dentro de <div class="main">
    Opcionales: @section('jquery', 'jquery-1.11.2.min.js'), @section('jquery_ui', 'jquery-ui-1.11.4.min.js'),
    @section('body_class', '…') (se agrega a pageCategory<N>), @section('wrapClass', 'customerDetail') (se agrega a .wrap).
--}}
@php
    $legacyUi = app(\App\Services\Nucleo\LegacyUi::class);
    // @section('ui_version', '1'|'2'): fuerza la UI clásica o la 2 solo en esta pantalla (NUCLEO.md §5)
    $legacyUi->forceUiVersion(trim($__env->yieldContent('ui_version')) ?: null);
    $staff = auth('admin')->user();
    $menuKey = trim($__env->yieldContent('menu'));
    $menu = $menuKey !== '' ? $legacyUi->menu($menuKey, $staff) : null;
    $category = $legacyUi->categoryFromUrl() ?? ($menu['category'] ?? null);
    $isTopPage = $__env->hasSection('top_page');
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ja" xml:lang="ja" dir="ltr">

<head>
@include('layouts.legacy.head')

@yield('css')

@if ($menu)
{{-- Acordeones del menú lateral (inc/ac.php); se abre el bloque que contiene la página actual --}}
<script type="text/javascript">
$(document).ready(function() {
	var acs = [1,2,3,4,5,6,7,8,9,10,11,12,13,14,109,110,111];
	for (var i = 0; i < acs.length; i++) {
		$(".ac" + acs[i]).accordion({ collapsible: true , active: false});
	}
	var path = location.pathname;
	$(".leftNavi dl.accordionBlock a").each(function() {
		if (this.pathname === path || '/' + this.pathname === path) {
			$(this).parents("dl.accordionBlock").accordion('option', 'active', 0);
		}
	});
});
</script>
@endif

@yield('js')

<title>@yield('title')</title>
</head>

<body id="pTop" class="pageCategory{{ $legacyUi->pageCategory($category) }}{{ $__env->hasSection('body_class') ? ' ' . trim($__env->yieldContent('body_class')) : '' }}">

<div id="container">

@include('layouts.legacy.header')


<div class="wrap{{ $menu ? '' : ' oneColumn' }}{{ $__env->hasSection('wrapClass') ? ' ' . trim($__env->yieldContent('wrapClass')) : '' }}">

<div class="main1">
<div class="main2">
<div class="main3">
<div class="main4">
<div class="main5">
<div class="main6">
<div class="main7">

<div class="main">

@yield('content')

<!-- / .main --></div>

<!-- / .main7 --></div>
<!-- / .main6 --></div>
<!-- / .main5 --></div>
<!-- / .main4 --></div>
<!-- / .main3 --></div>
<!-- / .main2 --></div>
<!-- / .main1 --></div>

@if ($menu)
@include('layouts.legacy.left-navi')
@endif

<!-- / .wrap --></div>


@include('layouts.legacy.footer')

</div><!-- / #container -->

</body>
</html>
