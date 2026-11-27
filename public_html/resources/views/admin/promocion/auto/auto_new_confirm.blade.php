{{--
	自動メール配信条件確認 — legacy: system/category3/auto/auto_new_confirm.php
	Variables: $data (limpio), $hidden (arrays unidos con coma), $nextAction (auto_mail_customer_select), $toolName, $kind, $isPush, $activeAc
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', '自動' . $toolName . '登録情報 修正 - ' . $siteName)

@php
	$d = fn (string $k) => $data[$k] ?? '';
	$months = (array) ($data['step2_1'] ?? []);
	$days = (array) ($data['step2_2'] ?? []);
	$weekdays = (array) ($data['step2_3'] ?? []);
	$dow = ['日', '月', '火', '水', '木', '金', '土'];
@endphp

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$("{{ $activeAc }}").accordion("option", "active", 0);
});
</script>
<script type="text/javascript" src="{{ asset('legacy/system/js/formToWizard.js') }}"></script>
<script type="text/javascript">
	$(document).ready(function(){
		$("#form1").formToWizard({ submitButton: 'SaveAccount' })
	});
</script>
@endsection

@section('content')
<div id="dummy"></div>
<div class="contents form">
<p class="h2Title">■自動{{ $toolName }}配信条件確認</p>

<fieldset>
<legend>設定内容を確認してください</legend>
<table class="tableBox">
@unless ($isPush)
<tr>
		<th>配信{{ $toolName }}タイプ</th>
		<td>{{ match ($d('mail_type')) { 'auto' => 'テキストのみ', 'autodeco' => 'デコメール', default => '' } }}</td>
	</tr>
@endunless
	<tr>
		<th>用途</th>
		<td>@if ($d('step1') === '1')設定月の同日に{{ $toolName }}を配信@elseif ($d('step1') === '2')毎曜日に{{ $toolName }}を配信@endif</td>
	</tr>
@if ($d('step1') === '1')
	<tr>
		<th>配信月</th>
		<td><span class="text3L bold attention">
@if ($months !== []){{ implode('月 ', $months) }}月@endif
</span></td>
	</tr>
	<tr>
		<th>配信日</th>
		<td><span class="text3L bold attention">
{{ str_replace('32日', '月末', implode('日 ', $days) . '日') }}
</span></td>
	</tr>
@endif
@if ($d('step1') === '2')
	<tr>
		<th>配信曜日</th>
		<td><span class="text3L bold attention">
@foreach ($weekdays as $w){{ $dow[(int) $w] ?? '' }}曜日 @endforeach
</span></td>
	</tr>
@endif
	<tr>
		<th>配信時間</th>
		<td><span class="text3L bold attention">{{ $d('step2_jikan') }}時{{ $d('step2_fun') }}分</span></td>
	</tr>
</table>

<p class="textCenter">
上記条件で{{ $toolName }}を配信する設定です。<br />
以上の内容でよろしければ、「{{ $toolName }}配信内容設定」を押して次へ進んで下さい。<br />
<span class="attention">次のセクションへ進むと、以上の設定を変更する事はできません。</span>
</p>

<div class="btnBox">
<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.promocion.auto.rule') }}">
@csrf
@foreach ($hidden as $key => $val)
<input type="hidden" name="{{ $key }}" value="{{ $val }}" />
@endforeach
<input type="submit" name="modoru" value="書き直す" class="existBtn btnStyleM" />
</form>

<form method="post" name="form2" id="form2" class="form" action="{{ $nextAction }}">
@csrf
@foreach ($hidden as $key => $val)
<input type="hidden" name="{{ $key }}" value="{{ $val }}" />
@endforeach
<input type="submit" name="enter" id="enter" class="btn btnStyleM" value="{{ $toolName }}配信内容設定へ" />
</form>
</div>
</fieldset>
<!-- / .contents --></div>
@endsection
