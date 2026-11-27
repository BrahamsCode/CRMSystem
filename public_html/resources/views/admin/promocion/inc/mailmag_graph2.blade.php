{{--
	クリックカウンタ — legacy: system/category3/inc/mailmag_graph2.php (+ graph_sql.php), común a
	mail_mag/mailmag_analyze, decomail/mailmag_analyze, mycoupon/mycoupon_analyze y anketo/anketo_analyze.
	Variables: $pageTitle, $activeAc, $requestType, $stats (ClickStats), $isMaster
	La descarga CSV por Adobe AIR/Flash del legacy se cambia por el formulario normal.
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', $pageTitle . ' - ' . $siteName)

@php
	$ages = $stats['ages'];
	$jobItems = collect($stats['jobs'])->map(fn ($n, $job) => [$job, $n, \App\Services\Promotions\ClickStats::JOB_COLORS[$job] ?? ''])->values();
@endphp

@section('js')
<!--[if IE]><script type="text/javascript" src="{{ asset('legacy/system/js/excanvas.js') }}"></script><![endif]-->
<script type="text/javascript" src="{{ asset('legacy/system/js/circle.js') }}"></script>
<script type="text/javascript">
window.onload = function() {
  var params = {
    caption: true,
    legend: false
  };

  var cg1 = new html5jp.graph.circle("total");
  if( ! cg1 ) { return; }
  var total_items = [
    ["男性", {{ $stats['otoko_total'] }}],
    ["女性", {{ $stats['onna_total'] }}, "#D60000"],
    ["不明", {{ $stats['fumei_total'] }}, "#333333"]
  ];
  cg1.draw(total_items, params);

  var cg2 = new html5jp.graph.circle("nendai_betsu");
  if( ! cg2 ) { return; }
  var nendai_items = [
    ["19歳以下", {{ $ages['customer_0'] ?? 0 }}],
    ["20代", {{ $ages['customer_20'] ?? 0 }}],
    ["30代", {{ $ages['customer_30'] ?? 0 }}],
    ["40代", {{ $ages['customer_40'] ?? 0 }}],
    ["50代", {{ $ages['customer_50'] ?? 0 }}],
    ["60代", {{ $ages['customer_60'] ?? 0 }}],
    ["70歳以上", {{ $ages['customer_70'] ?? 0 }}],
    ["その他", {{ $ages['customer_undef'] ?? 0 }}, "#CCCCCC"]
  ];
  cg2.draw(nendai_items, params);

  var cg3 = new html5jp.graph.circle("shokugyo_betsu");
  if( ! cg3 ) { return; }
  var shokugyo_items = {!! json_encode($jobItems, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!};
  cg3.draw(shokugyo_items, params);
};

$(document).ready(function() {
	$("{{ $activeAc }}").accordion('option', 'active', 0);
	window.scroll(0,0);
});
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■{{ $pageTitle }}</p>

@if ($stats['count'] > 0)
<table id="graph" width="750px">
	<tr>
		<th colspan="2" class="graphTitleM">性別</th>
	</tr>
	<tr style="height: 320px;">
		<td colspan="2" style="vertical-align:top;border:1px solid lightgrey;">
			<div style="width: 300px; text-align:center;">
			<canvas width="300" height="300" id="total"></canvas>
			</div>
		</td>
	</tr>
	<tr>
		<th class="graphTitleM">年代別</th>
		<th class="graphTitleM">職業別</th>
	</tr>
	<tr height="300px">
		<td style="vertical-align:top;border:1px solid lightgrey;width:50%">
			<div style="width: 300px; text-align:center;">
			<canvas width="300" height="300" id="nendai_betsu"></canvas>
			</div>
		</td>
		<td style="vertical-align:top;border:1px solid lightgrey;width:50%">
			<div style="width: 300px; text-align:center;">
			@if ($jobItems->isNotEmpty())
			<canvas width="300" height="300" id="shokugyo_betsu"></canvas>
			@else
			<p style="margin-top:150px;color:red;font-size:20px;font-weight:bold;">データはありません</p>
			@endif
			</div>
		</td>
	</tr>
</table>

<div style="test-align:center;margin-top:10px;width:750px;">
@if ($isMaster)
	<form method="post" action="{{ route('legacy.promocion.inc.csv_download') }}">
		@csrf
		<input name="request_type" type="hidden" value="{{ $requestType }}" />
		<input type="submit" name="enter" value="CSV出力する" />
	</form>
@endif
</div>

<div><p>※当集計は、配信されたメルマガに記載されるマイページへのリンクからアクセスされた数をもとに集計しております。</p></div>
@else
該当する情報がみつかりません。<br /><br />
@endif

<!-- / .contents --></div>
@endsection
