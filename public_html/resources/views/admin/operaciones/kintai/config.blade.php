{{-- Legacy: system/category5/kintai/config.php (各種設定データ). Mismo HTML: 登録店舗 (cambia la página), horas
     de corte/始業/終業 (minutos de 5 en 5), CSV mensual de un empleado y 背景画像. La sección シフト区分管理 del
     legacy es su propia pantalla (menú スケジュール管理): aquí queda un enlace. Sin el botón Flash del cliente AIR
     ni la sesión de ajax_session_update.php (solo la usaba ese botón).
     Variables: $shopCd, $shops, $times (['kijun'|'start'|'end' => [h, m]]), $staff, $yy, $mm, $thumb ([w, h]|null), $error --}}
@extends('layouts.legacy')

@section('menu', 'operaciones')

@section('title', '各種設定データ - '.($siteName ?? '').' - ')

@section('body_class', 'config')

@section('css')
<link href="/system/css/jquery.alerts.css" rel="stylesheet" type="text/css" media="all" />

<style type="text/css">
<!--

p.errorText
{
	padding: 5px;
	border: 1px dotted #FF0000;
	color: #FF0000;
}

-->
</style>
@endsection

@section('js')
<script type="text/javascript" src="/system/js/jquery.alerts.js"></script>
<script type="text/javascript">
<!--

// =============================================================================
// 登録店舗セレクト変更
// =============================================================================

$(document).ready(function(){

	$('#shop').change(function(){

		location.href = "{{ route('admin.operaciones.kintai.config.edit') }}?shop="+$(this).val();

	});

});

// =============================================================================
// 月別勤怠のCSV出力
// =============================================================================

function csvOutput(){

	var staffCd = $('#monthly_csv_user').val();	// スタッフ選択
	var yy = $('#monthly_csv_yy').val();		// 年選択
	var mm = $('#monthly_csv_mm').val();		// 月選択

	if(staffCd != ""){
		location.href = "{{ route('admin.operaciones.kintai.csv.monthly') }}?staff="+staffCd+"&yy="+yy+"&mm="+mm+"";
	}else{
		jAlert("スタッフを選択してください。", "入力エラー");
	} // if(staffCd != "") --- END

} // function csvOutput() --- END

// =============================================================================
// フォーム送信確認
// =============================================================================

function submitCheck(){

	var ret = confirm("更新処理を行います。\nよろしいですか？");

	return ret;

} // function submitCheck() --- END

-->
</script>
@endsection

@section('content')
<div class="contents">


<p class="h2Title">■各種設定データ</p>


<p>勤怠の各種設定データの変更ができます。</p>
@if ($error !== '')<p class="errorText">{!! $error !!}</p>@endif


<div class="configArea">


<form action="{{ route('admin.operaciones.kintai.config.update') }}" class="form" method="post" name="form1" onsubmit="return submitCheck();" enctype="multipart/form-data">
@csrf


<table class="tableBox">

	<tr>
	<th width="200">登録店舗</th>
	<td>

	<select name="shop" id="shop">
	@include('admin.operaciones.partials.options', ['placeholder' => '▼店舗選択', 'options' => $shops, 'selected' => $shopCd])
	</select>

	</td>
	</tr>

@foreach (['kijun' => '日付変更基準時間', 'start' => '始業時間', 'end' => '終業時間'] as $key => $label)
	<tr>
	<th width="200">{{ $label }}</th>
	<td>
	<select name="{{ $key }}_time_h">
	@for ($i = 0; $i < 24; $i++)
	<option value="{{ sprintf('%02d', $i) }}"@if ($times[$key][0] === $i) selected="selected"@endif>{{ sprintf('%02d', $i) }}</option>
	@endfor
	</select>&nbsp;時&nbsp;

	<select name="{{ $key }}_time_m">
	@for ($i = 0; $i < 60; $i += 5)
	<option value="{{ sprintf('%02d', $i) }}"@if ($times[$key][1] === $i) selected="selected"@endif>{{ sprintf('%02d', $i) }}</option>
	@endfor
	</select>&nbsp;分&nbsp;
	</td>
	</tr>

@endforeach
	<tr>
	<th width="200">月別勤怠のCSV出力</th>
	<td>

	<select id="monthly_csv_user" name="monthly_csv_user">
	<option value="">選択してください</option>
	@foreach ($staff as $member)
	<option value="{{ $member->cd }}">{{ $member->name }}</option>
	@endforeach
	</select>

	<select name="monthly_csv_yy" id="monthly_csv_yy">
	@for ($i = 1990; $i <= (int) now(\App\Services\Operaciones\LegacyMonth::TIMEZONE)->format('Y'); $i++)
	<option value="{{ $i }}"@if ($yy === $i) selected="selected"@endif>{{ $i }}</option>
	@endfor
	</select>&nbsp;年&nbsp;

	<select name="monthly_csv_mm" id="monthly_csv_mm">
	@for ($i = 1; $i <= 12; $i++)
	<option value="{{ sprintf('%02d', $i) }}"@if ($mm === $i) selected="selected"@endif>{{ sprintf('%02d', $i) }}</option>
	@endfor
	</select>&nbsp;月&nbsp;

	<input type="button" name="csv" id="csv" onclick="csvOutput();" value="CSV出力" />

	</td>
	</tr>

	<tr>
	<th width="200">勤怠打刻画面の背景画像</th>
	<td>

	@if ($thumb)
	<div><img src="/hp/uploads/c5/kintai/{{ $shopCd }}/thum.jpg?t={{ time() }}" width="{{ $thumb[0] }}" height="{{ $thumb[1] }}" alt="現在表示中の背景画像" /></div>
	@endif
	<input name="upfile" id="upfile" type="file" size="50" />

	</td>
	</tr>

</table>


<div class="btnBox">
<input type="submit" name="act" id="act" class="btnStyleM" value="　　登録　　" />
<!-- □□ /.btnBox □□ --></div>


</form>


<!--/.configArea--></div>


<p class="h2Title">■シフト区分管理</p>
<p><a href="{{ route('admin.operaciones.shift-kinds.index') }}" class="btnStyle">シフト区分管理</a></p>


<!-- / .contents --></div>
@endsection
