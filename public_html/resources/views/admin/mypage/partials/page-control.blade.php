{{--
    Enlaces de system/category1/inc/page_control.php ($page_control): 戻る, números (página ±2) y 次へ.
    Recibe: $pager (LegacyPageControl::paginate) y $pageUrl (URL de la lista sin query).
    Se imprime dentro del <ul class="btnBox2"> de cada pantalla.
--}}
@if ($pager['total'] > 0)
@if ($pager['page'] != 1 && $pager['kensu'] < $pager['total'])
<li class=""><a href="{{ $pageUrl }}?page={{ $pager['page'] - 1 }}&kensu={{ $pager['kensu'] }}" class="btnStyleS" style="padding: 5px; border: 1px solid #527B86; background: transparent;">戻る</a></li>
@endif
@if ($pager['maxPage'] > 1)
@for ($l = max(1, $pager['page'] - 2); $l <= min($pager['maxPage'], $pager['page'] + 2); $l++)
@if ($l == $pager['page'])
<li class="now"><b>{{ $l }}</b></li>
@else
<li><a href="{{ $pageUrl }}?page={{ $l }}&kensu={{ $pager['kensu'] }}">{{ $l }}</a></li>
@endif
@endfor
@endif
@if ($pager['page'] < $pager['maxPage'])
<li class=""><a href="{{ $pageUrl }}?page={{ $pager['page'] + 1 }}&kensu={{ $pager['kensu'] }}" class="btnStyleS" style="padding: 5px; border: 1px solid #527B86; background: transparent;">次へ</a></li>
@endif
@endif
