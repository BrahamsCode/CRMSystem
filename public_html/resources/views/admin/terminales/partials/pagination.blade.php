{{--
    Paginación de 電子看板管理 ($page_control de system/category7/inc/page_control.php).
    Imprime solo los <li>; la vista los envuelve en <ul class="btnBox2"> como el legacy.

    Parámetros:
      $paginator   Illuminate\Pagination\LengthAwarePaginator  filas de la página actual (perPage = kensu)
      $baseUrl     string  URL del listado sin query (p. ej. route('admin.terminales.signage-displays.index'))
      $extraQuery  array   (opcional) query adicional. El legacy solo propaga page y kensu
                           (se pierde display_cd al cambiar de página); por defecto [] para igualarlo.

    Reglas del legacy:
      - «戻る»: si page != 1 y kensu < total.
      - números: si hay más de una página, desde page-5 hasta page+5; la actual como <li class="now"><b>n</b></li>.
      - «次へ»: si page < última página.
--}}
@php
    $pcPage = $paginator->currentPage();
    $pcMaxPage = $paginator->lastPage();
    $pcKensu = $paginator->perPage();
    $pcUrl = fn (int $n): string => $baseUrl . '?' . http_build_query(array_merge(['page' => $n, 'kensu' => $pcKensu], $extraQuery ?? []));
@endphp
@if ($pcPage != 1 && $pcKensu < $paginator->total())
<li class="btn"><a href="{{ $pcUrl($pcPage - 1) }}"><img src="{{ asset('legacy/system/img/btn_back_off.gif') }}" width="92" height="32" alt="戻る" /></a></li>
@endif
@if ($pcMaxPage > 1)
@for ($pcLink = max(1, $pcPage - 5); $pcLink <= min($pcMaxPage, $pcPage + 5); $pcLink++)
@if ($pcLink == $pcPage)
<li class="now"><b>{{ $pcLink }}</b></li>
@else
<li><a href="{{ $pcUrl($pcLink) }}">{{ $pcLink }}</a></li>
@endif
@endfor
@endif
@if ($pcPage < $pcMaxPage)
<li class="btn"><a href="{{ $pcUrl($pcPage + 1) }}"><img src="{{ asset('legacy/system/img/btn_next_off.gif') }}" width="92" height="32" alt="次へ" /></a></li>
@endif
