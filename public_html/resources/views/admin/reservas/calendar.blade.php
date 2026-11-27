{{--
    Legacy: system/category9/yoyaku/yoyaku_calendar.php (時間選択: franjas del día del empleado con su ocupación).
    Variables:
      $input        array    petición limpia con form2Db (shop, staff, menu[], option_menu[], people…)
      $getData      string   consulta que lleva los datos del flujo (`$getData` del legacy)
      $kijunTime    int      día mostrado (epoch)
      $preKijun     int      día anterior (epoch)
      $nextKijun    int      día siguiente (epoch)
      $dateTitle    string   'Y年m月d日'
      $weekday      string   día de la semana (日…土)
      $shopName     ?string  getShopNameFromCd()
      $staffName    ?string  tb_staff.name
      $menus        Collection  menús elegidos del empleado (name, shoyou_time)
      $ymd          string   'Ymd' del día mostrado
      $day          array    AvailabilityService::day(): slot (?ReservationSlot), rows (n, time, hhmm, plus, status, people, error)
      $slotEditUrl  string   受付日設定 (admin.reservas.slots.edit) con cd= si hay franja (新規設定を行う si no)
--}}
@extends('layouts.legacy-popup')

@section('title', 'トップ -総合業務管理システム-')

@section('css')
<link href="{{ asset('legacy/system/category9/yoyaku/css/time_table.css') }}" rel="stylesheet" type="text/css" media="all" />
<style type="text/css">

#container {
	min-width: 500px;
}

.scheduleTable {

}

.scheduleTable td {
	height: 35px;
}

.scheChildTableTH td {
	padding: 0 5px;
	border: 1px solid #000000;
	text-align: right;
	font-weight: bold;
}

.tableHead {
	background: #EEEEEE;
}

.scheChildTableTD td {
	padding: 0 5px;
	border: 1px solid #000000;
	border-left: 0;
}

.scheBtn {
	overflow: hidden;
	width: 300px;
	height: 32px;
	text-align: left;
}



.yoyakuObj {
	margin-right: 5px;
	text-decoration: underline;
}

#popWindow {
	display: none;
	position: absolute;
	top: 0;
	left: 0;
	padding: 10px;
	background: #FFFFFF;
	border: 1px solid #999999;
}

.choiceMenu {
	margin: 10px auto;
}

.choiceMenu th {
	padding: 5px 10px;
	border: 1px solid #333333;
	background: #EEF3FF
}

.choiceMenu td {
	padding: 1px 5px;
	border: 1px solid #333333;
}

</style>
@endsection

@section('js')
<script type="text/javascript">

function yoyakuIni() {
	$(".yoyakuObj").mouseover(function(e){
		$("#popWindow").css("display","block");
		$("#popWindow").css("left",(e.pageX + 2) + "px");
		$("#popWindow").css("top",(e.pageY + 2) + "px");

		$("#ninzu").html( "現在の受付人数：" + $(this).attr("ninzu") + "人" );

	});

	$(".yoyakuObj").mouseout(function(e){
		$("#popWindow").css("display","none");
	});
}

function displayAlert() {
}

function displayAlert2() {
}


$(document).ready(function() {

	//閉じるボタンの挙動
	$(".popCloseBtn").click(function(){

		if ( window == window.parent ) {
			window.close();
		} else {
			window.parent.popUpWinClose("");
		}

	});

});

</script>
@endsection

@section('content')
@php
    $slot = $day['slot'];
    $customerUrl = fn (array $row) => route('admin.reservas.customer-select') . '?' . $getData . '&kijun_time=' . $kijunTime . ($row['plus'] ? '&plus=1' : '') . '&yoyaku_date=' . $ymd . $row['hhmm'];
@endphp
<div class="contents">


<div style="margin-bottom: 5px;">
<a href="{{ route('admin.reservas.create') }}?{{ $getData }}{{ ($input['kijun_time'] ?? '') !== '' ? '&kijun_time=' . $input['kijun_time'] : '' }}">&lt;&lt;メニュー選択に戻る</a>
</div>


<div style="text-align: center;">
<a class="tsukiNaviBtn" style="float: left;" href="{{ route('admin.reservas.calendar-select') }}?{{ $getData }}&kijun_time={{ $preKijun }}">&lt;&lt;前へ</a>
<a class="tsukiNaviBtn" style="float: right;" href="{{ route('admin.reservas.calendar-select') }}?{{ $getData }}&kijun_time={{ $nextKijun }}">次へ&gt;&gt;</a>
<span style="color: #476A87; font-size: 30px; font-weight: bold;">{{ $dateTitle }}</span>
<span style="color: #476A87; font-size:25px; font-weight: bold;">({{ $weekday }})</span>
</div>




<table class="choiceMenu wordBreakAll">
	<tr>
	<th>お店 / 指名</th>
	<td>{{ $shopName }} / {{ $staffName }}</td>
	</tr>

	<tr>
	<th>選択メニュー</th>
	<td>@foreach ($menus as $menu)【{{ $menu->name }}<span style="font-size:90%;">&nbsp;({{ $menu->shoyou_time }}分)</span>】<br />@endforeach</td>
	</tr>

	<tr>
	<th>受付人数</th>
	<td>{{ $input['people'] ?? '' }}人</td>
	</tr>
</table>




<table class="scheduleTable">
	<tr>
	<td>
		<table class="scheChildTableTH" style="width:100%;">

		<tr>
		<td class="tableHead"></td>
		</tr>

		<tr>
		<td class="tableHead" style="height:5em;">設定</td>
		</tr>
@foreach ($day['rows'] as $row)
		<tr>
		<td class="tableHead">
		{{ $row['time'] }}
		</td>
		</tr>
@endforeach
		</table>
	</td>
	<td>
		<table class="scheChildTableTD" style="width:100%;">
			<tr>
			<td>
			<div style="text-align: center; background: #FFEEEE; border: 1px solid #999999;">
@if (\Illuminate\Support\Facades\Route::has('admin.reservas.slots.edit'))
			<a href="{{ $slotEditUrl }}">{{ $slot ? '設定内容・予約状況閲覧' : '新規設定を行う' }}</a>
@endif
			</div>
			</td>
			</tr>

			<tr>
			<td style="height:5em;">
@if ($slot)
			設定最大人数　<span style="font-weight: bold;">{{ $slot->people }}人</span><br />
			設定時間　<span style="font-weight: bold;">{{ \App\Services\Reservations\ReservationSlotService::clock($slot->start_time) }}</span> ～
			<span style="font-weight: bold;">{{ \App\Services\Reservations\ReservationSlotService::clock($slot->end_time) }}</span>
@endif
			</td>
			</tr>

@foreach ($day['rows'] as $row)
			<tr>
			<td>
			<div class="scheBtn">
@if ($slot)
@if ($row['status'] === 'closed')
-
@else
@if ($row['status'] === 'break')
<a href="{{ $customerUrl($row) }}" onclick="displayAlert2();" >
<img class="yoyakuObj" ninzu="{{ $row['people'] }}" src="{{ asset('legacy/system/img/batsu.gif') }}" alt="{{ $row['people'] }}" style="float:left;" /></a>
<span style="color: #336699;">[休憩・外出]</span>
@elseif ($row['status'] === 'setsubi')
<a href="{{ $customerUrl($row) }}" onclick="displayAlert();" >
<img class="yoyakuObj" ninzu="{{ $row['people'] }}" src="{{ asset('legacy/system/img/batsu.gif') }}" alt="{{ $row['people'] }}" style="float:left;" /></a>
<span style="color: #336699;">[{{ $row['error'] }}]</span>
@elseif ($row['status'] === 'full')
<a href="{{ $customerUrl($row) }}" onclick="displayAlert();" >
<img class="yoyakuObj" ninzu="{{ $row['people'] }}" src="{{ asset('legacy/system/img/batsu.gif') }}" alt="{{ $row['people'] }}" style="float:left;" /></a>
<span style="color: #336699;">[予約不可]</span>
@else
<a href="{{ $customerUrl($row) }}">
<img class="yoyakuObj" ninzu="{{ $row['people'] }}" src="{{ asset('legacy/system/img/maru.gif') }}" alt="{{ $row['people'] }}" style="float:left;" /></a>
<span style="color: #EE0000;">[予約可]　&nbsp;</span>
@endif
@if ($row['status'] !== 'break')予約人数：<span style="font-size: 18px; font-weight: bold;">{{ $row['people'] }}</span>人<small>/最大{{ $slot->people }}人中</small>@endif
@endif
@endif
			</div>
			</td>
			</tr>
@endforeach
		</table>
	</td>


	</tr>

</table>

<div class="btnBox">
<input type="button" value="閉じる" class="popCloseBtn" />
</div>



<!-- / .contents --></div>


<div id="popWindow">

<div id="ninzu"></div>
</div>
@endsection
