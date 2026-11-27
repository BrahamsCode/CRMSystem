{{--
	予約配信メール 配信解除 — legacy: system/category3/mail_mag/mail_kaijo.php
	Variables: $done (ya borrado), $mail (MailmagMaster o null), $routes
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', '予約配信メール' . ' - ' . $siteName)

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac1").accordion('option', 'active', 0);
});
</script>
@endsection

@section('content')
<div class="contents">
@if ($done)
<p class="h2Title">■予約配信メール 配信解除</p>
<p>配信予定を解除致しました。</p>
@else
<p class="h2Title">■予約配信メール配信解除</p>
<p>「<font color="red">{{ $mail?->getAttribute('配信日時') }}</font>」予定配信を解除致します。<br>よろしいですか？</p>
<p></p>
<div class="btnBox">
      <form action="{{ $routes['cancel'] }}" method="post">
      @csrf
      <input type="submit" value="は　い">
      <input type="hidden" name="act" value="登録削除する">
      <input type="hidden" name="dele" value="1">
	  <input type="hidden" name="send_flg" value="2">
      <input name="mailmagcd" type="hidden" value="{{ $mail?->cd }}">
      </form>
	  <form action="{{ $routes['edit'] }}" method="get">
      <input type="submit" value="いいえ">
      <input name="mailmagcd" type="hidden" value="{{ $mail?->cd }}">
	  </form>
</div>
@endif
<!-- / .contents --></div>
@endsection
