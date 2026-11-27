{{--
    売上データ削除 (legacy: system/category1/karute_menu/karute_delete_list.php).

    Datos:
      $shopCd    int|null
      $shopName  string
      $shops     Collection<Shop>
      $isMaster  bool
      $staff     Collection<Staff>   スタッフ名 del buscador (tienda elegida)
      $filters   array{shop, date1, date2, staff}
      $page      array|null          LegacyPageControl: items = filas 合計 con staff_name, customer_id, customer_name

    Cambios: el borrado va por POST (el legacy hacía GET a karute_delete_register.php) y la hora del
    登録日時 sale en 24 h (el legacy usaba date('Y/m/d h:i'), sin AM/PM).
--}}
@extends('layouts.legacy')

@section('menu', 'clientes')

@section('title', '売上追加・修正・削除')

@section('css')
<link href="{{ asset('legacy/system/category1/karute_menu/css/karute_frm.css') }}" rel="stylesheet" type="text/css" media="all" />
<link href="/system/css/jquery.alerts.css" rel="stylesheet" type="text/css" media="all" />
<link href="/system/css/colorbox.css" rel="stylesheet" type="text/css" media="all" />
@endsection

@section('js')
<script type="text/javascript" src="/system/plugin/ui-datepicker/ui.datepicker-ja.js"></script>
<script type="text/javascript" src="/system/plugin/ui-datepicker/gcalendar-holidays.js"></script>
<script type="text/javascript" src="/system/js/jquery.alerts.js"></script>
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

	// 店舗選択
	$("#shop").change(function(){
		document.shop_select.submit();
	});

});

// 削除ボタン
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
@include('admin.ventas.partials.shop-select', ['action' => route('admin.ventas.sales.index')])
</div>

@if ($shopCd)
<div class="contents">

<p class="h3Title">■{{ $shopName }}</p>


<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.ventas.sales.index') }}">
@csrf
<input type="hidden" name="shop" value="{{ $filters['shop'] }}" />


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
	@foreach ($staff as $member)
	<option value="{{ $member->cd }}" @selected((string) $member->cd === $filters['staff'])>{{ $member->name }}</option>
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

@if ($page && $page['total'] > 0)
<table class="tableBox2">

<tr>
<th style="width: 8.5em;">登録日時</th>
<th style="width: 5.5em;">顧客ID</th>
<th>顧客名</th>
<th style="width: 8.5em;">金額</th>
<th>スタッフ名</th>
<th style="width: 4em;">&nbsp;</th>
</tr>

@foreach ($page['items'] as $i => $row)
<tr @if ($i % 2 == 0) class="tableBg" @endif>
<td>{{ date('Y/m/d H:i', (int) $row->touroku_date) }}</td>
<td>{{ $row->customer_id }}</td>
<td>{{ $row->customer_name }}</td>
<td>{{ number_format((float) $row->float_data) }}円</td>
<td>{{ $row->staff_name }}</td>
<td align="center">
<input class="btn btnStyle" type="button" value="削除" onclick="deleteHandler({{ (int) $row->tb_customer_cd }},{{ (int) $row->category_inner_group }})" /></td>
</tr>
@endforeach

</table>

<form method="post" name="form_delete" id="form_delete" class="displayNone" action="{{ route('admin.ventas.sales.destroy') }}">
@csrf
<input type="hidden" name="act" value="del" />
<input type="hidden" name="cd" value="" />
<input type="hidden" name="group" value="" />
<input type="hidden" name="shop" value="{{ $filters['shop'] }}" />
<input type="hidden" name="staff" value="{{ $filters['staff'] }}" />
<input type="hidden" name="date1" value="{{ $filters['date1'] }}" />
<input type="hidden" name="date2" value="{{ $filters['date2'] }}" />
</form>

<ul class="btnBox2">
@include('admin.ventas.partials.page-control', ['pageUrl' => route('admin.ventas.sales.index'), 'query' => $filters])
</ul>

@else
<div>履歴がありません。</div>
@endif


</div>

<!-- / .contents --></div>
@endif
@endsection
