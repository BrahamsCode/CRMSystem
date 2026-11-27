{{-- 店舗新規登録 / 店舗登録情報 修正: system/category0/shop/shop_new.php, y shop_new_confirm.php con errores
     (mismo formulario con las celdas marcadas class="Ng" y los mensajes de error_check.php). --}}
@php
    $check ??= null;
    $ng = fn ($f) => $check ? $check->ng($f) : '';
    $msg = fn ($f) => $check ? $check->messages($f) : '';
    $hide = $extShop ? " style='display: none'" : '';
    $v = fn ($f) => $data[$f] ?? '';
    $heading = $shopCd ? '店舗登録情報 修正' : '店舗新規登録';
    // Como el legacy: vacío o 0 terminan seleccionando las 10
    $startHour = (string) $v('start_work_time1');
    $hourSelected = $startHour !== '' && $startHour !== '0' ? (int) $startHour : 10;
@endphp
@extends('layouts.legacy')

@section('title', $heading . ' - ' . $siteName)

@section('menu', 'base')

@section('css')
<link rel="stylesheet" type="text/css" href="/system/plugin/ui-datepicker/ui.datepicker.css" />
@endsection

@section('js')
<script type="text/javascript" src="/system/plugin/ui-datepicker/ui.datepicker-ja.js"></script>
<script type="text/javascript">
$(document).ready(function() {
	$(".ac2").accordion('option', 'active', 0);

	$("#zip").mask("9999999",{placeholder:""});

	$("#zip_search").click(function() {
		$("#add").attr("value","");
		zip = $("#zip").attr("value");
		$.getJSON("zip.php",{
						zip: zip
		}, change_addr)
	});

@unless ($check)
	$( "input[name=new_store_start_date]" ).datepicker();
	$( "input[name=new_store_end_date]" ).datepicker();
@endunless
});

</script>

<script type="text/javascript" src="../../js/jquery.maskedinput-1.2.2.js"></script>

<script type="text/javascript">
function change_addr(data) {
		$("#pref").attr("value",data.zip_name);
		$("#add").attr("value",data.town_name);
		$("#add_kana").attr("value",data.town_name_kana);
}
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■{{ $heading }}</p>

<p><span class="attention">※</span>マークがある項目は、必須項目です。（空白にはしないで下さい）</p>

<form method="post" name="form1" id="form1" class="form" action="shop_new_confirm.php">

<fieldset>
<legend>店舗情報</legend>
<table class="tableBox">
	<tr>
		<th style="width: 210px;"><label for="kaishamei_kana">会社名/屋号（カナ）</label></th>
		<td colspan="3"{!! $ng('kaishamei_kana') !!}><input type="text" name="kaishamei_kana" id="kaishamei_kana" class="writeArea inputSmall" value="{{ $v('kaishamei_kana') }}" />
		{!! \App\Support\LegacyHtml::tooltip(6) !!}{!! $msg('kaishamei_kana') !!}
		</td>
	</tr>
	<tr>
		<th><label for="kaishamei">会社名/屋号<em>※</em></label></th>
		<td colspan="3"{!! $ng('kaishamei') !!}><input type="text" name="kaishamei" id="kaishamei" class="writeArea" value="{{ $v('kaishamei') }}">
		{!! \App\Support\LegacyHtml::tooltip(5) !!}{!! $msg('kaishamei') !!}
		</td>
	</tr>
	<tr>
		<th>店舗グループ</th>
		<td colspan="3">
			@if (! count($categories))
				店舗グループの設定がありません。
				<input type="hidden" name="shop_category_cd" value="0">
			@else
				<select name="shop_category_cd" id="shop_category_cd" class="selectBox">
					<option value="0">---</option>
					@foreach ($categories as $cd => $title)
						<option value="{{ $cd }}"@if ((string) $v('shop_category_cd') === (string) $cd) selected @endif>{{ $title }}</option>
					@endforeach
				</select>
			@endif
		</td>
	</tr>
	<tr>
		<th>エリア情報</th>
		<td colspan="3">
			<select name="area_cd" id="area_cd" class="selectBox">
				<option value="">---</option>
				{!! \App\Support\LegacyHtml::areaOptions($v('area_cd')) !!}
			</select>
		</td>
	</tr>
	<tr>
		<th>ステイタス</th>
		<td colspan="3"{!! $ng('status') !!}>
			<div style="display: inline-block; margin-right: 20px;">
				<label>
					<input type="radio" name="is_closed" value="1" {!! (int) $v('is_closed') === 1 ? "checked='checked'" : '' !!}> 閉店済み
				</label>
				<label>
					<input type="radio" name="is_closed" value="0" {!! (int) $v('is_closed') !== 1 ? "checked='checked'" : '' !!}> 営業中
				</label>
			</div>
			<label>
				<input type="checkbox" name="is_new_store" value="1" {!! (int) $v('is_new_store') === 1 ? "checked='checked'" : '' !!}> 新店フラグ
			</label>
			対象期間 <input type="text" name="new_store_start_date" value="{{ $v('new_store_start_date') }}" style="width:100px"> ～
			<input type="text" name="new_store_end_date" value="{{ $v('new_store_end_date') }}" style="width:100px">
			{!! $msg('status') !!}
		</td>
	</tr>
	<tr>
		<th><label for="zip">郵便番号</label></th>
		<td colspan="3"{!! $ng('zip') !!}><input type="text" name="zip" id="zip" class="inputS" value="{{ $v('zip') }}" style="ime-mode: disabled;" />&nbsp;
		<input type="button" name="zip_search" id="zip_search" value="住所検索" />{!! $msg('zip') !!}</td>
	</tr>
	<tr>
		<th>都道府県</th>
		<td colspan="3"><select name="pref" id="pref" class="selectBox">
		{!! \App\Support\LegacyHtml::prefOptions($v('pref')) !!}
		</select></td>
	</tr>
	<tr>
		<th><label for="add"><small>市区町村(カナ)</small></label></th>
		<td colspan="3"{!! $ng('add_kana') !!}><input type="text" name="add_kana" id="add_kana" class="writeArea inputSmall" value="{{ $v('add_kana') }}" />
		{!! \App\Support\LegacyHtml::tooltip(41) !!}{!! $msg('add_kana') !!}
		</td>
	</tr>
	<tr>
		<th><label for="add">市区町村</label></th>
		<td colspan="3"><input type="text" name="add" id="add" class="writeArea" value="{{ $v('add') }}" />
		{!! \App\Support\LegacyHtml::tooltip(40) !!}
		</td>
	</tr>
	<tr>
		<th><label for="add_number">丁目・番地</label></th>
		<td colspan="3"><input type="text" name="add_number" id="add_number" class="inputM" value="{{ $v('add_number') }}" />
		{!! \App\Support\LegacyHtml::tooltip(2) !!}
		</td>
	</tr>
	<tr>
		<th><label for="add_build_kana"><small>マンション・ビル名(カナ)</small></label></th>
		<td colspan="3"{!! $ng('add_build_kana') !!}><input type="text" name="add_build_kana" id="add_build_kana" class="writeArea inputSmall" value="{{ $v('add_build_kana') }}" />
		{!! \App\Support\LegacyHtml::tooltip(11) !!}{!! $msg('add_build_kana') !!}
		</td>
	</tr>
	<tr>
		<th><label for="add_build">マンション・ビル名</label></th>
		<td colspan="3"><input type="text" name="add_build" id="add_build" class="writeArea" value="{{ $v('add_build') }}" />
		{!! \App\Support\LegacyHtml::tooltip(10) !!}
		</td>
	</tr>
	<tr>
		<th><label for="tel">電話番号</label></th>
		<td{!! $ng('tel') !!}><input type="text" name="tel" id="tel" class="inputM" value="{{ $v('tel') }}" style="ime-mode: disabled;" />
		{!! \App\Support\LegacyHtml::tooltip(0) !!}{!! $msg('tel') !!}
		</td>

		<th><label for="fax">ファックス番号</label></th>
		<td{!! $ng('fax') !!}><input type="text" name="fax" id="fax" class="inputM" value="{{ $v('fax') }}" style="ime-mode: disabled;" />
		{!! \App\Support\LegacyHtml::tooltip(0) !!}{!! $msg('fax') !!}
		</td>
	</tr>
	@if ((string) $admin->application_use_flg === '1')
	<tr>
		<th><label for="mycolle_tel">マイコレ検索用電話番号</label></th>
		<td colspan="3"{!! $ng('mycolle_tel') !!}><input type="text" name="mycolle_tel" id="mycolle_tel" class="inputM" value="{{ $v('mycolle_tel') }}" style="ime-mode: disabled;" />
		{!! \App\Support\LegacyHtml::tooltip(0) !!}{!! $msg('mycolle_tel') !!}
		</td>
	</tr>
	@endif
	<tr>
		<th><label for="shop_url">店舗ホームページアドレス</label></th>
		<td colspan="3"{!! $ng('shop_url') !!}><input type="text" name="shop_url" id="shop_url" class="writeArea" value="{{ $v('shop_url') !== '' || $check ? $v('shop_url') : 'http://' . request()->getHost() . '/hp/m/' }}" style="ime-mode: disabled;" />{!! $msg('shop_url') !!}</td>
	</tr>
	<tr>
		<th><label for="email">お問い合わせ用メールアドレス<em>※</em></label></th>
		<td colspan="3"{!! $ng('email') !!}><input type="text" name="email" id="email" class="writeArea" value="{{ $v('email') }}" style="ime-mode: disabled;" /><br />このアドレスがお問い合わせ用のメールアドレスになります。{!! $msg('email') !!}</td>
	</tr>
	<tr{!! $hide !!}>
		<th><label for="tantou_kana"><small>責任者(カナ)</small></label></th>
		<td colspan="3"{!! $ng('tantou_kana') !!}><input type="text" name="tantou_kana" id="tantou_kana" class="inputL inputSmall" value="{{ $v('tantou_kana') }}" />
		{!! \App\Support\LegacyHtml::tooltip(4) !!}{!! $msg('tantou_kana') !!}
		</td>
	</tr>
	<tr{!! $hide !!}>
		<th><label for="tantou">責任者<em>※</em></label></th>
		<td colspan="3"{!! $ng('tantou') !!}><input type="text" name="tantou" id="tantou" class="inputL" value="{{ $v('tantou') }}" />
		{!! \App\Support\LegacyHtml::tooltip(3) !!}{!! $msg('tantou') !!}
		</td>
	</tr>
	<tr{!! $hide !!}>
		<th><label for="kyuyo_marume">給与丸め</label></th>
		<td colspan="3"><select name="kyuyo_marume" id="kyuyo_marume" class="selectBox" />

		<option value="15"@if ((string) $v('kyuyo_marume') === '15') selected="selected"@endif>15</option>
		<option value="30"@if ((string) $v('kyuyo_marume') === '30') selected="selected"@endif>30</option>
		</select>分
		</td>
	</tr>
	<tr{!! $hide !!}>
		<th><label for="start_work_time1">始業時間</label></th>
		<td><select name="start_work_time1" id="start_work_time1" class="selectBox" />
		@for ($i = 0; $i <= 23; $i++)
			<option value="{{ $i }}"@if ($i === $hourSelected) selected="selected"@endif>{{ $i }}</option>
		@endfor
		</select>時
		<select name="start_work_time2" id="start_work_time2" class="selectBox" />
		@for ($i = 0; $i <= 6; $i++)
			<option value="{{ $i }}0"@if ((string) $v('start_work_time2') === $i . '0') selected="selected"@endif>{{ $i }}0</option>
		@endfor
		</select>分
		</td>

		<th><label for="break_time">休憩時間</label></th>
		<td><select name="break_time" id="break_time" class="selectBox" />
		@php $breakSelected = in_array((string) $v('break_time'), array_map(fn ($i) => $i . '0', range(0, 18)), true) ? (string) $v('break_time') : '60'; @endphp
		@for ($i = 0; $i <= 18; $i++)
			<option value="{{ $i }}0"@if ($breakSelected === $i . '0') selected="selected"@endif>{{ $i }}0</option>
		@endfor
		</select>分
		</td>
	</tr>
	<tr{!! $hide !!}>
		<th><label for="shiire_id">仕入れID</label></th>
		<td><input type="text" name="shiire_id" id="shiire_id" class="inputM" value="{{ $v('shiire_id') }}" style="ime-mode: disabled;" />{!! $msg('shiire_id') !!}</td>
		<th><label for="shiire_pass">仕入れパスワード</label></th>
		<td><input type="text" name="shiire_pass" id="shiire_pass" class="inputM" value="{{ $v('shiire_pass') }}" style="ime-mode: disabled;" />{!! $msg('shiire_pass') !!}</td>
	</tr>
	<tr{!! $hide !!}>
		<th><label for="latitude">緯度</label></th>
		<td><input type="text" name="latitude" id="latitude" class="inputM" value="{{ $v('latitude') }}" style="ime-mode: disabled;" /></td>
		<th><label for="longitude">経度</label></th>
		<td><input type="text" name="longitude" id="longitude" class="inputM" value="{{ $v('longitude') }}" style="ime-mode: disabled;" /></td>
	</tr>
	@if ((string) $admin->application_use_flg === '1' && (string) $admin->beacon_use_flg === '1')
	@foreach (['beacon_visiting_area' => '来店用ビーコン距離', 'beacon_accounting_area' => '会計用ビーコン距離'] as $field => $label)
	<tr>
		<th><label for="{{ $field }}">{{ $label }}</label></th>
		<td colspan="3">
			<select name="{{ $field }}">
				@foreach (\App\Services\Base\ShopService::BEACON_AREAS as $val)
					<option value="{{ $val }}"@if ((string) $val === (string) $v($field)) selected @endif>{{ $val > 0 ? $val / 100 : '至近距離' }}</option>
				@endforeach
			</select> m以内 <small style="font-size: 11px">(メートル以内)<br>
			※設定される値は目安となります。お客様の設置環境に合わせた設定をお願いいたします。</small>
		</td>
	</tr>
	@endforeach
	@endif

</table>
</fieldset>


<div class="btnBox">
<input type="hidden" name="shop_cd" id="shop_cd" class="btn" value="{{ $shopCd }}">
@if ($check)<input type="hidden" name="act" id="enter" class="btn" value="up">@endif
<input type="submit" name="enter" id="enter" class="btn" value="この内容で確認する">
</div>

</form>

<!-- / .contents --></div>
@endsection
