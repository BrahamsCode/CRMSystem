{{--
	リマインダーメール新規作成 — legacy: system/category3/reminder/reminder_new.php (sin POS: fuera de alcance)
	Variables: $data, $isMaster, $staffShopName, $staffShopCd, $shops
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', 'リマインダーメール新規作成 - ' . $siteName)

@php
	$d = fn (string $k) => (string) ($data[$k] ?? '');
@endphp

@section('js')
<script type="text/javascript" src="{{ asset('legacy/system/js/formToWizard.js') }}"></script>
<script type="text/javascript">
function submitChk() {
	if (document.form1.shop_cd.value == "") {
		alert("配信対象店舗を選択してください。");
		return false;
	}
	if (document.form1.step2_1.value == "") {
		alert("配信日が空白です。\n\n日にちをご記入下さい。");
		return false;
	} else {
		if (!document.form1.step2_1.value.match(/^[0-9]+$/)) {
			alert("配信日は半角数字のみでご記入ください。");
			return false;
		}
	}
}

$(document).ready(function() {
	$(".ac110").accordion('option', 'active', 0);
});
</script>
@endsection

@section('content')
<div id="dummy"></div>
<div class="contents">
<p class="h2Title">■リマインダーメール新規作成 - {{ $siteName }}</p>
<p>※リマインダーメールとは、指定の日時に予約確認メールを送る機能です。</p>

<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.promocion.reminder.rule-confirm') }}" onsubmit="return submitChk()">
@csrf
<fieldset>
<legend>基本情報</legend>
<table class="tableBox" style="margin-bottom: 10px;">
	<tr>
		<th><label for="step1_1">配信対象店舗</label></th>
		<td>
@if (! $isMaster)
			{{ $staffShopName }}<input type="hidden" name="shop_cd" id="shop_cd" value="{{ $staffShopCd }}" />
@else
			<select name="shop_cd" id="shop_cd">
			<option value="">▼店舗選択</option>
@foreach ($shops as $shop)
			<option value="{{ $shop->cd }}" @selected($d('shop_cd') === (string) $shop->cd)>{{ $shop->kaishamei }}</option>
@endforeach
			</select>
@endif
<span class="attention">※ここで選択された店舗の顧客に配信されますので、選択にはご注意ください。</span></td>
	</tr>
	<tr>
		<th><label for="mail_type1">配信メールタイプ</label></th>
		<td>
			<ul class="checkBtn">
			<li>
			<input type="radio" name="mail_type" id="mail_type1" value="yoyaku" @checked($d('mail_type') !== 'remdeco') />
			<label for="mail_type1">テキストのみ</label>
			</li>
			<li>
			<input type="radio" name="mail_type" id="mail_type2" value="remdeco" @checked($d('mail_type') === 'remdeco') />
			<label for="mail_type2">デコメール</label>
			</li>
			</ul>
		</td>
	</tr>
</table>

<legend>配信日程</legend>
<table class="tableBox">
	<tr>
		<th><label for="step2_1">配信日</label></th>
		<td>
			予約日の
			<input type="text" name="step2_1" id="step2_1" class="input3S" value="{{ $d('step2_1') }}" style="ime-mode: disabled;" />
			日前になったら、お客様にメールを配信します。
		</td>
	</tr>
	<tr>
		<th><label for="step2_2">配信時間</label></th>
		<td>
			<select name="step2_jikan" id="step2_jikan" class="selectBox" />
@for ($i = 0; $i <= 23; $i++)
				<option value="{{ sprintf('%02d', $i) }}" @selected($d('step2_jikan') === sprintf('%02d', $i))>{{ sprintf('%02d', $i) }}時</option>
@endfor
			</select>
			<select name="step2_fun" id="step2_fun" class="selectBox" />
@for ($i = 0; $i <= 55; $i += 5)
				<option value="{{ sprintf('%02d', $i) }}" @selected($d('step2_fun') === sprintf('%02d', $i))>{{ sprintf('%02d', $i) }}分</option>
@endfor
			</select>
			にメールを配信します。
		</td>
	</tr>
	<tr id="yoyakuflgTr">
		<th><label for="step2_1">携帯予約</label></th>
		<td>
			<input type="checkbox" name="step2_yoyakuflg" id="step2_yoyakuflg" value="1" @checked($d('step2_yoyakuflg') === '1') />
			<label for="step2_yoyakuflg">&nbsp;携帯予約の方にだけ配信する</label>
		</td>
	</tr>
</table>
<div class="btnBox" id="SaveAccount">
<input type="hidden" name="act" id="act" value="">
<input type="submit" name="enter" id="enter" class="btn btnStyleM" value="メール配信条件確認へ" />
</div>
</fieldset>
</form>
<!-- / .contents --></div>
@endsection
