{{--
    Pantalla intermedia «登録中です。» / «削除中です。» de 電子看板管理: la que imprimen
    screen_saver_register.php, screen_saver_delete.php, movie_program_register.php,
    movie_program_delete.php, display_register.php y display_delete.php antes de redirigir
    con <meta http-equiv="Refresh">. Es una vista completa (no un include): la devuelven
    store, update y destroy de las tres familias.

    Contrato de datos:
      $family        string         'materials' (放映素材: popup sin menú) | 'programs' | 'displays'
      $action        string         'new' | 'update' | 'delete'
      $refreshUrl    string         destino del Refresh:
                                    - 素材 登録 → signage-materials.show (+ register_type, display_cd, page, kensu)
                                    - 素材 削除 → signage-materials.deleted
                                    - プログラム/ディスプレイ 登録 → .show (+ register_type)
                                    - プログラム 削除 → signage-programs.index (+ shop_cd, movie_program_cd, page, kensu)
                                    - ディスプレイ 削除 → signage-displays.index (+ shop_cd, display_cd, page, kensu)
      $uploadErrors  array<string>  (opcional) errores de subida («N枚目の画像 ※…») que
                                    screen_saver_register.php imprime debajo del mensaje

    Como el legacy: <title> y h2 = «$title $mode» ($mode vacío al borrar), Refresh de 2 s al
    registrar y de 1 s al borrar, y $sub_navi solo en los 登録 de pantalla completa.
    El <meta http-equiv="Refresh"> va en la sección css porque es la que el layout pone dentro de <head>.
--}}
@php
    $popup = $family === 'materials';
    $baseTitle = ['materials' => '放映ファイル管理', 'programs' => '放映プログラム管理', 'displays' => 'ディスプレイ管理'][$family];
    $modeText = ['new' => '新規登録', 'update' => '修正', 'delete' => ''][$action];
    $pageTitle = $baseTitle . ' ' . $modeText;
    $isDelete = $action === 'delete';
    $message = $isDelete ? '削除中です。' : '登録中です。';
    $refreshSeconds = $isDelete ? 1 : 2;
    $subNaviFamily = (! $popup && ! $isDelete) ? $family : null;
@endphp
@extends($popup ? 'layouts.legacy-popup' : 'layouts.legacy')

@section('menu', 'terminales')

@section('title', $pageTitle)

@section('css')
<meta http-equiv="Refresh" content="{{ $refreshSeconds }}; URL={{ $refreshUrl }}">
@endsection

@section('js')
@if (! $popup)
<script type="text/javascript">
$(document).ready(function() {
	$(".ac1").accordion('option', 'active', 0);
});
</script>
@endif
@endsection

@section('content')
@if ($subNaviFamily)
@include('admin.terminales.partials.sub-navi', ['family' => $subNaviFamily])
@endif
<div class="contents">
<p class="h2Title">■{{ $pageTitle }}</p>



<p class="center"><img src="{{ asset('legacy/system/img/loading.gif') }}" alt="{{ $message }}" />
<br />{{ $message }}</p>

@foreach ($uploadErrors ?? [] as $uploadError)
{{ $uploadError }}<br />
@endforeach



<!-- / .contents --></div>
@endsection
