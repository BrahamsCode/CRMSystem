{{-- Legacy: system/category5/kintai/kintai_update.php (勤怠データ編集). Mismo HTML, JS (勤務状況 ≠ 出勤 desactiva
     los selectores; 外出/戻り se marcan en pareja para borrar) y mensajes. Cambios: 一括削除 por POST con CSRF;
     al volver a pintar con errores se muestran las horas enviadas (el legacy mostraba las guardadas en 出勤/退勤);
     備考 escapado.
     Variables: $hiduke, $staffCd, $shopCd, $yy/$mm/$dd, $hours, $hasData, $staffName, $current, $kinmuStatus, $bikou,
     $statuses, $shukkin/$taikin (['cd', 'value' => [hora, minuto]]), $gaishutu/$modori (lista de lo mismo),
     $showNewOuting, $newValues, $errorText, $query --}}
@extends('layouts.legacy')

@section('menu', 'operaciones')

@section('title', '勤怠データ編集 - '.($siteName ?? '').' - ')

@section('body_class', 'kintaiUpdate')

@section('css')
<link href="/system/css/jquery.alerts.css" rel="stylesheet" type="text/css" media="all" />

<style type="text/css">
<!--

/* css code */

-->
</style>
@endsection

@section('js')
<script type="text/javascript" src="/system/js/jquery.alerts.js"></script>
<script type="text/javascript">
$(document).ready(function() {
	$(".ac2").accordion('option', 'active', 0);
});

// =============================================================================
// 戻るボタン
// =============================================================================

function pageBack(){

	location.href = "{!! route('admin.operaciones.kintai.index', $query) !!}";

} // function pageBack() --- END

// =============================================================================
// 削除ボタン
// =============================================================================

function dataDelete(){


	jConfirm("チェックの入っているデータを全て削除します。\nよろしいですか？", "削除確認", function(ret){

		if(ret == false){
			return false;
		}else{


			var cds = [];

			// 外出・戻り
			$('input[name="gaishutu_del[]"],input[name="modori_del[]"]').each(function(){

				if($(this).attr("checked")) cds.push($(this).val());

			});

			// 退勤
			var taikinObj = $('input[name="taikin_del"]');

			if(taikinObj.attr("checked") && taikinObj.val() != "") cds.push(taikinObj.val());


			// 削除チェックがあれば実行
			if(cds.length > 0){

				var form = document.getElementById('deleteForm');

				for (var i = 0; i < cds.length; i++) {
					var input = document.createElement('input');
					input.type = 'hidden'; input.name = 'cd[]'; input.value = cds[i];
					form.appendChild(input);
				}

				form.submit();

			} // if(cds.length > 0) --- END


		} // if(ret == false) --- END

	});


} // function dataDelete() --- END

// =============================================================================
// フォームの送信時にdisabledを解除
// =============================================================================

function undisabled(){

	$('#scheduleTbl select').attr('disabled',false);
	return true;

} // function undisabled() --- END

// =============================================================================

$(document).ready(function(){


	// 削除チェックボックス --------------------------------------------------------

	$('input[name="gaishutu_del[]"],input[name="modori_del[]"]').bind("click",function(){

		var thisId = $(this).attr("id").split("_");
		var checkedVal = $(this).attr("checked");

		// 「外出」と「戻り」をセットで削除するためにチェックを連動させる

		if(thisId[0] == "modori"){

			$("#gaishutu_del_"+thisId[2]).attr("checked",checkedVal)

		}else{

			$("#modori_del_"+thisId[2]).attr("checked",checkedVal)

		}

	});


	// 勤務状況セレクト ------------------------------------------------------------

	function statusChange() {

		var currentStatus = $('#status').val();	// 選択内容

		if(currentStatus != "出勤"){

			$('#userStatus1').hide();
			$('#userStatus2').show();

			$('#scheduleTbl select').attr('disabled',true);

		}else{

			$('#userStatus1').show();
			$('#userStatus2').hide();

			$('#scheduleTbl select').attr('disabled',false);

		} // if(currentStatus != "出勤") --- END

	}

	// イベントリスナー

	$('#status').change(statusChange);

	// 初期表示

	statusChange();

	// -----------------------------------------------------------------------------


});
</script>
@endsection

@section('content')
<div class="contents">

<p class="h2Title">■勤怠データ編集</p>


<form id="form1" class="form" method="post" action="{{ route('admin.operaciones.kintai.update', $hiduke) }}">
@csrf
@method('PUT')

<fieldset>


@if ($hasData)
<legend><span>{{ $yy }}/{{ sprintf('%02d', $mm) }}/{{ sprintf('%02d', $dd) }}</span> 勤怠データ</legend>
@if ($errorText !== '')<p class="errorText">{!! $errorText !!}</p>@endif


<div class="configArea inputArea">

<table class="tableBox">

<tr>
<th>名前</th>
<td>{{ $staffName }}</td>
</tr>

<tr>
<th>ステータス</th>
<td>
<span id="userStatus1">{{ $current }}</span>
<span id="userStatus2">---</span>
</td>
</tr>

<tr>
<th>勤務状況</th>
<td>
<select id="status" name="status">
@foreach ($statuses as $status)
<option value="{{ $status }}"@if ($kinmuStatus === $status) selected="selected"@endif>{{ $status }}</option>
@endforeach
</select>
</td>
</tr>

<tr>
<th>備考</th>
<td>
<textarea class="inputM2" cols="40" rows="4" id="bikou" name="bikou">{{ $bikou }}</textarea>
</td>
</tr>

</table>

<!-- □□ /.configArea □□ --></div>


<table id="scheduleTbl" class="tableBox" style="margin-top:10px;">

<tr>
<th align="center" valign="middle">出勤</th>
<th align="center" valign="middle">外出</th>
<th align="center" valign="middle">戻り</th>
<th align="center" valign="middle">退勤</th>
</tr>

<tr>

<td align="center" valign="middle">
<select id="shukkin_h" name="shukkin_h">
@include('admin.operaciones.kintai.partials.time-options', ['type' => 'hour', 'selected' => $shukkin['value'][0]])
</select>
:
<select id="shukkin_m" name="shukkin_m">
@include('admin.operaciones.kintai.partials.time-options', ['type' => 'minute', 'selected' => $shukkin['value'][1]])
</select>
<input type="hidden" name="shukkin_cd" value="{{ $shukkin['cd'] }}" />
</td>

<td align="center" valign="top">
@foreach ($gaishutu as $key => $row)
<div style="display:inline-block;">

<select id="gaishutu_h_{{ $key }}" name="gaishutu_h[]">
@include('admin.operaciones.kintai.partials.time-options', ['type' => 'hour', 'selected' => $row['value'][0], 'blank' => false])
</select>
:
<select id="gaishutu_m_{{ $key }}" name="gaishutu_m[]">
@include('admin.operaciones.kintai.partials.time-options', ['type' => 'minute', 'selected' => $row['value'][1], 'blank' => false])
</select>
<input type="hidden" name="gaishutu_cd[]" value="{{ $row['cd'] }}" />
<input type="checkbox" name="gaishutu_del[]" id="gaishutu_del_{{ $key }}" value="{{ $row['cd'] }}" />
</div><br />
@endforeach
@if ($showNewOuting)
<div style="display:inline-block;">

<select id="gaishutu_h_new" name="gaishutu_h_new">
@include('admin.operaciones.kintai.partials.time-options', ['type' => 'hour', 'selected' => $newValues['gaishutu'][0]])
</select>
:
<select id="gaishutu_m_new" name="gaishutu_m_new">
@include('admin.operaciones.kintai.partials.time-options', ['type' => 'minute', 'selected' => $newValues['gaishutu'][1]])
</select>
</div><br />
@endif
</td>

<td align="center" valign="top">
@foreach ($modori as $key => $row)
<div style="display:inline-block;">

<select id="modori_h_{{ $key }}" name="modori_h[]">
@include('admin.operaciones.kintai.partials.time-options', ['type' => 'hour', 'selected' => $row['value'][0], 'blank' => false])
</select>
:
<select id="modori_m_{{ $key }}" name="modori_m[]">
@include('admin.operaciones.kintai.partials.time-options', ['type' => 'minute', 'selected' => $row['value'][1], 'blank' => false])
</select>
<input type="hidden" name="modori_cd[]" value="{{ $row['cd'] }}" />
<input type="checkbox" name="modori_del[]" id="modori_del_{{ $key }}" value="{{ $row['cd'] }}" />
</div><br />
@endforeach
<div style="display:inline-block;">

<select id="modori_h_new" name="modori_h_new">
@include('admin.operaciones.kintai.partials.time-options', ['type' => 'hour', 'selected' => $newValues['modori'][0]])
</select>
:
<select id="modori_m_new" name="modori_m_new">
@include('admin.operaciones.kintai.partials.time-options', ['type' => 'minute', 'selected' => $newValues['modori'][1]])
</select>
</div><br />
</td>

<td align="center" valign="middle">
<select id="taikin_h" name="taikin_h">
@include('admin.operaciones.kintai.partials.time-options', ['type' => 'hour', 'selected' => $taikin['value'][0]])
</select>
:
<select id="taikin_m" name="taikin_m">
@include('admin.operaciones.kintai.partials.time-options', ['type' => 'minute', 'selected' => $taikin['value'][1]])
</select>
<input type="hidden" name="taikin_cd" value="{{ $taikin['cd'] }}" />
<input type="checkbox" name="taikin_del" value="{{ $taikin['cd'] }}" />
</td>

</tr>

<!--■■ /#scheduleTbl ■■--></table>

</fieldset>


<div class="btnBox">
<input type="button" name="del" class="btnStyleM" onclick="dataDelete();" value=" チェックをいれたデータを一括削除 " />
<input type="button" name="back" class="btnStyleM" onclick="pageBack();" value="　　戻る　　" />
<input type="submit" name="enter" id="enter" class="btnStyleM" onclick="undisabled();" value="　　更新　　" />
<!--□□ /.btnBox □□--></div>

<input type="hidden" name="act" value="up" />
<input type="hidden" name="staff" value="{{ $staffCd }}" />
<input type="hidden" name="yy" value="{{ $yy }}" />
<input type="hidden" name="mm" value="{{ sprintf('%02d', $mm) }}" />
<input type="hidden" name="dd" value="{{ sprintf('%02d', $dd) }}" />
<input type="hidden" name="shop" value="{{ $shopCd }}" />
@else
<p>勤怠データがありません。</p>
<input type="button" name="back" class="btnStyleM" onclick="pageBack();" value="　戻る　" />
@endif


</form>

<form id="deleteForm" method="post" action="{{ route('admin.operaciones.kintai.records.destroy', $hiduke) }}" style="display:none;">
@csrf
@method('DELETE')
<input type="hidden" name="staff" value="{{ $staffCd }}" />
<input type="hidden" name="yy" value="{{ $yy }}" />
<input type="hidden" name="mm" value="{{ sprintf('%02d', $mm) }}" />
<input type="hidden" name="dd" value="{{ sprintf('%02d', $dd) }}" />
<input type="hidden" name="shop" value="{{ $shopCd }}" />
</form>


<!-- / .contents --></div>
@endsection
