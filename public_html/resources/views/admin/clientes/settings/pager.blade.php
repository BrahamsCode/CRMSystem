{{-- Legacy: system/category1/customer/inc/page.php ($page_control de search_cate_list.php y search_form_list.php).
     Variables:
       $page, $maxPage, $kensu, $total   página actual, nº de páginas, 表示件数, filas en total
       $count                            filas de la página actual (sin filas no hay paginador)
       $pageUrl                          Closure(int $page): string con la URL de esa página
     Se imprime dentro de <ul class="btnBox2">. --}}
@if ($count > 0)
@if ($page != 1 && $kensu < $total)
<li class="btn"><a href="{{ $pageUrl($page - 1) }}"><img src="{{ asset('legacy/system/img/btn_back_off.gif') }}" width="92" height="32" alt="戻る" /></a></li>
@endif
@if ($maxPage > 1)
@for ($l = max(1, $page - 5); $l <= min($maxPage, $page + 5); $l++)
@if ($l == $page)
<li class="now"><b>{{ $l }}</b></li>
@else
<li><a href="{{ $pageUrl($l) }}">{{ $l }}</a></li>
@endif
@endfor
@endif
@if ($page < $maxPage)
<li class="btn"><a href="{{ $pageUrl($page + 1) }}"><img src="{{ asset('legacy/system/img/btn_next_off.gif') }}" width="92" height="32" alt="次へ" /></a></li>
@endif
@endif
