{{-- Legacy: system/category5/kintai/password_update.php (端末パスワード変更). Mismo HTML y mensajes.
     Variables: $shopCd, $shops, $error, $passBefore, $passAfter --}}
@extends('layouts.legacy')

@section('menu', 'operaciones')

@section('title', '端末パスワード変更 - '.($siteName ?? '').' - ')

@section('body_class', 'passwordUpdate')

@section('css')
<style type="text/css">
<!--

p.errorText
{
	padding: 5px;
	border: 1px dotted #FF0000;
	color: #FF0000;
}

-->
</style>
@endsection

@section('content')
<div class="contents">

<p class="h2Title">■端末パスワード変更</p>


<form class="form" method="post" action="{{ route('admin.operaciones.kintai.password.update') }}">
@csrf

<p>端末の設定画面にはいるためのパスワードの変更ができます。</p>
@if ($error !== '')<p class="errorText">{{ $error }}</p>@endif

<div class="configArea">

<table id="passwordTbl" class="tableBox">

<tr>
<th align="center" valign="middle">店舗選択</th>
<td>
<select name="shop" id="shop">
@include('admin.operaciones.partials.options', ['placeholder' => '▼店舗選択', 'options' => $shops, 'selected' => $shopCd])
</select>
</td>
</tr>

<tr>
<th align="center" valign="middle">現在のパスワード</th>
<td align="left" valign="middle"><input type="text" name="passBefore" id="passBefore" class="inputL" maxlength="30" value="{{ $passBefore }}" style="ime-mode: inactive;" /></td>
</tr>

<tr>
<th align="center" valign="middle">新しいパスワード</th>
<td align="left" valign="middle"><input type="text" name="passAfter" id="passAfter" class="inputL" maxlength="30" value="{{ $passAfter }}" style="ime-mode: inactive;" /></td>
</tr>

<!--■■ /#passwordTbl ■■--></table>

<div class="btnBox">
<input type="submit" name="act" id="act" class="btnStyleM" value="　　変更　　" />
<!-- □□ /.btnBox □□ --></div>

<!--■■ /.configArea ■■--></div>

</form>

<!-- / .contents --></div>
@endsection
