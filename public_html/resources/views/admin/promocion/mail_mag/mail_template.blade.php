{{-- テンプレート管理 — legacy: system/category3/mail_mag/mail_template.php --}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', 'テンプレート管理' . ' - ' . $siteName)

@section('css')
<link rel="stylesheet" type="text/css" href="{{ asset('legacy/system/css/popInline.css') }}">
@endsection

@section('js')
<script type="text/javascript" src="{{ asset('legacy/system/js/popInline.js') }}"></script>
<script type="text/javascript">
function statusControl(cate, type, target_cd, vl){

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

	$(".tableDisp").load("{{ route('legacy.promocion.mail_mag.ajax_mail_template') }}",{
		send_cate: cate,
		send_type:type,
		send_targetCd:target_cd,
		send_vl:vl
	});

}

$(document).ready(function() {
	//ポップアップ用
	popIni();

	$("#categoryBtn").click(function(){
		@if ($isTablet ?? false)
			roid.popUp('{{ route('admin.promocion.template-categories.index') }}');
		@else
			openPopWindow("{{ route('admin.promocion.template-categories.index') }}", '400', '');
		@endif
	});

	$("#newTemplateBtn").click(function(){
		@if ($isTablet ?? false)
			roid.popUp('{{ route('admin.promocion.templates.entry') }}');
		@else
			openPopWindow("{{ route('admin.promocion.templates.entry') }}", '650', '');
		@endif
	});

	//一覧をajaxでロードする処理
	statusControl("");

	//select選択でカテゴリ毎の表示用
	$("#categorySelect").change(function(){
		statusControl($(this).val());
	});
});
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■テンプレート管理</p>


<input type="button" id="categoryBtn" class="btnStyleS" value="カテゴリ管理" />
<input type="button" id="newTemplateBtn" class="btnStyleS" value="テンプレート新規登録" />

<p class="h3Title">テンプレート一覧</p>

@if ($categories->isNotEmpty())
<select name="categorySelect" id="categorySelect">
<option value="">全て</option>
@foreach ($categories as $category)
<option value="{{ $category->cd }}">{{ $category->name }}</option>
@endforeach
</select>
@else
カテゴリーが存在しません。
@endif

<div class="tableDisp"></div>


<!-- / .contents -->
</div>
@endsection
