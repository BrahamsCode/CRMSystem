{{-- Legacy: system/category1/entrance/work_list.php (入退室一覧 del día). El 決定 del editor de fila envía un
     formulario POST oculto a admin.clientes.entries.update (el legacy navegaba por GET con scd, sh, sm… y guardaba).
     Variables:
       $siteName
       $date        CarbonImmutable: el día
       $dd          'Y/n/j' del día
       $shopCd      int|null: filtro shop_cd (el calendario no lo envía)
       $rows        LengthAwarePaginator de ['customer' => Customer, 'entry' => null | ['in','in_h','in_m','out','out_h','out_m','status','exit_status']]
       $entryStatuses, $exitStatuses  opciones de 入室状況 / 退出状況 --}}
@extends('layouts.legacy')

@section('menu', 'clientes')
@section('title', '入退室一覧 - ' . $siteName)

@php
    $kensu = $rows->perPage();
    $page = $rows->currentPage();
    $from = ($page - 1) * $kensu;
    $maxrows = $rows->total();
    $listUrl = fn (array $params = []) => route('admin.clientes.entries.index', $params);
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

var tmp_syukkin_time, tmp_taikin_time, tmp_syukkin_flg, tmp_syukkin_kubun, tmp_henkou;
var jyokyou_opt = @json($entryStatuses);
var kubun_opt = @json($exitStatuses);

// Escapa el texto actual antes de meterlo en el HTML del <select>
function escHtml(s){
	return $("<div/>").text(s).html().replace(/"/g, "&quot;");
}

function creatForm(val){

	$(".img_btn").css('visibility','hidden');

	jikkouFlg = 0;


	if(jikkouFlg == 0){

		//---------- 出勤時間 ----------
		tmp_syukkin_time = $("#syukkin_time" + val).html();
		tmp_syukkin_hour = $("#syukkin_hour" + val).val();
		tmp_syukkin_min = $("#syukkin_min" + val).val();

		syukkinForm = "<select name=\"user_syukkin_hour\" id=\"user_syukkin_hour\">";
		if (tmp_syukkin_hour == '') syukkinForm += '<option value="" ></option>';
		for (i=0; i<24; i++) {
			disp_i = i<10 ? '0'+i : i;
			selected = disp_i == tmp_syukkin_hour ? 'selected' : '' ;
			syukkinForm += '<option value="'+disp_i+'" '+selected+'>'+disp_i+'</option>';
		}
		syukkinForm += "</select>&nbsp;：";

		syukkinForm += "<select name=\"user_syukkin_min\" id=\"user_syukkin_min\">";
		if (tmp_syukkin_min == '') syukkinForm += '<option value="" ></option>';
		for (i=0; i<60; i++) {
			disp_i = i<10 ? '0'+i : i;
			selected = disp_i == tmp_syukkin_min ? 'selected' : '' ;
			syukkinForm += '<option value="'+disp_i+'" '+selected+'>'+disp_i+'</option>';
		}
		syukkinForm += "</select>&nbsp;";

		//---------- 退勤時間 ----------
		tmp_taikin_time = $("#taikin_time" + val).html();
		tmp_taikin_hour = $("#taikin_hour" + val).val();
		tmp_taikin_min = $("#taikin_min" + val).val();

		taikinForm = "<select name=\"user_taikin_hour\" id=\"user_taikin_hour\">";
		if (tmp_taikin_hour == '') taikinForm += '<option value="" ></option>';
		for (i=0; i<24; i++) {
			disp_i = i<10 ? '0'+i : i;
			selected = disp_i == tmp_taikin_hour ? 'selected' : '' ;
			taikinForm += '<option value="'+disp_i+'" '+selected+'>'+disp_i+'</option>';
		}
		taikinForm += "</select>&nbsp;：";

		taikinForm += "<select name=\"user_taikin_min\" id=\"user_taikin_min\">";
		if (tmp_taikin_min == '') taikinForm += '<option value="" ></option>';
		for (i=0; i<60; i++) {
			disp_i = i<10 ? '0'+i : i;
			selected = disp_i == tmp_taikin_min ? 'selected' : '' ;
			taikinForm += '<option value="'+disp_i+'" '+selected+'>'+disp_i+'</option>';
		}
		taikinForm += "</select>&nbsp;";

		//---------- 出勤状況 ----------
		tmp_syukkin_flg = $("#syukkin_flg" + val).html();
		jyoukyouForm = "<select name=\"user_syukkin_flg\" id=\"user_syukkin_flg\" class=\"selectBox\">";
		jyoukyouForm += '<option value="'+escHtml($("#syukkin_flg" + val).text())+'" >'+tmp_syukkin_flg+'</option>';
		for (i=0; i<jyokyou_opt.length; i++) {
			selected = '' ;
			jyoukyouForm += '<option value="'+jyokyou_opt[i]+'" '+selected+'>'+jyokyou_opt[i]+'</option>';
		}
		jyoukyouForm += "</select>";

		//---------- 出勤区分 ----------
		tmp_syukkin_kubun = $("#syukkin_kubun" + val).html();
		kubunForm = "<select name=\"user_syukkin_kubun\" id=\"user_syukkin_kubun\" class=\"selectBox\">";
		kubunForm += '<option value="'+escHtml($("#syukkin_kubun" + val).text())+'" >'+tmp_syukkin_kubun+'</option>';
		for (i=0; i<kubun_opt.length; i++) {
			selected = '' ;
			kubunForm += '<option value="'+kubun_opt[i]+'" '+selected+'>'+kubun_opt[i]+'</option>';
		}
		kubunForm += "</select>";

		tmp_henkou = $("#btnArea" + val).html();
		henkouForm = "<input type=\"submit\" name=\"kettei\" id=\"kettei\" class=\"btn\" value=\"決定\" />&nbsp;";
		henkouForm += "<input type=\"button\" name=\"close\" id=\"close\" class=\"btn\" value=\"閉じる\" />";

		$("#syukkin_time" + val).html(syukkinForm);
		$("#taikin_time" + val).html(taikinForm);
		$("#syukkin_flg" + val).html(jyoukyouForm);
		$("#syukkin_kubun" + val).html(kubunForm);
		$("#btnArea" + val).html(henkouForm);

		jikkouFlg = 1;
		dousaFlg = val;

		$("#close").click(function () {
			$("#syukkin_time" + val).html(tmp_syukkin_time);
			$("#taikin_time" + val).html(tmp_taikin_time);
			$("#syukkin_flg" + val).html(tmp_syukkin_flg);
			$("#syukkin_kubun" + val).html(tmp_syukkin_kubun);
			jikkouFlg = 0;
			$("#btnArea" + val).html(tmp_henkou);
			$(".img_btn").css('visibility','visible');
		});

		$("#kettei").click(function () {
			// El legacy navegaba a work_list.php?…&scd=…&sh=… (GET); aquí se envía el formulario POST oculto
			var f = document.entry_update;
			f.scd.value = $("#staffID" + val).attr("number");
			f.sh.value = $("#user_syukkin_hour").val();
			f.sm.value = $("#user_syukkin_min").val();
			f.th.value = $("#user_taikin_hour").val();
			f.tm.value = $("#user_taikin_min").val();
			f.sf.value = $("#user_syukkin_flg").val();
			f.sk.value = $("#user_syukkin_kubun").val();
			f.submit();
		});

	}

}






</script>

<script type="text/javascript">


//表示件数の制御

function kensuSet(parts){
	document.form_search.kensu.value = parts.value;
	document.form_search.submit();
}

</script>
@endsection

@section('content')
<form method="get" name="form_search" id="form_search" class="displayNone" action="{{ route('admin.clientes.entries.index') }}">
<input type="hidden" name="kensu" id="kensu" value="{{ $kensu }}" />
<input type="hidden" name="page" id="page" value="{{ $page }}" />
<input type="hidden" name="shop_cd" id="shop_cd" value="{{ $shopCd }}" />
<input type="hidden" name="dd" id="coupon_kubun" value="{{ $dd }}" />
<input type="hidden" name="act" id="act" value="up" />
</form>

<div class="contents">
<p class="h2Title">■入退室一覧</p>



<div class="form">
@if ($rows->count() > 0)

<p class="maxListLength">表示件数<select name="kensu_act" id="kensu_act" class="selectBox" onchange="kensuSet(this)">@for ($i = 1; $i <= 5; $i++)<option value="{{ $i * 10 }}" {{ $kensu == $i * 10 ? 'selected' : '' }}>{{ $i * 10 }}</option>
@endfor</select></p>

<p><span class="attention">{{ $date->year }}年{{ $date->month }}月{{ $date->day }}日<BR>{{ $from + 1 }}件～{{ min($from + $kensu, $maxrows) }}件目/{{ $maxrows }}件中</span></p>

<table class="tableBox2" style="table-layout:fixed;">
  <tr>
    <th>生徒番号</th>
    <th>名前</th>
    <th>入室時間</th>
    <th>退室時間</th>
    <th>入室状況</th>
    <th>退出状況</th>
    <th>&nbsp;</th>
  </tr>


@foreach ($rows as $row)
@php($customer = $row['customer'])
@php($entry = $row['entry'])
@php($n = $loop->index)
	@if ($n % 2 <> 0)
	  <tr class="tableBg">
	@else
	  <tr>
	@endif

    <td id="staffID{{ $n }}" number="{{ $customer->cd }}">{{ $customer->id }}</td>
    <td id="staffName{{ $n }}"><span title="{{ $customer->name_kana }}">{{ $customer->name }}</span></td>
@if ($entry)

    <td id="syukkin_time{{ $n }}">
	{{ $entry['in'] }}
    </td>
	<input type="hidden" id="syukkin_hour{{ $n }}" value="{{ $entry['in_h'] }}">
	<input type="hidden" id="syukkin_min{{ $n }}" value="{{ $entry['in_m'] }}">

    <td id="taikin_time{{ $n }}">
	{{ $entry['out'] }}
    </td>
	<input type="hidden"id="taikin_hour{{ $n }}" value="{{ $entry['out_h'] }}">
	<input type="hidden"id="taikin_min{{ $n }}" value="{{ $entry['out_m'] }}">

    <td id="syukkin_flg{{ $n }}">{{ $entry['status'] }}</td>

    <td id="syukkin_kubun{{ $n }}">{{ $entry['exit_status'] }}</td>

    <td id="btnArea{{ $n }}"><img class="img_btn" src="{{ asset('legacy/system/img/btn_revise_off.gif') }}" width="40" height="20" alt="編集" onclick="creatForm({{ $n }})" /></td>


@else
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
検索条件に合った生徒は、見つかりませんでした。再度検索してください
@endif

<form method="get" name="send_work_list" id="send_work_list" class="form mT10" action="{{ route('admin.clientes.entries.calendar') }}">
<input type="submit" name="send_work_list_btn" id="send_work_list_btn" value="日付選択へ戻る" />
<input type="hidden" name="dd" id="dd" value="{{ $dd }}" />
<input type="hidden" name="se_flg" id="se_flg" value="1" />
<input type="hidden" name="act" id="act" value="up" />
</form>
<ul class="btnBox2">
@include('admin.clientes.config.partials.page-control', [
    'paginator' => $rows,
    'pageUrl' => fn ($p) => $listUrl(['page' => $p, 'shop_cd' => $shopCd, 'dd' => $dd, 'act' => 'up', 'kensu' => $kensu]),
])
</ul>


</div>

<form method="post" name="entry_update" id="entry_update" class="displayNone" action="{{ route('admin.clientes.entries.update') }}">
@csrf
<input type="hidden" name="page" value="{{ $page }}" />
<input type="hidden" name="shop_cd" value="{{ $shopCd }}" />
<input type="hidden" name="dd" value="{{ $dd }}" />
<input type="hidden" name="kensu" value="{{ $kensu }}" />
<input type="hidden" name="scd" value="" />
<input type="hidden" name="sh" value="" />
<input type="hidden" name="sm" value="" />
<input type="hidden" name="th" value="" />
<input type="hidden" name="tm" value="" />
<input type="hidden" name="sf" value="" />
<input type="hidden" name="sk" value="" />
</form>






<!-- / .contents --></div>
@endsection
