{{--
	フォローメール新規作成 (condiciones) — legacy: system/category3/follow/follow_new.php
	Variables: $data, $shops, $staffShopName, $visitsEnabled, $reservationsEnabled, $customFields, $toolName, $kind, $isPush, $isMaster, $activeAc
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

{{-- El legacy compara $_POST['act'] con "new", que nunca llega: siempre «修正» --}}
@section('title', 'フォロー' . $toolName . '登録情報 修正 - ' . $siteName)

@php
	$d = fn (string $k) => (string) ($data[$k] ?? '');
@endphp

@section('js')
<script type="text/javascript" src="{{ asset('legacy/system/js/formToWizard.js') }}"></script>
<script type="text/javascript">
function submitChk() {
	if(document.form1.step2_1.value == ""){
		alert("配信日が空白です。\n\n日にちをご記入下さい。");
		return false;
	}else{
		if(!document.form1.step2_1.value.match(/^[0-9]+$/)){
			alert("配信日は半角数字のみでご記入ください。");
		return false;
		}
	}
}

function changeLabel() {
	var selectedItem = $("input[name='step1']:checked");
	$("#step2_lb1").text(selectedItem.attr('lb1'));
	$("#step2_lb2").text(selectedItem.attr('lb2'));
	if (selectedItem.val().substr(0,6) == 'custom') {
		$("#step2_zengo").show();
	} else {
		$("#step2_zengo").hide();
	}
	if ( selectedItem.val() == '1' && $("#yoyakuflgTr").length > 0 ) {
		$("#yoyakuflgTr").show();
	} else {
		$("#yoyakuflgTr").hide();
		$("#step2_yoyakuflg").attr('checked', false);
	}
}

$(document).ready(function() {
	$("{{ $activeAc }}").accordion("option", "active", 0);
	$(".youto_radio").click(function(){
		changeLabel();
	});
	$("#form1").formToWizard({ submitButton: 'SaveAccount' })
	changeLabel();
});
</script>
@endsection

@section('content')
<div id="dummy"></div>
<div class="contents">
<p class="h2Title">■フォロー{{ $toolName }}情報 修正</p>

<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.promocion.follow.rule-confirm') }}" onsubmit="return submitChk()">
@csrf
<fieldset>
<legend>用途を選択してください</legend>
<table class="tableBox" style="margin-bottom: 10px;">
	<tr>
		<th><label for="step1_1">配信対象店舗</label></th>
		<td>
@if ($isMaster)
		<select name="shop_cd">
@foreach ($shops as $shop)
		<option value="{{ $shop->cd }}" @selected($d('shop_cd') === (string) $shop->cd) >{{ $shop->kaishamei }}</option>
@endforeach
		<option value="-1" @selected($d('shop_cd') === '-1')>全店舗</option>
		</select>
@else
		{{ $staffShopName }}
		<input type="hidden" name="shop_cd" id="shop_cd" value="{{ $shops->first()?->cd }}" />
@endif
<span class="attention">※ここで選択された店舗の顧客に配信されますので、選択にはご注意ください。</span></td>
	</tr>
@unless ($isPush)
	<tr>
		<th><label for="mail_type1">配信{{ $toolName }}タイプ</label></th>
		<td>
		<ul class="checkBtn">
		<li><input type="radio" name="mail_type" id="mail_type1" value="follow" @checked(in_array($d('mail_type'), ['', 'follow'], true)) /><label for="mail_type1">テキストのみ</label></li>
		<li><input type="radio" name="mail_type" id="mail_type2" value="followdeco" @checked($d('mail_type') === 'followdeco') /><label for="mail_type2">デコメール</span></label></li>
		</ul>
		</td>
	</tr>
@endunless
</table>

<table class="tableBox bgHover">
@if ($visitsEnabled)
	<tr>
		<td><label for="step1_1" style="display: block;"><input class="youto_radio" type="radio" name="step1" id="step1_1" class="mR5" value="1" @checked(in_array($d('step1'), ['', '1'], true)) lb1="最終来店日から" lb2="日後" /><span class="text2L bold">来店後のフォロー{{ $toolName }}を配信</span><br />
		最終来店日から、設定した日にちを経過したお客様に{{ $toolName }}を配信します。</label></td>
	</tr>
@endif
	<tr>
		<td><label for="step1_2" style="display: block;"><input class="youto_radio" type="radio" name="step1" id="step1_2" value="2" @checked($d('step1') === '2') lb1="会員登録日から" lb2="日後" /><span class="text2L bold">会員登録日から○日後のお客様に{{ $toolName }}配信</span><br />
		会員登録日から、設定した日にちを経過したお客様に{{ $toolName }}を配信します。</label></td>
	</tr>
	<tr>
		<td><label for="step1_3" style="display: block;"><input class="youto_radio" type="radio" name="step1" id="step1_3" value="3" @checked($d('step1') === '3') lb1="誕生日から" lb2="日前" /><span class="text2L bold">誕生日から○日前のお客様に{{ $toolName }}配信</span><br />
		お客様の誕生日の数日前に{{ $toolName }}を配信します。</label></td>
	</tr>
@if ($visitsEnabled)
	<tr>
		<td><label for="step1_4" style="display: block;"><input class="youto_radio" type="radio" name="step1" id="step1_4" value="4" @checked($d('step1') === '4') lb1="最終来店日から来店周期日数後の" lb2="日前" /><span class="text2L bold">最終来店日から来店周期日数後のお客様に{{ $toolName }}配信</span><br />
		お客様の最終来店日から来店周期日数後に{{ $toolName }}を配信します。</label></td>
	</tr>
@endif
@foreach ($customFields as $field)
	@php $id = 'custom_' . $field->cd; @endphp
	<tr>
		<td><label for="step1_{{ $id }}" style="display: block;"><input class="youto_radio" type="radio" name="step1" id="step1_{{ $id }}" value="{{ $id }}" @checked($d('step1') === $id) lb1="{{ $field->name }}から" lb2="日" /><span class="text2L bold">{{ $field->name }}から○日前後のお客様に{{ $toolName }}配信</span><br />
		お客様の【{{ $field->name }}】の数日前後に{{ $toolName }}を配信します。</label></td>
	</tr>
@endforeach
</table>
</fieldset>

<fieldset>
<legend>配信日程を設定してください</legend>
<table class="tableBox">
	<tr>
		<th><label for="step2_1">配信日</label></th>
		<td><span id="step2_lb1"></span>
			<input type="text" name="step2_1" id="step2_1" class="input3S" value="{{ $d('step2_1') }}" style="ime-mode: disabled;" /><span id="step2_lb2"></span>
			<select name="step2_zengo" id="step2_zengo">
				<option value="前" @selected($d('step2_zengo') === '前')>前</option>
				<option value="後" @selected($d('step2_zengo') === '後')>後</option>
			</select>
			、お客様に{{ $toolName }}を配信します。
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
に{{ $toolName }}を配信します。
		</td>
	</tr>
@if ($reservationsEnabled)
	<tr id="yoyakuflgTr">
		<th><label for="step2_1">予約管理</label></th>
		<td>
			<input type="checkbox" name="step2_yoyakuflg" id="step2_yoyakuflg" value="1" @checked($d('step2_yoyakuflg') === '1') /><label for="step2_yoyakuflg">&nbsp;既に予約があるお客様に{{ $toolName }}配信しない</label>
		</td>
	</tr>
@endif
</table>
<div class="btnBox" id="SaveAccount">
<input type="hidden" name="kind" id="kind" value="{{ $kind }}" />
<input type="hidden" name="act" id="act" value="">
<input type="submit" name="enter" id="enter" class="btn btnStyleM" value="{{ $toolName }}配信条件確認へ" />
</div>
</fieldset>
</form>
<!-- / .contents --></div>
@endsection
