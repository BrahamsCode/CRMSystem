{{--
    Legacy: checkIsNumber(), submitChk() y addBreakTime() de yoyaku_uketsuke_day.php / yoyaku_multi_uketsuke_day.php
    (mismo código activo en las dos; el Form Request repite la validación). Sin variables.
--}}
<script type="text/javascript">

function checkIsNumber(value){
	return (value.match(/[0-9]+/g) == value);
}


function submitChk () {

	var errorMess = "";
	var startTime = document.form1.start_hour.value + ":" + document.form1.start_min.value;
	var endTime = document.form1.end_hour.value + ":" + document.form1.end_min.value;
	var shopStartTime = $("#shop_start_time").val();
	var shopEndTime = $("#shop_end_time").val();

	if(document.form1.people.value == ""){
		errorMess += "「最大受付人数」が未入力です。\n";
	}else if(!checkIsNumber(document.form1.people.value)) {
		errorMess += "「最大受付人数」の入力内容が不適切です。\n";
	}

	if(startTime >= endTime){
		errorMess += "開始時間と終了時間を確認してください。\n";
	}

	if(startTime < shopStartTime || endTime > shopEndTime){
		errorMess += "出勤時間は"+shopStartTime+"から"+shopEndTime+"までの間にしてください。\n";
	}

	$(".timeSelect").each(function() {

		var self = $(this)
		  , startBreakH = self.children(".startBreakTimeH").val()
		  , startBreakM = self.children(".startBreakTimeMin").val()
		  , endBreakH = self.children(".endBreakTimeH").val()
		  , endBreakM = self.children(".endBreakTimeMin").val()
		  , startBreak = startBreakH +':'+ startBreakM
		  , endBreak   = endBreakH +':'+ endBreakM

		self.find('input.startBreakTime').val('');
		self.find('input.endBreakTime').val('');

		if (startBreakH == '' && startBreakM == '' && endBreakH == '' && endBreakM == '') {

			return true; // jqueryのeachループをcontinue

		}

		if (startBreak >= endBreak) {
			errorMess += "休憩・外出時間は異常です。("+startBreak+" ～ "+endBreak+")\n";
			return false; // jqueryのeachループを終了

		} else {
			self.find('input.startBreakTime').val(startBreak);
			self.find('input.endBreakTime').val(endBreak);
		}

	});

	if (errorMess != "") {
		jAlert(errorMess, "警告");
	} else {
		jConfirm ( "この内容で登録してもよろしいですか？\n\n書き直す場合は['キャンセル']ボタンを押して下さい", "予約受付登録", function(ret) {
			if (ret) {
				document.form1.submit();
			}
		});
	}

	return false;
}

var timeSelectCount = 0;

function addBreakTime(start, end) {

	if ($(".timeSelect").length >= 10) return false; // 2012.05.17 マルティン 10個までに変えました

	var businessStartTime = $("#shop_start_time").val();
	var startTime = businessStartTime.split(":");
	var businessEndTime = $("#shop_end_time").val();
	var endTime = businessEndTime.split(":");

	var selectedStart = start.split(':')
	  , selectedEnd   = end.split(':');

	var dispTime = selectedFlg = buttonHtml = ""
	  , startHtml = endHtml = '<option value="">&nbsp;</option>\n'
	  , startMinHtml = endMinHtml = '<option value="">&nbsp;</option>\n'
	  , intervalArray = '00<>10<>20<>30<>40<>50'.split("<>");

	var i
	  , imin = parseInt(startTime[0],10)
	  , imax = parseInt(endTime[0],10)
	  , j    = 0
	  , jmax = intervalArray.length;

	for (i=imin; i<=imax; i++) {

		dispTime = (i<10) ? '0'+i : i ;

		selectedFlg = (dispTime == selectedStart[0]) ? " selected" : "";
		startHtml += '<option value="'+dispTime+'"'+selectedFlg+'>'+dispTime+'</option>\n';

		selectedFlg = (dispTime == selectedEnd[0]) ? " selected" : "";
		endHtml += '<option value="'+dispTime+'"'+selectedFlg+'>'+dispTime+'</option>\n';

	} // END --- for (i=imin; i<=imax; i++)

	for (j=0; j<jmax; j++) {

		selectedFlg = (intervalArray[j] == selectedStart[1]) ? " selected" : "";
		startMinHtml += '<option value="'+intervalArray[j]+'"'+selectedFlg+'>'+intervalArray[j]+'</option>\n';

		selectedFlg = (intervalArray[j] == selectedEnd[1]) ? " selected" : "";
		endMinHtml += '<option value="'+intervalArray[j]+'"'+selectedFlg+'>'+intervalArray[j]+'</option>\n';

	} // END --- for (j=0; j<jmax; j++)

	buttonHtml = '&nbsp;<a class="btn btnStyle" onclick="$(this).parent().remove();">－</a>';

	var html = '<p class="timeSelect" style="padding:0;">';
	    html+= '<select class="startBreakTimeH" name="startBreakTimeH['+timeSelectCount+']">\n'+startHtml+'</select>時';
	    html+= '<select class="startBreakTimeMin" name="startBreakTimeMin['+timeSelectCount+']">\n'+startMinHtml+'</select>分 ～ ';
	    html+= '<select class="endBreakTimeH" name="endBreakTimeH['+timeSelectCount+']">\n'+endHtml+'</select>時';
	    html+= '<select class="endBreakTimeMin" name="endBreakTimeMin['+timeSelectCount+']">\n'+endMinHtml+'</select>分';
	    html+= '<input type="hidden" name="startBreakTime['+timeSelectCount+']" class="startBreakTime" value="" />'
	    html+= '<input type="hidden" name="endBreakTime['+timeSelectCount+']" class="endBreakTime" value="" />'
	    html+= buttonHtml+'</p>\n';

	$("#selectBreakTime").append(html);
	timeSelectCount++;
}

@if (isset($errors) && $errors->any())
{{-- Errores del servidor (mismos textos que el JS): se muestran igual que submitChk() --}}
$(document).ready(function() {
	jAlert({{ \Illuminate\Support\Js::from(implode("\n", $errors->all()) . "\n") }}, "警告");
});
@endif

</script>
