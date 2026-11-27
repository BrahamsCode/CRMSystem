{{-- Legacy: system/category1/customer/search_form_list.php (『カテゴリー』 検索フォーム部品設定一覧).
     Variables:
       $siteName    nombre del sitio (<title>)
       $category    CustomCategory (pcd); record_type '' muestra la columna メルマガ置き換え
       $items       campos de la página (CustomField, disp_flg = '1', ORDER BY rank)
       $total, $page, $kensu, $from, $maxPage   paginación como el legacy (10 por defecto)
       $kensuParam  kensu tal como llegó en la URL
       $menuItems   menús para la vista previa del tipo メニュー項目 (CustomerFormService::menuItems)
     Vista previa: partials/custom_field (createform_request) sin valores.
     Diferencias: el título lleva el nombre de la categoría (en el legacy PHP 8 salía 『』); ▲▼ por POST (#sort_row). --}}
@extends('layouts.legacy')

@section('menu', 'clientes')

@php
    $categoryName = html_entity_decode((string) $category->name, ENT_COMPAT | ENT_HTML401, 'UTF-8');
    $pageTitle = '『' . $categoryName . '』 検索フォーム部品設定';
    $listUrl = fn (int $p) => route('admin.clientes.custom-fields', [$category->cd, 'page' => $p, 'pcd' => $category->cd, 'kensu' => $kensu]);
    $count = count($items);
    $recordTypeNormal = (string) $category->record_type === '';
@endphp

@section('title', $pageTitle . '一覧 - ' . $siteName)

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

@section('content')
<a href="{{ route('admin.clientes.custom-fields.create', $category->cd) }}">{{ $pageTitle }}新規追加</a> / <a href="{{ route('admin.clientes.custom-fields', [$category->cd, 'page' => $page, 'kensu' => $kensu, 'se_flg' => 1, 'act' => 'up']) }}">{{ $pageTitle }}登録一覧</a>


<div class="contents">
<p class="h2Title">■{{ $pageTitle }}一覧</p>

@if ($total > 0)
<div id="form">
<form method="post" name="form_cnt" id="form_cnt" class="form" action="{{ route('admin.clientes.custom-fields', $category->cd) }}">
<p class="maxListLength">表示件数
<select name="kensu_act" id="kensu_act" class="selectBox" onchange="kensuSet(this)">
@for ($srow = 10; $srow < 60; $srow += 10)
<option value="{{ route('admin.clientes.custom-fields', [$category->cd, 'kensu' => $srow, 'pcd' => $category->cd, 'page' => $page]) }}"@if ($srow == $kensuParam) selected @endif>{{ $srow }}</option>
@endfor
</select>
</form>
</p>
</div>



<p><span class="attention">{{ $from + 1 }}件～{{ min($from + $kensu, $total) }}件目/{{ $total }}件中</span></p>

<div class="form">

<form method="post" name="update_row" id="update_row" class="form" action="{{ route('admin.clientes.custom-fields.flags', $category->cd) }}">
@csrf

<font color="#FF9F9F">■</font>…必須項目　<font color="#B3EFB3">■</font>…検索条件項目<!--　<font color="#FFFFCC">■</font>…CSV出力項目--><br />

<div class="tableBox2_wrap" style="display: block; width: 100%; overflow-x: auto;">
<style>
.tableBox2 th {
    max-width: 240px;
    white-space: nowrap;
}
</style>
<table class="tableBox2" style="width: auto;">
  <tr>
    <th>&nbsp;</th>
    <th>検索カテゴリー名</th>
    <th>フォームタイプ</th>
    <th>プレビュー</th>
    <th>必須</th>
    <th>検索</th>
@if ($recordTypeNormal)
    <th>メルマガ<br/>置き換え</th>
@endif
    <th>表示</th>
    <th>情報更新</th>
    <th>並び</th>
  </tr>
@foreach ($items as $row => $field)
@if ($row % 2 != 0)
  <tr class="tableBg">
@else
  <tr>
@endif
    <td>{{ $from + 1 + $row }}</td>
    {{-- name/unit/comment guardados con get_form2(): se imprimen tal cual, como el legacy --}}
    <td>{!! $field->name !!}</td>
    <td>{{ $field->form_type }}</td>
    <td >
@if ($field->form_type === 'テーブル')<div style="width: 400px;"><div style="width: 400px;overflow:auto;display: block;width: 100%;overflow-x: auto;">@endif
    	@include('admin.clientes.partials.custom_field', ['field' => $field->getAttributes(), 'values' => [], 'mode' => 'input', 'menuItems' => $menuItems])
@if ($field->form_type === 'テーブル')</div></div>@endif
@if ($field->unit)
@if ($field->form_type === '起算日')
<br />※検索は年数の範囲を指定します。（{!! $field->unit !!}）<br />
@elseif ($field->form_type !== 'テーブル')
{!! $field->unit !!}<br />
@endif
@endif
{!! nl2br((string) $field->comment) !!}</td>
    <td align="center" style="background-color:#FF9F9F">
<input type="checkbox" name="hissu{{ $row }}" value="1"@if ($field->hissu) checked @endif>
</td>
    <td align="center" style="background-color:#B3EFB3">
<input type="checkbox" name="serach_flg{{ $row }}" value="1"@if ($field->serach_flg) checked @endif>
</td>
@if ($recordTypeNormal)
	<td align="center">
@if ($field->form_type !== 'テーブル' && $field->form_type !== 'メニュー項目')
		<input type="checkbox" name="mail_word_flg{{ $row }}" value="1"@if ($field->mail_word_flg == '1') checked @endif>
@endif
	</td>
@endif
    <td align="center" style="background-color:#C2E4EF">
			<select name="hyouji_flg{{ $row }}" id="hyouji_flg{{ $row }}">
				<option value="2"@if ($field->hyouji_flg == '2') selected @endif>管理画面のみ</option>
				<option value="1"@if ($field->hyouji_flg == '1') selected @endif>管理・携帯 両画面</option>
				<option value=""@if ($field->hyouji_flg == '') selected @endif>非表示</option>
			</select>
</td>
    <td align="center">
	<ul>
	<li><a href="{{ route('admin.clientes.custom-fields.edit', [$field->cd, 'page' => $page, 'pcd' => $category->cd, 'kensu' => $kensu]) }}">
	<img src="{{ asset('legacy/system/img/icon_syosai.gif') }}" value="詳細" /></a>
	</li></td>
   <td>{{ $field->rank }}
<ul style="paddng:0px;margin:0px;float:right;line-height:13px;font-size:12px;width:1em">
@if ($row > 0 || $page != 1)
<li><a href="#" onclick="sortRow({{ $field->cd }}, {{ (int) $field->rank }}, 'up'); return false;">▲</a></li>
@else&nbsp;@endif
@if ($page != $maxPage || $row < $count - 1)
<li><a href="#" onclick="sortRow({{ $field->cd }}, {{ (int) $field->rank }}, 'down'); return false;">▼</a></li>
@endif
</ul>
</td>
<input type="hidden" name="cd{{ $row }}" value="{{ $field->cd }}">
  </tr>
@endforeach
</table>
</div>

<input type="hidden" name="act" value="update">
<input type="hidden" name="update_max" value="{{ $count }}">
<input type="hidden" name="pcd" value="{{ $category->cd }}">
<input type="hidden" name="page" value="{{ $page }}">
<input type="hidden" name="kensu" value="{{ $kensuParam }}">
<input type="submit" value="チェックした情報を更新する">
</form><!--update_row-->

</div>

@else
該当する情報がみつかりません。<br /><br />

<a href="{{ route('admin.clientes.custom-fields.create', $category->cd) }}">⇒新規追加する</a><br />
@endif



<ul class="btnBox2">
@include('admin.clientes.settings.pager', ['pageUrl' => $listUrl])
</ul>


<a href="{{ route('admin.clientes.custom-categories') }}">カテゴリ登録一覧へ戻る</a>

<!-- / .contents --></div>

{{-- ▲▼: el legacy recargaba la lista por GET con thisCd/thisRow/kind; aquí va por POST --}}
<form method="post" name="sort_row" id="sort_row" action="{{ route('admin.clientes.custom-fields.sort', $category->cd) }}" style="display:none;">
@csrf
<input type="hidden" name="thisCd" value="">
<input type="hidden" name="thisRow" value="">
<input type="hidden" name="kind" value="">
<input type="hidden" name="page" value="{{ $page }}">
<input type="hidden" name="kensu" value="{{ $kensuParam }}">
</form>
@endsection
