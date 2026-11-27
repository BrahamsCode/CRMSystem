{{-- Legacy: system/category3/reservation/reserve_new_register.php sin act válido (texto $error del legacy, que nunca se
     llegaba a imprimir: aquí se muestra).
     Variables:
       $siteName  nombre del sitio
       $hidden    campos del formulario que reenvía 「登録内容を修正する」 --}}
@extends('layouts.legacy')

@section('menu', 'promocion')
@section('title', '予約新規登録 - ' . $siteName)

@section('content')
<div class="contents">
<p class="h2Title">■予約新規登録 確認</p>

<p>エラーが発生しました。<br />大変お手数ですが、再度<a href="{{ route('admin.reservas.simple.customer-search') }}">予約新規登録</a>よりお進みください。</p>

<div class="btnBox">
<form method="post" action="{{ route('admin.reservas.simple.create') }}">
@csrf
<input type="submit" name="modoru" id="act" value="登録内容を修正する" />
@foreach ($hidden as $key => $val)
<input type="hidden" name="{{ $key }}" value="{{ $val }}">
@endforeach
</form>
</div>

<!-- / .contents --></div>
@endsection
