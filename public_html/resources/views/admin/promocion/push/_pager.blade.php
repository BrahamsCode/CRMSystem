{{--
	Paginador de category3/inc/page_control.php: 戻る, números (±5) y 次へ.
	Variables: $url (ruta de la lista), $params (condiciones de búsqueda, sin page), $page, $kensu, $maxrows
--}}
@php
	$maxpage = $maxrows > 0 ? (int) ceil($maxrows / $kensu) : 1;
	$link = fn (int $p) => $url . '?' . http_build_query(['page' => $p] + $params + ['kensu' => $kensu]);
@endphp
@if ($page != 1 && $kensu < $maxrows)
<li class="btn"><a href="{{ $link($page - 1) }}"><img src="/system/img/btn_back_off.gif" width="92" height="32" alt="戻る" /></a></li>
@endif
@if ($maxpage > 1)
@for ($l = max(1, $page - 5); $l <= min($maxpage, $page + 5); $l++)
@if ($l == $page)
<li class="now"><b>{{ $l }}</b></li>
@else
<li><a href="{{ $link($l) }}">{{ $l }}</a></li>
@endif
@endfor
@endif
@if ($page < $maxpage)
<li class="btn"><a href="{{ $link($page + 1) }}"><img src="/system/img/btn_next_off.gif" width="92" height="32" alt="次へ" /></a></li>
@endif
