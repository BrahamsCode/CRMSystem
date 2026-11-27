{{-- Legacy: system/category1/inc/page_control.php (solo el HTML de $page_control: 戻る, ventana de 5 páginas, 次へ).
     Variables: $paginator (LengthAwarePaginator), $pageUrl (Closure: número de página → URL con los parámetros del legacy).
     Se imprime dentro de <ul class="btnBox2">. --}}
@php
    $page = $paginator->currentPage();
    $maxpage = $paginator->lastPage();
@endphp
@if ($paginator->total() > 0)
@if ($page != 1 && $paginator->perPage() < $paginator->total())
<li class=""><a href="{{ $pageUrl($page - 1) }}" class="btnStyleS" style="padding: 5px; border: 1px solid #527B86; background: transparent;">戻る</a></li>@endif
@if ($maxpage > 1)
@for ($l = max(1, $page - 2); $l <= min($maxpage, $page + 2); $l++)
@if ($l == $page)<li class="now"><b>{{ $l }}</b></li>@else<li><a href="{{ $pageUrl($l) }}">{{ $l }}</a></li>@endif
@endfor
@endif
@if ($page < $maxpage)
<li class=""><a href="{{ $pageUrl($page + 1) }}" class="btnStyleS" style="padding: 5px; border: 1px solid #527B86; background: transparent;">次へ</a></li>@endif
@endif
