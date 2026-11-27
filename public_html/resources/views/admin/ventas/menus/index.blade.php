{{--
    メニュー管理 (legacy: system/category1/karute_menu/karute_menu.php).

    Datos:
      $shopCd    int|null   tienda (master: ?shop=; resto: la de la sesión)
      $shopName  string
      $shops     Collection<Shop>
      $isMaster  bool
      $item      ServiceMenu|null  en edición (?cd=)

    El listado se carga por AJAX desde la URL del legacy (ajax_karute_menu_list.php).
--}}
@extends('layouts.legacy')

@section('menu', 'clientes')

@section('title', 'メニュー管理 - ' . ($siteName ?? ''))

@section('js')
<script type="text/javascript" src="/system/js/ajaxupload-3.5.js"></script>
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

	if(document.form1.kingaku.value == ""){
		errorMess += "「金額」が未入力です。";
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

	$(".tableDisp").load("{{ route('legacy.ventas.ajax-menu-list', [], false) }}",{

		shop_cd: "{{ $shopCd }}",
		send_type: type,
		send_targetCd: target_cd,
		send_vl: vl

	});

} // function statusControl(shop_cd, type, target_cd, vl) --- END

@include('admin.ventas.partials.numbers-only-js')
-->
</script>
@endsection

@section('content')
<div class="contents">

<p class="h2Title">■メニュー管理</p>
@include('admin.ventas.partials.shop-select', ['action' => route('admin.ventas.menus.index')])

@if ($shopCd)

<p class="h3Title">■{{ $shopName }}</p>


<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.ventas.menus.store') }}" onsubmit="return submitChk()">
@csrf

<fieldset>
<legend>メニュー登録情報</legend>

<table class="tableBox">

	<tr>
	<th><label for="name">名称<span class="attention">※</span></label></th>
	<td><input type="text" name="name" id="name" class="inputL" value="{{ $item?->name }}" /></td>
	</tr>

	<tr>
	<th><label for="kingaku">金額<span class="attention">※</span></label></th>
	<td><input type="text" name="kingaku" id="kingaku" class="inputL" onKeyPress="return numbersonly(this, event)" onkeyup="replace_value(this)" onmouseover="replace_value(this)" onchange="replace_value(this)" value="{{ $item?->kingaku }}" /></td>
	</tr>

	<tr>
	<th><label for="type">区分</label></th>
	<td>
	<input type="radio" value="1" class="checkBtn" id="type_1" name="type" @checked(in_array((string) $item?->type, ['1', ''], true))>
	<label for="type_1">技術</label>
	<input type="radio" value="2" class="checkBtn" id="type_2" name="type" @checked((string) $item?->type === '2')>
	<label for="type_2">物販</label>
	<input type="radio" value="3" class="checkBtn" id="type_3" name="type" @checked((string) $item?->type === '3')>
	<label for="type_3">その他</label>
	</td>
	</tr>

</table>

</fieldset>



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
