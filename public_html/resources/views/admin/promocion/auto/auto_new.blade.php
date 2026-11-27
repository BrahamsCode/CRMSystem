{{--
	自動メール新規作成 (condiciones) — legacy: system/category3/auto/auto_new.php
	Variables: $data (step1, step2_1/2/3 como arrays, step2_jikan, step2_fun, mail_type), $toolName, $kind, $isPush, $activeAc
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

{{-- El legacy compara $_POST['act'] con "new", que nunca llega: siempre «登録情報 修正» --}}
@section('title', '自動' . $toolName . '登録情報 修正 - ' . $siteName)

@php
	$d = fn (string $k) => $data[$k] ?? '';
	$in = fn (string $k, $v) => in_array((string) $v, array_map('strval', (array) ($data[$k] ?? [])), true);
@endphp

@section('js')
<script type="text/javascript" src="{{ asset('legacy/system/category3/js/auto_mail_select.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/js/formToWizard.js') }}"></script>
<script type="text/javascript">
function submitChk() {
	if ($("#step1_1").attr("checked") == true) {
		start = 1; end = 2;
	} else {
		start = 3; end = 3;
	}
	for (var i=start; i<=end; i++) {
		var checkitem = false;
		$(".chkbox"+i).each(function() {
			if ($(this).attr("checked") == true) {
				checkitem = true;
			}
		});
		if (!checkitem) {
			if(i == 1){
				alert("配信月をご選択下さい。");
			}else if(i == 2){
				alert("配信日をご選択下さい。");
			}else if(i == 3){
				alert("配信曜日をご選択下さい。");
			}
			return false;
		}
	}
}

$(document).ready(function() {
	$("{{ $activeAc }}").accordion("option", "active", 0);
	$("#form1").formToWizard({ submitButton: 'SaveAccount' })
});
</script>
@endsection

@section('content')
<div id="dummy"></div>
<div class="contents">
<p class="h2Title">■自動{{ $toolName }}情報</p>

<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.promocion.auto.rule-confirm') }}" onsubmit="return submitChk()">
@csrf
<fieldset>
<legend>用途を選択してください</legend>
@unless ($isPush)
<table class="tableBox" style="margin-bottom: 10px;">
	<tr>
		<th><label for="mail_type1">配信{{ $toolName }}タイプ</label></th>
		<td>
<ul class="checkBtn">
<li><input type="radio" name="mail_type" id="mail_type1" value="auto" @checked(in_array($d('mail_type'), ['', 'auto'], true)) /><label for="mail_type1">テキストのみ</label></li>
<li><input type="radio" name="mail_type" id="mail_type2" value="autodeco" @checked($d('mail_type') === 'autodeco') /><label for="mail_type2">デコメール</span></label></li>
</ul>
</td>
	</tr>
</table>
@endunless
<table class="tableBox bgHover">
	<tr>
		<td><label for="step1_1" style="display: block;"><input type="radio" name="step1" id="step1_1" class="mR5" value="1" @checked(in_array($d('step1'), ['', '1'], true)) /><span class="text2L bold">指定日に{{ $toolName }}配信</span><br />
		設定した月・日に、お客様へ{{ $toolName }}を配信します。</label></td>
	</tr>
	<tr>
		<td><label for="step1_2" style="display: block;"><input type="radio" name="step1" id="step1_2" class="mR5" value="2" @checked($d('step1') === '2') /><span class="text2L bold">毎曜日に{{ $toolName }}を配信</span><br />
		毎週、設定した曜日にお客様へ{{ $toolName }}を配信します。</label></td>
	</tr>
</table>
</fieldset>

<fieldset>
<legend>配信日程を設定してください</legend>
<div id="month_set">
<table class="tableBox">
	<tr>
		<th><label for="step2_1_1">配信月</label></th>
		<td>
			<table class="calenderTable">
				<tr>
@for ($i = 1; $i < 13; $i++)
					<td style="width: 8%;"><label for="step2_1_{{ $i }}"><span style="margin-bottom: 5px;">{{ $i }}月</span>
<input type="checkbox" name="step2_1[]" id="step2_1_{{ $i }}" class="chkbox1" value="{{ $i }}" @checked($in('step2_1', $i)) /></label></td>
@endfor
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<th><label for="step2_2_1">配信日</label></th>
		<td>
			<table class="calenderTable">
@for ($num = 1; $num <= 31; $num++)
	@if ($num % 7 === 1)
				<tr>
	@endif
					<td style="width: 14%;"><label for="step2_2_{{ $num }}"><span style="margin-bottom: 5px;">{{ $num }}日</span>
<input type="checkbox" name="step2_2[]" id="step2_2_{{ $num }}" class="chkbox2" value="{{ $num }}" @checked($in('step2_2', $num)) /></label></td>
	@if ($num % 7 === 0)
				</tr>
	@endif
@endfor
					<td style="width: 14%;"><label for="step2_2_last"><span style="margin-bottom: 5px;">月末</span>
<input type="checkbox" name="step2_2[]" id="step2_2_last" class="chkbox2" value="32" @checked($in('step2_2', 32)) /></label></td>
@for ($ii = 0; $ii < 7 - (31 % 7) - 1; $ii++)
		<td>&nbsp;</td>
@endfor
				</tr>
			</table>
		</td>
	</tr>
</table>
</div>
<div id="week_set">
<table class="tableBox">
	<tr>
		<th><label for="step2_3_1">配信曜日</label></th>
		<td>
			<table class="calenderTable">
				<tr>
@foreach (['日', '月', '火', '水', '木', '金', '土'] as $i => $label)
					<td style="width: 8%;"><label for="step2_3_{{ $i }}"><span style="margin-bottom: 5px;">{{ $label }}</span>
<input type="checkbox" name="step2_3[]" id="step2_3_{{ $i }}" class="chkbox3" value="{{ $i }}" @checked($in('step2_3', $i)) /></label></td>
@endforeach
				</tr>
			</table>
		</td>
	</tr>
</table>
</div>
</fieldset>

<fieldset id="month_set">
<legend>配信時間を設定してください</legend>
<table class="tableBox">
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
</table>
<div class="btnBox" id="SaveAccount">
<input type="hidden" name="kind" id="kind" value="{{ $kind }}" />
<input type="hidden" name="act" id="act" value="">
<input type="submit" name="enter" id="enter" class="btn btnStyleM" value="メール配信条件確認へ" />
</div>
</fieldset>
</form>
<!-- / .contents --></div>
@endsection
