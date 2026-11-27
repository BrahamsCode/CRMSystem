{{--
    Legacy: system/category9/yoyaku/yoyaku_kinou_config.php (予約設定 > 機能設定; ventana emergente sin menú).
    Variables:
      $timeIntervals  int[]   intervalos permitidos (5, 10, 15, 20, 30, 40, 60).
      $timeInterval   int     admin_master.yoyaku_time_interval (30 si vacío o fuera de la lista).
      $meyasuJikanFlg string  admin_master.meyasu_jikan_flg ('9' = 表示しない).
      $isTablet       bool    terminal tablet: 閉じる con roid.windowClose().
      $saved          bool    se acaba de guardar (legacy act=reg): aviso .tsuchiArea y recarga del padre.
--}}
@extends('layouts.legacy-popup')

@section('title', '予約設定 - ' . ($siteName ?? config('app.name')))

@section('css')
<style type="text/css"><!--

#container {
	min-width: 450px;
}

.main1 {
	background: none;
}

--></style>
@endsection

@section('js')
<script type="text/javascript">
$(document).ready(function() {
@if ($saved)

	$("#mess").empty().html('<div class="tsuchiArea">設定しました。</div>');
	$(".tsuchiArea").animate({ opacity: "toggle" }, 1500 , "easeInQuart",function(){
		parent.location.reload(true);
	});

@elseif (! $errors->any())
	$("#mess").hide();
@endif
});
</script>

<script type="text/javascript" src="{{ asset('legacy/system/js/ajaxupload-3.5.js') }}"></script>
@endsection

@section('content')
<div class="contents">

<a href="{{ route('admin.reservas.settings.shop') }}">各店舗設定</a> /
<a href="{{ route('admin.reservas.settings.form') }}">予約フォーム設定</a> /
機能設定 /
<a href="{{ route('admin.reservas.settings.comment') }}">注釈設定</a>

<div id="mess" style="text-align:center;background:#FFFF00;color:red;margin:5px;">@if ($errors->any()){{ $errors->first() }}@endif</div>


<p class="h2Title">機能設定</p>
<form action="{{ route('admin.reservas.settings.features.update') }}" method="post" name="form1" class="form">
@csrf

<table class="tableBox">

	<tr>

		<th style="width:35%;"><label for="time_interval">予約時間間隔</label></th>

		<td style="text-align:left;">
			<select name="time_interval" id="time_interval">
@foreach ($timeIntervals as $interval)
			<option value="{{ $interval }}" @selected($interval == $timeInterval)>{{ $interval }}</option>
@endforeach
			</select>&nbsp;分
		</td>

	</tr>

	<tr>
		<th style="width:35%;"><label for="time_interval">目安時間の表示</label></th>

		<td style="text-align:left;">
			<input type="radio" id="meyasu_jikan_flg1" name="meyasu_jikan_flg" value="" @checked($meyasuJikanFlg != '9') />&nbsp;<label for="meyasu_jikan_flg1">表示する</label>&emsp;
			<input type="radio" id="meyasu_jikan_flg2" name="meyasu_jikan_flg" value="9" @checked($meyasuJikanFlg == '9') />&nbsp;<label for="meyasu_jikan_flg2">表示しない</label>
		</td>

	</tr>

</table>

<div class="btnBox hideSection">
<input type="hidden" name="act" id="act" value="reg" />
<input type="submit" name="enter" id="enter" class="btn" value="確定" />
<input type="button" value="閉じる" class="btn" onclick="{{ $isTablet ? 'roid.windowClose();' : "window.parent.popUpWinClose('');" }}" />
</div>

</form>



<!-- / .contents --></div>
@endsection
