{{-- 登録中です (touch_config_register.php): vuelve a カードリーダ設定 al segundo. Variables: $next --}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', 'カードリーダ設定 - ' . $siteName)

@section('css')
<meta http-equiv="Refresh" content="1; URL={{ $next }}">
@endsection

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac4").accordion('option', 'active', 0);
});
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■カードリーダ設定</p>



<p class="center"><img src="{{ asset('legacy/system/img/loading.gif') }}" alt="登録中です。" />
<br />登録中です。</p>




<!-- / .contents --></div>
@endsection
