{{--
	過去配信内容一覧 — legacy: system/category3/push/push_past.php → category3/inc/push_list.php
	Variables: $filters, $sort, $rows (push_master), $counts [cd => {total, nobe, opened}], $kensu, $page, $maxrows,
	  $minYear, $maxYear, $activeAc
--}}
@php
	$pageTitle = '過去配信内容一覧';
	$from = ($page - 1) * $kensu;
	$pastUrl = route('admin.promocion.push.past');
	[$fy, $fm, $fd] = $filters['fromParts'];
	[$ty, $tm, $td] = $filters['toParts'];
	// $search_str del legacy (+ ポイント付与 / その他の配信, que el legacy perdía al paginar)
	$search = array_filter([
		'from' => $fy !== '' ? "$fy/$fm/$fd" : '',
		'to' => $ty !== '' ? "$ty/$tm/$td" : '',
		'texto' => $filters['texto'],
		'point_add_flg' => $filters['point_add_flg'] ? '1' : '',
		'etc_flg' => $filters['etc_flg'] ? '1' : '',
	], fn ($v) => $v !== '') + ['sort_type' => 'DESC', 'sort_value' => $sort];
	$options = function (int $min, int $max, string $selected) {
		$html = '';
		for ($i = $min; $i <= $max; $i++) {
			$html .= '<option value="' . $i . '"' . ($selected !== '' && (int) $selected === $i ? ' selected="selected"' : '') . '>' . $i . '</option>';
		}
		return $html;
	};
@endphp
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', $pageTitle . '一覧 - ' . $siteName)

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$("{{ $activeAc }}").accordion('option', 'active', 0);
	window.scroll(0,0);
});

//表示件数の制御
function kensuSet(parts){
	document.last_search.sort_type.value = $("#sort_type").val();
	document.last_search.sort_value.value = $("#sort_value").val();
	document.last_search.kensu.value = $("#kensu_act").val();
	document.last_search.submit();
}
</script>
@endsection

@section('content')
<form method="post" name="last_search" id="last_search" class="displayNone" action="{{ $pastUrl }}">
	<input type="hidden" name="from_year" value="{{ $fy }}" />
	<input type="hidden" name="from_month" value="{{ $fm }}" />
	<input type="hidden" name="from_day" value="{{ $fd }}" />
	<input type="hidden" name="to_year" value="{{ $ty }}" />
	<input type="hidden" name="to_month" value="{{ $tm }}" />
	<input type="hidden" name="to_day" value="{{ $td }}" />
	<input type="hidden" name="texto" value="{{ $filters['texto'] }}" />
	@if ($filters['point_add_flg'])<input type="hidden" name="point_add_flg" value="1" />@endif
	@if ($filters['etc_flg'])<input type="hidden" name="etc_flg" value="1" />@endif
	<input type="hidden" name="kensu" value="{{ $kensu }}" />
	<input type="hidden" name="sort_type" value="DESC" />
	<input type="hidden" name="sort_value" value="{{ $sort }}" />
	<input type="hidden" name="page" value="{{ $page }}" />
</form>

<div class="contents">

	<p class="h2Title">■過去配信検索</p>

	<form method="post" name="form_search" id="form_search" class="form" action="{{ $pastUrl }}">
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
				<td>
					<input type="text" name="texto" id="texto" class="writeArea" value="{{ $filters['texto'] }}" />
					<label for="point_add_flg">
						<input id="point_add_flg" type="checkbox" name="point_add_flg" value="1"@if ($filters['point_add_flg']) checked="checked"@endif> ポイント付与
					</label>
					<label for="etc_flg">
						<input id="etc_flg" type="checkbox" name="etc_flg" value="1"@if ($filters['etc_flg']) checked="checked"@endif> その他の配信
					</label>
				</td>
			</tr>
		</table>

		<div class="btnBox">
			<input type="submit" name="act" class="btn btnStyleM" value="検索する">
		</div>
		<input type="hidden" name="kensu" value="{{ $kensu }}" />
	</form>
<!-- / .contents --></div>


<div class="contents">
	<p class="h2Title">■{{ $pageTitle }}</p>

	@if ($rows->isNotEmpty())

	<div id="form">
		<table style="width: 100%;">
			<tr>
				<td>
					<p class="maxListLength">表示件数<select name="kensu_act" id="kensu_act" class="selectBox" onchange="kensuSet(this)">@include('admin.promocion.push._kensu')</select></p>
				</td>
				<td style="text-align:right">
					<input type="hidden" name="sort_type" id="sort_type" value="DESC">
					<select name="sort_value" id="sort_value" onchange="kensuSet(this)">
						@foreach (\App\Services\Promotions\PushReport::SORTS as $key => $label)
							<option value="{{ $key }}" @selected($key === $sort)>{{ $label }}</option>
						@endforeach
					</select>
				</td>
			</tr>
		</table>
	</div>

	<p><span class="attention">{{ $from + 1 }}件～{{ min($from + $kensu, $maxrows) }}件目/{{ $maxrows }}件中</span></p>

	<table class="tableBox2 TLF">
		<tr>
		<th style="width: 100px;">ID</th>
		<th>配信日時</th>
		<th>タイトル</th>
		<th>抽出条件</th>
		<th>クリックカウント率<br><small style="font-size: 80%;">(ｸﾘｯｸｶｳﾝﾄ/総配信数)</small></th>
		<th style="width: 50px;"></th>
		</tr>
	@foreach ($rows as $row)
		@php
			$display = (string) $row->search_display;
			if (mb_strwidth($display) > 50) $display = mb_strimwidth($display, 0, 50) . '...';
			$c = $counts[$row->cd] ?? null;
			$rate = $c && $c->opened && $c->total ? (int) ceil($c->opened / $c->total * 100) : 0;
		@endphp
		<tr>
		<td>{{ $row->cd }}@if ((int) $row->post_type === 2) <img src="/system/img/l_e_point_40.png" style="width: 17px;border-radius: 8px;">@endif</td>
		<td>{{ \Illuminate\Support\Carbon::createFromTimestamp((int) $row->touroku_date, \App\Services\Promotions\LegacyTime::ZONE)->format('Y年m月d日 H:i:s') }}@if ((string) $row->img_flg === '1') <img src="/system/img/clip.gif" alt="画像" />@endif</td>
		<td>{{ $row->title }}</td>
		<td>{{ $display }}</td>
		<td>{{ $rate }}%<br><div style="font-size: 80%;">総配信数:{{ $c->total ?? '' }}<br>閲覧のべ人数:{{ $c->nobe ?? '' }}<br>閲覧実人数:{{ $c->opened ?? '' }}<br></div></td>
		<td align="center"><a href="{{ route('admin.promocion.push.show') . '?' . http_build_query(['pushcd' => $row->cd, 'page' => $page] + $search + ['kensu' => $kensu]) }}" class="btnStyle">詳細</a><br /></td>
		</tr>
	@endforeach
	</table>
	<ul class="btnBox2">
	@include('admin.promocion.push._pager', ['url' => $pastUrl, 'params' => $search])
	</ul>

	@else
	<p>現在過去配信はありません</p>
	@endif

<!-- / .contents --></div>
@endsection
