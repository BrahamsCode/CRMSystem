{{--
    Resultado de 日報・週報・月報 (legacy: karute_menu/ajax_report.php), cargado por AJAX.
    Datos: $report (SalesReportService::periodReport: shop_name, totals, days, staff), $period, $csvUrl (nuevo)
--}}
@php $t = $report['totals']; @endphp
<p class="h2Title">■{{ $report['shop_name'] }}&emsp;{{ $period['label'] }}の集計</p>

@if (count($report['days']) > 0)
<div class="btnBox"><a href="{{ $csvUrl }}" class="btn btnStyleM">CSV出力</a></div>

<div class="reportBox">

<div style="width: 15%;">
<table class="tableBox">
  <tr>
    <th>売上</th>
  </tr>
  <tr>
    <td class="right">{{ number_format($t['price']) }}円</td>
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
    <td class="right">{{ number_format($t['discount']) }}円</td>
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
    <td class="right">{{ number_format($t['other_discount']) }}円</td>
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
    <td class="right">{{ number_format($t['keihi']) }}円</td>
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
    <td class="right">{{ number_format($t['jun']) }}円</td>
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
    <td class="right">{{ number_format($t['jun']) }}円</td>
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
    <td class="right">{{ number_format($t['buai']) }}円</td>
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
    <td class="right">{{ number_format($t['ara']) }}円</td>
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

@foreach ($report['days'] as $day => $d)
  <tr>
    <td>{{ $day }}</td>
    <td class="right">{{ number_format($d['price']) }}円</td>
    <td class="right">{{ number_format($d['discount']) }}円</td>
    <td class="right">{{ number_format($d['other_discount']) }}円</td>
    <td class="right">{{ number_format($d['keihi']) }}円</td>
    <td class="right">{{ number_format($d['jun']) }}円</td>
    <td class="right">{{ number_format($d['buai']) }}円</td>
    <td class="right">{{ number_format($d['ara']) }}円</td>
  </tr>
@endforeach
  <tr>
	<th>合計</th>
    <th class="right">{{ number_format($t['price']) }}円</th>
    <th class="right">{{ number_format($t['discount']) }}円</th>
    <th class="right">{{ number_format($t['other_discount']) }}円</th>
    <th class="right">{{ number_format($t['keihi']) }}円</th>
    <th class="right">{{ number_format($t['jun']) }}円</th>
    <th class="right">{{ number_format($t['buai']) }}円</th>
    <th class="right">{{ number_format($t['ara']) }}円</th>
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

@foreach ($report['staff'] as $name => $s)
  <tr>
    <td>{{ $name }}</td>
    <td class="right">{{ number_format($s['price']) }}円</td>
    <td class="right">{{ number_format($s['discount']) }}円</td>
    <td class="right">{{ number_format($s['other_discount']) }}円</td>
    <td class="right">{{ number_format($s['amount']) }}円</td>
    <td class="right">{{ number_format($s['buai']) }}円</td>
  </tr>
@endforeach
  <tr>
	<th>合計</th>
    <th class="right">{{ number_format($t['price']) }}円</th>
    <th class="right">{{ number_format($t['discount']) }}円</th>
    <th class="right">{{ number_format($t['other_discount']) }}円</th>
    <th class="right">{{ number_format($t['amount']) }}円</th>
    <th class="right">{{ number_format($t['buai']) }}円</th>
  </tr>
</table>
@else
データがありません。
@endif
