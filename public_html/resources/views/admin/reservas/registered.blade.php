{{--
    Legacy: system/category9/yoyaku/yoyaku_register.php (página vacía que, tras registrar, recarga el 予約表 y cierra la ventana).
    Variables:
      $shopCd  string  tienda del alta
      $retDay  string  'Ymd' del día reservado
      $isIpad  bool    User-Agent con iPad
--}}
@extends('layouts.legacy-popup')

@section('title', 'トップ -総合業務管理システム-')

@section('js')
<script type="text/javascript">
$(document).ready(function() {

@if (\Illuminate\Support\Facades\Route::has('admin.reservas.timetable'))
	var url = {{ \Illuminate\Support\Js::from(route('admin.reservas.timetable', ['shop' => $shopCd, 'd' => $retDay])) }};
@endif

@if ($isIpad)
	window.opener.location.reload();
@endif

	if ( window == window.parent ) {
		window.parent.location.reload(true);
		if (window.opener) window.opener.location.reload(true);
		window.close();
	} else {
		window.parent.location.reload(true);
		window.parent.popUpWinReloadClose('', true);
	}

});
</script>
@endsection

@section('content')
<div class="contents">


<!-- / .contents --></div>

<div id="popWindow">

<div id="ninzu"></div>
</div>
@endsection
