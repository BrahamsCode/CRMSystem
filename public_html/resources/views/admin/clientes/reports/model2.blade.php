{{-- Legacy: system/category1/shukei/model2.php (+ model2_search.php): 【<campo>】集計 de un campo numérico, ventana sin menú.
     Variables: $form (CustomField: cd, name, unit), $meta (CustomerReportService::applyVisitFilters), $blocks (list: key, value,
       stats [顧客数, 顧客延べ数, 合計, 平均, 最大, 最小] ya formateados),
       + las de partials/visit-search-form. La メール y el gráfico estaban comentados en el legacy. --}}
@extends('layouts.legacy-popup')

@section('title', 'トップ -総合業務管理システム-')

@section('css')
@include('admin.clientes.reports.partials.head')
@endsection

@section('content')
<div class="contents">

<div class="h2Title" style="font-size:120%;">【{{ $form->name }}】集計</div>

@include('admin.clientes.reports.partials.visit-search-form', ['formCd' => $form->cd])

<br/><br/>
<p class="h2Title">【{{ $form->name }}】{{ $meta['rirekiTitle'] . $meta['title'] . $meta['titleB'] }}集計</p>
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
<ul style="display: inline-block; *display: inline; *zoom: 1; vertical-align: middle;min-width:120px;">
@foreach ($block['stats'] as $label => $value)
<li style="font-size: 120%;">[{{ $label }}]<span style="margin-left: 3px; font-weight: bold;">{{ $value }}</span></li>
@endforeach
</ul>
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
