// 2011.02.14 マルティン
// 会員検索

var preSearchWord = "";


// #################### 会員情報を追加 ####################
function setMember(cd, name) {
	var del = '<span style="color:red;vertical-align:+25%;"><a onclick="delMember();">ｘ</a></span>';
	$("#kaiin_name").html(name+'&nbsp;'+del+'&emsp;');
	$("#kaiin").attr('value',cd);
	$('.NoMember').val("").attr("disabled", true);
	$('.NoMemberTR').hide();
	$('#kaiin_btn').colorbox.close();
}

// #################### 会員情報を削除 ####################
function delMember() {
	$("#kaiin_name").html('');
	$("#kaiin").attr('value','');
	$('.NoMember').attr("disabled", false);
	$('.NoMemberTR').show();
}

// #################### 一覧表 ####################
function getMember(e) {
		var send_val = $("#easySearchWord").val();
		
		$.ajax({
			type: "POST",
			//cache: false,
			url: "ajax_search_kaiin.php",
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

/*	searchForm += '	<tr>';
	searchForm += ' <th style="width:18%;">ID</th><td><input type="text" name="member_id" id="member_id" class="inputM" style="ime-mode:disabled;" /></td>';
	searchForm += ' <th style="width:18%;">名前</th><td><input type="text" name="member_name" id="member_name" class="inputM" /></td>';
	searchForm += '	</tr>';

	searchForm += '	<tr>';
	searchForm += ' <th>電話番号</th><td><input type="text" name="member_tel" id="member_tel" class="inputM" style="ime-mode:disabled;" /></td>';
	searchForm += ' <th>管理番号</th><td><input type="text" name="kanri_id" id="kanri_id" class="inputM" style="ime-mode:disabled;" /></td>';
	searchForm += '	</tr>';

	searchForm += '	<tr>';
	searchForm += ' <th>生年月日</th>';
	searchForm += ' <td colspan="3"><select name="birth_yy" id="birth_yy"><option value="">&nbsp;</option>';
	for (var i=y; i>y-80; i--) {
		searchForm += '<option value="'+i+'">'+i+'</option>';
	}
	searchForm += '</select>年';

	searchForm += '<select name="birth_mm" id="birth_mm"><option value="">&nbsp;</option>';
	for (var i=1; i<=12; i++) {
		var disp_i = (i<10) ? '0'+i : i;
		searchForm += '<option value="'+disp_i+'">'+disp_i+'</option>';
	}
	searchForm += '</select>月';

	searchForm += '<select name="birth_dd" id="birth_dd"><option value="">&nbsp;</option>';
	for (var i=1; i<=31; i++) {
		var disp_i = (i<10) ? '0'+i : i;
		searchForm += '<option value="'+disp_i+'">'+disp_i+'</option>';
	}
	searchForm += '</select>日</td>';
	searchForm += '	</tr>';

	searchForm += '	<tr>';
	searchForm += ' <th>メールアドレス</th><td colspan="3"><input type="text" name="member_mail" id="member_mail" class="inputL" style="ime-mode:disabled;" /></td>';
	searchForm += '	</tr>';
*/

	searchForm += '	<tr>';
	searchForm += ' <td><input type="text" name="easySearchWord" id="easySearchWord" class="inputL" style="width: 98%; font-size: 20px;" /><br />';
	searchForm += ' <small>※キーワードは ID 管理番号 名前 カナ 電話番号 メールアドレスから検索されます。</small></td>';
	searchForm += '	</tr>';

	searchForm += '	</table>';

//	searchForm += '		<p style="text-align:center;"><input type="button" name="btn_search" id="btn_search" value="　　検索　　" /></p>';
	searchForm += '		<div id="result" style="width:100%;height:290px;overflow:auto;"></div>';

	searchForm += '	</div>';
	searchForm += '</div>';

	$("#pTop").prepend(searchForm);

	$("#kaiin_btn").colorbox({opacity:0, width:400, height:420, transition:"none", inline:true, overlayClose:false, scrollbars:false, href:"#dialog"});
	$("#kaiin_btn").bind("click", function(){
		$("#easySearchWord").focus();
	});

//	$("#btn_search").click(function(){
//		$("#result").html('<p style="text-align:center;color:red;">検索中</p>');
//		getMember();
//	});

	$("#easySearchWord").bind("keyup", function(e){
		
		/*if(
		    e.keyCode==27  //esc
		 || e.keyCode==244 //半角全角
		 || e.keyCode==9   //Tab
		 || e.keyCode==242 //Caps
		 || e.keyCode==16  //shift
		 || e.keyCode==17  //ctrl
		 || e.keyCode==112 //F1
		 || e.keyCode==113 //F2
		 || e.keyCode==114 //F3
		 || e.keyCode==115 //F4
		 || e.keyCode==116 //F5
		 || e.keyCode==117 //F6
		 || e.keyCode==118 //F7
		 || e.keyCode==119 //F8
		 || e.keyCode==120 //F9
		 || e.keyCode==121 //F11
		 || e.keyCode==122 //F12
		 ){
			return false;
		}*/
		
		if($("#easySearchWord").val() == preSearchWord) return false;
		preSearchWord = $("#easySearchWord").val();
		
		$("#result").html('<p style="text-align:center;color:red;">検索中</p>');
		getMember(e);
	});


	// ----------------------------------------------------------------------------------------------------------------------------
});
