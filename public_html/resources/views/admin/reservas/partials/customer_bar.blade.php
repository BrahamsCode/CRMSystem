{{--
    Legacy: makeYoyakuTableRow() de system/category9/yoyaku/time_table_function.php (una reserva del 予約表).
    Variables:
      $bar array, ya calculado por el controlador:
        'left'   => float  px ((start_time - 9h - inicio de config) / 60)
        'top'    => int    px (24 * reservas solapadas anteriores, 0 si no solapa)
        'width'  => float  px ((end_time - start_time) / 60 - 2)
        'height' => int    20 (1 persona) | 40 (2 o más)
        'isKaiin'=> bool   tb_yoyaku.tb_customer_cd no vacío (fondo #FFBBBB; si no, #AACCFF)
        'cd', 'staff' (tb_staff_cd), 'people' (max(people, 1)), 'peopleRaw' (tb_yoyaku.people), 'mark', 'entryType'
        'name'   => string tb_customer.name si es socio; si no, tb_yoyaku.name
        'tel'    => string tb_yoyaku.tel, o 'データがありません。' si el socio no tiene teléfono
        'startTime', 'endTime' => 'H:i'
        'comment'     => string tb_yoyaku.comment sin escapar
        'menuNames'   => string[] nombres de tb_yoyaku_koumoku_master (base y opción, en ese orden)
        'freeKoumoku' => string[] valores (rawurldecode) de los free_koumoku visibles, en orden
    Los atributos que el JS mete con .html() (kokyaku, comment, menu, free_koumoku) se escapan dos veces:
    el atributo lleva HTML seguro (`<br />`, `<ul><li>`) y el texto del usuario queda como texto.
--}}
@php
    $menuHtml = '<ul>'.collect($bar['menuNames'])->map(fn ($menuName) => '<li>'.e($menuName).'</li>')->implode('').'</ul>';
    $freeKoumokuAttr = collect($bar['freeKoumoku'])->map(fn ($value) => e($value))->implode('<>');
@endphp
<div class="customerBar" style="left: {{ $bar['left'] }}px; top: {{ $bar['top'] }}px; width: {{ $bar['width'] }}px; height: {{ $bar['height'] }}px;background: {{ $bar['isKaiin'] ? '#FFBBBB' : '#AACCFF' }};">
<a class="yoyakuObj" style="white-space: nowrap; overflow: hidden; text-overflow:ellipsis; width:100%; height:100%;"
cd="{{ $bar['cd'] }}"
staff="{{ $bar['staff'] }}"
kokyaku="{{ e($bar['name']) }}"
people="{{ $bar['people'] }}"
mark="{{ $bar['mark'] }}"
tel="{{ $bar['tel'] }}"
startTime="{{ $bar['startTime'] }}"
endTime="{{ $bar['endTime'] }}"
comment="{{ nl2br(e($bar['comment'])) }}"
entryType="{{ $bar['entryType'] }}"
free_koumoku="{{ $freeKoumokuAttr }}"
menu="{{ $menuHtml }}">
{{-- separador fijo del sistema: <br> si hay más de una persona, espacio si no --}}
{{ $bar['mark'] }}{{ $bar['name'] }}{!! (int) $bar['peopleRaw'] > 1 ? '<br>' : ' ' !!}{{ $bar['people'] }}名
</a>
</div>
