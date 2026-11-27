{{-- creatForm() de work_list.php / work_list2.php (edición en la fila: horas, 出勤状況, 出勤区分), copiado del legacy.
     Variables: $selectClass (work_list2 añade class="selectBox" y quita el "：" entre hora y minuto), $onDecide (JS que se
     ejecuta en 決定 con staff_cd, nengappi, syukkin_hour… ya leídos) --}}
var jikkouFlg = 0;
var dousaFlg;

var tmp_syukkin_time, tmp_taikin_time, tmp_syukkin_flg, tmp_syukkin_kubun, tmp_henkou;

function creatForm(val){

	$(".img_btn").css('visibility','hidden');

	jikkouFlg = 0;


	if(jikkouFlg == 0){

		//---------- 出勤時間 ----------
		tmp_syukkin_time = $("#syukkin_time" + val).html();
		tmp_syukkin_hour = $("#syukkin_hour" + val).val();
		tmp_syukkin_min = $("#syukkin_min" + val).val();

		syukkinForm = "<select name=\"user_syukkin_hour\" id=\"user_syukkin_hour\"{!! $selectClass !!}>";
		if (tmp_syukkin_hour == '') syukkinForm += '<option value="" ></option>';
		for (i=0; i<24; i++) {
			disp_i = i<10 ? '0'+i : i;
			selected = disp_i == tmp_syukkin_hour ? 'selected' : '' ;
			syukkinForm += '<option value="'+disp_i+'" '+selected+'>'+disp_i+'</option>';
		}
		syukkinForm += "</select>&nbsp;{!! $selectClass === '' ? '：' : '' !!}";

		syukkinForm += "<select name=\"user_syukkin_min\" id=\"user_syukkin_min\"{!! $selectClass !!}>";
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

		taikinForm = "<select name=\"user_taikin_hour\" id=\"user_taikin_hour\"{!! $selectClass !!}>";
		if (tmp_taikin_hour == '') taikinForm += '<option value="" ></option>';
		for (i=0; i<24; i++) {
			disp_i = i<10 ? '0'+i : i;
			selected = disp_i == tmp_taikin_hour ? 'selected' : '' ;
			taikinForm += '<option value="'+disp_i+'" '+selected+'>'+disp_i+'</option>';
		}
		taikinForm += "</select>&nbsp;{!! $selectClass === '' ? '：' : '' !!}";

		taikinForm += "<select name=\"user_taikin_min\" id=\"user_taikin_min\"{!! $selectClass !!}>";
		if (tmp_taikin_min == '') taikinForm += '<option value="" ></option>';
		for (i=0; i<60; i++) {
			disp_i = i<10 ? '0'+i : i;
			selected = disp_i == tmp_taikin_min ? 'selected' : '' ;
			taikinForm += '<option value="'+disp_i+'" '+selected+'>'+disp_i+'</option>';
		}
		taikinForm += "</select>&nbsp;";

		//---------- 出勤状況 ----------
		tmp_syukkin_flg = $("#syukkin_flg" + val).html();
		jyokyou_opt = new Array("早出","出勤","遅刻");
		jyoukyouForm = "<select name=\"user_syukkin_flg\" id=\"user_syukkin_flg\" class=\"selectBox\">";
		jyoukyouForm += '<option value="'+tmp_syukkin_flg+'" >'+tmp_syukkin_flg+'</option>';
		for (i=0; i<jyokyou_opt.length; i++) {
			selected = '' ;
			jyoukyouForm += '<option value="'+jyokyou_opt[i]+'" '+selected+'>'+jyokyou_opt[i]+'</option>';
		}
		jyoukyouForm += "</select>";

		//---------- 出勤区分 ----------
		tmp_syukkin_kubun = $("#syukkin_kubun" + val).html();
		kubun_opt = new Array("早退","残業","退社");
		kubunForm = "<select name=\"user_syukkin_kubun\" id=\"user_syukkin_kubun\" class=\"selectBox\">";
		kubunForm += '<option value="'+tmp_syukkin_kubun+'" >'+tmp_syukkin_kubun+'</option>';
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
			staff_cd = $("#staffID" + val).attr("number");
			nengappi = $("#dd").val();
			syukkin_hour = $("#user_syukkin_hour").val();
			syukkin_min = $("#user_syukkin_min").val();
			taikin_hour = $("#user_taikin_hour").val();
			taikin_min = $("#user_taikin_min").val();
			syukkin_flg = $("#user_syukkin_flg").val();
			syukkin_kubun = $("#user_syukkin_kubun").val();
{!! $onDecide !!}
		});

	}

}
