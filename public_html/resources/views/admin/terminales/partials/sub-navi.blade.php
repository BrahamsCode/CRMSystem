{{--
    Sub-navegación de 電子看板管理 ($sub_navi del legacy): «新規追加 / 一覧».

    Parámetros:
      $family       string  'materials' (放映素材) | 'programs' (放映プログラム) | 'displays' (ディスプレイ)
      $createQuery  array   (opcional) query del enlace 新規追加.
                            materials: ['tb_display_cd' => int] (como screen_saver_list.php)
      $listQuery    array   (opcional) query del enlace al listado. Por defecto ['page' => ''],
                            igual que el legacy ($page aún no existe cuando se arma $sub_navi).
                            materials: ['page' => '', 'kensu' => int, 'display_cd' => int]

    El texto del enlace al listado es 検索・一覧 en materiales y 一覧 en el resto (igual que el legacy).
--}}
@php
    $subNaviRoute = 'admin.terminales.signage-' . $family;
    $subNaviListLabel = $family === 'materials' ? '検索・一覧' : '一覧';
@endphp
<a href="{{ route($subNaviRoute . '.create', $createQuery ?? []) }}">新規追加</a> / <a href="{{ route($subNaviRoute . '.index', $listQuery ?? ['page' => '']) }}">{{ $subNaviListLabel }}</a>
