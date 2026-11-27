{{--
	配信 (registro hecho) común de los envíos — legacy: system/category3/inc/mail_send_frm.php
	Variables: las comunes de MailController y $mail (MailmagMaster registrado), $in (campos enviados), $imageUrl, $shopNames
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@php
	$v = fn (string $k) => $in[$k] ?? '';
	$m = fn (string $k) => $mail->getAttribute($k);
	$immediate = $v('send_flg') === '1';
	$recurring = in_array($mailType, ['follow', 'auto', 'followdeco', 'autodeco'], true);
	$sendDate = match (true) {
		in_array($mailType, ['follow', 'followdeco'], true) => '毎日' . ($in['step2_jikan'] ?? $v('jikan')) . '時' . ($in['step2_fun'] ?? $v('fun')) . '分',
		in_array($mailType, ['auto', 'autodeco'], true) => '&emsp;' . e($v('display_parameters')),
		in_array($mailType, ['yoyaku', 'posyoyaku', 'remdeco', 'posremdeco'], true) => e($v('search_display')),
		default => e($v('nen') . '年' . $v('tuki') . '月' . $v('hi') . '日' . $v('jikan') . '時' . $v('fun') . '分'),
	};
	$shopNames = $shopNames ?? [];
@endphp

@section('title', $toolName . '配信')

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$("{{ $activeAc }}").accordion('option', 'active', 0);
});
</script>
@endsection

@section('content')
<div id="dummy"></div>

<div class="contents">
<p class="h2Title">■{{ $toolName }}配信</p>

<p>@if ($immediate)
	以下の内容で配信いたしました。
	@else
	以下の内容で予約いたしました。
	@endif</p>

<table class="tableBox">
  <tr>
    <th><label for="title">{{ $toolName }}タイトル</label></th>
    <td>{{ $m('タイトル') }}</td>
  </tr>
  <tr>
    <th><label for="naiyou">{{ $toolName }}内容<em>※</em></label></th>
    <td><div class="merumagaKakuninField"><div class="mailKakuninNaiyou{{ str_contains($mailType, 'deco') ? '2' : '' }}">@if (str_contains($mailType, 'deco')){!! nl2br($m('pc内容')) !!}@else{!! nl2br(e($m('pc内容'))) !!}@endif</div></div></td>
  </tr>

@if (in_array($mailType, ['mail', 'follow', 'auto'], true))
  <tr>
    <th width="100" >画像</th>
    <td width="550" >
@if ((string) $m('画像有無') === '1' && $imageUrl)
<img src="{{ $imageUrl }}" border="0" align="left">
画像タイプ：<b>{{ $m('画像タイプ') }}</b><br>
画像サイズ：横<b>{{ $v('p_size_w') }}</b>px、縦<b>{{ $v('p_size_h') }}</b>px<br>
画像ファイルサイズ：<b>{{ $m('画像サイズ小') }}</b>byte
@endif
	</td>
  </tr>
@endif

  <tr>
    <th>振分条件</th>
    <td>{{ $m('search_display') }}</td>
  </tr>
@if ($immediate)
          <tr>
            <th>各配信数</th>
            <td><table width="300" border="0" cellpadding="4" cellspacing="1">
@else
          <tr>
            <th>配信予定数</th>
            <td>
            @if ($recurring)
            以下の数は、現状でのリアルタイム該当顧客数です。<br />実際の配信数を表すものではありませんのでご注意ください。
            @else
            予約のため各配信数は予定の数です。配信までに微増微減する場合があります。<br>
            @endif
			<table width="300" border="0" cellpadding="4" cellspacing="1" >
@endif
                <tr >
                  <td width="100" >パソコン</td>
                  <td width="50">{{ $m('pc配信数') }}</td>
                  <td width="100" >DoCoMo</td>
                  <td width="50">{{ $m('docomo配信数') }}</td>
                </tr>
                <tr >
                  <td width="100" >AU</td>
                  <td>{{ $m('au配信数') }}</td>
                  <td width="100" >Softbank</td>
                  <td>{{ $m('vodafone配信数') }}</td>
                </tr>
                <tr >
                  <td width="100" >その他</td>
                  <td>{{ $m('その他配信数') }}</td>
                  <td width="100" > </td>
                  <td> </td>
                </tr>
                <tr align="right">
                  <td colspan="4" >総数：{{ $m('配信総数') }}</td>
                </tr>
              </table>
@if ($immediate)
			  <p>ここでの総配信数は検索条件にマッチした配信数であり、実際に配信を試みた総数は、メールでの送信レポートをご確認ください。</p>
@endif
			</td>
          </tr>

@if ($v('get_coupon_text') !== '')
  <tr>
    <th width="100" >添付クーポン</th>
    <td>{{ $v('get_coupon_text') }}</td>
  </tr>
@endif

@if (! str_contains($mailType, 'yoyaku') && ! str_contains($mailType, 'remdeco'))
  <tr>
    <th width="100" >例外顧客</th>
    <td>
@foreach (app(\App\Services\Promotions\MailAudience::class)->customers(\App\Services\Promotions\MailAudience::cds($v('reigai_kaiin_cds'))) as $customer)
【{{ $customer->id }}】　{{ $customer->name }}　({{ $customer->email1 }})<br />
@endforeach
    </td>
  </tr>
@endif

  <tr>
    <th width="100" >フッター表記店舗</th>
    <td >{{ $shopNames[$m('登録者')] ?? '' }}</td>
  </tr>
  <tr>
    <th>備考</th>
    <td>{{ $m('備考') }}</td>
  </tr>
  <tr>
    <th>配信日時</th>
@if ($immediate)
    <td>即時配信</td>
@else
    <td >{!! $sendDate !!}</td>
@endif
  </tr>
</table>

<!-- / .contents --></div>
@endsection
