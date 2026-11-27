{{--
	個別来店履歴 — legacy: system/category3/visit/store_visit_list.php
	Variables: $kikan, $periods, $data (VisitStatsService::shopTable() o null), $visitors, $selectedDay, $submitted
	El formulario CSVファイル出力 estaba comentado en el legacy (storecsv_download.php solo escribía 今日): se activa.
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', '個別来店履歴 - ' . $siteName)

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac4").accordion('option', 'active', 0);
});
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■個別来店履歴抽出</p>

<form method="post" name="form_search" id="form_search" class="form" action="{{ route('admin.promocion.visit.store-list') }}">

<select name="kikan">
@foreach ($periods as $i => $label)
<option value="{{ $i }}"@selected($submitted && $kikan === $i)>{{ $label }}</option>
@endforeach
</select>
<input type="submit" name="submit" value="表示" />
</form>

<!-- / .contents --></div>


<div class="contents">

@if ($data !== null && $visitors->isNotEmpty())

<p class="h2Title">■来客数一覧</p>

<div>
<div class="tableFrame" style="float: right; width: 85%;">

	<table class="tableBox2" style="text-align:center;table-layout:fixed;">
	  <tr style="height: 32px;">
@foreach ($data['columns'] as $column)
		    <th style="width:3em;">{{ $column[0] }}</th>
@endforeach
		    <th style="width:3em;">小計</th>
		  </tr>

@foreach ($data['rows'] as $n => $row)
		  <tr style="background:{{ $n % 2 === 0 ? '#E0E9EF' : '#FFFFFF' }};height: 32px;">
@foreach ($row['counts'] as $i => $count)
@if ($kikan === 0)
				<td style="border-right:1px solid #87AFAE;">{{ $count }}</td>
@else
				<td style="border-right:1px solid #87AFAE;">@if ($count > 0)<a href="{{ route('admin.promocion.visit.day', ['shop_cd' => $row['cd'], 'kikan' => $kikan - 1, 'date' => $data['columns'][$i][1]]) }}">{{ $count }}</a>@else{{ $count }}@endif</td>
@endif
@endforeach
			    <td style="font-weight:bold;border-right:1px solid #87AFAE;">{{ $row['total'] }}</td>
			  </tr>
@endforeach
		  <tr>
@foreach ($data['totals'] as $sum)
		    <th>{{ $sum }}</th>
@endforeach
		    <th>{{ $data['total'] }}</th>
	  </tr>
	</table>
</div>
<div style="width: 15%; float: left;">
	<table class="tableBox2" style="text-align:center;table-layout:fixed;">
	  <tr style="height: 32px;">
	    <th>&nbsp;</th>
	  </tr>
@foreach ($data['rows'] as $n => $row)
	  <tr style="background:{{ $n % 2 === 0 ? '#E0E9EF' : '#FFFFFF' }};height: 32px;">
	    <td style="font-weight:bold;border-right:1px solid #87AFAE;">{{ $row['name'] }}</td>
	  </tr>
@endforeach
	  <tr>
		<th>合計</th>
	  </tr>
	</table>
</div>
</div>

<p style="clear:both;"></p>
<br/>
<form method="post" action="{{ route('admin.promocion.visit.store-csv') }}">
<input type="hidden" name="kikan" value="{{ $kikan }}" />
<input type="hidden" name="date" value="{{ $selectedDay }}" />
<input type="hidden" name="act" value="csv_download" />
<input type="submit" name="enter" id="enter" class="btn" value="CSVファイル出力" />
</form>
<br/>
<p class="h2Title">■来客一覧</p>

<table class="tableBox2" style="text-align:center;">
  <tr>
    <th></th>
    <th></th>
    <th>顧客ID</th>
    <th>氏名</th>
    <th>来店回数</th>
  </tr>
@foreach ($visitors as $n => $row)
  <tr @if ($n % 2 != 0) class="tableBg" @endif>
    <td><input type="checkbox" name="chk_kaiin[{{ (int) $row->user_cd }}]"/></td>
    <td>{{ $n + 1 }}</td>
    <td><a href="/system/category1/customer/customer_detail.php?kaiin_cd={{ (int) $row->user_cd }}&amp;shop_cd=&amp;kikan={{ $kikan }}&amp;from_visit=visit2">{{ $row->user_id }}</a></td>
    <td>{{ $row->name }}</td>
    <td>{{ $row->count }}</td>
  </tr>
@endforeach
</table>

@elseif ($submitted)
現在データはありません。
@endif


<ul class="btnBox2">
</ul>


<!-- / .contents --></div>
@endsection
