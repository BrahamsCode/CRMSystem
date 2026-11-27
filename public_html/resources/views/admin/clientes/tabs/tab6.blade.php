{{-- Legacy: system/category1/inc/detail_tab6.php (pestaña マイクーポン).
     Variables:
       $customer     modelo tb_customer
       $couponUsed   bool: request('used') == 'true' → lista de usados (tb_mycoupon_used); si no, vigentes (tb_mycoupon)
       $couponList   LengthAwarePaginator (kensu fijo 10) de tb_mycoupon[_used] JOIN tb_mycoupon_master:
                     cd, tb_shop_cd, shop_name ('全店舗' si tb_shop_cd = -1; si no tb_shop.kaishamei), title, comment,
                     time_limit (epoch, vigentes) / used_date (epoch, usados)
       $isMaster     bool: $_SESSION['rank'] == 'master'
     El legacy borraba la マイクーポン desde el propio template (GET ?coupon_cd=). Aquí deleteMyCoupon() envía un POST
     con @csrf a admin.clientes.coupons.destroy, que vuelve a esta pestaña. --}}
@php
    $baseParams = ['tab' => request('tab'), 'kaiin_cd' => $customer->cd];
    if ($couponUsed) {
        // 表示用
        $listTitle = '使用済';
        $storeColumnTitle = '使用店舗';
        $limitColumnTitle = '使用日時';
        // アンカー用
        $anchorLabel = '未使用';
        $anchorParams = $baseParams;
        $pageParams = $baseParams + ['used' => request('used')];
    } else {
        $listTitle = '未使用';
        $storeColumnTitle = '発行店舗';
        $limitColumnTitle = '有効期限';
        $anchorLabel = '使用済';
        $anchorParams = ['used' => 'true'] + $baseParams;
        $pageParams = $baseParams;
    }
    $epochDisp = fn ($v, $format) => is_numeric($v) && $v > 0
        ? \Illuminate\Support\Carbon::createFromTimestamp($v, 'Asia/Tokyo')->format($format)
        : '';
@endphp
<!--マイクーポンtable start-->
<div class="tabBody">

<script type="text/javascript">

// 2012.12.10【大野】マイクーポン削除機能を実装
function deleteMyCoupon( cd ) {

	if (cd == '') return false;

	jConfirm('このクーポンをマイクーポンから削除します。\n削除してよろしいですか？','削除確認',function(ret){

		if (ret) {
			document.delete_mycoupon_form.coupon_cd.value = cd;
			document.delete_mycoupon_form.submit();
		}

	});

}

</script>

<form method="post" class="displayNone" name="delete_mycoupon_form" id="delete_mycoupon_form" action="{{ route('admin.clientes.coupons.destroy', $customer->cd) }}">
	@csrf
	<input type="hidden" name="tab" value="{{ request('tab') }}" />
	<input type="hidden" name="kaiin_cd" value="{{ $customer->cd }}" />
	<input type="hidden" name="coupon_cd" value="" />
</form>

	<p style="text-align:right"><a href="{{ route('admin.clientes.show', array_merge([$customer->cd], $anchorParams)) }}">{{ $anchorLabel }}クーポン</a></p>
@if ($couponList->total() > 0)
	<p class="h2Title">■{{ $listTitle }}クーポン一覧</p>

	<div class="form">
		<table class="tableBox2">
			<tr>
			@if ($isMaster ?? false)
				<th>{{ $storeColumnTitle }}</th>
			@endif
				<th>{{ $limitColumnTitle }}</th>
				<th>タイトル</th>
				<th>内容</th>
				@if (! $couponUsed)
				<th>&nbsp;</th>
				@endif
			</tr>
	@foreach ($couponList as $mycoupon)

			<tr @if ($loop->even) class="tableBg"@endif>
			@if ($isMaster ?? false)
				<td>{{ $mycoupon->shop_name }}</td>
			@endif
				<td>{{ $couponUsed ? $epochDisp($mycoupon->used_date, 'Y/m/d H:i') : $epochDisp($mycoupon->time_limit, 'Y/m/d') }}</td>
				<td>{{ $mycoupon->title }}</td>
				<td>{{ $mycoupon->comment }}</td>
				@if (! $couponUsed)
				<td><input type="button" onclick="deleteMyCoupon('{{ $mycoupon->cd }}')" value="削除" class="btn btnStyleS"></td>
				@endif
			</tr>

	@endforeach
		</table>
	</div> <!--form-->

	<ul class="btnBox2">
	@include('admin.clientes.partials.page-control', ['paginator' => $couponList, 'params' => $pageParams])
	</ul>
@else
	<p style="text-align:center;font-size: 120%;color:#FF0000;font-weight:bold;"><br/><br/>現在{{ $listTitle }}クーポンがありません。<br/><br/></p>
@endif

</div>
<!--マイクーポンtable end-->
