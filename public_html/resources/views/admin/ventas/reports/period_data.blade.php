{{--
    Legacy: system/category1/karute_menu/ajax_report.php (resultado de 日報・週報・月報, fragmento HTML).
    Variables:
      $shopName     ?string  kaishamei de la tienda (null = 全店舗)
      $periodLabel  string   'Y/m/d' o 'Y/m/d ～ Y/m/d'
      $days         array    [business_date => [price_total, discount, other_discount, buai, keihi]] (orden del legacy)
      $staff        array    [スタッフ名 => [price_total, discount, other_discount, buai]]
      $totals       array    price_total, discount, other_discount, buai, keihi
      $junRieki     float    純利益 = 売上 - (割引 + その他の割引 + 経費)
      $araRieki     float    粗利 = 純利益 - スタッフ歩合
--}}
<p class="h2Title">■{{ $shopName ?? '全店舗' }}&emsp;{{ $periodLabel }}の集計</p>

@if (count($days) > 0)
<div class="reportBox">

<div style="width: 15%;">
<table class="tableBox">
  <tr>
    <th>売上</th>
  </tr>
  <tr>
    <td class="right">{{ number_format($totals['price_total']) }}円</td>
  </tr>
</table>
</div>

<div class="kigou">
<p>-</p>
</div>

<div style="width: 15%;">
<table class="tableBox">
  <tr>
    <th>割引</th>
  </tr>
  <tr>
    <td class="right">{{ number_format($totals['discount']) }}円</td>
  </tr>
</table>
</div>

<div class="kigou">
<p>-</p>
</div>

<div style="width: 15%;">
<table class="tableBox">
  <tr>
    <th>その他の割引</th>
  </tr>
  <tr>
    <td class="right">{{ number_format($totals['other_discount']) }}円</td>
  </tr>
</table>
</div>

<div class="kigou">
<p>-</p>
</div>

<div style="width: 15%;">
<table class="tableBox">
  <tr>
    <th>経費</th>
  </tr>
  <tr>
    <td class="right">{{ number_format($totals['keihi']) }}円</td>
  </tr>
</table>
</div>

<div class="kigou">
<p>=</p>
</div>

<div style="width: 15%;">
<table class="tableBox">
  <tr>
    <th>純利益</th>
  </tr>
  <tr>
    <td class="right">{{ number_format($junRieki) }}円</td>
  </tr>
</table>
</div>

</div>

<div class="reportBox">

<div style="width: 15%;">
<table class="tableBox">
  <tr>
    <th>純利益</th>
  </tr>
  <tr>
    <td class="right">{{ number_format($junRieki) }}円</td>
  </tr>
</table>
</div>

<div class="kigou">
<p>-</p>
</div>

<div style="width: 15%;">
<table class="tableBox">
  <tr>
    <th>スタッフ歩合</th>
  </tr>
  <tr>
    <td class="right">{{ number_format($totals['buai']) }}円</td>
  </tr>
</table>
</div>

<div class="kigou">
<p>=</p>
</div>

<div style="width: 15%;">
<table class="tableBox">
  <tr>
    <th>粗利</th>
  </tr>
  <tr>
    <td class="right">{{ number_format($araRieki) }}円</td>
  </tr>
</table>
</div>

</div>

<p class="h3Title">売上</p>
<table class="tableBox4">
  <tr>
    <th><label for="send_date">日付</label></th>
    <th><label for="send_date">売上</label></th>
    <th><label for="send_date">割引</label></th>
    <th><label for="send_date">その他の割引</label></th>
    <th><label for="send_date">経費</label></th>
    <th><label for="send_date">純利益</label></th>
    <th><label for="send_date">スタッフ歩合</label></th>
     <th><label for="send_date">粗利</label></th>
 </tr>

@foreach ($days as $day => $dayData)
@php
    $dayJunRieki = ($dayData['price_total'] ?? 0) - (($dayData['discount'] ?? 0) + ($dayData['other_discount'] ?? 0) + ($dayData['keihi'] ?? 0));
    $dayAraRieki = $dayJunRieki - ($dayData['buai'] ?? 0);
@endphp
  <tr>
    <td>{{ $day }}</td>
    <td class="right">{{ number_format($dayData['price_total'] ?? 0) }}円</td>
    <td class="right">{{ number_format($dayData['discount'] ?? 0) }}円</td>
    <td class="right">{{ number_format($dayData['other_discount'] ?? 0) }}円</td>
    <td class="right">{{ number_format($dayData['keihi'] ?? 0) }}円</td>
    <td class="right">{{ number_format($dayJunRieki) }}円</td>
    <td class="right">{{ number_format($dayData['buai'] ?? 0) }}円</td>
    <td class="right">{{ number_format($dayAraRieki) }}円</td>
  </tr>
@endforeach
  <tr>
	<th>合計</th>
    <th class="right">{{ number_format($totals['price_total']) }}円</th>
    <th class="right">{{ number_format($totals['discount']) }}円</th>
    <th class="right">{{ number_format($totals['other_discount']) }}円</th>
    <th class="right">{{ number_format($totals['keihi']) }}円</th>
    <th class="right">{{ number_format($junRieki) }}円</th>
    <th class="right">{{ number_format($totals['buai']) }}円</th>
    <th class="right">{{ number_format($araRieki) }}円</th>
  </tr>
</table>

<p class="h3Title">スタッフ別</p>
<table class="tableBox4">
  <tr>
    <th>スタッフ名</th>
    <th>売上</th>
    <th>割引</th>
    <th>その他の割引</th>
    <th>金額</th>
    <th>スタッフ歩合</th>
  </tr>

@foreach ($staff as $name => $staffData)
  <tr>
    <td>{{ $name }}</td>
    <td class="right">{{ number_format($staffData['price_total'] ?? 0) }}円</td>
    <td class="right">{{ number_format($staffData['discount'] ?? 0) }}円</td>
    <td class="right">{{ number_format($staffData['other_discount'] ?? 0) }}円</td>
    <td class="right">{{ number_format(($staffData['price_total'] ?? 0) - (($staffData['discount'] ?? 0) + ($staffData['other_discount'] ?? 0))) }}円</td>
    <td class="right">{{ number_format($staffData['buai'] ?? 0) }}円</td>
  </tr>
@endforeach
  <tr>
	<th>合計</th>
    <th class="right">{{ number_format($totals['price_total']) }}円</th>
    <th class="right">{{ number_format($totals['discount']) }}円</th>
    <th class="right">{{ number_format($totals['other_discount']) }}円</th>
    <th class="right">{{ number_format($totals['price_total'] - ($totals['discount'] + $totals['other_discount'])) }}円</th>
    <th class="right">{{ number_format($totals['buai']) }}円</th>
  </tr>
</table>
@else
データがありません。
@endif
