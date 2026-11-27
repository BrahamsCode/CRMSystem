{{-- Legacy: system/category1/shukei/customerDataOutPut.php (csv出力管理画面, ventana emergente de 集計ツール).
     Variables:
       $total             visitas que entran en la búsqueda (0 si no se acaba de buscar, como el legacy)
       $limits            opciones de 件づつ (100, 250, 500, 1000, 2500, 5000)
       $csvLimit          tamaño de bloque elegido; $csvLimitSelected: llegó csv_limit en la petición
       $viewPage          número de botones de bloque ((int)(total / límite) + 1; 0 sin búsqueda)
       + las de CustomerSearchForm::viewData() para partials/search_set1 y partials/search_custom_set1
         ($filters = búsqueda guardada en sesión, $searchItems, $shops, $customSearchCategories…)
--}}
@extends('layouts.legacy-popup')

@section('title', 'トップ -総合業務管理システム-')

@section('css')
<link href="{{ asset('legacy/system/category3/css/colorbox.css') }}" rel="stylesheet" type="text/css" media="all" />
<link rel="stylesheet" type="text/css" href="{{ asset('legacy/system/plugin/ui-datepicker/ui.datepicker.css') }}" />
<link rel="stylesheet" type="text/css" href="{{ asset('legacy/system/plugin/myflexselect/myFlexSelect.css') }}" />
<style type="text/css"><!--

.main1 {
	background: none;
}

.formButtonWrap {
	margin: 10px;
}

.formButtonWrap form{
	display: inline;
}

.tableBox table {
	margin: 0;
	margin-bottom: 15px;
}

--></style>
@endsection

@section('js')
<script type="text/javascript" src="{{ asset('legacy/system/category3/js/jquery.colorbox.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/plugin/ui-datepicker/ui.datepicker-ja.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/plugin/ui-datepicker/gcalendar-holidays.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/plugin/myflexselect/myFlexSelect.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/js/scrollsmoothly.js') }}"></script>
@endsection

@section('content')
<div class="contents">
<div class="h2Title" style="font-size:120%;">csv出力管理画面 ( 来店履歴抽出数 : {{ $total }}件 )<br />
※顧客項目表示数やカルテの数により、ダウンロードに時間がかかる場合がございます。
　その場合は、分割してのダウンロードをおすすめいたします。</div>

<table class="tableBox">
<tr>
<th>csv出力</th>
<td>

<div class="formButtonWrap">

<form action="{{ route('admin.clientes.reports.visit-csv') }}" method="post" class="form">
@csrf
<input type="hidden" name="mode" value="search" />

@include('admin.clientes.partials.search_set1')

@include('admin.clientes.partials.search_custom_set1')

<br />

<select name="csv_limit">
@foreach ($limits as $limit)
<option value="{{ $limit }}" @selected($csvLimitSelected && $csvLimit === $limit)>{{ $limit }}</option>
@endforeach
</select>　件づつCSVファイルに出力する。<br /><br />
<input type="submit" id="enter" class="btn btnStyleS" value="検索" style="height: 32px;" />
</form>

<form action="{{ route('admin.clientes.reports.visit-csv') }}" method="post">
@csrf
<input type="hidden" name="mode" value="search_clear" />
<input type="submit" id="enter" class="btn btnStyleS" value="検索条件クリア" style="height: 32px;" />
</form>

<form action="{{ route('admin.clientes.reports.visit-csv.download') }}" method="post" style="display:none">
@csrf
<input type="hidden" name="mode" value="csv" />
<input type="submit" name="enter" id="enter" class="btn btnStyleS" value="{{ $total }}件(全件)をcsvファイルで出力" style="height: 32px;" />
</form>

<br />
<br />
<br />
<br />

@if ($viewPage && $total)
<div style="border: 1px solid #CCCCCC;
background: #EFEFEF;
text-align: center;
font-size: 22px;
font-weight: bold;
margin-bottom: 27px;">検索結果 : CSVファイル取得ボタン</div>
@endif
@for ($i = 0; $i < $viewPage; $i++)
@php
    $startStr = $csvLimit * $i + 1;
    $endStr = min($csvLimit * ($i + 1), $total);
@endphp
@if ($endStr >= $startStr)
<div style="float: left; margin: 0 10px 10px 0;width: 230px;">
<form action="{{ route('admin.clientes.reports.visit-csv.download') }}" method="post">
@csrf
<input type="hidden" name="mode" value="csv" />
<input type="hidden" name="page" value="{{ $i }}" />
<input type="hidden" name="view" value="{{ $csvLimit }}" />
<input type="submit" name="enter" id="enter" class="btn btnStyleS" value="{{ $startStr }}件～{{ $endStr }}件をcsvファイルで出力" style="height: 32px;" />
</form>
</div>
@endif
@endfor

</div>

</td>
</tr>
</table>

<!-- / .contents --></div>
@endsection
