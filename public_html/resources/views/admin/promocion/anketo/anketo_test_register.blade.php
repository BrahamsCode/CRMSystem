{{--
	アンケートテスト 送信 — legacy: category3/anketo(2)/anketo_test_register.php («送信中です» y vuelta con ok=ok)
	Variables: $family, $next
--}}
@extends('layouts.legacy')

@section('title', 'アンケートテスト 送信')

@section('css')
<meta http-equiv="Refresh" content="1; URL={{ $next }}">
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■アンケートテスト 送信</p>



<p class="center"><img src="/system/img/loading.gif" alt="送信中です。" />
<br />送信中です。</p>




<!-- / .contents --></div>
@endsection
