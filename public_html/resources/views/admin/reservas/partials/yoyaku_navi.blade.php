{{--
    Legacy: system/category9/yoyaku/inc/yoyaku_navi.php (navegación propia del 予約管理, sin menú lateral).
    Variables (todas opcionales; sirve para cualquier pantalla de reservas):
      $d                ?string  `d` de la petición (Ymd) para 店舗毎予約数; por defecto request('d').
      $isTablet         bool     terminal tablet: abre con roid.popUp() en lugar de openPopWindow().
      $staffWord        string   manageWord('スタッフ', 'c9_yoyaku[スタッフ]'); por defecto 'スタッフ'.
      $yoyakuSetsubiFlg ?string  admin_master.yoyaku_setsubi_flg (cd = 1): '1' muestra 設備管理.
      $freeYoyakuFlg    ?string  admin_master.free_yoyaku_flg (cd = 1): '1' muestra フリー予約.
    Permisos: myKengen[1027|1028|1029][1] o rank master → @can('kengen', [N, 1]) (el Gate cubre a master).
--}}
@php
    $naviD = $d ?? request('d');
    $naviIsTablet = $isTablet ?? false;
    // fnc_return_openPopWindow(): script del onclick para abrir la ventana emergente
    $naviPopScript = fn (string $url, string $width, string $height) => $naviIsTablet
        ? "roid.popUp('".$url."');"
        : "openPopWindow('".$url."', '".$width."', '".$height."');";
@endphp
<ul id="yoyakuNavi" class="yoyakuMenu">
<li><a href="{{ route('nucleo.select') }}">総合TOPへ</a></li>
<li><a href="{{ route('admin.reservas.timetable') }}">本日</a></li>
<li><a href="{{ route('admin.reservas.by-shop', ['d' => $naviD]) }}">店舗毎予約数</a></li>
@can('kengen', [1027, 1])<li><a href="javascript:void(0);" class="popWindowAnchor" onclick="{{ $naviPopScript(route('admin.reservas.menus.index'), '600', '') }}">予約項目管理</a></li>@endcan
@can('kengen', [1028, 1])<li><a href="javascript:void(0);" class="popWindowAnchor" onclick="{{ $naviPopScript(route('admin.reservas.staff-works'), '600', '') }}">{{ $staffWord ?? 'スタッフ' }}割当</a></li>@endcan
@can('kengen', [1029, 1])<li><a href="javascript:void(0);" class="popWindowAnchor" onclick="{{ $naviPopScript(route('admin.reservas.settings.shop'), '600', '600') }}">予約設定</a></li>@endcan

@if (($yoyakuSetsubiFlg ?? null) == '1')<li><a href="javascript:void(0);" class="popWindowAnchor" onclick="{{ $naviPopScript(route('admin.reservas.facilities.index'), '600', '') }}">設備管理</a></li>@endif

@if (($freeYoyakuFlg ?? null) == '1')<li><a href="javascript:void(0);" class="popWindowAnchor" onclick="{{ $naviPopScript(route('admin.reservas.free'), '600', '') }}">フリー予約</a></li>@endif
</ul>
