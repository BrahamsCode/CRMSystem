{{--
    Legacy: system/category9/yoyaku/ajax_yoyaku_koumoku_new.php (lista que carga 予約項目管理 por AJAX).
    Variables:
      $shopCd          int    tienda de la lista (-1 = 全店舗)
      $isMaster        bool   rank master: el enlace 修正 lleva &shop=
      $baseSections    list<array{category: ?ReservationMenuCategory, menus: Collection<ReservationMenu>}>  ベースメニュー
      $optionSections  ídem  オプションメニュー
      $thumbnails      array<int, string>  [cd => URL de 1_s.jpg] de los menús con imagen
    El legacy imprimía `$shop_name` sin definir en los títulos: queda vacío igual.
--}}
<p class="h2Title">■ ベースメニュー登録情報一覧</p>

@foreach ($baseSections as $section)
@include('admin.reservas.partials.menu_list_table', ['section' => $section])
@endforeach


<p class="h2Title">■ オプションメニュー登録情報一覧</p>

@foreach ($optionSections as $section)
@include('admin.reservas.partials.menu_list_table', ['section' => $section])
@endforeach

※全店共通項目は全店舗設定時に情報変更が行えます。<br />
※全店共通項目は優先的に上部に表示されます。
