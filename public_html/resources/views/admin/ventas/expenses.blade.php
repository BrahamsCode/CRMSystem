{{--
    Legacy: system/category1/karute_menu/keihi.php (経費管理).
    Variables:
      $expense          ?object               fila de tb_customer_sub (form -10) que se edita (?cd=) o null en el alta
      $date             string                使用日 'Y/m/d' (la del gasto o la de hoy)
      $isMaster         bool                  rank master: selector de tiendas
      $shops            Collection<Shop>      opciones de make_shop_options() (solo master)
      $sessionShopCd    ?int                  tienda de la sesión (no master)
      $sessionShopName  ?string               su kaishamei
      $paginator        LengthAwarePaginator  経費一覧 (filas de tb_customer_sub)
--}}
@extends('layouts.legacy')

@section('title', '経費管理 - ' . $siteName)

@section('menu', 'clientes')

@section('css')
{{-- El legacy pedía ./css/staffTotal.css (con mayúscula: no existe en un servidor Linux); el archivo es stafftotal.css --}}
<link href="{{ asset('legacy/system/category1/karute_menu/css/stafftotal.css') }}" rel="stylesheet" type="text/css" media="all" />
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
					var form = document.form_delete;
					form.action = "{{ route('admin.ventas.expenses.destroy', '__CD__') }}".replace('__CD__', val);
					form.cd.value = val;
					form.submit();
				}

		});
} // function submitChk2 () --- END

@include('admin.ventas.partials.numbersonly')

-->
</script>
@endsection

@section('content')
<div class="contents">

<p class="h2Title">■経費管理</p>

<p>使用した経費の情報を入力してください</p>

<form method="post" name="form1" id="form1" class="form" action="{{ $expense ? route('admin.ventas.expenses.update', $expense->cd) : route('admin.ventas.expenses.store') }}" onsubmit="return submitChk()">
@csrf

<fieldset>

<table class="tableBox">
	<tr>
		<th style="width:25%">集計を行うスタッフ</th>
		<td>
@if (! $isMaster)
			{{ $sessionShopName }}<input type="hidden" name="shop" id="shop" value="{{ $sessionShopCd }}" />
@else
			<select name="shop" id="shop">
			@include('admin.ventas.partials.shop_options', ['selected' => old('shop', $expense->int_data ?? null)])
			</select>
@endif
		</td>
	</tr>

	<tr>
		<th>使用日</th>
		<td>
			<input type="text" name="date1" value="{{ old('date1', $date) }}" id="date1" class="inputM" readonly="readonly" />
		</td>
	</tr>

	<tr>
		<th>使用目的</th>
		<td><input type="text" name="mokuteki" id="mokuteki" class="inputL" value="{{ old('mokuteki', $expense->text_data ?? null) }}" /></td>
	</tr>

	<tr>
		<th>使用金額</th>
		<td><input type="text" name="price" id="price" class="inputM" value="{{ old('price', $expense->float_data ?? null) }}" onKeyPress="return numbersonly(this, event)" onkeyup="replace_value(this)" onmouseover="replace_value(this)" onchange="replace_value(this)" style="ime-mode:disabled" />円</td>
	</tr>
</table>

</fieldset>

@if ($errors->any())
<p class="attention">{!! nl2br(e(implode("\n", $errors->all()))) !!}</p>
@endif

<div class="btnBox">
@if ($expense)
<input type="submit" name="enter" id="revise" value="この内容で修正する" />
<input type="hidden" name="act" id="act" value="up" />
<input type="hidden" name="cd" id="cd" value="{{ $expense->cd }}" />
@else
<input type="submit" name="enter" id="reivse" value="この内容で登録する" />
<input type="hidden" name="act" id="act" value="new" />
@endif
</div>

</form>

<p class="h2Title">■経費一覧</p>

@if ($paginator->count() > 0)
<table class="tableBox2">
	<tr>
		<th>使用日</th>
		<th>使用目的</th>
		<th>使用金額</th>
		@can('ventas.update')<th align="center">修正</th>@endcan
		@can('ventas.delete')<th align="center">削除</th>@endcan
	</tr>

@foreach ($paginator as $row => $data)
	<tr @if ($row % 2 <> 0) class="tableBg" @endif>
		<td align="center">{{ $data->update_date }}</td>
		<td align="center">{{ $data->text_data }}</td>
		<td align="right">{{ number_format((float) $data->float_data) }}</td>
		@can('ventas.update')
		<td align="top" class="textCenter" width="100">
			<a href="{{ route('admin.ventas.expenses.index', ['cd' => $data->cd]) }}"><img src="{{ asset('legacy/system/img/btn_revise_off.gif') }}" width="40" height="20" alt="修正" /></a>
		</td>
		@endcan
		@can('ventas.delete')
		<td align="top" class="textCenter" width="100">
			<a href="javascript:void(0)" onclick="submitChk2('{{ $data->cd }}'); return false;"><img src="{{ asset('legacy/system/img/btn_delete_off.gif') }}" width="40" height="20" alt="削除" /></a>
		</td>
		@endcan
@endforeach

</table>

<form method="post" name="form_delete" id="form_delete" class="displayNone" action="">
@csrf
<input type="hidden" name="delete_flg" id="delete" class="btn" value="del">
<input type="hidden" name="cd" class="btn" value="">
<input type="submit" name="delete" id="delete" class="btn" value="削除する">
</form>

<ul class="btnBox2">
@include('admin.ventas.partials.page_control', [
    'pageUrl' => fn (int $p) => route('admin.ventas.expenses.index', ['page' => $p, 'kensu' => $paginator->perPage()]),
])
</ul>

@else
<p style="text-align:center;font-size: 120%;color:#FF0000;font-weight:bold;">データがありません。</p>
@endif

<!-- / .contents --></div>
@endsection
