{{-- 登録中です (mycoupon_register.php): pasa al detalle a los 2 s. Variables: $title, $mode, $next --}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', $title . ' ' . $mode)

@section('css')
<meta http-equiv="Refresh" content="2; URL={{ $next }}">
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
<p class="h2Title">■{{ $title }} {{ $mode }}</p>
<p class="center"><img src="{{ asset('legacy/system/img/loading.gif') }}" alt="登録中です。" />
<br />登録中です。</p>
</div>
@endsection
