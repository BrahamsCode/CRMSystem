{{-- 店舗・営業所詳細: system/category0/shop/shop_detail.php --}}
@extends('layouts.legacy')

@section('title', '店舗・営業所詳細 -総合業務管理システム-')

@section('menu', 'base')

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac2").accordion('option', 'active', 0);
});
</script>

<script type="text/javascript">
		function submitChk () {
@if ((int) ($d['disp_flg'] ?? 0) === 9)
				var flg = confirm ( "削除を取り消ししてもよろしいですか？\n\n取り消ししない場合は[キャンセル]ボタンを押して下さい");
@else
				var flg = confirm ( "削除してもよろしいですか？\n\n削除した場合、所属するスタッフと顧客データは本社または本店に移動されます。\n\n削除しない場合は[キャンセル]ボタンを押して下さい");
@endif
				return flg;
		}
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■店舗・営業所詳細</p>




<div class="form">

@include('admin.base.shops._display', ['variant' => 'detail'])





<div class="btnBox">

<p class="left"><a href="shop_list.php?page={{ $page }}&kensu={{ $kensu }}&se_flg=2"><img src="../../img/btn_back_list_off.gif" width="90" height="15" alt="" /></a></p>

<form method="post" name="form1" id="form1" class="form" action="shop_new.php">
<input type="hidden" name="shop_cd" id="shop_cd" class="btn" value="{{ request('shop_cd') }}">
<input type="submit" name="revise" id="revise" class="btn" value="修正する">
</form>

@if ($isDevelop && $shop && (int) $shop->cd !== 1)
	@if ((int) $shop->disp_flg === 9)
<form method="post" name="form2" id="form2" class="form" action="shop_delete_register.php" onsubmit="return submitChk()">
<input type="hidden" name="delete_flg" id="delete_flg" class="btn" value="recover">
<input type="hidden" name="customer_cd" id="customer_cd" class="btn" value="{{ $shop->cd }}">
<input type="submit" name="delete" id="delete" class="btn" value="削除を取り消す">
<input type="hidden" name="shop_cd" id="shop_cd" class="btn" value="{{ request('shop_cd') }}">
</form>
	@else
<form method="post" name="form2" id="form2" class="form" action="shop_delete_register.php" onsubmit="return submitChk()">
<input type="hidden" name="delete_flg" id="delete_flg" class="btn" value="del">
<input type="hidden" name="customer_cd" id="customer_cd" class="btn" value="{{ $shop->cd }}">
<input type="hidden" name="shop_cd" id="shop_cd" class="btn" value="{{ request('shop_cd') }}">
<input type="submit" name="delete" id="delete" class="btn" value="削除する">
</form>
	@endif
@endif

</div>



<!-- / .form --></div>


<!-- / .contents --></div>
@endsection
