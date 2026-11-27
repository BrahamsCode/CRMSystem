{{--
    Ventana emergente del legacy (popups de category*/inc/…): mismo <head>, sin cabecera, menú ni pie.
    Mismas secciones que layouts.legacy: title, css, js, content (más jquery, jquery_ui y body_class opcionales).
--}}
@php
    $legacyUi = app(\App\Services\Nucleo\LegacyUi::class);
    $category = $legacyUi->categoryFromUrl();
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ja" xml:lang="ja" dir="ltr">

<head>
@include('layouts.legacy.head')

@yield('css')

@yield('js')

<title>@yield('title')</title>
</head>

<body id="pTop" class="pageCategory{{ $legacyUi->pageCategory($category) }}{{ $__env->hasSection('body_class') ? ' ' . trim($__env->yieldContent('body_class')) : '' }}">

<div id="container">

<div class="wrap{{ $__env->hasSection('wrapClass') ? ' ' . trim($__env->yieldContent('wrapClass')) : '' }}">

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

<!-- / .wrap --></div>

</div>
<!-- / #container -->

</body>
</html>
