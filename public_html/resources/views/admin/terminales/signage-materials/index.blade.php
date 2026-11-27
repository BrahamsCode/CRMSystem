{{--
    放映素材 一覧 (legacy: system/category7/digital_signage/screen_saver_list.php).

    Contrato de datos:
      $materials  LengthAwarePaginator<SignageMaterial>  tb_screensaver con disp_flg = '1' y
                  tb_display_cd = $displayCd (si hay), ORDER BY rank; perPage = $kensu.
                  Columnas usadas: cd, title, comment, filetype, rank.
      $displays   Collection<SignageDisplay>  tb_display con disp_flg = '1' ORDER BY rank (cd, name)
      $displayCd  int|null  display elegido; si no llega, el primero de $displays (como el legacy)
      $kensu      int       filas por página (10 por defecto)
      $siteName   string    (compartida por el núcleo) nombre del sitio para el <title>

    Request leído directamente (como el legacy con $_REQUEST): page, kensu (para los ▲▼).

    Rutas:
      GET|POST admin.terminales.signage-materials.index   (form_search y form1 envían por POST)
      GET      admin.terminales.signage-materials.sort    (▲▼: thisCd, thisRow, kind=up|down, page, kensu, display_cd)
      GET      admin.terminales.signage-materials.show    ({cd} + page, kensu, display_cd)

    Nota: el legacy usa aquí $title = 放映プログラム管理 (no 放映ファイル管理); se conserva el texto.
--}}
@extends('layouts.legacy')

@section('menu', 'terminales')

@section('title', '放映プログラム管理  - ' . ($siteName ?? ''))

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac1").accordion('option', 'active', 0);
});

//表示件数の制御

function kensuSet(parts){
	document.form_search.kensu.value = parts.value;
	document.form_search.submit();
}

</script>
@endsection

@section('content')
@php
    $page = $materials->currentPage();
    $maxPage = $materials->lastPage();
    $rowCount = $materials->count();
@endphp
@include('admin.terminales.partials.sub-navi', [
    'family' => 'materials',
    'createQuery' => ['tb_display_cd' => (int) $displayCd],
    'listQuery' => ['page' => '', 'kensu' => (int) request('kensu'), 'display_cd' => (int) $displayCd],
])


<form method="post" name="form_search" id="form_search" class="displayNone" action="{{ route('admin.terminales.signage-materials.index') }}">
@csrf
<input type="hidden" name="kensu" id="kensu" value="{{ $kensu }}" />
<input type="hidden" name="display_cd" id="display_cd" value="{{ $displayCd }}" />
<input type="hidden" name="page" id="page" value="1" />
</form>


<div class="contents">
<p class="h2Title">■放映プログラム管理 抽出</p>

<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.terminales.signage-materials.index') }}">
@csrf

<input type="hidden" name="kensu" id="kensu" value="{{ $kensu }}" />
<select name="display_cd">
@foreach ($displays as $display)
<option value="{{ $display->cd }}"{{ $displayCd == $display->cd ? ' selected' : '' }}>{{ $display->name }}</option>
@endforeach
</select>
<input type="submit" name="submit" value="表示" />

</form>
<br/>
<p class="h2Title">■放映プログラム管理 </p>

@if ($rowCount > 0)
@include('admin.terminales.partials.kensu-select', ['kensu' => $kensu])

<p><span class="attention">{{ $materials->total() }}件</span>の該当結果がありました。</p>

<table class="tableBox2">
  <tr>
    <!--<th>ID</th>-->
    <th width="5%">ID</th>
    <th width="30%">タイトル</th>
    <th width="35%">内容</th>
    <th width="10%" class="btn3">タイプ</th>
    <th width="10%" class="btn3">&nbsp;</th>
    <th width="10%" class="btn3">並び</th>
  </tr>

@foreach ($materials as $material)
@if ($loop->index % 2 != 0)
  <tr class="tableBg">
@else
  <tr>
@endif

    <td>{{ $material->cd }}</td>
    <td>{{ $material->title }}</td>
    <td>{{ $material->comment }}</td>
    <td>{{ $material->filetype }}</td>

    <td align="center">
	<ul>
	<li><a href="{{ route('admin.terminales.signage-materials.show', [$material->cd, 'page' => $page, 'kensu' => $kensu, 'display_cd' => $displayCd]) }}"><img src="{{ asset('legacy/system/img/icon_syosai.gif') }}" width="40" height="40" alt="詳細" /></a></li>
	</ul>
    </td>


    <td>{{ $material->rank }}
<ul style="paddng:0px;margin:0px;float:right;line-height:13px;font-size:12px">
@if ($loop->index > 0 || $page != 1)
<li><a href="#" onclick="location.href='{{ route('admin.terminales.signage-materials.sort', ['page' => request('page'), 'kensu' => request('kensu'), 'thisCd' => $material->cd, 'thisRow' => $material->rank, 'kind' => 'up', 'display_cd' => $displayCd]) }}'">▲</a></li>
@else
&nbsp;
@endif

@if ($page != $maxPage || $loop->index < $rowCount - 1)
<li><a href="#" onclick="location.href='{{ route('admin.terminales.signage-materials.sort', ['page' => request('page'), 'kensu' => request('kensu'), 'thisCd' => $material->cd, 'thisRow' => $material->rank, 'kind' => 'down', 'display_cd' => $displayCd]) }}'">▼</a></li>
@endif
</ul>
</td>


  </tr>
@endforeach
</table>

<ul class="btnBox2">
@include('admin.terminales.partials.pagination', ['paginator' => $materials, 'baseUrl' => route('admin.terminales.signage-materials.index')])
</ul>

@else
放映プログラム管理データはありません。
@endif

<!-- / .contents --></div>
@endsection
