/*
2012/09/05 ORITA

画像やCSSの要らない簡易ポップアップウインドウ

*/

//ポップアップ
var closeLock="";
var SetPopWindow = function(marginTop,width,height){

	var $popwindowBack = $("<div id='popwindowBack' />");
	var $popwindow = $("<div id='popwindow' />");
	var $dispTb = $("<div id='dispTb' />");
	var $dbMess = $("<div id='dbMess' />");
	var $popContents = $("<div id='popContents' />");
	var $closeBtn = $("<input type='button' class='blackBtn' id='closeBtn' value='閉じる' />");

	//ポップアップ領域スタイル設定
	$popwindowBack.css({
	width: '100%',
	height: '100%',
	position:'fixed',
	top:'0px',
	left:'0px',
	backgroundColor:'black',
	opacity: '0.5'
	}).appendTo('body');

	$popwindow.css({
	width: '100%',
	height: '100%',
	position:'fixed',
	top:'0px',
	left:'0px',
	}).appendTo('body');

	$dispTb.css({
	marginLeft: 'auto',
	marginRight: 'auto',
	marginTop: marginTop,
	width: width,
	display: 'table',
	textAlign:'center',
	opacity: '1'
	}).appendTo('#popwindow');

	$dbMess.css({
	padding:'10px',
	backgroundColor:'#fff',
	height: height,
	verticalAlign:'middle',
	display: 'table-cell',
	
	borderRadius: '10px',
	webkitBorderRadius: '10px',
	mozBorderRadius: '10px',

	boxShadow: '0px 0px 5px #888888',
	mozBoxShadow: '0px 0px 5px #888888',
	webkitBoxShadow: '0px 0px 5px #888888',
	msFilter: "progid:DXImageTransform.Microsoft.Glow(color=#888888,strength=3) progid:DXImageTransform.Microsoft.Shadow(color=#000000,direction=180,strength=6)",
	filter: "progid:DXImageTransform.Microsoft.Glow(color=#888888,strength=3) progid:DXImageTransform.Microsoft.Shadow(color=#000000,direction=180,strength=6"
	}).appendTo('#dispTb');


	$popContents.css({

	height: height,
	width: width,
	marginLeft: 'auto',
	marginRight: 'auto',
	overflow: 'auto',
	backgroundColor:'#EEE',
	}).appendTo('#dbMess');

	$('#popContents').after($closeBtn)

	$("#popwindow,#closeBtn").bind("click",function(e){
		e.preventDefault();

		closePopWindow();
	});


	$("#dispTb").click(function(e){
	//	return false;
	//	e.preventDefault();
		e.stopPropagation();
	});

}

SetPopWindow.prototype = {
  setAjax : function(url,dataCd,sessId) {
	//情報セット
	$("#popContents").html("<p>読み込み中...</p>");

	$.ajax({
		type: "post",
		url: url,
		data: {
		data_cd:dataCd,
		sessId:sessId
		},
		error: function(){
			alert('通信失敗しました');
		},
		success: function(data) {
			$("#popContents").html(data);
		}
	});
  }

  ,setIframe : function(url,dataCd,sessId) {
	//情報セット
	var $popwindowIframe = $("<iframe id='popwindowIframe' />");

  	$popwindowIframe.css({
	borders:'none',
	width: '98%',
	height: '98%',
	marginLeft: 'auto',
	marginRight: 'auto',
	overflow: 'auto'
	
	}).attr('src', url+"?dataCd="+dataCd).appendTo('#popContents');
  
  }
};

function closePopWindow(){

	$("#popwindowBack").remove();
	$("#popwindow").remove();

}




/**********************************************************/
//更新中画面
/**********************************************************/

var EditDataWindow = function(marginTop){
	var $editDataWindowBack = $("<div id='editDataWindowBack' />");
	var $processStage = $("<div id='processStage' />");
	var $processImage = $("<div id='processImage' >");

	$editDataWindowBack.css({
	width: '100%',
	height: '100%',
	position:'fixed',
	top:'0px',
	left:'0px',
	backgroundColor:'black',
	opacity: '0.5'
	}).appendTo('body');

	$processStage.css({
	width: '100%',
	height: '100%',
	position:'fixed',
	top:'0px',
	left:'0px',
	}).appendTo('body');

	$processImage.css({
	marginLeft: 'auto',
	marginRight: 'auto',
	marginTop: marginTop,
	display: 'table',
	textAlign:'center',
	opacity: '1'
	}).appendTo('#editDataWindowBack');

	$("#processImage").append("<img src='/js/process.gif' />　<span id='processProg' style='color:#fff'>Accessing...</span>");
}
//更新
EditDataWindow.prototype = {
  setAjax : function(url,senddata,sessId) {
	$("#processProg").html("Proccessing...");
	$.ajax({
		type: "post",
		url: url,
		data: senddata,
		error: function(){

			alert('通信失敗しました');
		},
		success: function(data) {
			var res = eval("("+data+")");

			PopFinish(res);
		},
		complete: function(){

		}
	});
  }
};


function closeEditProcessWindow(){
	$("#editDataWindowBack").remove();
	$("#processStage").remove();
}

function PopFinish(res){
	if(res.actFlg=="d"){
		if(res.typeFlg=="del"){
			alert('削除しました');
		}else{

			alert('登録しました');
		}
		closeEditProcessWindow();
		closePopWindow();
		location.reload();

	}else if(res.actFlg=="e"){
		alert(res.comment);

	}else　if (res.responce.match(/registerCheckOk/i)) {

		PopFinishDisp(res);
	}else{
		PopErrorDisp(res);
	}
}

function PopFinishDisp(res){
	alert('登録しました');
	closeEditProcessWindow();
	closePopWindow();
	location.reload();
	//処理は各システム依存可

}


function PopErrorDisp(res){
	alert(res.responce);
	//処理は各システム依存可

}
