{{-- Legacy: system/category5/schedule/staff_shift.php (日別シフト管理). Mismo HTML, CSS (bootstrap del módulo) y JS
     (add.shift.js, modales de bootstrap, html2canvas para 印刷). Cambios: el jQuery del módulo ya es el 3.7.1 del layout,
     así que `$1113` apunta al mismo; los datos del JS se emiten con @json; se quitó el enlace roto a time_table.php.
     Variables: $day (CarbonImmutable), $d (Ymd), $shops, $shopCd, $staff, $slots, $hours, $shifts, $kinds, $calendars, $print --}}
@extends('layouts.legacy')

@section('menu', 'operaciones')

@section('title', '日別シフト管理 - '.($siteName ?? ''))

@php
    $w = ['日', '月', '火', '水', '木', '金', '土'];
    $lastSlot = array_key_last($slots);
    $timeStr = array_map(fn ($s) => $s['h'].':'.$s['m'], $slots);
    $jsData = (object) array_map(fn ($s) => ['cd' => (string) $s['staff_cd'], 'start' => $s['start'], 'end' => $s['end'],
        'kubun' => urlencode($s['kubun']), 'komment' => urlencode($s['komment'])], $shifts);
@endphp

@section('css')
<link rel="stylesheet" href="/system/category5/schedule/css/bootstrap.min.css" />
<link rel="stylesheet" href="/system/category5/schedule/css/time_table.css" />
<link rel="stylesheet" href="/system/category5/schedule/css/bootstrap-modal-bs3patch.css" />
<link rel="stylesheet" href="/system/category5/schedule/css/bootstrap-modal.css" />
<link rel="stylesheet" href="/system/category5/schedule/css/add.style.css" />
@if ($print)
<style>
body {
	-webkit-print-color-adjust: exact;
}
.timeTable{
	background-color: #E2E6E8 !important;
}
.timeTableBase > tbody > tr > th:first-child,
.timeTableBase > tbody > tr > th:nth-child(2),
.dataTh th {
	background-color: #F9F9F9 !important;
	border:none;
}
.setTh table,
.setTh table td,
.setTh table th{
	border:none;
}
#header,
.h2Title,
.leftNavi,
.submitWrap,
#calendarControlWrap
{
	display:none;
}
.main2{
	margin-left: 0px;
}
#printArea{
	display:block;
	height: auto !important;
}
.reserve{
	background-color: #B3DA8E !important;
}
</style>
@endif
@endsection

@section('js')
<script type="text/javascript" src="/system/category5/schedule/js/bootstrap.min.js"></script>
<script type="text/javascript" src="/system/category5/schedule/js/bootstrap-modalmanager.js"></script>
<script type="text/javascript" src="/system/category5/schedule/js/bootstrap-modal.js"></script>
<script>
function inverseObject (obj, keyIsNumber) {
  return Object.keys(obj).reduceRight(function (ret, k) {
    return (ret[obj[k]] = keyIsNumber ? parseInt(k, 10) : k, ret);
  }, {});
}
var $1113 = jQuery;
var data = @json($jsData);
var INTERVAL = 34;
var test;
@if ($slots)
var unixData = @json(array_map(fn ($s) => (string) $s['u'], $slots));
var houeData = @json(array_column($slots, 'h'));
var minuData = @json(array_column($slots, 'm'));
var staffKindAry = @json((object) $kinds->pluck('name', 'cd')->all());
var staffKindColorAry = @json((object) $kinds->pluck('color', 'cd')->all());
var timeStrData = inverseObject( @json($timeStr) );
@endif
var shopWorkTime = [ "{{ sprintf('%02d', $hours['start'][0]) }}", "{{ sprintf('%02d', $hours['start'][1]) }}" ];
</script>
<script type="text/javascript" src="/system/category5/schedule/js/add.shift.js"></script>
<script src="/system/category5/schedule/js/html2canvas.js"></script>
<script>
function screenshot( selector) {
	var element = $(selector)[0];
	html2canvas(element, { onrendered: function(canvas) {
		var imgData = canvas.toDataURL();
		$('#screen_image')[0].src = imgData;
		$("#printArea").hide();
		window.print();
	}});
}
$(document).ready(function() {
	$(".ac3").accordion('option', 'active', 0);
});
@if ($print)
$(document).ready(function() {
	screenshot("#printArea");
});
function printShift(){
	screenshot("#printArea");
	return false;
}
@else
function printShift(){
	window.open('{{ route('admin.operaciones.shifts.day', ['shop_cd' => $shopCd, 'd' => $d]) }}&print', 'printWindow', 'width={{ 100 + 140 + 34 * count($slots) }}, height=300, menubar=no, toolbar=no, scrollbars=yes');
	return false;
}
@endif
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■日別シフト管理</p>
<div class="tableFrame">

<div style=" padding: 30px 0; background: #f3f5f8;" id="calendarControlWrap">
<div id="calendarControl" class="clearfix" style="display: block; text-align: center;">
<div id="dateDispArea">
<div id="nenDisp"><span>{{ $day->year }}</span>年</div>
<div id="dateDisp">{{ $day->format('m') }}/{{ $day->format('d') }}<span>({{ $w[$day->dayOfWeek] }})</span></div>
<div class="shop_select">
<form action="{{ route('admin.operaciones.shifts.day') }}" method="get" name="form1">
	<input type="hidden" name="d" value="{{ $d }}">
@can('nucleo.master')
		店舗選択 : <select name="shop_cd">
@foreach ($shops as $cd => $name)
		<option value="{{ $cd }}" @selected($cd === $shopCd) >{{ $name }}</option>
@endforeach
		</select>
@else
		{{ $shops->first() }}
		<input type="hidden" name="shop_cd" id="shop_cd" value="{{ $shopCd }}" />
@endcan
	<button type="button" onclick="document.form1.submit()" class="btn btn-primary">店舗選択</button>
</form>
</div>
</div>
@foreach ($calendars as $month)
@include('admin.operaciones.shifts.partials.small-calendar', ['month' => $month, 'selected' => $day, 'shopCd' => $shopCd])
@endforeach
<!-- /#calendarControl --></div>
</div>

<form method="post" name="sche_body" id="sche_body" class="form" action="{{ route('admin.operaciones.shifts.save') }}">
@csrf
<input type="hidden" name="year" value="{{ $day->year }}">
<input type="hidden" name="month" value="{{ $day->format('m') }}">
<input type="hidden" name="day" value="{{ $day->format('d') }}">
<input type="hidden" name="d" value="{{ $d }}">
<input type="hidden" name="shop_cd" id="shop_cd" value="{{ $shopCd }}" />
@foreach ($shifts as $key => $shift)
<input type="hidden" name="sch[{{ $key }}]" class="sch{{ $key }}" value="{{ $shift['staff_cd'] }}_{{ $shift['start_unix'] }}_{{ $shift['end_unix'] }}">
@endforeach
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="col-md-6">
					<h1 class="center-block">
						{{ $day->year }}年{{ $day->format('m') }}月{{ $day->format('d') }}日 シフト一覧
					</h1>
				</div>
				<div class="col-md-6 text-right">
@unless ($print)
						<a href="#" onClick="printShift(); return false;" class="btn btn-default" style="margin-top:10px" >印刷</a>
@endunless
				</div>
			</div>
			<div id="printArea" style="padding:0;height:420px;overflow:scroll;position:relative;" class="center-block">
			<div id="printAreaInner">
@if ($staff->isEmpty())
				<div class="noData">
					対象のスタッフ情報がありません。
				</div>
@else
				<table class="table table-bordered table-striped timeTableBase">
@foreach ($staff as $member)
					<tr style="height: 24px;">
						<th style="height: 25px;background-color: #F9F9F9;">&nbsp;</th>
						<th class="setTh staffData">
							<table>
								<tr class="dataTh">
@foreach ($slots as $k => $slot)
@if ($k !== $lastSlot)
											<th style="border-right: 1px solid #CCC; font-size: 11px;padding:0 3px;width:34px;"><div style="width:27px">{{ $slot['h'] }}:{{ $slot['m'] }}</div></th>
@endif
@endforeach
								</tr>
							</table>
						</th>
					</tr>
					<tr>
						<th style="height: 64px;background:#FFF">
							<table style="width: 100%;">
								<tr>
									<th colspan="2" style="">
										{{-- tb_staff.name se guarda con get_form2 (ya escapado) --}}
										<a href="#modal{{ $member->cd }}" role="button" class="btn btn-default" data-toggle="modal" style="width: 100%;">{!! $member->name !!}</a>
										@include('admin.operaciones.shifts.partials.shift-modal', ['id' => 'modal'.$member->cd, 'staffName' => $member->name, 'button' => 'setShift', 'staffCd' => $member->cd])
									</th>
								</tr>
							</table>
						</th>
						<td class="timeTableWrapper staffCD" id="timeTable{{ $member->cd }}" data-staff_cd="{{ $member->cd }}" style="border:2px solid #CCCCCC;border-collapse:collapse;padding:0;">
							<div class="timeTable" style="position:relative;background-color:#E2E6E8;min-height:60px;">
							</div>
						</td>
					</tr>
@endforeach
				</table>
				@include('admin.operaciones.shifts.partials.shift-modal', ['id' => 'modalEdit', 'staffName' => null, 'button' => 'setShiftEdit', 'staffCd' => null])
@endif
			</div>
			</div>
			<div class="submitWrap">
				<input type="hidden" name="mode" value="update">
				<button type="button" onclick="document.sche_body.submit()" class="btn btn-primary">更新</button>
			</div>
			<p id="results" style="margin-top:30px;width:1000px;height:250px;border:1px solid red;overflow-y:auto;display:none;" class="center-block"></p>
		</div>
	</div>
</form>
</div>
<div id="output_screen">
  <img id="screen_image" src="" style="width:100%">
</div>

<!-- / .contents --></div>
@endsection
