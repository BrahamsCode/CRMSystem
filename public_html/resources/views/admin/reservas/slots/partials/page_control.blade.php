{{--
    Legacy: system/category1/inc/page_control.php (solo el HTML de $page_control) en el 予約者一覧 de yoyaku_uketsuke_day.php.
    Variables: $paginator (LengthAwarePaginator: página, total y kensu = perPage),
               $params (parámetros de la petición sin page/kensu, como el $search_str del legacy).
--}}
@php
    $page = $paginator->currentPage();
    $maxpage = $paginator->lastPage();
    $kensu = $paginator->perPage();
    $pageUrl = fn ($p) => route('admin.reservas.slots.edit', array_merge(['page' => $p], $params, ['kensu' => $kensu]));
@endphp
@if ($page != 1 && $kensu < $paginator->total())
<li class=""><a href="{{ $pageUrl($page - 1) }}" class="btnStyleS" style="padding: 5px; border: 1px solid #527B86; background: transparent;">戻る</a></li>@endif
@if ($maxpage > 1)
@for ($l = max(1, $page - 2); $l <= min($maxpage, $page + 2); $l++)
@if ($l == $page)<li class="now"><b>{{ $l }}</b></li>@else<li><a href="{{ $pageUrl($l) }}">{{ $l }}</a></li>@endif
@endfor
@endif
@if ($page < $maxpage)
<li class=""><a href="{{ $pageUrl($page + 1) }}" class="btnStyleS" style="padding: 5px; border: 1px solid #527B86; background: transparent;">次へ</a></li>@endif
