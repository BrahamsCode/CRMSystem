{{--
    Legacy: system/category1/karute_menu/karute_new.php (売上データ作成).
    Variables:
      $shopCd    ?int              tienda (master: ?shop=; si no, la de la sesión); null = sin elegir
      $isMaster  bool              rank master: selector de tiendas
      $shops     Collection<Shop>  opciones de make_shop_options() (solo master)
--}}
@extends('layouts.legacy')

@section('title', '売上データ作成 - ' . $siteName)

@section('menu', 'clientes')

@section('css')
<link href="{{ asset('legacy/system/category1/karute_menu/css/karute_frm.css') }}" rel="stylesheet" type="text/css" media="all" />
<link href="{{ asset('legacy/system/css/jquery.alerts.css') }}" rel="stylesheet" type="text/css" media="all" />
<link href="{{ asset('legacy/system/css/colorbox.css') }}" rel="stylesheet" type="text/css" media="all" />
@endsection

@section('js')
<script type="text/javascript" src="{{ asset('legacy/system/js/jquery.alerts.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/js/jquery.colorbox.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/js/ajaxupload-3.5.js') }}"></script>

<script type="text/javascript">
<!--

$(document).ready(function() {

	$(".ac4").accordion('option', 'active', 0);

	//店舗選択 -------------------------------------------------------------------------------

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
@if (! $isMaster)
&nbsp;<br />
@else
<form action="{{ route('admin.ventas.sales.create') }}" method="get" name="shop_select">
<select name="shop" id="shop" style="margin-bottom: 10px; padding: 1px; border: 1px solid #7F9DB9;">
@include('admin.ventas.partials.shop_options', ['selected' => $shopCd])
</select>&emsp;
※登録・閲覧したい店舗を選択してください。
</form>
@endif

@if ($shopCd)
<div id="karteForm">
<input type="button" onclick="window.open('{{ route('admin.ventas.sales.form', ['shop' => $shopCd]) }}', '売上データ作成', 'menubar=no, toolbar=no, scrollbars=yes');" name="karteFormOpen" id="karteFormOpen" class="btn btnStyleM" value="　　売上データ作成フォームを開く　　" />
<!--■□■ /#karteForm ■□■--></div>
@endif
<!-- / .contents --></div>
@endsection
