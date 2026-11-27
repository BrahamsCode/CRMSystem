{{--
    放映プログラム 一覧 (legacy: system/category7/digital_signage/movie_program_list.php).

    Contrato de datos:
      $programs  LengthAwarePaginator<SignageProgram>  tb_c7_movie_program con disp_flg = '1' (filtrado por
                 tienda) ORDER BY rank; perPage = $kensu. Columnas usadas: cd, name, disp_conf, comment.
      $kensu     int     filas por página (10 por defecto)
      $siteName  string  (compartida por el núcleo) nombre del sitio para el <title>

    Rutas:
      GET|POST admin.terminales.signage-programs.index  (form_search envía por POST)
      GET      admin.terminales.signage-programs.show   ({cd} + page, kensu)

    El legacy tiene la columna 並び (▲▼) comentada: no se muestra y no hay ruta sort para programas.
--}}
@extends('layouts.legacy')

@section('menu', 'terminales')

@section('title', '放映プログラム管理  - ' . ($siteName ?? ''))

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
    $page = $programs->currentPage();
@endphp
@include('admin.terminales.partials.sub-navi', ['family' => 'programs'])

<form method="post" name="form_search" id="form_search" class="movie_programNone" action="{{ route('admin.terminales.signage-programs.index') }}">
@csrf
<input type="hidden" name="kensu" id="kensu" value="{{ $kensu }}" />
<input type="hidden" name="page" id="page" value="1" />
</form>


<div class="contents">

<p class="h2Title">■放映プログラム管理 </p>

@if ($programs->count() > 0)
@include('admin.terminales.partials.kensu-select', ['kensu' => $kensu])

<p><span class="attention">{{ $programs->total() }}件</span>の該当結果がありました。</p>

<table class="tableBox2">
  <tr>
    <!--<th>ID</th>-->
    <th>ID</th>
    <th>番組名</th>
    <th>システム</th>
    <th>メモ</th>
    <th>&nbsp;</th>
  <!--  <th>並び</th>-->
  </tr>

@foreach ($programs as $program)
@if ($loop->index % 2 != 0)
  <tr class="tableBg">
@else
  <tr>
@endif

    <td>{{ $program->cd }}</td>
    <td>{{ $program->name }}</td>
    <td>{{ $program->disp_conf == '1' ? 'ユーザ設定「放映プログラム」' : ($program->disp_conf == '2' ? 'システム内蔵「出力画面」' : '') }}</td>
    <td align="center">{{ $program->comment }}</td>
    <td align="center">
	<ul>
	<li><a href="{{ route('admin.terminales.signage-programs.show', [$program->cd, 'page' => $page, 'kensu' => $kensu]) }}"><img src="{{ asset('legacy/system/img/icon_syosai.gif') }}" width="40" height="40" alt="詳細" /></a></li>
	</ul>
    </td>
{{-- Columna 並び (▲▼) comentada en el legacy --}}
  </tr>
@endforeach
</table>

<ul class="btnBox2">
@include('admin.terminales.partials.pagination', ['paginator' => $programs, 'baseUrl' => route('admin.terminales.signage-programs.index')])
</ul>

@else
放映プログラム管理データはありません。
@endif

<!-- / .contents --></div>
@endsection
