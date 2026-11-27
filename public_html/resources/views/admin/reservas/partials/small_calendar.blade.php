{{-- Sin salto de línea al final del archivo: las tablas .smallCal son inline-block y el legacy las pinta sin espacios entre ellas (si no, la 3.ª baja de línea).
    Legacy: dispCalendar() de system/category9/yoyaku/time_table.php (calendario mensual pequeño, .smallCal).
    Variables:
      $month     Carbon  día 1 del mes a pintar.
      $shopCd    int     tienda para los enlaces.
      $d         ?string `d` de la petición (Ymd): marca .selectday solo si viene en la petición.
      $ctiParams array   parámetros CTI que se arrastran en los enlaces.
--}}
@php
    $calToday = now('Asia/Tokyo')->format('Ymd');
    $daysInMonth = $month->daysInMonth;
    $firstYoubi = $month->copy()->startOfMonth()->dayOfWeek;
    $lastYoubi = $month->copy()->endOfMonth()->dayOfWeek;
@endphp
<table class="smallCal"><tr><th colspan="7">{{ $month->format('Y') }}年{{ $month->month }}月</th></tr>@for ($i = 1; $i <= $daysInMonth; $i++)@php
    $calDay = $month->copy()->day($i);
    $calYmd = $calDay->format('Ymd');
    $calYoubi = $calDay->dayOfWeek;
    $calClass = match ($calYoubi) { 0 => 'sunday', 6 => 'saturday', default => 'weekday' };
    if ($calYmd === $calToday) {
        $calClass .= ' today';
    }
    if ($d && $calYmd === $d) {
        $calClass .= ' selectday';
    }
@endphp @if ($calYoubi == 0)<tr>@endif @if ($i == 1 && $firstYoubi > 0)<tr>@for ($n = 0; $n < $firstYoubi; $n++)<td></td>@endfor @endif<td><span class="{{ $calClass }}"><a href="{{ route('admin.reservas.timetable', array_merge(['shop' => $shopCd, 'd' => $calYmd], $ctiParams)) }}">{{ $i }}</a></span></td>@if ($calYoubi == 6)</tr>@endif @if ($i == $daysInMonth && $lastYoubi != 6)@for ($n = 0; $n < 6 - $lastYoubi; $n++)<td></td>@endfor</tr>@endif @endfor</table>