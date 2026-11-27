{{-- Legacy: system/category5/working/working_chouhyou.php (帳票管理; <title> 日別シフト管理 como el legacy).
     Mismo HTML con el bootstrap y el datepicker del módulo schedule. Cambios: el jQuery es el 3.7.1 del layout
     (`$1113` apunta a él, sin jquery-1.3.2); el master puede elegir cualquier tienda (el legacy siempre volvía a la
     primera); solo se ven los empleados de la tienda elegida.
     Variables: $ym ('Y/m'), $shops, $shop, $staff, $staffCd, $days [día => day, w, color, attendance, return_office],
       $totals (minutos) --}}
@extends('layouts.legacy')

@section('menu', 'operaciones')

@section('title', '日別シフト管理 - '.($siteName ?? ''))

@php($returnDay = \App\Services\Operaciones\Working\WorkingReport::RETURN_DAY)
@php($hm = fn ($m) => \App\Services\Operaciones\Working\WorkingReport::hourMin($m))

@section('css')
{{-- スタッフスケジュール管理用 --}}
<link rel="stylesheet" href="/system/category5/schedule/css/bootstrap.min.css" />
<link rel="stylesheet" href="/system/category5/schedule/css/bootstrap-modal-bs3patch.css" />
<link rel="stylesheet" href="/system/category5/schedule/css/bootstrap-modal.css" />
<link rel="stylesheet" href="/system/category5/schedule/css/datepicker.css" />
@endsection

@section('js')
<script type="text/javascript" src="/system/category5/schedule/js/bootstrap.min.js"></script>
<script type="text/javascript" src="/system/category5/schedule/js/bootstrap-modalmanager.js"></script>
<script type="text/javascript" src="/system/category5/schedule/js/bootstrap-modal.js"></script>
<script type="text/javascript" src="/system/category5/schedule/js/bootstrap-datepicker.js"></script>
<script type="text/javascript" src="/system/category5/schedule/js/bootstrap-datepicker.ja.min.js"></script>
<script>
var $1113 = jQuery;
</script>
<script type="text/javascript">
$1113(function () {
	$1113('#datetimepicker1').datepicker({
		format: 'yyyy/mm',
		language: 'ja',
		autoclose: true,
		minViewMode: 'months'
	});
});

$(document).ready(function() {
	$(".ac2").accordion('option', 'active', 0);
});
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■帳票管理</p>

<form action="{{ route('admin.operaciones.working.report') }}" style="margin-bottom: 20px; overflow: hidden;">
	<div class="form-group" style=" overflow: hidden;">

		<label for="staffName" class="col-xs-2 text-center margin">日付: </label>
		<div class="col-xs-3">
			<input class="form-control date" id="datetimepicker1" name="ym" value="{{ $ym }}" />
		</div>

		<label for="staffName" class="col-xs-3 text-center">店舗名: </label>
		<div class="col-xs-3">

@can('nucleo.master')
				<select id="shop_cd" class="form-control" name="shop_cd">
					<option value="">未選択</option>
@foreach ($shops as $s)
<option value="{{ $s->cd }}" @selected($s->cd === $shop?->cd) >{{ $s->kaishamei }}</option>
@endforeach
				</select>
@else
				{{ $shop?->kaishamei }}
				<input type="hidden" name="shop_cd" id="shop_cd" value="{{ $shop?->cd }}" />
@endcan

		</div>

	</div>

	<div class="form-group" style=" overflow: hidden;">

		<label for="staffName" class="col-xs-2 text-center">スタッフ名: </label>
		<div class="col-xs-3">
			<select id="staff_cd" class="form-control" name="staff_cd">
				<option value="">未選択</option>
@foreach ($staff as $member)
						<option value="{{ $member->cd }}" @selected($member->cd === $staffCd)>{{ $member->name }}</option>
@endforeach
			</select>
		</div>

		<div class="col-xs-6 text-right">
			<input type="submit" class="btn btn-primary" value="確認する">
		</div>

	</div>

</form>

<hr>

<div class="row">
@foreach ([0, 1] as $col)
		<div class="col-xs-6">
			<table class="table table-striped table-bordered table-hover text-center">
				<tr>
					<th style="width:50px" class="text-center">日</th>
					<th style="width:50px" class="text-center">曜日</th>
					<th class="text-center">出社</th>
					<th class="text-center">退社</th>
				</tr>
@foreach (range(1, $returnDay) as $v)
@php($sDate = $days[$v + $returnDay * $col] ?? null)
@if ($sDate === null)
						<tr>
							<td colspan="4">&nbsp;</td>
						</tr>
@else
						<tr>
							<td>{{ $sDate['day'] }}</td>
							<td style="color:{{ $sDate['color'] }}">{{ $sDate['w'] }}</td>
							<td>{{ $sDate['attendance'] }}</td>
							<td>{{ $sDate['return_office'] }}</td>
						</tr>
@endif
@endforeach
			</table>
		</div>
@endforeach
</div>

<hr>

<div class="row">
	<div class="col-xs-6">
		<table class="table table-bordered text-center">
			<tr>
				<th class="text-center active">就業時間合計</th>
				<th class="text-center active">時間外合計</th>
				<th class="text-center active">休出勤合計</th>
			</tr>
			<tr>
				<td>{{ $hm($totals['working']) }}</td>
				<td>{{ $hm($totals['overtime']) }}</td>
				<td>{{ $hm($totals['holiday']) }}</td>
			</tr>
		</table>
	</div>
	<div class="col-xs-6">
		<table class="table table-bordered text-center">
			<tr>
				<th class="text-center active" style="width:200px">出勤日数</th>
				<td>{{ number_format($totals['attendances']) }}</td>
			</tr>
			<tr>
				<th class="text-center active">欠勤時間</th>
				<td>{{ $hm($totals['absenteeism']) }}</td>
			</tr>
			<tr>
				<th class="text-center active">有給利用日数</th>
				<td>{{ number_format($totals['paidUseDays']) }}</td>
			</tr>
		</table>
	</div>
</div>

<!-- / .contents --></div>
@endsection
