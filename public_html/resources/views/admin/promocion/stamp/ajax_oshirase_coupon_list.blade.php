{{--
	ajax_oshirase_coupon.php sin stamp_count: avisos configurados de la tienda. Variables: $rows, $coupons (cd => título)
	El legacy imprimía naiyou y títulos sin escapar; aquí se escapan (nl2br sobre el texto escapado).
--}}
<table class="tableBox8" style="margin-bottom: 5px;">
	<tr>
	<th style="width: 75px;">スタンプ数</th>
	<th style="width: 16em;">メール内容</th>
	<th>紹介クーポン</th>
	<th style="width: 50px;"></th>
	</tr>
@foreach ($rows as $row)
	<tr>
	<td><a href="javascript:selectStampCount({{ (int) $row->stamp_count }});">{{ $row->stamp_count }}</a></td>
	<td><div class="sampleDecoView">{!! nl2br(e($row->naiyou)) !!}</div></td>
	<td style="vertical-align:top;">
	@foreach (explode(',', (string) $row->tb_mycoupon_master_cd) as $couponCd)
	【ID：{{ $couponCd }}】&nbsp;{{ $coupons[$couponCd] ?? '' }}<br/>
	@endforeach
	</td>
	<td>
	@if ((string) $row->hyouji_flg === '1')
	<span style="color:#0000FF;font-weight:bold;">適用中</span>
	@else
	<span style="color:#FF0000;font-weight:bold;">非適用</span>
	@endif
	</td>
	</tr>
@endforeach
</table>
