{{--
    Legacy: system/category9/yoyaku/view_shop.php (予約一覧: 店舗毎予約数, sin cabecera ni menú lateral).
    Variables:
      $d          ?string `d` de la petición (Ymd), para la navegación.
      $month      Carbon  día 1 del mes mostrado (Asia/Tokyo).
      $prevMonth, $nextMonth string Ymd del último día del mes anterior y del día 1 del siguiente.
      $days       array   [['day' => 1, 'youbi' => '月', 'color' => '#000000'], …] un elemento por día del mes.
      $shops      Collection cd => kaishamei de las tiendas con disp_flg = '1' (orden cd).
      $counts     array   [shop_cd]['Y/m/d'] => reservas activas.
      $versionFlg string  '1' | '2' (time_table.css solo en '1').
      $siteName   string  nombre del sitio (<title>).
      $isIos      bool    iPhone/iPad/iPod (CSS de iOS).
      $isTablet, $staffWord, $yoyakuSetsubiFlg, $freeYoyakuFlg: los de partials/yoyaku_navi.
--}}
@extends('layouts.legacy-popup')

@section('title', '予約一覧 - ' . $siteName)

@section('css')
@if ($versionFlg == '1')
<link href="{{ asset('legacy/system/category9/yoyaku/css/time_table.css') }}" rel="stylesheet" type="text/css" media="all" />
@endif
<link rel="stylesheet" type="text/css" href="{{ asset('legacy/system/css/popInline.css') }}">

<style type="text/css"><!--

.contents {
	overflow-x: scroll;
}

.main1 {
	background: none;
}

.yoyakuField {
	padding-bottom: 20px;
}


.yoyakuShopTable {
	width: {{ count($days) * 40 + 360 }}px;
	margin: 0;
}

.yoyakuShopTable tr {
	background: #EDF6FB;
}

.yoyakuShopTable tr.bg {
	background: #E0EAF1;
}

.yoyakuShopTable th,
.yoyakuShopTable td {
	background: transparent;
}

@if ($isIos)
.wrap { overflow:visible!important; background: none; }
#popWinWrap { position: relative !important; }
@endif

--></style>
@endsection

@section('js')
<script type="text/javascript" src="{{ asset('legacy/system/js/popInline.js') }}"></script>

<script type="text/javascript">
$(document).ready(function() {
	//ポップアップ用
	popIni();

});
</script>
@endsection

@section('content')
@include('admin.reservas.partials.yoyaku_navi')


<div class="yoyakuDayControlArea">
<a class="tsukiNaviBtn" href="{{ route('admin.reservas.by-shop', ['d' => $prevMonth]) }}">&lt;&lt;前月</a>
<span class="hyoujiDate">{{ $month->format('Y') }}年{{ $month->format('m') }}月</span>
<a class="tsukiNaviBtn" href="{{ route('admin.reservas.by-shop', ['d' => $nextMonth]) }}">次月&gt;&gt;</a>
</div>


<div class="contents">


<div class="yoyakuField">

<table class="yoyakuShopTable TLF">
<thead>
<tr>
<th class="viewShopTh1"></th>
@foreach ($days as $day)
<th align="center" style="padding: 0 5px;"><span style="color: {{ $day['color'] }}">{{ $day['day'] }}({{ $day['youbi'] }})</span></th>
@endforeach
</tr>
</thead>
<tbody>
@foreach ($shops as $cd => $kaishamei)
<tr class="{{ $loop->index % 2 == 1 ? 'bg' : '' }}">
<th class="viewShopTh2" style="">{{ $kaishamei }}</th>
@foreach ($days as $day)
@php
    $dayKey = $month->format('Y/m/') . sprintf('%02d', $day['day']);
    $dispKazu = (int) ($counts[$cd][$dayKey] ?? 0);
@endphp
<td align="center"><a href="{{ route('admin.reservas.timetable', ['d' => $month->format('Ym') . sprintf('%02d', $day['day']), 'shop' => $cd]) }}" style="{{ $dispKazu > 0 ? 'font-size:16px;font-weight: bold; color: #EE0000;' : '' }}">{{ $dispKazu }}</a></td>
@endforeach
</tr>
@endforeach
</tbody>
</table>

<ul class="btnBox2">
</ul>

<!-- / .yoyakuField --></div>
<!-- / .contents --></div>
@endsection
