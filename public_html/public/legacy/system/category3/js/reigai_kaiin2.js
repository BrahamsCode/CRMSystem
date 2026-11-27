// ----------------------------------------------------------------------------------------------------------------------------
// 2010.10.20 マルティン
// 例外顧客（フォローメールと自動メール用）
// ---------------------------------------------------------------------------------------------------------------------------


var reigai_kaiin_cds;
var cds = new Array();

function fnc_DisplayKaiin() {
	if (reigai_kaiin_cds == "") {
		$("#reigai_kaiin_disp").html("");
		return;
	}

	fnc_setMember(reigai_kaiin_cds);
}

function fnc_setMember(kaiin_cd) {
	$('#reigai_btn').colorbox.close();
	if (fnc_is_listed_member(kaiin_cd)) {
		$("#member_"+kaiin_cd+" table").css('background','#FFFF00');
		setTimeout(function(){
			$("#member_"+kaiin_cd+" table").css('background','#FFFFFF');
		}, 500);
		return;
	}

	$.get("/system/category3/inc/ajax_search_kaiin.php",{
		type:'display',
		reigai_kaiin_cds:kaiin_cd
	},function(data) {
		$("#reigai_kaiin_disp").append(data);
		fnc_SetCds();
	});
}

function fnc_DeleteMember(kaiin_cd) {
	$('#member_'+kaiin_cd).remove();
	fnc_SetCds();
}

function fnc_SetCds() {
	var temp_cds = new Array()
	var j = 0;
	$("#reigai_kaiin_disp div").each(function(){
		temp_cds[j++] = ($(this).attr("id").split("_"))[1];
	});

	cds = temp_cds;
	reigai_kaiin_cds = cds.join();
	$("#reigai_kaiin_cds").val(reigai_kaiin_cds);
}

function fnc_is_listed_member(kaiin_cd) {
	var listed = false;
	$("#reigai_kaiin_disp div").each(function(){
		if (kaiin_cd == ($(this).attr("id").split("_"))[1]) listed=true;
	});
	return listed;
}
$(document).ready(function() {

	// 2010.10.26 マルティン
	var dialog_frm;
//	dialog_frm  = '<div style="display:none;"><div id="reigai_kaiin_dialog">&nbsp;店舗:<select id="reigai_shop_list"></select><br/>&nbsp;名前:&nbsp;<input type="text" name="member_name" id="member_name" />&nbsp;&nbsp;&nbsp;ID:&nbsp;<input type="text" name="member_id" id="member_id" /><br/>';

	dialog_frm  = '<div style="display:none;text-align:center;"><div id="reigai_kaiin_dialog">';
	dialog_frm += '<table class="tableBox" style="margin-bottom:0;">';

	dialog_frm += '<tr>';
	dialog_frm += '<th style="width:18%;">店舗</th><td><select id="reigai_shop_list"></select></td>';
	dialog_frm += '<th>管理番号</th><td><input type="text" name="kanri_id" id="kanri_id" class="inputM" style="ime-mode:disabled;" /></td>';
	dialog_frm += '</tr>';

	dialog_frm += '<tr>';
	dialog_frm += '<th style="width:18%;">ID</th><td><input type="text" name="member_id" id="member_id" class="inputM" style="ime-mode:disabled;" /></td>';
	dialog_frm += '<th style="width:18%;">名前</th><td><input type="text" name="member_name" id="member_name" class="inputM" /></td>';
	dialog_frm += '</tr>';

	dialog_frm += '</table>';

	dialog_frm += '<p style="text-align:center;margin:5px 0;"><input type="button" name="btn_search_kaiin" id="btn_search_kaiin" value="　　検索　　" /></p><hr>';
	dialog_frm += '<div id="search_kaiin_result" style="width:100%;height:330px;overflow:auto;margin-top:5px;"></div>';
	dialog_frm += '</div></div>';

	$("body").prepend(dialog_frm);

	$.get("/system/category3/inc/ajax_get_shop_data.php", function(data) {
		$("#reigai_shop_list").html(data);
	});

	reigai_kaiin_cds = $("#reigai_kaiin_cds").val();
	if (reigai_kaiin_cds != "") cds = reigai_kaiin_cds.split(",");

	fnc_DisplayKaiin();

	$("#reigai_btn").colorbox({opacity:0, width:500, height:500, transition:"none", inline:true, overlayClose:false, scrollbars:false, href:"#reigai_kaiin_dialog"});

	$("#btn_search_kaiin").click(function(){
		send_shop_cd = $("#reigai_shop_list").val();
		send_id = $("#member_id").val();
		send_name = $("#member_name").val();
		kanri_id = $("#kanri_id").val();
		$("#search_kaiin_result").empty().load("/system/category3/inc/ajax_search_kaiin.php",{
			shop_cd:send_shop_cd,
			name:send_name,
			id:send_id,
			kanri_id:kanri_id
		});
	});
});
