{{-- 店舗新規登録 / 修正の確認: system/category0/shop/shop_new_confirm.php (sin errores) --}}
@extends('layouts.legacy')

@section('title', ($shopCd ? '店舗登録情報 修正' : '店舗新規登録') . ' - ' . $siteName)

@section('menu', 'base')

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac2").accordion('option', 'active', 0);
});
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■{{ $shopCd ? '店舗登録情報 修正' : '店舗新規登録' }}</p>



<div class="form">

@include('admin.base.shops._display', ['variant' => 'confirm'])


</div>


<div class="btnBox">


<form method="post" action="shop_new.php">
@foreach ($data + ['shop_cd' => $shopCd] as $key => $val)
<input type="hidden" name="{{ $key }}" value="{{ $val }}">
@endforeach

<input type="submit" name="modoru" value="書き直す" class="existBtn" />
</form>

<form method="post" action="shop_new_register.php">
@foreach ($data + ['shop_cd' => $shopCd] as $key => $val)
<input type="hidden" name="{{ $key }}" value="{{ $val }}">
@endforeach

<input type="hidden" name="act" value="{{ $shopCd ? 'up' : 'new' }}" />
<input type="submit" name="enter" value="この内容で登録する" class="existBtn" />


</form>

</div>

<!-- / .contents --></div>
@endsection
