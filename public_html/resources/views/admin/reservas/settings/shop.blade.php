{{--
    Legacy: system/category9/yoyaku/yoyaku_config.php (予約設定 > 各店舗設定; ventana emergente sin menú).
    Variables:
      $isMaster         bool        $_SESSION['rank'] == 'master' (<select> de tiendas; si no, la tienda de sesión).
      $shopCd           int|string|null  tienda seleccionada (`shop_cd` de la petición, o la de sesión).
      $shopName         ?string     kaishamei de la tienda de sesión (solo si !$isMaster).
      $shops            Collection  tiendas (cd, kaishamei), disp_flg != '9', orden cd (make_shop_options).
      $yoyakuSetsubiFlg ?string     admin_master.yoyaku_setsubi_flg: '1' muestra キャパ.
      $saved            bool        se acaba de guardar (legacy act=reg): mensaje y recarga de la ventana padre.
    Los valores de la tienda los trae el JS por AJAX (legacy ajax_yoyaku_config.php → route admin.reservas.settings.shop.data).
    El formulario era GET a sí mismo; ahora POST + CSRF a admin.reservas.settings.shop.update.
--}}
@extends('layouts.legacy-popup')

@section('title', '予約設定 - ' . ($siteName ?? config('app.name')))

@section('css')
<style type="text/css"><!--

#container {
	min-width: 450px;
}

.main1 {
	background: none;
}

--></style>
@endsection

@section('js')
@php
    $mess = $errors->any() ? $errors->first() : ($saved ? '設定しました。' : '');
@endphp
<script type="text/javascript">
$(document).ready(function() {

	$(".ac2").accordion('option', 'active', 0);

	$.ajaxSetup({async: false}); //非同期をfalseにする。

	$("#shop_cd").change(function(){
		searchShopConfig();
	});

	$("#automail").change(function(){
		if ($("#automail").val()=="") {
			$("#automail_time1").val("");
			$("#automail_time2").val("");
		}
	});

	searchShopConfig();

@if ($mess !== '')
	$("#mess").html(@json($mess, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)).show();
	setTimeout('hideMess()', 2000);
@endif

@if ($saved)
	if ( !window.opener ) {
		parent.location.reload();
	} else {
		window.opener.location.reload();
	}
@endif
});

function searchShopConfig() {
	var	shopCD = $("#shop_cd").val();
	if (shopCD == "") {
		$(".hideSection").hide();
		return;
	}
	{{-- legacy: $.getJSON("ajax_yoyaku_config.php", …) --}}
	$.getJSON(@json(route('admin.reservas.settings.shop.data')), {
			shop_cd:shopCD
		}, function(data) {

			var startTimeArray = data.start_time.split(":");
			var endTimeArray = data.end_time.split(":");
			var automailTimeArray = data.automail_time.split(":");

			$("#start_time1").val(startTimeArray[0]);
			$("#start_time2").val(startTimeArray[1]);
			$("#end_time1").val(endTimeArray[0]);
			$("#end_time2").val(endTimeArray[1]);
			$("#end_time").val(data.end_time);
			$("#shimekiri").val(data.shimekiri);
			$("#shimekiri_time").val(data.shimekiri_time);
			$("#automail").val(data.automail);
			$("#automail_time1").val(automailTimeArray[0]);
			$("#automail_time2").val(automailTimeArray[1]);

			if ( $("#setsubi_capacity").length > 0 ) $("#setsubi_capacity").val(data.setsubi_capacity);

			$("input[name='uketuke_type'][value='"+data.uketuke_type+"']").attr("checked",true);
			$("input[name='timeline_sort_type'][value='"+data.timeline_sort_type+"']").attr("checked",true);
			$(".hideSection").show();
			displayShimekiriTime();
		}
	);
}

function displayShimekiriTime() {
	var	shimekiri = $("#shimekiri").val();
	if (shimekiri == "0") {
		$("#shimekiri_time").show();
	} else {
		$("#shimekiri_time").val("").hide();
	}
}

function hideMess() {
		$("#mess").hide().html('');
}

function submitChk () {
	var startTime = $("#start_time1").val() + $("#start_time2").val();
	var endTime = $("#end_time1").val() + $("#end_time2").val();

	if ($("#start_time1").val() == "" || $("#start_time2").val() == ""){
		alert("開始時間を選択して下さい！");
		return false;
	}

	if ($("#end_time1").val() == "" || $("#end_time2").val() == ""){
		alert("終了時間を選択して下さい！");
		return false;
	}

	if (startTime >= endTime) {
		alert("時間を確認して下さい！");
		return false;
	}
	return true;
}
</script>

<script type="text/javascript" src="{{ asset('legacy/system/js/ajaxupload-3.5.js') }}"></script>
@endsection

@section('content')
<div class="contents">

各店舗設定 /
<a href="{{ route('admin.reservas.settings.form') }}">予約フォーム設定</a> /
<a href="{{ route('admin.reservas.settings.features') }}">機能設定</a> /
<a href="{{ route('admin.reservas.settings.comment') }}">注釈設定</a>

<div id="mess" style="display:none;text-align:center;background:#FFFF00;color:red;margin:5px;"></div>


<p class="h2Title">各店舗設定</p>
<form action="{{ route('admin.reservas.settings.shop.update') }}" method="post" name="form1" onsubmit="return submitChk()">
@csrf
<table class="tableBox">
	<tr>
		<th style="width:35%;">店舗選択</th>
		<td>
@if (! $isMaster)
			{{ $shopName }}<input type="hidden" name="shop_cd" id="shop_cd" value="{{ $shopCd }}" />
@else
			<select name="shop_cd" id="shop_cd">
			<option value="">▼店舗選択</option>
			@foreach ($shops as $shop)
			<option value="{{ $shop->cd }}" @selected((string) old('shop_cd', $shopCd) === (string) $shop->cd)>{{ $shop->kaishamei }}</option>
			@endforeach
			</select>
@endif
		</td>
	</tr>

	<tr class="hideSection">
		<th>店舗受付開始時間<span class="attention">※</span></th>
		<td>
			<select name="start_time1" id="start_time1">
			<option value="" >&nbsp;</option>
			@for ($i = 0; $i < 24; $i++)
				<option value="{{ sprintf('%02d', $i) }}" >{{ sprintf('%02d', $i) }}</option>
			@endfor
			</select>時

			<select name="start_time2" id="start_time2">
			<option value="" >&nbsp;</option>
			@foreach (['00', '30'] as $fun)
				<option value="{{ $fun }}" >{{ $fun }}</option>
			@endforeach
			</select>分

		</td>
	</tr>

	<tr class="hideSection">
		<th>店舗受付終了時間<span class="attention">※</span></th>
		<td>
			<select name="end_time1" id="end_time1">
			<option value="" >&nbsp;</option>
			@for ($i = 0; $i < 30; $i++)
				<option value="{{ sprintf('%02d', $i) }}" >{{ sprintf('%02d', $i) }}</option>
			@endfor
			</select>時

			<select name="end_time2" id="end_time2">
			<option value="" >&nbsp;</option>
			@foreach (['00', '30'] as $fun)
				<option value="{{ $fun }}" >{{ $fun }}</option>
			@endforeach
			</select>分
		</td>
	</tr>

@if ($yoyakuSetsubiFlg == '1')
	<tr class="hideSection">
		<th>キャパ</th>
		<td>
			<input type="text" name="setsubi_capacity" id="setsubi_capacity" size="4" maxlength="3" value="" style="ime-mode:disabled;" />
		</td>
	</tr>
@endif

	<tr class="hideSection">
		<th>営業時間中の予約受付</th>
		<td>
			<input type="radio" name="uketuke_type" id="uketuke_type1" class="checkBtn"@if (request('uketuke_type') != '1') checked="checked"@endif value="" />
			<label for="uketuke_type1">すべての時間予約を受け付ける</label><br />
			<input type="radio" name="uketuke_type" id="uketuke_type2" class="checkBtn"@if (request('uketuke_type') == '1') checked="checked"@endif value="1" />
			<label for="uketuke_type2">営業時間中はフォームからの予約を受け付けない</label>
		</td>
	</tr>

	<tr class="hideSection">
		<th>〆切日</th>
		<td>
			<select name="shimekiri" id="shimekiri" onchange="displayShimekiriTime()">
			<option value="" >&nbsp;</option>
			<option value="0" >当日</option>
			@for ($i = 1; $i <= 30; $i++)
				<option value="{{ $i }}" >{{ $i }}日前</option>
			@endfor
			</select>&emsp;
			<select name="shimekiri_time" id="shimekiri_time">
			<option value="" >&nbsp;</option>
			@for ($i = 1; $i <= 23; $i++)
				<option value="{{ $i }}" >{{ $i }}時間前</option>
			@endfor
			</select>&emsp;
		</td>
	</tr>

	{{-- リマインダーメール送信: oculto en el legacy desde 2013.03.14 (リマインダーメール管理) --}}

	<tr class="hideSection">
		<th>予約タイムラインの表示方法</th>
		<td>
			<input type="radio" name="timeline_sort_type" id="timeline_sort_type1" class="checkBtn"@if (request('timeline_sort_type') == '1') checked="checked"@endif value="1" />
			<label for="timeline_sort_type1">予約受付を開始しているスタッフを優先して表示する</label><br />
			<input type="radio" name="timeline_sort_type" id="timeline_sort_type2" class="checkBtn"@if (request('timeline_sort_type') != '1') checked="checked"@endif value="" />
			<label for="timeline_sort_type2">予約受付を開始しているスタッフを優先しない</label>
		</td>
	</tr>

	<tr class="hideSection">
		<td colspan="2">
			<small>
			※〆切日は、締切対象日の一日中が予約可能になります。<br />
			<span style="color: #336699;">例)「1日前」に設定した場合、20日の予約を行う事ができるのは19日の23時59分までです。<br />
			例)「2日前」に設定した場合、20日の予約を行う事ができるのは18日の23時59分までです。</span><br />
			※店舗受付は、店舗としての設定個所です。受付日設定でスタッフの1受付毎に受付時間をさらに設定する事ができます。<br />
			※〆切日に設定された日数で予約受付を締め切ります。<br />
			</small>
		</td>
	</tr>
</table>

<div class="btnBox hideSection">
<input type="hidden" name="act" id="act" value="reg" />
<input type="submit" name="enter" id="enter" class="btn" value="確定" />
</div>

</form>



<!-- / .contents --></div>
@endsection
