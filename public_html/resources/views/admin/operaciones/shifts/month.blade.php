{{-- Legacy: system/category5/schedule/staff_shift_month.php (月別シフト管理). Mismo HTML y JS que el diario
     (add.shift.js con data-day por fila). Cambios: solo los días que tiene el mes; $1113 = jQuery del layout; @json.
     Variables: $day (día 1), $month, $d, $shops, $shopCd, $staff, $staffCd, $member, $slots, $hours, $shifts, $kinds, $months --}}
@extends('layouts.legacy')

@section('menu', 'operaciones')

@section('title', '月別シフト管理 - '.($siteName ?? ''))

@php
    $lastSlot = array_key_last($slots);
    $timeStr = array_map(fn ($s) => $s['h'].':'.$s['m'], $slots);
    $jsData = (object) array_map(fn ($s) => ['cd' => (string) $s['staff_cd'], 'start' => $s['start'], 'end' => $s['end'],
        'kubun' => urlencode($s['kubun']), 'komment' => urlencode($s['komment'])], $shifts);
    $ym = $day->format('Y').'年'.$day->format('m').'月';
@endphp

@section('css')
<link rel="stylesheet" href="/system/category5/schedule/css/bootstrap.min.css" />
<link rel="stylesheet" href="/system/category5/schedule/css/time_table.css" />
<link rel="stylesheet" href="/system/category5/schedule/css/bootstrap-modal-bs3patch.css" />
<link rel="stylesheet" href="/system/category5/schedule/css/bootstrap-modal.css" />
<link rel="stylesheet" href="/system/category5/schedule/css/add.style.css" />
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
<script type="text/javascript">
$(document).ready(function() {
	$(".ac3").accordion('option', 'active', 0);
});
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■月別シフト管理</p>
<div class="tableFrame">

<div style=" padding: 30px 0; background: #f3f5f8;">
<form action="{{ route('admin.operaciones.shifts.month') }}" method="get" name="form1">
	<input name="d" type="hidden" class="d" value="{{ $d }}" />
	<div class="row">
		<div id="calendarControl" class="clearfix col-md-4" style="display: block; text-align: center;">
		<div id="dateDispArea">
		<div id="nenDisp"><span>{{ $day->format('Y') }}</span>年 <span>{{ $day->format('m') }}</span>月</div>
		<div class="shop_select left">
@can('nucleo.master')
				店舗選択 : <select name="shop_cd" class="shopSelect">
@foreach ($shops as $cd => $name)
				<option value="{{ $cd }}" @selected($cd === $shopCd) >{{ $name }}</option>
@endforeach
				</select>
@else
				{{ $shops->first() }}
				<input type="hidden" name="shop_cd" id="shop_cd" value="{{ $shopCd }}" />
@endcan
			<br >
			スタッフ選択 : <select name="staff_cd">
				<option value="">選択してください</option>
@foreach ($staff as $row)
				<option value="{{ $row->cd }}" @selected($row->cd === $staffCd)>{!! $row->name !!}</option>
@endforeach
			</select>
		</div>
		</div>
		<!-- /#calendarControl --></div>
		<div class="col-md-8">
@foreach ($months as $option)
@php($label = $option->format('Y').'年'.$option->format('m').'月')
					<button class="btn {{ $label === $ym ? 'btn-warning' : 'btn-default' }} setTimeYM" data-value="{{ $option->format('Ym01') }}" >{{ $label }}</button>
@endforeach
		</div>
	</div>
	<div class="row">
		<div class="col-md-12 center">
			<button type="button" onclick="document.form1.submit()" class="btn btn-primary" style="width:200px">選択条件のシフトを表示</button>
		</div>
	</div>
</form>
</div>

<form method="post" name="sche_body" id="sche_body" class="form" action="{{ route('admin.operaciones.shifts.month.save') }}">
@csrf
<input type="hidden" name="year" value="{{ $day->format('Y') }}">
<input type="hidden" name="month" value="{{ $day->format('m') }}">
<input type="hidden" name="day" value="{{ $day->format('d') }}">
<input type="hidden" name="shop_cd" id="shop_cd" value="{{ $shopCd }}" />
<input type="hidden" name="staff_cd" id="staff_cd" value="{{ $staffCd ?: '' }}" />
<input name="d" type="hidden" class="d" value="{{ $d }}" />
@foreach ($shifts as $key => $shift)
<input type="hidden" name="sch[{{ $key }}]" class="sch{{ $key }}" value="{{ $shift['staff_cd'] }}_{{ $shift['start_unix'] }}_{{ $shift['end_unix'] }}">
@endforeach
	<div class="row">
		<div class="col-md-12">
			<h1 style="width: 1000px;" class="center-block">{!! $member?->name !!} {{ $ym }} シフト一覧</h1>
			<div style="padding:0;height:420px;overflow:scroll;position:relative;" class="center-block">
@if ($staff->isEmpty())
				<div class="noData">
					対象のスタッフ情報がありません。
				</div>
@else
				<table class="table table-bordered table-striped timeTableBase">
@if ($member)
@foreach (range(1, $month->daysInMonth()) as $dayNo)
@php($key = $dayNo - 1)
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
									<th colspan="2">
										<a href="#modal{{ $key }}" role="button" class="btn btn-default" data-toggle="modal" style="width: 100%;">
											{{ $day->format('Y') }} / {{ $day->format('m') }} / {{ $dayNo }}
										</a>
										<div id="modal{{ $key }}" class="modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
											<div class="modal-header">
												<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
											<h3 id="myModalLabel">スケジュール設定</h3>
											</div>
											<div class="modal-body">
												<span>スタッフ名: {!! $member->name !!}</span>
												<br><br>
												<span>シフト開始時間</span>
												<select name="sk" class="sk_select">
@foreach ($timeStr as $time)
														<option value="{{ $time }}">{{ $time }}</option>
@endforeach
												</select>
												<br><br>
												<span>シフト終了時間</span>
												<select name="ek" class="ek_select">
@foreach ($timeStr as $time)
														<option value="{{ $time }}">{{ $time }}</option>
@endforeach
												</select>
@if ($kinds->isNotEmpty())
												<br><br>
												<span>役職 : </span>
												<select name="kubun" class="kubun_select">
@foreach ($kinds as $kind)
														<option value="{{ $kind->cd }}">{{ $kind->name }}</option>
@endforeach
												</select>
@endif
												<br><br>
												<span>メモ : </span>
												<div class="form-group">
													<textarea name="komment" class="form-control komment_textarea"></textarea>
												</div>
											</div>
											<div class="modal-footer">
												<button class="btn btn-default" data-dismiss="modal" aria-hidden="true">閉じる</button>
												<button class="btn btn-primary setShift" data-dismiss="modal" data-staff_cd="{{ $key }}">設定</button>
											</div>
										</div>
									</th>
								</tr>
							</table>
						</th>
						<td class="timeTableWrapper staffCD" id="timeTable{{ $key }}" data-staff_cd="{{ $key }}" data-day="{{ $dayNo }}" style="border:2px solid #CCCCCC;border-collapse:collapse;padding:0;">
							<div class="timeTable" style="position:relative;background-color:#E2E6E8;min-height:60px;">
							</div>
						</td>
					</tr>
@endforeach
@endif
				</table>
@endif
			</div>
@if ($staff->isNotEmpty())
			<div class="submitWrap">
				<input type="hidden" name="mode" value="update">
				<button type="button" onclick="document.sche_body.submit()" class="btn btn-primary">更新</button>
			</div>
@endif
			<p id="results" style="margin-top:30px;width:1000px;height:250px;border:1px solid red;overflow-y:auto;display:none;" class="center-block"></p>
		</div>
	</div>
</form>
@include('admin.operaciones.shifts.partials.shift-modal', ['id' => 'modalEdit', 'staffName' => null, 'button' => 'setShiftEdit', 'staffCd' => null])
</div>

<!-- / .contents --></div>
@endsection
