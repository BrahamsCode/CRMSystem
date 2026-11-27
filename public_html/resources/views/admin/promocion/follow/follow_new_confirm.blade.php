{{--
	フォローメール配信条件確認 — legacy: system/category3/follow/follow_new_confirm.php
	Variables: $data, $shopName, $purpose, $lb1, $lb2, $searchUniqid, $reservationsEnabled, $nextAction, $toolName, $kind, $isPush, $activeAc
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', 'フォロー' . $toolName . '登録情報 修正 - ' . $siteName)

@php
	$d = fn (string $k) => (string) ($data[$k] ?? '');
	$scalars = array_filter($data, fn ($v) => ! is_array($v));
@endphp

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$("{{ $activeAc }}").accordion("option", "active", 0);
});
</script>
@endsection

@section('content')
<div id="dummy"></div>
<div class="contents form">
<p class="h2Title">■フォロー{{ $toolName }}配信条件確認</p>

<fieldset>
<legend>設定内容を確認してください</legend>
<table class="tableBox">
	<tr>
		<th>配信対象店舗</th>
		<td>{{ $shopName }}</td>
	</tr>
@unless ($isPush)
	<tr>
		<th>配信{{ $toolName }}タイプ</th>
		<td>{{ match ($d('mail_type')) { 'follow' => 'テキストのみ', 'followdeco' => 'デコメール', default => '' } }}</td>
	</tr>
@endunless
	<tr>
		<th>用途</th>
		<td>{{ $purpose }}</td>
	</tr>
	<tr>
		<th>配信日</th>
		<td><span class="text3L bold attention">{{ $lb1 }}</span><span class="text3L bold attention">{{ $d('step2_1') }}{{ $lb2 }}</span> のお客様へ</td>
	</tr>
	<tr>
		<th>配信時間</th>
		<td><span class="text3L bold attention">{{ $d('step2_jikan') }}時{{ $d('step2_fun') }}分</span></td>
	</tr>
@if ($d('step1') === '1' && $reservationsEnabled)
	<tr>
		<th>予約管理</th>
		<td>既に予約があるお客様に{{ $toolName }}を<span class="text3L bold attention">{{ $d('step2_yoyakuflg') === '1' ? '配信しない' : '配信する' }}</span></td>
	</tr>
@endif
</table>

<p class="textCenter">
上記条件で{{ $toolName }}を配信する設定です。<br />
以上の内容でよろしければ、「{{ $toolName }}配信内容設定」を押して次へ進んで下さい。<br />
<span class="attention">次のセクションへ進むと、以上の設定を変更する事はできません。</span>
</p>

<div class="btnBox">
<form method="post" action="{{ route('admin.promocion.follow.rule') }}">
@csrf
@foreach ($scalars as $key => $val)
<input type="hidden" name="{{ $key }}" value="{{ $val }}" />
@endforeach
<input type="submit" name="modoru" value="書き直す" class="existBtn btnStyleM" />
</form>

<form method="post" name="form1" id="form1" class="form" action="{{ $nextAction }}">
@csrf
	<input type="hidden" name="search_uniqid" value="{{ $searchUniqid }}" />
@foreach ($scalars as $key => $val)
<input type="hidden" name="{{ $key }}" value="{{ $val }}" />
@endforeach
<input type="submit" name="enter" id="enter" class="btn btnStyleM" value="{{ $toolName }}配信内容設定へ" />
</div>
</fieldset>
</form>
<!-- / .contents --></div>
@endsection
