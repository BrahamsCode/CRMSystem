{{--
    Legacy: system/category1/karute_menu/ajax_staffTotal.php (resultado de スタッフ別集計, fragmento HTML).
    Variables:
      $staff            ?Staff       スタッフ (nombre y kingaku_buai = 現在の歩合率)
      $periodLabel      string       'Y/m/d' o 'Y/m/d ～ Y/m/d'
      $byDay            Collection   business_date, price_total, buai_total
      $byCustomer       Collection   tb_customer_cd, name, cnt, price_total
      $byMenu           Collection   menu_name, cnt, price_total
      $salesTotal       float        suma de price_total por día (売上)
      $commissionTotal  float        suma de buai_total (スタッフ歩合)
--}}
<p class="h2Title">■{{ $staff?->name }}&emsp;{{ $periodLabel }}の集計</p>

<table class="tableBox">

<tr>
<th>現在の歩合率</th>
<td>{{ number_format((float) ($staff?->kingaku_buai ?? 0)) }}%</td>
</tr>

<tr>
<th>売上</th>
<td>{{ number_format($salesTotal) }}</td>
</tr>

<tr>
<th>スタッフ歩合</th>
<td>{{ number_format($commissionTotal) }}</td>
</tr>

<!-- /.tableBox --></table>

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
@forelse ($byDay as $row)
<tr><td align="left">{{ $row->business_date }}</td><td align="right">{{ number_format((float) $row->price_total) }}</td><td align="right">{{ number_format((float) $row->buai_total) }}</td></tr>
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
@forelse ($byCustomer as $row)
<tr><td align="left">{{ $row->name }}</td><td align="right">{{ number_format((float) $row->cnt) }}</td><td align="right">{{ number_format((float) $row->price_total) }}</td></tr>
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
@forelse ($byMenu as $row)
<tr><td align="left">{{ $row->menu_name }}</td><td align="right">{{ number_format((float) $row->cnt) }}</td><td align="right">{{ number_format((float) $row->price_total) }}</td></tr>
@empty
<tr><td colspan="3" align="center">データがありません。</td></tr>
@endforelse
    </table>
  </div>

<!-- /.staffShuukei --></div>
