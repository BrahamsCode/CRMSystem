{{--
	アンケート詳細 + 部品 / アンケート項目 一覧 — legacy: category3/anketo(2)/anketo_form_list.php (+ ../inc/graph_sql.php)
	Variables: $family, $cate, $editable (アンケート管理 sin 発行), $registerNew (お客様アンケート en alta), $sended, $mail,
	  $stats (ClickStats), $respondents, $coupon, $canIssue, $forms, $total, $subPage, $subKensu, $maxPage, $from,
	  $listQuery (page, kensu, which de la lista), $isMaster
	La プレビュー de cada ítem usa el parcial de S2 de campos personalizados (createform_request).
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@php
	$customer = $family->isCustomer();
	$pageTitle = '『' . $cate->name . '』 ' . ($customer ? 'アンケート項目 ' : '部品 ');
	$pcd = $cate->cd;
	$query = fn (array $q = []) => array_filter($q + $listQuery, fn ($v) => (string) $v !== '');
	$pageLink = fn (int $p) => $family->route('forms.index', $query(['pcd' => $pcd, 'sub_page' => $p, 'sub_kensu' => $subKensu, 'register_type' => $registerNew ? 'new' : '']));
	$jobs = $stats ? collect($stats['jobs'])->map(fn ($n, $job) => [$job, $n, \App\Services\Promotions\ClickStats::JOB_COLORS[$job] ?? null])->values()->all() : [];
	$m = fn (string $c) => $mail?->{$c} ?? '';
@endphp

@section('title', $pageTitle . '一覧 - ' . $siteName)

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$("{{ $family->ac() }}").accordion('option', 'active', 0);
	window.scroll(0,0);
});
</script>

@if ($stats)
<script type="text/javascript" src="/system/js/circle.js"></script>
<script type="text/javascript">
window.onload = function() {
  var params = {
    caption: true,
    legend: false
  };

  var cg1 = new html5jp.graph.circle("total");
  if( ! cg1 ) { return; }
  var total_items = [
    ["男性", {{ $stats['otoko_total'] }}],
    ["女性", {{ $stats['onna_total'] }}, "#D60000"],
    ["不明", {{ $stats['fumei_total'] }}, "#333333"]
  ];
  cg1.draw(total_items, params);

  var cg2 = new html5jp.graph.circle("nendai_betsu");
  if( ! cg2 ) { return; }
  var nendai_items = [
    ["19歳以下", {{ $stats['ages']['customer_0'] }}],
    ["20代", {{ $stats['ages']['customer_20'] }}],
    ["30代", {{ $stats['ages']['customer_30'] }}],
    ["40代", {{ $stats['ages']['customer_40'] }}],
    ["50代", {{ $stats['ages']['customer_50'] }}],
    ["60代", {{ $stats['ages']['customer_60'] }}],
    ["70歳以上", {{ $stats['ages']['customer_70'] }}],
    ["その他", {{ $stats['ages']['customer_undef'] }}, "#CCCCCC"]
  ];
  cg2.draw(nendai_items, params);

  var cg3 = new html5jp.graph.circle("shokugyo_betsu");
  if( ! cg3 ) { return; }
  var shokugyo_items = {!! json_encode($jobs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!};
  cg3.draw(shokugyo_items, params);

};
</script>
@endif

<script type="text/javascript">
//発行／複製フォームの確認
function submitChk(cd_no, act_type) {

	var flg;

	switch (act_type) {

		case '発行':
			flg = confirm ( "発行してもよろしいですか？\n発行したアンケートは修正できません。\n\n発行しない場合は[キャンセル]ボタンを押して下さい");
			break;

		case '登録':
			if (document.getElementById('update_row') != null) {
				flg = confirm ( "この内容で登録してもよろしいですか？\n登録したアンケートは修正できません。\n\n登録しない場合は[キャンセル]ボタンを押して下さい");
			} else {
				alert("アンケート項目を登録してください");
				flg = false;
			}
			break;

		case '削除':
			flg = confirm ( "削除してもよろしいですか？\n削除したアンケートは復元しません。\n\n削除しない場合は[キャンセル]ボタンを押して下さい");
			break;

		case 'キャンセル':
			flg = false;
			if (confirm ( "登録をキャンセルして前の画面に戻ります。\nキャンセルしてよろしいですか？\n\n登録を続ける場合は[キャンセル]ボタンを押して下さい")) {
				location.href = '{{ $family->route('create') }}';
			}
			break;

		default:
			flg = confirm ( "複製してもよろしいですか？\n\n複製しない場合は[キャンセル]ボタンを押して下さい");
			break;

	}

	if (flg) {
		document.form_action.anketo_cate_cd.value = cd_no;
		document.form_action.act.value = act_type;
		document.form_action.submit();
	}
}

// 項目の削除 (el legacy: enlace GET; aquí POST con el mismo aviso)
function submitChk2 (cd) {
	var flg = confirm ( "{{ $customer ? '項目を削除してもよろしいですか？' : '削除してもよろしいですか？' }}\n\n削除しない場合は[キャンセル]ボタンを押して下さい");
	if (flg) {
		document.form_delete.cd.value = cd;
		document.form_delete.submit();
	}
	return false;
}

// 並び ▲▼ (el legacy: GET thisCd/thisRow/kind; aquí POST)
function moveRow (cd, rank, kind) {
	document.form_move.thisCd.value = cd;
	document.form_move.thisRow.value = rank;
	document.form_move.kind.value = kind;
	document.form_move.submit();
	return false;
}
</script>
@endsection

@section('content')
<div class="contents form">
<p class="h2Title">■アンケート詳細</p>

<fieldset style="{{ $customer ? 'margin:20px; padding:15px; border:2px #CFDBE5 dotted;' : 'margin:20px;padding:15px;border:#CFDBE5 dotted;' }}">
<legend>詳細</legend>
<table class="tableBox" style="margin:auto;">
	<tr>
		<th><label for="name">アンケート名</th>
		<td>{{ $cate->name }}</td>
		@if ($editable)
		<td rowspan="4" align="center">
			<a href="{{ $family->route('edit', $query(['cd' => $pcd])) }}"><img src="/system/img/icon_syosai.gif" alt="修正" title="修正" /></a>
		</td>
		@endif
	</tr>

	<tr>
		<th>コメント</th>
		<td>{!! nl2br(e($cate->comment)) !!}</td>
	</tr>
@unless ($customer)
	<tr>
		<th>開始年月日</th>
		<td>{{ \App\Services\Promotions\AnketoSurveyService::date($cate->start_date) }}</td>
	</tr>
	<tr>
		<th>終了年月日</th>
		<td>{{ \App\Services\Promotions\AnketoSurveyService::date($cate->end_date) }}</td>
	</tr>

@if ($sended)
	<tr>
		<th>回答者数</th>
		<td>{{ $respondents }} 人</td>
	</tr>
	<tr>
		<th>総配信者数</th>
		<td>{{ (int) $m('配信総数') }} 人</td>
	</tr>
	<tr>
		<th>メールタイトル</th>
		<td>{{ $m('タイトル') }}</td>
	</tr>
	<tr>
		<th>メール内容</th>
		<td>
		<div class="sampleDecoView">
		{!! nl2br(e($m('pc内容'))) !!}
		</div>
		</td>
	</tr>
@if ((string) $cate->attached_cd !== '')
	<tr>
		<th>付属クーポン</th>
		<td><span style="font-weight:bold">{{ $coupon?->title }}</span><br/>{!! nl2br(e(mb_strwidth((string) $coupon?->comment) > 100 ? mb_strimwidth((string) $coupon?->comment, 0, 100, '...') : (string) $coupon?->comment)) !!}</td>
	</tr>
@endif
@endif
@endunless

</table>
</fieldset>


@if ($mail)

<!-- アクセス解析テーブル 開始 -->
<table class="tableBox" style="width:700px" cellpadding="4" cellspacing="1" >
	<tr><th colspan="3" style="text-align:center;">このアンケートメールのクリックカウンタ</th></tr>
	<tr height="280px">
	  <td style="vertical-align:top;width:33%">
			<div style="text-align:center">
			<p style="color:blue;font-size:16px;font-weight:bold;">性別</p>
			@if ($stats['count'] > 0)
			<canvas width="200" height="200" id="total"></canvas>
			@else
			<p style="margin-top:100px;color:red;font-size:16px;font-weight:bold;">データはありません</p>
			@endif
			</div>
	  </td>
	  <td style="vertical-align:top;width:33%">
			<div style="text-align:center">
			<p style="color:blue;font-size:16px;font-weight:bold;">年代別</p>
			@if ($stats['count'] > 0)
			<canvas width="200" height="200" id="nendai_betsu"></canvas>
			@else
			<p style="margin-top:100px;color:red;font-size:16px;font-weight:bold;">データはありません</p>
			@endif
			</div>
	  </td>
	  <td style="vertical-align:top;width:33%">
			<div style="text-align:center">
			<p style="color:blue;font-size:16px;font-weight:bold;">職業別</p>
			@if ($jobs !== [])
			<canvas width="200" height="200" id="shokugyo_betsu"></canvas>
			@else
			<p style="margin-top:100px;color:red;font-size:16px;font-weight:bold;">データはありません</p>
			@endif
			</div>
	  </td>
	</tr>
</table>

@if ($stats['count'] > 0)
<div style="test-align:center;width:700px;">
	<form method="post" action="{{ route('admin.promocion.mail.clicks-csv') }}">
		<input name="mailmagcd" type="hidden" value="{{ $mail->cd }}" />
		<input type="submit" name="enter" value="CSV出力する" />
	</form>
</div>
<br />
@endif

<!-- アクセス解析テーブル 終了 -->

@endif


@if ($customer)
@if ($registerNew)<a href="{{ $family->route('forms.create', ['pcd' => $pcd]) }}" class="btnStyleM" style="padding: 0.3em 1em; overflow: hidden; font-size: 19px;">アンケート項目追加</a>@endif

<p class="h2Title" style="margin-top: 5px;">■{{ $pageTitle }}</p>
@else
@if (! $sended)<a href="{{ $family->route('forms.create', ['pcd' => $pcd]) }}">{{ $pageTitle }}新規追加</a>@endif

<p class="h2Title">■{{ $pageTitle }}一覧</p>
@endif

@if ($total > 0)


<div id="form">
<form method="post" name="form_cnt" id="form_cnt" class="form" action="{{ $family->route('forms.index') }}">
<p><span class="attention">{{ $from + 1 }}件～{{ min($from + $subKensu, $total) }}件目/{{ $total }}件中</span></p>
</form>
</div>

<div class="form">

<form method="post" name="form1" id="update_row" class="form" action="{{ $family->route('forms.index') }}">

<table class="tableBox2">
	<tr>
		<th>&nbsp;</th>
		<th>項目名</th>
		<th>フォームタイプ</th>
		<th>プレビュー</th>
@if ($customer)
		<th style="width:8em;">必須</th>
		<th style="width:8em;">回答一覧に表示</th>
		@if ($registerNew)
		<th style="width:8em;">情報修正・削除</th>
		<th>並び</th>
		@endif
@elseif ($editable)
		<th width="100px">必須</th>
		<th width="100px">情報修正・削除</th>
		<th>並び</th>
@endif
	</tr>

@foreach ($forms as $row => $form)
	<tr @if ($row % 2 != 0) class="tableBg" @endif>

		<td>{{ $from + 1 + $row }}<input type="hidden" name="cd{{ $row }}" value="{{ $form->cd }}"></td>
		<td>{{ $form->name }}</td>
		<td>{{ $form->form_type }}</td>
		<td>@include('admin.clientes.partials.custom_field', ['field' => $form->getAttributes(), 'values' => [], 'mode' => 'input'])
@if ((string) $form->unit !== '')
@if ($form->form_type === '起算日')<br />※検索は年数の範囲を指定します。（{{ $form->unit }}）<br />@else{{ $form->unit }}<br />@endif
@endif
{!! nl2br(e($form->comment)) !!}</td>

@if ($customer || $editable)
		<td align="center" style="background-color:#FF9F9F">
		<input type="checkbox" name="hissu{{ $row }}" value="1"@if ((string) $form->hissu !== '') checked @endif />
		</td>
@endif
@if ($customer)
		<td align="center" style="background-color:#C2E4EF">
		<input type="checkbox" name="hyouji_flg{{ $row }}" value="-1"@if ((string) $form->hyouji_flg === '-1') checked @endif />
		</td>
@endif

@if ($editable || $registerNew)
		<td align="center">
		<ul>
		<li><a href="{{ $family->route('forms.edit', array_filter(['register_type' => $registerNew ? 'new' : '', 'cd' => $form->cd, 'pcd' => $pcd] + $listQuery, fn ($v) => (string) $v !== '')) }}" class="btnStyle">修正</a></li>
		<li><a href="#" onclick="return submitChk2({{ $form->cd }});" class="btnStyle">削除</a></li>
		</ul>
		</td>

		<td>
		<ul style="paddng:0px;margin:auto;text-align:center;line-height:13px;font-size:12px;width:1em">
		@if ($row > 0 || $subPage != 1)
		<li><a href="#" onclick="return moveRow({{ $form->cd }}, {{ (int) $form->rank }}, 'up');">▲</a></li>
		@else
		&nbsp;
		@endif

		@if ($subPage != $maxPage || $row < $forms->count() - 1)
		<li><a href="#" onclick="return moveRow({{ $form->cd }}, {{ (int) $form->rank }}, 'down');">▼</a></li>
		@endif
		</ul>
		</td>
@endif

	</tr>
@endforeach

@if ($customer)
	<tr>
		<td colspan="4">&nbsp;</td>
		<td colspan="2" align="center">
		<input type="hidden" name="register_type" value="{{ $registerNew ? 'new' : '' }}" />
		<input type="hidden" name="act" value="update" />
		<input type="hidden" name="update_max" value="{{ $forms->count() }}" />
		<input type="hidden" name="pcd" value="{{ $pcd }}" />
		<input type="hidden" name="page" value="{{ $listQuery['page'] ?? '' }}" />
		<input type="hidden" name="kensu" value="{{ $listQuery['kensu'] ?? '' }}" />
		<input type="submit" class="btnStyleS" value="必須設定・回答一覧表示設定を更新" style="cursor:pointer;" />
		</td>
		@if ($registerNew)
		<td colspan="2">&nbsp;</td>
		@endif
	</tr>
@elseif ($editable)
<input type="hidden" name="act" value="update">
<input type="hidden" name="update_max" value="{{ $forms->count() }}">
<input type="hidden" name="pcd" value="{{ $pcd }}">
<input type="hidden" name="page" value="{{ $listQuery['page'] ?? '' }}">
<input type="hidden" name="kensu" value="{{ $listQuery['kensu'] ?? '' }}">
  <tr>
	<td colspan="4">&nbsp;</td>
	<td align="center"><input type="submit" class="btnStyleS" value="必須情報を更新" style="cursor:pointer;"></td>
	<td colspan="2">&nbsp;</td>
  </tr>
@endif
</table>
</form><!--update_row-->

@unless ($customer)
<div class="btnBox">
	<ul>
@if ($editable)

	@if ($canIssue)
<form method="post" name="form2" id="form2" class="form" action="{{ $family->route('mail.select') }}">
<input type="hidden" name="cd" id="cd" value="{{ $pcd }}" />
<input type="hidden" name="hakkou_cd" id="hakkou_cd" value="{{ $pcd }}" />
		<li><input type="submit" name="enter" id="enter" class="btn" value="発行条件を選択する" /></li>
</form>
	@else
<span class="attention">※終了年月日は過去の日付で発行できません<br />日付を修正してください。</span>
	@endif

		<p><input type="button" value="このアンケートを削除する" onclick="submitChk({{ $pcd }}, '削除'); return false;" class="btnStyleM"></p>

@else
		<li><input type="button" value="このアンケートを複製する" onclick="submitChk({{ $pcd }}, '複製'); return false;" class="btnStyleM"></li>
@endif
	</ul>
</div>
@endunless

</div>

@else
@if ($customer)
<br /><br />アンケート項目を追加してください。<br /><br />
@else
該当する情報がみつかりません。<br /><br />

@if (! $sended)<a href="{{ $family->route('forms.create', ['pcd' => $pcd]) }}" class="btnStyleM">新規追加する</a>@endif<br />
@endif
@endif

<form method="post" name="form_action" action="{{ $family->route('action') }}">
<input type="hidden" name="act" value="" />
<input type="hidden" name="anketo_cate_cd" value="" />
<input type="hidden" name="page" value="{{ $listQuery['page'] ?? '' }}" />
<input type="hidden" name="kensu" value="{{ $listQuery['kensu'] ?? '' }}" />
</form>

<form method="post" name="form_delete" action="{{ $family->route('forms.destroy') }}">
<input type="hidden" name="cd" value="" />
<input type="hidden" name="pcd" value="{{ $pcd }}" />
@foreach ($listQuery as $name => $value)
<input type="hidden" name="{{ $name }}" value="{{ $value }}" />
@endforeach
</form>

<form method="post" name="form_move" action="{{ $family->route('forms.move') }}">
<input type="hidden" name="thisCd" value="" />
<input type="hidden" name="thisRow" value="" />
<input type="hidden" name="kind" value="" />
<input type="hidden" name="pcd" value="{{ $pcd }}" />
<input type="hidden" name="sub_kensu" value="{{ $subKensu }}" />
<input type="hidden" name="sub_page" value="{{ $subPage }}" />
<input type="hidden" name="register_type" value="{{ $registerNew ? 'new' : '' }}" />
@foreach ($listQuery as $name => $value)
<input type="hidden" name="{{ $name }}" value="{{ $value }}" />
@endforeach
</form>

@if ($customer)
@if ($registerNew)
<p>
<input type="button" value="登録をキャンセルする" onclick="submitChk({{ $pcd }}, 'キャンセル'); return false;" class="btnStyleM" />
<input type="button" value="アンケートを登録する" onclick="submitChk({{ $pcd }}, '登録'); return false;" class="btnStyleM" /></p>
@else
<p><input type="button" value="アンケートを削除する" onclick="submitChk({{ $pcd }}, '削除'); return false;" class="btnStyleM" /></p>
@endif
@endif


<ul class="btnBox2">
@if ($forms->count() > 0)
@if ($subPage != 1 && $subKensu < $total)
<li class="btn"><a href="{{ $pageLink($subPage - 1) }}"><img src="/system/img/btn_back_off.gif" width="92" height="32" alt="戻る" /></a></li>
@endif
@if ($maxPage > 1)
@for ($l = max(1, $subPage - 5); $l <= min($maxPage, $subPage + 5); $l++)
@if ($l == $subPage)
<li class="now"><b>{{ $l }}</b></li>
@else
<li><a href="{{ $pageLink($l) }}">{{ $l }}</a></li>
@endif
@endfor
@endif
@if ($subPage < $maxPage)
<li class="btn"><a href="{{ $pageLink($subPage + 1) }}"><img src="/system/img/btn_next_off.gif" width="92" height="32" alt="次へ" /></a></li>
@endif
@endif
</ul>


@if (! $registerNew)<a href="{{ $family->route('index', $customer ? array_filter(['page' => $listQuery['page'] ?? '', 'kensu' => $listQuery['kensu'] ?? '']) : $listQuery) }}" class="backBtnStyle">一覧に戻る</a>@endif

<!-- / .contents --></div>
@endsection
