{{-- 店舗グループ: system/category0/shop/shop_category.php (+ shop_category_frm.php) --}}
@extends('layouts.legacy')

@section('title', '店舗グループ管理 - ' . $siteName)

@section('menu', 'base')

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac2").accordion('option', 'active', 0);
});
</script>

<script type="text/javascript">

function submitChk () {

	if(document.form1.title.value == ""){
		alert("店舗グループ名が空白です。\n\n店舗グループ名をご記入下さい。");
		return false;
	}

	var flg = confirm ( "この内容で登録してもよろしいですか？\n\n書き直す場合は['キャンセル']ボタンを押して下さい");
	return flg;
}


function submitChk2 (val) {
	element = eval("document.form_" + val);

	var flg = confirm ( "削除してもよろしいですか？\n\n削除しない場合は['キャンセル']ボタンを押して下さい");

	if(flg){
		element.submit();
	}
}

</script>
@endsection

@section('content')
<div class="contents">

<p class="h2Title">■店舗グループ 登録</p>


<form method="post" name="form1" id="form1" class="form" action="shop_category.php" onsubmit="return submitChk()">


<fieldset>
<legend>店舗グループ情報</legend>

<table class="tableBox">

<tr>
<th>店舗グループ名</th>
<td>
<input type="text" name="title" id="title" class="writeArea" value="{{ $title }}" />
</td>
</tr>

</table>

</fieldset>

<div class="btnBox">
@if ($edit)
<input type="submit" name="enter" id="revise" class="btnStyleM" value="この内容で修正する" />
<input type="hidden" name="act" value="up" />
<input type="hidden" name="cd" id="cd" value="{{ $edit->cd }}" />
@else
<input type="submit" name="enter" id="reivse" class="btnStyleM" value="この内容で登録する" />
<input type="hidden" name="act" value="new" />
@endif
</div>

</form>



<!-- / .contents --></div>

<div class="contents">
<p class="h2Title">■店舗グループ一覧</p>
<div class="form">
@if ($rows->isNotEmpty())

<p class="textCenter">{{ $rows->count() }}件</span>の該当結果を表示しています。</p>

<table class="tableBox2">
	<tr>
		<th>店舗グループ名</th>
		<th>修正・削除</th>
		<th>並び</th>
	</tr>

@foreach ($rows as $i => $row)
	<tr{!! $i % 2 != 0 ? ' class="tableBg"' : '' !!}>
	<td>{{ $row->title }}</td>
	<td class="textCenter" width="100">
	<a href="shop_category.php?cd={{ $row->cd }}" class="btnStyle">修正</a>
	<a href="javascript:void(0)" onclick="submitChk2({{ $i + 1 }}); return false;" class="btnStyle">削除</a>

	<form method="post" name="form_{{ $i + 1 }}" id="form_{{ $i + 1 }}" class="displayNone" action="./shop_category.php">
	<input type="hidden" name="act" class="btn" value="del">
	<input type="hidden" name="cd" class="btn" value="{{ $row->cd }}">
	<input type="submit" name="delete" class="btn" value="削除する">
	</form>
	</td>

	<td style="width: 3em;">{{ $i + 1 }}
	<ul style="paddng:0px;margin:0px;float:right;line-height:13px;font-size:12px;width:1em">
	<li><a href="#" onclick="location.href='shop_category.php?page=&kensu=&thisCd={{ $row->cd }}&thisRow={{ $row->sort }}&kind=up&page=&pcd='">▲</a></li>
	<li><a href="#" onclick="location.href='shop_category.php?page=&kensu=&thisCd={{ $row->cd }}&thisRow={{ $row->sort }}&kind=down&page=&pcd='">▼</a></li>
	</ul>

	</td>
  </tr>
@endforeach

</table>
@else
店舗グループは登録されていません。
@endif

<ul class="btnBox2">
</ul>

</div>

<!-- / .contents --></div>
@endsection
