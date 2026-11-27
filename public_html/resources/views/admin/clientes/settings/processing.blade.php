{{-- Legacy: system/category1/customer/form_config_register.php, search_cate_{new,update,delete}_up.php y
     search_form_{new,update,delete}_up.php: pantalla intermedia 「登録中です。」/「削除中です。」 que vuelve a la lista
     con <meta http-equiv="Refresh" content="2; URL=…">.
     Variables:
       $title        <title> completo
       $heading      texto del h2Title (con ■)
       $message      '登録中です。' | '削除中です。'
       $redirectUrl  URL de vuelta (lista)
       $navLinks     (opcional) [[url, texto], …]: enlaces 新規追加 / 登録一覧 de encima de .contents --}}
@extends('layouts.legacy')

@section('menu', 'clientes')
@section('title', $title)

@section('css')
<meta http-equiv="Refresh" content="2; URL={{ $redirectUrl }}">
@endsection

@section('content')
@if (! empty($navLinks))
@foreach ($navLinks as [$url, $text])<a href="{{ $url }}">{{ $text }}</a>@if (! $loop->last) / @endif @endforeach

@endif

<div class="contents">
<p class="h2Title">{{ $heading }}</p>


<div class="center"><p><img src="{{ asset('legacy/system/img/loading.gif') }}" alt="{{ $message }}" />
<br />{{ $message }}</p></div>


<!-- / .contents --></div>
@endsection
