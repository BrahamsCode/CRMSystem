{{-- 店舗登録情報 (完了): system/category0/shop/shop_new_complete.php --}}
@extends('layouts.legacy')

@section('title', '店舗登録情報 - ' . $siteName)

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
<p class="h2Title">■店舗登録情報</p>


<p>登録情報は以下の通りです。</p>

<div class="form">

@include('admin.base.shops._display', ['variant' => 'complete'])


</div>



<!-- / .contents --></div>
@endsection
