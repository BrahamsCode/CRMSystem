{{--
    Legacy: system/category1/karute_menu/karute_delete_list.php (売上追加・修正・削除 / ■売上データ削除).
    Variables:
      $shopCd        ?int                     tienda (master: ?shop=; si no, la de la sesión)
      $shopName      ?string                  tb_shop.kaishamei de $shopCd
      $isMaster      bool                     rank master: selector de tiendas
      $shops         Collection<Shop>         opciones de make_shop_options() (solo master)
      $staffOptions  Collection<Staff>        スタッフ de la tienda (disp_flg 1, orden rank, cd)
      $filters       array{date1:string, date2:string, staff:string}  condiciones de búsqueda
      $paginator     ?LengthAwarePaginator    filas 合計 con staff_name, id y name del cliente (null sin tienda)
--}}
@extends('layouts.legacy')

@section('title', '売上追加・修正・削除')

@section('menu', 'clientes')

@section('css')
<link href="{{ asset('legacy/system/category1/karute_menu/css/karute_frm.css') }}" rel="stylesheet" type="text/css" media="all" />
<link href="{{ asset('legacy/system/css/jquery.alerts.css') }}" rel="stylesheet" type="text/css" media="all" />
<link href="{{ asset('legacy/system/css/colorbox.css') }}" rel="stylesheet" type="text/css" media="all" />
<link rel="stylesheet" type="text/css" href="{{ asset('legacy/system/plugin/ui-datepicker/ui.datepicker.css') }}" />
@endsection

@section('js')
<script type="text/javascript" src="{{ asset('legacy/system/plugin/ui-datepicker/ui.datepicker-ja.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/plugin/ui-datepicker/gcalendar-holidays.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/js/jquery.alerts.js') }}"></script>

<script type="text/javascript">
<!--

$(document).ready(function() {

	$(".ac4").accordion('option', 'active', 0);

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

	// 店舗選択 ------------------------------------------------------------------------------

	$("#shop").change(function(){

		document.shop_select.submit();

	});

});

// =======================================================================================
// 削除ボタン (el legacy iba por GET a karute_delete_register.php; aquí POST con el mismo contenido)
// =======================================================================================

function deleteHandler( customerCD, innerGroup ) {

	jConfirm("削除してもよろしいですか？\n削除しない場合は['キャンセル']ボタンを押して下さい","削除確認",function(ret){

		if (ret) {
			document.form_delete.cd.value = customerCD;
			document.form_delete.group.value = innerGroup;
			document.form_delete.submit();
		} else {
			return false;
		}

	});

} // END --- function deleteHandler( customerCD, innerGroup )

-->
</script>
@endsection

@section('content')
<div>
<p class="h2Title">■売上データ削除</p>
@if (! $isMaster)
&nbsp;<br />
@else
<form action="{{ route('admin.ventas.sales.index') }}" method="get" name="shop_select">
<select name="shop" id="shop" style="margin-bottom: 10px; padding: 1px; border: 1px solid #7F9DB9;">
@include('admin.ventas.partials.shop_options', ['selected' => $shopCd])
</select>&emsp;
※登録・閲覧したい店舗を選択してください。
</form>
@endif
</div>

@if ($shopCd)
<div class="contents">

<p class="h3Title">■{{ $shopCd === -1 ? '全店舗用' : $shopName }}</p>

<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.ventas.sales.index') }}">
@csrf
<input type="hidden" name="shop" value="{{ $shopCd }}" />

<fieldset>
<legend>検索条件</legend>

<table class="tableBox">

	<tr>
	<th style="width:15%"><label for="data1">登録日時</label></th>
	<td><input type="text" name="date1" id="date1" class="inputM" value="{{ $filters['date1'] }}" />
	&nbsp;～&nbsp;
	<input type="text" name="date2" id="date2" class="inputM" value="{{ $filters['date2'] }}" />
	<br />例)2012/01/01～2012/02/29
	</td>
	</tr>

	<tr>
	<th style="width:15%"><label for="shop">スタッフ名</label></th>
	<td>
	<select name="staff" id="staff" style="padding: 1px; border: 1px solid #7F9DB9;">
	<option value="">▼スタッフ選択</option>
	@foreach ($staffOptions as $staff)
	<option value="{{ $staff->cd }}"@if ((string) $staff->cd === $filters['staff']) selected="selected"@endif>{{ $staff->name }}</option>
	@endforeach
	</select>
	</td>
	</tr>

</table>

</fieldset>

<div class="btnBox">
<input type="submit" name="search" id="search" class="btn btnStyleM" value="この条件で検索する" />
</div>

</form>

<div id="result"> </div>

<!-- / .contents --></div>

<div class="contents">

<p class="h2Title">■検索結果</p>

<div class="form">

@if ($paginator && $paginator->count() > 0)
<table class="tableBox2">

<tr>
<th style="width: 8.5em;">登録日時</th>
<th style="width: 5.5em;">顧客ID</th>
<th>顧客名</th>
<th style="width: 8.5em;">金額</th>
<th>スタッフ名</th>
<th style="width: 4em;">&nbsp;</th>
</tr>

@foreach ($paginator as $i => $row)
<tr @if ($i % 2 == 0) class="tableBg" @endif>
<td>{{ \Illuminate\Support\Carbon::createFromTimestamp((int) $row->touroku_date, 'Asia/Tokyo')->format('Y/m/d h:i') }}</td>
<td>{{ $row->id }}</td>
<td>{{ $row->name }}</td>
<td>{{ number_format((float) $row->float_data) }}円</td>
<td>{{ $row->staff_name }}</td>
<td align="center">
<input class="btn btnStyle" type="button" value="削除" onclick="deleteHandler({{ (int) $row->tb_customer_cd }},{{ (int) $row->category_inner_group }})" /></td>
</tr>
@endforeach

</table>

<ul class="btnBox2">
@include('admin.ventas.partials.page_control', [
    'pageUrl' => fn (int $p) => route('admin.ventas.sales.index', [
        'page' => $p, 'shop' => $shopCd, 'date1' => $filters['date1'], 'date2' => $filters['date2'],
        'staff' => $filters['staff'], 'kensu' => $paginator->perPage(),
    ]),
])
</ul>

<form method="post" name="form_delete" id="form_delete" class="displayNone" action="{{ route('admin.ventas.sales.destroy') }}">
@csrf
<input type="hidden" name="act" value="del" />
<input type="hidden" name="shop" value="{{ $shopCd }}" />
<input type="hidden" name="staff" value="{{ $filters['staff'] }}" />
<input type="hidden" name="date1" value="{{ $filters['date1'] }}" />
<input type="hidden" name="date2" value="{{ $filters['date2'] }}" />
<input type="hidden" name="cd" value="" />
<input type="hidden" name="group" value="" />
</form>

@else
<div>履歴がありません。</div>
@endif

</div>

<!-- / .contents --></div>
@endif
@endsection
