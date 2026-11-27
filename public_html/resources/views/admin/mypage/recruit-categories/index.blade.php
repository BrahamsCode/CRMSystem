{{--
    求人職種管理 (system/category6/recruit/recruit_category.php): formulario de 職種 (alta o ?cd= modificación)
    y lista paginada.

    CONTRATO DE DATOS
        $category           RecruitCategory|null  el 職種 en modificación
        $editing            bool    llegó ?cd=
        $shops              Collection<Shop>  tb_shop disp_flg <> '9' ORDER BY cd; sin マスター solo la de la sesión
        $singleShop         Shop|null  una sola tienda o sin マスター: nombre + hidden
        $showAllShopsOption bool    マスター: añade <option value="-1">全店舗</option>
        $shopNames          Collection [cd => kaishamei] para la columna 店舗 de la lista
        $pager              array   LegacyPageControl::paginate (items = RecruitCategory, kensu 20 por defecto)
        $service            RecruitCategoryService (inUse: con 求人 activos no se ofrece 削除)
        $siteName           string  (MypageServiceProvider)

    Diferencia con el legacy: la columna 店舗 salía vacía para los 職種 de una tienda (el array de nombres
    se llenaba con la clave mal escrita `cd]`); aquí muestra el nombre.
--}}
@extends('layouts.legacy')

@section('title', '職種管理 - ' . $siteName)

@section('menu', 'mypage')

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac1").accordion({ collapsible: true , active: false});
	$(".ac2").accordion({ collapsible: true , active: false});
	$(".ac3").accordion({ collapsible: true , active: false});
	$(".ac4").accordion({ collapsible: true , active: false});
	$(".ac5").accordion({ collapsible: true , active: false});
	$(".ac6").accordion({ collapsible: true , active: false});
	$(".ac7").accordion({ collapsible: true , active: false});
	$(".ac8").accordion({ collapsible: true , active: false});
	$(".ac3").accordion('option', 'active', 0);
});



function submitChk () {
	if(document.form1.shop_cd.value == ""){
		alert("店舗を選択して下さい。");
		return false;
	}

	if(document.form1.cate_name.value == ""){
		alert("職種名が空白です。\n\n職種名をご記入下さい。");
		return false;
	}

	var flg = confirm ( "この内容で登録してもよろしいですか？\n\n書き直す場合は[キャンセル]ボタンを押して下さい");
	return flg;
}


function submitChk2 (val) {
	element = eval("document.form_" + val);

	var flg = confirm ( "削除してもよろしいですか？\n\n削除しない場合は[キャンセル]ボタンを押して下さい");

	if(flg){
		element.submit();
	}
}
</script>
@endsection

@section('content')
@php
    $selectedShop = $category === null ? null : ($category->tb_shop_cd === null ? '-1' : (string) $category->tb_shop_cd);
@endphp
<div class="contents">

<p class="h2Title">■職種登録</p>


<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.mypage.recruit-categories.store') }}" onsubmit="return submitChk()">
@csrf


<fieldset>
<legend>職種情報</legend>

<table class="tableBox">
<tr>
<th><label for="shop_cd">登録店舗<em>※</em></label></th>
<td>
@if ($singleShop !== null)
{{ $singleShop->kaishamei }}
<input type="hidden" name="shop_cd" value="{{ $singleShop->cd }}">
@else
<select name="shop_cd">
@foreach ($shops as $shop)
<option value="{{ $shop->cd }}" @if ($selectedShop === (string) $shop->cd) selected="selected"@endif>{{ $shop->kaishamei }}</option>
@endforeach
@if ($showAllShopsOption)
<option value="-1" @if ($selectedShop === '-1') selected="selected"@endif>全店舗</option>
@endif
</select>
@endif
</td>
</tr>

<tr>
<th><label for="cate_name">職種名<em>※</em></label></th>
<td>
<input type="text" name="cate_name" id="cate_name" class="writeArea" value="{{ $category?->shokushu }}" />
</td>
</tr>

</table>

</fieldset>

<div class="btnBox">
@if ($editing)
<input type="submit" name="enter" id="revise" value="この内容で修正する" />
<input type="hidden" name="act" id="act" value="up" />
<input type="hidden" name="cd" id="cd" value="{{ $category?->cd }}" />
@else
<input type="submit" name="enter" id="reivse" value="この内容で登録する" />
<input type="hidden" name="act" id="act" value="new" />
@endif
</div>

</form>



<!-- / .contents --></div>

<div class="contents">
<p class="h2Title">■職種一覧</p>
<div class="form">
@if ($pager['total'] > 0)
@php
    $dispMaxKensu = min($pager['from'] + $pager['kensu'], $pager['total']);
@endphp
<p><span class="attention">{{ $pager['from'] + 1 }}件～{{ $dispMaxKensu }}件目/{{ $pager['total'] }}件中</span><br />表示ページでは、当ページの順番で該当店舗の情報が表示され、登録・更新したものが一番上に移動します。</p>

<table class="tableBox2">
  <tr>
    <th>No</th>
    <th>職種名</th>
    <th>店舗</th>
    <th>修正・削除</th>
  </tr>


@foreach ($pager['items'] as $row)
@php($memCnt = $loop->iteration)
@if ($loop->index % 2 != 0)
  <tr class="tableBg">
@else
  <tr>
@endif
    <td>{{ $row->cd }}</td>
    <td>{{ $row->shokushu }}</td>
    <td>{{ $row->tb_shop_cd === null ? '全店舗' : ($shopNames[$row->tb_shop_cd] ?? '') }}
    </td>
    <td class="textCenter" width="100">
    	<a href="{{ route('admin.mypage.recruit-categories.index', ['cd' => $row->cd, 'kensu' => $pager['kensu'], 'page' => $pager['page']]) }}"><img src="{{ asset('legacy/system/img/btn_revise_off.gif') }}" width="40" height="20" alt="修正" /></a>
@if (! $service->inUse($row))
		<a href="javascript:void(0)" onclick="submitChk2({{ $memCnt }}); return false;"><img src="{{ asset('legacy/system/img/btn_delete_off.gif') }}" width="40" height="20" alt="削除" /></a>

<form method="post" name="form_{{ $memCnt }}" id="form_{{ $memCnt }}" class="displayNone" action="{{ route('admin.mypage.recruit-categories.destroy') }}">
@csrf
<input type="hidden" name="delete_flg" id="delete" class="btn" value="del">
<input type="hidden" name="cd" class="btn" value="{{ $row->cd }}">
<input type="submit" name="delete" id="delete" class="btn" value="削除する">
</form>
@endif
  </td>
</tr>

@endforeach

</table>
<p class="right">※掲載中の記事があるカテゴリは削除できませんのでご注意ください。</p>
@else
職種は登録されていません。
@endif

<ul class="btnBox2">
@include('admin.mypage.partials.page-control', ['pageUrl' => route('admin.mypage.recruit-categories.index')])
</ul>

</div>

<!-- / .contents --></div>
@endsection
