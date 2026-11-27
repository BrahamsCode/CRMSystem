{{--
    Legacy: system/category9/yoyaku/time_table_staff.php (スタッフ別予約表: el mes de un empleado, día por fila;
    marcar días sin franja y 「選択した日で一括受付開始する」 → yoyaku_multi_uketsuke_day.php).
    Variables:
      $shopCd        int      tienda (la de sesión si no es master).
      $isMaster      bool     muestra el <select> de tiendas; $shopName ?string (no master); $shops iterable (cd, kaishamei).
      $staffCd       int      empleado; $staffRequest ?string `staff` de la petición; $staffName string.
      $d             ?string  `d` de la petición (Ymd); $td string `d` u hoy (variable JS `td`).
      $backDay       string   `bd` o `d`: día al que vuelve 「戻る」.
      $month         Carbon   día 1 del mes mostrado; $prevMonth, $nextMonth string Ymd (último día del mes anterior, día 1 del siguiente).
      $versionFlg    string   '1' carga time_table.css.
      $isTablet, $isIpad, $isIos bool.
      $timeCount     int|float franjas de 30 min del horario de tb_yoyaku_config; $timeLabels list<string> 'H:i'.
      $staffWord     string   manageWord('スタッフ', 'c9_yoyaku[スタッフ]').
      $yoyakuSetsubiFlg, $freeYoyakuFlg ?string (yoyaku_navi).
      $monthDays     list     un elemento por día: day, ymd, value ('Y/m/d'), dow, dowDisp, dowColor, bg, header (fila de horas),
                              valid (>= hoy) y slot (null o la misma forma que `slot` de timetable.blade.php).
      $freeKoumokuLabels list<string> campos libres visibles (popup).
--}}
@extends('layouts.legacy-popup')

@section('title', 'トップ -総合業務管理システム-')

@section('wrapClass', 'yoyakuWrap')

@section('css')
@if ($versionFlg == '1')
<link href="{{ asset('legacy/system/category9/yoyaku/css/time_table.css') }}" rel="stylesheet" type="text/css" media="all" />
@endif
<link rel="stylesheet" type="text/css" href="{{ asset('legacy/system/css/popInline.css') }}">
<style type="text/css"><!--

.yoyakuField {
	width: {{ $timeCount * 30 + 234 }}px;
}

@if ($isIos)
.wrap { overflow:visible!important; background: none; }
#popWinWrap { position: relative !important; }
@endif

--></style>
@endsection

@section('js')
<script type="text/javascript" src="{{ asset('legacy/system/js/popInline.js') }}"></script>
{{-- inc/ac.php --}}
<script type="text/javascript">
$(document).ready(function() {
@foreach ([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 109, 110, 111] as $ac)
	$(".ac{{ $ac }}").accordion({ collapsible: true , active: false});
@endforeach
});
</script>
@php
    $slotEditUrl = route('admin.reservas.slots.edit');
@endphp
<script type="text/javascript">

var yoyakuWindow = window;
var td = {{ \Illuminate\Support\Js::from($td) }};
var sess = {{ \Illuminate\Support\Js::from(session()->getId()) }};
var hyoujiType = "month";

@include('admin.reservas.partials.timetable_js')

function submitChk () {
	var a=false;

	$(".day_check").each(function(e) {
		if ($(this).attr("checked") == true) a=true;
	});

	if (a==false) alert ('日を選択して下さい！！！');

	return a;
}

$(document).ready(function() {

	$(".ac1").accordion('option', 'active', 0);

	$("#shop").change(function(){
		document.shop_select.submit();
	});

	$(".contents").height(($(window).height() - 262));

	//ポップアップ用
	popIni();

	$(".day_check").click(function(e) {
		var status = $(this).attr("checked");
		var dow = $(this).attr("dow");
		if (e.ctrlKey) {
			$(".day"+dow).attr("checked", status);
		}
	});
});

$(window).resize(function(){
	$(".contents").height(($(window).height() - 262));
});

function setDayStatus(targetday) {

	var statusFlg = $("#status_flg_" + targetday).val();

		$.get("/system/category9/yoyaku/ajax_uketsuke_status.php", {
			td: targetday,
			sess: sess,
			staffCd: {{ \Illuminate\Support\Js::from((string) $staffRequest) }},
			statusFlg: statusFlg
			}, function(retData) {
				if (retData == "") {
					location.reload();
				} else {
					alert("エラーが発生しました。。。" + retData);
				}
		});

}

function wopen_config_day(targetday){
	var sendData;
		sendData = "staff=" + encodeURIComponent({{ \Illuminate\Support\Js::from((string) $staffRequest) }}) + "&yoyaku_date="+targetday;

	// 2011.12.06 ohno tablet用にボタンスクリプトを分岐
@if ($isTablet)
		roid.popUp({{ \Illuminate\Support\Js::from($slotEditUrl) }} + '?' + sendData);
@else
		openPopWindow({{ \Illuminate\Support\Js::from($slotEditUrl) }} + '?' + sendData, '600', '500');
@endif
}

function wopen_config(cd){
	if (cd == "" ) return;

	// 2011.12.06 ohno tablet用にボタンスクリプトを分岐
@if ($isTablet)
		roid.popUp({{ \Illuminate\Support\Js::from($slotEditUrl) }} + '?cd='+cd);
@else
		openPopWindow({{ \Illuminate\Support\Js::from($slotEditUrl) }} + '?cd='+cd, '600', '500');
@endif
}

</script>
@endsection

@section('content')
<form action="{{ route('admin.reservas.timetable') }}" method="get" name="shop_select" style="position: absolute; top: 5px; right: 5px; z-index: 2;">

@if (! $isMaster)
			{{ $shopName }}<input type="hidden" name="shop" id="shop" value="{{ $shopCd }}" />
@else
			<select name="shop" id="shop">
			<option value="">▼店舗選択</option>
@foreach ($shops as $shop)
			<option value="{{ $shop->cd }}" @if ($shop->cd == $shopCd) selected="selected" @endif>{{ $shop->kaishamei }}</option>
@endforeach
			</select>
@endif

@if ($d != '')
<input type="hidden" name="d" value="{{ $d }}" />
@endif
</form>


@include('admin.reservas.partials.yoyaku_navi')

@php
    $tableWidth = (int) $timeCount * 30 + 30;
    $statusFlgOptions = \App\Services\Reservations\TimetableService::STATUS_OPTIONS;
    $timetableAt = fn (array $params, string $fragment) => route('admin.reservas.timetable', $params).'#'.$fragment;
@endphp

<div class="yoyakuDayControlArea">
<div style="text-align:left;margin: 0 0 0 10px;position:absolute;"><a class="tsukiNaviBtn" href="{{ $timetableAt(['shop' => $shopCd, 'd' => $backDay], 'st'.$staffRequest) }}">　戻る　</a></div>
<a class="tsukiNaviBtn" href="{{ route('admin.reservas.timetable.staff', ['shop' => $shopCd, 'staff' => $staffRequest, 'd' => $prevMonth, 'bd' => $backDay]) }}">&lt;&lt;前月</a>

<span class="hyoujiDate">[{{ $month->format('Y') }}<small>年</small>{{ $month->format('m') }}<small>月</small>] {{ $staffName }} <small>予約状況</small></span>
<a class="tsukiNaviBtn" href="{{ route('admin.reservas.timetable.staff', ['shop' => $shopCd, 'staff' => $staffRequest, 'd' => $nextMonth, 'bd' => $backDay]) }}">次月&gt;&gt;</a>
</div>


<form action="{{ route('admin.reservas.slots.bulk') }}" method="get" name="form1" onsubmit="return submitChk()">

<div class="contents" id="timeTableDiv" style="position:relative;overflow:auto;">

<div class="yoyakuField">

<table class="yoyakuListTable">
@foreach ($monthDays as $day)
@if ($day['header'])

	<tr>
	<th class="timeBarMidashi"></th>
	<th class="timeBarMidashi" style="width: 82px;">状況</th>
	<td style="width: {{ $tableWidth }}px;">
	<div class="timeBarParent">
@foreach ($timeLabels as $i => $label)
		<div class="timeBar" style="left: {{ $i * 30 }}px;">{{ $label }}</div>
@endforeach
	</div>
	</td>
	</tr>
@endif
@php
    $slot = $day['slot'];
    $bgStyle = $day['bg'];
@endphp
	<tr>
	<th class="{{ $bgStyle }}" style="text-align:right;">
	<a href="{{ $timetableAt(['shop' => $shopCd, 'd' => $day['ymd']], 'st'.$staffRequest) }}" style="text-decoration: underline;">{{ $day['day'] }}日</a>（<span style="color:{{ $day['dowColor'] }}">{{ $day['dowDisp'] }}</span>）
@if ($day['valid'])
		<br/><a href="javascript:{{ $slot ? "wopen_config('".$slot['cd']."');" : "wopen_config_day('".$day['ymd']."');" }}" style="font-weight:normal; font-size:10px; text-decoration: underline;" >[設定]</a>
@endif
	</th>
@if ($slot)
@php
    $flgStyle = $day['valid'] ? match ((string) $slot['status_flg']) { '1' => '#778899', '2' => '#998888', default => null } : null;
@endphp
	<th @if ($flgStyle) style="background:{{ $flgStyle }}" @else class="{{ $bgStyle }}" @endif>

@if ($day['valid'])
	<select name="status_flg_{{ $day['ymd'] }}" id="status_flg_{{ $day['ymd'] }}">
@foreach ($statusFlgOptions as $flg => $statusLabel)
@php $flgVal = $flg == 0 ? '' : (string) $flg; @endphp
<option value="{{ $flgVal }}" @selected($flgVal === (string) $slot['status_flg'])>{{ $statusLabel }}</option>
@endforeach
	</select>
	<input type="button" name="flgbtn_{{ $day['ymd'] }}" value="　状況変更　" onclick="setDayStatus('{{ $day['ymd'] }}')" />
@else
	<span style="color:#FF3333;font-weight:bold;">終了しました</span>
@endif
	</th>
	<td class="{{ $bgStyle }}">
	<div style="position:relative;border:none;min-height:60px;">
@foreach ($slot['breaks'] as $break)
		<div style="position:absolute; width:{{ $break['width'] }}px; height:100%; left:{{ $break['left'] }}px; top:0px; background: #777777" title="休憩"></div>
@endforeach

		@if ($slot['noWorkLeft'] > 0)<div style="position:absolute; width:{{ $slot['noWorkLeft'] }}px; height:100%; left:1px; top:0px; background: #777777"></div>@endif
		@if ($slot['noWorkRight'] > 0)<div style="position:absolute; width:{{ $slot['noWorkRight'] }}px; height:100%; right:0px; top:0px; background: #777777"></div>@endif

		<div class="customerBarParent">
@foreach ($slot['bars'] as $bar)
@include('admin.reservas.partials.customer_bar', ['bar' => $bar])
@endforeach
@if (! is_null($slot['fillerHeight']))
<div style="width: 1px; height: {{ $slot['fillerHeight'] }}px;"></div>
@endif
		</div>
	</div>
	</td>
@else
<th class="{{ $bgStyle }}" style="text-align:center;">@if ($day['valid'])<input class="day_check day{{ $day['dow'] }}" type="checkbox" name="days[]" value="{{ $day['value'] }}" dow="{{ $day['dow'] }}" />@endif</th><td class="yoyaku_none{{ $bgStyle }}">予約受付が開始されていません。</td>
@endif
	</tr>
@endforeach
</table>
<!-- / .yoyakuField --></div>

<div id="popFormWrap" style="position:absolute;padding:2px;">
</div>

<!-- / .contents --></div>


<div class="btnBox">

<table class="tableBox5" style="width:500px;">
@foreach (['日', '月', '火', '水', '木', '金', '土'] as $i => $youbi)
<td style="width:14%;">
{{ $youbi }}曜日<br />
<input type="button" value="全て選択" class="btn" onclick="$('.day{{ $i }}').attr('checked', true);" /><br />
<input type="button" value="全て解除" class="btn" onclick="$('.day{{ $i }}').attr('checked', false);" />
</td>
@endforeach
</tr>
</table>

<input type="hidden" name="shop" value="{{ $shopCd }}" />
<input type="hidden" name="staff" value="{{ $staffRequest }}" />
<input type="hidden" name="d" value="{{ $d }}" />
<input type="hidden" name="bd" value="{{ $backDay }}" />
<input type="submit" class="btn" value="選択した日で一括受付開始する" />
</div>

</form>

{{-- Fuera de #container en el legacy --}}
<div id="popWindow">
	<div class="customerInfo"></div>

	<table style="width: 100%; margin-bottom: 10px; border: 1px solid #CCCCCC;">
	<tr style="border-bottom: 1px solid #CCCCCC;">
	<th align="left" style="background:#EEEEEE;font-weight: normal; padding: 0 3px;">所要時間</th>
	</tr>

	<tr>
	<td style="padding: 0 3px;"><span id="startTime"></span> ～ <span id="endTime"></span></td>
	</tr>
	</table>


	<table style="width: 100%; margin-bottom: 10px; border: 1px solid #CCCCCC;">
	<tr style="border-bottom: 1px solid #CCCCCC;">
	<th align="left" style="background:#EEEEEE;font-weight: normal; padding: 0 3px;">施術内容</th>
	</tr>

	<tr>
	<td style="padding: 0 3px;"><span id="menu"></span></td>
	</tr>
	</table>

@foreach ($freeKoumokuLabels as $freeKoumokuLabel)
	<table style="width: 100%; margin-bottom: 10px; border: 1px solid #CCCCCC;">
	<tr style="border-bottom: 1px solid #CCCCCC;">
	<th align="left" style="background:#EEEEEE;font-weight: normal; padding: 0 3px;">{{ $freeKoumokuLabel }}</th>
	</tr>

	<tr>
	<td style="padding: 0 3px;"><span id="free_koumoku_{{ $loop->iteration }}">&emsp;</span></td>
	</tr>
	</table>
@endforeach


	<table style="width: 100%; margin-bottom: 10px; border: 1px solid #CCCCCC;">
	<tr style="border-bottom: 1px solid #CCCCCC;">
	<th align="left" style="background:#EEEEEE;font-weight: normal; padding: 0 3px;">備考</th>
	</tr>

	<tr>
	<td style="padding: 0 3px;"><span id="comment"></span></td>
	</tr>
	</table>

</div>
@endsection
