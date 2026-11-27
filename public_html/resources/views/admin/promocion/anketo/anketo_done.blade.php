{{--
	«登録中です / 処理中です / 削除中です» con meta refresh (fin de anketo_cate_register.php, anketo_cate_action.php,
	anketo_form_register.php y anketo_form_delete.php del legacy).
	Variables: $family, $heading (título; vacío en los del ítem, como el legacy), $message, $seconds, $next
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', $heading)

@section('css')
<meta http-equiv="Refresh" content="{{ $seconds }}; URL={{ $next }}">
@endsection

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$("{{ $family->ac() }}").accordion('option', 'active', 0);
});
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■{{ $heading }}</p>



<p class="center"><img src="/system/img/loading.gif" alt="{{ $message }}" />
<br />{{ $message }}</p>




<!-- / .contents --></div>
@endsection
