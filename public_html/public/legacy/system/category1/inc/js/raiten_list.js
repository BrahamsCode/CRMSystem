// ----------------------------------------------------------------------------------------------------------------------------
// 2011.08.17 マルティン
// 既にある来店データを選択する枠
// ---------------------------------------------------------------------------------------------------------------------------

function showRaitenDialog(page) {
	var kaiin_cd = $("#kaiin_cd").val();
	$("#raiten_dialog").load("/system/category1/inc/ajax_raiten_list.php",{
		page:page,
		kaiin_cd:kaiin_cd
	});
}

function setRaiten(cd, shop_cd) {
	$("#related_features_cd").remove();
//	$("#mainForm").append('<input type="hidden" value="'+cd+'" name="raiten_cd" id="raiten_cd">');
	$("#raiten_cd").val(cd);

	$("#selected_shop_cd").val(shop_cd).change();
	$('#raiten_btn').colorbox.close();
/*
	$("#raiten_timestamp").load("/system/category1/inc/ajax_raiten_list.php",{
		type:'display',
		raiten_cd:cd
	});
*/
}

$(document).ready(function() {

	$("body").prepend('<div style="display:none;"><div id="raiten_dialog"></div></div>');

	showRaitenDialog(1);

	$("#raiten_btn").colorbox({
		opacity:0,
		width:400,
		height:400,
		transition:"none",
		inline:true,
		overlayClose:false,
		scrollbars:false,
		href:"#raiten_dialog"
	});

/*
	$(document).bind('cbox_open', function(){
		document.getElementsByTagName("html")[0].style.overflow = "hidden";
	});

	$(document).bind('cbox_closed', function(){
		document.getElementsByTagName("html")[0].style.overflow = "auto";
	});
*/
});
