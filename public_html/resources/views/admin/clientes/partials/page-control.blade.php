{{-- Legacy: system/category1/inc/page_control.php (solo el HTML de $page_control; la consulta paginada la hace el controlador).
     Variables: $paginator (Illuminate\Pagination\LengthAwarePaginator: página actual, total y kensu = perPage),
                $customer (modelo tb_customer, para la URL de la ficha),
                $params (array: parámetros GET que el legacy añadía en $search_str, en el mismo orden: tab, kaiin_cd, used…).
     Se imprime dentro de <ul class="btnBox2"> como en el legacy. --}}
@php
    $page = $paginator->currentPage();
    $maxpage = $paginator->lastPage();
    $kensu = $paginator->perPage();
    // URL de la página $p de la ficha con los mismos parámetros que el legacy (?page=N&…&kensu=K)
    $pageUrl = fn ($p) => route('admin.clientes.show', array_merge([$customer->cd, 'page' => $p], $params, ['kensu' => $kensu]));
@endphp
@if ($paginator->total() > 0)
@if ($page != 1 && $kensu < $paginator->total())
<li class=""><a href="{{ $pageUrl($page - 1) }}" class="btnStyleS" style="padding: 5px; border: 1px solid #527B86; background: transparent;">戻る</a></li>@endif
@if ($maxpage > 1)
@for ($l = max(1, $page - 2); $l <= min($maxpage, $page + 2); $l++)
@if ($l == $page)<li class="now"><b>{{ $l }}</b></li>@else<li><a href="{{ $pageUrl($l) }}">{{ $l }}</a></li>@endif
@endfor
@endif
@if ($page < $maxpage)
<li class=""><a href="{{ $pageUrl($page + 1) }}" class="btnStyleS" style="padding: 5px; border: 1px solid #527B86; background: transparent;">次へ</a></li>@endif
@endif
