{{-- Legacy: ページコントロール de work_list.php / work_list2.php / ng_work_list.php (<ul class="btnBox2">).
     Variables: $pager (lista de [tipo prev|now|num|next, página] armada por WorkingController::pager()),
       $routeName (ruta de la lista), $params (parámetros que conservan los enlaces) --}}
<ul class="btnBox2">
@foreach ($pager as [$type, $n])
@php($href = route($routeName, ['page' => $n] + $params))
@if ($type === 'prev')
<li class="btn"><a href="{{ $href }}"><img src="/system/img/btn_back_off.gif" width="92" height="32" alt="戻る" /></a></li>
@elseif ($type === 'now')
<li class="now"><b>{{ $n }}</b></li>
@elseif ($type === 'num')
<li><a href="{{ $href }}">{{ $n }}</a></li>
@else
<li class="btn"><a href="{{ $href }}"><img src="/system/img/btn_next_off.gif" width="92" height="32" alt="次へ" /></a></li>
@endif
@endforeach
</ul>
