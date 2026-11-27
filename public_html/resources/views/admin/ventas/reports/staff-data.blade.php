{{--
    Resultado de スタッフ別集計 (legacy: karute_menu/ajax_staffTotal.php), cargado por AJAX.
    Datos: $report (SalesReportService::staffTotal), $period (from, to, label), $csvUrl (nuevo: CSV SJIS-win)
--}}
<p class="h2Title">■{{ $report['staff']?->name }}&emsp;{{ $period['label'] }}の集計</p>

<table class="tableBox">

<tr>
<th>現在の歩合率</th>
<td>{{ number_format((float) ($report['staff']?->kingaku_buai ?? 0)) }}%</td>
</tr>

<tr>
<th>売上</th>
<td>{{ number_format($report['total']) }}</td>
</tr>

<tr>
<th>スタッフ歩合</th>
<td>{{ number_format($report['buai']) }}</td>
</tr>

<!-- /.tableBox --></table>

<div class="btnBox"><a href="{{ $csvUrl }}" class="btn btnStyleM">CSV出力</a></div>

<div class="staffShuukei">

  <div style="width:33%;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tableBox2">
      <tr>
        <th colspan="3" style="border-bottom:1px solid #87AFAE;">売上</th>
      </tr>
      <tr>
        <th>日付</th>
        <th>売上</th>
        <th>スタッフ歩合</th>
      </tr>
@forelse ($report['days'] as $day)
<tr><td align="left">{{ $day['date'] }}</td><td align="right">{{ number_format($day['price']) }}</td><td align="right">{{ number_format($day['buai']) }}</td></tr>
@empty
<tr><td colspan="3" align="center">データがありません。</td></tr>
@endforelse
    </table>
  </div>

  <div style="width:32%;margin: 0 1%;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tableBox2">
      <tr>
        <th colspan="3" style="border-bottom:1px solid #87AFAE;">顧客</th>
      </tr>
      <tr>
        <th>顧客</th>
        <th>件数</th>
        <th>金額</th>
      </tr>
@forelse ($report['customers'] as $customer)
<tr><td align="left">{{ $customer['name'] }}</td><td align="right">{{ number_format($customer['count']) }}</td><td align="right">{{ number_format($customer['price']) }}</td></tr>
@empty
<tr><td colspan="3" align="center">データがありません。</td></tr>
@endforelse
    </table>
  </div>

  <div style="width:33%;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tableBox2">
      <tr>
        <th colspan="3" style="border-bottom:1px solid #87AFAE;">商品</th>
      </tr>
      <tr>
        <th>メニュー</th>
        <th>件数</th>
        <th>金額</th>
      </tr>
@forelse ($report['menus'] as $menu)
<tr><td align="left">{{ $menu['name'] }}</td><td align="right">{{ number_format($menu['count']) }}</td><td align="right">{{ number_format($menu['price']) }}</td></tr>
@empty
<tr><td colspan="3" align="center">データがありません。</td></tr>
@endforelse
    </table>
  </div>

<!-- /.staffShuukei --></div>
