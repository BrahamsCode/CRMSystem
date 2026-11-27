{{-- 権限項目管理 (popup de alta/edición en iframe): system/category0/config/authority_contents.php --}}
@extends('layouts.legacy-popup')

@section('title', '権限項目管理 - ' . $siteName)

@section('css')
<link href="/system/css/import.css" rel="stylesheet" type="text/css" media="all" />

<style type="text/css"><!--

#container {
	min-width: 450px;
}

.main1 {
	background: none;
}

--></style>
@endsection

@section('js')
<script type="text/javascript" src="/system/js/scrollsmoothly.js"></script>
<script type="text/javascript" src="/system/js/iepngfix.js"></script>
{{-- el legacy volvía a cargar jquery-1.3.2 aquí; ya lo carga el <head> y recargarlo quitaría el token CSRF de $.ajaxSetup --}}
<script type="text/javascript">

function submitChk () {
	if (document.form1.type.value == ""){

		alert("タイプを選択してください。");
		return false;

	} else if (document.form1.name.value == "") {

		alert("項目名を記入してください。");
		return false;

	} else if (document.form1.tb_base_system_cd.value == "") {

		alert("大カテゴリーを選択してください。");
		return false;

	} else {

		var flg = confirm ( "この内容で登録してもよろしいですか？\n\n書き直す場合は['キャンセル']ボタンを押して下さい");

		if (flg) {
			var sendData = $("#form1").serialize();
			$.ajax({
				type: "POST",
				url: "ajax_authority_contents_register.php",
				data: sendData,
				success: function(resp){
					alert (resp);
					parent.location.href = 'authority_contents_list.php';
				}
			});
		}

		return false;
	}
}

</script>
@endsection

@php
    $isEdit = $data !== null;
    $type = $isEdit && (int) $data->cd < 1000 ? '1' : '2';
@endphp

@section('content')
<div class="contents">

<form method="post" name="form1" id="form1" class="form" action="" onsubmit="return submitChk()">

<fieldset>
<legend>権限項目情報</legend>

<table class="tableBox">

<tr>
<th>タイプ</th>
<td>
@if ($isEdit)
	{{ $type === '1' ? 'フォーム項目タイプ' : '機能タイプ' }}
	<input type="hidden" name="type" id="type" value="{{ $type }}" />
@else
	<select name="type" id="type">
		<option value="">▼タイプ選択</option>
		<option value="1">フォーム項目タイプ</option>
		<option value="2">機能タイプ</option>
	</select>
@endif
</td>
</tr>

<tr>
	<th>項目名</th>
	<td><input type="text" name="name" id="name" class="writeArea" value="{{ $data?->name }}" /></td>
</tr>

<tr>
<th>大カテゴリー</th>
<td>
	<select name="tb_base_system_cd">
	<option value="">▼大カテゴリー選択</option>
	@foreach ($baseSystems as $system)
	<option value="{{ $system->cd }}"@if ($isEdit && (string) $system->cd === (string) $data->tb_base_system_cd) selected="selected"@endif >{{ $system->system_name }}</option>
	@endforeach
	</select>
</td>
</tr>

<tr>
<th>説明</th>
<td>
	<textarea name="comment" id="comment" class="writeArea" rows="4" style="width: 15em;">
{{ $data?->comment }}</textarea>
</td>
</tr>

<tr>
<th>表示</th>
<td>
	<input type="checkbox" name="hyouji_flg" id="hyouji_flg" value="1" {!! $data?->hyouji_flg === '1' ? ' checked="checked"' : '' !!} />
</td>
</tr>

</table>

</fieldset>

<div class="btnBox">
@if ($isEdit)
<input type="submit" name="enter" id="revise" value="この内容で修正する" />
<input type="hidden" name="act" id="act" value="up" />
<input type="hidden" name="cd" id="cd" value="{{ $data->cd }}" />
@else
<input type="submit" name="enter" id="reivse" value="この内容で登録する" />
<input type="hidden" name="act" id="act" value="new" />
@endif
</div>

</form>

<!-- / .contents --></div>
@endsection
