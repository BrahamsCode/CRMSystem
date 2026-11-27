{{-- Legacy: system/category1/biyou/sejutsu_cate.php (予約カテゴリー管理: 施術カテゴリー登録 + 施術カテゴリー一覧; ?cd= edita).
     Variables:
       $siteName  nombre del sitio para el <title>
       $item      ServiceCategory|null: la que se edita (?cd=)
       $items     Collection de ServiceCategory (disp_flg = '1', ORDER BY cd DESC; sin master solo su tienda)
       $shops     array cd => kaishamei (disp_flg <> 9; sin master solo la de la sesión)
       $isMaster  bool: master (selector con 全店舗) --}}
@extends('layouts.legacy')

@section('menu', 'clientes')
@section('title', '予約カテゴリー管理 - ' . $siteName)

@section('js')
<script type="text/javascript">
$(document).ready(function() {

	$(".ac4").accordion('option', 'active', 0);

});
</script>



<script type="text/javascript">

function submitChk () {
	if(document.form1.shop_cd.value == ""){
		alert("店舗を選択して下さい。");
		return false;
	}

	if(document.form1.cate_name.value == ""){
		alert("施術カテゴリー名が空白です。\n\n予約カテゴリー名をご記入下さい。");
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

@php
    // tb_shop_cd nulo = 全店舗 (-1 en el formulario)
    $selectedShop = $item ? ($item->tb_shop_cd === null ? '-1' : (string) $item->tb_shop_cd) : null;
    $fixedShop = count($shops) === 1 || ! $isMaster;
@endphp

@section('content')
<div class="contents">

<p class="h2Title">■施術カテゴリー登録</p>


<form method="post" name="form1" id="form1" class="form" action="{{ $item ? route('admin.clientes.service-categories.update', $item->cd) : route('admin.clientes.service-categories.store') }}" onsubmit="return submitChk()">
@csrf
@if ($item)
@method('PUT')
@endif


<fieldset>
<legend>施術カテゴリー情報</legend>

<table class="tableBox">
<tr>
<th>店舗</th>
<td>
@if ($fixedShop)
@php $firstShopCd = array_key_first($shops); @endphp
{{ $shops[$firstShopCd] ?? '' }}
<input type="hidden" name="shop_cd" value="{{ $firstShopCd }}">
@else
<select name="shop_cd">
<option value="">▼店舗選択</option>
@foreach ($shops as $cd => $name)
<option value="{{ $cd }}" @selected($selectedShop === (string) $cd)>{{ $name }}</option>
@endforeach
<option value="-1" @selected($selectedShop === '-1')>全店舗</option>
</select>
@endif
</td>
</tr>

<tr>
<th>施術カテゴリー名</th>
<td>
{{-- name guardado con get_form2() (ya escapado) --}}
<input type="text" name="cate_name" id="cate_name" class="writeArea" value="{!! $item?->name !!}" />
</td>
</tr>

</table>

</fieldset>

<div class="btnBox">
@if ($item)
<input type="submit" name="enter" id="revise" value="この内容で修正する" />
<input type="hidden" name="act" id="act" value="up" />
<input type="hidden" name="cd" id="cd" value="{{ $item->cd }}" />
@else
<input type="submit" name="enter" id="reivse" value="この内容で登録する" />
<input type="hidden" name="act" id="act" value="new" />
@endif
</div>

</form>



<!-- / .contents --></div>

<div class="contents">
<p class="h2Title">■施術カテゴリー一覧</p>
<div class="form">
@if ($items->count() > 0)

<p class="textCenter">最新から{{ $items->count() }}件</span>の該当結果を表示しています。</p>

<table class="tableBox2">
  <tr>
    <th>No</th>
    <th>施術カテゴリー名</th>
    <th>店舗</th>
    <th>修正・削除</th>
  </tr>


@foreach ($items as $category)
@php $memCnt = $loop->iteration; @endphp
@if ($loop->index % 2 <> 0)
  <tr class="tableBg">
@else
  <tr>
@endif
    <td>{{ $category->cd }}</td>
    <td>{!! $category->name !!}</td>
    <td>@if ($category->tb_shop_cd === null)全店舗@else{{ $shops[$category->tb_shop_cd] ?? '' }}@endif
    </td>
    <td class="textCenter" width="100">
    	<a href="{{ route('admin.clientes.service-categories.index', ['cd' => $category->cd]) }}"><img src="{{ asset('legacy/system/img/btn_revise_off.gif') }}" width="40" height="20" alt="修正" /></a>
		<a href="javascript:void(0)" onclick="submitChk2({{ $memCnt }}); return false;"><img src="{{ asset('legacy/system/img/btn_delete_off.gif') }}" width="40" height="20" alt="削除" /></a>

<form method="post" name="form_{{ $memCnt }}" id="form_{{ $memCnt }}" class="displayNone" action="{{ route('admin.clientes.service-categories.destroy', $category->cd) }}">
@csrf
@method('DELETE')
<input type="hidden" name="delete_flg" id="delete" class="btn" value="del">
<input type="hidden" name="cd" class="btn" value="{{ $category->cd }}">
<input type="submit" name="delete" id="delete" class="btn" value="削除する">
</form></td>
  </tr>
@endforeach

</table>
@else
施術カテゴリーは登録されていません。
@endif

<ul class="btnBox2">

</ul>

</div>

<!-- / .contents --></div>
@endsection
