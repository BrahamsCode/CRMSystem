{{--
	マイクーポン 使用数ランキング — legacy: system/category3/mycoupon/mycoupon_ranking.php
	Variables: $rows (top 20), $mailCounts [cd => envíos], $shops, $shopNames, $shop, $kubun, $kubunOptions
	El legacy fallaba (pg_query(['connect'])) al mostrar un cupón de メルマガ配信用; aquí se muestra el conteo.
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', 'マイクーポン 使用数ランキング')

@section('css')
<style type="text/css"><!--
.rank1 {
	display: block;
	width: 4em;
	border: 1px solid #996600;
	text-align: center;
	font-weight: bold;
	color: #996600;
	font-size: 140%;
}
.rank2 {
	display: block;
	width: 4em;
	border: 1px solid #555555;
	text-align: center;
	font-weight: bold;
	color: #555555;
	font-size: 125%;
}
.rank3 {
	display: block;
	width: 4em;
	border: 1px solid #773300;
	text-align: center;
	font-weight: bold;
	color: #773300;
	font-size: 110%;
}
.rankOther {
	display: block;
	width: 4em;
	border: 1px solid #CCCCCC;
	text-align: center;
}
--></style>
@endsection

@section('js')
<script type="text/javascript">
function changeShopSelect(val){
	$("#shopSelectForm").submit();
}
$(document).ready(function() {
	$(".ac7").accordion('option', 'active', 0);
});
</script>
@endsection

@section('content')
<div class="contents">
<form action="{{ route('admin.promocion.coupons.ranking') }}" method="get" name="shopSelectForm" id="shopSelectForm">
<select name="shop" onchange="changeShopSelect(this.value)">
<option value="">全店舗</option>
@foreach ($shops as $s)
<option value="{{ $s->cd }}" @selected($shop === (string) $s->cd)>{{ $s->kaishamei }}</option>
@endforeach
</select>
<select name="coupon_kubun" id="coupon_kubun" onchange="changeShopSelect(this.value)">
<option value="">全て</option>
@foreach ($kubunOptions as $value => $label)
<option value="{{ $value }}" @selected($kubun === (string) $value)>{{ $label }}</option>
@endforeach
</select>
</form>

@if ($rows->isNotEmpty())
<p class="h2Title">■マイクーポン 使用数ランキング</p>
<table class="tableBox">
	<tr>
	<th style="width: auto;">順位</th>
	<th style="width: auto;">使用回数</th>
	<th style="width: auto;">発行店舗</th>
	<th style="width: auto;">使用区分</th>
	<th style="width: 15em;">タイトル / 内容</th>
	</tr>
@foreach ($rows as $i => $row)
	<tr class="{{ $i % 2 != 0 ? 'tableBg' : '' }}">
	<td align="center"><span class="{{ ['rank1', 'rank2', 'rank3'][$i] ?? 'rankOther' }}">{{ $i + 1 }}位</span></td>
	<td>{{ $row->cnt }}回</td>
	<td>{{ (string) $row->tb_shop_cd === '-1' ? '全店舗' : ($shopNames[$row->tb_shop_cd] ?? '') }}</td>
	<td>{{ \App\Http\Controllers\Admin\Promotions\CouponStatsController::RANKING_KUBUN[(string) $row->coupon_kubun] ?? '' }}@if ((string) $row->coupon_kubun === '')<br /><small>(クーポンメルマガ配信回数：{{ (int) ($mailCounts[$row->cd] ?? 0) }}回)</small>@endif</td>
	<td>
	<strong><a href="{{ route('admin.promocion.coupons.show', ['func' => 'ranking', 'cd' => $row->cd, 'shop' => $shop, 'coupon_kubun' => $kubun]) }}">{{ $row->title }}</a></strong><br />
	{{ $row->comment }}
	</td>
	</tr>
@endforeach
</table>
@else
マイクーポンデータはありません。
@endif
</div>
@endsection
