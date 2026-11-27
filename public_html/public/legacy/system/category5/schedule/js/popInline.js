

//ポップアップの縦横設定
var htmlOverflowMoto = "";//もともとのoverflow
var popHeightFlg = 0;

var appendHtml = '';
appendHtml += '<div id="appendArea">\n';
appendHtml += '<div id="popWinWrap">\n';

appendHtml += '<div id="popHeadBar">\n';
appendHtml += '<a class="popCloseBtn popUpWinClose">×</a>\n';
appendHtml += '</div>\n';

appendHtml += '<div id="popWinContents"></div>\n';

//appendHtml += '<div class="btnBox">\n';
//appendHtml += '<input type="button" id="popUpWinReloadClose" value="ページを更新して閉じる" />\n';
//appendHtml += '<input type="button" class="popUpWinClose" value="閉じる" />\n';
//appendHtml += '</div>\n';

appendHtml += '</div>\n';
appendHtml += '</div>\n';


function appendPopContents(){
	$("body").append(appendHtml);
}


function openPopWindow(addr, popWidth, popHeight){
	
	if(popHeight == ""){
		popHeightFlg = 1;
	}else{
		popHeightFlg = 0;
	}
	
	$("html").css("overflow", "hidden");
	
	$("#appendArea").width($(document).width());
	$("#appendArea").height($(document).height());
	//$("#appendArea").css("background", "rgba(0, 0, 0, 0.7)");
	//$("#appendArea").css("background", "#000000");
	//$("#appendArea").fadeTo("slow", 0.33);
	
	//イベントの登録
	$(window).bind("resize", windowResizeToPop);
	//$("#appendArea").bind("click", popUpWinClose);
	/*$(window).keydown(function(e){
		if(e.keyCode == 27){
			popUpWinClose(e);
		}
	});*/
	$(window).bind("keydown", function(e){
		if(e.keyCode == 27){
			popUpWinClose(e);
		}
	});
	
	
	$("#appendArea").css("display", "block");
	$("#popWinContents").html('<iframe id="popWinContentsIframe" src="'+addr+'" frameborder="0" width="'+popWidth+'" height="'+popHeight+'"></iframe>');
	$("#popWinWrap").css("width", parseInt(popWidth) + 8);
	
	if(popHeightFlg == 1){
		$("#popWinContentsIframe").height($(window).height()-75);
	}
}


//リサイズイベント
function windowResizeToPop(){
	
	$("html").css("overflow", "hidden");

	var htmlWidth = $("html").width();
	var htmlHeight = $("html").height();
	var docWidth = $(document).width();
	var docHeight = $(document).height();

	/*
	if(htmlWidth <= docWidth){
		$("#appendArea").width(htmlWidth);
	}else{
		$("#appendArea").width(docWidth);
	}
	if(htmlHeight <= docHeight){
		$("#appendArea").height(htmlHeight);
	}else{
		$("#appendArea").height(docHeight);
	}*/
	$("#appendArea").width(docWidth);
	$("#appendArea").height(docHeight);
	
	if(popHeightFlg == 1){
		$("#popWinContentsIframe").height($(window).height()-75);
	}
	
}

//更新せずに閉じる場合
function popUpWinCloseBtn(targetBtn){
	$(targetBtn).click(function(){
		popUpWinClose("");
	});
}




function popUpWinClose(e){
	if (e != "") {
		var target = $(e.target);
		if (target[0].id == "popWinWrap" || target.parents('#popWinWrap').length > 0) {
			return false;
		}
	}
	$(window).unbind("resize", windowResizeToPop());
	$("#appendArea").css("display", "none");
	$("#popWinContents").html('');
	//$("#appendArea").unbind("click", popUpWinClose);
	$(window).unbind("keydown");
	
	$("html").css("overflow", htmlOverflowMoto);
}

//更新して閉じる場合
function popUpWinReloadCloseBtn(targetBtn){
	$(targetBtn).click(function(){
		popUpWinReloadClose("");
	});
}

function popUpWinReloadClose(e){
	if (e != "") {
		var target = $(e.target);
		if (target[0].id == "popWinWrap" || target.parents('#popWinWrap').length > 0) {
			return false;
		}
	}
	$(window).unbind("resize", windowResizeToPop());
	$("#appendArea").css("display", "none");
	$("#popWinContents").html('');
	//$("#appendArea").unbind("click", popUpWinClose);
	location.reload(true);
}


function popIni(){
	
	htmlOverflow = $("html").css("overflow");
	
	appendPopContents();

	popUpWinCloseBtn(".popUpWinClose");
	popUpWinReloadCloseBtn("#popUpWinReloadClose");
}





