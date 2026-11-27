{{--
	リマインダーメール配信条件確認 — legacy: system/category3/reminder/reminder_new_confirm.php
	Variables: $data, $shopName, $searchUniqid
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', 'リマインダーメール新規作成 - ' . $siteName)

@php
	$d = fn (string $k) => (string) ($data[$k] ?? '');
	$scalars = array_filter($data, fn ($v) => ! is_array($v));
@endphp

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac110").accordion('option', 'active', 0);
});
</script>
@endsection

@section('content')
<div id="dummy"></div>
<div class="contents form">
<p class="h2Title">■リマインダーメール配信条件確認</p>

<fieldset>
<legend>設定内容を確認してください</legend>
<table class="tableBox">
	<tr>
		<th>配信対象店舗</th>
		<td>{{ $shopName }}</td>
	</tr>
	<tr>
		<th>配信メールタイプ</th>
		<td>{{ $d('mail_type') === 'remdeco' ? 'デコメール' : 'テキストのみ' }}</td>
	</tr>
	<tr>
		<th>配信日</th>
		<td><span class="text3L bold attention">予約日の{{ $d('step2_1') }}日前</span>になったら、お客様へ配信</td>
	</tr>
	<tr>
		<th>配信時間</th>
		<td><span class="text3L bold attention">{{ $d('step2_jikan') }}時{{ $d('step2_fun') }}分</span></td>
	</tr>
@if ($d('step2_yoyakuflg') === '1')
	<tr>
		<th>携帯予約</th>
		<td>携帯予約の方にだけ配信する</td>
	</tr>
@endif
</table>

<p class="textCenter">
上記条件でメールを配信する設定です。<br />
以上の内容でよろしければ、「メール配信内容設定」を押して次へ進んで下さい。<br />
<span class="attention">次のセクションへ進むと、以上の設定を変更する事はできません。</span>
</p>

<div class="btnBox">
<form method="post" action="{{ route('admin.promocion.reminder.rule') }}">
@csrf
@foreach ($scalars as $key => $val)
<input type="hidden" name="{{ $key }}" value="{{ $val }}" />
@endforeach
<input type="submit" name="modoru" value="書き直す" class="existBtn btnStyleM" />
</form>

<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.promocion.reminder.create') }}">
@csrf
<input type="hidden" name="search_uniqid" value="{{ $searchUniqid }}" />
@foreach ($scalars as $key => $val)
<input type="hidden" name="{{ $key }}" value="{{ $val }}" />
@endforeach
<input type="submit" name="enter" id="enter" class="btn btnStyleM" value="メール配信内容設定へ" />
</form>
</div>
</fieldset>
<!-- / .contents --></div>
@endsection
