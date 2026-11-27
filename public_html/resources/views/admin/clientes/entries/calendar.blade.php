{{-- Legacy: system/category1/entrance/working_calendar.php (入退室一覧: calendario). Las flechas de mes son
     formularios GET (el legacy hacía POST a la misma página) y cada día envía dd = Y/n/j a admin.clientes.entries.index.
     Variables: $siteName, $month, $previous, $next (LegacyMonth),
       $days [día => ['open' => bool (no es 休日 en tb_shop_work), 'class' => nowday|holiday|normalday]],
       $firstWeekday (0 = domingo) --}}
@extends('layouts.legacy')

@section('menu', 'clientes')
@section('title', '営業日設定 - ' . $siteName)

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
            ? '&lt;&lt;&nbsp;' . $to->start->year . '年' . $to->start->month . '月へ'
            : $to->start->year . '年' . $to->start->month . '月へ&nbsp;&gt;&gt;';

        return '<form action="' . e(route('admin.clientes.entries.calendar')) . '" method="get" style="float: ' . $float . ';">' . "\n"
            . '<input type="submit" value="' . $label . '">' . "\n"
            . '<input type="hidden" name="year" id="year" value="' . $to->start->year . '">' . "\n"
            . '<input type="hidden" name="mon" id="mon" value="' . $to->start->month . '">' . "\n"
            . '</form>';
    };
    $daysInMonth = count($days);
    $weeks = (int) ceil(($daysInMonth + $firstWeekday) / 7);
    $openCount = count(array_filter($days, fn ($d) => $d['open']));
    $year = $month->start->year;
    $mon = $month->start->month;
@endphp

@section('content')
<div class="contents">
<p class="h2Title">■入退室一覧</p>



<div class="btnBox">

{!! $nav($previous, 'left', true) !!}


<span class="text3L bold">{{ $year }}年{{ $mon }}月</span>

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
<td{!! $days[$day]['open'] ? '' : ' class="holidaySet"' !!}><span class="{{ $days[$day]['class'] }}">{{ $day }}</span><form action="{{ route('admin.clientes.entries.index') }}" method="get"><input type="hidden" name="dd" id="{{ $day }}" class="inputDay" value="{{ $year }}/{{ $mon }}/{{ $day }}" ><input type="submit" id="{{ $day }}" class="inputDay" value="表　示"></form></td>
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
