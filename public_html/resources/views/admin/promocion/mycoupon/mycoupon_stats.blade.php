{{--
	クーポン利用数確認 — legacy: system/category3/mycoupon/mycoupon_stats.php
	Variables: $rows (paginador), $mode (título del periodo), $shops, $shopCd, $isMaster, $filters, $kubunLabels
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', 'クーポン ' . $mode)

@php
	$k = $filters['kikan_option'];
	$query = array_merge($filters, ['page' => $rows->currentPage()]);
@endphp

@section('css')
<link rel="stylesheet" type="text/css" href="{{ asset('legacy/system/plugin/ui-datepicker/ui.datepicker.css') }}" />
@endsection

@section('js')
<script type="text/javascript" src="{{ asset('legacy/system/plugin/ui-datepicker/ui.datepicker-ja.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/plugin/ui-datepicker/gcalendar-holidays.js') }}"></script>
<script type="text/javascript">
$(document).ready(function() {
	$(".ac7").accordion('option', 'active', 0);
	$("#search_start, #search_end").datepicker();
});
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■クーポン 抽出</p>
<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.promocion.coupons.stats') }}" autocomplete="off">
@csrf
使用店舗：
@if ($isMaster)
<select name="shop_cd" @if ($shops->count() < 2) class="displayNone" @endif>
<option value="-1">全店舗</option>
@foreach ($shops as $s)
<option value="{{ $s->cd }}" @selected($shopCd === (string) $s->cd)>{{ $s->kaishamei }}</option>
@endforeach
</select>
@else
{{ $shops->first()?->kaishamei }}&nbsp;
<input type="hidden" name="shop_cd" value="{{ $shops->first()?->cd }}">
@endif
	<p>
		<input type="radio" name="kikan_option" value="1" @checked($k === '' || $k === '1') />&nbsp;本日分<br/>
		<input type="radio" name="kikan_option" value="2" @checked($k === '2') />&nbsp;昨日分<br/>
		<input type="radio" name="kikan_option" value="3" @checked($k === '3') />&nbsp;過去一週間分<br/>
		<input type="radio" name="kikan_option" value="4" @checked($k === '4') />&nbsp;過去一ヶ月分<br/>
		<input type="radio" name="kikan_option" value="5" @checked($k === '5') />&nbsp;今月分<br/>
		<input type="radio" name="kikan_option" value="6" @checked($k === '6') />&nbsp;期間指定&emsp;
		<input type="text" name="search_start" id="search_start" value="{{ $filters['search_start'] }}" maxlength="10" style="ime-mode:disabled;" />&nbsp;～&nbsp;
		<input type="text" name="search_end" id="search_end" value="{{ $filters['search_end'] }}" maxlength="10" style="ime-mode:disabled;" />
	</p>
<input type="submit" name="submit" class="btnStyle" value="表示" />
</form>
<br/>

<p class="h2Title">■{{ $mode }}</p>
@if ($rows->total() > 0)
<p><span class="attention">{{ $rows->total() }}件</span>の該当結果がありました。</p>
<table class="tableBox2">
  <tr>
    <th>ID</th>
    <th>使用区分</th>
    <th>タイトル</th>
    <th>使用数</th>
    <th style="width: 80px;">利用者照会</th>
    <th style="width: 80px;">利用統計</th>
  </tr>
@foreach ($rows as $i => $row)
  <tr @if ($i % 2 != 0) class="tableBg" @endif>
    <td>{{ $row->cd }}</td>
    <td>{{ $kubunLabels[(int) $row->coupon_kubun] ?? '' }}</td>
    <td>{{ $row->title }}</td>
    <td align="center">{{ $row->cnt }}</td>
    <td align="center"><a href="{{ route('admin.promocion.coupons.stats.show', array_merge($query, ['cd' => $row->cd])) }}" class="btnStyle" >照会</a></td>
    <td align="center">
	<ul>
	<li><a href="{{ route('admin.promocion.coupons.show', array_merge($query, ['cd' => $row->cd])) }}" class="btnStyle">詳細</a></li>
	</ul>
    </td>
  </tr>
@endforeach
</table>
<ul class="btnBox2">
@if ($rows->lastPage() > 1)
@for ($l = max(1, $rows->currentPage() - 5); $l <= min($rows->lastPage(), $rows->currentPage() + 5); $l++)
@if ($l === $rows->currentPage())
<li class="now"><b>{{ $l }}</b></li>
@else
<li><a href="{{ route('admin.promocion.coupons.stats', array_merge($filters, ['page' => $l])) }}">{{ $l }}</a></li>
@endif
@endfor
@endif
</ul>
@else
該当データがありません。
@endif
</div>
@endsection
