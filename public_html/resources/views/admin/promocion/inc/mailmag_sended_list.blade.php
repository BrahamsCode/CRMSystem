{{--
	配信先一覧 (クリック詳細) — legacy: system/category3/inc/mailmag_sended_list.php
	Variables: $mailType, $toolName, $pageTitle, $mail, $rows (paginador de 50), $backQuery, $routes
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', $pageTitle . '詳細')

@php
	$from = ($rows->currentPage() - 1) * 50;
	$pageLink = fn (int $p) => $routes['recipients'] . '?' . http_build_query(array_merge($backQuery, ['mailmagcd' => $mail->cd, 'page' => $p]));
@endphp

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$("{{ $mailType === 'deco' ? '.ac9' : '.ac1' }}").accordion('option', 'active', 0);
	window.scroll(0,0);
});
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■{{ $pageTitle }}</p>

<table class="tableBox" style="table-layout:fixed;">
	<tr>
		<th style="text-align:center;width:20px;">ID</th>
		<td style="text-align:center;width:60px;">{{ $mail->cd }}</td>
		<th style="text-align:center;width:60px;">配信時間</th>
		<td style="text-align:center;width:110px;">{{ $mail->getAttribute('配信日時') }}</td>
		<th style="text-align:center;width:100px;">{{ $toolName }}タイトル</th>
		<td style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $mail->getAttribute('タイトル') }}</td>
	</tr>
</table>

@if ($rows->total() > 0)
<p><span class="attention">{{ $from + 1 }}件～{{ min($from + 50, $rows->total()) }}件目/{{ $rows->total() }}件中</span></p>
<table class="tableBox2">
	<tr>
		<th>顧客ID</th>
		<th>管理番号</th>
		<th>顧客名</th>
		<th>配信先メールアドレス</th>
		<th>生年月日</th>
		<th>開封</th>
	</tr>
	@foreach ($rows as $i => $row)
	<tr @if ($i % 2 == 1) class="tableBg" @endif>
		<td>{{ $row->id }}</td>
		<td>{{ $row->kanri_id }}</td>
		<td>{{ $row->name }}</td>
		<td>{{ $row->email }}</td>
		<td>{{ $row->kojin_birth }}</td>
		<td style="text-align:center;">{{ $row->click_time ? '○' : '' }}</td>
	</tr>
	@endforeach
</table>

<ul class="btnBox2">
@if ($rows->currentPage() > 1)
<li class="btn"><a href="{{ $pageLink($rows->currentPage() - 1) }}"><img src="{{ asset('legacy/system/img/btn_back_off.gif') }}" width="92" height="32" alt="戻る" /></a></li>
@endif
@if ($rows->lastPage() > 1)
@for ($l = max(1, $rows->currentPage() - 5); $l <= min($rows->lastPage(), $rows->currentPage() + 5); $l++)
@if ($l === $rows->currentPage())
<li class="now"><b>{{ $l }}</b></li>
@else
<li><a href="{{ $pageLink($l) }}">{{ $l }}</a></li>
@endif
@endfor
@endif
@if ($rows->hasMorePages())
<li class="btn"><a href="{{ $pageLink($rows->currentPage() + 1) }}"><img src="{{ asset('legacy/system/img/btn_next_off.gif') }}" width="92" height="32" alt="次へ" /></a></li>
@endif
</ul>

<div style="margin:10px;text-align:center;">
	<form method="post" action="{{ $routes['recipients'] }}">
		@csrf
		<input name="mailmagcd" type="hidden" value="{{ $mail->cd }}" />
		<input type="hidden" name="csvFlg" value="1" />
		<input type="submit" name="enter" value="CSV出力する" />
	</form>
</div>
@else
<p>データがありません</p>
@endif

<p><a href="{{ $routes['past'] . '?' . http_build_query($backQuery) }}" class="backBtnStyle">一覧へ戻る</a><br/></p>
<!-- / .contents --></div>
@endsection
