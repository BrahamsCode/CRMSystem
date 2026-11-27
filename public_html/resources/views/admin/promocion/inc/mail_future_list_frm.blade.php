{{--
	予約配信一覧 — legacy: system/category3/inc/mail_future_list_frm.php
	(mail_mag, decomail; más adelante follow, auto, reminder con 起動/停止)
	Variables: las comunes de MailController y $mails (paginador), $perPage, $pageTitle, $shopNames, $kengenNo
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', $pageTitle . '一覧' . ' - ' . $siteName)

@php
	$recurring = in_array($mailType, ['follow', 'followdeco', 'auto', 'autodeco'], true);
	$canEdit = $isMaster || Gate::allows('kengen.' . $kengenNo . '.3');
	$from = ($mails->currentPage() - 1) * $perPage;
@endphp

@section('css')
<style type="text/css"><!--
.naiyouPreviewArea {
	width: 15em;
	max-height: 150px;
	margin: 0;
	overflow-y: scroll;
	background: #D6D5D9;
	border: 1px solid #999;
}
.naiyouPreviewArea span {
	white-space: normal !important;
}
--></style>
@endsection

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$("{{ $activeAc }}").accordion('option', 'active', 0);
	window.scroll(0,0);
});

function kensuSet(parts){
	document.form_search.kensu.value = parts.value;
	document.form_search.submit();
}
</script>
@endsection

@section('content')
<form method="get" name="form_search" id="form_search" class="displayNone" action="{{ $routes['future'] }}">
<input type="hidden" name="kensu" id="kensu" value="{{ $perPage }}" />
<input type="hidden" name="page" id="page" value="{{ $mails->currentPage() }}" />
</form>

<div class="contents">
<p class="h2Title">■{{ $pageTitle }}</p>
@if ($mails->total() > 0)
<div id="form">
<p class="maxListLength">表示件数<select name="kensu_act" id="kensu_act" class="selectBox" onchange="kensuSet(this)">
@foreach ([10, 20, 30, 50, 100] as $n)
<option value="{{ $n }}" @selected($perPage === $n)>{{ $n }}件</option>
@endforeach
</select></p>
</div>
<p><span class="attention">{{ $from + 1 }}件～{{ min($from + $perPage, $mails->total()) }}件目/{{ $mails->total() }}件中</span></p>

<table class="tableBox2">
	<tr>
	<th>ID</th>
	<th>配信日時</th>
@if ($isMaster)
	<th>@if ($recurring)配信対象店舗 ／ @endif フッター表記店舗</th>
@endif
	<th>内容</th>
	<th>配信条件</th>
	<th>&nbsp;</th>
	</tr>
@foreach ($mails as $row => $mail)
@php $m = fn (string $c) => $mail->getAttribute($c); @endphp
	<tr @if ($row % 2 == 1) class="tableBg" @endif>
	<td>{{ $mail->cd }}</td>
	<td>{{ $m('配信日') }}
	{{ $m('配信時間') }}</td>
@if ($isMaster)
	<td>{{ $shopNames[$m('登録者')] ?? '' }}</td>
@endif
	<td>
	件名：<strong>{{ $m('タイトル') }}</strong><br />
	@if (in_array($mail->type_flg, ['deco', 'followdeco', 'autodeco', 'remdeco', 'posremdeco'], true))
	<div class="naiyouPreviewArea">{!! $m('pc内容') !!}</div>
	@else
	<textarea name="naiyou" id="naiyou" class="inputM" rows="3" readonly="readonly">{{ $m('pc内容') }}</textarea>
	@endif
	</td>
	<td>
	<textarea name="naiyou" id="naiyou" class="inputM" rows="3" readonly="readonly">{{ $m('search_display') }}</textarea>
	</td>
	<td align="center" style="width: 6em;">
	@if ($canEdit)
	<input type="button" class="btn btnStyleS" value="修正する" onclick="javascript:location.href='{{ $routes['edit'] }}?mailmagcd={{ $mail->cd }}';" />
	@endif
	</td>
  </tr>
@endforeach
</table>

<ul class="btnBox2">
@if ($mails->currentPage() > 1)
<li class="btn"><a href="{{ $routes['future'] }}?kensu={{ $perPage }}&page={{ $mails->currentPage() - 1 }}"><img src="{{ asset('legacy/system/img/btn_back_off.gif') }}" width="92" height="32" alt="戻る" /></a></li>
@endif
@if ($mails->lastPage() > 1)
@for ($l = max(1, $mails->currentPage() - 5); $l <= min($mails->lastPage(), $mails->currentPage() + 5); $l++)
@if ($l === $mails->currentPage())
<li class="now"><b>{{ $l }}</b></li>
@else
<li><a href="{{ $routes['future'] }}?kensu={{ $perPage }}&page={{ $l }}">{{ $l }}</a></li>
@endif
@endfor
@endif
@if ($mails->hasMorePages())
<li class="btn"><a href="{{ $routes['future'] }}?kensu={{ $perPage }}&page={{ $mails->currentPage() + 1 }}"><img src="{{ asset('legacy/system/img/btn_next_off.gif') }}" width="92" height="32" alt="次へ" /></a></li>
@endif
</ul>
@else
<p>{{ $pageTitle }}はありません</p>
@endif
<!-- / .contents --></div>
@endsection
