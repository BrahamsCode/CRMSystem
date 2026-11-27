{{-- Legacy: system/category1/rank/customer_rank_frm.php (incluido por kingaku_rank_new.php / kingaku_rank_update.php /
     raiten_rank_new.php / raiten_rank_update.php).
     Variables:
       $siteName  nombre del sitio (<title>)
       $type      'kingaku' (使用金額ランク) | 'raiten' (来店回数ランク)
       $rank      ?Model rango que se edita (cd, name, jyouken1, jyouken2, hyouji_flg) o null en el alta
     Errores de validación (RankRequest) en $errors con las claves 'name' y 'jyouken'.
--}}
@php
    $title = ['kingaku' => '使用金額ランク', 'raiten' => '来店回数ランク'][$type];
    $rankName = ['kingaku' => '使用金額', 'raiten' => '来店回数'][$type];
    $rankUnit = ['kingaku' => '円', 'raiten' => '回'][$type];
    $mode = $rank ? '更新' : '新規登録';
    $hyoujiFlg = (string) old('hyouji_flg', $rank?->hyouji_flg);
@endphp
@extends('layouts.legacy')

@section('menu', 'clientes')

@section('title', $title . ' - ' . $siteName)

@section('js')
<script type="text/javascript">
<!--

// =================================================================================================
// 数字入力チェック
// =================================================================================================

function replace_value(data) {

	data.value = data.value.replace(/[^0-9\.]/g, '');

}

function numbersonly(myfield, e, dec) {

	var key,keychar;

	if (window.event)
	   key = window.event.keyCode;
	else if (e)
	   key = e.which;
	else
	   return true;
	keychar = String.fromCharCode(key);

	// control keys
	if ((key==null) || (key==0) || (key==8) ||
	    (key==9) || (key==13) || (key==27) )
	   return true;

	// numbers
	else if ((("0123456789").indexOf(keychar) > -1))
	   return true;

	// decimal point jump
	else if (dec && (keychar == "."))
		{
			if ( (myfield.value).indexOf(keychar) > -1 ) {
				return false;
			} else {
				return true;
			}
		}
	else
	   return false;

}

-->
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■顧客ランク {{ $mode }}</p>

<div>
<form method="post" name="form1" id="form1" class="form" action="{{ $rank ? route('admin.clientes.ranks.update', [$type, $rank->cd]) : route('admin.clientes.ranks.store', $type) }}">
@csrf
@if ($rank)
@method('PUT')
@endif

<fieldset>
<legend>{{ $title }}</legend>

<table class="tableBox">

	<tr>
		<th><label for="hyouji_flg1">表示フラグ</label></th>
		<td>
		<ul>
		<li><input type="radio" name="hyouji_flg" id="hyouji_flg1" class="checkBtn" value="1" @checked($hyoujiFlg != '9') />
		<label for="hyouji_flg1">表示</label></li>
		<li><input type="radio" name="hyouji_flg" id="hyouji_flg2" class="checkBtn" value="9" @checked($hyoujiFlg == '9') />
		<label for="hyouji_flg2">非表示</label></li>
		</ul>
		</td>
	</tr>

	<tr>
		<th><label for="name">ランク名<em>※</em></label></th>
		<td{!! $errors->has('name') ? ' class="Ng"' : '' !!}>
		<input type="text" name="name" id="name" class="inputL" value="{{ old('name', $rank?->name) }}" />
		@if ($errors->has('name'))<br />{{ $errors->first('name') }}@endif
		</td>
	</tr>

	<tr>
		<th><label for="jyouken1">適用条件<em>※</em></label></th>
		<td{!! $errors->has('jyouken') ? ' class="Ng"' : '' !!}>
		期間内の{{ $rankName }}が&nbsp;
		<input type="text" name="jyouken1" id="jyouken1" class="inputS" value="{{ old('jyouken1', $rank?->jyouken1) }}" maxlength="9" style="ime-mode:disabled;" onKeyPress="return numbersonly(this, event)" onkeyup="replace_value(this)" onmouseover="replace_value(this)" onchange="replace_value(this)" />{{ $rankUnit }}以上
		&emsp;～&emsp;
		<input type="text" name="jyouken2" id="jyouken2" class="inputS" value="{{ old('jyouken2', $rank?->jyouken2) }}" maxlength="9" style="ime-mode:disabled;" onKeyPress="return numbersonly(this, event)" onkeyup="replace_value(this)" onmouseover="replace_value(this)" onchange="replace_value(this)" />{{ $rankUnit }}未満
		@if ($errors->has('jyouken'))<br />{{ $errors->first('jyouken') }}@endif</td>
	</tr>

</table>
</fieldset>



<div class="btnBox">
<input type="hidden" name="cd" id="cd" value="{{ $rank?->cd }}" />
<input type="hidden" name="act" id="act" value="{{ $rank ? 'update' : 'new' }}" />
<input type="button" name="dummy_back_input" id="dummy_back_input" class="btnStyleM" value="　　一覧に戻る　　" onclick="document.back_btn.submit()" />
<input type="button" name="enter" id="enter" class="btn btnStyleM" value="この内容で登録する" onclick="document.form1.submit()" />
</div>



</form>

<form name="back_btn" id="back_btn" action="{{ route('admin.clientes.ranks') }}" method="get" style="display: none">
</form>



<!-- / .form --></div>

<!-- / .contents --></div>
@endsection
