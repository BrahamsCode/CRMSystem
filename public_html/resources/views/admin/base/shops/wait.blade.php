{{-- Pantalla intermedia del legacy (*_register.php): "登録中です" / "削除中です" y refresco a los 2 s --}}
@extends('layouts.legacy')

@section('title', $title)

@section('menu', 'base')

@section('css')
<meta http-equiv="Refresh" content="{{ $seconds ?? 2 }}; URL={{ $refresh }}">
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
<p class="h2Title">{{ $heading }}</p>


@if ($class === 'center')
<div class="center"><p><img src="../../img/loading.gif" alt="{{ $message }}" />
<br />{{ $message }}</p></div>
@else
<p class="{{ $class }}"><img src="../../img/loading.gif" alt="{{ $message }}" /><br />{{ $message }}</p>
@endif


<!-- / .contents --></div>
@endsection
