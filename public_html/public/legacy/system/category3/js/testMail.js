// ----------------------------------------------------------------------------------------------------------------------------
// 2011.08.22 マルティン
// テストメール送信先選択
// ---------------------------------------------------------------------------------------------------------------------------

var testMail_cds;
var cds = new Array();

function showTestMailDialog(page, testMail_cds) {
	var search_uniqid = $("#this_search_uniqid").val();
	$("#testMail_dialog").load("/system/category3/inc/ajax_testMailList.php",{
		page:page,
		search_uniqid:search_uniqid,
		testMail_cds:testMail_cds
	});
}

function testMailKettei() {
	$("#testMailMsg").html("");
	$("#testMail_cds").val(testMail_cds);
	if (testMail_cds == '') {
		$("#testMailMsg").html("送信先メールを選択されていません！");
	} else {
		$('#testMail_btn').colorbox.close();
		sendTestMail();
	}
}

/*
function fnc_KaiinClear() {
	testMail_cds = "";
	cds = Array();
	fnc_DisplayKaiin();
	$(".testMail").attr("checked",true);
}

function fnc_DisplayKaiin() {
	$("#testMail_cds").val(testMail_cds);
	if (testMail_cds == "") {
		$("#testMail_disp").html("");
		return;
	}

	$("#testMail_disp").load("/system/category3/inc/ajax_testMailList.php",{
		type:'display',
		testMail_cds:testMail_cds
	});
}
*/

function setCds(elem) {
//	alert (elem.getAttribute('desc'));
	var temp_cds = new Array()
	var j = 0;
	var x = cds.length;
	if (!elem.checked) {
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

	testMail_cds = cds.join();
}

function fnc_TotalReigaiKaiin() {
	return cds.length;
}

$(document).ready(function() {

	$("body").prepend('<div style="display:none;"><div id="testMail_dialog"></div></div>');

	testMail_cds = $("#testMail_cds").val();
	if (testMail_cds != "") cds = testMail_cds.split(",");

	showTestMailDialog(1, testMail_cds);
//	fnc_DisplayKaiin();
	
	$("#testMail_btn").colorbox({opacity:0, width:500, height:550, transition:"none", inline:true, overlayClose:false, scrollbars:false, href:"#testMail_dialog"});

});
