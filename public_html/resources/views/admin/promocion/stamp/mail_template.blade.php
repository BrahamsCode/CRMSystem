{{--
	Plantilla de aviso de sellos (popup) — legacy: category3/stamp/stamp_{alert,shikkou}_mail_template_entry.php
	Variables: $type, $heading, $title, $body, $completed, $surnameEnabled, $stampEnabled, $visitsEnabled
	閉じる cierra con popUpWinClose; tras guardar (comp=1) recarga el padre con popUpWinReloadClose, como el legacy.
--}}
@extends('layouts.legacy-popup')

@section('title', 'メールテンプレート' . ' - ' . $siteName)

@section('css')
<style type="text/css">
body {
	background: #FFFFFF;
}
</style>
@endsection

@section('js')
<script type="text/javascript">
function submitChk(){
	var errorCheck = 0;
	var errorTxt = "";

	if($("#title").val() == ""){
		errorCheck = 1;
		errorTxt += "タイトル,";
	}
	if($("#naiyou").val() == ""){
		errorCheck = 1;
		errorTxt += "内容,";
	}
	if(errorCheck == 1){
		alert(errorTxt+"が入力されていません。");
		return false;
	}

	return confirm ( "この内容で登録してもよろしいですか？\n\n書き直す場合は['キャンセル']ボタンを押して下さい");
}

$(document).ready(function(){
	$("#popCloseBtn").click(function(){
		@if ($isTablet) roid.windowClose(); @else window.parent.popUpWinClose(""); @endif
	});
});
@if ($completed)
@if ($isTablet) roid.windowClose(); @else window.parent.popUpWinReloadClose(""); @endif
@endif
</script>
@endsection

@php
	$readonly = ' type="text" readonly="readonly" style="background:#FFF; border: 1px solid #999; width: 6em; text-align: center;" onclick="this.select(0,this.value.length)"';
	$tag = fn (string $t) => '※ <font color="black" style="font-weight: bold;"><input value="{' . $t . '}"' . $readonly . ' /></font>';
@endphp

@section('content')
<div class="contents" style="margin-bottom: 0px;">
<p class="h2Title">{{ $heading }}</p>

<form action="{{ route('admin.promocion.stamps.template.update', ['type' => $type]) }}" method="post" name="form1" id="form" class="form" onsubmit="return submitChk()">
@csrf
<table class="tableBox8" style="margin-bottom: 5px;">

<tr>
<th width="75">タイトル<font color="red">*</font></th>
<td><input type="text" name="title" id="title" class="inputL" value="{{ $title }}" /></td>
</tr>

<tr class="normalMailArea">
<th>内容<font color="red">*</font></th>
<td>
	<p>{!! $tag('名前') !!} と入力された箇所には顧客の<font color="red">名前</font>が挿入されます。<br />
	@if ($surnameEnabled)
	{!! $tag('苗字') !!} と入力された箇所には顧客の<font color="red">苗字</font>が挿入されます。<br />
	@endif
	{!! $tag('ID') !!} と入力された箇所には顧客の<font color="red">ID番号</font>が挿入されます。<br />
	{!! $tag('誕生月日') !!} と入力された箇所には顧客の<font color="red">誕生月日</font>が挿入されます。<br />
	{!! $tag('住所') !!} と入力された箇所には顧客の<font color="red">住所</font>が挿入されます。<br />
	{!! $tag('電話番号') !!} と入力された箇所には顧客の<font color="red">電話番号</font>が挿入されます。<br />
	@if ($stampEnabled)
	{!! $tag('スタンプ数') !!} と入力された箇所には顧客の<font color="red">スタンプ数</font>が挿入されます。<br />
	@endif
	@if ($visitsEnabled)
	{!! $tag('最終来店日') !!} と入力された箇所には顧客の<font color="red">最終来店</font>が挿入されます。<br />
来店履歴が無い場合はそのまま表示されてしまいますので、ご注意ください。<br />(フォローメール推奨)
	@endif
	</p>
<textarea name="naiyou" id="naiyou" class="mceNoEditor" cols="30" rows="20">{{ $body }}</textarea><br />
	※ 他ページで表示されている文章をコピー&amp;ペーストなどで張り付けた場合、改行などが行われないなどの挙動が発生する場合がありますのでご注意ください。
</td>
</tr>

</table>

<div class="center" style="margin-bottom: 5px;">
<input type="submit" value="この内容で登録する" />
<input type="button" id="popCloseBtn" value="　閉じる　" />
</div>

<!-- / .contents -->
</div>
@endsection
