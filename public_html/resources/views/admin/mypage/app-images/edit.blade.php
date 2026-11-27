{{--
    マイコレ管理 (アプリ画像) — reescritura de 5 pantallas legacy de system/category6/user_hp/:

        $section      legacy                                   título del bloque visible
        'all'         mobile_mycolle_edit.php                  マイコレHOMEに追加するコメント + 顧客グループ表示
        'cover'       mobile_mycolle_edit_coverflow.php        マイページアプリのカバーフロー画像設定
        'banner'      mobile_mycolle_edit_banner.php           マイページアプリのバナー画像設定
        'main'        mobile_mycolle_edit_main.php             マイページアプリのHOMEに表示されるメインイメージ
        'thumbnail'   mobile_mycolle_edit_thumbnail.php        マイページアプリのHOMEに表示されるサムネイル画像

    El legacy imprime las 6 secciones en cada pantalla y oculta con style="display:none" las que no
    tocan. Aquí solo se renderiza la sección visible (formularios, diálogos y JS); los bloques <style>
    sí se imprimen todos y en el mismo orden, porque son globales y unos dependen de otros
    (p. ej. .imageFrame.loading:after vive en el bloque de la メインイメージ).

    ===================================================================================================
    CONTRATO DE DATOS
    ===================================================================================================

    $section  string  'all' | 'cover' | 'banner' | 'main' | 'thumbnail'.

    $applicationUseFlg  bool  admin_master.application_use_flg == 1 (cd = 1). Si es false el legacy no
                        imprime nada dentro de .main.

    $siteName  string  Nombre del sitio para el <title> (legacy: $site_name de inc/db.php). Lo pone
               MypageServiceProvider (composer de admin.mypage.*).

    $shops  iterable (Collection o array) de filas de tb_shop con disp_flg = '1' ORDER BY cd
            (legacy: $shop_data_ary). Cada fila admite acceso por índice (array o modelo Eloquent) con:
                cd                int
                kaishamei         string
                shop_category_cd  int|null   (solo se imprime en data-category)
            Lo usan: cover/banner (si está vacío no sale el recuadro 表示店舗), main/thumbnail (un hueco por
            tienda) y la comentario HOME (un textarea por tienda).

    $mypageConfig  array  contents_master(category='tb_c6_user_hp', field='mypage_config', ORDER BY cd DESC
                   LIMIT 1).text_data con str_replace("'", '"') + unserialize, tal cual el legacy
                   ([] si no hay fila). Aquí solo se lee 'comment_flg' => [shop_cd => 1]; el resto de
                   claves llega ya digerido en $slots.

    $slots  list<array>  Huecos de la sección. Forma según $section:

        'cover'  → 21 huecos (índices 0..20), legacy: imágenes de hp/uploads/c6/aplicover/<n>.<ext>
                   y enlaces de contents_master field='aplicover' (text_data separado por '||').
            [
                'index'     => int,           // n
                'imageUrl'  => string|null,   // asset('hp/uploads/c6/aplicover/<n>.<ext>').'?t='.filemtime, null si no hay archivo
                'linkUrl'   => string,        // explode('||', text_data)[n] ?? ''
                'allShops'  => bool,          // isset($mypageConfig['cover'][n][0])
                'shopFlags' => [shop_cd => bool], // isset($mypageConfig['cover'][n][shop_cd])
            ]

        'banner' → 21 huecos, legacy: hp/uploads/c6/aplibanner/<n>.jpg|gif y field='aplibanner'.
            [
                'index', 'imageUrl', 'linkUrl', 'allShops', 'shopFlags'  // igual que 'cover', con clave 'banner'
                'cellStatus' => int,          // $mypageConfig['cell_status'][n] ?? 1  (1 = 自由選択, 2 = ﾃﾞﾌｫﾙﾄ選択)
                'cellImg'    => string|null,  // $mypageConfig['cell_img'][n] ?? null (archivo de $bannerCellList)
            ]

        'main' / 'thumbnail' → count($shops) + 1 huecos. Índice 0 = 全店舗用; índice i > 0 = $shops[i-1].
                   Imágenes en hp/uploads/c6/apli_home_image/<shop_cd>.<ext> (main) o
                   hp/uploads/c6/apli_home_thumbnail/<shop_cd>.<ext> (thumbnail), con shop_cd = 0 en el
                   hueco 0. Se busca por shop_cd, que es lo que lee la API de la app (el legacy mostraba
                   el archivo del índice i: bug corregido).
            [
                'index'            => int,
                'shop'             => fila de $shops|null,   // null en el índice 0
                'imageUrl'         => string|null,           // con ?t=filemtime
                'useAllShopsImage' => bool,  // $mypageConfig['home_image_flg'|'home_thum_flg'][shop_cd] == 1
            ]

        'all' → [] (no se usa).

    $homeComments  array  contents_master field='apli_home_comment' (ORDER BY cd DESC LIMIT 1).text_data con
                   str_replace("'", '"') + unserialize: [0 => texto 全店舗, shop_cd => texto]. [] si no hay fila.
                   Solo en 'all'.

    $customerGroupDisplay  bool  contents_master field='apli_customer_group_disp'.int_data == '1'. Solo en 'all'.

    $bannerCellList  list<string>  Archivos de hp/img/banner/ que se ofrecen en ﾃﾞﾌｫﾙﾄ選択 (lista fija del
                     legacy mobile_mycolle_edit_banner.php, incluido el 'tabelog_1.jpg' repetido). Solo en 'banner'.

    $areaTree  list<array>  Árbol de tb_area para la selección de tiendas por área (cover/banner), legacy
               Area::createAreaCheckUi(). Raíces: disp_flg <> '9' AND flg = '1' AND (pid IS NULL OR pid < 1);
               hijos: mismo filtro con pid = cd del padre. Orden: row_lis.
                   ['cd' => int, 'name' => string, 'children' => list<array> (misma forma)]

    $areaShops  list<array>  Tiendas para el árbol de áreas, legacy Shop::getList(): tb_shop disp_flg = '1'
                ORDER BY touroku_date DESC.
                   [
                       'cd'          => int,
                       'kaishamei'   => string,
                       'areaCd'      => int|string|null,   // tb_shop.area_cd
                       'isClosed'    => bool,              // tb_shop.is_closed → añade " (閉店)"
                       'withoutArea' => bool,              // area_cd === '0' o null, o su área tiene disp_flg = '9'
                                                           // o flg <> '1', o el área no existe en tb_area
                   ]

    $areaDepthMax  int  Profundidad máxima del árbol de áreas (Area::$depth_max; raíz = 1). Solo para el CSS.

    $tempImageUrl  string  URL base (terminada en '/') donde se ven las imágenes recién subidas antes de
                   guardarlas (legacy: ./uploads/temp/ relativo a category6/user_hp). El JS le concatena
                   el imageName que devuelve legacy.mypage.apli-file-upload.
--}}
@extends('layouts.legacy')

@section('title', 'ショップページ設定 - ' . ($siteName ?? config('app.name')))

@section('menu', 'mypage')

{{-- El legacy usa $useLatestJquery = true (inc/head.php): jQuery 1.11.2 + jQuery UI 1.11.4 --}}
@section('jquery', 'jquery-1.11.2.min.js')
@section('jquery_ui', 'jquery-ui-1.11.4.min.js')

@section('css')
    @include('admin.mypage.app-images.partials.styles')
@endsection

@section('js')
    @include('admin.mypage.app-images.partials.scripts.page')
    @if ($applicationUseFlg)
        @switch($section)
            {{-- Mismo orden que el legacy: toggle 全店舗, JS del árbol de áreas (va dentro de cada hueco) y JS de la sección --}}
            @case('cover')
                @include('admin.mypage.app-images.partials.scripts.shop-select')
                @if (count($shops))
                    @include('admin.mypage.app-images.partials.scripts.area-check')
                @endif
                @include('admin.mypage.app-images.partials.scripts.cover')
                @break
            @case('banner')
                @include('admin.mypage.app-images.partials.scripts.shop-select')
                <script type="text/javascript" src="{{ asset('legacy/system/category6/user_hp/js/imageselect.js') }}"></script>
                @if (count($shops))
                    @include('admin.mypage.app-images.partials.scripts.area-check')
                @endif
                @include('admin.mypage.app-images.partials.scripts.banner')
                @break
            @case('main')
            @case('thumbnail')
                @include('admin.mypage.app-images.partials.scripts.home-images', ['home' => $section])
                @break
        @endswitch
    @endif
@endsection

@section('content')
    @if ($applicationUseFlg)
        @switch($section)
            @case('cover')
                @include('admin.mypage.app-images.partials.cover')
                @break
            @case('banner')
                @include('admin.mypage.app-images.partials.banner')
                @break
            @case('main')
            @case('thumbnail')
                @include('admin.mypage.app-images.partials.home-images', ['home' => $section])
                @break
            @default
                @include('admin.mypage.app-images.partials.home-comment')
                @include('admin.mypage.app-images.partials.customer-group')
        @endswitch
    @endif
@endsection
