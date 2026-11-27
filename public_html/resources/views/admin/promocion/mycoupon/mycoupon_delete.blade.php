{{-- 削除中です (mycoupon_delete.php): vuelve a la lista al segundo. Variables: $next --}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', 'マイクーポン 削除')

@section('css')
<meta http-equiv="Refresh" content="1; URL={{ $next }}">
@endsection

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac7").accordion('option', 'active', 0);
	window.scroll(0,0);
});
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■マイクーポン 削除</p>
<p class="center"><img src="{{ asset('legacy/system/img/loading.gif') }}" alt="削除中です。" />
<br />削除中です。</p>
</div>
@endsection
