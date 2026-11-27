{{-- 新着情報登録 — legacy: system/category3/digital_signage/digital_signage_register.php (a los 2 s vuelve al listado) --}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', '新着情報登録 - ' . $siteName)

@section('css')
<meta http-equiv="Refresh" content="2; URL={{ route('admin.promocion.news.index') }}">
@endsection

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac2").accordion('option', 'active', 0);
});
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■新着情報登録</p>

<div class="center"><p><img src="/system/img/loading.gif" alt="登録中です。" />
<br />登録中です。</p></div>

<!-- / .contents --></div>
@endsection
