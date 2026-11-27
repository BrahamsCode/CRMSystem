{{--
    Legacy: system/category9/yoyaku/yoyaku_form_config.php (予約設定 > 予約フォーム設定; ventana emergente sin menú).
    Variables:
      $basicFields Collection  filas tb_koumoku (system_cate '9', tb_name 'tb_yoyaku', tb_koumoku name/name_kana/mail/tel), orden cd.
      $freeFields  Collection  filas tb_koumoku free_koumoku_* de tb_yoyaku, orden rank.
      $isTablet    bool        terminal tablet: 閉じる con roid.windowClose().
      $saved       bool        se acaba de guardar (legacy act=reg): mensaje y recarga de window.opener.
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
	$("#mess").html('設定しました。').show();
	setTimeout('hideMess()', 2000);

	window.opener.location.reload();
@endif
});




function hideMess() {
		$("#mess").hide().html('');
}
</script>

<script type="text/javascript" src="{{ asset('legacy/system/js/ajaxupload-3.5.js') }}"></script>
@endsection

@section('content')
<div class="contents">

<a href="{{ route('admin.reservas.settings.shop') }}">各店舗設定</a>
 / 予約フォーム設定 /
<a href="{{ route('admin.reservas.settings.features') }}">機能設定</a> /
<a href="{{ route('admin.reservas.settings.comment') }}">注釈設定</a>

<div id="mess" style="display:none;text-align:center;background:#FFFF00;color:red;margin:5px;"></div>


<p class="h2Title">予約フォーム設定</p>
<form action="{{ route('admin.reservas.settings.form.update') }}" method="post" name="form1" class="form">
@csrf

<p class="h3Title">基本項目設定</p>
@if ($basicFields->isNotEmpty())
<table class="tableBox">
	<tr>
		<th>項目名</th>
		<th align="center">入力</th>
		<th align="center">必須</th>
		<th align="center">CSV</th>
	</tr>
@foreach ($basicFields as $koumoku)
	<tr>
		<th style="width:35%;">{{ $koumoku->tb_koumoku_name }}</th>

		<td align="center">
		<input type="checkbox" name="koumoku_disp_{{ $koumoku->tb_koumoku }}" value="1" @checked($koumoku->koumoku_disp == 1) @disabled($koumoku->tb_koumoku_name == '名前') />
		</td>

		<td align="center">
		<input type="checkbox" name="koumoku_check_{{ $koumoku->tb_koumoku }}" value="1" @checked($koumoku->koumoku_check == 1) @disabled($koumoku->tb_koumoku_name == '名前') />
		</td>

		<td align="center">
		<input type="checkbox" name="koumoku_select_{{ $koumoku->tb_koumoku }}" value="1" @checked($koumoku->koumoku_select == 1) @disabled($koumoku->tb_koumoku_name == '名前') />
		</td>
	</tr>
@endforeach



</table>
@endif


@if ($freeFields->isNotEmpty())
<p class="h3Title">フリー項目設定</p>
<table class="tableBox">
	<tr>
		<th>項目名</th>
		<th align="center">入力</th>
		<th align="center">必須</th>
		<th align="center">CSV</th>
	</tr>
@foreach ($freeFields as $koumoku)
	<tr>
		<th style="width:35%;">
		<input type="text" name="free_koumoku_name_{{ $koumoku->cd }}" value="{{ $koumoku->tb_koumoku_name }}" class="inputS" style="width:98%;" />
		<input type="hidden" name="free_koumoku_cd[]" value="{{ $koumoku->cd }}" />
		</th>

		<td align="center">
		<input type="checkbox" name="free_koumoku_disp_{{ $koumoku->cd }}" value="1" @checked($koumoku->koumoku_disp == 1) style="width:98%;" />
		</td>

		<td align="center">
		<input type="checkbox" name="free_koumoku_check_{{ $koumoku->cd }}" value="1" @checked($koumoku->koumoku_check == 1) style="width:98%;" />
		</td>

		<td align="center">
		<input type="checkbox" name="free_koumoku_select_{{ $koumoku->cd }}" value="1" @checked($koumoku->koumoku_select == 1) style="width:98%;" />
		</td>
	</tr>
@endforeach

</table>
@endif

<div class="btnBox hideSection">
<input type="hidden" name="act" id="act" value="reg" />
<input type="submit" name="enter" id="enter" class="btn" value="確定" />
<input type="button" value="閉じる" class="btn" onclick="{{ $isTablet ? 'roid.windowClose();' : "window.parent.popUpWinClose('');" }}" />
</div>

</form>



<!-- / .contents --></div>
@endsection
