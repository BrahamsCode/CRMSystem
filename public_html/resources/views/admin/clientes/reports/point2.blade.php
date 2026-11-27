{{-- Legacy: system/category1/shukei/point2.php (+ point2_search.php): 【ポイント】集計 por tienda y periodo, ventana sin menú.
     Variables: $in (parámetros GET), $shops (cd => kaishamei), $isMaster, $staffShopCd, $staffShopName, $selfUrl,
       $minYear (int: primer año de tb_point_log), $kikanSent (bool: vino kikan_type),
       $result (CustomerReportService::point2: rirekiTitle, rirekiType, th, unit, min, max,
         shops [nombre => [periods [periodo 2 cifras o año => members, visits, given [合計, 最大, 最小], used [...]], total [...]]]).
     El legacy imprimía el aviso de error de mes/año, que nunca salía (siempre pone el actual): se omite. --}}
@extends('layouts.legacy-popup')

@section('title', 'トップ -総合業務管理システム-')

@section('css')
@include('admin.clientes.reports.partials.head')
<link rel="stylesheet" type="text/css" href="/system/plugin/ui-datepicker/ui.datepicker.css" />
@endsection

@section('js')
<script type="text/javascript" src="/system/plugin/ui-datepicker/ui.datepicker-ja.js"></script>
<script type="text/javascript" src="/system/plugin/ui-datepicker/gcalendar-holidays.js"></script>
<script type="text/javascript">
$(document).ready(function() {

	// 日別・月別・年度別の入力切替 ============================================================

	$("input[name='kikan_type']").change(kikanTypeChange);

	function kikanTypeChange() {

		var val = $("input[name='kikan_type']:checked").val();

		$("#kikan_type_input1,#kikan_type_input2,#kikan_type_input3").hide();
		$("#kikan_type_input"+val).show();

	}

	kikanTypeChange();

});
</script>
@endsection

@php
	$kikan = (string) ($in['kikan_type'] ?? '');
	$sel = fn (string $name, int $value) => (string) ($in[$name] ?? '') !== '' && (int) $in[$name] === $value ? ' selected' : '';
	$years = range((int) now('Asia/Tokyo')->format('Y'), $minYear);
	$gray = fn (string $v) => '<span style="color:#999999;">' . $v . '</span>';
@endphp

@section('content')
<div class="contents">

<div class="h2Title mB10" style="font-size:120%;">【ポイント】集計</div>

<form method="get" name="form_search" id="form_search" action="{{ $selfUrl }}">
	<table class="tableBox" style="margin:0;width:500px;">

	<tr>
	<th>集計店舗</th>
	<td>
		@if ($isMaster)
		<select name="touroku_shop_cd" @if (count($shops) < 2) class="displayNone" @endif>
		<option value="">▼店舗選択</option>
		@foreach ($shops as $cd => $name)
		<option value="{{ $cd }}"{{ $sel('touroku_shop_cd', (int) $cd) }}>{{ $name }}</option>
		@endforeach
		</select>
		@else
		{{ $staffShopName }}
		<input type="hidden" name="touroku_shop_cd" value="{{ $staffShopCd }}">
		@endif
	</td>
	</tr>

	<tr>
	<th>集計期間</th>
	<td>

		<div style="overflow: hidden;"><!--
		--><input type="radio" name="kikan_type" id="kikan_type1" value="1"@if ($kikan === '' || $kikan === '1') checked="checked"@endif /><label for="kikan_type1">&nbsp;日別&emsp;</label><!--
		--><input type="radio" name="kikan_type" id="kikan_type2" value="2"@if ($kikan === '2') checked="checked"@endif /><label for="kikan_type2">&nbsp;月別&emsp;</label><!--
		--><input type="radio" name="kikan_type" id="kikan_type3" value="3"@if ($kikan === '3') checked="checked"@endif /><label for="kikan_type3">&nbsp;年度別&emsp;</label><!--
		--></div>

		<div id="kikan_type_input1" class="pT10" style="display:none;">
		<div class="pB10">指定月のポイント集計を日別に表示します。</div>
		<select name="type_m_year"><option value="">&nbsp;</option>@foreach ($years as $y)<option value="{{ $y }}"{{ $sel('type_m_year', $y) }}>{{ $y }}</option>@endforeach</select>&nbsp;年&nbsp;
		<select name="type_m_month"><option value="">&nbsp;</option>@for ($i = 1; $i <= 12; $i++)<option value="{{ sprintf('%02d', $i) }}"{{ $sel('type_m_month', $i) }}>{{ sprintf('%02d', $i) }}</option>@endfor</select>&nbsp;月
		</div>

		<div id="kikan_type_input2" class="pT10" style="display:none;">
		<div class="pB10">指定年度のポイント集計を月別に表示します。</div>
		<select name="type_y_year"><option value="">&nbsp;</option>@foreach ($years as $y)<option value="{{ $y }}"{{ $sel('type_y_year', $y) }}>{{ $y }}</option>@endforeach</select>&nbsp;年&nbsp;
		</div>

		<div id="kikan_type_input3" class="pT10" style="display:none;">
		<div>年度別のポイント集計を表示します。</div>
		</div>

	</td>
	</tr>

	<tr>
	<td colspan="2" style="text-align:center;">
		<input type="submit" value="　　表示　　" />
	</td>
	</tr>
	</table>
</form>

<br/><br/>
<p class="h2Title">【ポイント】&nbsp;{{ $result['rirekiTitle'] }}の集計結果&emsp;{{ $result['rirekiType'] }}</p>
@if (count($result['shops']) > 0)
@foreach ($result['shops'] as $shopName => $shop)
<table class="tableBox8">

<tr>
<th>{{ $shopName }}</th>
</tr>

<tr>
<td>
	<div>
	<table style="text-align:left;margin:0px;width:100%">

	<tr>
	<th rowspan="2">{{ $result['th'] }}</th>
	<th rowspan="2" style="width:13%;">顧客数</th>
	<th rowspan="2" style="width:13%;">顧客延べ数</th>
	<th colspan="3" style="width:30%;">ポイント付与数</th>
	<th colspan="3" style="width:30%;">ポイント還元数</th>
	</tr>

	<tr>
	<th>合計</th>
	<th>最大</th>
	<th>最小</th>
	<th>合計</th>
	<th>最大</th>
	<th>最小</th>
	</tr>

@for ($i = $result['min']; $i <= $result['max']; $i++)
@php $p = $shop['periods'][sprintf('%02d', $i)] ?? null; @endphp
	<tr>
	<th>{{ $i }}{{ $result['unit'] }}</th>
@if ($p === null)
	<td>{!! $gray('0人') !!}</td>
	<td>{!! $gray('0人') !!}</td>
	<td style="background:#F9F3E3;">{!! $gray('0') !!}</td>
	<td>{!! $gray('0') !!}</td>
	<td>{!! $gray('0') !!}</td>
	<td style="background:#F9F3E3;">{!! $gray('0') !!}</td>
	<td>{!! $gray('0') !!}</td>
	<td>{!! $gray('0') !!}</td>
@else
	<td>{{ number_format($p['members']) }}人</td>
	<td>{{ number_format($p['visits']) }}人</td>
	<td style="background:#F9F3E3;">{{ number_format($p['given'][0]) }}</td>
	<td>{{ number_format($p['given'][1]) }}</td>
	<td>{{ number_format($p['given'][2]) }}</td>
	<td style="background:#F9F3E3;">{{ number_format($p['used'][0]) }}</td>
	<td>{{ number_format($p['used'][1]) }}</td>
	<td>{{ number_format($p['used'][2]) }}</td>
@endif
	</tr>
@endfor
	<tr>
	<th style="bold;border-top:2px solid #CCCCCC;">&nbsp;</th>
@foreach ($shop['total'] as $totalKey => $value)
	<td style="color:#113366;font-weight:bold;border-top:2px solid #CCCCCC;{{ in_array($totalKey, ['付与合計', '還元合計'], true) ? 'background:#F9F3E3;' : '' }}">
	{{ number_format($value) }}{{ in_array($totalKey, ['顧客数', '顧客延べ数'], true) ? '人' : '' }}
	</td>
@endforeach
	</tr>

	</table>
	</div>

</td>
</tr>
</table>
@endforeach
@else
{{ $kikanSent ? '該当データがありません。' : '集計の方法と期間を指定してください。' }}
@endif

<!-- / .contents --></div>
@endsection
