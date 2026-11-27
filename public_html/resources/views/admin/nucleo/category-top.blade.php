{{-- Portada de un módulo: system/category<N>/index.php (+ inc/entry_counter.php en 1 y 3) --}}
@extends('layouts.legacy')

@section('title', $systemName . 'トップ ' . ($titleSuffix ?? '- ' . $siteName))

@section('menu', $menuKey)

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac1").accordion('option', 'active', 0);
});
</script>
@endsection

@section('content')
<div class="contents cover"><img src="/system/img/cover{{ $category }}.gif" width="595" height="220" alt="{{ $systemName }}" />
<!-- / .contents --></div>

@if ($counter)
@php
    $list = '/system/category1/customer/customer_list.php?se_flg=1&act=up' . $counter['shopQuery'] . '&touroku_kubun=2';
    $active = '&disp_flg=' . urlencode('登録済');
    $left = '&disp_flg=' . urlencode('退会済');
    $fail = '&mailmaga_flg=' . urlencode('不達配信');
    $headings = ['all' => '顧客登録数 総計', 'month' => $counter['now']->format('n') . '月度 登録数', 'day' => $counter['now']->format('n') . '月' . $counter['now']->format('j') . '日 登録数'];
@endphp
<div class="contents">
<ul class="tableList">
@foreach ($counter['counts'] as $period => $c)<li>
<table class="tableBox6">
  <thead>
    <tr>
      <th colspan="2">{{ $headings[$period] }}</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <th>登録数</th>
      <td><a href="{{ $list . $active . '&mailmaga_flg=' . ($c['from'] ? '&touroku_date_1=' . $c['from'] : '') }}">{{ $c['add'] }}件</a></td>
    </tr>
@if ($category === 1)
    <tr>
      <th>解約数</th>
      <td><a href="{{ $list . $left . '&mailmaga_flg=' . ($c['from'] ? '&update_date_1=' . $c['from'] : '') }}">{{ $c['del1'] }}件</a></td>
    </tr>
@else
    <tr>
      <th>不達確定数</th>
      <td><a href="{{ $list . $active . $fail . ($c['from'] ? '&update_date_1=' . $c['from'] : '') }}">{{ $c['del2'] }}件</a></td>
    </tr>
@endif
  </tbody>
</table>
</li>@endforeach
</ul>
<!-- / .contents --></div>
@endif
@endsection
