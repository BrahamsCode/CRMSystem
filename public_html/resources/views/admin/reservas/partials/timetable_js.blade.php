{{-- Legacy: makeJS() de system/category9/yoyaku/time_table_function.php (JS en línea del 予約表).
     Variables: $isTablet (bool), $isIpad (bool), $staffWord (string, manageWord スタッフ).
     Las URL ajax_*.php se dejan con la ruta absoluta del legacy (/system/category9/yoyaku/...), que es la que resolvía el JS relativo. --}}
// #################### 数字のみを入力するファンクション 始 ####################
var no_digit = /\D/g;
var isTablet = {{ $isTablet ? '1' : '0' }};  // 2011.12.06 ohno tabletのUAを判別
var isiPad   = {{ $isIpad ? '1' : '0' }};

function replace_value(data) {
	data.value = data.value.replace(no_digit, '');
}

function numbersonly(myfield, e, dec)
{
	var key;
	var keychar;

	if (window.event)
	   key = window.event.keyCode;
	else if (e)
	   key = e.which;
	else
	   return true;
	keychar = String.fromCharCode(key);

	// control keys
	if ((key==null) || (key==0) || (key==8) ||
	    (key==9) || (key==13) || (key==27) )
	   return true;

	// numbers
	else if ((("0123456789").indexOf(keychar) > -1))
	   return true;

	// decimal point jump
	else if (dec && (keychar == "."))
	   {
	   myfield.form.elements['dec'].focus();
	   return false;
	   }
	else
	   return false;
}
// #################### 数字のみを入力するファンクション 終 ####################

function wopen(staff, date){
	var code = $("#shop").val();
	if (code == "" ) return;

	var touch_start_y;

	if (date != "") {
		td = date;
	}

	// iOSの背景スクロール対策
	//var props = {
	//	overflow: 'hidden',
	//	height: '100%',
	//	position: 'fixed'
	//}
	//$('body').css(props);
	//$('html').css(props);

	// 2017.09.04 マルティン CTI連動を追加
	var customerCd = $('#customerCd').val() || '';
	var extraQS = (customerCd != '' ) ? '&kaiin=' + customerCd : '';


	var osVer = "iPhone";

	// 2011.12.06 ohno tablet用にボタンスクリプトを分岐
	if(isiPad) {
		window.open({{ \Illuminate\Support\Js::from(route('admin.reservas.create')) }}+'?shop='+code+'&staff='+staff+'&td='+td, '_blank');
	} else if ( isTablet ) {
		roid.popUp({{ \Illuminate\Support\Js::from(route('admin.reservas.create')) }}+'?shop='+code+'&staff='+staff+'&td='+td);
	} else if ( navigator.userAgent.indexOf(osVer)>0 ) {
		window.open( {{ \Illuminate\Support\Js::from(route('admin.reservas.create')) }}+'?shop='+code+'&staff='+staff+'&td='+td + extraQS );
	} else {
		openPopWindow({{ \Illuminate\Support\Js::from(route('admin.reservas.create')) }}+'?shop='+code+'&staff='+staff+'&td='+td + extraQS, '600', '');
	}

	var element = document.getElementById('popWinContents');
	var height = element.offsetTop;
	element.addEventListener('touchend', e => {
		console.log("touch")
		e.preventDefault()
		console.log("aaa",element.clientHeight)
		// var height = window.pageYOffset
		var iframe = document.getElementById('popWinContentsIframe');
		var iframeDOM = iframe.contentWindow.document;
		console.log(height)
		iframeDOM.getElementById('container').scrollTo(0, height)
	}, { passive: false });

}

function changeShop(shop) {
	$("#shop").val(shop).change();
}

function saveData(cd) {
	var checked_recalculation = 0;
	var bartime = 0;

	if ( $("#recalculation_check:checked").val() == '1' ) {
		checked_recalculation = 1;

		$('.base_menu_cd').each(function(index){
			if ($(this).attr('checked')) bartime += ( Number( $(this).attr('time') ) > 0) ? Number( $(this).attr('time') ) : 0 ;
		});

		$('.option_menu_cd').each(function(index){
			if ($(this).attr('checked')) bartime += ( Number( $(this).attr('time') ) > 0) ? Number( $(this).attr('time') ) : 0 ;
		});

	}


	var base_menu = option_menu = free_field = '';
	var c=0;

	$('.base_menu_cd').each(function(index){
//		if ($(this).attr('checked')) base_menu = $(this).val();
		if ($(this).attr('checked')) base_menu += (c++ == 0) ? $(this).val() : ','+$(this).val();
	});

	c = 0;
	$('.option_menu_cd').each(function(index){
		if ($(this).attr('checked')) option_menu += (c++ == 0) ? $(this).val() : ','+$(this).val();
	});

	$('.free_field').each(function(index){
		free_field += (index == 0) ? $(this).attr('name')+'='+$(this).val() : '&'+$(this).attr('name')+'='+$(this).val();
	});

	var name = ($("#yoyakuName").length > 0) ? $("#yoyakuName").val() : '';
	var nameKana = ($("#name_kana").length > 0) ? $("#name_kana").val() : '';
	var tel = ($("#yoyakuTel").length > 0) ? $("#yoyakuTel").val() : '';
	var mail = ($("#yoyakuMail").length > 0) ? $("#yoyakuMail").val() : '';
	var people = ($("#yoyakuPeople").length > 0) ? $("#yoyakuPeople").val() : '';
	var mark = ($("#yoyakuMark").length > 0) ? $("#yoyakuMark").val() : '';
	var startTime = $("#start_hour").val() + ":" + $("#start_min").val();
	var endTime = $("#end_hour").val() + ":" + $("#end_min").val();
	// var people = $("#people").val();
	var comment = $("#yoyaku_comment").val();
	var tb_staff_cd = ($('.reserveStaffEditArea .reserveStaffEditSelected').length > 0) ? $('.reserveStaffEditArea .reserveStaffEditSelected').attr('cd') : '';
	var moushikomi_date = $("#edit_moushikomi_date").val();

	// 2011.10.31 マルティン ・ メール配信を追加
	var sendMailFlg = ($("#sendMailFlg").is(":checked")) ? '1' : '';

	var reg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
	var reg2 = /^[ァ-ヶー\ー0-9\-\(\)\s]+$/;

	if (base_menu == '') {
		alert('ベースメニューを選択して下さい。');
		return false;
	}

	if ($("#yoyakuName").length > 0 && name == '') {
		alert('名前を入力して下さい。');
		return false;
	}

	if (nameKana != '' && reg2.test(nameKana) == false) {
		alert('全角カタカナで入力してください。');
		return false;
	}

	if (mail != '' && reg.test(mail) == false) {
		alert('メールアドレスは異常な形式です。');
		return false;
	}

	if (startTime >= endTime) {
		alert("時間を確認して下さい！！");
		return false;
	}

	$.get("/system/category9/yoyaku/ajax_yoyaku_register.php?"+free_field, {
		cd: cd,
		name: name,
		name_kana: nameKana,
		tel: tel,
		mail: mail,
		startTime: startTime,
		endTime: endTime,
		sess: sess,
		sendMailFlg: sendMailFlg,
		comment: comment,
		base_menu: base_menu,
		option_menu: option_menu,
		people: people,
		mark: mark,
		bartime: bartime,
		checked_recalculation: checked_recalculation,
		tb_staff_cd: tb_staff_cd,
		moushikomi_date: moushikomi_date
		}, function(retData) {
			if (retData == "") {
				if(isiPad) {
					window.opener.location.reload();
				}
				location.reload();
			} else {
				alert("エラーが発生しました。。。"+retData);
			}
	});
}

function deleteData(cd) {
	var resp = confirm("削除してもよろしいですか？");
	if (resp) {
		$.get("/system/category9/yoyaku/ajax_yoyaku_register.php", {
			cd: cd,
			sess: sess,
			act: 'del'
			}, function(retData) {
				if (retData == "") {
					location.reload();
				} else {
					alert("エラーが発生しました。。。"+retData);
				}
		});
	}
}

function closePopForm(){
	$("#popFormWrap").css("display","none");
	$(document).unbind('mousedown');
}

function closepopWindow() {
	var iPadFlg = (isiPad) ? 1 : 0;

	if(iPadFlg) {
		window.opener.location.reload();
		window.close();
	} else {
		$(".main1").css("display","block");
		$("#popWindow").removeClass("popWindowIn");
		$("#popWindow").addClass("popWindowOff");
		$("#basicContainer").css("display","block");
		$("#iPadOptionContainer").css("display","none");
		$(document).unbind('mousedown');
	}
}

function setPopPosition(thisObj) {
	var iPadFlg = (isiPad) ? 1 : 0;

//	alert ("thisObj:"+thisObj.offset().left + ", " + thisObj.offset().top + ', scroll:'+thisObj.scrollLeft() + ", "+thisObj.scrollTop() + ', timeTableDiv:'+$("#timeTableDiv").offset().left + ", " + $("#timeTableDiv").offset().top+ ', scroll:'+$("#timeTableDiv").scrollLeft() + ", "+$("#timeTableDiv").scrollTop());
	var left = (thisObj.offset().left + $("#timeTableDiv").scrollLeft() + thisObj.width() - 34) - $("#popFormWrap").width();
	if (left < 0) left = 0;
//	var left = (thisObj.offset().left + $("#timeTableDiv").scrollLeft() + thisObj.width() - 40);
	var top = (thisObj.offset().top - $("#timeTableDiv").offset().top + $("#timeTableDiv").scrollTop() + 15);


	if(iPadFlg) {
		$("#popFormWrap").css("max-width","initial");
		$("#popFormWrap").css("height","100vh");
		$("#popFormWrap").css("top","0");
		// $("#popFormWrap").css("bottom","0");
		$("#popFormWrap").css("right","0");
		$("#popFormWrap").css("left","initial");
		$("#popFormWrap").css("position","fixed");
		$("#popFormWrap").css("overflow-y","scroll");

		$("#yoyakuCustomerInfo").css("max-height","initial");
		$("#yoyakuCustomerInfo").css("width","100vw");
		$("#yoyakuCustomerInfo").css("margin","10px auto");

		$("#popFormWrap").css("width","100vw");
		$("#popFormWrap").css("z-index","2");
	} else {
		$("#popFormWrap").css("left", left + "px");
		$("#popFormWrap").css("top", top + "px");
		$("#popFormWrap").css("width","280px");
		$("#timeTableDiv").scrollLeft(left);
		$("#timeTableDiv").scrollTop(top - 20);
	}
}


//2011.09.20 yoshida 既存顧客との関連付け
function relatedCustomer(yoyakuCd, customerCd){
	if(yoyakuCd=="" || customerCd=="") return false;

	jConfirm( "この内容で登録してもよろしいですか？\n\n登録しない場合は['キャンセル']ボタンを押して下さい", "登録", function(ret){
		if (ret) {
			$.ajax({
				type: "POST",
				//cache: false,
				url: "/system/category9/yoyaku/ajax_related_customer.php",
				data: "yoyaku_cd="+yoyakuCd+"&customer_cd="+customerCd,

				success: function(data){
					if(data == 1){
						location.reload(true);
					}
				}
			});

		}else{
			return false;
		}
	});
}

function selDest(cd, objs) {

	if ( objs == "changeDatetime" ) {
		if(isiPad) {
			$("#iPadOptionContainer").load("/system/category9/yoyaku/ajax_getYoyakuDatetimeForm.php?cd="+cd, function(){setPopPosition(thisObj);});
			$("#basicContainer").css("display","none");
			$("#iPadOptionContainer").css("display","block");
		} else {
			$("#popFormWrap").load("/system/category9/yoyaku/ajax_getYoyakuDatetimeForm.php?cd="+cd, function(){setPopPosition(thisObj);});
		}
	} else {

		var inside = '';

		inside += '<span style="font-weight:bold;color:red;">移動先'+{{ \Illuminate\Support\Js::from(e($staffWord)) }}+'の選択</span>';


		// iPadの時はテーブルレイアウト止める
		if(isiPad) {
			inside += '<div id="iPadContextStaffContainer">';
			$(".staffData").each(function(){
				if ($(this).attr("cd") != thisObj.attr("staff")) {
					inside += '<div onclick="moveObj(\''+cd+'\', \''+$(this).attr("cd")+'\', \''+objs+'\')">'+$(this).attr("name")+'</div>';
				}
			});

			inside += '</div>'
		} else {
			inside += '<table width="100%" cellpading="2">';
			$(".staffData").each(function(){
				if ($(this).attr("cd") != thisObj.attr("staff")) {
					inside += '<tr onmouseover="this.style.color=\'#FFFFFF\';this.style.backgroundColor=\'#0000FF\';" onmouseout="this.style.color=\'#000000\';this.style.backgroundColor=\'#FFFFFF\';">';
					inside += '<td style="cursor:pointer;" onclick="moveObj(\''+cd+'\', \''+$(this).attr("cd")+'\', \''+objs+'\')">'+$(this).attr("name")+'</td></tr>';
				}
			});
			inside += '</table>';
		}

		if(isiPad) {

			$("#basicContainer").css("display","none");
			$("#iPadOptionContainer").css("display","block");
			$("#iPadOptionContainer").css("height","90%");
			$("#iPadOptionContainer").css("-webkit-transform","translateZ(0px)");

			$("#iPadOptionContainer").html(inside);
		} else {
			$("#popFormWrap").html(inside);
			setPopPosition(thisObj);
		}
	}

}


function moveObj(cd, newStaff, objs) {
//	alert (cd + " --> " + dest + " --> " + objs);
	closePopForm();
	$.get("/system/category9/yoyaku/ajax_move_objs.php",{
		cd:cd,
		newStaff:newStaff,
		objs:objs
	}, function(retData) {
		if (retData == "") {
			if(isiPad) {
				window.opener.location.reload();
			}
			location.reload();
		} else {
			alert("エラーが発生しました。。。"+retData);
		}
	});
}


// ********************************************************************* //
// 2012.01.24 マルティン
// 予約日時の変更を追加
function changeReserveDatetime(cd) {
	var reserveDate = $("#new_reserve_year").val() + "/" + $("#new_reserve_month").val() + "/" + $("#new_reserve_day").val();
	var startTime = $("#new_start_hour").val() + ":" + $("#new_start_min").val();
	var sendMailFlg = ($("#sendMailFlg").is(":checked")) ? '1' : '';

	$.get("/system/category9/yoyaku/ajax_move_objs.php", {
		cd: cd,
		reserveDate: reserveDate,
		startTime: startTime,
		sendMailFlg: sendMailFlg
		}, function(retData) {
			if (retData == "") {
				location.reload();
			} else {
				alert("エラーが発生しました。。。"+retData);
			}
	});
}



function changeReserveDateForiPad(cd) {
	$("#iPadOptionContainer").load("/system/category9/yoyaku/ajax_time_table.php",{
			cd:cd
		}, function() {
			$("#basicContainer").css("display","none");
			$("#yoyakuCustomerPopCloseBtn").parent("div").css("display","none");
			$("#iPadOptionContainer").css("display","block");
			$("#yoyakuCustomerInfo").css("max-height","initial");
			$("#yoyakuCustomerInfo").css("width","100%");

			$("#yoyakuCustomerInfo").scrollTop(0);
			$("#popFormWrap").focus();
		});
}
// ********************************************************************* //


var thisObj;
$(document).ready(function() {

	$.ajaxSetup({ cache: false }); // 2013.01.18【大野】Ajaxのキャッシュを切る

	// iPadの時は従来の設定だとhoverやクリックアクションが混ざるので処理を変える
	if(isiPad) {
		var touched = false;
		var touch_time = 0;

		// longタップの判定時間 (1秒)
		var touchduration = 1000;
		var yoyakuObj = document.getElementsByClassName("yoyakuObj");

		[].forEach.call(yoyakuObj,function(x){
			x.addEventListener('touchend', function(e) {
				window.open( {{ \Illuminate\Support\Js::from(route('admin.reservas.timetable')) }} + "?d=" + td + "&shop=" + $("#shop").val() + "&open_yoyaku_cd=" + $(e.target).attr("cd"), "_blank" );
			});
			x.addEventListener('virtual_open', function(e) {
				popupReserveData(e);
			});
		});


		var changeTouchmove = function(e) {
			e.preventDefault();
		}

		if ( location.href.indexOf("open_yoyaku_cd=") != -1 ) {
			// var query_param = location.href.split("open_yoyaku_cd=");
			var query_param = $('#open_yoyaku_cd').val();
			//console.log( query_param );
			for ( i=0; i < yoyakuObj.length; i++ ) {
				if( yoyakuObj[i].getAttribute("cd") == query_param ) {
					var event = document.createEvent( "HTMLEvents" );
					event.initEvent("virtual_open", false, true);
					yoyakuObj[i].dispatchEvent(event);
				}
			}
		}

	} else {

		$(".yoyakuObj, .yoyakuObjPast").mouseover(function(e){
			popupReserveData(e);
		});

		$(".yoyakuObj, .yoyakuObjPast").mouseout(function(e){
			$("#popWindow").css("display","none");
		});

		$(".yoyakuObj").click(function(e){
			popupEditReserveData(e);
		});

		$(".yoyakuObj").bind('contextmenu', function(e){
			contextMenu(e)
		});

	}

	// 予約データクリック時にポップアップする予約編集メニュー
	function popupEditReserveData(e) {
		$(document).bind('mousedown', function(event){
			var target = $(event.target);
			if (target[0].id != "popFormWrap" && target.parents('#popFormWrap').length == 0) {
				closePopForm();
				$(document).unbind('mousedown');
			}
		});

		$(window).bind('resize', function(event){
			setPopPosition(thisObj);
		});

		thisObj = $(e.target);

		$("#popWindow").css("display","none");
		$("#popFormWrap").html("お待ちください...");
		setPopPosition(thisObj);
		$("#popFormWrap").css("display","block");

		var sendCd = thisObj.attr("cd");
		$("#popFormWrap").load("/system/category9/yoyaku/ajax_time_table.php",{
			cd:sendCd
		}, function() {
			setPopPosition(thisObj);
			$("#yoyakuCustomerInfo").scrollTop(0);
			$("#popFormWrap").focus();
		});
	}

	// 予約データ右クリック時のポップアップメニュー
	function contextMenu(e) {
		e.preventDefault();
			thisObj = $(e.target);
			$(window).bind('resize', function(event){
				setPopPosition(thisObj);
			});

			$(document).bind('mousedown', function(event){
				var target = $(event.target);
				if (target[0].id != "popFormWrap" && target.parents('#popFormWrap').length == 0) {
					closePopForm();
				}
			});

			var inside = '<table cellpading="2">';
			inside += '<tr onmouseover="this.style.color=\'#FFFFFF\';this.style.backgroundColor=\'#0000FF\';" onmouseout="this.style.color=\'#000000\';this.style.backgroundColor=\'#FFFFFF\';">';
			inside += '<td style="cursor:pointer;" onclick="selDest(\''+thisObj.attr("cd")+'\', \'changeDatetime\')">予約日時を変更する</td></tr>';

			if (hyoujiType == "day") {
				inside += '<tr onmouseover="this.style.color=\'#FFFFFF\';this.style.backgroundColor=\'#0000FF\';" onmouseout="this.style.color=\'#000000\';this.style.backgroundColor=\'#FFFFFF\';">';
				inside += '<td style="cursor:pointer;" onclick="selDest(\''+thisObj.attr("cd")+'\', \'1\')">この一件を移動する</td></tr>';
				inside += '<tr onmouseover="this.style.color=\'#FFFFFF\';this.style.backgroundColor=\'#0000FF\';" onmouseout="this.style.color=\'#000000\';this.style.backgroundColor=\'#FFFFFF\';">';
				inside += '<td style="cursor:pointer;" onclick="selDest(\''+thisObj.attr("cd")+'\', \'today\')">この日の予約を一括で移動する</td>';
				inside += '<tr onmouseover="this.style.color=\'#FFFFFF\';this.style.backgroundColor=\'#0000FF\';" onmouseout="this.style.color=\'#000000\';this.style.backgroundColor=\'#FFFFFF\';">';
				inside += '<td style="cursor:pointer;" onclick="selDest(\''+thisObj.attr("cd")+'\', \'all\')">この日以降全件を一括で移動する</td>';
			}
			inside += '</tr></table>';

			$("#popWindow").css("display","none");

			$("#popFormWrap").html(inside);
			setPopPosition(thisObj);
			$("#popFormWrap").css("display","block");

			return false;
	}

	// 予約データをマウスオーバーしたときに表示されるポップアップ
	function popupReserveData(e) {

		if(isiPad) {
			//$("#popWindow").addClass("popWindowIn");
			$("#popWindow").css("display","block");
			$(".main1").css("display","none");
			$("#popWindow").focus();
			$("#iPadOptionContainer").css("height","90%")


			$("#iPadCustomer").css("display","block");
			$("#popWindow").css("max-width","initial");
			$("#popWindow").css("height","100vh");

			$("#popWindow").css("top","0");
			$("#popWindow").css("left","0");
			$("#popWindow").css("position","fixed");
			$("#popWindow").css("width","100vw");
			$("#popWindow").css("z-index","5");

			$("#yoyakuCustomerInfo").css("max-height","initial");
			$("#yoyakuCustomerInfo").css("width","30vw");
			$("#yoyakuCustomerInfo").css("height","90%");
			$("#yoyakuCustomerInfo").css("margin","10px auto");

		} else {
			$("#popWindow").css("display","block");
			$("#popWindow").css("left",(e.pageX + 2) + "px");
			// 2013.01.28【大野】画面最下部だと表示が見切れるのを修正
			var posY = ($('#timeTableDiv').height() < (e.pageY + $("#popWindow").height())) ? e.pageY - ($("#popWindow").height() + 2): e.pageY + 2;
			$("#popWindow").css("top",posY + "px");
		}

		var ninzuStr = "";
		ppl = new Number($(this).attr("people"));
		if(ppl > 1){
			ninzuStr = "他<span style=\"font-weight:bold;\">" + (ppl -1) + "</span>名";
		}
		thisObj = $(e.target);

		// 2012.10.03【大野】どこから予約を入れたかを記録するフィールドを追加
		// 2013.07.22【大野】携帯予約に「POS予約」も追加。フリー予約を別表示としてフラグ追加。
		var entryType = "";
		switch (thisObj.attr("entryType")) {
			case "1":
			case "2":
			case "4":
			case "100":
				entryType = "（携帯予約：指名）";
				break;

			case "5":
			case "6":
			case "101":
				entryType = "（携帯予約：フリー）";
				break;
		}
		$(".customerInfo").html("<span style=\"font-weight:bold;\">" + thisObj.attr("kokyaku") + "</span> 様 " + entryType + ninzuStr);
		$("#startTime").html( thisObj.attr("startTime") );
		$("#endTime").html(thisObj.attr("endTime") );
		$("#comment").html(thisObj.attr("comment"));
		$("#menu").html(thisObj.attr("menu"));

		// 2012.11.14【大野】フリー項目の表示を追加
		// 2013.02.07【大野】フリー項目がひとつのみの場合に表示のリセットが正常にかかっていなかったのを修正
		var freeKoumoku = thisObj.attr("free_koumoku");
		var freeKoumokuArr = (freeKoumoku != '' && typeof freeKoumoku !== "undefined" ) ? freeKoumoku.split('<>'): [];
		var i, max = freeKoumokuArr.length;

		$('#popWindow').find("span[id^='free_koumoku_']").empty();

		for ( i=0; i<max; i++ ) {
			$("#free_koumoku_"+ (i + 1)).html(freeKoumokuArr[i]);
		}

		// iPadの時は同時にcontextも表示する
		if(isiPad) {
			$("#iPadContext").empty();
			var inside = '';
			inside += '<div id="iPadContextInner">';
			inside += '<table cellpading="2" id="iPadContextMenu">';
			inside += '<tr style="background:#4D4D4D;">';
			inside += '<td style="color:#fff; text-align: center;">メニュー</td></tr>';
			inside += '<tr onmouseover="this.style.color=\'#FFFFFF\';this.style.backgroundColor=\'#0000FF\';" onmouseout="this.style.color=\'#000000\';this.style.backgroundColor=\'#FFFFFF\';">';
			inside += '<td style="cursor:pointer;" onclick="changeReserveDateForiPad(\''+thisObj.attr("cd")+'\')">予約情報を編集する</td></tr>';
			// inside += '<tr onmouseover="this.style.color=\'#FFFFFF\';this.style.backgroundColor=\'#0000FF\';" onmouseout="this.style.color=\'#000000\';this.style.backgroundColor=\'#FFFFFF\';">';
			// inside += '<td style="cursor:pointer;" onclick="selDest(\''+thisObj.attr("cd")+'\', \'changeDatetime\')">予約日時を変更する</td></tr>';

			if (hyoujiType == "day") {
				//inside += '<tr onmouseover="this.style.color=\'#FFFFFF\';this.style.backgroundColor=\'#0000FF\';" onmouseout="this.style.color=\'#000000\';this.style.backgroundColor=\'#FFFFFF\';">';
				//inside += '<td style="cursor:pointer;" onclick="selDest(\''+thisObj.attr("cd")+'\', \'1\')">この一件を移動する</td></tr>';
				inside += '<tr onmouseover="this.style.color=\'#FFFFFF\';this.style.backgroundColor=\'#0000FF\';" onmouseout="this.style.color=\'#000000\';this.style.backgroundColor=\'#FFFFFF\';">';
				inside += '<td style="cursor:pointer;" onclick="selDest(\''+thisObj.attr("cd")+'\', \'today\')">この日の予約を一括で移動する</td>';
				inside += '<tr onmouseover="this.style.color=\'#FFFFFF\';this.style.backgroundColor=\'#0000FF\';" onmouseout="this.style.color=\'#000000\';this.style.backgroundColor=\'#FFFFFF\';">';
				inside += '<td style="cursor:pointer;" onclick="selDest(\''+thisObj.attr("cd")+'\', \'all\')">この日以降全件を一括で移動する</td>';
			}
			inside += '</tr></table>';
			inside += '</div>';
			// inside += '<div id="closePopWindowButton" class="btnStyleM" ontouchstart="closepopWindow()">閉じる</div>'


			$("#iPadContext").css("display","block");
			$("#iPadContext").css("float","left");
			$("#iPadContext").append(inside);
		}

	}


});
