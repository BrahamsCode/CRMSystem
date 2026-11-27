{{-- Legacy: system/category1/customer/search_cate_list.php (顧客追加情報カテゴリ設定一覧).
     Variables:
       $siteName    nombre del sitio (<title>)
       $items       categorías de la página (CustomCategory, category1, disp_flg <> '9', ORDER BY rank)
       $total       categorías en total ($main_table_sum_max)
       $page        página actual
       $kensu       表示件数 (10 por defecto)
       $kensuParam  kensu tal como llegó en la URL (el legacy marca el <option> y lo reenvía solo si venía)
       $from        desplazamiento (page - 1) * kensu
       $maxPage     nº de páginas
     Diferencia: ▲▼ envían un POST (formulario oculto #sort_row) en vez de recargar la lista por GET. --}}
@extends('layouts.legacy')

@section('menu', 'clientes')
@section('title', '顧客追加情報カテゴリ設定一覧 - ' . $siteName)

@section('js')
<script type="text/javascript">
//表示件数
function kensuSet(parts){
location=parts.options[parts.selectedIndex].value;
}

// 並び順入れ替え (▲▼)
function sortRow(cd, rank, kind){
	var f = document.getElementById('sort_row');
	f.thisCd.value = cd;
	f.thisRow.value = rank;
	f.kind.value = kind;
	f.submit();
}
</script>
@endsection

@php
    $pageTitle = '顧客追加情報カテゴリ設定';
    $listUrl = fn (int $p) => route('admin.clientes.custom-categories', ['page' => $p, 'pcd' => '', 'kensu' => $kensu]);
    $count = count($items);
@endphp

@section('content')
<a href="{{ route('admin.clientes.custom-categories.create') }}">{{ $pageTitle }}新規追加</a> / <a href="{{ route('admin.clientes.custom-categories', ['page' => $page, 'kensu' => $kensu, 'se_flg' => 1, 'act' => 'up']) }}">{{ $pageTitle }}登録一覧</a>


<div class="contents">
<p class="h2Title">■{{ $pageTitle }}一覧</p>

@if ($total > 0)
<div id="form">
<form method="post" name="form_cnt" id="form_cnt" class="form" action="{{ route('admin.clientes.custom-categories') }}">
<p class="maxListLength">表示件数
<select name="kensu_act" id="kensu_act" class="selectBox" onchange="kensuSet(this)">
@for ($srow = 10; $srow < 60; $srow += 10)
<option value="{{ route('admin.clientes.custom-categories', ['kensu' => $srow, 'pcd' => '', 'page' => $page]) }}"@if ($srow == $kensuParam) selected @endif>{{ $srow }}</option>
@endfor
</select>
</form>
</p>
</div>



<p><span class="attention">{{ $from + 1 }}件～{{ min($from + $kensu, $total) }}件目/{{ $total }}件中</span></p>

<div class="form">

<form method="post" name="update_row" id="update_row" class="form" action="{{ route('admin.clientes.custom-categories.flags') }}">
@csrf


<table class="tableBox2">
  <tr>
    <th>&nbsp;</th>
    <th>カテゴリー名</th>
    <th>追加フォーム</th>
    <th>情報更新</th>
    <th>検索</th>
    <th>表示</th>
    <th>並び</th>

  </tr>
@foreach ($items as $row => $category)
@if ($row % 2 != 0)
  <tr class="tableBg">
@else
  <tr>
@endif

    <td>{{ $from + 1 + $row }}</td>
    {{-- name guardado con get_form2(): se imprime tal cual, como el legacy --}}
    <td>{!! $category->name !!}</td>




    <td align="center">
	<ul>
	<li><a href="{{ route('admin.clientes.custom-fields', $category->cd) }}">
	<img src="{{ asset('legacy/system/img/icon_syosai.gif') }}" value="詳細" /></a>
	</li></td>



    <td align="center">
	<ul>
	<li><a href="{{ route('admin.clientes.custom-categories.edit', [$category->cd, 'page' => $page, 'kensu' => $kensu]) }}">
	<img src="{{ asset('legacy/system/img/icon_syosai.gif') }}" value="詳細" /></a>
	</li></td>



    <td align="center" style="background-color:#B3EFB3">
<input type="checkbox" name="serach_flg{{ $row }}" value="1"@if ($category->serach_flg) checked @endif>
</td>


    <td align="center" style="background-color:#C2E4EF">
<input type="checkbox" name="hyouji_flg{{ $row }}" value="1"@if ($category->hyouji_flg) checked @endif>
</td>


   <td>{{ $category->rank }}
<ul style="paddng:0px;margin:0px;float:right;line-height:13px;font-size:12px;width:1em">
@if ($row > 0 || $page != 1)
<li><a href="#" onclick="sortRow({{ $category->cd }}, {{ (int) $category->rank }}, 'up'); return false;">▲</a></li>
@else&nbsp;@endif

@if ($page != $maxPage || $row < $count - 1)
<li><a href="#" onclick="sortRow({{ $category->cd }}, {{ (int) $category->rank }}, 'down'); return false;">▼</a></li>
@endif
</ul>

</td>

  </tr>

<input type="hidden" name="cd{{ $row }}" value="{{ $category->cd }}">

@endforeach
</table>

<input type="hidden" name="act" value="update">
<input type="hidden" name="update_max" value="{{ $count }}">
<input type="hidden" name="page" value="{{ $page }}">
<input type="hidden" name="kensu" value="{{ $kensuParam }}">
<input type="submit" value="チェックした情報を更新する">
</form><!--update_row-->

</div>

@else
該当する情報がみつかりません。<br /><br />

<a href="{{ route('admin.clientes.custom-categories.create') }}">⇒新規追加する</a><br />
@endif



<ul class="btnBox2">
@include('admin.clientes.settings.pager', ['pageUrl' => $listUrl])
</ul>


<!-- / .contents --></div>

{{-- ▲▼: el legacy recargaba la lista por GET con thisCd/thisRow/kind; aquí va por POST --}}
<form method="post" name="sort_row" id="sort_row" action="{{ route('admin.clientes.custom-categories.sort') }}" style="display:none;">
@csrf
<input type="hidden" name="thisCd" value="">
<input type="hidden" name="thisRow" value="">
<input type="hidden" name="kind" value="">
<input type="hidden" name="page" value="{{ $page }}">
<input type="hidden" name="kensu" value="{{ $kensuParam }}">
</form>
@endsection
