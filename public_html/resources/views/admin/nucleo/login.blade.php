{{-- ログイン: system/login.php del legacy, mismo HTML --}}
@php
    $legacyUi = app(\App\Services\Nucleo\LegacyUi::class);
    $craftfix = $legacyUi->sellerId() === 1;
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ja" xml:lang="ja" dir="ltr">

<head>
@include('layouts.legacy.head')

<link href="/system/index.html" rel="index" />



<title>ログイン {{ $legacyUi->siteName() }}</title>
<style>
#loginFooter {
    text-align: center;
    width: 100%;
    height: 20px;
    background: none;
    color: #607d8c;
    line-height: 25px;
    font-size: 12px;
    position: relative;
}
</style>
</head>

<body id="pTop" class="noBg" onLoad='document.forms[0].admin_id.focus()'>

<div id="loginWrap">
<div id="headDesignPart">@for ($i = 0; $i < 10; $i++)<span id="headDesignPartObj_{{ $i }}" class="headDesignPartObj"></span>@endfor</div>


<p class="text3L bold textCenter m0">CRM System 管理画面へようこそ</p>

<form method="post" name="form1" id="form1" action="/system/login.php">
@csrf

<div id="loginContainer">
<div id="loginBox">

<p id="loginTitle">総合業務管理システム</p>

<p id="loginExplain" class="textCenter">ユーザーID、パスワードをご入力ください。</p>

<fieldset>
<legend>会員様ログイン</legend>
<dl>
<dt><label for="admin_id">USER ID</label></dt>
<dd><input type="text" name="admin_id" id="admin_id" value="{{ $rememberedId }}" style="ime-mode: inactive;" /></dd>
<dt><label for="admin_pass">PASSWORD</label></dt>
<dd><input type="password" name="admin_pass" id="admin_pass" value="" autocomplete="current-password" style="ime-mode: inactive;" /></dd>
</dl>
</fieldset>

<div id="memoryPassword" class="btnBox">
<input type="checkbox" name="logincheck" id="logincheck" value="check"@if ($rememberedId !== '') checked="checked"@endif />
<label for="logincheck">このパスワードを記憶する</label></div>

<div id="loginBtnArea" class="btnBox">
<input type="hidden" name="act" id="act" value="login" />
<input type="submit" name="login" id="login" class="btn WD200" value="ログインする" />
</div>
</div><!-- / #loginBox -->
</div><!-- / #loginContainer -->
</form>
</div>
@if (session('status'))<font id="statusMsg" color=red>{{ session('status') }}</font>@endif<br />
@foreach ($errors->get('login') as $message)<font id="errorMsg" color=red>{{ $message }}</font>@if (str_contains($message, 'ユーザーID'))<br>@endif
@endforeach


<div id="loginFooter"><img src="/system/img/{{ $craftfix ? 'img_login_craftfix_crm.png' : 'img_login_exlink_crm.png' }}" style="margin-top: -3px;" alt="Extlink" width="69"> Copyright (C) {{ $craftfix ? '2025' : '2017' }}-{{ date('Y') }} 総合業務管理システム CRM System Rights Reserved.</div>


</body>
</html>
