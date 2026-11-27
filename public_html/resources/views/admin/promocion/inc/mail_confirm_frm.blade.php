{{--
	配信確認 común de los envíos — legacy: system/category3/inc/mail_confirm_frm.php

	Variables: las comunes de MailController (mailType, toolName, routes…) y
	  $in (campos ya convertidos), $error, $pastTime, $dispBody (デコメール), $preview, $image (p_* + url),
	  $excludedCustomers, $shopNames, $coupon, $fromMail, $verify
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@php
	$v = fn (string $k) => $in[$k] ?? '';
	$deco = str_contains($mailType, 'deco');
	$cssClass = $deco ? 'mailKakuninNaiyou2' : 'mailKakuninNaiyou';
	$ok = ! $error && ! $pastTime;
	$imageFields = ['p_name_b', 'p_name_s', 'p_kakucyo', 'p_type', 'p_size_b', 'p_size_s', 'p_size_w', 'p_size_h'];
	$hiddenFields = function (array $fields, array $skip = []) {
		$html = '';
		foreach ($fields as $key => $val) {
			if (in_array($key, $skip, true)) {
				continue;
			}
			if (is_array($val)) {
				foreach ($val as $subKey => $subVal) {
					$html .= '<input type="hidden" name="'.e($key).'['.e($subKey).']" value="'.e(is_scalar($subVal) ? $subVal : '').'" />'."\n";
				}
			} else {
				$html .= '<input type="hidden" name="'.e($key).'" value="'.e((string) $val).'" />'."\n";
			}
		}
		return $html;
	};
	$sendDate = match (true) {
		in_array($mailType, ['follow', 'followdeco'], true) => '毎日' . ($in['step2_jikan'] ?? $v('jikan')) . '時' . ($in['step2_fun'] ?? $v('fun')) . '分',
		in_array($mailType, ['auto', 'autodeco'], true) => $v('display_parameters'),
		in_array($mailType, ['yoyaku', 'posyoyaku', 'remdeco', 'posremdeco'], true) => '',
		default => $v('nen') . '年' . $v('tuki') . '月' . $v('hi') . '日' . $v('jikan') . '時' . $v('fun') . '分　配信',
	};
@endphp

@section('title', $toolName . '配信')

@section('css')
<link href="{{ asset('legacy/system/category3/css/colorbox.css') }}" rel="stylesheet" type="text/css" media="all" />
@endsection

@section('js')
<script type="text/javascript" src="{{ asset('legacy/system/category3/js/jquery.colorbox.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/category3/js/testMail.js') }}"></script>
<script type="text/javascript">
$(document).ready(function() {
	//$active_ac: mailmag=1, anketo=8, mycoupon=7
	$("{{ $activeAc }}").accordion('option', 'active', 0);
	window.scroll(0,0);
});

function sendTestMail() {
	var conf = confirm("テスト{{ $toolName }}を送信してもよろしいですか?");

	if (conf) {
		var sendData = $("#testMailSendForm").serialize();
		$.ajax({
			type: "POST",
			url: "{{ route('legacy.promocion.inc.ajax_send_test_mail') }}",
			data: sendData,
			success: function(resp){
				alert(resp);
			}
		});
	}
}
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■{{ $toolName }}配信</p>

@if ($error)
<div style="text-align:center;color:red;"><br/>{{ $error }}<br/></div>
@elseif (! $pastTime)
<p>以下の内容で配信してもよろしいですか？</p>

<table class="tableBox">
  <tr>
    <th><label for="title">{{ $toolName }}タイトル</label></th>
    <td>{{ $v('title') }}</td>
  </tr>
  <tr>
    <th><label for="naiyou">{{ $toolName }}内容<em>※</em></label></th>
    <td>
      <div class="{{ $cssClass }}" style="float: left; width: 15em; padding: 5px; border: 1px solid #CCCCCC; overflow-y: scroll; height: 500px; margin: 15px 15px 0 0;">@if (! $deco){!! nl2br(e($v('naiyou'))) !!}@else{!! $dispBody !!}@endif</div>
      <div class="merumagaKakuninField"><div class="{{ $cssClass }}">
      @if (! $deco){!! nl2br(e($preview)) !!}@else{!! $preview !!}@endif
      </div></div>
      <input type="button" id="testMail_btn" class="btnStyleM" value="テスト{{ $toolName }}配信" />
    </td>
  </tr>

  <tr>
    <th>送信者{{ $toolName }}アドレス</th>
    <td>{{ $fromMail }}</td>
  </tr>

@if (in_array($mailType, ['mail', 'follow', 'auto'], true))
  <tr>
    <th width="100" >画像</th>
    <td width="550" >
@if ($image && isset($image['p_type']))
<img src="{{ $image['url'] }}" border="0" align="left">
画像タイプ：<b>{{ $image['p_type'] }}</b><br>
画像サイズ：横<b>{{ $image['p_size_w'] }}</b>px、縦<b>{{ $image['p_size_h'] }}</b>px<br>
画像ファイルサイズ：<b>{{ $image['p_size_s'] }}</b>byte
@elseif ($image)
<img src="{{ $image['url'] }}" border="0" align="left">
@endif
	</td>
  </tr>
  <tr style="display:none">
	<th width="100" >画像リンク</th>
	<td width="550" >
		{{ $v('pic_link') }}
	</td>
  </tr>
@endif

  <tr>
    <th>振分条件</th>
    <td>{{ $v('search_display') }}</td>
  </tr>

@if ($v('get_coupon_text') !== '')
  <tr>
    <th width="100" >添付クーポン</th>
    <td>{{ $v('get_coupon_text') }}</td>
  </tr>
@elseif ($coupon)
  <tr>
    <th><label for="coupon_detail">添付クーポン</label></th>
	<td><span style="font-weight:bold">{{ $coupon->title }}</span><br/>{!! nl2br(e(mb_strimwidth((string) $coupon->comment, 0, 100, '...'))) !!}</td>
  </tr>
@endif

@if (! str_contains($mailType, 'yoyaku') && ! str_contains($mailType, 'remdeco'))
  <tr>
    <th width="100" >例外顧客</th>
    <td>
@foreach ($excludedCustomers as $customer)
【{{ $customer->id }}】　{{ $customer->name }}　({{ $customer->email1 }})<br />
@endforeach
    </td>
  </tr>
@endif

  <tr>
    <th width="100" >フッター表記店舗</th>
    <td>{{ $shopNames[$v('tourokusya')] ?? '' }}</td>
  </tr>
  <tr>
    <th>備考</th>
    <td>{!! nl2br(e($v('bikou'))) !!}</td>
  </tr>

  <tr>
    <th>配信時間</th>
@if ($v('send_flg') === '1')
    <td>即時配信</td>
@else
    <td >{{ $sendDate }}</td>
@endif
  </tr>

</table>

<p>※即時配信の場合は「配信する」ボタンを押すと取消はできませんのでご注意下さい。</p>
@else
<div style="text-align:center;color:red;"><br/>時間指定に過去を設定することはできません。<br/></div>
@endif

<div class="btnBox">

@if ($update ?? false)
<form action="{{ $routes['edit'] }}" method="post">
@csrf
<input type="submit" class="backBtnStyleM" value="戻　　る">
<input name="back_flg" type="hidden" value="1">
{!! $hiddenFields($in, ['back_flg', 'flg']) !!}
<input type="hidden" name="flg" value="1">
</form>
@if (! $pastTime && ! $error)
<form action="{{ $routes['update'] }}" method="post">
@csrf
<input type="submit" name="act" class="btnStyleM" value="配信する">
<input type="hidden" name="mail_send_verify" id="mail_send_verify" value="{{ $verify }}" />
{!! $hiddenFields($in, ['flg', 'act', 'mail_send_verify']) !!}
<input type="hidden" name="flg" value="1">
</form>
@endif
@else
@if (! $pastTime)
<form action="{{ $routes['select'] }}" method="post">
@csrf
@if (! str_contains($mailType, 'follow') && ! str_contains($mailType, 'shukei') && ! str_contains($mailType, 'yoyaku') && ! str_contains($mailType, 'remdeco'))
<input type="submit" class="btnStyleM" value="抽出しなおす" />
@endif
<input name="back_flg" type="hidden" value="1" />
{!! $hiddenFields($in, array_merge(['back_flg', 'flg'], $imageFields)) !!}
<input type="hidden" name="flg" value="1" />
</form>
&emsp;
@endif

<form action="{{ $routes['create'] }}" method="post">
@csrf
<input type="submit" class="btnStyleM" value="文章を書き直す" />
<input name="back_flg" type="hidden" value="1" />
{!! $hiddenFields($in, ['back_flg', 'flg']) !!}
<input type="hidden" name="flg" value="1" />
</form>

&emsp;
@if ($ok)
<form action="{{ $routes['store'] }}" method="post">
@csrf
<input type="submit" name="act" class="btnStyleM" value="配信する" />
<input type="hidden" name="mail_send_verify" id="mail_send_verify" value="{{ $verify }}" />
{!! $hiddenFields($in, ['flg', 'act', 'mail_send_verify']) !!}
<input type="hidden" name="flg" value="1" />
</form>
@endif

@endif

</div>

<div>
<form id="testMailSendForm">
<input type="hidden" name="testTitle" id="testTitle" value="{{ $v('title') }}" />
<input type="hidden" name="testBody" id="testBody" value="{{ $v('naiyou') }}" />
<input type="hidden" name="testPic" id="testPic" value="{{ $image['p_name_s'] ?? '' }}" />
<input type="hidden" name="testPicShop" id="testPicShop" value="{{ $image ? ($staffShopCd ?? -1) : '' }}" />
<input type="hidden" name="testTourokusya" id="testTourokusya" value="{{ $v('tourokusya') }}" />
<input type="hidden" name="testType" id="testType" value="{{ $mailType }}" />
<input type="hidden" name="search_uniqid" id="search_uniqid" value="{{ $v('search_uniqid') }}" />
<input type="hidden" name="reigai_kaiin_cds" value="{{ $v('reigai_kaiin_cds') }}" />
<input type="hidden" name="testMail_cds" id="testMail_cds" value="" />
</form>
</div>

<!-- / .contents --></div>
@endsection
