{{--
	部品 / 項目 新規登録・修正 — legacy: category3/anketo(2)/anketo_form_frm.php
	Variables: $family, $title, $mode, $action, $data (get_form: texto plano, se imprime escapado), $errors, $cd, $pcd, $page, $back
	El legacy pone `『』` en el título (pg_fetch_result con fila '' daba NULL en PHP 7): aquí va el nombre de la encuesta.
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', $title . $mode)

@php
	$v = fn (string $k) => $data[$k] ?? '';
	$err = fn (string $k) => $errors[$k] ?? '';
	$type = $v('form_type');
	$options = in_array($type, \App\Services\Promotions\AnketoItemService::OPTION_TYPES, true);
	$customer = $family->isCustomer();
@endphp

@section('css')
<style type="text/css"><!--


#kojinOption,
#houjinOption {
	display: none;
}


--></style>
@endsection

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$("{{ $family->ac() }}").accordion('option', 'active', 0);
	window.scroll(0,0);
});
</script>

<script type="text/javascript" src="/system/js/jquery.maskedinput-1.2.2.js"></script>

<script language="JavaScript">
<!--
function disp(parts){

	if(parts.value=='ラジオボタン'||parts.value=='セレクト'||parts.value=='チェックボックス'){

		document.getElementById('optionDisp').innerHTML='オプション数:<input type=\"text\" name=\"flds_row\" id=\"flds_row\" class=\"inputS\" value=\"\" style=\"ime-mode: disabled\" />\n<input type=\"button\" onClick=\"addForms(document.getElementById(\'flds_row\').value,\'\')\" value=\"オプション数をセット\">';

	}else if(parts.value=='テーブル'){
		document.getElementById('optionDisp').innerHTML='行数:<input type=\"text\" name=\"flds_col\" id=\"flds_col\" class=\"inputS\" value=\"\" style=\"ime-mode: disabled\" />\n 列数:<input type=\"text\" name=\"flds_row\" id=\"flds_row\" class=\"inputS\" value=\"\" style=\"ime-mode: disabled\" />\n<input type=\"button\" onClick=\"addForms(document.getElementById(\'flds_row\').value,\'テーブル\')\" value=\"行数・列数をセット\">';

	}else if(parts.value=='画像'||parts.value=='画像とテキスト'){
		document.getElementById('optionDisp').innerHTML='<br />大画像<br />横幅:<input type=\"text\" name=\"size_w0\" id=\"size_w0\" class=\"inputS\" value=\"\" style=\"ime-mode: disabled\" /> px &nbsp; x &nbsp; 縦幅:<input type=\"text\" name=\"size_h\" id=\"size_h\" class=\"inputS\" value=\"\" style=\"ime-mode: disabled\" /> px\n<br />自動縮小１<br />横幅:<input type=\"text\" name=\"size_w1\" id=\"size_w1\" class=\"inputS\" value=\"\" style=\"ime-mode: disabled\" /> px\n<br />自動縮小２<br />横幅:<input type=\"text\" name=\"size_w2\" id=\"size_w2\" class=\"inputS\" value=\"\" style=\"ime-mode: disabled\" /> px\n<br />';

	}else{
		document.getElementById('optionDisp').innerHTML='';
		document.getElementById('eachformData').innerHTML='';
	}
}


function addForms(row, parts){

	var eachFromCreate = "";
	var eachform = "";
	var eachsend = "";

	for(each_row = 0;each_row < row ;each_row ++){

		if(document.getElementById("eachform"+each_row)){ eachform = document.getElementById('eachform'+each_row).value; }else{eachform = "";}
		if(document.getElementById("eachsend"+each_row)){ eachsend = document.getElementById('eachsend'+each_row).value; }else{eachsend = "";}



		if(parts == 'テーブル'){

			eachFromCreate += "表示値：<input type=\"text\" name=\"eachform"+each_row+"\" id=\"eachform"+each_row+"\" class=\"writeArea inputM\" value=\""+ eachform +"\" />\n　";

			eachFromCreate += "スタイル：<input type=\"text\" name=\"eachsend"+each_row+"\" id=\"eachsend"+each_row+"\" class=\"inputM\" value=\""+ eachsend +"\" /><br />";

		}else{

			eachFromCreate += "表示値：<input type=\"text\" name=\"eachform"+each_row+"\" id=\"eachform"+each_row+"\" onchange=\"document.getElementById('eachsend"+each_row+"').value=this.value\" class=\"writeArea inputM\" value=\""+ eachform +"\" />\n　";

			eachFromCreate += "<div style=\"display:none\">送信値：<input type=\"text\" name=\"eachsend"+each_row+"\" id=\"eachsend"+each_row+"\" class=\"inputM\" value=\""+ eachsend +"\" /></div><br />";
		}

	}


	document.getElementById('eachformData').innerHTML=eachFromCreate;
}
//-->

</script>
@endsection

@section('content')
@unless ($customer)
<a href="{{ $family->route('forms.create', ['pcd' => $pcd]) }}">{{ $title }}新規追加</a> / <!--<a href="./form_config.php">{{ $title }}検索条件変更</a> / --><a href="{{ $family->route('forms.index', ['pcd' => $pcd]) }}">{{ $title }}登録一覧</a>
@endunless

<div class="contents">
<p class="h2Title">■{{ $title }}{{ $mode }}@unless ($customer) - {{ $siteName }}@endunless</p>

<p><span class="attention">※</span>マークがある項目は、必須項目です。（空白にはしないで下さい）</p>

<form method="post" name="form1" id="form1" class="form" action="{{ $action }}">
<fieldset>
<legend>{{ $title }}</legend>
<table class="tableBox">

	<tr>
		<th><label for="name">項目タイトル<span class="attention">※</span></label></th>
		<td @if (isset($errors['name'])) class="Ng" @endif><input type="text" name="name" id="name" class="writeArea" value="{{ $v('name') }}" />
		{!! $err('name') !!}
		</td>
	</tr>

	<tr>
		<th><label for="unit">単位</label></th>
		<td><input type="text" name="unit" id="unit" class="writeArea" value="{{ $v('unit') }}" />
		</td>
	</tr>

	<tr>
		<th><label for="comment">コメント</label></th>
		<td><textarea name="comment" id="comment" class="writeArea" rows="3" onkeyup="resize_textarea(event)">{{ $v('comment') }}</textarea>
		</td>
	</tr>

	<tr>
		<th><label for="form_type">項目タイプ</label></th>
		<td @if (isset($errors['flds_row'])) class="Ng" @endif>

<select name="form_type" onChange="disp(this)">
@foreach (\App\Services\Promotions\AnketoItemService::TYPES as $t)
<option value="{{ $t }}"@if ($type === $t) selected @endif>{{ $t }}</option>
@endforeach
</select>


<span id="optionDisp">@if ($options)
オプション数:<input type="text" name="flds_row" id="flds_row" class="inputS" value="{{ $v('flds_row') }}" />
<input type="button" onClick="addForms(document.getElementById('flds_row').value,'{{ $type }}')" value="オプション数をセット">
@endif</span>




	{!! $err('flds_row') !!}
	</td>
	</tr>



	<tr>
		<th>項目設定</th>
		<td>
<div id="eachformData">
@if ($options && (int) $v('flds_row') > 0)
@for ($i = 0; $i < (int) $v('flds_row'); $i++)
表示値：<input type="text" name="eachform{{ $i }}" id="eachform{{ $i }}" onchange="document.getElementById('eachsend{{ $i }}').value=this.value" class="inputM" value="{{ $v('eachform'.$i) }}" />
<div style="display:none">送信値：<input type="text" name="eachsend{{ $i }}" id="eachsend{{ $i }}" class="inputM" value="{{ $v('eachsend'.$i) }}" /></div><br />
@endfor
@endif
</div>

		</td>
	</tr>

	<tr>
		<th><label for="style_class">表示仕様</label></th>
		<td>
		<select name="style_class">
		{{ \App\Services\Customers\CustomFieldService::styleOptions($v('style_class')) }}
		</select>
		<span class="attention">※実際の見た目は、表示環境に依存します。</span>
		</td>
	</tr>

</table>
</fieldset>


<!--基本情報table start-->



<a href="{{ $back }}" class="backBtnStyle">{{ $customer ? '　戻る　' : '一覧に戻る' }}</a>


<div class="btnBox">
<input type="hidden" name="cd" id="cd" value="{{ $cd }}" />
<input type="hidden" name="act" value="up">
<input type="hidden" name="pcd" value="{{ $pcd }}">
<input type="hidden" name="page" value="{{ $page }}">
<input type="submit" name="enter" id="enter" class="btn btnStyleM" value="{{ $customer ? '登録する' : $title . '登録' }}">
</div>

</form>

<!-- / .contents --></div>
@endsection
