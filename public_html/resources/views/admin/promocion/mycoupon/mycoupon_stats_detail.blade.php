{{--
	利用者照会 — legacy: system/category3/mycoupon/mycoupon_stats_detail.php
	Variables: $rows (paginador: cd, id, name, last_used, cnt), $times [cd => usos], $mode
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', 'クーポン ' . $mode . ' - ' . $siteName)

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac7").accordion('option', 'active', 0);
});
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■{{ $mode }}</p>
@if ($rows->total() > 0)
<table class="tableBox2">
	<tr>
		<th>会員番号</th>
		<th>名前</th>
		<th>使用数</th>
		<th>使用時間</th>
		<th style="width: 80px;">会員詳細</th>
	</tr>
@foreach ($rows as $i => $row)
@php
	$detail = Route::has('admin.clientes.show')
		? route('admin.clientes.show', ['customer' => $row->cd, 'wintype' => 'no_headers'])
		: '/system/category1/inc/customer_detail.php?kaiin_cd=' . $row->cd . '&wintype=no_headers';
@endphp
	<tr @if ($i % 2 != 0) class="tableBg" @endif>
		<td>{{ $row->id }}</td>
		<td>{{ $row->name }}</td>
		<td>{{ $row->cnt }}</td>
		<td>@if ((int) $row->cnt === 1){{ date('Y/m/d H:i', (int) $row->last_used) }}@else @foreach ($times[$row->cd] ?? [] as $t){{ date('Y/m/d H:i', (int) $t) }}<br/>@endforeach @endif</td>
		<td>
			<input type="button" onclick="javascript:window.open('{{ $detail }}', 'mywindow1', 'width=970, height=550, menubar=no, toolbar=no, scrollbars=yes');" value="詳細" class="btn btnStyle">
		</td>
	</tr>
@endforeach
</table>
@else
該当データがありません。
@endif
</div>
@endsection
