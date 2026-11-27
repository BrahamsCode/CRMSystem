{{--
	過去配信詳細 + クリックカウンタ — legacy: system/category3/inc/mailmag_shousai2.php (+ graph_sql.php)
	Variables: $mailType, $toolName, $pageTitle, $mail, $stats (ClickStats::forMail), $imageUrl, $imagePath, $couponTitle,
	  $shopNames, $isMaster, $canCreate, $backQuery, $routes
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', $pageTitle)

@php
	$m = fn (string $c) => $mail->getAttribute($c);
	$a = $stats['ages'];
	$jobs = collect($stats['jobs'])->map(fn ($n, $job) => [$job, $n, \App\Services\Promotions\ClickStats::JOB_COLORS[$job] ?? null])->values()->all();
	$size = $imagePath && is_file($imagePath) ? getimagesize($imagePath) : null;
@endphp

@section('js')
<!--[if IE]><script type="text/javascript" src="{{ asset('legacy/system/js/excanvas.js') }}"></script><![endif]-->
<script type="text/javascript" src="{{ asset('legacy/system/js/circle.js') }}"></script>
<script type="text/javascript">
window.onload = function() {
  var params = {
    caption: true,
    legend: false
  };

  var cg1 = new html5jp.graph.circle("total");
  if( ! cg1 ) { return; }
  var total_items = [
    ["男性", {{ $stats['otoko_total'] }}],
    ["女性", {{ $stats['onna_total'] }}, "#D60000"],
    ["不明", {{ $stats['fumei_total'] }}, "#333333"]
  ];
  cg1.draw(total_items, params);

  var cg2 = new html5jp.graph.circle("nendai_betsu");
  if( ! cg2 ) { return; }
  var nendai_items = [
    ["19歳以下", {{ $a['customer_0'] }}],
    ["20代", {{ $a['customer_20'] }}],
    ["30代", {{ $a['customer_30'] }}],
    ["40代", {{ $a['customer_40'] }}],
    ["50代", {{ $a['customer_50'] }}],
    ["60代", {{ $a['customer_60'] }}],
    ["70歳以上", {{ $a['customer_70'] }}],
    ["その他", {{ $a['customer_undef'] }}, "#CCCCCC"]
  ];
  cg2.draw(nendai_items, params);

  var cg3 = new html5jp.graph.circle("shokugyo_betsu");
  if( ! cg3 ) { return; }
  var shokugyo_items = {!! json_encode($jobs, JSON_UNESCAPED_UNICODE) !!};
  cg3.draw(shokugyo_items, params);
};

$(document).ready(function() {
	$("{{ \App\Http\Controllers\Admin\Promotions\MailController::activeAc($mailType) }}").accordion('option', 'active', 0);
});
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■{{ $pageTitle }}</p>
<table class="tableBox">
  <tr>
    <th><label for="staff_name">{{ $toolName }}タイトル</label></th>
    <td>{{ $m('タイトル') }}</td>
  </tr>
  <tr>
    <th><label for="staff_name">{{ $toolName }}内容<em class="attention">※</em></label></th>
    <td><div class="merumagaKakuninField"><div class="mailKakuninNaiyou{{ $mail->type_flg === 'deco' ? '2' : '' }}">@if ($mail->type_flg === 'deco'){!! nl2br($m('pc内容')) !!}@else{!! nl2br(e($m('pc内容'))) !!}@endif</div></div></td>
  </tr>
@if ((string) $m('画像有無') === '1' && $imageUrl)
<tr>
    <th><label for="staff_name">画像</label></th>
    <td>
<img src="{{ $imageUrl }}" border="0" align="left">
画像タイプ：<b>{{ $m('画像タイプ') }}</b><br>
画像サイズ：横<b>{{ $size[0] ?? '' }}</b>px、縦<b>{{ $size[1] ?? '' }}</b>px<br>
画像ファイルサイズ：<b>{{ $m('画像サイズ小') }}</b>byte
    </td>
</tr>
@endif
  <tr>
    <th><label for="staff_name">振分条件</label></th>
    <td>{{ $m('search_display') }}</td>
  </tr>
@if ($mail->type_data_cd)
  <tr>
    <th><label for="staff_name">添付クーポン</label></th>
    <td>
		{{ $couponTitle }}
    </td>
  </tr>
  <tr>
    <th width="100" >メルマガ投稿店舗</th>
    <td >{{ $shopNames[$m('店id')] ?? '' }}</td>
  </tr>
@endif
  <tr>
    <th><label for="tourokusya">フッター表記店舗</label></th>
    <td>{{ $shopNames[$m('登録者')] ?? '' }}</td>
  </tr>
  <tr>
    <th width="100" >メルマガ投稿店舗</th>
    <td >{{ $shopNames[$m('店id')] ?? '' }}</td>
  </tr>
  <tr>
    <th><label for="bikou">備考</label></th>
    <td>{!! nl2br(e($m('備考'))) !!}</td>
  </tr>
  <tr>
    <th><label for="send_flg">配信時間</label></th>
    <td>
    {{ $m('配信日時') }}
    </td>
  </tr>
</table>

<!-- アクセス解析テーブル 開始 -->
<table class="tableBox" style="width:700px" cellpadding="4" cellspacing="1" >
	<tr><th colspan="3" style="text-align:center;">クリックカウンタ</th></tr>
	<tr height="280px">
	  <td style="vertical-align:top;width:33%">
			<div style="text-align:center">
			<p style="color:blue;font-size:16px;font-weight:bold;">性別</p>
			@if ($stats['count'] > 0)
			<canvas width="200" height="200" id="total"></canvas>
			@else
			<p style="margin-top:100px;color:red;font-size:16px;font-weight:bold;">データはありません</p>
			@endif
			</div>
	  </td>
	  <td style="vertical-align:top;width:33%">
			<div style="text-align:center">
			<p style="color:blue;font-size:16px;font-weight:bold;">年代別</p>
			@if ($stats['count'] > 0)
			<canvas width="200" height="200" id="nendai_betsu"></canvas>
			@else
			<p style="margin-top:100px;color:red;font-size:16px;font-weight:bold;">データはありません</p>
			@endif
			</div>
	  </td>
	  <td style="vertical-align:top;width:33%">
			<div style="text-align:center">
			<p style="color:blue;font-size:16px;font-weight:bold;">職業別</p>
			@if ($jobs !== [])
			<canvas width="200" height="200" id="shokugyo_betsu"></canvas>
			@else
			<p style="margin-top:100px;color:red;font-size:16px;font-weight:bold;">データはありません</p>
			@endif
			</div>
	  </td>
	</tr>
</table>

@if ($stats['count'] > 0 && $isMaster)
<div style="test-align:center;width:700px;">
	<form method="post" action="{{ $routes['clicksCsv'] }}">
		@csrf
		<input name="mailmagcd" type="hidden" value="{{ $mail->cd }}" />
		<input type="submit" name="enter" value="CSV出力する" />
	</form>
</div>
@endif
<!-- アクセス解析テーブル 終了 -->

@if (in_array($mailType, ['mail', 'deco'], true))
<div class="btnBox">
<form action="{{ $routes['select'] }}" method="post">
@csrf
<input type="hidden" name="mailmagcd" value="{{ $mail->cd }}">
@if ($canCreate)
<input type="submit" name="act" class="btnStyleM" value="同じ内容で新規に{{ $toolName }}配信する">
@endif
</form>
</div>
@endif

<p><a href="{{ $routes['past'] . '?' . http_build_query($backQuery) }}" class="backBtnStyle">一覧へ戻る</a><br/></p>
<!-- / .contents --></div>
@endsection
