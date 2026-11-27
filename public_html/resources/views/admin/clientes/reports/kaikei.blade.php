{{-- Legacy: system/category1/shukei/point1.php y stamp1.php (+ *_search.php): 【会計金額】集計, ventana sin menú.
     Variables: $kind ('point' | 'stamp'), $meta (CustomerReportService::applyVisitFilters), $blocks (list: key, value,
       members, visits, kaikei [合計, 平均, 最大, 最小] ya formateados en 円),
       + las de partials/visit-search-form. 差引会計 y 消費ポイント/スタンプ estaban comentados en el legacy: solo sale 会計. --}}
@extends('layouts.legacy-popup')

@section('title', 'トップ -総合業務管理システム-')

@section('css')
@include('admin.clientes.reports.partials.head')
@if ($kind === 'stamp')
<link href="/system/css/import.css" rel="stylesheet" type="text/css" media="all" />
@endif
@endsection

@section('content')
<div class="contents">

<div class="h2Title" style="font-size:120%;">【会計金額】集計</div>

@include('admin.clientes.reports.partials.visit-search-form', ['formCd' => null])

<br/><br/>
※本集計は、カルテツールにより入力されたデータを元に算出しています。
<p class="h2Title">【会計金額】{{ $meta['rirekiTitle'] . $meta['title'] . $meta['titleB'] }}集計</p>
@if (count($blocks) > 0)
@foreach ($blocks as $block)
<table class="tableBox8">
<tr>
@if ((string) ($in['cond'] ?? '') !== '')<th rowspan="2" style="width:150px;">{{ $block['key'] }}</th>@endif
<th>
@include('admin.clientes.reports.partials.type-links')

</th>
</tr>

<tr>
<td>
<div>
<p>
顧客数：<span style="margin-left: 3px; font-weight: bold;">{{ $block['members'] }}</span><br/>
顧客延べ数：<span style="margin-left: 3px; font-weight: bold;">{{ $block['visits'] }}</span>
</p>
<table style="text-align:left;margin:0px;width:100%">
<tr>
<td>
<ul style="display: inline-block; *display: inline; *zoom: 1; vertical-align: middle;min-width:120px;">
<li><span style="font-weight: bold;">会計</span></li>
@foreach ($block['kaikei'] as $label => $value)
<li style="font-size: 120%;">[{{ $label }}]<span style="margin-left: 3px; font-weight: bold;">{{ $value }}</span></li>
@endforeach
</ul>
</td>
</tr>
</table>
</div>

</td>
</tr>
</table>
@endforeach
@include('admin.clientes.reports.partials.mail-form')
@else
該当データがありません。
@endif

<!-- / .contents --></div>
@endsection
