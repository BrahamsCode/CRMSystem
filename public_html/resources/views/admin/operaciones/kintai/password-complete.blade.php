{{-- Legacy: system/category5/kintai/password_update_complete.php. Variables: $shopCd --}}
@extends('layouts.legacy')

@section('menu', 'operaciones')

@section('title', '端末パスワード変更 - '.($siteName ?? '').' - ')

@section('body_class', 'passwordUpdate')

@section('content')
<div class="contents">

<p class="h2Title">■端末パスワード変更</p>


<p>端末パスワード変更しました。</p>


<form class="form" method="get" action="{{ route('admin.operaciones.kintai.password.edit') }}">
<div class="btnBox">
<input type="submit" class="btnStyleM" value="　　戻る　　" />
<input type="hidden" name="shop" value="{{ $shopCd }}" />
<!-- □□ /.btnBox □□ --></div>
</form>

<!-- / .contents --></div>
@endsection
