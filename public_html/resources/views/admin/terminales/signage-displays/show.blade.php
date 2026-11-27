{{--
    ディスプレイ 詳細 (legacy: display_detail.php).

    Contrato de datos:
      $display   SignageDisplay  fila de tb_display (filtrada por tienda): cd, name, comment, tb_c7_movie_program_cds
      $program   SignageProgram|null  tb_c7_movie_program de tb_c7_movie_program_cds (disp_flg <> '9', por tienda);
                 se usan name y vertical_setting (tamaño del popup de プレビュー)
      $shopCd    int|null  tienda de la sesión (query shop_cd del reproductor)
      $siteName  string    (compartida por el núcleo) nombre del sitio para el <title>

    Request leído directamente (como el legacy): register_type (título), kensu, page, shop_cd.

    Rutas:
      GET|POST admin.terminales.signage-displays.index         (<<一覧へ戻る, POST como el legacy)
      GET|POST admin.terminales.signage-displays.edit    {cd}  (編集する, POST como el legacy)
      POST     admin.terminales.signage-displays.destroy {cd}  (削除する, tras confirm())
      Vista previa: /digitalsignage/index2.php?shop_cd=&display_cd= (URL pública del reproductor, se conserva).

    Diferencias con el legacy: tras PHP 7/8 el legacy no imprime el nombre del programa y siempre abre la
    vista previa a 900x550; aquí se muestra $program->name y se usa su vertical_setting. El enlace プレビュー
    sale, como en el legacy, cuando tb_c7_movie_program_cds no está vacío.
    Se conserva el </tr> que falta tras la fila メモ (HTML del legacy).
--}}
@extends('layouts.legacy')

@php
    $isPortrait = ($program?->vertical_setting) == 'portrait';
    $previewWidth = $isPortrait ? '550' : '900';
    $previewHeight = $isPortrait ? '900' : '550';
@endphp

@section('menu', 'terminales')

@section('title', 'ディスプレイ管理' . (request('register_type') == 'new' ? ' 新規登録' : ' 修正') . ' - ' . ($siteName ?? ''))

@section('css')
<link href="{{ asset('legacy/system/css/popupwindow.css') }}" rel="stylesheet" type="text/css" media="all" />
@endsection

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac1").accordion('option', 'active', 0);
});
</script>
<script type="text/javascript"><!--

function delChk () {
    var flg = confirm ( "削除するとこの端末に登録されている広告も一緒に削除されます。\n削除してもよろしいですか？\n\n削除しない場合は[キャンセル]ボタンを押して下さい");

    return flg;
}



--></script>

<script type="text/javascript" src="{{ asset('legacy/system/js/ajaxupload-3.5.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/js/jquery.maskedinput-1.2.2.js') }}"></script>


<script type="text/javascript" src="{{ asset('legacy/system/js/popupwindow-1.8.js') }}"></script>
@endsection

@section('content')
@include('admin.terminales.partials.sub-navi', ['family' => 'displays'])
<div class="contents">
<p class="h2Title">■ディスプレイ管理 </p>

<div class="form">

<fieldset>
<legend>ディスプレイ管理</legend>

<table class="tableBox">




	<tr>
		<th>端末名</th>
		<td>{{ $display->name }}</td>
	</tr>

	<tr>
		<th>メモ</th>
		<td>{{ $display->comment }}</td>




	<tr>
		<th>ディスプレイ出力</th>
		<td>
{{ $program?->name }}<br />

@if ($display->tb_c7_movie_program_cds != '')
 <a href="#" onclick="new $pop('{{ url('digitalsignage/index2.php') }}?shop_cd={{ $shopCd ?? '' }}&display_cd={{ $display->cd }}', {type: 'iframe', title: 'プレビュー',width: {{ $previewWidth }}, height: {{ $previewHeight }}, windowmode:true, resize: true , modal: true,effect: 'normal'} ); return false;">プレビュー</a>
@endif

</td>

</tr>
</table>


</fieldset>



<div class="btnBox">


<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.terminales.signage-displays.index') }}">
@csrf
<input type="hidden" name="kensu" id="kensu" value="{{ request('kensu') }}" />
<input type="hidden" name="page" id="page" value="{{ request('page') }}" />
<input type="submit" name="enter" id="enter" class="btn" value="&lt;&lt;一覧へ戻る" />
</form>



<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.terminales.signage-displays.edit', $display->cd) }}">
@csrf
<input type="hidden" name="cd" id="cd" value="{{ $display->cd }}" />
<input type="submit" name="enter" id="enter" class="btn" value="編集する" />
</form>



<form method="post" name="form3" id="form2" class="form" action="{{ route('admin.terminales.signage-displays.destroy', $display->cd) }}" onsubmit="return delChk()">
@csrf
<input type="hidden" name="delete_flg" id="delete_flg" value="del">
<input type="hidden" name="cd" id="cd" value="{{ $display->cd }}" />
<input type="hidden" name="kensu" id="kensu" value="{{ request('kensu') }}" />
<input type="hidden" name="page" id="page" value="{{ request('page') }}" />
<input type="hidden" name="shop_cd" id="shop_cd" value="{{ request('shop_cd') }}" />
<input type="submit" name="delete" id="delete" class="btn" value="削除する">
</form>




</div>


<!-- / .form --></div>



<!-- / .contents --></div>
@endsection
