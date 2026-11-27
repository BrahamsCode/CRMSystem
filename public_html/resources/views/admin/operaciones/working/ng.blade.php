{{-- Legacy: system/category5/working/ng_work_list.php (勤怠一覧, variante: corrige solo 出勤状況/出勤区分 en una ventanita).
     Cambios: el AJAX es $.post (el legacy usaba $.getJSON, un GET que escribía) y no envía horas, así que no borra las
     marcas (el legacy las vaciaba); la paginación funciona (el legacy leía $page sin definir: siempre la 1);
     戻る es un GET al calendario con el dd de la lista.
     Variables: $rows (WorkTimeService::dayRows), $shop, $day, $dd, $shopCd, $kensu, $page, $total, $from, $pager --}}
@extends('layouts.legacy')

@section('menu', 'operaciones')

@section('title', '勤怠一覧 - '.($siteName ?? ''))

@php
    $hm = fn ($t) => $t?->format('H:i');
    $kensuUrl = route('admin.operaciones.working.ng', ['shop_cd' => $shopCd, 'dd' => $dd]);
    $changeUrl = route('legacy.operaciones.working-change');
    $hhmm = function (?float $sum) {
        $hours = (int) $sum;
        $mins = (int) (($sum - $hours) * 60);

        return ($hours < 10 ? '0'.$hours : $hours).':'.($mins < 10 ? '0'.$mins : $mins);
    };
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

var jikkouFlg = 0;
var dousaFlg;

function creatForm(val){

		$(".henkouArea").remove();
		jikkouFlg = 0;

		if(jikkouFlg == 0){

			henkouForm = "<div class=\"henkouArea\">";
			henkouForm += "<fieldset class=\"henkouForm\"\><legend>訂正内容を入力</legend>";
			henkouForm += "<form method=\"post\" name=\"henkouForm" + val + "\" id=\"henkouForm" + val + "\" class=\"form\" >";
			henkouForm += "<p>" + $("#staffID" + val).attr("number") + "</p>";
			henkouForm += "<p>「" + $("#staffName" + val).text() + "」修正</p>";
			henkouForm += "出勤状況<select name=\"user_syukkin_flg\" id=\"user_syukkin_flg\" class=\"selectBox\">";
			henkouForm += "<option value=\"\"></option>";
			henkouForm += "<option value=\"早出\">早出</option>";
			henkouForm += "<option value=\"出勤\">出勤</option>";
			henkouForm += "<option value=\"遅刻\">遅刻</option>";
			henkouForm += "</select><br />";
			henkouForm += "出勤区分<select name=\"user_syukkin_kubun\" id=\"user_syukkin_kubun\" class=\"selectBox\">";
			henkouForm += "<option value=\"\"></option>";
			henkouForm += "<option value=\"早退\">早退</option>";
			henkouForm += "<option value=\"残業\">残業</option>";
			henkouForm += "<option value=\"退社\">退社</option>";
			henkouForm += "</select><br />";
			henkouForm += "<input type=\"submit\" name=\"kettei\" id=\"kettei\" class=\"btn\" value=\"決定\" />";
			henkouForm += "<input type=\"button\" name=\"close\" id=\"close\" class=\"btn\" value=\"閉じる\" />";
			henkouForm += "</form>";
			henkouForm += "</fieldset>";
			henkouForm += "</div>";

			$("#btnArea" + val).append(henkouForm);

			jikkouFlg = 1;
			dousaFlg = val;

			$("#close").click(function () {
				$(".henkouArea").remove();
				jikkouFlg = 0;
			});

			$("#kettei").click(function () {
				staff_cd = $("#staffID" + val).attr("number");
				nengappi = $("#dd").val();
				syukkin_flg = $("#user_syukkin_flg").val();
				syukkin_kubun = $("#user_syukkin_kubun").val();
				$.post(@json($changeUrl),{
					staff_cd: staff_cd,
					nengappi: nengappi,
					syukkin_flg: syukkin_flg,
					syukkin_kubun: syukkin_kubun
				}, function (data) {
					$("#syukkin_flg"+val).html(data.shukkin_flg);
					$("#syukkin_kubun"+val).html(data.shukkin_kubun);
				}, "json");
				$(".henkouArea").remove();
				jikkouFlg = 0;
				return false;
			});

		}

}
</script>
@endsection

@section('content')
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

<table class="tableBox2">
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
    <td>{{ $row['in'] ? $hm($row['in']).'('.$hm($row['start']).')' : '' }}</td>
    <td>{{ $row['out'] ? $hm($row['out']) : '' }}</td>
    <td>{{ $row['sum'] ? $hhmm($row['sum']) : '' }}</td>
    <td id="gaisyutu_cnt{{ $i }}">@if ($row['in']){{ $row['breaks'] }}({{ $shop?->break_time }}分)@endif</td>
    <td id="syukkin_flg{{ $i }}">{{ $row['flg'] }}</td>
    <td id="syukkin_kubun{{ $i }}">{{ $row['kubun'] }}</td>
    <td id="btnArea{{ $i }}">@if ($row['in'])<img src="/system/img/btn_revise_off.gif" width="40" height="20" alt="編集" onclick="creatForm({{ $i }})" />@endif</td>
@else
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td><img src="/system/img/btn_revise_off.gif" width="40" height="20" alt="編集" /></td>
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
    'routeName' => 'admin.operaciones.working.ng',
    'params' => ['kensu' => $kensu, 'se_flg' => 1, 'shop_cd' => $shopCd, 'dd' => $dd, 'act' => 'up'],
])


</div>

<!-- / .contents --></div>
@endsection
