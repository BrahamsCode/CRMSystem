{{-- テンプレートカテゴリ管理 (popup) — legacy: system/category3/mail_mag/mail_template_category.php --}}
@extends('layouts.legacy-popup')

@section('title', 'テンプレートカテゴリ管理' . ' - ' . $siteName)

@section('css')
<link href="{{ asset('legacy/system/css/import.css') }}" rel="stylesheet" type="text/css" media="all" />
<link rel="stylesheet" type="text/css" href="{{ asset('legacy/system/css/popInline.css') }}">
<style type="text/css"><!--

body {
	background: #FFFFFF;
}

--></style>
@endsection

@section('js')
<script type="text/javascript" src="{{ asset('legacy/system/js/scrollsmoothly.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/js/default.js') }}"></script>
<script type="text/javascript">

function submitChk(){

	if(!$("#name").val()){
		alert("入力がありません。");
		return false;
	}

	var flg = confirm ( "この内容で登録してもよろしいですか？\n\n書き直す場合は['キャンセル']ボタンを押して下さい");
	return flg;
}

function statusControl(type, target_cd, vl){

	if(type=="hyouji"){
		var ret = confirm("表示フラグを変更しますか？");
		if(ret == false){
			type = "";
		}
	}

	if(type=="del"){
		var ret = confirm("本当に削除してよろしいですか？");
		if(ret == false){
			type = "";
		}
	}

	$(".tableDisp").load("{{ route('legacy.promocion.mail_mag.ajax_mail_template_category') }}",{
		send_type:type,
		send_targetCd:target_cd,
		send_vl:vl
	});

}

function rHref(val){
	location.href = "{{ route('admin.promocion.template-categories.index') }}?cd="+val;
}

$(document).ready(function(){
	statusControl("");
});
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■テンプレートカテゴリ管理</p>


<p class="h3Title">{{ $category ? '編集' : '新規登録' }}</p>

<form action="{{ route('legacy.promocion.mail_mag.mail_template_category_register') }}" method="post" name="form1" id="form1" class="form" onsubmit="return submitChk()">
@csrf
<table class="tableBox">

<tr>
<th>名称</th>
<td><input type="text" name="name" id="name" class="inputL" value="{{ $category?->name }}" /></td>
</tr>

</table>

<div class="center" style="margin-bottom: 20px;"><input type="submit" class="btnStyleM" value="登録" /></div>
@if ($category)
<input type="hidden" name="cd" value="{{ $category->cd }}" />
@endif
</form>

<div class="tableDisp"></div>

<!-- / .contents -->
</div>
@endsection
