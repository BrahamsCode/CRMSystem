{{--
    放映素材 新規登録 / 修正 (legacy: screen_saver_new.php y screen_saver_update.php → screen_saver_frm.php).
    Pantalla sin menú (body blanco): se abre sola o dentro del popup $pop del formulario de 放映プログラム.
    El legacy no tiene confirmación: envía, valida y pasa a «登録中です。» (partials/processing).

    Contrato de datos:
      $material    SignageMaterial|null  null (o sin exists) = alta; en edición, la fila de tb_screensaver:
                     cd, categorize_value_0, title, comment, filetype ('画像ファイル' | 'SWFファイル' | 'FLVファイル'),
                     play_frame_sec,
                     picFlags      array<int,string>  pic_flg partido por '<>' ('1' = 表示する)
                     picExtensions array<int,string>  pic_extension partido por '<>' ('.jpg', '.swf', …)
                     picTexts      array<int,string>  (opcional) pic_text partido por '<>' (alt/title de la miniatura)
      $categories  Collection<string>    categorize_value_0 distintos de tb_screensaver (no vacíos, disp_flg <> '9',
                                         filtrados por tienda), ordenados
      $siteName    string                (compartida por el núcleo) nombre del sitio para el <title>
      $errors      ViewErrorBag          title, comment, filetype → marca class="Ng" y <p class="m0">※mensaje</p>
                                         (mensaje del Form Request sin «※», p. ej. 必須項目です。)

    Old input: tras un error de validación se reutilizan los valores enviados (old()).

    Rutas:
      POST admin.terminales.signage-materials.store           (alta; multipart: photo0..4, photo10, photo20)
      POST admin.terminales.signage-materials.update  {cd}    (edición)
    Campos enviados (los mismos que lee screen_saver_register.php / pic_up.php):
      categorize_value_0_new, categorize_value_0, title, comment, filetype, photo{0..4,10,20},
      pic_extension[x], pic_flg[x], play_frame_sec, pic_max (5), pic_cate_folder, hb_which (solo alta), cd, act.
    {!! !!} solo imprime atributos fijos escritos en la vista (class="Ng", style="display:none"), nunca datos.
--}}
@extends('layouts.legacy-popup')

@php
    $isNew = ! (isset($material) && $material->exists);
    $mode = $isNew ? '新規登録' : '修正';
    $hasOld = session()->hasOldInput();
    $materialCd = $isNew ? '' : $material->cd;
    $filetype = old('filetype', $isNew ? '' : $material->filetype);
    // pic_flg: el legacy usa lo enviado si vuelve con error y lo guardado en la BD si no
    $picFlags = $hasOld ? (array) old('pic_flg', []) : ($isNew ? [] : ($material->picFlags ?? []));
    // pic_extension: siempre el de la BD (en alta no hay)
    $picExtensions = $isNew ? [] : ($material->picExtensions ?? []);
    $picTexts = $isNew ? [] : ($material->picTexts ?? []);
    $picMax = 5;
    $picFolder = 'hp/uploads/c7/screen_saver/' . $materialCd;
@endphp

@section('title', '放映ファイル管理 ' . $mode . ' - ' . ($siteName ?? ''))

@section('js')
<script type="text/javascript" src="{{ asset('legacy/system/category7/digital_signage/js/user.js') }}"></script>

<script type="text/javascript" src="{{ asset('legacy/system/js/jquery-1.3.2.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/js/jquery-ui-1.7.2.custom.min.js') }}"></script>
<script type="text/javascript">
$(document).ready(function() {

	$("#filetype").change(function(){

	check_p = $("#filetype").val();


		if(check_p == "画像ファイル"){

			$("#pic_forms").css("display","block");
			$("#flv_forms").css("display","none");
			$("#swf_forms").css("display","none");
		}


		if(check_p == "SWFファイル"){

			$("#pic_forms").css("display","none");
			$("#flv_forms").css("display","none");
			$("#swf_forms").css("display","block");
		}

		if(check_p == "FLVファイル"){

			$("#pic_forms").css("display","none");
			$("#flv_forms").css("display","block");
			$("#swf_forms").css("display","none");
		}
	});

});
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■放映ファイル管理 {{ $mode }}</p>

<div>
<form method="post" name="form1" id="form1" class="form" action="{{ $isNew ? route('admin.terminales.signage-materials.store') : route('admin.terminales.signage-materials.update', $materialCd) }}" enctype="multipart/form-data">
@csrf

<fieldset>
<legend>放映ファイル管理</legend>

<table class="tableBox">

	<tr>
		<th><label for="categorize_value_0">カテゴリ</label></th>
		<td{!! $errors->has('categorize_value_0') ? ' class="Ng"' : '' !!}>
		<input type="text" name="categorize_value_0_new" id="categorize_value_0_new" maxlength="10" value="{{ old('categorize_value_0_new') }}" />

		<select name="categorize_value_0" id="categorize_value_0" />

<option value="">選択して下さい</option>
@foreach ($categories as $category)
<option value="{{ $category }}"{{ old('categorize_value_0', $isNew ? '' : $material->categorize_value_0) == $category ? ' selected' : '' }}>{{ $category }}</option>
@endforeach
		</select>

		@error('categorize_value_0')<p class="m0">※{{ $message }}</p>@enderror</td>
	</tr>


	<tr>
		<th><label for="title">タイトル<em>※</em></label></th>
		<td{!! $errors->has('title') ? ' class="Ng"' : '' !!}>
		<input type="text" name="title" id="title" maxlength="100" class="writeArea" value="{{ old('title', $isNew ? '' : $material->title) }}" />
		@error('title')<p class="m0">※{{ $message }}</p>@enderror</td>
	</tr>

	<tr>
		<th><label for="comment">コメント<em>※</em></label></th>
		<td{!! $errors->has('comment') ? ' class="Ng"' : '' !!}>
		<input type="text" name="comment" id="comment" maxlength="100" class="writeArea" value="{{ old('comment', $isNew ? '' : $material->comment) }}" />
		@error('comment')<p class="m0">※{{ $message }}</p>@enderror</td>
	</tr>



	<tr>
		<th><label for="filetype">ファイルタイプ<em>※</em></label></th>
		<td{!! $errors->has('filetype') ? ' class="Ng"' : '' !!}>
		<select name="filetype" id="filetype" id="filetype" />

		<option{{ $filetype == '画像ファイル' ? ' selected' : '' }}>画像ファイル</option>
		<option{{ $filetype == 'SWFファイル' ? ' selected' : '' }}>SWFファイル</option>
		<option{{ $filetype == 'FLVファイル' ? ' selected' : '' }}>FLVファイル</option>

		</select>
		@error('filetype')<p class="m0">※{{ $message }}</p>@enderror</td>
	</tr>


	<tr>
		<th><label for="files">ファイル</label></th>
		<td{!! $errors->has('files') ? ' class="Ng"' : '' !!}>

{{-- ////////////////////////// 画像 --}}
<div  style="display:{{ ($filetype == '画像ファイル' || $filetype == '') ? 'block' : 'none' }}" id="pic_forms">

@for ($x = 0; $x < $picMax; $x++)
{{ $x + 1 }}@if (($picFlags[$x] ?? null) == '1')<img src="{{ asset($picFolder . '/' . $x . 's' . ($picExtensions[$x] ?? '')) }}?t={{ time() }}" alt="{{ $picTexts[$x] ?? '' }}" title="{{ $picTexts[$x] ?? '' }}" class="mB10" />@endif

    	<input type="file" name="photo{{ $x }}" /> 1280 x 1024

	<input type="hidden" name="pic_extension[{{ $x }}]" value="{{ $picExtensions[$x] ?? '' }}" />
	表示する：<input type="radio" name="pic_flg[{{ $x }}]" value="1"{{ ($picFlags[$x] ?? null) == '1' ? ' checked' : '' }} />
	しない：<input type="radio" name="pic_flg[{{ $x }}]" value="0"{{ ($picFlags[$x] ?? null) != '1' ? ' checked' : '' }} /><br />

@endfor

<input type="hidden" name="pic_max" value="{{ $picMax }}">
<input type="hidden" name="pic_cate_folder" value="../../../hp/uploads/c7/screen_saver">
</div>{{-- ////////////////////////// 画像 END --}}

<div  style="display:{{ $filetype == 'FLVファイル' ? 'block' : 'none' }}" id="flv_forms">
{{-- ////////////////////////// FLV (el legacy reservaba aquí el reproductor: «プレイヤー設置予定地») --}}
    	<input type="file" name="photo10" />　flvファイル

	<input type="hidden" name="pic_extension[10]" value="{{ $picExtensions[10] ?? '' }}" />
	表示する：<input type="radio" name="pic_flg[10]" value="1"{{ ($picFlags[0] ?? null) == '1' ? ' checked' : '' }} />
	しない：<input type="radio" name="pic_flg[10]" value="0"{{ ($picFlags[0] ?? null) != '1' ? ' checked' : '' }} /><br />


</div>{{-- ////////////////////////// FLV END --}}


<div  style="display:{{ $filetype == 'SWFファイル' ? 'block' : 'none' }}" id="swf_forms">
{{-- ////////////////////////// SWF: el legacy incrustaba <object>/<embed> de "<cd>/20<ext>"; Flash no se reproduce --}}
@if (($picFlags[0] ?? null) == '1')
@include('admin.terminales.partials.swf-placeholder', ['fileName' => '20' . ($picExtensions[0] ?? '')])<br />
@endif

    	<input type="file" name="photo20" />　swfファイル

	<input type="hidden" name="pic_extension[20]" value="{{ $picExtensions[20] ?? '' }}" />
	表示する：<input type="radio" name="pic_flg[20]" value="1"{{ ($picFlags[0] ?? null) == '1' ? ' checked' : '' }} />
	しない：<input type="radio" name="pic_flg[20]" value="0"{{ ($picFlags[0] ?? null) != '1' ? ' checked' : '' }} /><br />

	再生時間： <input type="text" name="play_frame_sec" id="play_frame_sec" maxlength="3" class="inputS " value="{{ old('play_frame_sec', $isNew ? '' : $material->play_frame_sec) }}" />秒

</div>{{-- ////////////////////////// SWF END --}}


		@error('files')<p class="m0">※{{ $message }}</p>@enderror</td>
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
<input type="hidden" name="cd" id="cd" value="{{ $materialCd }}" />
<input type="hidden" name="act" id="act" value="up" />
<input type="submit" name="send" id="send" class="btn" value="この内容で登録する" />
</div>

</form>

<!-- / .form --></div>

<!-- / .contents --></div>
@endsection
