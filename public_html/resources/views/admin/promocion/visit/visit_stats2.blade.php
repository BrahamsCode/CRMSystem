{{--
	来店履歴一覧 — legacy: system/category3/visit/visit_stats2.php
	Variables (VisitStatsService::stats() + controlador): $shops, $isMaster, $periods, $shopCd, $kikan, $udate, $disp,
	$kensu, $page, $selectedDay, $startTime, $endTime, $dispTitle, $titleStr, $data, $total, $widthMultiply,
	$graph, $ages, $jobs, $ratios, $modoruDisp, $modoruKikan, $list, $submitted
	Sin el botón Flash (AdobeAIR) ni el gráfico vertical (el legacy fija $type = "horizontal").
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', '来店履歴一覧 - ' . $siteName)

@section('css')
<link href="{{ asset('legacy/system/css/popupwindow.css') }}" rel="stylesheet" type="text/css" media="all" />
<style type="text/css"><!--
.graLink {
	text-decoration: none!important;
}

.graBar {
	display: block;
	width: 10px;
	height: 10px;
	margin: 0 auto;
	border: 1px solid #2F8EFF;
	background: #5FA8FF;
}

.graLink:hover .graBar {
	border: 1px solid #336699;
	background: #3B67EF;
}

.graKensu {
	display: block;
	text-align: center;
}

.graphCanvas {
	font-size: small;
}

.graphCanvas h1 {
	font-size: large;
}

.graphCanvas h2 {
	font-size: medium;
}
--></style>
@endsection

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac4").accordion('option', 'active', 0);
	window.scroll(0,0);
});
</script>

<script type="text/javascript" src="{{ asset('legacy/system/js/popupwindow-1.8.js') }}"></script>

<script type="text/javascript">
window.onresize = dispScrollbar;
graph_type = 'horizontal';

//表示件数の制御

function kensuSet(parts){
	document.form_change_page.kensu.value = parts.value;
	ChangePage('1');
}

function ChangeKikan(disp, kikan, udate){
	document.form_change_page.disp.value = disp;
	document.form_change_page.kikan.value = kikan;
	document.form_change_page.udate.value = udate;
	ChangePage('1');
}

function ChangePage(page){
	document.form_change_page.page.value = page;
	document.form_change_page.submit();
}

function dispScrollbar() {
	if ($("#graph").width() < 745 && graph_type == 'vertical') {
		$("#graph").css("overflow-x","auto");
	} else {
		$("#graph").css("overflow-x","");
	}
}
</script>

{{-- 円グラフ (始) --}}
<script type="text/javascript" src="{{ asset('legacy/system/js/circle.js') }}"></script>
<script type="text/javascript">
window.onload = function() {
  dispScrollbar();
  var params = {
    caption: false,
    legend: false
  };

  var cg1 = new html5jp.graph.circle("total");
  if( ! cg1 ) { return; }
  var total_items = [
    ["男性", {{ (int) $graph['otoko_total'] }}],
    ["女性", {{ (int) $graph['onna_total'] }}, "#D60000"],
    ["不明", {{ (int) $graph['fumei_total'] }}, "#333333"]
  ];
  cg1.draw(total_items, params);

  var cg2 = new html5jp.graph.circle("nendai_betsu");
  if( ! cg2 ) { return; }
  var nendai_items = [
    ["19歳以下", {{ (int) $ages['customer_0'] }}],
    ["20代", {{ (int) $ages['customer_20'] }}],
    ["30代", {{ (int) $ages['customer_30'] }}],
    ["40代", {{ (int) $ages['customer_40'] }}],
    ["50代", {{ (int) $ages['customer_50'] }}],
    ["60代", {{ (int) $ages['customer_60'] }}],
    ["70歳以上", {{ (int) $ages['customer_70'] }}],
    ["その他", {{ (int) $ages['customer_undef'] }}, "#CCCCCC"]
  ];
  cg2.draw(nendai_items, params);

  var cg3 = new html5jp.graph.circle("shokugyo_betsu");
  if( ! cg3 ) { return; }
  var shokugyo_items = {!! json_encode(array_map(fn ($j) => array_values(array_filter($j, fn ($v) => $v !== null)), $jobs), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!};
  cg3.draw(shokugyo_items, params);
};
</script>
{{-- 円グラフ (終) --}}
@endsection

@section('content')
@php
	$legend = function (array $items) {
		$html = '<div style="width:120px;float:right;text-align:left;padding:5px;font-size:12px;">'."\n".'<br>'."\n".'<ul>'."\n";
		foreach ($items as $label => $color) {
			$html .= '<li><span style="background-color:'.$color.';width:22px;height:12px;">&nbsp;&nbsp;&nbsp;</span>&nbsp;'.e($label).'</li>'."\n";
		}

		return $html.'</ul>'."\n".'</div>';
	};
	$self = route('admin.promocion.visit.stats');
@endphp
<div class="contents">
<p class="h2Title">■来店履歴抽出</p>

<form method="post" name="form_search" id="form_search" class="form" action="{{ $self }}">

@if ($isMaster)
<select name="shop_cd"@if ($shops->count() < 2) class="displayNone"@endif>
@foreach ($shops as $shop)
<option value="{{ $shop->cd }}"@selected((int) $shop->cd === $shopCd)>{{ $shop->kaishamei }}</option>
@endforeach
</select>

@else
{{ $shops->first()->kaishamei }}
<input type="hidden" name="shop_cd" value="{{ $shops->first()->cd }}">
@endif

<select name="kikan">
@foreach ($periods as $i => $label)
<option value="{{ $i }}"@selected($kikan === $i)>{{ $label }}</option>
@endforeach
</select>

<input type="submit" name="sbm" class="btnStyle" value="表示" />
</form>

<!-- / .contents --></div>


<div class="contents">

@if ($total > 0)

<!-- 解析テーブル 開始 -->
<p class="h2Title">■解析</p>
<table class="tableBox" style="width:700px" cellpadding="4" cellspacing="1" >
	<tr height="250px">
		<td style="vertical-align:middle;width:50%">
		<span style="font-size:120%;font-weight:bold; color: #336699;">{{ $dispTitle }}</span>来店人数は<span style="font-size:120%;font-weight:bold; color: #336699;">{{ $total }}人</span>。<br />
	【内訳】
	<ul>
		<li>■ 男性： <strong>{{ $graph['otoko_total'] }}人</strong>来店(<strong>{{ $ratios['otoko'] }}%</strong>)</li>
		<li>■ 女性： <strong>{{ $graph['onna_total'] }}人</strong>来店(<strong>{{ $ratios['onna'] }}%</strong>)</li>
		<li>■ 不明： <strong>{{ $graph['fumei_total'] }}人</strong>来店(<strong>{{ $ratios['fumei'] }}%</strong>)</li>
		<li>
@if ($isMaster)
			<form method="post" action="{{ route('admin.promocion.visit.csv') }}">
				<input name="start_time" type="hidden" value="{{ $startTime }}" />
				<input name="end_time" type="hidden" value="{{ $endTime }}" />
				<input name="selected_day" type="hidden" value="{{ $selectedDay }}" />
				<input name="kikan" type="hidden" value="{{ $kikan }}" />
				<input name="shop_cd" type="hidden" value="{{ $shopCd }}" />
				<input type="submit" name="enter" value="CSV出力する" />
			</form>
@endif
		</li>
	</ul>

		</td>
		<td style="vertical-align:top;width:50%">
			<p class="graphTitleS">性別</p>
			{!! $legend(['男性' => '#1829CE', '女性' => '#D60000', '不明' => '#333333']) !!}
			<div style="float:left"><canvas width="200" height="200" id="total"></canvas></div>
		</td>
	</tr>
	<tr height="250px">
		<td style="vertical-align:top;width:50%">
			<p class="graphTitleS">年代別</p>
			{!! $legend(['19歳以下' => '#1829CE', '20代' => '#C60094', '30代' => '#D60000', '40代' => '#FF9C00', '50代' => '#219C00', '60代' => '#840000', '70歳以上' => '#5FA8FF', 'その他' => '#CCCCCC']) !!}
			<div style="float:left"><canvas width="200" height="200" id="nendai_betsu"></canvas></div>
		</td>
		<td style="vertical-align:top;width:50%">
			<p class="graphTitleS">職業別</p>
			{!! $legend(\App\Services\Promotions\VisitStatsService::JOB_COLORS) !!}
			<div style="float:left"><canvas width="200" height="200" id="shokugyo_betsu"></canvas></div>
		</td>
	</tr>
</table>
<!-- 解析テーブル 終了 -->


<p class="h2Title">■来店者数</p>


<span style="font-weight:bold; font-size:130%; color:#336699;">{!! $titleStr !!}</span>
<div id="graph">

<table class="tableBox4" style="text-align:center;">
@foreach ($data as $row)
	  <tr>
	    <th style="border:none;">{{ $row['title'] }}</th>
		<td style="text-align: left; border:none;">
			@if ($row['count'] > 0)<a href ="javascript:;" onclick="{{ $row['onclick'] }}"><span class="graBar" style="width: {{ $row['count'] * $widthMultiply }}px;display:inline-block;"></span></a>@endif
			{{ $row['count'] }}
		</td>
	  </tr>
@endforeach
</table>
@if ($modoruDisp !== '')
&nbsp;<a href="{{ $self }}?shop_cd={{ rawurlencode((string) $shopCd) }}&amp;kikan={{ $modoruKikan }}&amp;udate={{ $selectedDay }}&amp;type=horizontal">{{ $modoruDisp }}</a>
@endif
</div>
<br/>
<form name="form_change_page" action="{{ $self }}#result" method="get">
<input type="hidden" name="shop_cd" value="{{ $shopCd }}">
<input type="hidden" name="kikan" value="{{ $kikan }}">
<input type="hidden" name="udate" value="{{ $udate }}">
<input type="hidden" name="type" value="horizontal">
<input type="hidden" name="disp" value="{{ $disp }}">
<input type="hidden" name="kensu" value="{{ $kensu }}">
<input type="hidden" name="page" value="{{ $page }}">
</form>
@if ($list !== null)
<div class="contents" id="result">
<div id="form"><p class="maxListLength">表示件数<select name="kensu_act" id="kensu_act" class="selectBox" onchange="kensuSet(this)">
@for ($i = 1; $i <= 5; $i++)
<option value="{{ $i * 10 }}" @selected($kensu === $i * 10)>{{ $i * 10 }}</option>
@endfor
</select></p></div>

<p><span class="attention">{{ $list['from'] + 1 }}件～{{ $list['last'] }}件目/{{ $list['count'] }}件中</span>

<p class="h2Title clearBoth">■来客一覧 ({{ $list['dispDate'] }})</p>

<table class="tableBox2" style="text-align:center;">
  <tr>
    <th>顧客ID</th>
    <th>管理番号</th>
    <th>氏名</th>
    <th>最新来店</th>
    <th>来店回数</th>
    <th></th>
  </tr>
@foreach ($list['rows'] as $n => $row)
  <tr @if ($n % 2 != 0) class="tableBg" @endif>
    <td>
    {{ $row->user_id }}
    </td>
    <td>{{ $row->kanri_id }}</td>
    <td>{{ $row->name }}</td>
    <td>{{ \App\Services\Promotions\VisitStatsService::date((int) $row->last_raiten)->format('Y/m/d H:i:s') }}</td>
    <td>{{ $row->count }}</td>
    <td><input type="button" class="btnStyle" value="詳細" onclick="javascript:window.open('/system/category1/inc/customer_detail.php?kaiin_cd={{ (int) $row->user_cd }}&wintype=no_headers', 'mywindow1', 'width=970, height=550, menubar=no, toolbar=no, scrollbars=yes');" /></td>
  </tr>
@endforeach
</table>

<ul class="btnBox2">
@if ($page != 1 && $kensu < $list['count'])
<li class="btn"><a href="#" onclick="ChangePage({{ $page - 1 }})"><img src="{{ asset('legacy/system/img/btn_back_off.gif') }}" width="92" height="32" alt="前へ" /></a></li>
@endif
@if ($list['maxPage'] > 1)
@for ($l = max(1, $page - 5); $l <= min($list['maxPage'], $page + 5); $l++)
@if ($l == $page)
<li class="now"><b>{{ $l }}</b></li>
@else
<li><a href="#" onclick="ChangePage({{ $l }})">{{ $l }}</a></li>
@endif
@endfor
@endif
@if ($page < $list['maxPage'])
<li class="btn"><a href="#" onclick="ChangePage({{ $page + 1 }})"><img src="{{ asset('legacy/system/img/btn_next_off.gif') }}" width="92" height="32" alt="次へ" /></a></li>
@endif
</ul>
</div>
@endif

@elseif ($submitted)
現在データはありません。
@endif


<!-- / .contents --></div>
@endsection
