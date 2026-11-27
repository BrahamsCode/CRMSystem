{{--
    ディスプレイ 一覧 (legacy: system/category7/digital_signage/display_list.php).

    Contrato de datos:
      $displays  LengthAwarePaginator<SignageDisplay>  tb_display con disp_flg = '1' (filtrado por tienda)
                 ORDER BY rank; perPage = $kensu. Columnas usadas: cd, name, comment, rank,
                 programName  string|null  name del tb_c7_movie_program de tb_c7_movie_program_cds
                                           (disp_flg <> '9'). El legacy deja la celda vacía (pg_fetch_result
                                           con fila '' ya devolvía NULL en PHP 7): pasar null para igualarlo.
      $kensu     int     filas por página (10 por defecto)
      $siteName  string  (compartida por el núcleo) nombre del sitio para el <title>

    Request leído directamente (como el legacy con $_REQUEST): page, kensu (para los ▲▼).

    Rutas:
      GET|POST admin.terminales.signage-displays.index  (form_search envía por POST)
      GET      admin.terminales.signage-displays.sort   (▲▼: thisCd, thisRow, kind=up|down, page, kensu)
      GET      admin.terminales.signage-displays.show   ({cd} + page, kensu)
--}}
@extends('layouts.legacy')

@section('menu', 'terminales')

@section('title', 'ディスプレイ管理  - ' . ($siteName ?? ''))

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac1").accordion('option', 'active', 0);
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

@section('content')
@php
    $page = $displays->currentPage();
    $maxPage = $displays->lastPage();
    $rowCount = $displays->count();
@endphp
@include('admin.terminales.partials.sub-navi', ['family' => 'displays'])

<form method="post" name="form_search" id="form_search" class="displayNone" action="{{ route('admin.terminales.signage-displays.index') }}">
@csrf
<input type="hidden" name="kensu" id="kensu" value="{{ $kensu }}" />
<input type="hidden" name="page" id="page" value="1" />
</form>


<div class="contents">


<p class="h2Title">■ディスプレイ管理 </p>

@if ($rowCount > 0)
@include('admin.terminales.partials.kensu-select', ['kensu' => $kensu])

<p><span class="attention">{{ $displays->total() }}件</span>の該当結果がありました。</p>

<table class="tableBox2">
  <tr>
    <!--<th>ID</th>-->
    <th>ID</th>
    <th>端末名</th>
    <th>メモ</th>
    <th>放映プログラム</th>
    <th>&nbsp;</th>
    <th>並び</th>
  </tr>

@foreach ($displays as $display)
@if ($loop->index % 2 != 0)
  <tr class="tableBg">
@else
  <tr>
@endif

    <td>{{ $display->cd }}</td>
    <td>{{ $display->name }}</td>
    <td>{{ $display->comment }}</td>



    <td align="center">
{{ $display->programName ?? '' }}<br />
</td>


    <td align="center">
	<ul>
	<li><a href="{{ route('admin.terminales.signage-displays.show', [$display->cd, 'page' => $page, 'kensu' => $kensu]) }}"><img src="{{ asset('legacy/system/img/icon_syosai.gif') }}" width="40" height="40" alt="詳細" /></a></li>
	</ul>
    </td>


    <td>
<ul style="paddng:0px;margin:0px;float:right;line-height:13px;font-size:12px">
@if ($loop->index > 0 || $page != 1)
<li><a href="#" onclick="location.href='{{ route('admin.terminales.signage-displays.sort', ['page' => request('page'), 'kensu' => request('kensu'), 'thisCd' => $display->cd, 'thisRow' => $display->rank, 'kind' => 'up']) }}'">▲</a></li>
@else
&nbsp;
@endif

@if ($page != $maxPage || $loop->index < $rowCount - 1)
<li><a href="#" onclick="location.href='{{ route('admin.terminales.signage-displays.sort', ['page' => request('page'), 'kensu' => request('kensu'), 'thisCd' => $display->cd, 'thisRow' => $display->rank, 'kind' => 'down']) }}'">▼</a></li>
@endif
</ul>
</td>
  </tr>
@endforeach
</table>

<ul class="btnBox2">
@include('admin.terminales.partials.pagination', ['paginator' => $displays, 'baseUrl' => route('admin.terminales.signage-displays.index')])
</ul>

@else
ディスプレイ管理データはありません。
@endif

<!-- / .contents --></div>
@endsection
