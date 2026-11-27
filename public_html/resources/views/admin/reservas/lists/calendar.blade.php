{{--
    Legacy: system/category9/yoyaku/yoyaku_list_calendar.php (予約・来店一覧: reservas simples de tb_reserve por día).
    Variables:
      $month      Carbon     día 1 del mes mostrado (Asia/Tokyo).
      $prevMonth, $nextMonth Carbon día 1 del mes anterior / siguiente (botones «年月へ»).
      $shops      Collection tiendas visibles (cd, kaishamei): todas las no borradas (master) o la de la sesión.
      $weeks      array      semanas de 7 celdas; null = fuera del mes; si no:
                             ['day' => int, 'dayClass' => 'nowday'|'holiday'|'normalday', 'closed' => bool (休日 en tb_shop_work),
                              'count' => int (tb_reserve del día), 'ymd' => 'Ymd', 'dd' => 'Y/n/j'].
      $siteName   string     nombre del sitio (<title>).
    「表　示」 lleva al 予約表 de ese día (el legacy enviaba a yoyaku_list_time_table.php, descartado).
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', '予約一覧 - ' . $siteName)

@section('js')
{{-- inc/ac.php --}}
<script type="text/javascript">
$(document).ready(function() {
	$(".ac5").accordion({ collapsible: true , active: false});
	$(".ac5").accordion('option', 'active', 0);

});
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■予約・来店一覧</p>



<div class="btnBox">

<form action="{{ route('admin.reservas.list-calendar') }}" method="post" style="float: left;">
@csrf
<input type="submit" value="&lt;&lt;&nbsp;{{ $prevMonth->year }}年{{ $prevMonth->month }}月へ">
<input type="hidden" name="year" id="year" value="{{ $prevMonth->year }}">
<input type="hidden" name="mon" id="mon" value="{{ $prevMonth->month }}">
</form>

<form action="{{ route('admin.reservas.list-calendar') }}" method="post" style="float: right;">
@csrf
<input type="submit" value="{{ $nextMonth->year }}年{{ $nextMonth->month }}月へ&nbsp;&gt;&gt;">
<input type="hidden" name="year" id="year" value="{{ $nextMonth->year }}">
<input type="hidden" name="mon" id="mon" value="{{ $nextMonth->month }}">
</form>


<span class="text3L bold">{{ $month->year }}年{{ $month->month }}月</span>



</div>



<table class="calenderTable">
<tr>
<th><span class="attention">日</span></th>
<th>月</th>
<th>火</th>
<th>水</th>
<th>木</th>
<th>金</th>
<th>土</th>
</tr>

@foreach ($weeks as $week)
<tr>
@foreach ($week as $cell)
@if ($cell === null)
<td>&nbsp;</td>
@else
<td{!! $cell['closed'] ? ' class="holidaySet"' : '' !!}><span class="{{ $cell['dayClass'] }}">{{ $cell['day'] }}</span><form action="{{ route('admin.reservas.timetable') }}" method="get">
@if ($shops->count() > 1)
@if ($cell['count'] > 0)
<span style="background: #FFFFCF;">全<font style="font-weight: bold; color: #EE0000">{{ $cell['count'] }}</font>件</span>
@else
<span style="background: transparent;">&nbsp;</span>
@endif
@else
{{ '(' . $cell['count'] . '件)' }}<input type="hidden" name="shop" value="{{ $shops->first()?->cd }}" />
@endif
<input type="hidden" name="d" value="{{ $cell['ymd'] }}" ><input type="hidden" name="dd" id="{{ $cell['day'] }}" class="inputDay" value="{{ $cell['dd'] }}" ><input type="submit" id="{{ $cell['day'] }}" class="inputDay" value="表　示"></form></td>
@endif
@endforeach
</tr>
@endforeach
</table>



<div class="btnBox">

<form action="{{ route('admin.reservas.list-calendar') }}" method="post" style="float: left;">
@csrf
<input type="submit" value="&lt;&lt;&nbsp;{{ $prevMonth->year }}年{{ $prevMonth->month }}月へ">
<input type="hidden" name="year" id="year" value="{{ $prevMonth->year }}">
<input type="hidden" name="mon" id="mon" value="{{ $prevMonth->month }}">
</form>


<form action="{{ route('admin.reservas.list-calendar') }}" method="post" style="float: right;">
@csrf
<input type="submit" value="{{ $nextMonth->year }}年{{ $nextMonth->month }}月へ&nbsp;&gt;&gt;">
<input type="hidden" name="year" id="year" value="{{ $nextMonth->year }}">
<input type="hidden" name="mon" id="mon" value="{{ $nextMonth->month }}">
</form>

</div>



<!-- / .contents --></div>
@endsection
