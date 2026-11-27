{{--
    Legacy: system/category9/yoyaku/ajax_loadNewRaitenList.php (来店リスト del día en el panel lateral).
    Variables:
      $rows     Collection hasta 20 tb_raiten_rireki del día (+ name de tb_customer), orden raiten_time desc.
      $day      Carbon     día mostrado (`d` de la petición u hoy).
      $shopCd   ?string    tb_shop_cd de la petición (enlace al 予約表 de ese día).
      $isTablet bool       abre la ficha con roid.popUp().
--}}
@if ($rows->isNotEmpty())
<table class="tableBox8">
<tr>
<th>来店時間</th>
<th>顧客名</th>
</tr>
@foreach ($rows as $row)
@php
    $detailUrl = '/system/category1/inc/customer_detail.php?kaiin_cd='.$row->user_cd.'&wintype=no_headers';
    $detailJs = $isTablet
        ? "roid.popUp('".url($detailUrl)."');"
        : "javascript:window.open('".$detailUrl."', 'mywindow1', 'width=970, height=550, menubar=no, toolbar=no, scrollbars=yes');";
    // raiten_time es epoch en texto: si no es numérico el legacy no mostraba la hora
    $visitedAt = is_numeric($row->raiten_time) ? \Illuminate\Support\Carbon::createFromTimestamp((int) $row->raiten_time, 'Asia/Tokyo') : null;
    $dayUrl = route('admin.reservas.timetable', ['shop' => $shopCd, 'd' => $visitedAt?->format('Ymd') ?? '']);
@endphp

<tr>
<td><a onclick="javascript:location.href={{ \Illuminate\Support\Js::from($dayUrl) }};">{{ $visitedAt?->format('m月d日 H時i分s秒') }}</a></td>
<td>
@if ($row->user_cd)
<a href="#" onclick="{{ $detailJs }}">
@endif
{{ $row->name }}
@if ($row->user_cd)
</a>
@endif
</td>
</tr>

@endforeach
</table>
@else
<div style="margin-top: 10px;text-align: center;">
{{ $day->format('Y') }}年{{ $day->format('m') }}月{{ $day->format('d') }}日の来店履歴はありません。
</div>
@endif
