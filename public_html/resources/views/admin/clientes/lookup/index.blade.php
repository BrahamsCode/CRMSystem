{{-- Legacy: system/category1/customer/karute_customer_search.php (顧客検索: selector de cliente para la カルテ).
     Variables:
       $searched       bool: hay búsqueda (act=up)
       $searchUniqid   id de la búsqueda guardada en sesión (paginación)
       $kensu, $page   表示件数 y página actual
       $itemMaxNo      número de 項目 de tb_koumoku del buscador (item_max_no)
       $total          clientes encontrados
       $customers      clientes de la página (tb_customer ORDER BY touroku_date DESC): cd, id, name, kojin_birth, zip, add, tel,
                       email1, mailmaga_flg, error_send_cnt
       $formContent    filtros de la búsqueda (escalares) que el legacy reenviaba en campos ocultos ($form_content)
       + las de partials/search_set1: $searchItems, $filters, $hiddenSearchMenus, $cellTag, $isMaster, $shopCd, $shopName, $shops,
         $areaCheckUi, $jobs, $customerGroups, $raitenDoukis, $terminalNos, $staffs, $raitenRanks, $kingakuRanks,
         $nameInputModeFlg, $applicationUseFlg --}}
@extends('layouts.legacy')

@section('menu', 'clientes')
@section('title', '顧客一覧・検索 - ' . app(\App\Services\Nucleo\LegacyUi::class)->siteName())

@section('css')
<link rel="stylesheet" type="text/css" href="{{ asset('legacy/system/plugin/myflexselect/myFlexSelect.css') }}" />
@endsection

@section('js')
<script type="text/javascript" src="{{ asset('legacy/system/plugin/myflexselect/myFlexSelect.js') }}"></script>


<script type="text/javascript">
$(document).ready(function() {
	$(".ac1").accordion({ collapsible: true , active: false});
	$(".ac2").accordion({ collapsible: true , active: false});
	$(".ac3").accordion({ collapsible: true , active: false});
	$(".ac4").accordion({ collapsible: true , active: false});
	$(".ac5").accordion({ collapsible: true , active: false});
	$(".ac6").accordion({ collapsible: true , active: false});
	$(".ac7").accordion({ collapsible: true , active: false});
	$(".ac8").accordion({ collapsible: true , active: false});

	$(".ac4").accordion('option', 'active', 0);


});
</script>


<script type="text/javascript">


//表示件数の制御

function kensuSet(parts){
	document.form_search.kensu.value = parts.value;
	document.form_search.submit();
}



</script>
@endsection

@php
    $pageUrl = fn (int $p) => route('admin.clientes.lookup', ['page' => $p, 'kensu' => $kensu, 'se_flg' => 1, 'act' => 'up', 'search_uniqid' => $searchUniqid]);
    $maxPage = $kensu ? (int) ceil($total / $kensu) : 0;
@endphp

@section('content')
<div class="contents">
<p class="h2Title">■顧客検索</p>

<form method="post" name="form_search" id="form_search" class="form" action="{{ route('admin.clientes.lookup') }}">
@csrf

@include('admin.clientes.partials.search_set1')

<div class="btnBox">
<input type="hidden" name="item_max_no" value="{{ $itemMaxNo }}" />
<input type="hidden" name="act" value="up" id="act" />
<input type="hidden" name="kensu" id="kensu" value="{{ $kensu }}" />
<input type="hidden" name="se_flg" value="1" />
<input type="hidden" name="search_uniqid" value="{{ $searchUniqid }}" />
<input type="submit" name="enter" id="enter" class="btn" value="この条件で検索する" />
</div>

</form>

@if ($searched)
<div class="btnBox">

<table class="tableBox7">
<thead>
<tr>
<th colspan="10"><span class="text3L attention">{{ $total }}</span>件見つかりました。

</th>
</tr>
</thead>

</table>

</div>


<!-- / .contents --></div>


<div class="contents">
<p class="h2Title">■顧客一覧</p>

@if (count($customers) > 0)
@php
    $from = ($page - 1) * $kensu;
    $dispMaxKensu = min($from + $kensu, $total);
@endphp
<div id="form"><p class="maxListLength">表示件数<select name="kensu_act" id="kensu_act" class="selectBox" onchange="kensuSet(this)">
@for ($i = 1; $i <= 5; $i++)
<option value="{{ $i * 10 }}" @selected($kensu == $i * 10)>{{ $i * 10 }}</option>
@endfor
</select></p></div>

<p><span class="attention">{{ $from + 1 }}件～{{ $dispMaxKensu }}件目/{{ $total }}件中</span></p>

<div class="form">

<table class="tableBox2">
  <tr>
    <th></th>
    <th>ID</th>
    <th>名前<br />生年月日</th>
    <th>郵便番号<br />住所</th>
    <th>電話番号<br />メールアドレス</th>
    <th>メールマガジン配信有無</th>
    <th>&nbsp;</th>
  </tr>
@foreach ($customers as $kaiinRow => $kaiin)
@if ($kaiinRow % 2 != 0)
  <tr class="tableBg">
@else
  <tr>
@endif
<td>{{ $from + 1 + $kaiinRow }}</td>
    <td>{{ $kaiin->id }}</td>

    <td>{{ $kaiin->name }}<br />
    @if ($kaiin->kojin_birth)<small>{{ $kaiin->kojin_birth }}</small>@else---@endif</td>

    <td>{{ $kaiin->zip }}<br />{{ $kaiin->add }}</td>

    <td>@if ($kaiin->tel){{ $kaiin->tel }}@else---@endif<br />
    {{ explode('@', (string) $kaiin->email1)[0] }}@...</td>

    <td>{{ $kaiin->mailmaga_flg == '1' ? '○' : '×' }}<br />送信エラー：{{ $kaiin->error_send_cnt }}回</td>

    <td align="center">
	<ul>
	@php
		// karute_new.php: alta de カルテ del cliente (admin.clientes.kartes.create, GET); sin ella, la URL legacy por POST
		$karuteNew = Route::has('admin.clientes.kartes.create');
	@endphp
	<li><form method="{{ $karuteNew ? 'get' : 'post' }}" name="customer_detail{{ $kaiinRow }}" id="customer_detail{{ $kaiinRow }}" action="{{ $karuteNew ? route('admin.clientes.kartes.create', $kaiin->cd) : url('system/category1/customer/karute_new.php') }}">
		@unless ($karuteNew) @csrf @endunless
		<input type="image" name="customer_detail" id="customer_detail" src="{{ asset('legacy/system/img/icon_syosai.gif') }}" value="詳細" />
		{{-- 検索結果をinputのhiddenで格納しているものを受け渡し --}}
		@foreach ($formContent as $key => $val)
		<input type="hidden" name="{{ $key }}" value="{{ $val }}">
		@endforeach
		<input type="hidden" name="kaiin_cd" id="kaiin_cd" value="{{ $kaiin->cd }}" />
		<input type="hidden" name="page" id="page" value="{{ $page }}" />
		<input type="hidden" name="kensu" id="kensu" value="{{ $kensu }}" />

	</form></li></td>
  </tr>
@endforeach
</table>

</div>



@else
該当する顧客がみつかりません。
@endif



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

@endif

<!-- / .contents --></div>
@endsection
