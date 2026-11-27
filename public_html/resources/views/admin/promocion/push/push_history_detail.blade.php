{{--
	プッシュ集計 - 詳細 — legacy: system/category3/push/push_history_detail.php
	Variables: $rows (destinatarios), $kensu, $page, $maxrows, $query (list_page, list_kensu, master_cd, type), $activeAc
--}}
@php
	$pageTitle = 'プッシュ集計 - 詳細';
	$from = ($page - 1) * $kensu;
	$detailUrl = route('admin.promocion.push.history.show');
@endphp
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', $pageTitle . '一覧 - ' . $siteName)

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$("{{ $activeAc }}").accordion('option', 'active', 0);
	window.scroll(0,0);
});

//表示件数の制御 (el legacy llamaba a kensuSet sin definirla)
function kensuSet(parts){
	document.form_kensu.kensu.value = parts.value;
	document.form_kensu.submit();
}
</script>
@endsection

@section('content')
<form method="get" name="form_kensu" id="form_kensu" class="displayNone" action="{{ $detailUrl }}">
@foreach ($query as $name => $value)
<input type="hidden" name="{{ $name }}" value="{{ $value }}" />
@endforeach
<input type="hidden" name="kensu" value="{{ $kensu }}" />
<input type="hidden" name="page" value="{{ $page }}" />
</form>

<div class="contents">

<p class="h2Title">■{{ $pageTitle }}</p>

<div>
	<form action="{{ route('admin.promocion.push.history') }}" method="GET" style="margin-bottom: 20px;">
		<input type="hidden" name="page" value="{{ $query['list_page'] ?? '' }}">
		<input type="hidden" name="kensu" value="{{ $query['list_kensu'] ?? '' }}">
		<input type="submit" value="一覧に戻る">
	</form>
	<form action="{{ $detailUrl }}" method="GET" style="margin-bottom: 20px;float: right; top: -45px; position: relative;">
		<input type="hidden" name="csv_print" value="1">
		<input type="hidden" name="list_page" value="{{ $query['list_page'] ?? '' }}">
		<input type="hidden" name="list_kensu" value="{{ $query['list_kensu'] ?? '' }}">
		<input type="hidden" name="master_cd" value="{{ $query['master_cd'] ?? '' }}">
		<input type="hidden" name="type" value="{{ $query['type'] ?? '' }}">
		<input type="submit" value="CSV出力">
	</form>
</div>

@if ($maxrows == 0)

	<p>該当のデータはありません。</p>

@else

	<div id="form">
		<p class="maxListLength">表示件数<select name="kensu_act" id="kensu_act" class="selectBox" onchange="kensuSet(this)">@include('admin.promocion.push._kensu')</select></p>
	</div>

	<p><span class="attention">{{ $from + 1 }}件～{{ min($from + $kensu, $maxrows) }}件目/{{ $maxrows }}件中</span></p>

	<table class="tableBox2">
		<tr>
			<th>顧客名</th>
			<th>電話番号</th>
			<th>登録店舗</th>
			<th>年齢</th>
			<th>性別</th>
			<th>職業</th>
			<th>会員登録日</th>
		</tr>
		@foreach ($rows as $row)
			<tr>
				<td>{{ $row->name }}</td>
				<td>{{ $row->tel }}</td>
				<td>{{ $row->shop_name }}</td>
				<td>{{ $row->age }}</td>
				<td>{{ $row->kojin_sex }}</td>
				<td>{{ $row->job }}</td>
				<td>{{ $row->touroku_ymd }}</td>
			</tr>
		@endforeach
	</table>

	<ul class="btnBox2">
		@include('admin.promocion.push._pager', ['url' => $detailUrl, 'params' => $query])
	</ul>

@endif

<!-- / .contents --></div>
@endsection
