{{--
	過去配信一覧 — legacy: system/category3/inc/mailmag_list.php (mail_mag/mail_past.php, decomail/mail_past.php)
	Variables: $mailType, $toolName, $pageTitle, $mails (paginador), $perPage, $filters (from, to, texto), $minYear, $maxYear,
	  $isMaster, $canDelete, $shopNames, $clicks [cd => n], $appEnabled, $pushStats [cd => {nobe, real, total}], $routes
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', $pageTitle . '一覧')

@php
	[$fy, $fm, $fd] = array_pad(explode('/', (string) $filters['from']), 3, '');
	[$ty, $tm, $td] = array_pad(explode('/', (string) $filters['to']), 3, '');
	$from = ($mails->currentPage() - 1) * $perPage;
	$query = array_filter(['from' => $filters['from'], 'to' => $filters['to'], 'texto' => $filters['texto']], fn ($v) => (string) $v !== '');
	$options = function (int $min, int $max, string $selected) {
		$html = '';
		for ($i = $min; $i <= $max; $i++) {
			$num = sprintf('%02d', $i);
			$html .= '<option value="' . $num . '"' . ($selected !== '' && (int) $selected === $i ? ' selected="selected"' : '') . '>' . $num . '</option>' . "\n";
		}
		return $html;
	};
	$pageLink = fn (int $p) => $routes['past'] . '?' . http_build_query(array_merge($query, ['kensu' => $perPage, 'page' => $p]));
@endphp

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$("{{ \App\Http\Controllers\Admin\Promotions\MailController::activeAc($mailType) }}").accordion('option', 'active', 0);
	window.scroll(0,0);

	$("#checkAll").click(function(e) {
		var c = $(this).prop('checked');
		$(".delCheckbox").prop('checked', c);
	});
});

function kensuSet(parts){
	document.last_search.kensu.value = parts.value;
	document.last_search.submit();
}

function deleteChk(val) {
	var flg = confirm ( "選択した{{ $toolName }}を削除してもよろしいですか？\n\n削除しない場合は['キャンセル']ボタンを押して下さい");
	if (flg) {
		$.ajax({
			type: "POST",
			url: "{{ $routes['delete'] }}",
			data: "dataCd="+val,
			success: function(msg){
				document.last_search.submit();
			}
		});
	} else {
		return false;
	}
}

function deleteMulti() {
	var temp = '';
	$(".delCheckbox").each(function(index, value){
		if ($(this).prop('checked')) {
			temp += (temp == '') ? $(this).val() : ',' + $(this).val();
		}
	});
	if (temp == '') {
		alert("削除する{{ $toolName }}をチェックして下さい。");
	} else {
		deleteChk(temp);
	}
}
</script>
@endsection

@section('content')
<form method="get" name="last_search" id="last_search" class="displayNone" action="{{ $routes['past'] }}">
<input type="hidden" name="from_year" value="{{ $fy }}" />
<input type="hidden" name="from_month" value="{{ $fm }}" />
<input type="hidden" name="from_day" value="{{ $fd }}" />
<input type="hidden" name="to_year" value="{{ $ty }}" />
<input type="hidden" name="to_month" value="{{ $tm }}" />
<input type="hidden" name="to_day" value="{{ $td }}" />
<input type="hidden" name="texto" value="{{ $filters['texto'] }}" />
<input type="hidden" name="kensu" value="{{ $perPage }}" />
<input type="hidden" name="page" value="{{ $mails->currentPage() }}" />
</form>

<div class="contents">
<p class="h2Title">■{{ $toolName }}配信検索</p>
<form method="get" name="form_search" id="form_search" class="form" action="{{ $routes['past'] }}">
<table class="tableBox">
  <tr>
    <th><label for="send_date">配信日時</label></th>
    <td>
      <select name="from_year" id="from_year"><option value="">&nbsp;</option>{!! $options($minYear, $maxYear, $fy) !!}</select>年
      <select name="from_month" id="from_month"><option value="">&nbsp;</option>{!! $options(1, 12, $fm) !!}</select>月
      <select name="from_day" id="from_day"><option value="">&nbsp;</option>{!! $options(1, 31, $fd) !!}</select>日
      　～
      <select name="to_year" id="to_year"><option value="">&nbsp;</option>{!! $options($minYear, $maxYear, $ty) !!}</select>年
      <select name="to_month" id="to_month"><option value="">&nbsp;</option>{!! $options(1, 12, $tm) !!}</select>月
      <select name="to_day" id="to_day"><option value="">&nbsp;</option>{!! $options(1, 31, $td) !!}</select>日
    </td>
  </tr>
  <tr>
    <th><label for="texto">キーワード</label></th>
    <td><input type="text" name="texto" id="texto" class="writeArea" value="{{ $filters['texto'] }}" /></td>
  </tr>
</table>
<div class="btnBox">
<input type="submit" name="act" class="btn btnStyleM" value="検索する">
</div>
<input type="hidden" name="kensu" value="{{ $perPage }}" />
</form>
<!-- / .contents --></div>

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
	@if ($canDelete)
	<th style="width:25px;"><input type="checkbox" id="checkAll" value="1" /></th>
	@endif
	<th style="width:50px;">ID</th>
	<th style="width:90px;">配信日時</th>
@if ($isMaster)
	<th style="width:120px;">フッター表記店舗</th>
@endif
	<th>タイトル</th>
	<th>抽出条件</th>
@if ($mailType === 'mycoupon')
	<th>ｸｰﾎﾟﾝ</th>
@endif
	<th style="min-width: 100px;">ﾒﾙﾏｶﾞｸﾘｯｸｶｳﾝﾄ率<br /><small style="font-weight: normal">(ﾒﾙﾏｶﾞｸﾘｯｸｶｳﾝﾄ/総配信数)</small></th>
	@if ($appEnabled)
		<th style="min-width: 100px;">ﾌﾟｯｼｭｸﾘｯｸｶｳﾝﾄ率<br /><small style="font-weight: normal">(ﾌﾟｯｼｭｸﾘｯｸｶｳﾝﾄ/総配信数)</small></th>
	@endif
	<th style="width: 100px;">&nbsp;</th>
  </tr>
@foreach ($mails as $row => $mail)
@php
	$m = fn (string $c) => $mail->getAttribute($c);
	$display = (string) $m('search_display');
	if (mb_strwidth($display) > 50) $display = mb_strimwidth($display, 0, 50) . '...';
	$clickCount = (int) ($clicks[$mail->cd] ?? 0);
	$total = (int) $m('配信総数');
	$link = array_merge($query, ['mailmagcd' => $mail->cd, 'page' => $mails->currentPage()]);
	$push = $pushStats[$mail->cd] ?? null;
	$pushReal = $push && $push->real && $push->total ? (int) ceil($push->real / $push->total * 100) : 0;
@endphp
  <tr @if ($row % 2 == 1) class="tableBg" @endif>
	@if ($canDelete)
	<td><input type="checkbox" class="delCheckbox" value="{{ $mail->cd }}" /></td>
	@endif
	<td>{{ $mail->cd }}</td>
	<td>{{ $m('配信日') }}<br />
      {{ $m('配信時間') }}@if ((string) $m('画像有無') === '1') <img src="{{ asset('legacy/system/img/clip.gif') }}" alt="画像" />@endif</td>
@if ($isMaster)
	<td>{{ $shopNames[$m('登録者')] ?? '' }}</td>
@endif
	<td>{{ $m('タイトル') }}</td>
	<td>
	{{ $display }}
    </td>
@if ($mailType === 'mycoupon')
    <td>{{ $mail->type_data_cd ? \Illuminate\Support\Facades\DB::table('tb_mycoupon_master')->where('cd', $mail->type_data_cd)->value('title') : '' }}</td>
@endif
    <td align="right" style="vertical-align:top;">
		<a class="btnStyle" href="{{ $routes['recipients'] . '?' . http_build_query($link) }}" style="line-height: 1.2em;">クリック詳細</a>
		<br />
		@if ($total !== 0)
		{{ \App\Services\Promotions\ClickStats::rate($clickCount, $total) }} <br /><span style="font-size:80%;">({{ $clickCount }}件/{{ $total }}件)</span>
		@endif
    </td>
    @if ($appEnabled)
    <td align="right" style="vertical-align:top;">
		<div style="font-size: 80%;text-align:right">
		<span style="font-size: 140%">{{ $pushReal }}%</span><br>
		総配信数:{{ $push->total ?? 0 }}<br>
		閲覧のべ人数:{{ $push->nobe ?? 0 }}<br>
		閲覧実人数:{{ $push->real ?? 0 }}<br>
		</div>
    </td>
    @endif
	<td align="center">
	<a href="{{ $routes['show'] . '?' . http_build_query($link) }}" class="btnStyle">詳細</a>
	@if ($canDelete)
	<a href="javascript:void(0)" onclick="deleteChk({{ $mail->cd }}); return false;" class="btnStyle">削除</a>
	@endif
    </td>
  </tr>
@endforeach
</table>
@if ($canDelete)
<input type="button" name="delMultiBtn" onclick="deleteMulti();" class="btnStyleS" value="チェックした{{ $toolName }}を削除する" />
@endif
<ul class="btnBox2">
@if ($mails->currentPage() > 1)
<li class="btn"><a href="{{ $pageLink($mails->currentPage() - 1) }}"><img src="{{ asset('legacy/system/img/btn_back_off.gif') }}" width="92" height="32" alt="戻る" /></a></li>
@endif
@if ($mails->lastPage() > 1)
@for ($l = max(1, $mails->currentPage() - 5); $l <= min($mails->lastPage(), $mails->currentPage() + 5); $l++)
@if ($l === $mails->currentPage())
<li class="now"><b>{{ $l }}</b></li>
@else
<li><a href="{{ $pageLink($l) }}">{{ $l }}</a></li>
@endif
@endfor
@endif
@if ($mails->hasMorePages())
<li class="btn"><a href="{{ $pageLink($mails->currentPage() + 1) }}"><img src="{{ asset('legacy/system/img/btn_next_off.gif') }}" width="92" height="32" alt="次へ" /></a></li>
@endif
</ul>
@else
<p>現在過去{{ $toolName }}配信はありません</p>
@endif
<!-- / .contents --></div>
@endsection
