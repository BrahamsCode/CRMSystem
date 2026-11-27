{{-- テストメールアドレス管理 — legacy: system/category3/mail_mag/test_mail_address.php --}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', 'テストメールアドレス管理')

@section('js')
<script type="text/javascript">
<!--
$(document).ready(function() {
	$(".ac1").accordion({ collapsible: true , active: false});
	$(".ac2").accordion({ collapsible: true , active: false});
	$(".ac3").accordion({ collapsible: true , active: false});
	$(".ac4").accordion({ collapsible: true , active: false});
	$(".ac5").accordion({ collapsible: true , active: false});
	$(".ac6").accordion({ collapsible: true , active: false});
	$(".ac7").accordion({ collapsible: true , active: false});
	$(".ac8").accordion({ collapsible: true , active: false});
	$(".ac9").accordion({ collapsible: true , active: false});
	$(".ac10").accordion({ collapsible: true , active: false});
	$(".ac11").accordion({ collapsible: true , active: false});
	$(".ac1").accordion('option', 'active', 0);

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
	var inputAddress = document.form1.email.value;
	var reg = /^[_a-z0-9-\.]+(\.[_a-z0-9-\.]+)*@[a-z0-9-]+([\.][a-z0-9-]+)+$/i;

	if(document.form1.name.value == ""){
		errorMess += "「名前」が未入力です。";
		errorFlg = 1;
	}

	if(inputAddress == ""){
		errorMess += "「メールアドレス」が未入力です。";
		errorFlg = 1;
	}
	else if(!reg.test(inputAddress)){
		errorMess += "「メールアドレス」が不適切な形式です。";
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

	var element = document.forms["form_" + val];
	var flg = confirm ( "削除してもよろしいですか？\n\n削除しない場合は['キャンセル']ボタンを押して下さい");
	if(flg) element.submit();

} // function submitChk2(val) --- END

function statusControl(shop_cd, type, target_cd, vl) {

	$(".tableDisp").load("{{ route('legacy.promocion.mail_mag.ajax_test_mail_address_new') }}",{
		shop_cd: "{{ $shopCd }}",
		send_type: type,
		send_targetCd: target_cd,
		send_vl: vl
	});

} // function statusControl(shop_cd, type, target_cd, vl) --- END
-->
</script>
<script type="text/javascript" src="{{ asset('legacy/system/js/ajaxupload-3.5.js') }}"></script>
@endsection

@section('content')
<div class="contents">
@if (! $isMaster)
&nbsp;<br />
@else
<form action="{{ route('admin.promocion.test-addresses.index') }}" method="get" name="shop_select">
<select name="shop" id="shop">
<option value="">▼店舗選択</option>
@foreach ($shops as $shop)
<option value="{{ $shop->cd }}" @selected((string) $shopCd === (string) $shop->cd)>{{ $shop->kaishamei }}</option>
@endforeach
</select>
</form>
<div>※登録・閲覧したい店舗を選択してください。</div>
@endif

@if ($shopCd)

<p class="h2Title">■{{ $shopName }}</p>

<form method="post" name="form1" id="form1" class="form" action="{{ route('legacy.promocion.mail_mag.test_mail_address_register') }}" onsubmit="return submitChk()">
@csrf
<fieldset>
<legend>テストメールアドレス登録情報</legend>
<table class="tableBox">
	<tr>
	<th><label for="name">名前<span class="attention">※</span></label></th>
	<td><input type="text" name="name" id="name" class="inputL" value="{{ $address?->name }}" /></td>
	</tr>
	<tr>
	<th><label for="email">メールアドレス<span class="attention">※</span></label></th>
	<td><input type="text" name="email" id="email" class="writeArea" value="{{ $address?->email }}" style="ime-mode: disabled;" />	</td>
	</tr>
</table>
</fieldset>

<div class="btnBox">
<input type="hidden" name="tb_shop_cd" value="{{ $shopCd }}" />
@if ($address)
<input type="submit" name="enter" id="revise" value="この内容で修正する" />
<input type="hidden" name="act" id="act" value="up" />
<input type="hidden" name="cd" id="cd" value="{{ $address->cd }}" />
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
