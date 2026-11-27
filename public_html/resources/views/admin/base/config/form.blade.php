{{-- 基本情報確認・変更: system/category0/config/config.php (y config_confirm.php con errores).
     Con $setup: 基本情報登録 de primer acceso (system/config.php), sin menú ni ホームページ情報. --}}
@php
    $ng = fn ($f) => $check ? $check->ng($f) : '';
    $msg = fn ($f) => $check ? $check->messages($f) : '';
    $v = fn ($f) => $data[$f] ?? '';
    $tip = fn ($n) => \App\Support\LegacyHtml::tooltip($n);
    $hour = (string) $v('start_work_time1');
@endphp
@extends('layouts.legacy')

@section('title', $setup ? '基本情報登録 - ' . $siteName : '基本情報登録 -総合業務管理システム-')

@if ($setup)
@section('top_page', '1')
@else
@section('menu', 'base')
@endif

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac1").accordion('option', 'active', 0);

	$("#zip").mask("9999999",{placeholder:""});

	$("#zip_search").click(function() {
		$("#add").attr("value","");
		zip = $("#zip").attr("value");
		$.getJSON("{{ $setup ? 'category0/config/zip.php' : 'zip.php' }}",{
						zip: zip
		}, change_addr)
	});
});

</script>
<script type="text/javascript" src="/system/js/jquery.maskedinput-1.2.2.js"></script>

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
<p class="h2Title">■{{ $setup ? '基本情報登録' : '基本情報確認・変更' }}</p>

<p><span class="attention">※</span>マークがある項目は、必須項目です。（空白にはしないで下さい）</p>

<form method="post" name="form1" id="form1" class="form" action="config_confirm.php">

<fieldset>
<legend>会社情報</legend>
<table class="tableBox">
	<tr>
		<th><label for="kaishamei_kana"><small>会社名/屋号(カナ)</small></label></th>
		<td colspan="3"{!! $ng('kaishamei_kana') !!}><input type="text" name="kaishamei_kana" id="kaishamei_kana" class="writeArea inputSmall" value="{{ $v('kaishamei_kana') }}" />
		{!! $tip(8) !!}{!! $msg('kaishamei_kana') !!}
		</td>
	</tr>
	<tr>
		<th><label for="kaishamei">会社名/屋号<em>※</em></label></th>
		<td colspan="3"{!! $ng('kaishamei') !!}><input type="text" name="kaishamei" id="kaishamei" class="writeArea" value="{{ $v('kaishamei') }}" />
		{!! $tip(7) !!}{!! $msg('kaishamei') !!}
		</td>
	</tr>
	<tr>
		<th><label for="daihyoushamei_kana"><small>代表者名(カナ)</small></label></th>
		<td colspan="3"{!! $ng('daihyoushamei_kana') !!}><input type="text" name="daihyoushamei_kana" id="daihyoushamei_kana" class="writeArea inputSmall" value="{{ $v('daihyoushamei_kana') }}" />
		{!! $tip(4) !!}{!! $msg('daihyoushamei_kana') !!}
		</td>
	</tr>
	<tr>
		<th><label for="daihyoushamei">代表者名</label></th>
		<td colspan="3"><input type="text" name="daihyoushamei" id="daihyoushamei" class="writeArea" value="{{ $v('daihyoushamei') }}" />
		{!! $tip(3) !!}
		</td>
	</tr>
	<tr>
		<th><label for="toukibi">登記年月日</label></th>
		<td colspan="3"{!! $ng('toukibi') !!}><input type="text" name="toukibi" id="toukibi" class="inputM" value="{{ $v('toukibi') }}" />
		{!! $tip(12) !!}{!! $msg('toukibi') !!}
		</td>
	</tr>
	<tr>
		<th>業種</th>
		<td colspan="3">
		<select name="gyoushu">
		<option value="">▼業種選択</option>
		@foreach (\App\Services\Base\OfficeService::GYOUSHU as $g)
		<option value="{{ $g }}" @if ((string) $v('gyoushu') === $g) selected="selected" @endif>{{ $g }}</option>
		@endforeach
		</select>
		</td>
	</tr>
	<tr>
		<th><label for="zip">郵便番号</label></th>
		<td colspan="3"{!! $ng('zip') !!}><input type="text" name="zip" id="zip" class="inputS" value="{{ $v('zip') }}"/>&nbsp;
		<input type="button" name="zip_search" id="zip_search" value="住所検索" />
		{!! $tip(0) !!}{!! $msg('zip') !!}
		</td>
	</tr>
	<tr>
		<th>都道府県</th>
		<td colspan="3"><select name="pref" id="pref" class="selectBox">
		{!! \App\Support\LegacyHtml::prefOptions($v('pref')) !!}</select>
		</td>
	</tr>
	<tr>
		<th><label for="add_kana"><small>市区町村(カナ)</small></label></th>
		<td colspan="3"{!! $ng('add_kana') !!}><input type="text" name="add_kana" id="add_kana" class="writeArea inputSmall" value="{{ $v('add_kana') }}" />
		{!! $tip(41) !!}{!! $msg('add_kana') !!}
		</td>
	</tr>
	<tr>
		<th><label for="add">市区町村</label></th>
		<td colspan="3"><input type="text" name="add" id="add" class="writeArea" value="{{ $v('add') }}" />
		{!! $tip(40) !!}
		</td>
	</tr>
	<tr>
		<th><label for="add_number">丁目・番地</label></th>
		<td colspan="3"><input type="text" name="add_number" id="add_number" class="inputL" value="{{ $v('add_number') }}" />
		{!! $tip(2) !!}
		</td>
	</tr>
	<tr>
		<th><label for="add_build_kana"><small>マンション・ビル名(カナ)</small></label></th>
		<td colspan="3"{!! $ng('add_build_kana') !!}><input type="text" name="add_build_kana" id="add_build_kana" class="writeArea inputSmall" value="{{ $v('add_build_kana') }}" />
		{!! $tip(11) !!}{!! $msg('add_build_kana') !!}
		</td>
	</tr>
	<tr>
		<th><label for="add_build">マンション・ビル名</label></th>
		<td colspan="3"><input type="text" name="add_build" id="add_build" class="writeArea" value="{{ $v('add_build') }}" />
		{!! $tip(10) !!}
		</td>
	</tr>
	<tr>
		<th><label for="tel">電話番号</label></th>
		<td{!! $ng('tel') !!}><input type="text" name="tel" id="tel" class="inputM" value="{{ $v('tel') }}" />
		{!! $tip(0) !!}{!! $msg('tel') !!}
		</td>
		<th><label for="fax">ファックス番号</label></th>
		<td{!! $ng('fax') !!}><input type="text" name="fax" id="fax" class="inputM" value="{{ $v('fax') }}" />
		{!! $tip(0) !!}{!! $msg('fax') !!}
		</td>
	</tr>
	<tr>
		<th><label for="kyuyo_marume">給与丸め</label></th>
		<td colspan="3"><select name="kyuyo_marume" id="kyuyo_marume" class="selectBox" />
		<option value="15"@if ((string) $v('kyuyo_marume') === '15') selected="selected"@endif>15</option>
		<option value="30"@if ((string) $v('kyuyo_marume') === '30') selected="selected"@endif>30</option>
		</select>分
		</td>
	</tr>
	<tr>
		<th><label for="start_work_time1">始業時間</label></th>
		<td><select name="start_work_time1" id="start_work_time1" class="selectBox" />
		@for ($i = 0; $i <= 23; $i++)
			<option value="{{ $i }}"@if ((int) $hour === $i) selected="selected"@endif>{{ $i }}</option>
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
		@for ($i = 0; $i <= 18; $i++)
			<option value="{{ $i }}0"@if ((string) $v('break_time') === $i . '0') selected="selected"@endif>{{ $i }}0</option>
		@endfor
		</select>分
		</td>
	</tr>
</table>
</fieldset>

@unless ($setup)
<fieldset>
<legend>ホームページ情報</legend>
<table class="tableBox">
	<tr>
		<th><label for="hp_hyouji_name">ホームページ表示名称</label></th>
		<td colspan="3"><input type="text" name="hp_hyouji_name" id="hp_hyouji_name" class="writeArea" value="{{ $v('hp_hyouji_name') }}" /></td>
	</tr>
	<tr>
		<th><label for="hp_hyouji_tel">ホームページ表示電話番号</label></th>
		<td colspan="3"><input type="text" name="hp_hyouji_tel" id="hp_hyouji_tel" class="writeArea" value="{{ $v('hp_hyouji_tel') }}" style="ime-mode: disabled;" /></td>
	</tr>
	<tr>
		<th><label for="shop_url">店舗ホームページアドレス</label></th>
		<td colspan="3"{!! $ng('shop_url') !!}><input type="text" name="shop_url" id="shop_url" class="writeArea" value="{{ $v('shop_url') }}" style="ime-mode: disabled;" />{!! $msg('shop_url') !!}</td>
	</tr>
	<tr>
		<th><label for="email">お問い合わせメールアドレス<em>※</em></label></th>
		<td colspan="3"{!! $ng('email') !!}><input type="text" name="email" id="email" class="inputL" value="{{ $v('email') }}" />{!! $msg('email') !!}</td>
	</tr>
</table>
</fieldset>
@else
<input type="hidden" name="shop_url" value="{{ $v('shop_url') }}" />
<input type="hidden" name="email" value="{{ $v('email') ?: $v('tantou_email') }}" />
@endunless

<fieldset>
<legend>管理者情報</legend>
<table class="tableBox">
	<tr>
		<th><label for="tantou">担当者<em>※</em></label></th>
		<td colspan="3"{!! $ng('tantou') !!}><input type="text" name="tantou" id="tantou" class="inputL" value="{{ $v('tantou') }}" />
		{!! $tip(3) !!}{!! $msg('tantou') !!}
		</td>
	</tr>
	<tr>
		<th><label for="tantou_tel">担当者 電話番号</label></th>
		<td{!! $ng('tantou_tel') !!}><input type="text" name="tantou_tel" id="tantou_tel" class="inputM" value="{{ $v('tantou_tel') }}" />
		{!! $tip(0) !!}{!! $msg('tantou_tel') !!}
		</td>
		<th><label for="tantou_fax">担当者 ファックス番号</label></th>
		<td{!! $ng('tantou_fax') !!}><input type="text" name="tantou_fax" id="tantou_fax" class="inputM" value="{{ $v('tantou_fax') }}" />
		{!! $tip(0) !!}{!! $msg('tantou_fax') !!}
		</td>
	</tr>
	<tr>
		<th><label for="tantou_email">担当者 メールアドレス<em>※</em></label></th>
		<td colspan="3"{!! $ng('tantou_email') !!}><input type="text" name="tantou_email" id="tantou_email" class="inputL" value="{{ $v('tantou_email') }}" />{!! $msg('tantou_email') !!}</td>
	</tr>
	<tr>
		<th>ID</th>
		<td>master</td>
		<th><label for="pass">パスワード<em>※</em></label></th>
		<td{!! $ng('pass') !!}><input type="text" name="pass" id="pass" class="inputM" value="{{ $v('pass') }}" />
		{!! $tip(100) !!}{!! $msg('pass') !!}
	</td>
	</tr>
</table>
</fieldset>

<div class="btnBox">
<input type="submit" name="enter" id="enter" class="btn" value="この内容で確認する" />
</div>

</form>

<!-- / .contents --></div>
@endsection
