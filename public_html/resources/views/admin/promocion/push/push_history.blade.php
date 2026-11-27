{{--
	プッシュ集計 — legacy: system/category3/push/push_history.php
	Variables: $rows (PushReport::history() con body y audience), $kensu, $page, $maxrows, $activeAc
--}}
@php
	$pageTitle = 'プッシュ集計';
	$from = ($page - 1) * $kensu;
	$listUrl = route('admin.promocion.push.history');
@endphp
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', $pageTitle . '一覧 - ' . $siteName)

@section('css')
<style>
.message_cell div {
    display: block;
    height: 50px;
    overflow: auto;
    border: none;
}
.message_cell p {
    max-width: 140px;
}
</style>
@endsection

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$("{{ $activeAc }}").accordion('option', 'active', 0);
	window.scroll(0,0);
});

//表示件数の制御 (el legacy llamaba a kensuSet sin definirla)
function kensuSet(parts){
	document.form_search.kensu.value = parts.value;
	document.form_search.submit();
}
</script>
@endsection

@section('content')
<form method="get" name="form_search" id="form_search" class="displayNone" action="{{ $listUrl }}">
<input type="hidden" name="kensu" value="{{ $kensu }}" />
<input type="hidden" name="page" value="{{ $page }}" />
</form>

<div class="contents">

<p class="h2Title">■{{ $pageTitle }}</p>

@if ($maxrows == 0)

	<p>該当のデータはありません。</p>

@else

	<div id="form">
		<p class="maxListLength">表示件数<select name="kensu_act" id="kensu_act" class="selectBox" onchange="kensuSet(this)">@include('admin.promocion.push._kensu')</select></p>
	</div>

	<p><span class="attention">{{ $from + 1 }}件～{{ min($from + $kensu, $maxrows) }}件目/{{ $maxrows }}件中</span></p>

	<table class="tableBox2">
		<colgroup>
			<col width="80"><col width=""><col width=""><col width=""><col width=""><col width="80"><col width=""><col width=""><col width=""><col width=""><col width="">
		</colgroup>
		<tr>
			<th>配信日</th>
			<th>曜日</th>
			<th>時間帯</th>
			<th>配信対象</th>
			<th>タイトル</th>
			<th>内容</th>
			<th>
				会員件数<br>
				<small style="font-size: 8px;">条件でヒットする件数</small>
			</th>
			<th>配信数</th>
			<th>閲覧数</th>
			<th>未読人数</th>
			<th>閲覧率</th>
		</tr>
		@foreach ($rows as $row)
		@php($detail = fn (string $type) => route('admin.promocion.push.history.show') . '?' . http_build_query(['list_page' => $page, 'list_kensu' => $kensu, 'master_cd' => $row->cd, 'type' => $type]))
			<tr>
				<td>{{ $row->push_date }}</td>
				<td>{{ $row->push_date_week }}</td>
				<td>{{ $row->push_date_time }}</td>
				<td class="message_cell"><p>{{ $row->search_display }}</p></td>
				<td class="message_cell"><p>{{ $row->title }}</p></td>
				<td class="message_cell"><div>{!! $row->body !!}</div></td>
				<td>{{ $row->audience }}</td>
				<td><a href="{{ $detail('haishin') }}">{{ $row->haishin }}</a></td>
				<td><a href="{{ $detail('eturan') }}">{{ $row->opened }}</a></td>
				<td>{{ $row->haishin - $row->opened }}</td>
				<td>{{ $row->rate }}</td>
			</tr>
		@endforeach
	</table>

	<ul class="btnBox2">
		@include('admin.promocion.push._pager', ['url' => $listUrl, 'params' => []])
	</ul>

@endif

<!-- / .contents --></div>
@endsection
