{{--
	回答解析 — legacy: category3/anketo(2)/anketo_answer_analyze.php
	Variables: $family, $cate, $items (form + stats de AnketoAnswerService::itemStats), $respondents (null = sin 回答状況),
	  $delivered (配信数; null en お客様アンケート), $isMaster (CSV), $listQuery
	Gráficos en <iframe> de /system/inc/graph_pie.php y graph_bar.php (núcleo), como el legacy.
	お客様アンケート: botón «内訳» → 回答一覧 filtrado por la respuesta.
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@php
	$customer = $family->isCustomer();
	$title = '『' . $cate->name . '』 回答解析';
	$letters = range('A', 'Z');
@endphp

@section('title', $title . ' ')

@section('css')
<link href="/system/css/popupwindow.css" rel="stylesheet" type="text/css" media="all" />
@endsection

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$("{{ $family->ac() }}").accordion('option', 'active', 0);
	window.scroll(0,0);
});
@if ($customer)

// 回答内訳ボタン =======================================================================

function viewAnswerDetail( formCD, answer ) {

	location.href = '{{ $family->route('answers', ['pcd' => $cate->cd, 'kensu2' => $listQuery['kensu'] ?? '', 'page2' => $listQuery['page'] ?? '', 'which' => 'sended']) }}&fcd='+ formCD +'&answer='+ encodeURIComponent(answer);

}

// ======================================================================================
@endif
</script>
<script type="text/javascript" src="/system/js/popupwindow-1.8.js"></script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■{{ $title }} </p>

<table class="tableBox">
<tr>
<th>回答状況</th>
<td>
@if ($customer)
<span style="font-size: 200%; font-weight: bold;">{{ $respondents }}</span>件の回答がありました。
@else
<span style="font-size: 180%; font-weight: bold;">{{ $delivered }}</span>人に配信して
<span style="font-size: 200%; font-weight: bold;">{{ $respondents }}</span>人が回答しました。
@endif
</td>
@foreach ($items as $item)
@php
	$form = $item['form'];
	$s = $item['stats'];
@endphp
<tr>
<th>{{ $form->name }}</th>
<td>

@if ($s['type'] === 'array')
@if (count($s['titles']) === 0)
回答はありません。
@endif
<ul style="display: inline-block; *display: inline; *zoom: 1; vertical-align: middle;min-width:150px;">
@foreach ($s['titles'] as $i => $optionTitle)
<li style="font-size: 120%;@if ($customer) margin-bottom: 1px;@endif">
@if ($customer)
<input type="button" name="detailBtn" class="btnStyle" value="内訳" onclick="viewAnswerDetail('{{ $form->cd }}', {{ json_encode((string) $optionTitle, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }})" style="padding: 2px 3px;" />
@endif
@if ($form->form_type === 'チェックボックス'){{ $letters[$i] ?? '' }}： @endif
<span style="">[{{ $optionTitle }}]</span>
<span style="margin-left: 3px; font-weight: bold;">{{ $s['counts'][$i] }}</span>人
@if ($form->form_type !== 'チェックボックス')
({{ number_format($s['total'] > 0 ? $s['counts'][$i] * 100 / $s['total'] : 0, 1) }}%)
@endif
</li>
@endforeach
</ul>
@else
<a href="#" onclick="new $pop('{{ $family->route('answer', ['pcd' => $cate->cd, 'frmcd' => $form->cd]) }}', { type: 'iframe', title: {{ json_encode($form->name . '（詳細）', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }}, width: 800, height: 650, windowmode:true, resize: true } ); return false;"><img value="詳細" src="/system/img/btn_detail_off.gif"></a>
@endif
@if (in_array($form->form_type, ['セレクト', 'ラジオボタン'], true))
<iframe src="/system/inc/graph_pie.php?w=500&amp;h=150&amp;v={{ rawurlencode(collect($s['titles'])->map(fn ($t, $i) => $t . ',' . $s['counts'][$i])->implode('|')) }}" frameborder="0" width="500" height="170" scrolling="no" style="display: inline-block; *display: inline; *zoom: 1; margin-left: 30px; vertical-align: middle; overflow:hidden"></iframe>
@elseif ($form->form_type === 'チェックボックス')
<iframe src="/system/inc/graph_bar.php?b=FFFFFF&amp;gb=CCCCCC&amp;lc=000000&amp;gtype=cylinder&amp;l=false&amp;w=500&amp;h=150&amp;x={{ rawurlencode(',' . implode(',', array_slice($letters, 0, count($s['titles'])))) }}&amp;y={{ rawurlencode('回答数') }}&amp;bc=005DFF&amp;v={{ rawurlencode(',' . implode(',', $s['counts'])) }}" frameborder="0" width="550" height="170" scrolling="no" style="display: inline-block; *display: inline; *zoom: 1; margin-left: 30px; vertical-align: middle; overflow:hidden"></iframe>
@endif

</td>
</tr>
@endforeach
</table>

@if ($isMaster)
<form method="post" action="{{ $family->route('csv') }}">
	<input name="pcd" type="hidden" value="{{ $cate->cd }}" />
	<input type="submit" name="enter" value="CSV出力する" />
</form>
@endif

<br />
<br />
<a href="{{ $family->route('index', $listQuery) }}"><img src="/system/img/btn_back_list_off.gif" width="90" height="15" alt="" /></a>

<!-- / .contents --></div>
@endsection
