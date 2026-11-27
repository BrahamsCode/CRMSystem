{{--
    経費管理 (legacy: system/category1/karute_menu/keihi.php).

    Datos:
      $expense          Expense|null   en edición (?cd=)
      $editing          bool           llegó ?cd= (el legacy muestra «この内容で修正する» aunque no exista)
      $date             string         使用日 (hoy por defecto)
      $isMaster         bool           master: <select> de tienda; resto: su tienda en texto + hidden
      $shops            Collection<Shop>
      $sessionShop      int|null
      $sessionShopName  string
      $page             array          LegacyPageControl (20 por página): items = Collection<Expense>

    El legacy enlazaba ./css/staffTotal.css (no existe con esa mayúscula): no se carga.
--}}
@extends('layouts.legacy')

@section('menu', 'clientes')

@section('title', '経費管理 - ' . ($siteName ?? ''))

@section('css')
<link href="/system/css/jquery.alerts.css" rel="stylesheet" type="text/css" media="all" />
@endsection

@section('js')
<script type="text/javascript" src="/system/plugin/ui-datepicker/ui.datepicker-ja.js"></script>
<script type="text/javascript" src="/system/js/jquery.alerts.js"></script>
<script type="text/javascript">
<!--
$(document).ready(function() {

	$(".ac4").accordion('option', 'active', 0);

	$("#date1").datepicker({
		dateFormat:"yy/mm/dd"
	});

});

function submitChk() {

	var errorMess = "";
	var errorFlg = 0;

	if ($('#shop').val() == '') {

		errorMess += '・店舗を選択してください。\n';
		errorFlg = 1;

	}

	var price = parseInt($('#price').val());
	if (isNaN(price)) price = 0;
	if (price <= 0) {

		errorMess += '・使用金額を入力してください。';
		errorFlg = 1;

	}

	if(errorFlg == 1){
		jAlert(errorMess,'入力エラー');
		return false;
	}else{
		jConfirm (
			"この内容で登録してもよろしいですか？\n書き直す場合は['キャンセル']ボタンを押して下さい。",
			"登録確認",
			 function(ret){

				if (ret == false) {
					return false;
				} else {
					document.form1.submit();
				}

		});
	}

	return false;

} // function submitChk () --- END

function submitChk2(val) {
		jConfirm (
			"削除してもよろしいですか？\n\n削除しない場合は['キャンセル']ボタンを押して下さい",
			"削除確認",
			 function(ret){

				if (ret == false) {
					return false;
				} else {
					document.form_delete.cd.value = val;
					document.form_delete.submit();
				}

		});
} // function submitChk2 () --- END

@include('admin.ventas.partials.numbers-only-js')
-->
</script>
@endsection

@section('content')
<div class="contents">

<p class="h2Title">■経費管理</p>

<p>使用した経費の情報を入力してください</p>

<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.ventas.expenses.store') }}" onsubmit="return submitChk()">
@csrf


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
@foreach ($shops as $shop)
			<option value="{{ $shop->cd }}" @selected((string) $expense?->int_data === (string) $shop->cd)>{{ $shop->kaishamei }}</option>
@endforeach
			</select>
@endif
		</td>
	</tr>

	<tr>
		<th>使用日</th>
		<td>
			<input type="text" name="date1" value="{{ $date }}" id="date1" class="inputM" readonly="readonly" />
		</td>
	</tr>

	<tr>
		<th>使用目的</th>
		<td><input type="text" name="mokuteki" id="mokuteki" class="inputL" value="{{ $expense?->text_data }}" /></td>
	</tr>

	<tr>
		<th>使用金額</th>
		<td><input type="text" name="price" id="price" class="inputM" value="{{ $expense ? (int) $expense->float_data : '' }}" onKeyPress="return numbersonly(this, event)" onkeyup="replace_value(this)" onmouseover="replace_value(this)" onchange="replace_value(this)" style="ime-mode:disabled" />円</td>
	</tr>
</table>

</fieldset>

<div class="btnBox">
@if ($editing)
<input type="submit" name="enter" id="revise" value="この内容で修正する" />
<input type="hidden" name="act" id="act" value="up" />
<input type="hidden" name="cd" id="cd" value="{{ request('cd') }}" />
@else
<input type="submit" name="enter" id="reivse" value="この内容で登録する" />
<input type="hidden" name="act" id="act" value="new" />
@endif
</div>

</form>

<p class="h2Title">■経費一覧</p>

@if ($page['total'] > 0)
<table class="tableBox2">
	<tr>
		<th>使用日</th>
		<th>使用目的</th>
		<th>使用金額</th>
		@can('ventas.update')<th align="center">修正</th>@endcan
		@can('ventas.delete')<th align="center">削除</th>@endcan
	</tr>

@foreach ($page['items'] as $row => $item)
	<tr @if ($row % 2 != 0) class="tableBg" @endif>
		<td align="center">{{ $item->update_date }}</td>
		<td align="center">{{ $item->text_data }}</td>
		<td align="right">{{ number_format((float) $item->float_data) }}</td>
		@can('ventas.update')
		<td align="top" class="textCenter" width="100">
			<a href="{{ route('admin.ventas.expenses.index', ['cd' => $item->cd]) }}"><img src="/system/img/btn_revise_off.gif" width="40" height="20" alt="修正" /></a>
		</td>
		@endcan
		@can('ventas.delete')
		<td align="top" class="textCenter" width="100">
			<a href="javascript:void(0)" onclick="submitChk2('{{ $item->cd }}'); return false;"><img src="/system/img/btn_delete_off.gif" width="40" height="20" alt="削除" /></a>
		</td>
		@endcan
	</tr>
@endforeach

</table>

<form method="post" name="form_delete" id="form_delete" class="displayNone" action="{{ route('admin.ventas.expenses.destroy') }}">
@csrf
<input type="hidden" name="delete_flg" id="delete" class="btn" value="del">
<input type="hidden" name="cd" class="btn" value="">
<input type="submit" name="delete" id="delete" class="btn" value="削除する">
</form>

<ul class="btnBox2">
@include('admin.ventas.partials.page-control', ['pageUrl' => route('admin.ventas.expenses.index'), 'query' => []])
</ul>

@else
<p style="text-align:center;font-size: 120%;color:#FF0000;font-weight:bold;">データがありません。</p>
@endif


<!-- / .contents --></div>
@endsection
