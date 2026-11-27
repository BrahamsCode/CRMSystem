{{--
    Legacy: system/category9/yoyaku/yoyaku_koumoku_category.php (予約項目カテゴリ管理, ventana emergente sin yoyaku_navi).
    Variables:
      $category  ?ReservationMenuCategory  categoría que se edita (?cd=) o null en el alta
    La lista (ajax_yoyaku_koumoku_category.php) se carga por AJAX: admin.reservas.menu-categories.list,
    .sort, .toggle y .destroy (devuelven la lista actualizada).
--}}
@extends('layouts.legacy-popup')

@section('title', '予約項目カテゴリ管理')

@section('css')
<link rel="stylesheet" type="text/css" href="{{ asset('legacy/system/css/popInline.css') }}">

<style type="text/css"><!--

body {
	background: #FFFFFF;
}

--></style>
@endsection

@section('js')
<script type="text/javascript">

function submitChk(){

	if(!$("#name").val()){
		alert("入力がありません。");
		return false;
	}

	var flg = confirm ( "この内容で登録してもよろしいですか？\n\n書き直す場合は['キャンセル']ボタンを押して下さい");
	return flg;
}

// Legacy: .load("ajax_yoyaku_koumoku_category.php", {send_type, send_targetCd, send_vl}).
// Ahora cada acción tiene su ruta (admin.reservas.menu-categories.list / .sort / .toggle / .destroy).
var categoryListUrl = @json(route('admin.reservas.menu-categories.list'));
var categoryUrls = {
	sort: @json(route('admin.reservas.menu-categories.sort', '__CD__')),
	toggle: @json(route('admin.reservas.menu-categories.toggle', '__CD__')),
	destroy: @json(route('admin.reservas.menu-categories.destroy', '__CD__'))
};

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

	var url = categoryListUrl;
	var data = {send_type:type, send_targetCd:target_cd, send_vl:vl};
	if(type=="rankup" || type=="rankdown"){
		url = categoryUrls.sort.replace("__CD__", target_cd);
	}else if(type=="hyouji"){
		url = categoryUrls.toggle.replace("__CD__", target_cd);
	}else if(type=="del"){
		url = categoryUrls.destroy.replace("__CD__", target_cd);
		data._method = "DELETE";
	}

	$(".tableDisp").load(url, data);

}

function rHref(val){
	location.href = @json(route('admin.reservas.menu-categories.index')) + "?cd="+val;
}



$(document).ready(function(){
	statusControl("");
});
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■予約項目カテゴリ管理</p>
<p class="h3Title">{{ $category ? '編集' : '新規登録' }}</p>
<form action="{{ $category ? route('admin.reservas.menu-categories.update', $category->cd) : route('admin.reservas.menu-categories.store') }}" method="post" name="form1" id="form1" class="form" onsubmit="return submitChk()">
@csrf
@if ($category) @method('PUT') @endif


<table class="tableBox">

<tr>
<th>名称</th>
<td><input type="text" name="name" id="name" class="inputL" value="{{ old('name', $category?->name) }}" /></td>
</tr>

</table>

<div class="center" style="margin-bottom: 20px;"><input type="submit" value="登録" /></div>
@if ($category)
<input type="hidden" name="cd" value="{{ $category->cd }}" />
@endif
</form>

<div class="tableDisp"></div>

<div class="btnBox" style="text-align:left;">
<input type="button" value="　戻る　" onclick="location.href='{{ route('admin.reservas.menus.index') }}';" />
</div>

<!-- / .contents -->
</div>
@endsection
