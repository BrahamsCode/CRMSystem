{{-- Legacy: system/category3/reservation/reserve_customer_search.php (予約新規登録: 顧客一覧・検索).
     Variables:
       $searched       bool: hay búsqueda guardada (act=up o search_uniqid)
       $searchUniqid   id de la búsqueda en sesión (paginación y botón 予約)
       $kensu, $page   表示件数 y página
       $itemMaxNo      número de 項目 de tb_koumoku del buscador (item_max_no)
       $total          clientes encontrados
       $customers      clientes de la página (tb_customer ORDER BY touroku_date DESC): cd, id, kanri_id, name
       $siteName       nombre del sitio (<title>)
       + las de CustomerSearchForm::viewData() para partials/search_set1, search_custom_set1 y search_rireki_set1 --}}
@extends('layouts.legacy')

@section('menu', 'promocion')
@section('title', '顧客一覧・検索 - ' . $siteName)

@section('css')
<link rel="stylesheet" type="text/css" href="{{ asset('legacy/system/plugin/myflexselect/myFlexSelect.css') }}" />
@endsection

@section('js')
<script type="text/javascript" src="{{ asset('legacy/system/plugin/myflexselect/myFlexSelect.js') }}"></script>
<script type="text/javascript">

$(document).ready(function() {
	$(".ac5").accordion({ collapsible: true , active: false});
	$(".ac5").accordion('option', 'active', 0);
	window.scroll(0,0);

});

//表示件数の制御
function kensuSet(parts){
	document.form_search.kensu.value = parts.value;
	document.form_search.submit();
}

</script>
@endsection

@php
    $pageUrl = fn (int $p) => route('admin.reservas.simple.customer-search', ['page' => $p, 'kensu' => $kensu, 'search_uniqid' => $searchUniqid]);
    $maxPage = $kensu ? (int) ceil($total / $kensu) : 0;
@endphp

@section('content')
<div class="contents">
<p class="h2Title">■顧客検索</p>

<form method="post" name="form_search" id="form_search" class="form" action="{{ route('admin.reservas.simple.customer-search') }}">
@csrf

@include('admin.clientes.partials.search_set1')

@include('admin.clientes.partials.search_custom_set1')

@include('admin.clientes.partials.search_rireki_set1')

<div class="btnBox">
<input type="hidden" name="item_max_no" value="{{ $itemMaxNo }}" />
<input type="hidden" name="act" value="up" id="act" />
<input type="hidden" name="kensu" id="kensu" value="{{ $kensu }}" />
<input type="hidden" name="se_flg" value="1" />
<input type="hidden" name="search_uniqid" value="{{ $searchUniqid }}" />
<input type="submit" name="enter" id="enter" class="btn" value="この条件で検索する" />
</div>

</form>
<form method="post" class="form" action="{{ route('admin.reservas.simple.create') }}">
@csrf
<input type="submit" class="btn" value="非会員の予約" />
<input type="hidden" name="mitouroku" value="mitouroku" />
<input type="hidden" name="act" value="new" />
</form>


<!-- / .contents --></div>

@if ($searched)
<div class="contents">
<p class="h2Title">■顧客一覧</p>
@if ($total > 0)
@php
    $from = ($page - 1) * $kensu;
    $dispLastKen = min($from + $kensu, $total);
@endphp
<div id="form"><p class="maxListLength">表示件数<select name="kensu_act" id="kensu_act" class="selectBox" onchange="kensuSet(this)">
@for ($i = 1; $i <= 5; $i++)
<option value="{{ $i * 10 }}" @selected($kensu == $i * 10)>{{ $i * 10 }}</option>
@endfor
</select></p></div>

<p><span class="attention">{{ $from + 1 }}件～{{ $dispLastKen }}件目/{{ $total }}件中</span>
</p>

<div class="form">

<table class="tableBox2">
  <tr>
    <th style="width: 4em;"></th>
    <th style="width: 7em;">会員番号</th>
    <th style="width: 7em;">管理番号</th>
    <th>名前</th>
    <th style="width: 3em;">&nbsp;</th>
  </tr>
@foreach ($customers as $tbRow => $customer)
@if ($tbRow % 2 != 0)
  <tr class="tableBg">
@else
  <tr>
@endif
<td>{{ $from + 1 + $tbRow }}</td>
    <td>{{ $customer->id }}</td>
    <td>{{ $customer->kanri_id }}</td>

    <td>{{ $customer->name }}</td>


    <td align="center">
	<ul>
	<li><form method="post" name="customer_detail{{ $tbRow }}" id="customer_detail{{ $tbRow }}" action="{{ route('admin.reservas.simple.create') }}">
		@csrf
		<input type="submit" name="customer_detail" id="customer_detail" class="btn" value="予約" />
		<input type="hidden" name="kaiin_cd" id="kaiin_cd" value="{{ $customer->cd }}" />
		<input type="hidden" name="page" id="page" value="{{ $page }}" />
		<input type="hidden" name="kensu" id="kensu" value="{{ $kensu }}" />
		<input type="hidden" name="search_uniqid" value="{{ $searchUniqid }}" />
	</form></li>
    </td>

  </tr>
@endforeach
</table>

</div>

<ul class="btnBox2">
@if ($page != 1 && $kensu < $total)
<li class="btn"><a href="{{ $pageUrl($page - 1) }}"><img src="{{ asset('legacy/system/img/btn_back_off.gif') }}" width="92" height="32" alt="戻る" /></a></li>
@endif
@if ($maxPage > 1)
@for ($l = max(1, $page - 5); $l <= min($maxPage, $page + 5); $l++)
@if ($l == $page)
<li class="now"><b>{{ $l }}</b></li>
@else
<li><a href="{{ $pageUrl($l) }}">{{ $l }}</a></li>
@endif
@endfor
@endif
@if ($page < $maxPage)
<li class="btn"><a href="{{ $pageUrl($page + 1) }}"><img src="{{ asset('legacy/system/img/btn_next_off.gif') }}" width="92" height="32" alt="次へ" /></a></li>
@endif
</ul>

@else
該当する顧客がみつかりません。
@endif

<!-- / .contents --></div>
@endif
@endsection
