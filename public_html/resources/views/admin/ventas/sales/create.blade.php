{{--
    売上データ作成 (legacy: system/category1/karute_menu/karute_new.php): selector de tienda (master) y botón
    que abre el formulario (karute_new_frm.php) en otra ventana.

    Datos: $shopCd (int|null), $shops (Collection<Shop>), $isMaster (bool)
--}}
@extends('layouts.legacy')

@section('menu', 'clientes')

@section('title', '売上データ作成 - ' . ($siteName ?? ''))

@section('css')
<link href="{{ asset('legacy/system/category1/karute_menu/css/karute_frm.css') }}" rel="stylesheet" type="text/css" media="all" />
<link href="/system/css/jquery.alerts.css" rel="stylesheet" type="text/css" media="all" />
<link href="/system/css/colorbox.css" rel="stylesheet" type="text/css" media="all" />
@endsection

@section('js')
<script type="text/javascript" src="/system/js/jquery.alerts.js"></script>
<script type="text/javascript" src="/system/js/jquery.colorbox.js"></script>
<script type="text/javascript">
<!--
$(document).ready(function() {

	$(".ac4").accordion('option', 'active', 0);

	//店舗選択
	$("#shop").change(function(){
		document.shop_select.submit();
	});

});
-->
</script>
@endsection

@section('content')
<div class="contents">

<p class="h2Title">■売上データ作成</p>
@include('admin.ventas.partials.shop-select', ['action' => route('admin.ventas.sales.create')])

@if ($shopCd)

<div id="karteForm">
<input type="button" onclick="window.open('{{ route('admin.ventas.sales.form', ['shop' => $shopCd]) }}', '売上データ作成', 'menubar=no, toolbar=no, scrollbars=yes');" name="karteFormOpen" id="karteFormOpen" class="btn btnStyleM" value="　　売上データ作成フォームを開く　　" />
<!--■□■ /#karteForm ■□■--></div>

@endif
<!-- / .contents --></div>
@endsection
