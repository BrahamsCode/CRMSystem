{{-- 要望・お問い合わせ [確認]: system/contact/contact_confirm.php (la incluye index.php con act=up) --}}
@extends('layouts.legacy-popup')

@section('title', '要望・お問い合わせ - ' . $siteName)

@section('content')
<div class="contents">
<p class="h2Title">■要望・お問い合わせ</p>

<form method="post" name="form1" id="form1" class="form" action="/system/contact/index.php">
<fieldset>
<legend>要望・お問い合わせフォーム</legend>
<table class="tableBox">
<tr>
<th>会社名/屋号</th>
<td>{{ $data['kaishamei'] }}</td>
</tr>
<tr>
<th>店舗名</th>
<td>{{ $shops[(int) $data['tb_shop_cd']] ?? '' }}</td>
</tr>
<tr>
<th>担当者名</th>
<td>{{ $data['tantou_name'] }}</td>
</tr>
<tr>
<th>担当者名(カナ)</th>
<td>{{ $data['tantou_name_kana'] }}</td>
</tr>
<tr>
<th>お問い合わせ内容</th>
<td>{{ $data['type'] }}</td>
</tr>
<tr>
<th>本文</th>
<td>{!! nl2br(e($data['comment'])) !!}</td>
</tr>
</table>
</fieldset>
</form>

<div class="btnBox">
<form method="post" action="/system/contact/index.php">
@csrf
@include('admin.nucleo.contact._hidden')
<input type="submit" name="modoru" value="内容を修正" class="existBtn btnStyleM" />
</form>
<form method="post" action="/system/contact/contact_complete.php">
@csrf
@include('admin.nucleo.contact._hidden')
<input type="hidden" name="verify" value="{{ $verify }}" />
<input type="submit" name="enter" id="enter" class="btn btnStyleM" value="この内容で送信">
</form>
</div>

@include('admin.nucleo.contact._close')

<!-- / .contents --></div>
@endsection
