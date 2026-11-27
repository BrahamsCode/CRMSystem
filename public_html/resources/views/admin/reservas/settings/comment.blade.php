{{--
    Legacy: system/category9/yoyaku/yoyaku_comment_config.php (予約設定 > 注釈設定; ventana emergente sin menú).
    Variables:
      $commentFields array<int,string>  cd de tb_manage_words => etiqueta (content_name 'mobile_yoyaku[<etiqueta>]').
      $comments      array<int,string>  cd => naiyou actual ('' si no hay fila).
      $isTablet      bool               terminal tablet: 閉じる con roid.windowClose().
      $saved         bool               se acaba de guardar (legacy act=reg): recarga de window.opener.
    El legacy no mostraba mensaje tras guardar ($msg nunca se asignaba): se mantiene igual.
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

<a href="{{ route('admin.reservas.settings.shop') }}">各店舗設定</a> /
<a href="{{ route('admin.reservas.settings.form') }}">予約フォーム設定</a> /
<a href="{{ route('admin.reservas.settings.features') }}">機能設定</a> /
注釈設定

<div id="mess" style="display:none;text-align:center;background:#FFFF00;color:red;margin:5px;"></div>


<p class="h2Title">注釈設定</p>
<form action="{{ route('admin.reservas.settings.comment.update') }}" method="post" name="form1" class="form">
@csrf

<p class="h3Title">予約フォーム</p>
<table class="tableBox">
<tr>
<th>項目名</th>
<th align="center">内容</th>
</tr>
@foreach ($commentFields as $cd => $label)
<tr>
<th>{{ $label }}</th>
<td>
<textarea name="comment_{{ $cd }}" id="comment_{{ $cd }}" class="inputL" cols="50" rows="3">{{ $comments[$cd] ?? '' }}</textarea>
</td>
</tr>
@endforeach
</table>



<div class="btnBox hideSection">
<input type="hidden" name="act" id="act" value="reg" />
<input type="submit" name="enter" id="enter" class="btn" value="確定" />
<input type="button" class="btnStyle" value="閉じる" onclick="{{ $isTablet ? 'roid.windowClose();' : "window.parent.popUpWinClose('');" }}" />
</div>

</form>



<!-- / .contents --></div>
@endsection
