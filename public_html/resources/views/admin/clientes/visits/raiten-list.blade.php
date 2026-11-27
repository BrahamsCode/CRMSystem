{{-- Legacy: system/category1/inc/ajax_raiten_list.php (fragmento HTML que carga inc/js/raiten_list.js en #raiten_dialog).
     Variables:
       $visits  LengthAwarePaginator (10 por página) de tb_raiten_rireki JOIN tb_shop: cd, raiten_time (epoch), shop_cd, kaishamei
     JS usados de la página: showRaitenDialog(), setRaitenTimeSelect(), setRaiten(). --}}
@php
    $youbi = ['日', '月', '火', '水', '木', '金', '土'];
    $page = $visits->currentPage();
    $maxpage = $visits->lastPage();
    $kensu = $visits->perPage();
@endphp
<div style="height:280px;">
<table class="tableBox2" style="table-layout:fixed;">
@if ($visits->count() > 0)
	<tr style="line-height:15px;">
		<th width="55%" style="line-height:15px;">来店日時</th>
		<th width="45%" style="line-height:15px;">店舗</th>
	</tr>
@foreach ($visits as $row)
@php
    $time = is_numeric($row->raiten_time) ? \Illuminate\Support\Carbon::createFromTimestamp((int) $row->raiten_time, 'Asia/Tokyo') : null;
    $sendJs = $time ? "'" . $time->format('Y') . "', '" . $time->format('m') . "', '" . $time->format('d') . "', '" . $time->format('H') . "', '" . $time->format('i') . "'" : "'', '', '', '', ''";
@endphp
	{{-- $sendJs: fechas numéricas generadas aquí --}}
	<tr style="line-height:15px;cursor:pointer;background-color:{{ $loop->index % 2 == 0 ? '#FFFFFF' : '#EEEEEE' }}" onclick="setRaitenTimeSelect({!! $sendJs !!});setRaiten({{ (int) $row->cd }}, {{ (int) $row->shop_cd }});">
		<td>{{ $time?->format('Y/m/d H:i') }}&nbsp;({{ $time ? $youbi[$time->dayOfWeek] : '' }})</td>
		<td>{{ $row->kaishamei }}</td>
	</tr>
@endforeach
</table>
</div>
<ul class="btnBox2">
@if ($page != 1 && $kensu < $visits->total())
<li class="btn"><a href="javascript:showRaitenDialog({{ $page - 1 }});" ><img src="{{ asset('legacy/system/img/btn_back_off.gif') }}" width="92" height="32" alt="戻る" /></a></li>@endif
@if ($maxpage > 1)
@for ($l = max(1, $page - 5); $l <= min($maxpage, $page + 5); $l++)
@if ($l == $page)<li class="now"><b>{{ $l }}</b></li>@else<li><a href="javascript:showRaitenDialog({{ $l }});" >{{ $l }}</a></li>@endif
@endfor
@endif
@if ($page < $maxpage)
<li class="btn"><a href="javascript:showRaitenDialog({{ $page + 1 }});" ><img src="{{ asset('legacy/system/img/btn_next_off.gif') }}" width="92" height="32" alt="次へ" /></a></li>@endif
</ul>
@else
<p>データが見つかりませんでした</p>
@endif
