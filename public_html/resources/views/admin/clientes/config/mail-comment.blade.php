{{-- Legacy: system/category1/config/mail_comment.php (メールコメント; sin enlace en el menú). El registro
     (mail_comment_register.php) es admin.clientes.mail-comment.update.
     Variables: $siteName, $entryComment / $exitComment (nyushitsu_komento / taishitsu_komento de tb_felica_mode cd = 2,
       guardados con get_form2(): se imprimen sin volver a escapar) --}}
@extends('layouts.legacy')

@section('menu', 'clientes')
@section('title', 'メールコメント管理 - ' . $siteName)

@section('js')
<script type="text/javascript">
$(document).ready(function() {

	$(".ac3").accordion('option', 'active', 0);

});

</script>

<script type="text/javascript">

function submitChk () {
	if(document.form1.nyushitsu_komento.value == ""){
		alert("入室コメントが空白です。\n\n入室コメントをご記入下さい。");
		return false;
	}

	if(document.form1.taishitsu_komento.value == ""){
		alert("退室コメントが空白です。\n\n退室コメントをご記入下さい。");
		return false;
	}

	var flg = confirm ( "この内容で登録してもよろしいですか？\n\n書き直す場合は[キャンセル]ボタンを押して下さい");
	return flg;

}

</script>
@endsection

@section('content')
<div class="contents">

<p class="h2Title">■メールコメント登録</p>


<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.clientes.mail-comment.update') }}" onsubmit="return submitChk()">
@csrf


<fieldset>
<legend>メールコメント情報</legend>

<p>※{生徒名}と入力された箇所には生徒の名前が挿入されます。<br />
※{時間}と入力された箇所には時間が挿入されます。</p>

<table class="tableBox">

<tr>
<th>入室コメント</th>
<td>
	<textarea name="nyushitsu_komento" id="nyushitsu_komento" class="writeArea" maxlength="200" rows="3">{!! $entryComment !!}</textarea>
</td>
</tr>

<tr>
<th>退室コメント</th>
<td>
	<textarea name="taishitsu_komento" id="taishitsu_komento" class="writeArea" maxlength="200" rows="3">{!! $exitComment !!}</textarea>
</td>
</tr>

</table>

</fieldset>



<div class="btnBox">
<input type="submit" name="enter" id="reivse" value="この内容で登録する" />
<input type="hidden" name="act" id="act" value="new" />
</div>

</form>

<!-- / .contents --></div>
@endsection
