{{-- クーポン選択 (inc/ajax_search_coupon.php). Variables: $coupons --}}
<table class="tableBox2" style="overflow:auto;table-layout:fixed;">
@if ($coupons->isNotEmpty())
<tr style="height:40px;">
<th width="10%">CD</th>
<th width="45%">タイトル</th>
<th width="45%">内容</th>
</tr>
@foreach ($coupons as $row => $coupon)
@php
	$title = mb_strwidth((string) $coupon->title) > 70 ? mb_strimwidth((string) $coupon->title, 0, 70, '...') : (string) $coupon->title;
	$comment = mb_strwidth((string) $coupon->comment) > 70 ? mb_strimwidth((string) $coupon->comment, 0, 70, '...') : (string) $coupon->comment;
	$label = '【' . $title . '   ／   ' . preg_replace("/\r|\n/", '', $comment) . '】(' . $coupon->cd . ')   ';
@endphp
<tr
	style="height:40px;cursor:pointer;background-color:{{ $row % 2 == 0 ? '#FFFFFF' : '#EEEEEE' }}"
	onclick="setCoupon({{ (int) $coupon->cd }}, {{ \Illuminate\Support\Js::from($label) }});">
<td>{{ $coupon->cd }}</td>
<td>{{ $title }}</td>
<td>{{ $comment }}</td>
</tr>
@endforeach
@else
<p>データが見つかりませんでした</p>
@endif
</table>
