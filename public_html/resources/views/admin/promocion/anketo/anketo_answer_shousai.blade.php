{{--
	回答一覧 (respuestas de texto de un ítem, popup del 回答解析) — legacy: category3/anketo(2)/anketo_answer_shousai.php
	(+ ../inc/page_control.php). Variables: $family, $formCd, $pcd, $rows, $total, $page, $kensu, $maxPage, $from
	text_data se guarda como lo escribió el cliente: se imprime escapado.
--}}
@extends('layouts.legacy-popup')

@section('title', '')

@php
	$link = fn (int $p) => $family->route('answer', ['page' => $p, 'frmcd' => $formCd, 'pcd' => $pcd, 'kensu' => $kensu]);
@endphp

@section('js')
<script type="text/javascript">
//表示件数の制御

function kensuSet(parts){
	document.form_search.kensu.value = parts.value;
	document.form_search.submit();
}

</script>
@endsection

@section('content')
<form method="post" name="form_search" id="form_search" class="displayNone" action="{{ $family->route('answer') }}">
<input type="hidden" name="kensu" id="kensu" value="{{ $kensu }}" />
<input type="hidden" name="page" id="page" value="{{ $page }}" />
<input type="hidden" name="frmcd" id="frmcd" value="{{ $formCd }}" />
<input type="hidden" name="pcd" value="{{ $pcd }}" />
</form>

<div class="contents">
<p class="h2Title">■回答一覧</p>
@if ($total > 0)
<div id="form"><p class="maxListLength">表示件数<select name="kensu_act" id="kensu_act" class="selectBox" onchange="kensuSet(this)">
@for ($i = 1; $i <= 5; $i++)
<option value="{{ $i * 10 }}" @if ($kensu == $i * 10) selected @endif>{{ $i * 10 }}</option>
@endfor
</select></p></div>

<p><span class="attention">{{ $from + 1 }}件～{{ min($from + $kensu, $total) }}件目/{{ $total }}件中</span>
</p>

<div class="form">

<table class="tableBox2">
  <tr>
    <th width="{{ $family->isCustomer() ? '30px' : '50px' }}"></th>
    <th>回答内容</th>
@if ($family->isCustomer())
    <th width="50px"></th>
@endif
  </tr>
@foreach ($rows as $row => $answer)
  <tr @if ($row % 2 != 0) class="tableBg" @endif>
	<td>{{ $from + 1 + $row }}</td>

	<td>{!! nl2br(e($answer->text_data)) !!}</td>
@if ($family->isCustomer())
	<td class="center"><input type="button" name="detailBtn" class="btnStyle" value="内訳" onclick="parent.viewAnswerDetail('{{ $formCd }}', {{ json_encode((string) $answer->text_data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }})" /></td>
@endif
  </tr>
@endforeach
</table>

</div>

<ul class="btnBox2">
@if ($page != 1 && $kensu < $total)
<li class="btn"><a href="{{ $link($page - 1) }}"><img src="/system/img/btn_back_off.gif" width="92" height="32" alt="戻る" /></a></li>
@endif
@if ($maxPage > 1)
@for ($l = max(1, $page - 5); $l <= min($maxPage, $page + 5); $l++)
@if ($l == $page)
<li class="now"><b>{{ $l }}</b></li>
@else
<li><a href="{{ $link($l) }}">{{ $l }}</a></li>
@endif
@endfor
@endif
@if ($page < $maxPage)
<li class="btn"><a href="{{ $link($page + 1) }}"><img src="/system/img/btn_next_off.gif" width="92" height="32" alt="次へ" /></a></li>
@endif
</ul>

@else
データがありません。
@endif

<!-- / .contents --></div>
@endsection
