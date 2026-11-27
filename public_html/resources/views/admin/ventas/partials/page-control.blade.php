{{--
    Paginación de system/category1/inc/page_control.php: 戻る, hasta 2 páginas a cada lado y 次へ.
    Datos: $page (LegacyPageControl::paginate), $pageUrl (URL de la pantalla), $query (filtros a repetir)
--}}
@php
    $link = fn (int $p) => $pageUrl . '?' . http_build_query(['page' => $p] + $query + ['kensu' => $page['kensu']]);
@endphp
@if ($page['page'] != 1 && $page['kensu'] < $page['total'])
<li class=""><a href="{{ $link($page['page'] - 1) }}" class="btnStyleS" style="padding: 5px; border: 1px solid #527B86; background: transparent;">戻る</a></li>
@endif
@if ($page['maxPage'] > 1)
@for ($l = max(1, $page['page'] - 2); $l <= min($page['maxPage'], $page['page'] + 2); $l++)
@if ($l == $page['page'])
<li class="now"><b>{{ $l }}</b></li>
@else
<li><a href="{{ $link($l) }}">{{ $l }}</a></li>
@endif
@endfor
@endif
@if ($page['page'] < $page['maxPage'])
<li class=""><a href="{{ $link($page['page'] + 1) }}" class="btnStyleS" style="padding: 5px; border: 1px solid #527B86; background: transparent;">次へ</a></li>
@endif
