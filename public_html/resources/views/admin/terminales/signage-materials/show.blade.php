{{--
    放映素材 詳細 (legacy: screen_saver_detail.php). Pantalla sin menú (body blanco); también se abre
    dentro del popup $pop «データ編集» del formulario de 放映プログラム.

    Contrato de datos:
      $material  SignageMaterial  fila de tb_screensaver (filtrada por tienda):
                   cd, categorize_value_0, title, comment, filetype,
                   picFlags      array<int,string>  pic_flg partido por '<>'
                   picExtensions array<int,string>  pic_extension partido por '<>'
                   picTexts      array<int,string>  (opcional) pic_text partido por '<>'
      $siteName  string  (compartida por el núcleo) nombre del sitio para el <title>

    Request leído directamente (como el legacy): register_type (título), kensu, page, display_cd, shop_cd
    (se reenvían en el formulario de borrado).

    Rutas:
      GET|POST admin.terminales.signage-materials.edit     {cd}  (botón 編集する, POST como el legacy)
      POST     admin.terminales.signage-materials.destroy  {cd}  (botón 削除する, tras confirm())

    Archivos: hp/uploads/c7/screen_saver/<cd>/<x>s<ext> (miniatura 150x150 de pic_up.php).
--}}
@extends('layouts.legacy-popup')

@php
    $picFlags = $material->picFlags ?? [];
    $picExtensions = $material->picExtensions ?? [];
    $picTexts = $material->picTexts ?? [];
    $picFolder = 'hp/uploads/c7/screen_saver/' . $material->cd;
@endphp

@section('title', '放映ファイル管理' . (request('register_type') == 'new' ? ' 新規登録' : ' 修正') . ' - ' . ($siteName ?? ''))

@section('js')
<script type="text/javascript"><!--

function delChk () {
    var flg = confirm ( "削除してもよろしいですか？\n\n削除しない場合は[キャンセル]ボタンを押して下さい");

    return flg;
}



--></script>

<script type="text/javascript" src="{{ asset('legacy/system/js/ajaxupload-3.5.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/js/jquery.maskedinput-1.2.2.js') }}"></script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■放映ファイル管理 </p>

<div class="form">

<fieldset>
<legend>放映ファイル管理</legend>

<table class="tableBox">

	<tr>
		<th>カテゴリ</th>
		<td>{{ $material->categorize_value_0 }}</td>
	</tr>

	<tr>
		<th>タイトル</th>
		<td>{{ $material->title }}</td>
	</tr>

	<tr>
		<th>コメント</th>
		<td>{{ $material->comment }}</td>
	</tr>


	<tr>
		<th>ファイルタイプ</th>
		<td>{{ $material->filetype }}</td>
	</tr>

	<tr>
		<th>ファイル</th>
		<td>

@if ($material->filetype == '画像ファイル')
@for ($x = 0; $x < 5; $x++)
@if (($picFlags[$x] ?? null) == '1')
<img src="{{ asset($picFolder . '/' . $x . 's' . ($picExtensions[$x] ?? '')) }}?t={{ time() }}" alt="{{ $picTexts[$x] ?? '' }}" title="{{ $x + 1 }}" class="mB10" style="margin:5px" />
@endif
@endfor
@endif


@if ($material->filetype == 'SWFファイル' && ($picFlags[0] ?? null) == '1')
{{-- El legacy incrustaba <object>/<embed> de "<cd>/20<ext>": Flash no se reproduce --}}
@include('admin.terminales.partials.swf-placeholder', ['fileName' => '20' . ($picExtensions[0] ?? '')])
@endif


{{-- FLV: pic_up.php guarda el flag del hueco 10 en la posición 0 de pic_flg (implode sin claves);
     el legacy miraba la posición 10 y nunca mostraba el texto. Se mira la 0. --}}
@if ($material->filetype == 'FLVファイル' && ($picFlags[0] ?? null) == '1')
プレイヤー設置予定
@endif
</td>
	</tr>

</table>
</fieldset>



<div class="btnBox">

<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.terminales.signage-materials.edit', $material->cd) }}">
@csrf
<input type="hidden" name="cd" id="cd" value="{{ $material->cd }}" />
<input type="submit" name="enter" id="enter" class="btn" value="編集する" />
</form>



<form method="post" name="form3" id="form2" class="form" action="{{ route('admin.terminales.signage-materials.destroy', $material->cd) }}" onsubmit="return delChk()">
@csrf
<input type="hidden" name="delete_flg" id="delete_flg" value="del">
<input type="hidden" name="cd" id="cd" value="{{ $material->cd }}" />
<input type="hidden" name="kensu" id="kensu" value="{{ request('kensu') }}" />
<input type="hidden" name="page" id="page" value="{{ request('page') }}" />
<input type="hidden" name="display_cd" id="display_cd" value="{{ request('display_cd') }}" />
<input type="hidden" name="shop_cd" id="shop_cd" value="{{ request('shop_cd') }}" />
<input type="submit" name="delete" id="delete" class="btn" value="削除する">
</form>



</div>


<!-- / .form --></div>



<!-- / .contents --></div>
@endsection
