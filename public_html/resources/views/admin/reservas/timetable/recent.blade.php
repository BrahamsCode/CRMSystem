{{--
    Legacy: system/category9/yoyaku/ajax_loadNewYoyakuList.php (新規予約リスト del panel lateral; vacío si no hay reservas).
    Variables:
      $rows       Collection las 10 últimas reservas (cd, tb_customer_cd, touroku_date epoch, customer_name, moushikomi_date 'Y/m/d', staff_name).
      $toast      bool    hay una reserva nueva desde la última carga (muestra 「新しいリストをロードしました。」).
      $shopCd     ?string tb_shop_cd de la petición (enlace al 予約表 de ese día).
      $staffLabel string  manageWord('スタッフ名', 'c9_yoyaku[スタッフ]').
      $isTablet   bool    abre la ficha con roid.popUp().
--}}
@if ($rows->isNotEmpty())
@if ($toast)
<script>$.toast("新しいリストをロードしました。",{top:190});</script>
@endif
<table class="tableBox8">
<tr>
<th style="width:70px;">予約日</th>
<th>予約者名</th>
<th>{{ $staffLabel }}</th>
<th>受付日</th>
</tr>
@foreach ($rows as $row)
@php
    $detailUrl = '/system/category1/inc/customer_detail.php?kaiin_cd='.$row->tb_customer_cd.'&wintype=no_headers';
    $detailJs = $isTablet
        ? "roid.popUp('".url($detailUrl)."');"
        : "javascript:window.open('".$detailUrl."', 'mywindow1', 'width=970, height=550, menubar=no, toolbar=no, scrollbars=yes');";
    $dayUrl = route('admin.reservas.timetable', ['shop' => $shopCd, 'd' => str_replace('/', '', (string) $row->moushikomi_date)]);
@endphp

<tr>
<td><a onclick="javascript:location.href={{ \Illuminate\Support\Js::from($dayUrl) }};">{{ $row->moushikomi_date }}</a></td>
<td>
@if ($row->tb_customer_cd)
<a href="#" onclick="{{ $detailJs }}">
@endif
{{ $row->customer_name }}
@if ($row->tb_customer_cd)
</a>
@endif
</td>
<td>{{ $row->staff_name }}</td>
<td>{{ \Illuminate\Support\Carbon::createFromTimestamp((int) $row->touroku_date, 'Asia/Tokyo')->format('Y/m/d H:i:s') }}</td>
</tr>

@endforeach
</table>
@endif
