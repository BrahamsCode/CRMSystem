{{--
	ポイント発行ルール一覧 — legacy: system/category3/point/point_rules_list.php
	Variables: $rules, $total, $page, $kensu, $from, $shopNames
	El borrado del legacy era un enlace GET; aquí el mismo enlace envía un formulario POST (CSRF).
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', 'ポイント発行ルール一覧' . ' - ' . $siteName)

@php
	use App\Http\Controllers\Admin\Promotions\PointRuleController as Rules;

	$hour = fn ($v) => (string) $v === '0' ? '0:00' : substr((string) $v, 0, -2) . ':00';
	$ruleText = function ($r) use ($hour) {
		return match ((string) $r->type_flg) {
			'1' => '毎日&nbsp;' . e($hour($r->start_time)) . '時 から ' . e($hour($r->end_time)) . '時 まで',
			'2' => '毎週' . e(Rules::DOW[$r->day_of_week] ?? '') . '曜日&nbsp;' . e($hour($r->start_time)) . '時 から ' . e($hour($r->end_time)) . '時 まで',
			'3' => date('Y年m月d日 H:i時', (int) $r->start_time) . ' から ' . date('Y年m月d日 H:i時', (int) $r->end_time) . ' まで',
			'4' => '毎月&nbsp;' . ((int) $r->start_time === 32 ? '末' : e($r->start_time)) . '日',
			'5' => date('Y年m月d日', (int) $r->start_time) . ' 終日',
			default => '',
		};
	};
	$shopName = fn ($cd) => (int) $cd === -1 ? '全店舗' : ($shopNames[$cd] ?? '');
	$listUrl = route('admin.promocion.points.rules.index');
@endphp

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac13").accordion('option', 'active', 0);
	window.scroll(0,0);
});

// 表示件数
function kensuSet(parts){
	location=parts.options[parts.selectedIndex].value;
}

function submitChk2 () {
	return confirm ( "削除してもよろしいですか？\n\n削除しない場合は[キャンセル]ボタンを押して下さい");
}

function deleteRule(cd) {
	if (!submitChk2()) return false;
	var f = document.getElementById('delete_form');
	f.cd.value = cd;
	f.submit();
	return false;
}
</script>
@endsection

@section('content')
<a href="{{ route('admin.promocion.points.rules.create') }}">ポイント発行ルール新規追加</a>

<div class="contents">
<p class="h2Title">■ポイント発行ルール一覧</p>

@if ($total > 0)
<div id="form">
<form method="post" name="form_cnt" id="form_cnt" class="form" action="{{ $listUrl }}">
@csrf
<p class="maxListLength">表示件数
<select name="kensu_act" id="kensu_act" class="selectBox" onchange="kensuSet(this)">
@for ($srow = 10; $srow < 60; $srow += 10)
<option value="?kensu={{ $srow }}&page={{ $page }}"@if ($srow === $kensu) selected @endif>{{ $srow }}</option>
@endfor
</select>
</form>
</p>
</div>

<p><span class="attention">{{ $from + 1 }}件～{{ min($from + $kensu, $total) }}件目/{{ $total }}件中</span></p>

<div class="form">

<form name="update_hyouji_flg" method="post" action="{{ $listUrl }}">
@csrf

<p><input type="submit" class="btnStyleS" value="表示情報を更新"/></p>

<p>※ポイント発行ルールは<span style="font-weight: bold; color: #113366;">リストの上位にあるものほど優先</span>されます。<br />
※具体的な内訳は下部を参照してください。</p>

@foreach ($rules as $i => $rule)
	@if ($i === 0 || (string) $rules[$i - 1]->tb_shop_cd !== (string) $rule->tb_shop_cd)
		@if ($i > 0)
</table>
		@endif
<p class="h3Title">{{ $shopName($rule->tb_shop_cd) }}のポイントルール</p>
<table class="tableBox2" style="margin-bottom: 10px;">
  <tr>
    <th style="width: 5%;">&nbsp;</th>
    <th style="width: 15%;">店舗</th>
    <th style="width: 10%;">区分</th>
    <th style="width: 40%;">ルール</th>
    <th style="width: 10%;">発行ポイント</th>
    <th style="width: 5%;">適用</th>
    <th style="width: 15%;">修正・削除</th>
  </tr>
	@endif
  <tr @if ($i % 2 !== 0) class="tableBg" @endif>
    <td>{{ $from + 1 + $i }}</td>
    <td>{{ $shopName($rule->tb_shop_cd) }}</td>
    <td>{{ Rules::TYPES[(int) $rule->type_flg] ?? '' }}</td>
    <td><span style="font-weight: bold;">{!! $ruleText($rule) !!}</span></td>
    <td>{{ $rule->point_count }}倍</td>
    <td align="center">
	<input type="checkbox" name="hyouji_flg_array[{{ $rule->cd }}]" @if ((string) $rule->hyouji_flg !== '9') checked @endif />
	<input type="hidden" name="update_cd[]" value="{{ $rule->cd }}" />
    </td>
    <td align="center">
	<a href="{{ route('admin.promocion.points.rules.edit', ['cd' => $rule->cd, 'kensu' => $kensu, 'page' => $page]) }}" class="btnStyle">修正</a>
	<a href="#" onclick="return deleteRule({{ $rule->cd }});" class="btnStyle">削除</a>
	</td>
  </tr>
	@if ($loop->last)
</table>
	@endif
@endforeach

<p class="right"><input type="submit" class="btnStyleS" value="表示情報を更新"/></p>

<input type="hidden" name="kensu" id="kensu" value="{{ $kensu }}" />
<input type="hidden" name="page" id="page" value="{{ $page }}" />
<input type="hidden" name="update_hyouji_flg" id="update_hyouji_flg" value="update" />
</form>

<form method="post" id="delete_form" action="{{ route('admin.promocion.points.rules.destroy') }}" style="display:none;">
@csrf
<input type="hidden" name="cd" value="" />
<input type="hidden" name="kensu" value="{{ $kensu }}" />
<input type="hidden" name="page" value="{{ $page }}" />
</form>

</div>

@include('admin.promocion.point.inc.point_rules_help')

@else
該当する情報がみつかりません。<br /><br />

<a href="{{ route('admin.promocion.points.rules.create') }}">⇒新規追加する</a><br />
@endif

<ul class="btnBox2">
{{ \App\Support\LegacyHtml::pager($listUrl . '?', $page, $kensu, $total, '') }}
</ul>

<!-- / .contents --></div>
@endsection
