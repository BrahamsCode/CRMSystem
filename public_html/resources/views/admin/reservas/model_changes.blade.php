{{-- Legacy: system/category3/reservation/model_reservation.php (予約機種一覧: tb_change_model con socio, tienda y puntos).
     Variables:
       $rows      array filas de la página: customer_cd (login_id), customer_name, customer_tel, shop_name, touroku ('Y年m月d日'),
                  resevation_type, resevation ('Y年m月d日'), next_model, service_point (int)
       $total     filas en total
       $kensu, $page, $maxPage  paginación (category1/inc/page_control.php: 20 por defecto)
       $siteName  nombre del sitio
     El texto sin datos usa $tool_name['mail_tool_name'] (kihon.php → getMailmagazineName): お知らせ con la app activa, si no メール (PromocionMenu::tool de S3). --}}
@extends('layouts.legacy')

@section('menu', 'promocion')
@section('title', '予約機種一覧一覧 - ' . $siteName)

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac111").accordion({ collapsible: true , active: false});
	$(".ac111").accordion('option', 'active', 0);
	window.scroll(0,0);

	$("#checkAll").click(function(e) {
		var c = $(this).attr('checked');
		$(".delCheckbox").attr('checked', c);
	});
});

{{-- kensuSet no estaba definida en el legacy (el selector no hacía nada): se navega con ?kensu= --}}
function kensuSet(parts){
	location.href = "{{ route('admin.reservas.model-changes') }}?kensu=" + parts.value;
}
</script>
@endsection

@php
    $pageUrl = fn (int $p) => route('admin.reservas.model-changes', ['page' => $p, 'kensu' => $kensu]);
    $from = ($page - 1) * $kensu;
@endphp

@section('content')
<div class="contents">
<p class="h2Title">■予約機種一覧</p>

@if ($total == 0)

	<p>現在過去{{ \App\Services\Promotions\PromocionMenu::tool() }}配信はありません</p>

@else

	<form action="{{ route('admin.reservas.model-changes') }}" method="POST" style="float: right;">
		@csrf
		<input type="hidden" name="csv_print" value="1">
		<input type="submit" value="CSV出力">
	</form>

	<div id="form">
		<p class="maxListLength">表示件数<select name="kensu_act" id="kensu_act" class="selectBox" onchange="kensuSet(this)">
@for ($i = 1; $i <= 5; $i++)
<option value="{{ $i * 10 }}" @selected($kensu == $i * 10)>{{ $i * 10 }}</option>
@endfor
</select></p>
	</div>

	<p><span class="attention">{{ $from + 1 }}件～{{ min($from + $kensu, $total) }}件目/{{ $total }}件中</span></p>

	<table class="tableBox2">
		<colgroup>
			<col width="50">
			<col width="200">
			<col width="110">
			<col width="180">
			<col width="150">
			<col width="180">
			<col width="140">
			<col width="110">
			<col width="110">
		</colgroup>
		<tr>
			<th>ID</th>
			<th>名前</th>
			<th>電話番号</th>
			<th>登録番号</th>
			<th>予約申込日</th>
			<th>ご希望予約時期</th>
			<th>該当する予約時期</th>
			<th>希望機種</th>
			<th>保有ポイント数</th>
		</tr>
		@foreach ($rows as $row)
			<tr>
				<td>{{ $row['customer_cd'] }}</td>
				<td>{{ $row['customer_name'] }}</td>
				<td>{{ $row['customer_tel'] }}</td>
				<td>{{ $row['shop_name'] }}</td>
				<td>{{ $row['touroku'] }}</td>
				<td>{{ $row['resevation_type'] }}</td>
				<td>{{ $row['resevation'] }}</td>
				<td>{{ $row['next_model'] }}</td>
				<td>{{ number_format($row['service_point']) }} pt</td>
			</tr>
		@endforeach
	</table>

	<ul class="btnBox2">
@if ($page != 1 && $kensu < $total)
<li class=""><a href="{{ $pageUrl($page - 1) }}" class="btnStyleS" style="padding: 5px; border: 1px solid #527B86; background: transparent;">戻る</a></li>
@endif
@if ($maxPage > 1)
@for ($l = max(1, $page - 2); $l <= min($maxPage, $page + 2); $l++)
@if ($l == $page)
<li class="now"><b>{{ $l }}</b></li>
@else
<li><a href="{{ $pageUrl($l) }}">{{ $l }}</a></li>
@endif
@endfor
@endif
@if ($page < $maxPage)
<li class=""><a href="{{ $pageUrl($page + 1) }}" class="btnStyleS" style="padding: 5px; border: 1px solid #527B86; background: transparent;">次へ</a></li>
@endif
	</ul>

@endif

<!-- / .contents --></div>
@endsection
