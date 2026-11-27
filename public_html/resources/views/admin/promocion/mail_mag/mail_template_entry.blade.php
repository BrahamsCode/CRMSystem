{{-- テンプレート登録 (popup) — legacy: system/category3/mail_mag/mail_template_entry.php --}}
@extends('layouts.legacy-popup')

@section('title', 'テンプレートカテゴリ管理')

@section('css')
<link href="{{ asset('legacy/system/css/import.css') }}" rel="stylesheet" type="text/css" media="all" />
<style type="text/css"><!--

body {
	background: #FFFFFF;
}

--></style>
@endsection

@section('js')
<script type="text/javascript" src="{{ asset('legacy/system/js/scrollsmoothly.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/js/default.js') }}"></script>

<!-- TinyMCE -->
<script type="text/javascript" src="{{ asset('legacy/system/category3/decomail/js/tiny_mce/tiny_mce.js') }}"></script>
<script type="text/javascript">
	tinyMCE.init({
		mode : "textareas",
		theme : "advanced",
		editor_deselector : "mceNoEditor",
		plugins : "inlinepopups,paste,emojidocomo,emojiau,emojisoftbank",
		theme_advanced_buttons1 : "forecolor,separator,justifyleft,justifycenter,justifyright,separator,link,unlink",
		theme_advanced_buttons2 : "emojidocomo,emojiau,emojisoftbank,emotions",
		theme_advanced_buttons3 : "",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",

		//spanをfont、divに変換
		convert_fonts_to_spans : false,
		formats : {
			forecolor : {inline : 'font',attributes : {color : '%value'}}, //フォントカラー
			fontsize : {inline : 'font',attributes : {size : '%value'}} //フォントサイズ
		},

		external_image_list_url : "js/image_list.js",
		extended_valid_elements : "td[*],div[*]",
		language:"ja",
		height : '400px'
	});
</script>
<!-- /TinyMCE -->

<script type="text/javascript">

function submitChk(){
	var errorCheck = 0;
	var errorTxt = "";

	if($("#tb_mail_template_category_cd").val() == ""){
		errorCheck = 1;
		errorTxt += "カテゴリ,";
	}

	if($("#title").val() == ""){
		errorCheck = 1;
		errorTxt += "タイトル,";
	}

	if($(".selectMailType:checked").length < 1){
		errorCheck = 1;
		errorTxt += "タイプ,";
	}

	if($(".selectMailType:checked").val() == "" && $("#naiyou").val() == ""){
		errorCheck = 1;
		errorTxt += "内容,";
	}

	if(errorCheck == 1){
		alert(errorTxt+"が入力されていません。");
		return false;
	}


	var flg = confirm ( "この内容で登録してもよろしいですか？\n\n書き直す場合は['キャンセル']ボタンを押して下さい");
	return flg;
}


function selectMailTypeFunc(){
	$(".selectMailType").change(function(){
		selectMailTypeFuncCore();
	});
}

function selectMailTypeFuncCore(){
	var target = $("input:radio:checked");
	if(target.length > 0){
		if(target.val() == "deco"){
			$(".decoMailArea").css("display", "");
			$(".normalMailArea").css("display", "none");
		}

		if(target.val() == ""){
			$(".decoMailArea").css("display", "none");
			$(".normalMailArea").css("display", "");
		}
	}
}


$(document).ready(function(){
	selectMailTypeFunc();
	selectMailTypeFuncCore();


	$("#popCloseBtn").click(function(){
		@if ($isTablet ?? false)
		roid.windowClose();
		@else
		window.parent.popUpWinClose("");
		@endif
	});
});

@if ((int) request('comp') === 1)
	//登録して帰ってきたときの処理
	@if ($isTablet ?? false)
	roid.windowClose();
	@else
	window.parent.popUpWinReloadClose("");
	@endif
@endif
</script>
@endsection

@section('content')
@php
	$readonly = ' type="text" readonly="readonly" style="background:#FFF; border: 1px solid #999; width: 8em; text-align: center;" onclick="this.select(0,this.value.length)"';
	$mailType = (string) ($template?->mail_type ?? '');
@endphp
<div class="contents" style="margin-bottom: 0px;">
<p class="h2Title">■テンプレート登録</p>


@if ($categories->isNotEmpty())


<form action="{{ route('legacy.promocion.mail_mag.mail_template_register') }}" method="post" name="form1" id="form" class="form" onsubmit="return submitChk()">
@csrf
<table class="tableBox8" style="margin-bottom: 5px;">

<tr>
<th width="75">カテゴリ*</th>
<td>

<select name="tb_mail_template_category_cd" id="tb_mail_template_category_cd" class="selectBox">
<option value="">&nbsp;</option>
@foreach ($categories as $category)
<option value="{{ $category->cd }}"@selected((string) ($template?->tb_mail_template_category_cd) === (string) $category->cd)>{{ $category->name }}</option>
@endforeach
</select>

</td>
</tr>

<tr>
<th>タイプ*</th>
<td>
<input type="radio" name="mail_type" id="mail_type1" class="checkBtn selectMailType" value=""@checked($mailType === '') /><label for="mail_type1">通常</label><br />
<input type="radio" name="mail_type" id="mail_type2" class="checkBtn selectMailType" value="deco"@checked($mailType === 'deco') /><label for="mail_type2">デコ</label>
</td>
</tr>

<tr>
<th>タイトル*</th>
<td><input type="text" name="title" id="title" class="inputL" value="{{ $template?->title }}" /></td>
</tr>

<tr>
<th>内容*</th>
<td>
	<p>
	{{-- 置き換えタグ: 名前, 苗字 (設定時), ID, 誕生月日, 住所, 電話番号, スタンプ数・ポイント数・最終来店日・ランク (機能が有効な場合), 追加項目 --}}
	@foreach ($replaceTags as $tag)
	※ <font color="black" style="font-weight: bold;"><input value="{{ '{' . $tag['word'] . '}' }}"{!! $readonly !!} /></font> と入力された箇所には顧客の<font color="red">{{ $tag['label'] }}</font>が挿入されます。<br />
	@if (! empty($tag['note']))
	　 {{ $tag['note'] }}<br />
	@endif
	@endforeach
	</p>
<div class="normalMailArea">
<textarea name="naiyou" id="naiyou" class="mceNoEditor" cols="30" rows="20">{{ $mailType === '' ? $template?->naiyou : '' }}</textarea><br />
</div>
<div class="decoMailArea">
<textarea name="naiyou_deco" id="naiyou_deco" style="width: 15em;" cols="30" rows="20">{{ $mailType === 'deco' ? $template?->naiyou : '' }}</textarea><br />
</div>
	※ 他ページで表示されている文章をコピー&ペーストなどで張り付けた場合、改行などが行われないなどの挙動が発生する場合がありますのでご注意ください。
</td>
</tr>


</table>

<div class="center" style="margin-bottom: 5px;">
<input type="submit" class="btnStyleM" value="この内容で登録する" />
<input type="button" id="popCloseBtn" class="btnStyleM" value="閉じる" />
</div>
@if ($template)
<input type="hidden" name="cd" value="{{ $template->cd }}" />
@endif
</form>

@else
<span style="color: #EE0000;">
カテゴリ登録がありません。<br />
カテゴリの登録を行って下さい。</span>
<input type="hidden" name="tb_mail_template_category_cd" id="tb_mail_template_category_cd" value="" />
@endif



<!-- / .contents -->
</div>
@endsection
