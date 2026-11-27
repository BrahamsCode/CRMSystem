{{-- dispCalendar() de staff_shift.php: mes pequeño con enlaces al día (hoy en .today, el elegido en .selectday).
     Variables: $month (CarbonInterface del mes), $selected (día elegido), $shopCd --}}
@php
    $first = $month->copy()->startOfMonth();
    $daysInMonth = $first->daysInMonth;
    $today = now('Asia/Tokyo');
    $html = '<table class="smallCal table table-bordered table-striped" style="width:auto;"><tr><th colspan="7">'.$first->year.'年'.$first->month.'月</th></tr>';
    for ($i = 1; $i <= $daysInMonth; $i++) {
        $date = $first->copy()->day($i);
        $wday = $date->dayOfWeek;
        if ($wday === 0) {
            $html .= '<tr>';
        }
        if ($i === 1 && $wday > 0) {
            $html .= '<tr>'.str_repeat('<td></td>', $wday);
        }
        $class = ($wday === 0 ? 'sunday' : ($wday === 6 ? 'saturday' : 'weekday'))
            .($date->isSameDay($today) ? ' today' : '')
            .($date->isSameDay($selected) ? ' selectday' : '');
        $url = route('admin.operaciones.shifts.day', ['shop_cd' => $shopCd, 'd' => $date->format('Ymd')]);
        $html .= '<td><span class="'.$class.'"><a href="'.e($url).'">'.$i.'</a></span></td>';
        if ($wday === 6) {
            $html .= '</tr>';
        }
        if ($i === $daysInMonth && $wday !== 6) {
            $html .= str_repeat('<td></td>', 6 - $wday).'</tr>';
        }
    }
    $html .= '</table>';
@endphp
{!! $html !!}
