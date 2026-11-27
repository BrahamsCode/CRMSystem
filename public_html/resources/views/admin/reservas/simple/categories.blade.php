{{-- Legacy: system/category3/reservation/category_entry.php (予約カテゴリー登録 y 一覧; ?cd= para modificar).
     Variables:
       $editing       ?SimpleReservationCategory la que se modifica (reserve_cate_name con entidades HTML: {!! !!})
       $selectedShop  ?string tienda de la categoría ('-1' = 全店舗)
       $isMaster      bool
       $shops         Collection tiendas del selector (cd, kaishamei): todas (master) o la de la sesión
       $shopNames     [cd => kaishamei] para la columna 店舗 de la lista
       $categories    Collection categorías activas (todas o las de la tienda de la sesión), ORDER BY cd DESC
       $siteName      nombre del sitio --}}
@extends('layouts.legacy')

@section('menu', 'promocion')
@section('title', '予約カテゴリー管理 - ' . $siteName)

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac5").accordion({ collapsible: true , active: false});
	$(".ac5").accordion('option', 'active', 0);
	window.scroll(0,0);
});
</script>



<script type="text/javascript">

function submitChk () {
	if(document.form1.shop_cd.value == ""){
		alert("店舗を選択して下さい。");
		return false;
	}

	if(document.form1.cate_name.value == ""){
		alert("予約カテゴリー名が空白です。\n\n予約カテゴリー名をご記入下さい。");
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
<div class="contents">

<p class="h2Title">■予約カテゴリー登録</p>


<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.reservas.simple-categories.store') }}" onsubmit="return submitChk()">
@csrf


<fieldset>
<legend>予約カテゴリー情報</legend>

<table class="tableBox">
<tr>
<th>店舗</th>
<td>
@if ($shops->count() == 1 || ! $isMaster)
{{ $shops->first()?->kaishamei }}
<input type="hidden" name="shop_cd" value="{{ $shops->first()?->cd }}">
@else
<select name="shop_cd">
<option value="">▼店舗選択</option>
@foreach ($shops as $shop)
<option value="{{ $shop->cd }}" @selected($selectedShop === (string) $shop->cd)>{{ $shop->kaishamei }}</option>
@endforeach
<option value="-1" @selected($selectedShop === '-1')>全店舗</option>
</select>
@endif
</td>
</tr>

<tr>
<th>予約カテゴリー名</th>
<td>
{{-- reserve_cate_name se guarda con get_form2 (entidades): sin escapar, como el legacy --}}
<input type="text" name="cate_name" id="cate_name" class="writeArea" value="{!! $editing?->reserve_cate_name !!}" />
</td>
</tr>

</table>

</fieldset>

<div class="btnBox">
@if ($editing)
<input type="submit" name="enter" id="revise" value="この内容で修正する" />
<input type="hidden" name="act" id="act" value="up" />
<input type="hidden" name="cd" id="cd" value="{{ $editing->cd }}" />
@else
<input type="submit" name="enter" id="reivse" value="この内容で登録する" />
<input type="hidden" name="act" id="act" value="new" />
@endif
</div>

</form>



<!-- / .contents --></div>

<div class="contents">
<p class="h2Title">■予約カテゴリー一覧</p>
<div class="form">
@if ($categories->isNotEmpty())

<p class="textCenter">最新から{{ $categories->count() }}件</span>の該当結果を表示しています。</p>

<table class="tableBox2">
  <tr>
    <th>No</th>
    <th>予約カテゴリー名</th>
    <th>店舗</th>
    <th>修正・削除</th>
  </tr>


@foreach ($categories as $category)
@php $memCnt = $loop->iteration; @endphp
@if ($loop->index % 2 != 0)
  <tr class="tableBg">
@else
  <tr>
@endif
    <td>{{ $category->cd }}</td>
    <td>{!! $category->reserve_cate_name !!}</td>
    <td>{{ $category->tb_shop_cd === null ? '全店舗' : ($shopNames[$category->tb_shop_cd] ?? '') }}
    </td>
    <td class="textCenter" width="100">
    	<a href="{{ route('admin.reservas.simple-categories.index', ['cd' => $category->cd]) }}"><img src="{{ asset('legacy/system/img/btn_revise_off.gif') }}" width="40" height="20" alt="修正" /></a>
		<a href="javascript:void(0)" onclick="submitChk2({{ $memCnt }}); return false;"><img src="{{ asset('legacy/system/img/btn_delete_off.gif') }}" width="40" height="20" alt="削除" /></a>

<form method="post" name="form_{{ $memCnt }}" id="form_{{ $memCnt }}" class="displayNone" action="{{ route('admin.reservas.simple-categories.destroy', $category->cd) }}">
@csrf
<input type="hidden" name="delete_flg" id="delete" class="btn" value="del">
<input type="hidden" name="cd" class="btn" value="{{ $category->cd }}">
<input type="submit" name="delete" id="delete" class="btn" value="削除する">
</form></td>
  </tr>
@endforeach

</table>
@else
予約カテゴリーは登録されていません。
@endif

<ul class="btnBox2">
</ul>

</div>

<!-- / .contents --></div>
@endsection
