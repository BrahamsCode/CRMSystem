{{-- Legacy: system/category5/working/work_list.php (勤怠一覧 del día).
     Cambios: 決定 envía un POST con CSRF a work_list_update (el legacy navegaba con GET); se quita la rama Flash
     (AdobeAIR) de la descarga CSV; 戻る es un GET al calendario; la salida sin redondeo calculado se pinta "()"
     (el legacy pintaba la hora de epoch 0).
     Variables: $rows (WorkTimeService::dayRows), $shop, $day, $dd, $shopCd, $kensu, $page, $total, $from, $pager --}}
@extends('layouts.legacy')

@section('menu', 'operaciones')

@section('title', '勤怠一覧 - '.($siteName ?? ''))

@php
    $hm = fn ($t) => $t?->format('H:i');
    $updateUrl = route('admin.operaciones.working.update', ['page' => $page, 'shop_cd' => $shopCd, 'dd' => $dd, 'kensu' => $kensu]);
    // 決定: el legacy hacía location.href = tobisaki (GET que escribía); aquí POST con CSRF
    $onDecide = <<<'JS'
			tobisaki += "&scd="+staff_cd+"&sh="+syukkin_hour+"&sm="+syukkin_min+"&th="+taikin_hour+"&tm="+taikin_min+"&sf="+encodeURIComponent(syukkin_flg)+"&sk="+encodeURIComponent(syukkin_kubun);
			// POST con CSRF (el legacy hacía location.href = tobisaki)
			$('<form method="post"></form>').attr('action', tobisaki)
				.append($('<input type="hidden" name="_token">').val($('meta[name="csrf-token"]').attr('content')))
				.appendTo('body').trigger('submit');
JS;
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

var tobisaki = @json($updateUrl);
@include('admin.operaciones.working.partials.edit-js', ['selectClass' => '', 'onDecide' => $onDecide])

//表示件数の制御

function kensuSet(parts){
	document.form_search.kensu.value = parts.value;
	document.form_search.submit();
}

</script>
@endsection

@section('content')
<form method="post" name="form_search" id="form_search" class="displayNone" action="{{ route('admin.operaciones.working.day') }}">
@csrf
<input type="hidden" name="kensu" id="kensu" value="{{ $kensu }}" />
<input type="hidden" name="page" id="page" value="{{ $page }}" />
<input type="hidden" name="shop_cd" id="shop_cd" value="{{ $shopCd }}" />
<input type="hidden" name="dd" id="coupon_kubun" value="{{ $dd }}" />
<input type="hidden" name="act" id="act" value="up" />
</form>

<div class="contents">
<p class="h2Title">■勤怠一覧</p>



<div class="form">
@if (count($rows) > 0)

<p class="maxListLength">表示件数<select name="kensu_act" id="kensu_act" class="selectBox" onchange="kensuSet(this)"><option value="2" >2</option>
@foreach ([10, 20, 30, 40, 50] as $n)
<option value="{{ $n }}" {{ $kensu === $n ? 'selected' : '' }}>{{ $n }}</option>
@endforeach
</select></p>

<p><span class="attention">{{ $day->year }}年{{ $day->month }}月{{ $day->day }}日<BR>{{ $from + 1 }}件～{{ min($from + $kensu, $total) }}件目/{{ $total }}件中</span></p>



<form method="post" name="koushin_form" id="koushin_form" action="{{ route('admin.operaciones.working.day') }}" style="float: left;">
@csrf
<input type="hidden" name="shop_cd" id="shop_cd" value="{{ $shopCd }}" />
<input type="hidden" name="dd" id="dd" value="{{ $dd }}" />
<input type="submit" name="input1" id="input1" value="更新する" />
</form>

<form action="/system/category5/working/csv_download.php" method="post" style="float: right; margin-bottom: 5px;">
@csrf
<input type="hidden" name="titleflg" value="1">
<input type="hidden" name="flg" value="upload">
<input type="hidden" name="dd" value="{{ $dd }}">
<input type="hidden" name="shop_cd" value="{{ $shopCd }}">
<input type="hidden" name="exp" value="ＣＳＶダウンロード" />
<input type="submit" name="enter" id="enter" class="btn" value="この条件でCSV出力する" />
</form>

<table class="tableBox2" style="table-layout:fixed; clear: both;">
  <tr>
    <th>スタッフID</th>
    <th>名前</th>
    <th>出勤時間</th>
    <th>退勤時間</th>
    <th>勤務時間</th>
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
	{{ $row['in'] ? $hm($row['in']).($row['start'] ? '('.$hm($row['start']).')' : '') : '' }}
    </td>
	<input type="hidden" id="syukkin_hour{{ $i }}" value="{{ $row['in']?->format('H') }}">
	<input type="hidden" id="syukkin_min{{ $i }}" value="{{ $row['in']?->format('i') }}">

    <td id="taikin_time{{ $i }}">
	{{ $row['out'] ? $hm($row['out']).'('.$hm($row['end']).')' : '' }}
    </td>
	<input type="hidden" id="taikin_hour{{ $i }}" value="{{ $row['out']?->format('H') }}">
	<input type="hidden" id="taikin_min{{ $i }}" value="{{ $row['out']?->format('i') }}">

    <td>{{ $row['sum'] ? number_format($row['sum'], 1) : '' }}　時間</td>

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
    'routeName' => 'admin.operaciones.working.day',
    'params' => ['shop_cd' => $shopCd, 'dd' => $dd, 'act' => 'up', 'kensu' => $kensu],
])


</div>

<!-- / .contents --></div>
@endsection
