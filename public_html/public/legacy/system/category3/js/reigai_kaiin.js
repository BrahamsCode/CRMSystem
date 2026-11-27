// ----------------------------------------------------------------------------------------------------------------------------
// 2010.04.19 マルティン
// 例外顧客
// ---------------------------------------------------------------------------------------------------------------------------

var reigai_kaiin_cds;
var cds = new Array();

function fnc_SetKaiinReigaiDialog(page, reigai_kaiin_cds) {
	var search_uniqid = $("#this_search_uniqid").val();
	$("#reigai_kaiin_dialog").load("/system/category3/inc/ajax_reigai_kaiin.php",{
		page:page,
		search_uniqid:search_uniqid,
		reigai_kaiin_cds:reigai_kaiin_cds
	});
}

function fnc_setKaiinKettei() {
	$('#reigai_btn').colorbox.close();
	fnc_DisplayKaiin();
}

function fnc_KaiinClear() {
	reigai_kaiin_cds = "";
	cds = Array();
	fnc_DisplayKaiin();
	$(".reigai_kaiin").attr("checked",true);
}

function fnc_DisplayKaiin() {
	$("#reigai_kaiin_cds").val(reigai_kaiin_cds);
	if (reigai_kaiin_cds == "") {
		$("#reigai_kaiin_disp").html("");
		return;
	}

	$("#reigai_kaiin_disp").load("/system/category3/inc/ajax_reigai_kaiin.php",{
		type:'display',
		reigai_kaiin_cds:reigai_kaiin_cds
	});
}

function fnc_SetCds(elem) {
//	alert (elem.getAttribute('desc'));
	var temp_cds = new Array()
	var j = 0;
	var x = cds.length;
	if (elem.checked) {
		for (i=0; i<x; i++) {
			if (elem.value == cds[i]) {
				delete cds[i];
			} else {
				temp_cds[j++] = cds[i];
			}
		}
		cds = temp_cds;
	} else {
		cds[x] = elem.value;
	}

	reigai_kaiin_cds = cds.join();
}

function fnc_TotalReigaiKaiin() {
	return cds.length;
}

$(document).ready(function() {

	$("body").prepend('<div style="display:none;"><div id="reigai_kaiin_dialog"></div></div>');

	reigai_kaiin_cds = $("#reigai_kaiin_cds").val();
	if (reigai_kaiin_cds != "") cds = reigai_kaiin_cds.split(",");

	fnc_SetKaiinReigaiDialog(1, reigai_kaiin_cds);
	fnc_DisplayKaiin();
	
	$("#reigai_btn").colorbox({opacity:0, width:650, height:650, transition:"none", inline:true, overlayClose:false, scrollbars:false, href:"#reigai_kaiin_dialog"});

});
