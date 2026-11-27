{{--
    Legacy: system/category9/yoyaku/time_table.php (+ makeJS()/makeYoyakuTableRow() de time_table_function.php).
    予約表: vista día (?d=Ymd), 月間 (?ichiran=1&date=Y-m) y 週間 (?ichiran=2&date=Y-Www).

    Variables:
      $shopCd               int     tienda mostrada (la de sesión si no es master).
      $isMaster             bool    $_SESSION['rank'] == 'master' (muestra el <select> de tiendas).
      $shopName             string  nombre (kaishamei) de la tienda de sesión; solo si !$isMaster.
      $shops                iterable tiendas para el <select> (cd, kaishamei); disp_flg != '9', orden cd.
      $d                    ?string `d` de la petición (Ymd) o null.
      $td                   string  $d o la fecha de hoy (Ymd); variable JS `td`.
      $baseDate             Carbon  día mostrado en vista día (Asia/Tokyo; hoy si no hay `d`).
      $ichiran              int     0 = día, 1 = 月間, 2 = 週間.
      $ichiranDate          ?string `date` de la petición ('Y-m' o 'Y-Www').
      $ichiranStart, $ichiranEnd ?Carbon primer y último día del 月間/週間 (null en vista día).
      $ctiParams            array   [] o ['isCti' => 1, 'tel' => .., 'customerCd' => ..] (CTI 電話予約モード).
      $customerCd           ?string `customerCd` de la petición (input oculto #customerCd).
      $versionFlg           string  '1' | '2' (Base::versionFlg: botones con imagen o texto, time_table.css solo en '1').
      $isTablet             bool    terminal tablet (roid.popUp).
      $isIpad               bool    User-Agent contiene iPad.
      $isMac                bool    cUserAgent TYPE == 'mac'.
      $isRw107              bool    cUserAgent DEVICE_NAME == 'CRMRW107'.
      $configError          bool    la tienda no tiene fila en tb_yoyaku_config.
      $timeCount            int     franjas de 30 min entre start_time y end_time de tb_yoyaku_config.
      $timeLabels           array   $timeCount + 1 textos 'H:i' (cabecera .timeBar, una cada 30 min).
      $startsAtHalfHour     bool    start_time de tb_yoyaku_config no empieza en ':00'.
      $keikaByou            int     segundos transcurridos hoy desde la hora de apertura (barra .timeZone, left = /60 px).
      $staffWord            string  manageWord('スタッフ', 'c9_yoyaku[スタッフ]').
      $yoyakuSetsubiFlg, $freeYoyakuFlg ?string admin_master cd=1 (navegación).
      $raitenListEnabled    bool    tb_base_system_child '来店履歴管理' activo (pestaña 来店リスト).
      $listPrintEnabled     bool    tb_base_system_grandchild '予約一覧出力' activo.
      $realtimePrintEnabled bool    tb_base_system_grandchild 'リアルタイム印刷' activo (iframe de impresión).
      $controlPanel         ?string cookie c9_yoyaku_control_panel ('yoyakuList' | 'raitenList' | 'ichiran' | otro).
      $calendarMonths       array   3 Carbon (día 1 del mes actual, +1, +2) para los calendarios pequeños.
      $staffCount           int     personal con reserve_staff_work_flg de la tienda.
      $days                 array   un elemento por día mostrado (1 en vista día). Forma:
          [
            'key'       => 'Ymd',
            'header'    => 'Y年m月d日(曜) ',
            'valid'     => bool,          // día >= hoy (se puede cambiar 状況)
            'yoyakuCnt' => int, 'peopleCnt' => int,
            'rows'      => [              // uno por empleado, en el orden de Staff::getList
              [
                'cd' => int, 'name' => string, 'bg' => 'bg' | '',
                'slot' => null | [        // tb_yoyaku_uketsuke_day del día (null: 予約受付が開始されていません)
                  'cd' => int, 'status_flg' => ?string,
                  'noWorkLeft'  => float, // px de gris a la izquierda ((start_time - inicio config) / 60)
                  'noWorkRight' => float, // px de gris a la derecha ((fin config - end_time) / 60 + 30)
                  'breaks' => [['left' => float, 'width' => float], ...], // 休憩 ya recortados y sin anchos <= 0
                  'bars'   => [ ... ver partials/customer_bar ... ],
                  'fillerHeight' => ?int, // alto del div final (pos_cnt_max * 24 + 2) o null sin reservas
                ],
              ], ...
            ],
          ]
          Las horas de tb_yoyaku/tb_yoyaku_uketsuke_day son segundos desde las 00:00 del día de la franja
          (pueden pasar de 86400); todas las posiciones (left/top/width/height en px) llegan ya calculadas.
      $totalYoyakuCnt, $totalPeopleCnt int totales de todos los días.
      $freeKoumokuLabels    array   tb_koumoku_name de los free_koumoku_N con koumoku_disp == '1' (popup).
      $openYoyakuCd, $openShopCd, $openD ?string parámetros GET open_* (iPad).
--}}
@extends('layouts.legacy-popup')

@section('title', 'トップ -総合業務管理システム-')

@section('wrapClass', 'yoyakuWrap')

@section('css')
@if ($versionFlg == '1')
<link href="{{ asset('legacy/system/category9/yoyaku/css/time_table.css') }}" rel="stylesheet" type="text/css" media="all" />
@endif
<link rel="stylesheet" type="text/css" href="{{ asset('legacy/system/css/popInline.css') }}">
<style type="text/css">
@if ($isIpad)
@include('admin.reservas.partials.timetable_ipad_css')
@endif

.yoyakuField {
	width: {{ $timeCount * 30 + 234 }}px;
}

@if ($isMac)
.timeBar {
	font-size: 9px;
}
@endif
@if ($isRw107)
body { background-color: transparent; }
#timeTableDiv { height:auto!important; }
.contents { overflow-x:visible!important; }
.wrap { overflow:visible!important; background: none; }
.main4 { border: 0; }
.main3 { padding: 10px 0; }
.yoyakuControlArea { text-align:left; padding: 10px 10px 0; }
@endif
</style>
<style>
	body::-webkit-scrollbar {
		display: none;
	}
	html::-webkit-scrollbar {
		display: none;
	}
</style>
@endsection

@section('js')
{{-- inc/ac.php --}}
<script type="text/javascript">
$(document).ready(function() {
	$(".ac1").accordion({ collapsible: true , active: false});
	$(".ac2").accordion({ collapsible: true , active: false});
	$(".ac3").accordion({ collapsible: true , active: false});
	$(".ac4").accordion({ collapsible: true , active: false});
	$(".ac5").accordion({ collapsible: true , active: false});
	$(".ac6").accordion({ collapsible: true , active: false});
	$(".ac7").accordion({ collapsible: true , active: false});
	$(".ac8").accordion({ collapsible: true , active: false});
	$(".ac9").accordion({ collapsible: true , active: false});
	$(".ac10").accordion({ collapsible: true , active: false});
	$(".ac11").accordion({ collapsible: true , active: false});
	$(".ac12").accordion({ collapsible: true , active: false});
	$(".ac13").accordion({ collapsible: true , active: false});
	$(".ac14").accordion({ collapsible: true , active: false});
	$(".ac109").accordion({ collapsible: true , active: false});
	$(".ac110").accordion({ collapsible: true , active: false});
	$(".ac111").accordion({ collapsible: true , active: false});
});
</script>
<script type="text/javascript" src="{{ asset('legacy/system/js/popInline.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/js/toast.js') }}"></script>
@php
    // URL base del 予約表 para los saltos que arma el JS (mismos parámetros que el legacy).
    $timetableUrl = route('admin.reservas.timetable');
@endphp
<script type="text/javascript">

var yoyakuWindow = window;
var td = {{ \Illuminate\Support\Js::from($td) }};
var sess = {{ \Illuminate\Support\Js::from(session()->getId()) }};
var hyoujiType = "day";
var intervalHandler;

@include('admin.reservas.partials.timetable_js')

$(document).ready(function() {

	$.ajaxSetup({ cache: false }); // 2013.01.18【大野】Ajaxのキャッシュを切る

	$(".ac1").accordion('option', 'active', 0);

	$("#shop").change(function(){
		document.shop_select.submit();
	});

	$(".contents").height(($(window).height() - 200));

	//ポップアップ用
	popIni();


	//時間のバーの設定
	timeWariate = '{{ $keikaByou }}';
	$(".timeZone").css("left",timeWariate/60+"px");
	$(".timeZone").css("height", "100%");


	//コントロールエリアの追加 2011.05.18 yoshida
	$("#calAreaControl_calendar").click(function(){
		loadNewYoyakuList("calendar");
	});

	$("#calAreaControl_yoyakuList").click(function(){
		loadNewYoyakuList("yoyakuList");
	});

	$("#calAreaControl_raitenList").click(function(){
		loadNewYoyakuList("raitenList");
	});

	$("#calAreaControl_ichiran").click(function(){
		loadNewYoyakuList("ichiran");
	});

	$("#ichiranButtons button").click(function(){
		if ( $(this).attr("id") == "ichiranMonthButton" ) {
			location.href={{ \Illuminate\Support\Js::from(route('admin.reservas.timetable', ['shop' => $shopCd, 'ichiran' => 1, 'date' => now('Asia/Tokyo')->format('Y-m')])) }};
		} else {
			location.href={{ \Illuminate\Support\Js::from(route('admin.reservas.timetable', ['shop' => $shopCd, 'ichiran' => 2, 'date' => now('Asia/Tokyo')->format('o-\WW')])) }};
		}
	});

	$("#ichiranCalendar button").click(function(){
		$("#ichiranButtons").show();
		$("#ichiranCalendar").hide();
	});

	$("#ichiranMonth, #ichiranWeek").change(function() {
		location.href={{ \Illuminate\Support\Js::from($timetableUrl) }}+'?shop={{ $shopCd }}&ichiran='+$(this).attr("ichiran")+'&date='+$(this).val()+'';
	});

	intervalHandler = setInterval(function(){location.reload(true);},300000);

	$(".date-header").click(function(){
		$('.'+$(this).attr('rel')).toggle();
	});

@if ($controlPanel == 'yoyakuList')
	loadNewYoyakuList("yoyakuList");
@elseif ($controlPanel == 'raitenList')
	loadNewYoyakuList("raitenList");
@elseif ($controlPanel == 'ichiran')
	loadNewYoyakuList("ichiran");
@else
	loadNewYoyakuList("calendar");
@endif

	$("*").click(function(e){
		if (!$(this).is('.propagateThis')) e.stopPropagation();
		clearInterval(intervalHandler);
		if ($(this).is('.popWindowAnchor')) return false;
		intervalHandler = setInterval(function(){location.reload(true);},300000);
	});

	// 2017.09.05 マルティン 電話予約モードを解除
	$('#clearCtiMode').click(function(evt) {
		popUpWinReloadClose('', true)
	});
});


$(window).resize(function(){
	$(".contents").height(($(window).height() - 255));
});


function setStatus(staffCd, date) {
	var statusFlg = $("#status_flg_"+staffCd+"_"+date).val();

	dday = "";
	if (date != "") {
		dday = date;
	} else {
		dday = td;
	}

	$.get("/system/category9/yoyaku/ajax_uketsuke_status.php", {
		td: dday,
		sess: sess,
		staffCd: staffCd,
		statusFlg: statusFlg
		}, function(retData) {
			if (retData == "") {
				location.reload();
			} else {
				alert("エラーが発生しました。。。"+retData);
			}
	});
}

var win_child = "";
function wopen_config(staff, cd, date){
	var sendData;
	if (staff != "" && date != "" && cd != "") {
		sendData = "staff="+staff+"&cd="+cd+"&yoyaku_date="+date;
	} else if (staff != "" && date != "") {
		sendData = "staff="+staff+"&yoyaku_date="+date;
	} else if (cd == "") {
		sendData = "staff="+staff+"&yoyaku_date="+td;
	} else {
		sendData = "cd="+cd;
	}


	// 2011.12.06 ohno tablet用にボタンスクリプトを分岐
@if ($isIpad)
	win_child = window.open( {{ \Illuminate\Support\Js::from(route('admin.reservas.slots.edit')) }}+'?'+sendData, "_blank" );
@elseif ($isTablet)
	roid.popUp({{ \Illuminate\Support\Js::from(route('admin.reservas.slots.edit')) }}+'?'+sendData);
@else
	openPopWindow({{ \Illuminate\Support\Js::from(route('admin.reservas.slots.edit')) }}+'?'+sendData, '600', '500');
@endif
}




function loadNewYoyakuList(typeFlg){
	if(typeFlg == "yoyakuList"){
		$("#calendarControl").hide();
		$("#newYoyakuList").show();
		$("#newRaitenList").hide();
		$("#ichiranControl").hide();

		$("#newYoyakuList").html('<img style="display: block; margin: -20px auto 0;" src="{{ asset('legacy/system/img/loading.gif') }}" alt="loading" />');

		$("#newYoyakuList").load("/system/category9/yoyaku/ajax_loadNewYoyakuList.php",{
			tb_shop_cd:{{ $shopCd }},
			type:typeFlg
		});

	}else if(typeFlg == "raitenList"){
		$("#calendarControl").hide();
		$("#newYoyakuList").hide();
		$("#newRaitenList").show();
		$("#ichiranControl").hide();

		$("#newRaitenList").html('<img style="display: block; margin: -20px auto 0;" src="{{ asset('legacy/system/img/loading.gif') }}" alt="loading" />');

		$("#newRaitenList").load("/system/category9/yoyaku/ajax_loadNewRaitenList.php?d=" + encodeURIComponent({{ \Illuminate\Support\Js::from((string) $d) }}),{
			tb_shop_cd:{{ $shopCd }},
			type:typeFlg
		});

	}else if(typeFlg == "ichiran"){
		$("#calendarControl").hide();
		$("#newYoyakuList").hide();
		$("#newRaitenList").hide();
		$("#ichiranControl").show();

		var req = "{{ $ichiran ?: '' }}";
		if (req > 0) {
			$("#ichiranButtons").hide();
			$("#ichiranCalendar").show();
			if (req == 1) {
				$("#ichiranMonthCalendar").show();
				$("#ichiranWeekCalendar").hide();
			} else {
				$("#ichiranMonthCalendar").hide();
				$("#ichiranWeekCalendar").show();
			}
		}

		setCookie('c9_yoyaku_control_panel', "ichiran", 30);

	}else{
		$("#calendarControl").show();
		$("#newYoyakuList").hide();
		$("#newRaitenList").hide();
		$("#ichiranControl").hide();

		//2011.10.07 yoshida
		setCookie('c9_yoyaku_control_panel', "", 30);
	}

}

</script>

{{-- Script que el legacy pone tras </body>: totales y cabeceras de día --}}
@php
    $headerData = [];
    $dispDateHeader = [];
    foreach ($days as $day) {
        $headerData[$day['key']] = ['day_yoyaku_cnt' => $day['yoyakuCnt'], 'day_people_cnt' => $day['peopleCnt']];
        $dispDateHeader[$day['key']] = $day['header'];
    }
@endphp
<script>
var headerData = {{ \Illuminate\Support\Js::from($headerData ?: null) }};
var disp_date_header = {{ \Illuminate\Support\Js::from($dispDateHeader ?: null) }};
$(document).ready(function() {
	$("#total_yoyaku_cnt").text("{{ $totalYoyakuCnt }}");
	$("#total_people_cnt").text("{{ $totalPeopleCnt }}");

	if (headerData) {
		for (key in headerData) {
			$('.date-header[rel="' + key + '"]').text(disp_date_header[key] + headerData[key]['day_yoyaku_cnt'] + '件 ' + headerData[key]['day_people_cnt'] + '名');
		}
	}
});
</script>
@endsection

@section('content')
<input type="hidden" id="customerCd" value="{{ $customerCd }}" />

<form action="{{ route('admin.reservas.timetable') }}" method="get" name="shop_select" style="position: absolute; top: 5px; right: 5px; z-index: 2;">

<div style="display: inline-block; width: 5em; border: 1px solid #00479D; background-color: #FFBBBB; color: #00479D; text-align: center;">会員</div>
<div style="display: inline-block; width: 5em; border: 1px solid #00479D; background-color: #AACCFF; color: #00479D; text-align: center;">非会員</div>

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

@if ($ctiParams)
<input type="hidden" name="isCti" value="{{ $ctiParams['isCti'] }}" />
<input type="hidden" name="tel" value="{{ $ctiParams['tel'] }}" />
<input type="hidden" name="customerCd" value="{{ $ctiParams['customerCd'] }}" />
@endif
</form>




@include('admin.reservas.partials.yoyaku_navi')

@if ($configError)
<div style="text-align: center; color:#EE0000;">※この店舗の予約設定は未設定です。まず、予約設定を行って下さい。</div>
@else
@include('admin.reservas.partials.yoyaku_control')

@if ($ctiParams)
<div style="height:24px; margin: 3px;">
	<div style="border:2px solid #E60000;background-color: #FFFF00;z-index:2;font-weight:bold;font-size:16px;padding:5px;position:absolute;">
		<span>電話予約モード</span>
		<input type="button" id="clearCtiMode" value="解除" />
	</div>
</div>
@endif

<div class="contents" id="timeTableDiv" style="position:relative;">

<div class="yoyakuField">

@if ($staffCount > 0)
@php
    $tableWidth = $timeCount * 30 + 30;
    // 状況 (status_flg): 0 は値 '' で送る
    $statusFlgOptions = [0 => '受付中', 1 => '電話受付', 3 => '締切間近', 2 => '受付終了'];
@endphp
<table class="yoyakuListTable">

@foreach ($days as $day)

	<tr><td class="date-header" rel="{{ $day['key'] }}" colspan="3" style="background:#202124; text-align: center; color:white; padding:5px;"></td></tr>

@foreach ($day['rows'] as $row)
@php
    $slot = $row['slot'];
    $bgStyle = $row['bg'];
    $staffName = $row['name'];
    $staffNameShort = mb_strwidth($staffName) > 16 ? mb_strimwidth($staffName, 0, 16, '...') : $staffName;
@endphp

	<tr class="{{ $day['key'] }}">
		<th class="timeBarMidashi"></th>
		<th class="timeBarMidashi" style="width: 82px;">状況</th>
		<td class="timeBarMidashi" style="width: {{ $tableWidth }}px;">
			<div class="timeBarParent">
@foreach ($timeLabels as $i => $label)
				<div class="timeBar" style="left: {{ $i * 30 }}px;">{{ $label }}</div>
@endforeach
			</div>
		</td>
	</tr>

	<tr class="{{ $day['key'] }}">
	<th class="{{ $bgStyle }}">
		{{-- name: se escapa dos veces porque selDest() lo concatena como HTML --}}
		<div id="st{{ $row['cd'] }}" style="min-height:60px;" class="staffData" cd="{{ $row['cd'] }}" name="{{ e($staffName) }}">
		<a href="{{ route('admin.reservas.timetable.staff', ['shop' => $shopCd, 'staff' => $row['cd'], 'd' => $d]) }}" style="text-decoration: underline;">{{ $staffNameShort }}</a>

			<br /><a class="popWindowAnchor" onClick="wopen('{{ $row['cd'] }}', '{{ $day['key'] }}');" style="font-weight:normal; font-size:10px; text-decoration: underline;">[予約]</a>
			<a class="popWindowAnchor" onClick="wopen_config('{{ $row['cd'] }}', '{{ $slot['cd'] ?? '' }}', '{{ $day['key'] }}');" style="font-weight:normal; font-size:10px; text-decoration: underline;">[設定]</a><br />

		</div>
	</th>
@if ($slot)
@php
    if ($day['valid']) {
        $thStyle = match ((string) $slot['status_flg']) {
            '1' => ['class' => null, 'style' => 'background:#778899; text-align: center;'],
            '2' => ['class' => null, 'style' => 'background:#998888; text-align: center;'],
            default => ['class' => $bgStyle, 'style' => 'text-align: center;'],
        };
    } else {
        $thStyle = ['class' => $bgStyle, 'style' => null];
    }
@endphp
	<th @if (! is_null($thStyle['class'])) class="{{ $thStyle['class'] }}" @endif @if ($thStyle['style']) style="{{ $thStyle['style'] }}" @endif>

@if ($day['valid'])
	<select name="status_flg_{{ $row['cd'] }}" id="status_flg_{{ $row['cd'] }}_{{ $day['key'] }}">
@foreach ($statusFlgOptions as $flg => $statusLabel)
@php $flgVal = $flg == 0 ? '' : (string) $flg; @endphp
<option value="{{ $flgVal }}" @selected($flgVal === (string) $slot['status_flg'])>{{ $statusLabel }}</option>
@endforeach
	</select>
	<input type="button" name="flgbtn_{{ $row['cd'] }}" value="状況変更" class="btn yoyakuStatusChenge" onclick="setStatus('{{ $row['cd'] }}', '{{ $day['key'] }}')" />
@else
	<span style="color:#FF3333;font-weight:bold;">終了しました</span>
@endif
	</th>
	<td class="{{ $bgStyle }}"@if ($startsAtHalfHour) style="background-position: -30px 0;"@endif>

	<div style="position:relative;border:none;min-height:60px;">
@foreach ($slot['breaks'] as $break)
		<div style="position:absolute; width:{{ $break['width'] }}px; height:100%; left:{{ $break['left'] }}px; top:0px; background: #777777" title="休憩"></div>
@endforeach

		@if ($slot['noWorkLeft'] > 0)<div style="position:absolute; width:{{ $slot['noWorkLeft'] }}px; height:100%; left:1px; top:0px; background: #777777"></div>@endif
		@if ($slot['noWorkRight'] > 0)<div style="position:absolute; width:{{ $slot['noWorkRight'] }}px; height:100%; right:0px; top:0px; background: #777777"></div>@endif

		<div class="timeZone"></div>
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
<th class="{{ $bgStyle }}"></th><td class="yoyaku_none{{ $bgStyle }}">予約受付が開始されていません。</td>
@endif

	</tr>
@endforeach

	<tr><td colspan="3" style="height:5px; background:white; border:0;"></td></tr>
@endforeach

</table>
@endif

<!-- / .yoyakuField --></div>

<div id="popFormWrap" class="wordBreakAll" style="position:absolute;padding:2px;">
</div>

<!-- / .contents --></div>
@endif

{{-- Fuera de #container en el legacy --}}
<div id="popWindow" class="wordBreakAll popWindowFix @if (! $isIpad) popWindowOff @endif" @if ($isIpad) style="display:none" @endif>

@if ($isIpad)
	<div id="iPadContext" style="display:none;" ></div>
	<div id="iPadCustomer"><!-- iPad用のdiv-->
		<div id="iPadCustomerInner">
			<div id="" class="closePopWindowButtonRight" ontouchend="closepopWindow()" style="position:absolute;top:10px;right:10px;">×</div>
			<div id="iPadOptionContainer" style="display:none;"></div>
@endif

	<!-- basicContainerで囲った部分はiPadの処理を入れる前からあったもの -->
	<div id="basicContainer">
		<div class="customerInfo"></div>

		<table style="width: 100%; margin-bottom: 10px; border: 1px solid #CCCCCC;">
			<tr style="border-bottom: 1px solid #CCCCCC;">
				<th align="left" style="background:#EEEEEE;font-weight: normal; padding: 0 3px;">目安時間</th>
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
		{{-- índice = número correlativo de los visibles (2013.05.16【大野】) --}}
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
	</div> <!-- 基本情報 -->

@if ($isIpad)
		</div>
	</div><!-- iPad用-->
@endif
</div>

@if ($realtimePrintEnabled && \Illuminate\Support\Facades\Route::has('admin.reservas.printer'))
<div style="display: none;">
<iframe src="{{ route('admin.reservas.printer') }}" frameborder="0" width="100" height="100"></iframe>
</div>
@endif
<input type="hidden" id="open_yoyaku_cd" name="open_yoyaku_cd" value="{{ $openYoyakuCd }}">
<input type="hidden" id="open_shop_cd" name="open_shop_cd" value="{{ $openShopCd }}">
<input type="hidden" id="open_d" name="open_d" value="{{ $openD }}">
@endsection
