{{-- プッシュ通知 完了 — legacy: system/category3/push/push_complete.php (a los 2 s, $link) --}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', 'プッシュ通知 ' . $siteName)

@section('css')
<meta http-equiv="Refresh" content="2; URL={{ $link }}">
@endsection

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$("{{ $activeAc }}").accordion('option', 'active', 0);
});
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■プッシュ通知</p>


<div class="center">
<p>完了しました。</p>
</div>

<!-- / .contents --></div>
@endsection
