{{-- Legacy: system/category5/config/shop_work_calendar.php (営業日設定).
     Las flechas de mes son formularios GET (el legacy hacía POST a la misma página).
     Variables: $month, $previous, $next (LegacyMonth), $days [día => ['open' => bool, 'class' => nowday|holiday|normalday]],
       $firstWeekday (0 = domingo) --}}
@extends('layouts.legacy')

@section('menu', 'operaciones')

@section('title', '営業日設定 - '.($siteName ?? ''))

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac4").accordion('option', 'active', 0);
});
</script>
@endsection

@php
    $nav = function (\App\Services\Operaciones\LegacyMonth $to, string $float, bool $back) {
        $label = $back
            ? '&lt;&lt;&nbsp;'.$to->start->year.'年'.$to->start->month.'月へ'
            : $to->start->year.'年'.$to->start->month.'月へ&nbsp;&gt;&gt;';

        return '<form action="'.e(route('admin.operaciones.shop-work.edit')).'" method="get" style="float: '.$float.'">'."\n"
            .'<input type="submit" value="'.$label.'">'."\n"
            .'<input type="hidden" name="year" id="year" value="'.$to->start->year.'">'."\n"
            .'<input type="hidden" name="mon" id="mon" value="'.$to->start->month.'">'."\n"
            .'</form>';
    };
    $daysInMonth = count($days);
    $weeks = (int) ceil(($daysInMonth + $firstWeekday) / 7);
    $openCount = count(array_filter($days, fn ($d) => $d['open']));
@endphp

@section('content')
<div class="contents">
<p class="h2Title">■営業日設定</p>

<div class="btnBox">
{!! $nav($previous, 'left', true) !!}
{!! $nav($next, 'right', false) !!}
<span class="text3L bold">{{ $month->start->year }}年{{ $month->start->month }}月</span><br />営業<span class="attention">休日</span>を<br />チェックしてください<br />
</div>

<form action="{{ route('admin.operaciones.shop-work.update') }}" method="post">
@csrf
@method('PUT')
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
@for ($i = 0; $i < $weeks; $i++)
<tr>
@for ($j = 0; $j < 7; $j++)
@php($day = 7 * $i + $j - $firstWeekday + 1)
@if (($i === 0 && $j < $firstWeekday) || $day > $daysInMonth)
<td>&nbsp;</td>
@else
<td{!! $days[$day]['open'] ? '' : ' class="holidaySet"' !!}><span class="{{ $days[$day]['class'] }}"><label for="{{ $day }}">{{ $day }}</label></span><label for="{{ $day }}"><input type="checkbox" name="{{ $day }}" id="{{ $day }}" class="inputDay" value="0" @checked(! $days[$day]['open'])></label></td>
@endif
@endfor
</tr>
@endfor
</table>

<div class="btnBox">
<input type="hidden" name="year" id="year" value="{{ $month->start->year }}">
<input type="hidden" name="mon" id="mon" value="{{ $month->start->month }}">
<input type="submit" value="更新">
</div>
</form>

<div class="btnBox">
{!! $nav($previous, 'left', true) !!}
{!! $nav($next, 'right', false) !!}
営業日数：{{ $openCount }}／{{ $daysInMonth }}
</div>

<!-- / .contents --></div>
@endsection
