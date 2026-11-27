{{--
	クーポンお知らせ機能管理 — legacy: system/category3/stamp/oshirase_coupon.php
	Variables: $shops (make_shop_options: todas para master, solo la propia para el resto)
	El legacy mandaba el registro como query string sin codificar ("naiyou="+…): aquí jQuery codifica los datos.
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', 'クーポンお知らせ機能管理' . ' - ' . $siteName)

@section('css')
<link rel="stylesheet" type="text/css" href="{{ asset('legacy/system/css/popInline.css') }}">
@endsection

@section('js')
<script type="text/javascript" src="{{ asset('legacy/system/js/popInline.js') }}"></script>
<script type="text/javascript">
function submitChk(){
	var errorCheck = 0;
	var errorTxt = "";

	if($("#tb_shop_cd").val() == ""){
		errorCheck = 1;
		errorTxt += "お店が未選択です。\n";
	}
	if($("#stamp_count").val() == ""){
		errorCheck = 1;
		errorTxt += "スタンプ数が未入力です。\n";
	}else{
		if($("#stamp_count").val().match(/[^0-9]+/)){
			errorCheck = 1;
			errorTxt += "入力値が半角数字ではありません。\n";
		}
	}
	if($("#naiyou").val() == ""){
		errorCheck = 1;
		errorTxt += "メール内容が未入力です。\n";
	}
	if($(".couponMasterCheck:checked").length < 1){
		errorCheck = 1;
		errorTxt += "クーポンが未選択です。\n";
	}
	if(errorCheck == 1){
		alert(errorTxt);
		return false;
	}
	return true;
}

function oshiraseIni(){
	// 店舗選択のファンクション
	$("#tb_shop_cd").change(function(){
		changeShopSelect();
	});

	// スタンプ数選択のファンクション
	$("#changeStampSelectBtn").click(function(){
		changeStampSelect($(this).val());
	});
}

function changeShopSelect(){
	$("#couponArea").empty().hide();
	$("#stamp_count").val("");
	if ($("#tb_shop_cd").val() == "") {
		$("#stampSelect").hide();
	} else {
		$("#stampSelect").show();
	}
}

function changeStampSelect(){
	$("#couponArea").hide();
	$("#couponArea").load("{{ route('admin.promocion.stamps.oshirase.ajax') }}", {
		tb_shop_cd: $("#tb_shop_cd").val(),
		stamp_count: $("#stamp_count").val()
	});
	$("#couponArea").show();
}

function sendFunc(){
	if(!submitChk()){
		return false;
	}

	cfm = confirm("この内容で登録を行ってもよろしいですか？");
	if(!cfm){
		return false;
	}

	var couponMasterVal = "";
	for(var i=0; i<$(".couponMasterCheck:checked").length; i++){
		if(i != 0){
			couponMasterVal += ",";
		}
		couponMasterVal += $(".couponMasterCheck:checked:eq("+i+")").val();
	}

	$.ajax({
		type: "POST",
		url: "{{ route('admin.promocion.stamps.oshirase.store') }}",
		data: {
			tb_shop_cd: $("#tb_shop_cd").val(),
			stamp_count: $("#stamp_count").val(),
			naiyou: $("#naiyou").val(),
			coupon_master: couponMasterVal,
			hyouji_flg: $("#hyouji_flg").val()
		},
		success: function(msg){
			alert( msg );
			changeStampSelect();
		}
	});
}

$(document).ready(function() {
	$(".ac6").accordion('option', 'active', 0);
	window.scroll(0,0);

	$("#popCloseBtn").click(function(){
		window.parent.popUpWinClose("");
	});

	oshiraseIni();
});

function selectStampCount(valor) {
	$("#stamp_count").val(valor);
	$("#changeStampSelectBtn").click();
}
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■クーポンお知らせ設定登録</p>

<form action="{{ route('admin.promocion.stamps.oshirase.store') }}" method="post" name="form1" id="form" class="form" onsubmit="return submitChk()">
@csrf

<table class="tableBox8" style="margin-bottom: 5px;">

<tr>
<th width="75">店舗選択*</th>
<td>
<select name="tb_shop_cd" id="tb_shop_cd" class="selectBox">
<option value="">▼店舗選択</option>
@foreach ($shops as $shop)
<option value="{{ $shop->cd }}" >{{ $shop->kaishamei }}</option>
@endforeach
</select>
</td>
</tr>

<tr id="stampSelect" style="display: none;">
<th>スタンプ数*</th>
<td>
<select name="stamp_count" id="stamp_count">
<option value="">設定済み一覧</option>
@for ($i = 1; $i <= 500; $i++)
<option value="{{ $i }}">{{ $i }}</option>
@endfor
</select>
<input type="button" id="changeStampSelectBtn" value="表示" />
</td>
</tr>

</table>

<div id="couponArea"></div>

</form>

<!-- / .contents -->
</div>
@endsection
