{{--
    Legacy: system/category9/yoyaku/setsubi_new.php (設備管理, ventana emergente sin yoyaku_navi).
    Variables:
      $facility    ?Facility          設備 que se edita (?cd=) o null en el alta
      $shopCd      ?int               tienda de la pantalla; null = master sin tienda elegida
      $shopName    ?string            tb_shop.kaishamei de $shopCd
      $isMaster    bool               rank master: muestra el selector de tiendas
      $shops       Collection<Shop>   opciones de make_shop_options() sin 全店舗 (solo master)
      $facilities  Collection<Facility>  設備 de la tienda (orden cd)
--}}
@extends('layouts.legacy-popup')

@section('title', '設備管理')

@section('css')
<style type="text/css"><!--

#container {
	min-width: 450px;
}

.main1 {
	background: none;
}

h3 {
	padding: 0 5px;
}

.form {
	margin-bottom: 15px;
}

--></style>
@endsection

@section('js')
<script type="text/javascript" src="{{ asset('legacy/system/js/scrollsmoothly.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/js/iepngfix.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/js/default.js') }}"></script>

<script type="text/javascript" src="{{ asset('legacy/system/js/jquery.alerts.js') }}"></script>

<script type="text/javascript">
$(document).ready(function() {

	$("#shop").change(function(){
		document.shop_select.submit();
	});

	//閉じるボタンの挙動
	$(".popCloseBtn").click(function(){
		window.parent.popUpWinClose("");
	});

});



function submitChk () {

	var errorMess = "";

	if (document.form1.name.value == "") errorMess += "「名称」が未入力です。\n";
	if (document.form1.quantity.value == "") errorMess += "「数量」が未入力です。\n";

	if (!document.form1.quantity.value.match(/^[1-9][0-9]*$/)) errorMess += "「数量」半角数字で入力してください。\n"

	if (errorMess != "") {
		jAlert(errorMess, "警告");
	} else {
		jConfirm ( "この内容で登録してもよろしいですか？\n\n書き直す場合は['キャンセル']ボタンを押して下さい", "設備登録", function(ret) {
			if (ret) document.form1.submit();
		});
	}

	return false;

}


// Legacy: location.href='./setsubi_register.php?shop_cd=..&cd=..&act=delete' (GET).
// Ahora envía el formulario oculto facilitiesDeleteForm (DELETE + CSRF) a admin.reservas.facilities.destroy.
function deleteChk (cd) {
	if ( typeof(cd)=="undefined" || cd == "" ) return false;

	jConfirm ( "削除してもよろしいですか？\n\n削除しない場合は['キャンセル']ボタンを押して下さい", "設備削除", function(ret) {
		if (ret) {
			document.facilitiesDeleteForm.action = @json(route('admin.reservas.facilities.destroy', '__CD__')).replace('__CD__', cd);
			document.facilitiesDeleteForm.submit();
		}
	});

}


</script>

<script type="text/javascript" src="{{ asset('legacy/system/js/ajaxupload-3.5.js') }}"></script>
@endsection

@section('content')
<div class="contents">


@if ($isMaster)
<form action="{{ route('admin.reservas.facilities.index') }}" method="get" name="shop_select">
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

@if ($errors->any())
<div class="attention">@foreach ($errors->all() as $error){{ $error }}<br />@endforeach</div>
@endif

<form method="post" name="form1" id="form1" class="form" action="{{ $facility ? route('admin.reservas.facilities.update', $facility->cd) : route('admin.reservas.facilities.store') }}">
@csrf
@if ($facility) @method('PUT') @endif


<fieldset>
<legend>設備登録情報</legend>

<table class="tableBox">

	<tr>
	<th style="width:20%;">名称<span class="attention">※</span></th>
	<td><input type="text" name="name" class="inputL" value="{{ old('name', $facility?->name) }}" /></td>
	</tr>

	<tr>
	<th style="width:20%;">数量<span class="attention">※</span></th>
	<td><input type="text" name="quantity" class="inputS" value="{{ old('quantity', $facility?->quantity) }}" style="ime-mode:disabled;" maxlength="5" /><br />
	※半角数字で入力してください。</td>
	</tr>

</table>

</fieldset>



<div class="btnBox">
	<input type="hidden" name="act" id="act" value="register" />
	<input type="hidden" name="cd" id="cd" value="{{ $facility?->cd }}" />
	<input type="hidden" name="shop_cd" value="{{ $shopCd }}" />
	<input type="button" name="enter" id="revise" value="この内容で{{ $facility ? '修正' : '登録' }}する" onclick="submitChk()" />
</div>

</form>


<!-- / .contents --></div>

<div class="contents">

<div class="tableDisp wordBreakAll">
	<p class="h2Title">■{{ $shopName }} 設備一覧</p>
@if ($facilities->isNotEmpty())
	<table class="tableBox8">
		<tr>
			<th>名前</th>
			<th width="50">数量</th>
			<th width="50"></th>
			<th width="50"></th>
		</tr>
@foreach ($facilities as $item)
		<tr>
			<td>{{ $item->name }}</td>
			<td width="50">{{ $item->quantity }}</td>
			<td width="50"><input type="button" value="修正" onclick="location.href='{{ route('admin.reservas.facilities.index', ['cd' => $item->cd]) }}';" /></td>
			<td width="50"><input type="button" value="削除" onclick="deleteChk({{ $item->cd }})" /></td>
		</tr>
@endforeach
	</table>
@else
登録データがありません。
@endif
</div>

<form method="post" name="facilitiesDeleteForm" class="displayNone" action="">
@csrf
@method('DELETE')
<input type="hidden" name="shop_cd" value="{{ $shopCd }}" />
</form>

@endif
<!-- / .contents --></div>
@endsection
