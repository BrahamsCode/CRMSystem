{{--
	来店履歴一覧 (日別) — legacy: system/category3/visit/day_visit_list.php
	Variables (VisitStatsService::day() + controlador): $shops, $isMaster, $periods, $requestShopCd, $submitted,
	$kikan, $shopCd, $selectedDay, $kensu, $page, $mmm, $ddd, $hhh, $table, $list, $hasVisits
	Añadido: botón CSVファイル出力 (daycsv_download.php existía vacío en el legacy).
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', '来店履歴一覧 - ' . $siteName)

@section('css')
<link href="{{ asset('legacy/system/css/import.css') }}" rel="stylesheet" type="text/css" media="all" />

<style type="text/css"><!--
.graLink {
	text-decoration: none!important;
}

.graBar {
	display: block;
	width: 10px;
	margin: 0 auto;
	border: 1px solid #2F8EFF;
	background: #5FA8FF;
}

.graLink:hover .graBar {
	border: 1px solid #336699;
	background: #3B67EF;
}

.graKensu {
	display: block;
	text-align: center;
}
--></style>
@endsection

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac4").accordion('option', 'active', 0);
});
</script>
@endsection

@section('content')
@php
	$self = route('admin.promocion.visit.day');
	// Parámetros que conservan los enlaces (el legacy perdía shop_cd y la fecha al paginar)
	$base = ['shop_cd' => $shopCd, 'kikan' => $kikan, 'date' => $selectedDay ?? null];
	$addstr = '&mmm='.rawurlencode((string) $mmm).'&ddd='.rawurlencode((string) $ddd).'&hhh='.rawurlencode((string) $hhh)
		.'&shop_cd='.rawurlencode((string) $shopCd).'&kikan='.rawurlencode((string) $kikan);
	$pageUrl = fn ($p) => $self.'?'.http_build_query(['page' => $p, 'kensu' => $kensu, 'mmm' => $mmm, 'ddd' => $ddd, 'hhh' => $hhh] + $base);
@endphp
<div class="contents">
<p class="h2Title">■来店履歴抽出</p>

<form method="post" name="form_search" id="form_search" class="form" action="{{ $self }}">

@if ($isMaster)
<select name="shop_cd">
@foreach ($shops as $shop)
<option value="{{ $shop->cd }}"@selected((string) $shop->cd === $requestShopCd)>{{ $shop->kaishamei }}</option>
@endforeach
</select>
@else
{{ $shops->first()->kaishamei }}
<input type="hidden" name="shop_cd" value="{{ $shops->first()->cd }}">
@endif

<select name="kikan">
@foreach ($periods as $i => $label)
<option value="{{ $i }}"@selected($requestShopCd !== '' && $kikan === $i)>{{ $label }}</option>
@endforeach
</select>
<input type="submit" name="submit" value="表示" />
</form>

<!-- / .contents --></div>


<div class="contents">

@if ($hasVisits)

<p class="h2Title">■来客数一覧</p>

<div>


<table class="tableBox4" style="text-align:center;">
	  <tr style="height: 32px;">
			<th>&nbsp;</th>
@if ($kikan !== 2) {{-- 時間別、日別 --}}
@for ($i = 5; $i < 29; $i++)
		    <th style="width: 2em;">{{ $i < 24 ? $i : $i - 24 }}</th>
@endfor
		    <th>{{ $kikan === 1 ? '小計' : '合計' }}</th>
	  </tr>

@foreach ($table['days'] as $n => $row)
	  <tr style="background:{{ $n % 2 === 1 ? '#E0E9EF' : '#FFFFFF' }};height: 32px;">
	    <td>{{ $row['date'] }}</td>
@foreach ($row['counts'] as $i => $count)
@php($hour = ($i + 5) % 24)
				<td  valign="bottom">
				@if ($count > 0)<a href="{{ $self }}?{{ http_build_query($base + ['ddd' => $row['day'], 'hhh' => $hour, 'page' => $page]) }}" class="graLink"><span class="graBar" style="height: {{ $count * 2 }}px;">&nbsp;</span></a>@endif
				<small class="graKensu">{{ $count }}</small>
				</td>
@endforeach
			    <td style="font-weight:bold;border-right:1px solid #87AFAE;">{{ $row['total'] }}</td>
	  </tr>
@endforeach

@if ($kikan === 1) {{-- 時間別 --}}
	  <tr>
	    <td>合計</td>
@foreach ($table['hourTotals'] as $sum)
			    <th>{{ $sum }}</th>
@endforeach
			    <th>{{ $table['total'] }}</th>
	  </tr>
@endif

@else {{-- 年間 --}}
@for ($i = 1; $i <= 31; $i++)
		    <th>{{ $i }}日</th>
@endfor
		    <th>小計</th>
		  </tr>

@foreach ($table['months'] as $row)
		  <tr style="background:{{ $row['month'] % 2 === 0 ? '#E0E9EF' : '#FFFFFF' }};height: 32px;">
		    <td>{{ $row['month'] }}月</td>
@foreach ($row['counts'] as $i => $count)
				<td  valign="bottom">
				@if ($count > 0)<a href="{{ $self }}?{{ http_build_query($base + ['mmm' => $row['month'], 'ddd' => $i, 'hhh' => 5, 'page' => $page]) }}" class="graLink"><span class="graBar" style="height: {{ $count * 2 }}px;">&nbsp;</span></a>@endif
				<small class="graKensu">{{ $count }}</small>
				</td>
@endforeach
			    <td style="font-weight:bold;border-right:1px solid #87AFAE;">{{ $row['total'] }}</td>
			  </tr>
@endforeach
		  <tr>
		    <td>合計</td>
@foreach ($table['dayTotals'] as $sum)
		    <th>{{ $sum }}</th>
@endforeach
		    <th>{{ $table['total'] }}</th>
		  </tr>

@endif
</table>

</div>

<br/>
<form method="post" action="{{ route('admin.promocion.visit.day-csv') }}">
<input type="hidden" name="shop_cd" value="{{ $shopCd }}" />
<input type="hidden" name="kikan" value="{{ $kikan }}" />
<input type="hidden" name="date" value="{{ $selectedDay }}" />
<input type="hidden" name="act" value="csv_download" />
<input type="submit" name="enter" id="enter" class="btn" value="CSVファイル出力" />
</form>

<p class="h2Title clearBoth">■来客一覧</p>

<table class="tableBox2" style="text-align:center;">
  <tr>
    <th>顧客ID</th>
    <th>氏名</th>
    <th>来店回数</th>
  </tr>
@foreach ($list['rows'] as $n => $row)
  <tr @if ($n % 2 != 0) class="tableBg" @endif>
    <td><a href="/system/category1/customer/customer_detail.php?kaiin_cd={{ (int) $row->user_cd }}&amp;from_visit=visit1&amp;page={{ $page }}{{ $addstr }}">{{ $row->user_id }}</a></td>
    <td>{{ $row->name }}</td>
    <td>{{ $row->count }}</td>
  </tr>
@endforeach
</table>


<form method="post" name="form1" id="form1" class="form" action="">

</form>

@elseif ($submitted)
現在データはありません。
@endif



<ul class="btnBox2">
@if ($list !== null && $list['maxPage'] > 0)
@if ($page != 1 && $kensu < $list['count'])
<li class="btn"><a href="{{ $pageUrl($page - 1) }}"><img src="{{ asset('legacy/system/img/btn_back_off.gif') }}" width="92" height="32" alt="戻る" /></a></li>
@endif
@if ($list['maxPage'] > 1)
@for ($l = max(1, $page - 5); $l <= min($list['maxPage'], $page + 5); $l++)
@if ($l == $page)
<li class="now"><b>{{ $l }}</b></li>
@else
<li><a href="{{ $pageUrl($l) }}">{{ $l }}</a></li>
@endif
@endfor
@endif
@if ($page < $list['maxPage'])
<li class="btn"><a href="{{ $pageUrl($page + 1) }}"><img src="{{ asset('legacy/system/img/btn_next_off.gif') }}" width="92" height="32" alt="次へ" /></a></li>
@endif
@endif
</ul>



<!-- / .contents --></div>
@endsection
