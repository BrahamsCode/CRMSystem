{{--
    Legacy: system/category1/karute_menu/staff_total.php (スタッフ別集計). El resultado lo carga ajax_staffTotal.php.
    Variables:
      $isMaster         bool              rank master: selector de tiendas
      $shops            Collection<Shop>  opciones de make_shop_options() (solo master)
      $sessionShopCd    ?int              tienda de la sesión (no master)
      $sessionShopName  ?string           su kaishamei
--}}
@extends('layouts.legacy')

@section('title', 'スタッフ別集計 - ' . $siteName)

@section('menu', 'clientes')

@section('css')
<link href="{{ asset('legacy/system/category1/karute_menu/css/stafftotal.css') }}" rel="stylesheet" type="text/css" media="all" />
<link href="{{ asset('legacy/system/css/jquery.alerts.css') }}" rel="stylesheet" type="text/css" media="all" />
<link href="{{ asset('legacy/system/plugin/ui-datepicker/ui.datepicker.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('js')
<script type="text/javascript" src="{{ asset('legacy/system/plugin/ui-datepicker/ui.datepicker-ja.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/js/jquery.alerts.js') }}"></script>

<script type="text/javascript">
<!--

var t;
$(document).ready(function() {

	$(".ac4").accordion('option', 'active', 0);

	$.ajaxSetup({async: false}); //非同期をfalseにする。

	$("#shop").change(function(){
		shop_cd = $("#shop").val();
		searchStaff(shop_cd);
	});

	$("#enter").click(function(){
		searchStaffTotal();
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
		dateFormat: "yy/mm/dd",
		onSelect: function( selectedDate ) {
			var date = $.datepicker.parseDate("yy/mm/dd", selectedDate);
			$( "#date1" ).datepicker( "option", "maxDate", date );
			date = new Date(date.getTime() - 60 * 24 * 60 * 60000);
			$( "#date1" ).datepicker( "option", "minDate", date );
		}
	});

@if (! $isMaster)
	searchStaff('{{ $sessionShopCd }}');
@endif

});

function searchStaff(shopCD) {
		if (shopCD=='') {
			$("#staff").html('<option value="">店舗を選択して下さい</option>');
			return;
		} else {
			$("#staff").html('<option value="">Loading...</option>');
			$("#staff").load("{{ route('legacy.ventas.staff-options') }}",{
				shop_cd:shopCD
			});
		}
}

function searchStaffTotal() {
		shopCD = $("#shop").val();
		staffCd = $("#staff").val();
		date1 = $("#date1").val();
		date2 = $("#date2").val();
		if (shopCD=='' || staffCd=='') {
			return;
		} else {
			$("#result").load("{{ route('legacy.ventas.staff-total') }}",{
				shop_cd:shopCD,
				staff_cd:staffCd,
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

<p class="h2Title">■スタッフ別集計</p>

<form action="{{ route('admin.ventas.reports.staff') }}" method="get" name="form1" class="form">

<div class="staffTotalSearchArea">

<table class="staffTotalSearch tableBox">

	<tr>
		<th style="width:25%">集計を行うスタッフ</th>
		<td>
@if (! $isMaster)
			{{ $sessionShopName }}<input type="hidden" name="shop" id="shop" value="{{ $sessionShopCd }}" />
@else
			<select name="shop" id="shop">
			@include('admin.ventas.partials.shop_options', ['selected' => null])
			</select>
@endif
			&emsp;&emsp;
			<select name="staff" id="staff">
				<option value="">店舗を選択して下さい</option>
			</select>
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

<div class="btnBox">
<input id="enter" class="btn btnStyleM" type="button" value="この条件で検索する" name="enter">
<!-- /.btnBox --></div>

<!-- /.staffTotalSearchArea --></div>

</form>

<div id="result" class="staffTotalResultArea">
</div>

<!-- / .contents --></div>
@endsection
