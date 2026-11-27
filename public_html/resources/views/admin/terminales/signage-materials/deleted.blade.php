{{--
    放映素材 削除完了 (legacy: screen_saver_delete_complete.php). Pantalla sin menú (body blanco);
    destino del Refresh de «削除中です。» (partials/processing).

    Contrato de datos:
      $siteName  string  (compartida por el núcleo) nombre del sitio para el <title>
    Request leído directamente: register_type (solo cambia el <title>, como el legacy).

    Rutas: GET admin.terminales.signage-materials.deleted

    Diferencias con el legacy:
      - El legacy deja <div class="contents"> sin cerrar (el </div> de «.form» cierra .btnBox): aquí se cierra.
      - El <script> inline del legacy no cierra $(document).ready (error de sintaxis) y no hace nada: se omite,
        igual que ./js/shopping_entry.js, que no existe.
--}}
@extends('layouts.legacy-popup')

@section('title', '放映ファイル管理' . (request('register_type') == 'new' ? ' 新規登録' : ' 修正') . ' - ' . ($siteName ?? ''))

@section('js')
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
		<td>削除しました</td>
	</tr>



</table>
</fieldset>



<div class="btnBox">
</div>

<!-- / .form --></div>



<!-- / .contents --></div>
@endsection
