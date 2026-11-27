{{--
	お客様アンケート回答検索 / 回答一覧 — legacy: category3/anketo2/anketo_answer_list.php (+ inc/page.php)
	Variables: $family, $pcd, $in (parámetros, con el periodo completado), $items (ítems con «回答一覧に表示»), $answers
	  (página actual: cd, tb_customer_cd, category_inner_group, touroku_date, data[form_cd]), $customers (cd => id, name, email1,
	  kojin_sex, disp_flg, age, raiten_cnt, answer_cnt), $total, $page, $kensu, $maxPage, $from, $carry ($add_getstr / $add_poststr),
	  $checked (cd de sub marcados para 返信), $minYear, $thisYear
	Datos del cliente y respuestas: texto plano, se imprimen escapados.
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', 'お客様アンケート回答一覧 - ' . $siteName)

@php
	$v = fn (string $k) => (string) ($in[$k] ?? '');
	$listUrl = fn (array $q) => $family->route('answers', $q + ['pcd' => $pcd] + $carry);
	$options = function (int $min, int $max, string $selected) {
		$html = '';
		for ($i = $min; $i <= $max; $i++) {
			$num = $i < 10 ? '0' . $i : (string) $i;
			$html .= '<option value="' . $num . '"' . ($selected !== '' && $num == $selected ? ' selected' : '') . '>' . $num . "</option>\n";
		}
		return new \Illuminate\Support\HtmlString($html);
	};
	$popup = fn (string $url) => "javascript:window.open('" . $url . "', 'mywindow1', 'width=970, height=550, menubar=no, toolbar=no, scrollbars=yes'); $('#yoyakuCustomerPopCloseBtn').click();";
@endphp

@section('js')
<script type="text/javascript">
$(document).ready(function() {

	$(".ac109").accordion('option', 'active', 0);
	window.scroll(0,0);


	// 全チェック =============================================================================

	$("#check_all").click(function(e) {

		var check = $(this).attr('checked');

		$(".reply_mail_check").attr('checked', check);

	});


	// 返信メール作成ボタン ===================================================================

	$('#enter').bind('click', function(e){

		if ($('.reply_mail_check:checked').length > 0) {
			$('#mail_send_form').submit();
		} else {
			jAlert("メールを返信したいお客様の左側にあるチェックボックスにチェックを入れてください。","確認");
		}

	});


	// ========================================================================================


});

// 表示件数
function kensuSet(parts){

	location.href = parts.options[parts.selectedIndex].value;

}
</script>
@endsection

@section('content')
<div class="contents">

<p class="h2Title">■お客様アンケート回答検索</p>

<form method="post" name="form_search" id="form_search" class="form" action="{{ $family->route('answers') }}">

<table class="tableBox">
  <tr>
    <th><label for="send_date">アンケート登録日</label></th>
    <td>

      <select name="from_year" id="from_year">
      <option value="">&nbsp;</option>
      {{ $options($minYear, $thisYear, $v('from_year')) }}
      </select>年
      <select name="from_month" id="from_month">
      <option value="">&nbsp;</option>
      {{ $options(1, 12, $v('from_month')) }}
      </select>月
      <select name="from_day" id="from_day">
      <option value="">&nbsp;</option>
      {{ $options(1, 31, $v('from_day')) }}
      </select>日&nbsp;

      <select name="from_h" id="from_h">
      <option value="">&nbsp;</option>
      {{ $options(0, 23, $v('from_h')) }}
      </select>時
      <select name="from_i" id="from_i">
      <option value="">&nbsp;</option>
      {{ $options(0, 59, $v('from_i')) }}
      </select>分

      &emsp;～&emsp;

      <select name="to_year" id="to_year">
      <option value="">&nbsp;</option>
      {{ $options($minYear, $thisYear, $v('to_year')) }}
      </select>年
      <select name="to_month" id="to_month">
      <option value="">&nbsp;</option>
      {{ $options(1, 12, $v('to_month')) }}
      </select>月
      <select name="to_day" id="to_day">
      <option value="">&nbsp;</option>
      {{ $options(1, 31, $v('to_day')) }}
      </select>日&nbsp;

      <select name="to_h" id="to_h">
      <option value="">&nbsp;</option>
      {{ $options(0, 23, $v('to_h')) }}
      </select>時
      <select name="to_i" id="to_i">
      <option value="">&nbsp;</option>
      {{ $options(0, 59, $v('to_i')) }}
      </select>分

    </td>
  </tr>
</table>

<div class="btnBox">
<input type="submit" name="act" class="btn btnStyleM" value="検索する" />
<input type="hidden" name="pcd" value="{{ $pcd }}" />
<input type="hidden" name="which" value="{{ $v('which') }}" />
<input type="hidden" name="page" value="{{ $v('page') }}" />
<input type="hidden" name="kensu" value="{{ $v('kensu') }}" />
<input type="hidden" name="page2" value="{{ $v('page2') }}" />
<input type="hidden" name="kensu2" value="{{ $v('kensu2') }}" />
<input type="hidden" name="fcd" value="{{ $v('fcd') }}" />
<input type="hidden" name="answer" value="{{ $v('answer') }}" />
</div>

</form>

<!-- / .contents --></div>





<div class="contents">
<p class="h2Title" style="margin-top: 5px;">■お客様アンケート回答一覧</p>

<p style="border: 1px dotted #CCCCCC; padding: 10px;">個別返信メールは同一文章で複数のお客様に返信をすることができます。<br />
メールを返信したいお客様の左側にあるチェックボックスにチェックを入れてから「返信メール作成」ボタンを押してください。文章を作成する画面に移ります。<br />
※チェックを入れることの出来ないお客様はメールアドレスの無いお客様です。<br />
<span class="attention">チェックをしたお客様に対してメールを送る場合は次のページに行く前にメールを送信してください。ページを切り替えるとチェックが消えてしまいます。</span></p>

@if ($total > 0)
<div id="form">
<form method="post" name="form_cnt" id="form_cnt" class="form" action="{{ $family->route('answers') }}">
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
<form method="post" name="mail_send_form" id="mail_send_form" class="form" action="{{ $family->route('reply.create') }}">


<table class="tableBox2">

	<tr>
		<th style="width: 3em;">返信<br />
		<div class="textCenter"><input type="checkbox" id="check_all" name="check_all" value="1" /></div></th>
		<th style="width: 5em;">ID</th>
		<th style="width:auto;">お名前</th>
		<th style="width: 3em;">性別</th>
		<th style="width: 3em;">年齢</th>
		<th style="width: 5em;">来店回数</th>
		<th style="width: 5em;">アンケート<br />登録日</th>
		<th style="width: 5em;">アンケート<br />回数</th>
		@foreach ($items as $item)
		<th>{{ $item->name }}</th>
		@endforeach
		<th style="width: 5.5em;">&nbsp;</th>
	</tr>

@foreach ($answers as $row => $answer)
@php
	$c = $customers[$answer['tb_customer_cd']];
	$date = is_numeric($answer['touroku_date']) ? \Illuminate\Support\Carbon::createFromTimestamp((int) $answer['touroku_date'], \App\Services\Promotions\LegacyTime::ZONE)->format('Y/m/d H:i') : '';
@endphp
	<tr @if ($row % 2 == 0) class="tableBg" @endif>
		<td class="textCenter">
		@if ($c['disp_flg'] !== '1')
		退会<br />済み
		@else
		<input type="checkbox" class="reply_mail_check" name="reply_mail[{{ $answer['cd'] }}]" value="{{ $c['email1'] }}"@if (in_array((string) $answer['cd'], $checked, true)) checked="checked"@endif @if ($c['email1'] === '') disabled="disabled"@endif />
		@endif
		</td>
		<td>{{ $c['id'] }}</td>
		<td>{{ $c['name'] }}</td>
		<td>{{ $c['kojin_sex'] }}</td>
		<td>{{ $c['age'] }}</td>
		<td>{{ $c['raiten_cnt'] }}</td>
		<td>{{ $date }}</td>
		<td>{{ $c['answer_cnt'] }}</td>
		@foreach ($items as $item)
		<td>{{ str_replace('<>', ',', $answer['data'][$item->cd] ?? '') }}</td>
		@endforeach
		<td class="center">
		<input type="button" class="btnStyle" value="顧客情報" onclick="{{ $popup('/system/category1/inc/customer_detail.php?kaiin_cd=' . $answer['tb_customer_cd'] . '&wintype=no_headers') }}" style="margin-bottom: 1px; padding: 2px; font-size: 100%;" />
		<input type="button" class="btnStyle" value="回答詳細" onclick="{{ $popup($family->route('answers.show', ['pcd' => $pcd, 'ccd' => $answer['tb_customer_cd'], 'group' => $answer['category_inner_group']])) }}" style="padding: 2px; font-size: 100%;" />
		</td>
	</tr>
@endforeach

</table>

<div class="pT10">
<input type="hidden" name="pcd" value="{{ $pcd }}" />
<input type="hidden" name="page" value="{{ $v('page') }}" />
<input type="hidden" name="kensu" value="{{ $v('kensu') }}" />
@foreach ($carry as $name => $value)
<input type="hidden" name="{{ $name }}" value="{{ $value }}" />
@endforeach
<input type="button" name="enter" id="enter" class="btn btnStyleM" value="返信メール作成" />
</div>

</form>
</div>


@else
該当する情報がみつかりません。<br />
@endif


<ul class="btnBox2">
@if (count($answers) > 0)
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


@if ($v('fcd') !== '')
<a href="{{ $family->route('analyze', ['pcd' => $pcd, 'kensu' => $v('kensu2'), 'page' => $v('page2'), 'which' => 'sended']) }}" class="backBtnStyle">解析へ戻る</a>
@else
<a href="{{ $family->route('index', array_filter(['page' => $v('page2'), 'kensu' => $v('kensu2')])) }}" class="backBtnStyle">一覧へ戻る</a>
@endif


<!-- / .contents --></div>
@endsection
