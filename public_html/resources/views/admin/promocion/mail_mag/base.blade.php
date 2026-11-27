{{--
	メールマガジン基本情報 / QRコード情報 — legacy: system/category3/mail_mag/base.php, base_mailmaga.php, base_mycolle.php, base_point.php
	Variables (QrBaseInfo::forType): $type, $counts (null = sin メール会員登録状況), $hasShops, $appTitle,
	$appQrs (null = sin bloque de la app) y $mobileQrs (null = sin 携帯WEBマイページ), listas de ['name', 'query'];
	$info (QrBaseInfo) para la URL absoluta de data-qr_code.
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', 'メールマガジン基本情報 - ' . $siteName)

@section('css')
<style>
.right_button_area {
    float: right;
    margin-top: -39px;
}
.right_button_area #print_button {
    width: 110px;
}
input[name^=qr_print_flg] {
    margin-top: -7px;
    font-size: 28px;
    height: 28px;
    width: 28px;
    vertical-align: text-top;
    z-index: 4;
    position: relative;
    top: 3px;
}
</style>
@endsection

@section('js')
<script type="text/javascript"><!--

$(document).ready(function(){
	$(".ac1").accordion('option', 'active', 0);
});

--></script>
<script>
$(function(){

	$("#print_button").click(function(){

		$("#qr_print_pdf *").remove();

		$("input[name^=qr_print_flg\\[\\]]").each(function(){
			var in_html = "";
			if ( $(this).attr('checked') ) {
				in_html += "<input type='hidden' name='print_name[]' value='" + $(this).attr("data-name") + "'>";
				in_html += "<input type='hidden' name='qr_code[]' value='" + $(this).attr("data-qr_code") + "'>";
			}
			$("#qr_print_pdf").append( in_html );
		});

		$("#qr_print_pdf").submit();

	});

});
</script>
@endsection

@section('content')
@php
	$qrPath = \App\Services\Promotions\QrBaseInfo::QR_PATH;
	$downloadUrl = fn (array $qr) => route('admin.promocion.base.download', ['qr' => $qr['query'], 'name' => $qr['name']]);
@endphp
<div class="contents">
<p class="h2Title">■QRコード情報</p>

@if ($counts !== null)
<p class="h3Title">メール会員登録状況</p>

<table class="tableBox4">
  <tr>
    <th>パソコン</th>
    <td>{{ $counts['pc'] }}</td>
    <th>DoCoMo</th>
    <td>{{ $counts['docomo'] }}</td>
  </tr>
  <tr>
    <th>Au</th>
    <td>{{ $counts['au'] }}</td>
    <th>Softbank</th>
    <td>{{ $counts['softbank'] }}</td>
  </tr>
  <tr>
    <th>その他</th>
    <td>{{ $counts['other'] }}</td>
    <th>総数</th>
    <td>{{ $counts['total'] }}</td>
  </tr>
</table>

@endif

@if ($hasShops)

<br>

<p class="h3Title">モバイルQRコード</p>

<div class="right_button_area">
	<button id="print_button">印刷</button>
	<form action="{{ route('admin.promocion.base.qr-print') }}" method="post" id="qr_print_pdf" target="_blank"></form>
</div>

<table class="tableBox4">

	@if ($appQrs !== null)
		<tr>
		<th>{{ $appTitle }}</th>
		</tr>
		<tr>
		<td>
		@foreach ($appQrs as $qr)
			@if ($loop->last)
		<div style="display: inline-block; width: 150px; margin-bottom: 5px; border: 1px solid #999999; text-align: center; padding: 10px 0;">
			<span style="display: inline-block; margin-bottom: 0px; z-index: 21; position: relative; font-size: 12px;">{{ $qr['name'] }}</span>
			<img style="z-index: 1; position: relative;" src="{{ $qrPath }}?{{ $qr['query'] }}" width="140" height="140" alt="" />
			@else
		<div style="display: inline-block; width: 150px; margin-bottom: 5px; border: 1px solid #999999; text-align: center; padding: 10px 0;">
		{{ $qr['name'] }}<br />
		<img src="{{ $qrPath }}?{{ $qr['query'] }}" width="140" height="140" alt="" />
			@endif
			<button onClick="location.href='{{ $downloadUrl($qr) }}'; return false;">ダウンロード</button>
			<input
				type="checkbox"
				name="qr_print_flg[]"
				data-name="{{ $qr['name'] }}"
				data-qr_code="{{ $info->absoluteQrUrl($qr['query']) }}"
			>
		</div>
		@endforeach

		</td>
		</tr>

	@endif

	@if ($mobileQrs !== null)
	<tr>
	<th>携帯WEBマイページ用の会員登録用QRコード</th>
	</tr>
	<tr>
	<td>
	@foreach ($mobileQrs as $qr)
	<div style="display: inline-block; width: 150px; margin-bottom: 5px; border: 1px solid #999999; text-align: center; padding: 10px 0;">
	{{ $qr['name'] }}<br />
	<img src="{{ $qrPath }}?{{ $qr['query'] }}" width="140" height="140" alt="" />
		<button onClick="location.href='{{ $downloadUrl($qr) }}'; return false;">ダウンロード</button>
		<input
			type="checkbox"
			name="qr_print_flg[]"
			data-name="{{ $qr['name'] }}"
			data-qr_code="{{ $info->absoluteQrUrl($qr['query']) }}"
		>
	</div>
	@endforeach

	</td>
	</tr>
	@endif
</table>
@endif

<!-- / .contents -->
</div>
@endsection
