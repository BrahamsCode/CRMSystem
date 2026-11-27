{{-- Legacy: system/category1/biyou/karute_detail.php?cd= (カルテ 詳細; sin enlace en el menú).
     Variables:
       $siteName      nombre del sitio para el <title>
       $karte         objeto tb_karute + sejutsu_name (JOIN tb_sejutsu_cate)
       $customer      objeto tb_customer (id, name)
       $shopName      kaishamei de la tienda ('全店舗' si tb_shop_cd = -1)
       $uketsukeDate  受付年月日 (Y/m/d) --}}
@extends('layouts.legacy')

@section('menu', 'clientes')
{{-- El legacy pone 「修正」 salvo con register_type=new --}}
@section('title', 'カルテ' . (request('register_type') === 'new' ? ' 新規登録' : ' 修正') . ' - ' . $siteName)

@section('js')
<script type="text/javascript">
$(document).ready(function() {

	$(".ac4").accordion('option', 'active', 0);

});
</script>

<script type="text/javascript"><!--

function submitChk () {
	var flg = confirm ( "この内容で発行してもよろしいですか？\n\n発行しない場合は[キャンセル]ボタンを押して下さい");
	return flg;
}

function delChk () {
    var flg = confirm ( "削除してもよろしいですか？\n\n削除しない場合は[キャンセル]ボタンを押して下さい");

    return flg;
}

--></script>

<script type="text/javascript" src="{{ asset('legacy/system/js/ajaxupload-3.5.js') }}"></script>
{{-- biyou/js/shopping_entry.js: no existe en el legacy (404), no se incluye --}}
<script type="text/javascript" src="{{ asset('legacy/system/js/jquery.maskedinput-1.2.2.js') }}"></script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■カルテ 詳細</p>

<div class="form">

<fieldset>
<legend>カルテ</legend>

<table class="tableBox">

	<tr>
		<th><label for="user_id">顧客ID</label></th>
		<td>{{ $customer->id }}</td>
	</tr>

	<tr>
		<th><label for="user_id">顧客名</label></th>
		<td>{{ $customer->name }}</td>
	</tr>

	<tr>
		<th>店舗</th>
		<td>{{ $shopName }}</td>
	</tr>

	<tr>
		<th>受付年月日</th>
		<td>{{ $uketsukeDate }}</td>
	</tr>

	<tr>
		<th>施術</th>
		<td>{!! $karte->sejutsu_name !!}</td>{{-- guardado con get_form2() (ya escapado) --}}
	</tr>

	<tr>
		<th>備考</th>
		<td>{!! nl2br(e($karte->comment)) !!}</td>
	</tr>

</table>
</fieldset>


<!-- / .form --></div>



<!-- / .contents --></div>
@endsection
