{{-- Legacy: system/category1/customer/customer_list.php ($page_control: ページコントロール).
     Variables:
       $page          página actual (1…)
       $maxPage       total de páginas (ceil($total / $kensu))
       $kensu         表示件数
       $total         total de clientes encontrados
       $searchUniqid  id del buscador en sesión (varias ventanas a la vez)
--}}
@php
    $pageUrl = fn (int $p) => route('admin.clientes.index', ['page' => $p, 'kensu' => $kensu, 'se_flg' => 1, 'search_uniqid' => $searchUniqid]);
@endphp
{{-- 前のページへ --}}
@if ($page != 1 && $kensu < $total)
<li class="btn"><a href="{{ $pageUrl($page - 1) }}"><img src="{{ asset('legacy/system/img/btn_back_off.gif') }}" width="92" height="32" alt="戻る" /></a></li>
@endif
{{-- 数字 --}}
@if ($maxPage > 1)
@for ($l = max(1, $page - 5); $l <= min($maxPage, $page + 5); $l++)
@if ($l == $page)
<li class="now"><b>{{ $l }}</b></li>
@else
<li><a href="{{ $pageUrl($l) }}">{{ $l }}</a></li>
@endif
@endfor
@endif
{{-- 次のページへ --}}
@if ($page < $maxPage)
<li class="btn"><a href="{{ $pageUrl($page + 1) }}"><img src="{{ asset('legacy/system/img/btn_next_off.gif') }}" width="92" height="32" alt="次へ" /></a></li>
@endif
