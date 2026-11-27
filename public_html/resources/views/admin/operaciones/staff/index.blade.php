{{-- Legacy: system/category5/staff/staff_list.php (スタッフ一覧) con inc/search_set1.php.
     Variables:
       $act         '' (solo formulario) | 'up' | 'return'
       $kensu       表示件数
       $page        página actual
       $list        null sin búsqueda; si no, ['total', 'from', 'rows', 'maxPage']
       $fields      tb_koumoku de tb_staff con 検索 marcado
       $words       valores del formulario guardados en la sesión
       $shops       tiendas activas (登録店舗)
       $belongposts nombres de tb_belongpost (所属部署) --}}
@extends('layouts.legacy')

@section('menu', 'operaciones')

@section('title', 'スタッフ一覧 - '.($siteName ?? ''))

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
function ChangePage(page){
	document.form_change_page.page.value = page;
	document.form_change_page.submit();
}
</script>
@endsection

@section('content')
<div class="contents">

<form method="get" name="form_change_page" id="form_change_page" action="{{ route('admin.operaciones.staff.index') }}#result">
<input type="hidden" name="act" value="{{ $act }}">
<input type="hidden" name="kensu" value="{{ $kensu }}">
<input type="hidden" name="page" id="page" value="{{ $page }}" />
</form>

<p class="h2Title">■スタッフ検索</p>

<form method="post" name="form_search" id="form_search" class="form" action="{{ route('admin.operaciones.staff.search') }}">
@csrf
@include('admin.operaciones.staff.partials.search-fields')
<div class="btnBox">
<input type="hidden" name="act" value="up" />
<input type="hidden" name="kensu" id="kensu" value="{{ $kensu }}" />
<input type="hidden" name="se_flg" value="1" />
<input type="submit" name="search_staff" class="btnStyleM" value="この条件で抽出する" />
</div>
　<a href="{{ route('admin.operaciones.staff.index') }}">検索条件クリア</a>
</form>

<div class="btnBox">
@if ($list !== null)
<table class="tableBox7">
<thead>
<tr>
<th colspan="10"><span class="text3L attention">{{ $list['total'] }}</span>件見つかりました。
@can('nucleo.master')
<form action="{{ route('admin.operaciones.staff.csv') }}" method="post">
@csrf
<input type="hidden" name="titleflg" value="1" />
<input type="submit" name="enter" id="enter" class="btnStyleM" value="この条件でCSV出力する" />
</form>
@endcan
</th>
</tr>
</thead>
</table>
@endif
</div>

<!-- / .contents --></div>

<div class="contents" id="result">
<p class="h2Title">■スタッフ一覧</p>
<div class="form">
@if (($list['total'] ?? 0) > 0)
<p class="maxListLength">表示件数<select name="kensu_act" id="kensu_act" class="selectBox" onchange="kensuSet(this)">
@for ($i = 1; $i <= 5; $i++)
<option value="{{ $i * 10 }}" @selected($kensu === $i * 10)>{{ $i * 10 }}</option>
@endfor
</select></p>

<p><span class="attention">{{ $list['from'] + 1 }}件～{{ $list['from'] + $list['rows']->count() }}件目/{{ $list['total'] }}件中</span></p>

<table class="tableBox2">
  <tr>
    <th style="width: 7em;">スタッフID</th>
    <th>名前</th>
    <th style="width: 3em;">&nbsp;</th>
  </tr>
@foreach ($list['rows'] as $staff)
  <tr @class(['tableBg' => $loop->index % 2 != 0])>
    {{-- tb_staff se guarda con get_form2 (ya escapado): se pinta tal cual, como el legacy --}}
    <td>{!! $staff->id !!}</td>
    <td><span title="{!! $staff->name_kana !!}">{!! $staff->name !!}</span></td>
    <td align="center">
	@can('kengen.1024.1')
	<form method="get" name="staff_detail{{ $loop->index }}" action="{{ route('admin.operaciones.staff.show', $staff->cd) }}">
		<input type="submit" class="btnStyle" value="詳細" />
		<input type="hidden" name="act" value="{{ $act }}" />
		<input type="hidden" name="page" value="{{ $page }}" />
		<input type="hidden" name="kensu" value="{{ $kensu }}" />
	</form>
	@endcan
	</td>
  </tr>
@endforeach
</table>

<ul class="btnBox2">
@if ($page != 1 && $kensu < $list['total'])
<li class="btn"><a href="#" onclick="ChangePage({{ $page - 1 }})"><img src="/system/img/btn_back_off.gif" width="92" height="32" alt="前へ" /></a></li>
@endif
@if ($list['maxPage'] > 1)
@for ($l = max(1, $page - 5); $l <= min($list['maxPage'], $page + 5); $l++)
@if ($l == $page)
<li class="now"><b>{{ $l }}</b></li>
@else
<li><a href="#" onclick="ChangePage({{ $l }})">{{ $l }}</a></li>
@endif
@endfor
@endif
@if ($page < $list['maxPage'])
<li class="btn"><a href="#" onclick="ChangePage({{ $page + 1 }})"><img src="/system/img/btn_next_off.gif" width="92" height="32" alt="次へ" /></a></li>
@endif
</ul>
@else
検索条件に合ったスタッフは、見つかりませんでした。再度検索してください
@endif
</div>

<!-- / .contents --></div>
@endsection
