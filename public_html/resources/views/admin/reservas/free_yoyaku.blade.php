{{--
    Legacy: system/category9/yoyaku/free_yoyaku.php (フリー予約設定, ventana emergente sin navegación).
    Variables:
      $isMaster bool       $_SESSION['rank'] == 'master' (muestra el <select> de tiendas).
      $shopCd   ?int       tienda de la pantalla (`shop` si es master, si no la de la sesión); null = sin elegir.
      $shops    Collection tiendas para make_shop_options($shop_cd, false) (cd, kaishamei); vacía si no es master.
      $shopName ?string    getShopNameFromCd($shopCd), o '全店舗用' si $shopCd = -1.
      $settings array      ['hyouji_flg', 'name', 'staff_select_flg', 'timeline_staff_flg'] => text_data de contents_master.
    AJAX: la tabla de 優先順位 se carga de route('admin.reservas.free.ranks') (antes ajax_free_yoyaku_rank.php).
--}}
@extends('layouts.legacy-popup')

@section('title', 'フリー予約設定')

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
<script type="text/javascript">
$(document).ready(function() {

	$.ajaxSetup({ cache: false }); // 2013.01.18【大野】Ajaxのキャッシュを切る



//	$(".ac2").accordion('option', 'active', 0);

	$("#shop").change(function(){
		document.shop_select.submit();
	});


	//一覧をajaxでロードする処理
	statusControl("");


	//閉じるボタンの挙動
	$(".popCloseBtn").click(function(){
		window.parent.popUpWinClose("");
	});



});



function submitChk () {

	jConfirm ( "この内容で登録してもよろしいですか？\n\n書き直す場合は['キャンセル']ボタンを押して下さい", "予約項目登録", function(ret) {
		if (ret) document.form1.submit();
	});
	return false;

}


function statusControl(shop_cd, type, target_cd, prev_cd, vl){

	{{-- El legacy enviaba $_REQUEST['shop'] (vacío fuera de master: la tabla no salía); aquí la tienda de la pantalla --}}
	$(".tableDisp").load("{{ route('admin.reservas.free.ranks') }}",{

		shop_cd      : "{{ $shopCd }}",
		send_type    : type,
		send_targetCd: target_cd,
		send_prevCd  : prev_cd,
		send_vl      : vl,
		_token       : "{{ csrf_token() }}"

	});

}


</script>

<script type="text/javascript" src="{{ asset('legacy/system/js/ajaxupload-3.5.js') }}"></script>
@endsection

@section('content')
<div class="contents">

@if ($isMaster)
<form action="{{ route('admin.reservas.free') }}" method="get" name="shop_select">
<select name="shop" id="shop">
<option value="">▼店舗選択</option>
@foreach ($shops as $shop)
<option value="{{ $shop->cd }}" @if ($shop->cd == $shopCd) selected="selected" @endif>{{ $shop->kaishamei }}</option>
@endforeach
</select>
</form>
<div>※登録・閲覧したい店舗を選択してください。</div>

@endif

@if ($shopCd)

<p class="h2Title">■{{ $shopName }}</p>


<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.reservas.free.update') }}" onsubmit="return submitChk()">
@csrf

<fieldset>
<legend>フリー予約設定</legend>

<table class="tableBox">

	<tr>
	<th style="width:25%;">フリー予約の使用</th>
	<td>
		<label for="hyouji_flg1">
		<input type="radio" name="hyouji_flg" id="hyouji_flg1" class="checkBtn"@if (($settings['hyouji_flg'] ?? null) != '1') checked="checked"@endif value="" />
		使用しない</label>&nbsp;
		<label for="hyouji_flg2">
		<input type="radio" name="hyouji_flg" id="hyouji_flg2" class="checkBtn"@if (($settings['hyouji_flg'] ?? null) == '1') checked="checked"@endif value="1" />
		使用する</label>
	</td>
	</tr>

	<tr>
	<th style="width:25%;">フリー予約の表示名</th>
	<td>
		<input type="text" name="name" class="inputL" value="{{ $settings['name'] ?? '' }}" /><br />
		※表示名が空の場合は「指名なし」と表示されます。
	</td>
	</tr>

	<tr>
	<th style="width:25%;">フリー予約使用時の指名選択</th>
	<td>
		<label for="staff_select_flg1">
		<input type="radio" name="staff_select_flg" id="staff_select_flg1" class="checkBtn"@if (($settings['staff_select_flg'] ?? null) != '1') checked="checked"@endif value="" />
		表示しない</label>&nbsp;
		<label for="staff_select_flg2">
		<input type="radio" name="staff_select_flg" id="staff_select_flg2" class="checkBtn"@if (($settings['staff_select_flg'] ?? null) == '1') checked="checked"@endif value="1" />
		表示する</label>

		<br />※表示しないを選択すると予約者はマイページでの予約の「指名」ができなくなります。
	</td>
	</tr>

	<tr>
	<th style="width:25%;">タイムラインのスタッフ名表示</th>
	<td>
		<label for="timeline_staff_flg1">
		<input type="radio" name="timeline_staff_flg" id="timeline_staff_flg1" class="checkBtn"@if (($settings['timeline_staff_flg'] ?? null) != '1') checked="checked"@endif value="" />
		表示しない</label>&nbsp;
		<label for="timeline_staff_flg2">
		<input type="radio" name="timeline_staff_flg" id="timeline_staff_flg2" class="checkBtn"@if (($settings['timeline_staff_flg'] ?? null) == '1') checked="checked"@endif value="1" />
		表示する</label>

		<br />※表示しないを選択するとマイページの予約タイムラインでスタッフ名が表示されなくなります。
	</td>
	</tr>

</table>


<div class="btnBox">
<input type="submit" name="enter" id="reivse" value="この内容で登録する" />
<input type="hidden" name="act" id="act" value="new" />
<input type="hidden" name="free_shop_cd" id="free_shop_cd" value="{{ $shopCd }}" />
</div>

</form>



<!-- / .contents --></div>

<div class="contents">

<div class="tableDisp"></div>


@endif
<!-- / .contents --></div>
@endsection
