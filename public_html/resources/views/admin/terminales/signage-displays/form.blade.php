{{--
    ディスプレイ 新規登録 / 修正 (legacy: display_new.php y display_update.php → display_frm.php).
    El legacy no tiene confirmación: envía, valida y pasa a «登録中です。» (partials/processing).

    Contrato de datos:
      $display   SignageDisplay|null  null (o sin exists) = alta; en edición, la fila de tb_display:
                   cd, name, comment, tb_c7_movie_program_cds (un solo cd: el formulario elige uno)
      $programs  Collection<SignageProgram>  tb_c7_movie_program con disp_flg <> '9', filtrados por tienda,
                   ORDER BY rank (cd, name). El <select> no tiene opción vacía: sin elección se envía el primero.
      $siteName  string        (compartida por el núcleo) nombre del sitio para el <title>
      $errors    ViewErrorBag  name (必須項目です。), comment, tb_c7_movie_program_cds
                               → class="Ng" + <p class="m0">※mensaje</p>
                               (el legacy valida también terminal_type == '0', pero el formulario no lo envía)

    Rutas:
      POST admin.terminales.signage-displays.store          (alta)
      POST admin.terminales.signage-displays.update  {cd}   (edición)
    Campos enviados (los que lee display_register.php): name, comment, tb_c7_movie_program_cds,
      hb_which (solo alta), cd, act.

    El legacy carga /system/js/query-image-preview.js y .css, que no existen en crm2-php8: se omiten.
    {!! !!} solo imprime atributos fijos escritos en la vista (class="Ng", style="display:none"), nunca datos.
--}}
@extends('layouts.legacy')

@php
    $isNew = ! (isset($display) && $display->exists);
    $mode = $isNew ? '新規登録' : '修正';
    $displayCd = $isNew ? '' : $display->cd;
    $field = fn (string $name) => old($name, $isNew ? '' : ($display->{$name} ?? ''));
@endphp

@section('menu', 'terminales')

@section('title', 'ディスプレイ管理 ' . $mode . ' - ' . ($siteName ?? ''))

@section('js')
<script type="text/javascript" src="{{ asset('legacy/system/category7/digital_signage/js/user.js') }}"></script>

<script type="text/javascript" src="{{ asset('legacy/system/js/jquery-1.3.2.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/js/jquery-ui-1.7.2.custom.min.js') }}"></script>
<script type="text/javascript">



$(document).ready(function() {

	$(".ac1").accordion('option', 'active', 0);

//	checkSignageFunc();




});
</script>
@endsection

@section('content')
@include('admin.terminales.partials.sub-navi', ['family' => 'displays'])
<div class="contents">
<p class="h2Title">■ディスプレイ管理 {{ $mode }}</p>

<div>




<form method="post" name="form1" id="form1" class="form" action="{{ $isNew ? route('admin.terminales.signage-displays.store') : route('admin.terminales.signage-displays.update', $displayCd) }}" enctype="multipart/form-data">
@csrf

<fieldset>
<legend>ディスプレイ管理</legend>

<table class="tableBox">




	<tr>
		<th><label for="title">端末名<em>※</em></label></th>
		<td{!! $errors->has('name') ? ' class="Ng"' : '' !!}>
		<input type="text" name="name" id="name" maxlength="100" class="writeArea" value="{{ $field('name') }}" />
		@error('name')<p class="m0">※{{ $message }}</p>@enderror</td>
	</tr>



	<tr>
		<th><label for="comment">メモ</label></th>
		<td{!! $errors->has('comment') ? ' class="Ng"' : '' !!}>
		<textarea name="comment" id="comment" class="writeArea" />{{ $field('comment') }}</textarea>
		@error('comment')<p class="m0">※{{ $message }}</p>@enderror</td>
	</tr>






	<tr>
		<th><label for="tb_c7_movie_program_cds">「放映プログラム」選択</label></th>
		<td{!! $errors->has('tb_c7_movie_program_cds') ? ' class="Ng"' : '' !!}>


<select name="tb_c7_movie_program_cds" id="tb_c7_movie_program_cds">
@foreach ($programs as $program)


<option value="{{ $program->cd }}"{{ (string) $program->cd === (string) $field('tb_c7_movie_program_cds') ? ' selected' : '' }}>{{ $program->name }}</option>

@endforeach

</select><br />


</td>
	</tr>







@if ($isNew)

	<tr>
		<th><label for="filetype">追加方法<em>※</em></label></th>
		<td>

	先頭に追加：<input type="radio" name="hb_which" value="1" checked />
	最後に追加：<input type="radio" name="hb_which" value="0" /><br />

	</tr>
@endif


</table>
</fieldset>

<div class="btnBox">
<input type="hidden" name="cd" id="cd" value="{{ $displayCd }}" />
<input type="hidden" name="act" id="act" value="up" />
<input type="submit" name="send" id="send" class="btn" value="この内容で登録する" />
</div>

</form>

<!-- / .form --></div>

<!-- / .contents --></div>
@endsection
