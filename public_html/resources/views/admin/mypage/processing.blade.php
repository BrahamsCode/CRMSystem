{{--
    Pantallas intermedias 「登録中です。」/「削除中です。」 de los *_register.php / *_delete.php del legacy:
    la acción ya está hecha y la página vuelve a la lista con <meta http-equiv="Refresh" content="2; …">.

    Contrato:
        $screen      string  'recommend-store' | 'recommend-destroy' | 'recruit-destroy'
                             | 'recruit-category-store' | 'recruit-category-destroy'
        $menuName    string  nombre del menú de オススメ (solo recommend-store)
        $refreshUrl  string  destino del Refresh
        $siteName    string  (MypageServiceProvider)
--}}
@php
    [$title, $heading, $message, $accordion] = match ($screen) {
        'recommend-store' => [$menuName . '管理 - ' . $siteName, $menuName . '情報登録', '登録中です。', 'ac1'],
        'recommend-destroy' => ['お勧め削除 ' . $siteName, 'お勧め削除', '削除中です。', 'ac1'],
        'recruit-destroy' => ['求人情報 ' . $siteName, '求人情報 削除', '削除中です。', 'ac3'],
        'recruit-category-store' => ['職種登録 ' . $siteName, '職種登録', '登録中です。', 'ac3'],
        'recruit-category-destroy' => ['職種削除 ' . $siteName, '職種削除', '削除中です。', 'ac3'],
    };
@endphp
@extends('layouts.legacy')

@section('title', $title)

@section('menu', 'mypage')

@section('css')
<meta http-equiv="Refresh" content="2; URL={{ $refreshUrl }}">
@endsection

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac1").accordion({ collapsible: true , active: false});
	$(".ac2").accordion({ collapsible: true , active: false});
	$(".ac3").accordion({ collapsible: true , active: false});
	$(".ac4").accordion({ collapsible: true , active: false});
	$(".ac5").accordion({ collapsible: true , active: false});
	$(".ac6").accordion({ collapsible: true , active: false});
	$(".ac7").accordion({ collapsible: true , active: false});
	$(".ac8").accordion({ collapsible: true , active: false});
	$(".{{ $accordion }}").accordion('option', 'active', 0);
});
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■{{ $heading }}</p>


<div class="center"><p><img src="{{ asset('legacy/system/img/loading.gif') }}" alt="{{ $message }}" />
<br />{{ $message }}</p></div>

<!-- / .contents --></div>
@endsection
