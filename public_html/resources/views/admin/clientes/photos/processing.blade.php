{{-- Legacy: system/category1/inc/photoalbum_register.php (「登録中です。」, 1 s) y photoalbum_delete.php (「削除中です。」, 2 s):
     pantalla intermedia que vuelve a la ficha con meta Refresh.
     Variables:
       $title      string: <title>
       $heading    string: texto de p.h2Title (sin ■)
       $message    '登録中です。' | '削除中です。'
       $seconds    int: segundos del meta Refresh
       $detailUrl  string: ficha a la que vuelve (tab, from=reg, photoalbum_cd) --}}
@extends('layouts.legacy')

@section('menu', 'clientes')
@section('title', $title)

@section('css')
<meta http-equiv="Refresh" content="{{ $seconds }}; URL={{ $detailUrl }}">
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■{{ $heading }}</p>


<div class="center"><p><img src="{{ asset('legacy/system/img/loading.gif') }}" alt="登録中です。" />
<br />{{ $message }}</p></div>


<!-- / .contents --></div>
@endsection
