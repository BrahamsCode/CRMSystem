{{--
    ICカード管理 / 端末管理 (legacy: system/category7/felica/terminal_list.php).

    Datos:
      $terminals  LengthAwarePaginator<Terminal>  tb_terminal flg <> '9' (filtro de tienda), ORDER BY rank;
                  atributo extra kaishamei (tienda con disp_flg <> '9')
      $kensu      int  filas por página (10 por defecto)
      $siteName   string  (núcleo) para el <title>

    ▲▼ como el legacy: ▲ salvo en la primera fila de la página, ▼ salvo en la última
    (el legacy comparaba $page y $maxpage sin definir). La columna 並び順 numera la fila en la página.
--}}
@extends('layouts.legacy')

@section('menu', 'terminales')
{{-- Como la muestra legacy: esta pantalla carga su propio <head> con la UI clásica (import.css). Lo aplica el layout de S1 (QA diferencias-terminales.md) --}}
@section('ui_version', '1')

@section('title', '端末管理  - ' . ($siteName ?? ''))

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac2").accordion('option', 'active', 0);
});
</script>

<script type="text/javascript">
//表示件数の制御

function kensuSet(parts){
	document.form_search.kensu.value = parts.value;
	document.form_search.submit();
}

</script>

<script type="text/javascript">
$(function(){

	$(".delcheck").click(function(){

		var cd = $(this).attr('delcd');
		var flg = confirm ( "削除してもよろしいですか？\n\n削除しない場合は[キャンセル]ボタンを押して下さい");

		if(flg){

			$("#row_tr" + cd).html("<td colspan=\"8\" align=\"center\"><img src=\"{{ asset('legacy/system/img/loading.gif') }}\" width=\"100\" /></td>");
			$("#row_tr" + cd).load("{{ route('legacy.terminales.felica-del-terminal') }}",{
				cd:cd
			});
		}
	});
});

</script>
@endsection

@section('content')
@php
    $rowCount = $terminals->count();
    $from = ($terminals->currentPage() - 1) * $terminals->perPage();
@endphp

<form method="post" name="form_search" id="form_search" class="movie_programNone" action="{{ route('admin.terminales.felica-terminals.index') }}">
@csrf
<input type="hidden" name="kensu" id="kensu" value="{{ $kensu }}" />
<input type="hidden" name="page" id="page" value="1" />
</form>


<div class="contents">


<p class="h2Title">■端末管理 </p>

@if ($terminals->total() > 0)
@include('admin.terminales.partials.kensu-select', ['kensu' => $kensu])

<p><span class="attention">{{ $terminals->total() }}件</span>の該当結果がありました。</p>


<p><span class="attention">{{ $from + 1 }}件～{{ $from + $rowCount }}件目</span></p>


<table class="tableBox2">
  <tr>
    <th></th>
    <th>ID</th>
    <th>店舗</th>
    <th>タイプ</th>
    <th>登録日</th>
    <th>シリアル</th>
    <th>並び順</th>
    <th>削除</th>
  </tr>
@foreach ($terminals as $terminal)
@if ($loop->index % 2 != 0)
  <tr class="tableBg" id="row_tr{{ $terminal->cd }}">
@else
  <tr id="row_tr{{ $terminal->cd }}">
@endif
<td>{{ $from + 1 + $loop->index }}</td>
    <td align="center">{{ $terminal->cd }}</td>

    <td align="center">

		{{ $terminal->kaishamei }}
		<input type="hidden" name="shop_cd" id="shop_cd" value="{{ session('shop_cd') }}" />

</td>

    <td align="center">{{ $terminal->type }}</td>
    <td align="center">{{ $terminal->legacyDate('touroku_date')?->format('Y/m/d h:i') }}</td>
    <td align="center">{{ $terminal->type == 'ピットタッチスリム' ? $terminal->terminal_no : $terminal->mac_address }}</td>

    <td align="center">
<p style="float:left;margin:3px">{{ $loop->index + 1 }}</p>
<ul style="paddng:0px;margin:0px;float:right;line-height:13px;font-size:12px">
@if ($loop->index > 0)
<li><a href="#" onclick="location.href='{{ route('admin.terminales.felica-terminals.sort', ['page' => request('page'), 'kensu' => request('kensu'), 'thisCd' => $terminal->cd, 'thisRow' => $terminal->rank, 'kind' => 'up']) }}'">▲</a></li>
@else
&nbsp;
@endif

@if ($loop->index < $rowCount - 1)
<li><a href="#" onclick="location.href='{{ route('admin.terminales.felica-terminals.sort', ['page' => request('page'), 'kensu' => request('kensu'), 'thisCd' => $terminal->cd, 'thisRow' => $terminal->rank, 'kind' => 'down']) }}'">▼</a></li>
@endif
</ul>
</td>
    <td align="center">

<input type="submit" delcd="{{ $terminal->cd }}" class="btn delcheck" value="削除する">
</td>
  </tr>
@endforeach
</table>


<ul class="btnBox2">
@include('admin.terminales.partials.pagination', ['paginator' => $terminals, 'baseUrl' => route('admin.terminales.felica-terminals.index')])
</ul>


@else
見つかりませんでした

@endif
<!-- / .contents --></div>
@endsection
