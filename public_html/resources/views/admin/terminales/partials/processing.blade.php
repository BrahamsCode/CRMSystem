{{--
    Pantalla intermedia «登録中です。» / «削除中です。» de 電子看板管理: la que imprimen
    screen_saver_register.php, screen_saver_delete.php, movie_program_register.php,
    movie_program_delete.php, display_register.php y display_delete.php antes de redirigir
    con <meta http-equiv="Refresh">. Es una vista completa (no un include): la devuelven
    store, update y destroy de las tres familias.

    Contrato de datos:
      $popup           bool         true = layouts.legacy-popup (放映素材, sin menú); false = layouts.legacy
      $pageTitle       string       texto del <title> y del h2 (sin «■»), igual que el legacy («$title $mode»):
                                    - 放映素材 登録: '放映ファイル管理 新規登録' | '放映ファイル管理 修正'
                                    - 放映素材 削除: '放映ファイル管理 ' ($mode no existe en el legacy)
                                    - 放映プログラム: '放映プログラム管理 新規登録' | '放映プログラム管理 修正' | '放映プログラム管理 '
                                    - ディスプレイ:   'ディスプレイ管理 新規登録' | 'ディスプレイ管理 修正' | 'ディスプレイ管理 '
      $message         string       '登録中です。' | '削除中です。' (también es el alt de loading.gif)
      $refreshUrl      string       destino del Refresh:
                                    - 素材 登録 → signage-materials.show (+ register_type, display_cd, page, kensu), 2 s
                                    - 素材 削除 → signage-materials.deleted, 1 s
                                    - プログラム/ディスプレイ 登録 → .show (+ register_type), 2 s
                                    - プログラム 削除 → signage-programs.index (+ shop_cd, movie_program_cd, page, kensu), 1 s
                                    - ディスプレイ 削除 → signage-displays.index (+ shop_cd, display_cd, page, kensu), 1 s
      $refreshSeconds  int          2 al registrar, 1 al borrar
      $subNaviFamily   string|null  'programs' | 'displays' en los 登録 de pantalla completa (el legacy imprime
                                    $sub_navi); null en los 削除 y en los popups de 素材
      $uploadErrors    array<string> (opcional) errores de pic_up.php («N枚目の画像 ※…») que
                                    screen_saver_register.php imprime debajo del mensaje

    El <meta http-equiv="Refresh"> va en la sección css porque es la que el layout pone dentro de <head>.
--}}
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
@if ($subNaviFamily ?? null)
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
