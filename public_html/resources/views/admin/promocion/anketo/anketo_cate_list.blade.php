{{--
	アンケート一覧 / 過去アンケート配信一覧 / お客様アンケート一覧 — legacy: category3/anketo(2)/anketo_cate_list.php (+ inc/page.php)
	Variables: $family (AnketoFamily), $pageTitle, $sended, $which, $cates, $extra (cd => respondents, total, display),
	  $total, $page, $kensu, $maxPage, $from, $activeCd (お客様アンケート «選択»)
	name/comment se guardan con get_form (texto plano): se imprimen escapados.
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', $pageTitle . '一覧 - ' . $siteName)

@php
	$listUrl = fn (array $q) => $family->route('index', array_filter($q + ['which' => $which], fn ($v) => (string) $v !== ''));
@endphp

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$("{{ $family->ac() }}").accordion('option', 'active', 0);
	window.scroll(0,0);
@if ($family->isCustomer())

	// アンケート選択 =========================================================================

	$('input.setAnketo').bind('click',function(){

		var thisElm = $(this)
		  , thisVal = thisElm.val();

		$('input.setAnketo').attr('checked',false);

		$.ajax({

			type: "POST",
			url: "/system/category3/anketo2/ajax_set_anketo.php",
			data: "anketo_cd="+thisVal,

			success: function(data){
				if (data == 'success') thisElm.attr('checked',true);
			},
			complete: function(){}

		});

	});
@endif
});

// 表示件数
function kensuSet(parts){
	location=parts.options[parts.selectedIndex].value;
}
</script>
@endsection

@section('content')
@if ($family->isCustomer())
<div class="contents">
<p class="h2Title" style="margin-top: 5px;">■{{ $pageTitle }}一覧</p>

<p style="border: 1px dotted #CCCCCC; padding: 10px;">一覧で「選択」にチェックをいれたアンケートは、「<span class="attention">{{ $toolNames['magazine'] }}管理</span>」「<span class="attention">デコメール管理</span>」「<span class="attention">フォロー{{ $toolNames['mail'] }}管理</span>」「<span class="attention">自動{{ $toolNames['mail'] }}管理</span>」の各<span style="font-weight: bold; color: #113366;">新規{{ $toolNames['mail'] }}作成画面</span>にて<span class="attention">「お客様アンケートのURL」が挿入出来る</span>ようになります。本文に貼り付けてご利用ください。</p>
@else
@if (! $sended)
<a href="{{ $family->route('create') }}">{{ $pageTitle }}新規追加</a>
@else
<br />
@endif

<div class="contents">
<p class="h2Title">■{{ $pageTitle }}一覧</p>
@endif

@if ($total > 0)
<div id="form">
<form method="post" name="form_cnt" id="form_cnt" class="form" action="{{ $family->route('index') }}">
<p class="maxListLength">表示件数
<select name="kensu_act" id="kensu_act" class="selectBox" onchange="kensuSet(this)">
@for ($srow = 10; $srow < 60; $srow += 10)
<option value="{{ $listUrl(['kensu' => $srow, 'page' => $page]) }}"@if ($srow == $kensu) selected @endif>{{ $srow }}</option>
@endfor
</select>
</form>
</p>
</div>

<p><span class="attention">{{ $from + 1 }}件～{{ min($from + $kensu, $total) }}件目/{{ $total }}件中</span></p>

<div class="form">

<table class="tableBox2">
  <tr>
    <th>ID</th>
    <th>アンケート名</th>
    <th>コメント</th>
@if ($sended)
    <th>振分配信</th>
    <th style="width:40px;">回答数<br />配信数</th>
@endif
@if ($family->isCustomer())
    <th style="width: 40px;">選択</th>
    <th style="width: 40px;">詳細</th>
    <th style="width: 40px;">解析</th>
    <th style="width: 40px;">回答</th>
@else
    <th style="width: 40px;">詳細</th>
@if ($sended)<th style="width: 40px;">回答解析</th>@endif
@endif
 </tr>
@foreach ($cates as $row => $cate)
@php
	$detailUrl = $family->route('forms.index', array_filter(['pcd' => $cate->cd, 'kensu' => $kensu, 'page' => $page, 'which' => $which], fn ($v) => (string) $v !== ''));
	$analyzeUrl = $family->route('analyze', array_filter(['pcd' => $cate->cd, 'kensu' => $kensu, 'page' => $page, 'which' => $which], fn ($v) => (string) $v !== ''));
@endphp
  <tr @if ($row % 2 != 0) class="tableBg" @endif>

    <td>{{ $cate->cd }}</td>

    <td>{{ $cate->name }}</td>

    <td>{!! nl2br(e($cate->comment)) !!}</td>

@if ($sended)
    <td>{{ $extra[$cate->cd]['display'] }}</td>
    <td>{{ $extra[$cate->cd]['respondents'] }} / {{ $extra[$cate->cd]['total'] }} <small>(人)</small></td>
@endif

@if ($family->isCustomer())
    <td align="center"><input type="checkbox" name="set_anketo" value="{{ $cate->cd }}" id="setAnketo_{{ $cate->cd }}" class="setAnketo"@if ($activeCd !== null && (int) $activeCd === (int) $cate->cd) checked="checked"@endif /></td>
    <td align="center"><a href="{{ $detailUrl }}" class="btnStyle">詳細</a></td>
    <td align="center"><a href="{{ $analyzeUrl }}" class="btnStyle">解析</a></td>
    <td align="center"><a href="{{ $family->route('answers', ['pcd' => $cate->cd, 'kensu2' => $kensu, 'page2' => $page, 'which' => 'sended']) }}" class="btnStyle">回答</a></td>
@else
    <td align="center">
	<ul>
	<li><a href="{{ $detailUrl }}" class="btnStyle">詳細</a>
	</li>
	</ul>
	</td>

@if ((string) $cate->hyouji_flg === '1')<td><a href="{{ $analyzeUrl }}" class="btnStyle">解析</a></td>@endif
@endif
  </tr>
@endforeach
</table>

</div>

@else
@if ($family->isCustomer())
該当する情報がみつかりません。<br />
@else
該当する情報がみつかりません。<br /><br />

<a href="{{ $family->route('create') }}" class="btnStyleM">新規追加する</a><br />
@endif
@endif

<ul class="btnBox2">
@if ($cates->count() > 0)
@if ($page != 1 && $kensu < $total)
<li class="btn"><a href="{{ $listUrl(['page' => $page - 1, 'kensu' => $kensu]) }}"><img src="/system/img/btn_back_off.gif" width="92" height="32" alt="戻る" /></a></li>
@endif
@if ($maxPage > 1)
@for ($l = max(1, $page - 5); $l <= min($maxPage, $page + 5); $l++)
@if ($l == $page)
<li class="now"><b>{{ $l }}</b></li>
@else
<li><a href="{{ $listUrl(['page' => $l, 'kensu' => $kensu]) }}">{{ $l }}</a></li>
@endif
@endfor
@endif
@if ($page < $maxPage)
<li class="btn"><a href="{{ $listUrl(['page' => $page + 1, 'kensu' => $kensu]) }}"><img src="/system/img/btn_next_off.gif" width="92" height="32" alt="次へ" /></a></li>
@endif
@endif
</ul>


<!-- / .contents --></div>
@endsection
