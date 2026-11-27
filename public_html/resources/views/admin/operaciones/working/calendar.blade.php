{{-- Legacy: system/category5/working/working_calendar.php (勤怠一覧, calendario).
     Cambios: las flechas de mes y el 表示 de cada día son formularios GET (el legacy hacía POST) y apuntan a las rutas nuevas.
     Variables: $month, $previous, $next (LegacyMonth), $days [día => ['open' => bool, 'class' => nowday|holiday|normalday]],
       $firstWeekday (0 = domingo), $shops (tiendas que puede ver) --}}
@extends('layouts.legacy')

@section('menu', 'operaciones')

@section('title', '勤怠一覧 - '.($siteName ?? ''))

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac2").accordion('option', 'active', 0);
});
</script>
@endsection

@php
    $nav = function (\App\Services\Operaciones\LegacyMonth $to, string $float, bool $back) {
        $label = $back
            ? '&lt;&lt;&nbsp;'.$to->start->year.'年'.$to->start->month.'月へ'
            : $to->start->year.'年'.$to->start->month.'月へ&nbsp;&gt;&gt;';

        return '<form action="'.e(route('admin.operaciones.working.calendar')).'" method="get" style="float: '.$float.';">'."\n"
            .'<input type="submit" value="'.$label.'">'."\n"
            .'<input type="hidden" name="year" value="'.$to->start->year.'">'."\n"
            .'<input type="hidden" name="mon" value="'.$to->start->month.'">'."\n"
            .'</form>';
    };
    $daysInMonth = count($days);
    $weeks = (int) ceil(($daysInMonth + $firstWeekday) / 7);
    $openCount = count(array_filter($days, fn ($d) => $d['open']));
@endphp

@section('content')
<div class="contents">
<p class="h2Title">■勤怠一覧</p>

<div class="btnBox">
{!! $nav($previous, 'left', true) !!}

<span class="text3L bold">{{ $month->start->year }}年{{ $month->start->month }}月</span>

{!! $nav($next, 'right', false) !!}
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
@for ($i = 0; $i < $weeks; $i++)
<tr>
@for ($j = 0; $j < 7; $j++)
@php($day = 7 * $i + $j - $firstWeekday + 1)
@if (($i === 0 && $j < $firstWeekday) || $day > $daysInMonth)
<td>&nbsp;</td>
@else
<td{!! $days[$day]['open'] ? '' : ' class="holidaySet"' !!}><span class="{{ $days[$day]['class'] }}">{{ $day }}</span><form action="{{ route('admin.operaciones.working.day') }}" method="get">@if ($shops->count() > 1)<select name="shop_cd" style="width: 100%;">@foreach ($shops as $shop)<option value="{{ $shop->cd }}">{{ $shop->kaishamei }}</option>@endforeach</select>@else<input type="hidden" name="shop_cd" value="{{ $shops->first()?->cd }}" />@endif<br /><input type="hidden" name="dd" class="inputDay" value="{{ $month->start->year }}/{{ $month->start->month }}/{{ $day }}" ><input type="submit" class="inputDay" value="表　示"></form></td>
@endif
@endfor
</tr>
@endfor
</table>

<div class="btnBox">
{!! $nav($previous, 'left', true) !!}

営業日数：{{ $openCount }}／{{ $daysInMonth }}

{!! $nav($next, 'right', false) !!}
</div>

<!-- / .contents --></div>
@endsection
