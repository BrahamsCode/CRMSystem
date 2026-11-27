{{--
    Paginación de card_signage/cPageControl.php: como la de page_control.php pero con los textos 前へ / 次へ
    (sin imágenes). Imprime solo los <li>.

    Parámetros:
      $paginator   LengthAwarePaginator
      $baseUrl     string  URL de la pantalla sin query
      $extraQuery  array   (opcional) el «addstr» de cPageControl (aggregate.php no lo ponía y perdía cd: aquí sí)
--}}
@php
    $pcPage = $paginator->currentPage();
    $pcMaxPage = $paginator->lastPage();
    $pcKensu = $paginator->perPage();
    $pcUrl = fn (int $n): string => $baseUrl . '?' . http_build_query(array_merge(['page' => $n, 'kensu' => $pcKensu], $extraQuery ?? []));
@endphp
@if ($pcPage != 1 && $pcKensu < $paginator->total())
<li class="btn"><a href="{{ $pcUrl($pcPage - 1) }}">前へ</a></li>
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
<li class="btn"><a href="{{ $pcUrl($pcPage + 1) }}">次へ</a></li>
@endif
