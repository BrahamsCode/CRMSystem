{{-- Legacy: system/category1/inc/detail_tab12.php (pestaña 予約状況 de la ficha; parcial sin @extends).
     Variables:
       $staffLabel    (string: manageWord('担当', 'c9_yoyaku[スタッフ]'))
       $reservations  (lista de reservas desde hoy: tb_yoyaku (disp_flg '1') + tb_yoyaku_uketsuke_day con moushikomi_date >= hoy,
                       orden moushikomi_date DESC, start_time DESC, cd. Claves por fila:
                         moushikomi_date  (string 'Y/m/d' o 'Y-m-d' de tb_yoyaku_uketsuke_day)
                         start_label, end_label (string 'H:i' ya calculados: moushikomi_date + start_time/end_time,
                                                 con la hora +24 si el segundo pasa de 86400, como el legacy)
                         shop_name        (tb_shop.kaishamei del tb_staff.shop_cd del responsable)
                         staff_name       (tb_staff.name)
                         menu             (nombres de tb_yoyaku_koumoku_master (base + option) vía tb_staff_works, con
                                           mb_convert_kana(name, 'k') y separados por espacio, como el legacy)
                         comment          (tb_yoyaku.comment)) --}}
@php
    $youbi = ['日', '月', '火', '水', '木', '金', '土'];
@endphp

<div class="tabBody">

<table class="tableBox2">

<tr>
<th style="width: 10em;">日付</th>
<th style="width: 10em;">予約時間</th>
<th style="width: 10em;">予約店舗</th>
<th style="width: 10em;">{{ $staffLabel }}</th>
<th>メニュー</th>
<th>備考</th>
</tr>

@forelse ($reservations as $reservation)
@php
    // 予約日 (fecha de calendario, sin hora)
    $moushikomiDate = \Illuminate\Support\Carbon::parse($reservation['moushikomi_date'], 'Asia/Tokyo');
@endphp
<tr{!! $loop->index % 2 != 0 ? ' class="tableBg"' : '' !!}>
<td>{{ $moushikomiDate->format('Y年m月d日') }}({{ $youbi[$moushikomiDate->dayOfWeek] }}) </td>
<td>{{ $reservation['start_label'] }}&nbsp;～&nbsp;{{ $reservation['end_label'] }}</td>
<td>{{ $reservation['shop_name'] }}</td>
<td>{{ $reservation['staff_name'] }}</td>
<td>{{ $reservation['menu'] }}</td>
<td>{!! nl2br(e($reservation['comment'])) !!}</td>
</tr>

@empty
<tr>
<td colspan="9">現在、予約はありません。</td>
</tr>

@endforelse
</table>

</div>
