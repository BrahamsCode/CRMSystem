{{--
    Legacy: system/category9/yoyaku/yoyaku_uketsuke_delete.php (予約受付削除: mensaje y recarga del 予約表). Sin variables.
--}}
@extends('layouts.legacy-popup')

@section('title', '予約受付削除')

@section('css')
<link href="{{ asset('legacy/system/css/import.css') }}" rel="stylesheet" type="text/css" media="all" />

<style type="text/css">

#container {
	min-width: 500px;
}

</style>
@endsection

@section('js')
<script type="text/javascript" src="{{ asset('legacy/system/js/scrollsmoothly.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/js/iepngfix.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/js/default.js') }}"></script>

<script type="text/javascript" src="{{ asset('legacy/system/js/jquery-1.3.2.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/js/jquery-ui-1.7.2.custom.min.js') }}"></script>


<script type="text/javascript">

function hideMess() {
	$("#mess").hide().html('');
}

$(document).ready(function() {
	$("#mess").html('予約受付削除しました。').show();
	setTimeout('hideMess()', 2000);
	if ( !window.opener || !Object.keys(window.opener).length ) {
		// ポップアップの場合
		window.parent.location.reload(); // 存在する場合はページを切りかえる
		window.parent.popUpWinClose('');
	} else {
		// 別ウィンドウで開いた場合
		window.opener.location.reload();
		window.close();

	}

});

</script>
@endsection

@section('content')
<div class="contents">


<div id="mess" style="display:none;text-align:center;background:#FFFF00;color:red;margin:5px;"></div>


<!-- / .contents --></div>
@endsection
