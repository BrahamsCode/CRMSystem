{{--
    Legacy: system/category1/karute_menu/karute_waribiki.php (割引管理).
    Variables:
      $item      ?Discount          割引 que se edita (?cd=) o null en el alta
      $shopCd    ?int               tienda de la pantalla (master: ?shop=; si no, la de la sesión); null = sin elegir
      $shopName  ?string            tb_shop.kaishamei de $shopCd (null si -1)
      $isMaster  bool               rank master: selector de tiendas
      $shops     Collection<Shop>   opciones de make_shop_options() (solo master)
--}}
@extends('layouts.legacy')

@section('title', '割引管理 - ' . $siteName)

@section('menu', 'clientes')

@section('js')
<script type="text/javascript" src="{{ asset('legacy/system/js/ajaxupload-3.5.js') }}"></script>
<script type="text/javascript">
<!--

$(document).ready(function() {

	$(".ac4").accordion('option', 'active', 0);

	//店舗選択
	$("#shop").change(function(){

		document.shop_select.submit();

	});

	//一覧をajaxでロードする処理
	statusControl("");

});

function submitChk() {

	var errorMess = "";
	var errorFlg = 0;

	if(document.form1.name.value == ""){
		errorMess += "「名称」が未入力です。";
		errorFlg = 1;
	}

	if(document.form1.value.value == ""){
		errorMess += "「割引額」が未入力です。";
		errorFlg = 1;
	}

	if(errorFlg == 1){
		alert(errorMess);
		return false;
	}else{
		var flg = confirm ( "この内容で登録してもよろしいですか？\n\n書き直す場合は['キャンセル']ボタンを押して下さい");
		return flg;
	}

} // function submitChk () --- END

function submitChk2(val) {

	element = eval("document.form_" + val);

	var flg = confirm ( "削除してもよろしいですか？\n\n削除しない場合は['キャンセル']ボタンを押して下さい");

	if(flg) element.submit();

} // function submitChk2(val) --- END

function statusControl(shop_cd, type, target_cd, vl) {

	$(".tableDisp").load("{{ route('legacy.ventas.discount-list') }}",{

		shop_cd: "{{ $shopCd }}",
		send_type: type,
		send_targetCd: target_cd,
		send_vl: vl

	});

} // function statusControl(shop_cd, type, target_cd, vl) --- END

@include('admin.ventas.partials.numbersonly')

-->
</script>
@endsection

@section('content')
<div class="contents">

<p class="h2Title">■割引管理</p>
@if (! $isMaster)
&nbsp;<br />
@else
<form action="{{ route('admin.ventas.discounts.index') }}" method="get" name="shop_select">
<select name="shop" id="shop" style="margin-bottom: 10px; padding: 1px; border: 1px solid #7F9DB9;">
@include('admin.ventas.partials.shop_options', ['selected' => $shopCd])
</select>&emsp;
※登録・閲覧したい店舗を選択してください。
</form>
@endif

@if ($shopCd)

<p class="h3Title">■{{ $shopCd === -1 ? '全店舗用' : $shopName }}</p>

<form method="post" name="form1" id="form1" class="form" action="{{ $item ? route('admin.ventas.discounts.update', $item->cd) : route('admin.ventas.discounts.store') }}" onsubmit="return submitChk()">
@csrf

<fieldset>
<legend>割引登録情報</legend>

<table class="tableBox">

	<tr>
	<th><label for="name">名称<span class="attention">※</span></label></th>
	<td>
		<input type="text" name="name" id="name" class="inputL" value="{{ old('name', $item?->name) }}" />
	</td>
	</tr>

	<tr>
	<th><label for="kingaku">割引額<span class="attention">※</span></label></th>
	<td>
		@php $type = (string) old('type', $item?->type); @endphp
		<input type="text" name="value" id="value" class="inputL" onKeyPress="return numbersonly(this, event)" onkeyup="replace_value(this)" onmouseover="replace_value(this)" onchange="replace_value(this)" value="{{ old('value', $item?->value) }}" />
		<select name="type" id="type" style="padding: 1px; border: 1px solid #7F9DB9;">
		<option value="1"@if ($type === '1') selected="selected"@endif>％</option>
		<option value="2"@if ($type === '2') selected="selected"@endif>円</option>
		</select>
	</td>
	</tr>

	<tr>
	<th><label for="type">歩合適用</label></th>
	<td>
		@php $buaiFlg = (string) old('buai_flg', $item?->buai_flg); @endphp
		<input type="radio" value="1" class="checkBtn" id="buai_flg_1" name="buai_flg"@if ($buaiFlg === '1' || $buaiFlg === '') checked="checked"@endif><label for="buai_flg_1">割引額を歩合に含む</label><br />
		<input type="radio" value="2" class="checkBtn" id="buai_flg_2" name="buai_flg"@if ($buaiFlg === '2') checked="checked"@endif><label for="buai_flg_2">割引額を歩合に含まない</label>
	</td>
	</tr>

</table>

</fieldset>

@if ($errors->any())
<p class="attention">{{ implode('', $errors->all()) }}</p>
@endif

<div class="btnBox">
<input type="hidden" name="tb_shop_cd" value="{{ $shopCd }}" />
@if ($item)
<input type="submit" name="enter" id="revise" value="この内容で修正する" />
<input type="hidden" name="act" id="act" value="up" />
<input type="hidden" name="cd" id="cd" value="{{ $item->cd }}" />
@else
<input type="submit" name="enter" id="reivse" value="この内容で登録する" />
<input type="hidden" name="act" id="act" value="new" />
@endif
</div>

</form>

<!-- / .contents --></div>

<div class="contents">

<div class="tableDisp"></div>

@endif
<!-- / .contents --></div>
@endsection
