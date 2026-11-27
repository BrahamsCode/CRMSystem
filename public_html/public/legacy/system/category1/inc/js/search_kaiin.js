// 2011.02.14 マルティン
// 会員検索

var preSearchWord = "";

// #################### 会員情報を追加 ####################
function setMember(cd, name) {
	var del = '<a onclick="delMember();" style="color:#FF0000;border:1px solid #FF0000;padding:3px;">解除</a>';
	$("#searchKaiin_nameDisp").html(name+'&nbsp;'+del+'&emsp;');
	$("#searchKaiin_cd").val(cd);
	$("#searchKaiin_name").val(name);
	$('#searchKaiin_btn').colorbox.close();
}

// #################### 会員情報を削除 ####################
function delMember() {
	$("#searchKaiin_nameDisp").html('');
	$("#searchKaiin_cd").val('');
	$("#searchKaiin_name").val('');
}

// #################### 一覧表 ####################
function getMember(e) {
	var send_val = $("#easySearchWord").val();
	
	$.ajax({
		type: "POST",
		//cache: false,
		url: "/system/category1/inc/ajax_search_kaiin.php",
		data: "w="+send_val,
		
		success: function(data){
			$("#result").html(data);
		}
	});
}

$(document).ready(function() { 
	// ----------------------------------------------------------------------------------------------------------------------------
	var y=new Date().getFullYear();
	var searchForm = '';
	searchForm += '<div style="display:none;">';
	searchForm += '	<div id="dialog" class="form">';

	searchForm += '	<table class="tableBox" style="margin-bottom:0;">';

	searchForm += '	<tr>';
	searchForm += ' <td><input type="text" name="easySearchWord" id="easySearchWord" class="inputL" style="width: 98%; font-size: 20px;" /><br />';
	searchForm += ' <small>※キーワードは ID 管理番号 名前 カナ 電話番号 メールアドレスから検索されます。</small></td>';
	searchForm += '	</tr>';

	searchForm += '	</table>';

	searchForm += '		<div id="result" style="width:100%;height:290px;overflow:auto;"></div>';

	searchForm += '	</div>';
	searchForm += '</div>';

	$("#pTop").prepend(searchForm);

	$("#searchKaiin_btn").colorbox({opacity:0, width:400, height:420, transition:"none", inline:true, overlayClose:false, scrollbars:false, href:"#dialog"});
	$("#searchKaiin_btn").bind("click", function(){
		$("#easySearchWord").focus();
	});

	$("#easySearchWord").bind("keyup", function(e){
		if($("#easySearchWord").val() == preSearchWord) return false;
		preSearchWord = $("#easySearchWord").val();
		
		$("#result").html('<p style="text-align:center;color:red;">検索中</p>');
		getMember(e);
	});


	// ----------------------------------------------------------------------------------------------------------------------------
});
