{{--
	クーポン一覧 — legacy: system/category3/mycoupon/mycoupon_list.php
	Variables: $coupons (paginador), $shops, $shopCd, $kubun, $kubunOptions, $kubunLabels, $perPage, $isMaster, $staffShopCd,
	  $canEditAny (kengen 1009/3), $shopNames
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', 'クーポン 一覧')

@php
	$query = ['shop_cd' => $shopCd, 'coupon_kubun' => $kubun];
	$pageLink = fn (int $p) => route('admin.promocion.coupons.index', array_merge($query, ['kensu' => $perPage, 'page' => $p]));
	$limitLabel = function ($c) {
		$limit = (string) $c->limit_flg === '5' && is_numeric($c->time_limit) ? date('Y/m/d', (int) $c->time_limit) : $c->time_limit;
		return ['', '発行から1週間', '発行から1ヶ月', '発行から3ヶ月', '発行から' . $limit . '日間', '指定日付：' . $limit . 'まで'][(int) $c->limit_flg] ?? '';
	};
@endphp

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac7").accordion('option', 'active', 0);
	window.scroll(0,0);
});

function kensuSet(parts){
	document.form_search.kensu.value = parts.value;
	document.form_search.submit();
}
</script>
@endsection

@section('content')
<form method="post" name="form_search" id="form_search" class="displayNone" action="{{ route('admin.promocion.coupons.index') }}">
@csrf
<input type="hidden" name="kensu" id="kensu" value="{{ $perPage }}" />
<input type="hidden" name="page" id="page" value="{{ $coupons->currentPage() }}" />
<input type="hidden" name="shop_cd" id="shop_cd" value="{{ $shopCd }}" />
<input type="hidden" name="coupon_kubun" value="{{ $kubun }}" />
</form>

<div class="contents">
<p class="h2Title">■クーポン 抽出</p>
<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.promocion.coupons.index') }}">
@csrf
<input type="hidden" name="kensu" value="{{ $perPage }}" />
@if ($isMaster)
<select name="shop_cd" @if ($shops->count() < 2) disabled="disabled" @endif>
<option value="-1">全店舗</option>
@foreach ($shops as $shop)
<option value="{{ $shop->cd }}" @selected((string) $shopCd === (string) $shop->cd)>{{ $shop->kaishamei }}</option>
@endforeach
</select>
@else
{{ $shops->first()?->kaishamei }}&nbsp;
<input type="hidden" name="shop_cd" value="{{ $shops->first()?->cd }}">
@endif
	<select name="coupon_kubun" id="coupon_kubun">
	@foreach ($kubunOptions as $value => $label)
	<option value="{{ $value }}" @selected((string) $kubun === (string) $value)>{{ $label }}</option>
	@endforeach
	</select>
<input type="submit" name="submit" class="btnStyle" value="表示" />
</form>
<br/>

<p class="h2Title">■クーポン 一覧</p>
@if ($coupons->total() > 0)
<div id="form">
<p class="maxListLength">表示件数<select name="kensu_act" id="kensu_act" class="selectBox" onchange="kensuSet(this)">
@foreach ([10, 20, 30, 50, 100] as $n)
<option value="{{ $n }}" @selected($perPage === $n)>{{ $n }}件</option>
@endforeach
</select></p>
</div>
<p><span class="attention">{{ $coupons->total() }}件</span>の該当結果がありました。</p>

<form name="update_hyouji_flg" method="post" action="{{ route('admin.promocion.coupons.index') }}">
@csrf
<table class="tableBox2">
	<tr>
		<th>ID</th>
		<th>有効期限</th>
@if ($isMaster)
		<th>店舗</th>
@endif
		<th>使用区分</th>
		<th>タイトル</th>
		<th>内容</th>
		<th>表示</th>
		<th style="width: 40px;">&nbsp;</th>
	</tr>
@foreach ($coupons as $row => $coupon)
<tr class="{{ $row % 2 != 0 ? 'tableBg' : '' }}">
<td>{{ $coupon->cd }}</td>
<td>{{ $limitLabel($coupon) }}</td>
@if ($isMaster)
<td>{{ (string) $coupon->tb_shop_cd === '-1' ? '全店舗' : ($shopNames[$coupon->tb_shop_cd] ?? '') }}</td>
@endif
		<td>{{ $kubunLabels[(int) ($coupon->coupon_kubun ?: 0)] ?? '' }}</td>
		<td>{{ $coupon->title }}@if ((string) $coupon->hyouji_flg === '1')<span class="attention mL5">(発行中)</span>@endif</td>
		<td><textarea name="naiyou" class="inputM" rows="3" readonly="readonly" style="font-size:12px;width:15em!important;">{{ $coupon->comment }}</textarea></td>
		<td align="center">
@if (($canEditAny && (string) $coupon->tb_shop_cd === (string) $staffShopCd) || $isMaster)
		<input type="checkbox" name="hyouji_flg_array[{{ $coupon->cd }}]" @checked((string) $coupon->hyouji_flg === '1') />
		<input type="hidden" name="update_cd[]" value="{{ $coupon->cd }}" />
@endif
		</td>
		<td align="center">
			<ul>
			<li><a href="{{ route('admin.promocion.coupons.show', array_merge($query, ['cd' => $coupon->cd, 'page' => $coupons->currentPage()])) }}" class="btnStyle">詳細</a></li>
			</ul>
		</td>
	</tr>
@endforeach
</table>
<div style="text-align:right;">
<input type="submit" class="btnStyleS" value="表示情報を更新"/>
</div>
<input type="hidden" name="kensu" value="{{ $perPage }}" />
<input type="hidden" name="page" value="{{ $coupons->currentPage() }}" />
<input type="hidden" name="shop_cd" value="{{ $shopCd }}" />
<input type="hidden" name="coupon_kubun" value="{{ $kubun }}" />
<input type="hidden" name="update_hyouji_flg" id="update_hyouji_flg" value="update" />
</form>
<ul class="btnBox2">
@if ($coupons->currentPage() > 1)
<li class="btn"><a href="{{ $pageLink($coupons->currentPage() - 1) }}"><img src="{{ asset('legacy/system/img/btn_back_off.gif') }}" width="92" height="32" alt="戻る" /></a></li>
@endif
@if ($coupons->lastPage() > 1)
@for ($l = max(1, $coupons->currentPage() - 5); $l <= min($coupons->lastPage(), $coupons->currentPage() + 5); $l++)
@if ($l === $coupons->currentPage())
<li class="now"><b>{{ $l }}</b></li>
@else
<li><a href="{{ $pageLink($l) }}">{{ $l }}</a></li>
@endif
@endfor
@endif
@if ($coupons->hasMorePages())
<li class="btn"><a href="{{ $pageLink($coupons->currentPage() + 1) }}"><img src="{{ asset('legacy/system/img/btn_next_off.gif') }}" width="92" height="32" alt="次へ" /></a></li>
@endif
</ul>
@else
クーポンデータはありません。
@endif
</div>
@endsection
