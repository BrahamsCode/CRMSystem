{{--
    日報・週報・月報 (legacy: system/category1/karute_menu/report.php). El resultado llega por AJAX
    (ajax_report.php, en su URL del legacy).

    Datos: $isMaster (bool; master elige tienda o 全店舗 = -1), $shops (Collection<Shop>),
           $sessionShop (int|null), $sessionShopName (string)
--}}
@extends('layouts.legacy')

@section('menu', 'clientes')

@section('title', '日報・週報・月報 - ' . ($siteName ?? ''))

@section('css')
<link href="{{ asset('legacy/system/category1/karute_menu/css/report.css') }}" rel="stylesheet" type="text/css" media="all" />
<link href="/system/css/jquery.alerts.css" rel="stylesheet" type="text/css" media="all" />
@endsection

@section('js')
<script type="text/javascript" src="/system/plugin/ui-datepicker/ui.datepicker-ja.js"></script>
<script type="text/javascript" src="/system/js/jquery.alerts.js"></script>
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

function getResults() {
		shopCD = $("#shop").val();
		date1 = $("#date1").val();
		date2 = $("#date2").val();
		if (shopCD=='') {
			return;
		} else {
			$("#result").html('<p style="text-align:center;"><img src="/system/img/loading.gif" alt="削除中です。" /></p>');
			$("#result").load("{{ route('legacy.ventas.ajax-report', [], false) }}",{
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

<form method="post" name="form1" id="form1" class="form" action="" onsubmit="return false">

<fieldset>
<table class="tableBox">
	<tr>
		<th style="width:25%">集計を行うスタッフ</th>
		<td>
@if (! $isMaster)
			{{ $sessionShopName }}<input type="hidden" name="shop" id="shop" value="{{ $sessionShop }}" />
@else
			<select name="shop" id="shop">
			<option value="">▼店舗選択</option>
			<option value="-1" selected="selected">全店舗</option>
@foreach ($shops as $shop)
			<option value="{{ $shop->cd }}">{{ $shop->kaishamei }}</option>
@endforeach
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
