{{-- Legacy: 表示件数 y ページコントロール de working_list.php / working_total_list.php.
     El legacy armaba los enlaces con ?page= pero nunca leía `page` (sin register_globals): siempre la página 1.
     Aquí la página funciona y conserva la búsqueda de la sesión (se_flg=1&act=up).
     Variables: $part ('kensu' | 'links'), $screen, $kensu, $page, $maxPage, $total --}}
@php($url = fn (int $p) => route('admin.operaciones.working.'.$screen, ['page' => $p, 'kensu' => $kensu, 'se_flg' => 1, 'act' => 'up']))
@if ($part === 'kensu')
<p class="maxListLength">表示件数<select name="kensu_act" id="kensu_act" class="selectBox" onchange="kensuSet(this)"><option value="{{ $kensu }}" selected>{{ $kensu }}</option>
<option value="" ></option>
@for ($i = 1; $i <= $maxPage; $i++)
<option value="{{ $i * 10 }}" @selected($kensu === $i * 10)>{{ $i * 10 }}</option>
@endfor
</select></p>
@else
<ul class="btnBox2">
@if ($page != 1 && $kensu < $total)
<li class="btn"><a href="{{ $url($page - 1) }}"><img src="/system/img/btn_back_off.gif" width="92" height="32" alt="戻る" /></a></li>
@endif
@if ($maxPage > 1)
@for ($l = 1; $l <= $maxPage; $l++)
@if ($l === $page)
<li class="now"><b>{{ $l }}</b></li>
@else
<li><a href="{{ $url($l) }}">{{ $l }}</a></li>
@endif
@endfor
@endif
@if ($page < $maxPage)
<li class="btn"><a href="{{ $url($page + 1) }}"><img src="/system/img/btn_next_off.gif" width="92" height="32" alt="次へ" /></a></li>
@endif
</ul>
@endif
