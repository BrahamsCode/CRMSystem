{{-- Legacy: system/category5/working/work_list2.php (勤怠一覧, variante: corrige por AJAX con change_syukkin.php).
     Cambios: la recarga espera a que termine el AJAX (el legacy navegaba sin esperar); 戻る es un GET al calendario;
     la salida sin redondeo calculado se pinta "()" (el legacy pintaba la hora de epoch 0).
     Variables: $rows (WorkTimeService::dayRows), $shop, $day, $dd, $shopCd, $kensu, $page, $total, $from, $pager --}}
@extends('layouts.legacy')

@section('menu', 'operaciones')

@section('title', '勤怠一覧 - '.($siteName ?? ''))

@php
    $hm = fn ($t) => $t?->format('H:i');
    $selfUrl = route('admin.operaciones.working.day2', ['page' => $page, 'shop_cd' => $shopCd, 'dd' => $dd, 'kensu' => $kensu]);
    $kensuUrl = route('admin.operaciones.working.day2', ['shop_cd' => $shopCd, 'dd' => $dd]);
    $onDecide = '			$("#dummy").load('.json_encode(route('legacy.operaciones.working-change')).',{
				staff_cd: staff_cd,
				nengappi: nengappi,
				syukkin_hour: syukkin_hour,
				syukkin_min: syukkin_min,
				taikin_hour: taikin_hour,
				taikin_min: taikin_min,
				syukkin_flg: syukkin_flg,
				syukkin_kubun: syukkin_kubun
			}, function () {
				now=new Date();now=parseInt(now/1000);
				location.href = tobisaki+"&t="+now;
			});';
@endphp

@section('css')
<style type="text/css"><!--

.henkouArea {
	position: relative;
	text-align: center;

}

.henkouForm {
	background: #FFFFFF;
	border: 1px solid #336699!important;
	position:absolute;
	right: 0;
	bottom: 0;
}

.henkouForm legend {
	background: #FFFFFF;
	border: 1px solid #336699!important;
	font-size: small;
}

--></style>
@endsection

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac2").accordion('option', 'active', 0);
});
</script>

<script type="text/javascript">

var tobisaki = @json($selfUrl);
@include('admin.operaciones.working.partials.edit-js', ['selectClass' => ' class=\"selectBox\"', 'onDecide' => $onDecide])


</script>
@endsection

@section('content')
<div id="dummy"></div>

<div class="contents">
<p class="h2Title">■勤怠一覧</p>



<div class="form">
@if ($total > 0)

<p class="maxListLength">表示件数<select name="kensu_act" id="kensu_act" class="selectBox" onchange="location.href={{ json_encode($kensuUrl) }}+'&kensu='+this.value"><option value="{{ $kensu }}" selected>{{ $kensu }}</option>
<option value="" ></option>
@for ($n = 1; $n <= $maxPage; $n++)
<option value="{{ $n * 10 }}" {{ $kensu === $n * 10 ? 'selected' : '' }}>{{ $n * 10 }}</option>
@endfor
</select></p>


<p><span class="attention">{{ $day->year }}年{{ $day->month }}月{{ $day->day }}日<BR>{{ $from + 1 }}件～{{ $from + $kensu }}件目/{{ $total }}件中</span></p>

<table class="tableBox2" style="table-layout:fixed;">
  <tr>
    <th>スタッフID</th>
    <th>名前</th>
    <th>出勤時間</th>
    <th>退勤時間</th>
    <th>労働時間</th>
    <th>休憩回数</th>
    <th>出勤状況</th>
    <th>出勤区分</th>
    <th>&nbsp;</th>
  </tr>

@foreach ($rows as $i => $row)
	<tr{!! $i % 2 !== 0 ? ' class="tableBg"' : '' !!}>
    <td id="staffID{{ $i }}" number="{{ $row['staff']->cd }}">{{ $row['staff']->id }}</td>
    <td id="staffName{{ $i }}"><span title="{{ $row['staff']->name_kana }}">{{ $row['staff']->name }}</span></td>
@if ($row['record'])
    <td id="syukkin_time{{ $i }}">
	{{ $row['in'] ? $hm($row['in']).'('.$hm($row['start']).')' : '' }}
    </td>
	<input type="hidden" id="syukkin_hour{{ $i }}" value="{{ $row['in']?->format('H') }}">
	<input type="hidden" id="syukkin_min{{ $i }}" value="{{ $row['in']?->format('i') }}">

    <td id="taikin_time{{ $i }}">
	{{ $row['out'] ? $hm($row['out']).'('.$hm($row['end']).')' : '' }}
    </td>
	<input type="hidden" id="taikin_hour{{ $i }}" value="{{ $row['out']?->format('H') }}">
	<input type="hidden" id="taikin_min{{ $i }}" value="{{ $row['out']?->format('i') }}">

    <td>{{ $row['sum'] ? number_format($row['sum'], 2) : '' }}</td>

    <td id="gaisyutu_cnt{{ $i }}">{{ $row['breaks'] }}({{ $shop?->break_time }}分)</td>

    <td id="syukkin_flg{{ $i }}">{{ $row['flg'] }}</td>

    <td id="syukkin_kubun{{ $i }}">{{ $row['kubun'] }}</td>

    <td id="btnArea{{ $i }}"><img class="img_btn" src="/system/img/btn_revise_off.gif" width="40" height="20" alt="編集" onclick="creatForm({{ $i }})" /></td>
@else
    <td>&nbsp;</td>

    <td>&nbsp;</td>

    <td>&nbsp;</td>

    <td>&nbsp;</td>

    <td>&nbsp;</td>

    <td>&nbsp;</td>

    <td>&nbsp;</td>
@endif
  </tr>
@endforeach

</table>
@else
検索条件に合ったスタッフは、見つかりませんでした。再度検索してください
@endif

<form method="get" name="send_work_list" id="send_work_list" class="form mT10" action="{{ route('admin.operaciones.working.calendar') }}">
<input type="image" name="send_work_list_btn" id="send_work_list_btn" src="/system/img/btn_back_list_off.gif" value="戻る" />
<input type="hidden" name="dd" id="dd" value="{{ $dd }}" />
<input type="hidden" name="se_flg" id="se_flg" value="1" />
<input type="hidden" name="act" id="act" value="up" />
</form>
@include('admin.operaciones.working.partials.pager', [
    'routeName' => 'admin.operaciones.working.day2',
    'params' => ['kensu' => $kensu, 'se_flg' => 1, 'shop_cd' => $shopCd, 'dd' => $dd, 'act' => 'up'],
])


</div>

<!-- / .contents --></div>
@endsection
