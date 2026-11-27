{{-- 登録中です / 削除中です (point_rules_register.php, point_rules_delete.php): vuelve a la lista al segundo. Variables: $mode, $message, $next --}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', 'ポイント発行ルール ' . $mode)

@section('css')
<meta http-equiv="Refresh" content="1; URL={{ $next }}">
@endsection

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac13").accordion('option', 'active', 0);
	window.scroll(0,0);
});
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■ポイント発行ルール {{ $mode }}</p>
<p class="center"><img src="{{ asset('legacy/system/img/loading.gif') }}" alt="{{ $message }}" />
<br />{{ $message }}</p>
<!-- / .contents --></div>
@endsection
