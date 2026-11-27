{{--
    Legacy: system/category9/yoyaku/yoyakuPrint.php (予約プリント: iframe del 予約表 con リアルタイム印刷).
    Sin variables. Cada 3 s carga ajaxYoyakuPrint.php (admin.reservas.printer.poll) en #result; la respuesta trae el
    <script> que envía el XML a la impresora OFSC local (ofscURL). Se omite el código muerto de cupones del legacy
    (.couponBtn / ajax_print_coupon.php: no hay botones ni ese archivo en category9).
--}}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ja" xml:lang="ja">
<head>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="robots" content="noindex" />
<meta name="robots" content="nofollow" />
<meta name="robot-control" content="deny-all" />
<meta name="robots" content="none" />
<meta http-equiv="content-style-type" content="text/css" />
<meta http-equiv="content-script-type" content="text/javascript" />



<script type="text/javascript" src="{{ asset('legacy/system/js/jquery-1.3.2.min.js') }}"></script>


<script type="text/javascript"><!--

function crawl(){
	tb_shop_cd_send = $("#tb_shop_cd").val();


	$("#result").load({{ \Illuminate\Support\Js::from(route('admin.reservas.printer.poll')) }},{
		tb_shop_cd:tb_shop_cd_send,
		_token:{{ \Illuminate\Support\Js::from(csrf_token()) }}
	});
}




//var ofscURL = 'http://192.168.1.205/cgi-bin/ofsc/service.cgi';
var ofscURL = 'http://192.168.3.204/cgi-bin/ofsc/service.cgi';

printContent = '';



//**********************************************************
//* ここからメッセージ送信に関する機能
//**********************************************************

// NORMALモード	通常レスポンス解析、リトライ処理なし
var NORMAL_MODE = "NORMAL";
// RETRYモード	通常レスポンス解析、リトライ処理あり
var RETRY_MODE = "RETRY";
// CheckHealthモード	CheckHealth用レスポンス解析、リトライ処理なし
var CHECKHEALTH_MODE = "CHECKHEALTH";
// 印刷の成否フラグ	true ：印刷成功	false：印刷失敗
var SuccessFlag = true;

/**
 * XMLHttpRequestオブジェクトを作成
 *
 * @return	object	HTTPリクエスト/レスポンスに対応するオブジェクト
 */
function createXMLHttpRequest() {
	var xmlhttp = null;
	if (window.XMLHttpRequest) {
		// 他のブラウザ
		xmlhttp = new XMLHttpRequest();
	}
	else if (window.ActiveXObject) {
		try {
			// MSXML2以降
			xmlhttp = new ActiveXObject("msxml2.XMLHTTP");
		} catch(e) {
			// MSXML用
			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		}
	}
	return xmlhttp;
}




/**
 * OFSC Driverに要求メッセージを送信
 *
 * @param	soap		string	要求メッセージのXML文字列
 * 			modeFlag	string	処理モード
 */

function sendMessage(soap,modeFlag) {
	// XMLHttpRequestを作成
	xmlhttp = createXMLHttpRequest();
	// リクエストを初期化
	xmlhttp.open("POST", ofscURL, true);
	// リクエストにヘッダを設定
	xmlhttp.setRequestHeader("Content-Type", "text/xml; charset=UTF-8");
	// SOAPActionヘッダを設定
	xmlhttp.setRequestHeader("SOAPAction", '""');
	// リクエスト送信
	xmlhttp.send(soap);
}



$(document).ready(function(){

	setInterval('crawl()',3000);
});




--></script>

<title>予約プリント</title>
</head>
<body>



<input type="hidden" name="tb_shop_cd" value="1" />

<div id="result"></div>


</body>
</html>
