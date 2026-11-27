{{--
    Legacy: system/category1/karute_menu/report.php (日報・週報・月報). El resultado lo carga ajax_report.php.
    Variables:
      $isMaster         bool              rank master: selector de tiendas (con 全店舗 = -1, elegido por defecto)
      $shops            Collection<Shop>  opciones de make_shop_options(-1, true) (solo master)
      $sessionShopCd    ?int              tienda de la sesión (no master)
      $sessionShopName  ?string           su kaishamei
--}}
@extends('layouts.legacy')

@section('title', '日報・週報・月報 - ' . $siteName)

@section('menu', 'clientes')

@section('css')
<link href="{{ asset('legacy/system/category1/karute_menu/css/report.css') }}" rel="stylesheet" type="text/css" media="all" />
<link href="{{ asset('legacy/system/css/jquery.alerts.css') }}" rel="stylesheet" type="text/css" media="all" />
<link href="{{ asset('legacy/system/plugin/ui-datepicker/ui.datepicker.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('js')
<script type="text/javascript" src="{{ asset('legacy/system/plugin/ui-datepicker/ui.datepicker-ja.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/js/jquery.alerts.js') }}"></script>

<script type="text/javascript">
<!--

$(document).ready(function() {

	$(".ac4").accordion('option', 'active', 0);

	$.ajaxSetup({async: false}); //非同期をfalseにする。

	$("#enter").click(function(){
		getResults();
	});

	$("#date1").datepicker({
		onSelect: function( selectedDate ) {
			var date = $.datepicker.parseDate("yy/mm/dd", selectedDate);
			$( "#date2" ).datepicker( "option", "minDate", date );
			date = new Date(date.getTime() + 60 * 24 * 60 * 60000);
			$( "#date2" ).datepicker( "option", "maxDate", date );
		}
	});

	$("#date2").datepicker({
		onSelect: function( selectedDate ) {
			var date = $.datepicker.parseDate("yy/mm/dd", selectedDate);
			$( "#date1" ).datepicker( "option", "maxDate", date );
			date = new Date(date.getTime() - 60 * 24 * 60 * 60000);
			$( "#date1" ).datepicker( "option", "minDate", date );
		}
	});

});

{{-- El legacy pone onsubmit="return submitChk()" en el formulario sin definir submitChk: el formulario no se envía nunca (el botón es type="button") --}}
function submitChk() {
	return false;
}

function getResults() {
		shopCD = $("#shop").val();
		date1 = $("#date1").val();
		date2 = $("#date2").val();
		if (shopCD=='') {
			return;
		} else {
			$("#result").html('<p style="text-align:center;"><img src="{{ asset('legacy/system/img/loading.gif') }}" alt="削除中です。" /></p>');
			$("#result").load("{{ route('legacy.ventas.report') }}",{
				shop_cd:shopCD,
				date1:date1,
				date2:date2
			});
		}
}

-->
</script>
@endsection

@section('content')
<div class="contents">

<p class="h2Title">■日報・週報・月報</p>

<form method="post" name="form1" id="form1" class="form" action="" onsubmit="return submitChk()">

<fieldset>

<table class="tableBox">
	<tr>
		<th style="width:25%">集計を行うスタッフ</th>
		<td>
@if (! $isMaster)
			{{ $sessionShopName }}<input type="hidden" name="shop" id="shop" value="{{ $sessionShopCd }}" />
@else
			<select name="shop" id="shop">
			@include('admin.ventas.partials.shop_options', ['selected' => -1, 'allShops' => true])
			</select>
@endif
		</td>
	</tr>

	<tr>
		<th><label for="data1">集計期間</label><br/><span class="attention">(最大60日分)</span></th>
		<td>
			<input type="text" name="date1" value="" id="date1" class="inputM" />&nbsp;～&nbsp;<input type="text" name="date2" value="" id="date2" class="inputM" />
			<br />例)2012/01/01～2012/02/29
		</td>
	</tr>
</table>

</fieldset>

<div class="btnBox">
<input type="button" name="enter" id="enter" class="btn btnStyleM" value="検索する" />
</div>

</form>

<div id="result">
</div>

<!-- / .contents --></div>
@endsection
