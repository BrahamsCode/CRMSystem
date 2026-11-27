{{-- 登録中です (stamp_pattern_register.php): vuelve a la pantalla a los $seconds segundos. Variables: $title, $message, $seconds, $next --}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', $title)

@section('css')
<meta http-equiv="Refresh" content="{{ $seconds }}; URL={{ $next }}">
@endsection

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac6").accordion('option', 'active', 0);
	window.scroll(0,0);
});
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■{{ $title }}</p>
<p class="center"><img src="{{ asset('legacy/system/img/loading.gif') }}" alt="{{ $message }}" />
<br />{{ $message }}</p>
<!-- / .contents --></div>
@endsection
