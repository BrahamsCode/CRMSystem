var csResp;
var VFDExists = false; // カスタマーディスプレーがあるかどうか
var VFDmaker = null; // カスタマーディスプレーメーカ

// ==========================================================================
// POSViewerアプリとのコミュニケーション
function fireEventForCS(funcName, sendData) {
	csResp = null;
	var event = document.createEvent('MessageEvent');

	var data = encodeURI(sendData);
	if (event.initMessageEvent) {
		var origin = window.location.protocol + '//' + window.location.host;
		event.initMessageEvent(funcName, true, true, data, origin, 1234, window, null);
	} else if (event.initEvent) {
		event = new MessageEvent(funcName, {
			'view': window,
			'bubbles': false,
			'cancelable': false,
			'data': data
		});
	}

	document.dispatchEvent (event);

	return csResp;
}
// ==========================================================================


// ==========================================================================
// 印刷実行関数（共通）
function _print(from, printer) {
	var sendData = '"' + from + '" "' + printer + '"';
	var error = "";

	try {

		var resp = fireEventForCS("csPrint", sendData);

		var jsonData;
		try {
			jsonData = JSON.parse(resp);
			//jsonData = jQuery.parseJSON( csResp );
		} catch (e) {
			error = "印刷用アプリをインストールして下さい。"; // C:\POSSystem\bin\printData.exeファイルが存在しない。
		}

		if ( jsonData && jsonData.error ) {
			// エラーの場合
			error = jsonData.error;
		} // end if (jsonData)

	} catch (e) {
		//error = "プリンターが接続されていません。";
		error = "POSViewerが必要";
	}

	return (error);
}
// ==========================================================================


// ==========================================================================
// フェリカリーダー
function getNFCData(secs) {
	if ( typeof(secs) == "undefined" ) secs = 15; // デフォールトは15秒
	else secs = parseInt(secs, 10);

	if ( isNaN(secs) ) secs = 15;

	var sendData = '"' + (secs * 1000) + '"';
	var error = "";

	var jsonData = null;

	try {

		var resp = fireEventForCS("csGetNFCData", sendData);

		try {
			jsonData = JSON.parse(resp);
		} catch (e) {
			error = "フェリカリーダー用アプリをインストールして下さい。"; // C:\POSSystem\bin\getNFCData.exeファイルが存在しない。
		}

	} catch (e) {
		error = "POSViewerが必要";
	}

	if ( error ) {
		jsonData = { "error" : error };
	}

	return (jsonData);
}
// ==========================================================================


// ==========================================================================
// BEEP
function beep(times, freq, duration) {
	if (typeof(times) == "undefined" || times == "") times = "0";
	if (typeof(freq) == "undefined" || freq == "") freq = "0";
	if (typeof(duration) == "undefined" || duration == "") duration = "0";

	var sendData = '"' + times + '" "' + freq + '" "' + duration + '"';
	try {
		var resp = fireEventForCS("csBeep", sendData);
	} catch (e) {
		//console.log("POSViewerが必要");
	}
}
// ==========================================================================


// ==========================================================================
// カスタマーディスプレー

// 初期化
function startVFD() {
	VFDExists = true;
	VFDmaker = "pana";

	var resp = sendVFD(); // panasonicカスタマディスプレーを探す
	var jsonData;
	try {
		jsonData = JSON.parse(resp);
	} catch(e) {
		VFDExists = false;
	}
	if (jsonData && jsonData.error) { // ない場合通常のVFDを探す
		VFDmaker = "";
		resp = sendVFD();
		try {
			jsonData = JSON.parse(resp);
		} catch (e) {
			VFDExists = false;
		}
		if (jsonData && jsonData.error) VFDExists = false; //VFDがない。
	}
}

// 表示
function sendVFD(line1, line2) {
	if (!VFDExists) return; // カスタマーディスプレーがない場合

	if (typeof(line1)=="undefined") line1 = "";
	if (typeof(line2)=="undefined") line2 = "";
	line1 += " ";
	line2 += " ";

	var sendData = '"' + line1 + '" "' + line2 + '" "' + VFDmaker + '"';

	try {

		var resp = fireEventForCS("csSendVFD", sendData);
		return resp;

	} catch (e) {
		//console.log("POSViewerが必要");
		return false;
	}
}
// ==========================================================================


// ==========================================================================
// 2014.05.02 高山マルティン
// プリンター設定を追加
function setDefaultPrinter(printerName) {
	if ( typeof(printerName) == "undefined" ) return;
	try {
		fireEventForCS("csSetDefaultPrinter", printerName);
	} catch (e) {}
}

function setPrinterMargins(t, r, b, l) {
	var top = parseFloat(t, 10);
	if ( isNaN(top) ) top = 0;
	var right = parseFloat(r, 10);
	if ( isNaN(right) ) right = 0;
	var bottom = parseFloat(b, 10);
	if ( isNaN(bottom) ) bottom = 0;
	var left = parseFloat(l, 10);
	if ( isNaN(left) ) left = 0;
	var sendData = top + ',' + right + ',' + bottom + ',' + left;
	try {
		var resp = fireEventForCS("csSetPrinterMargins", sendData);
	} catch (e) {}
}

function setPrinterOrientation(orientation) {
	try {
		var resp = fireEventForCS("csSetPrinterOrientation", orientation);
	} catch (e) {}
}

function setPrinterHeaderStr(left, center, right) {
	if ( typeof(left) == "undefined" ) left = "";
	if ( typeof(center) == "undefined" ) center = "";
	if ( typeof(right) == "undefined" ) right = "";
	var sendData = encodeURI(left) + '>' + encodeURI(center) + '>' + encodeURI(right);
	try {
		var resp = fireEventForCS("csSetPrinterHeaderStr", sendData);
	} catch (e) {}
}

function setPrinterFooterStr(left, center, right) {
	if ( typeof(left) == "undefined" ) left = "";
	if ( typeof(center) == "undefined" ) center = "";
	if ( typeof(right) == "undefined" ) right = "";
	var sendData = encodeURI(left) + '>' + encodeURI(center) + '>' + encodeURI(right);
	try {
		var resp = fireEventForCS("csSetPrinterFooterStr", sendData);
	} catch (e) {}
}

function setPrinterPaperType(paperType) {
	try {
		var resp = fireEventForCS("csSetPrinterPaperType", paperType);
	} catch (e) {}
}

// 2014.09.04 高山 マルティン インストールしているプリンタ名を取得する
function getPrinterList() {
	var printerList = null;
	try {
		var resp = fireEventForCS("csGetPrinterList");
		printerList = JSON.parse(decodeURIComponent(resp.replace(/%5C/g,"%5C%5C"))); // %5C = '\' 共有プリンタ名に含みます
	} catch (e) {}

	return printerList;
}
// ==========================================================================


// ==========================================================================
// 電源
function reboot() {
	try {
		fireEventForCS("csReboot", "");
	} catch (e) {
		//console.log("POSViewerが必要");
	}
}

function poweroff() {
	try {
		fireEventForCS("csPowerOff", "");
	} catch (e) {
		//console.log("POSViewerが必要");
	}
}
// ==========================================================================


// =============================================================================
// DBバックアップ
function backupDb(serialNo) {
	if ( typeof(serialNo) == "undefined" ) serialNo = "";

	var maxFiles = 10;
	var response = null;
	try {
		var resp = fireEventForCS("csBackupDb", serialNo + " " + maxFiles);

		/** デバッグ用 **/
		var jsonData;
		try {
			jsonData = JSON.parse(resp);
		} catch (e) {
			response = {"error" : "pg_dumpがありません。"};
		}
		if (jsonData) {
			response = jsonData;
		} // end if (jsonData)

	} catch (e) {
		response = {"error" : "DBバックアップ処理失敗しました。"};
	}

	return (response);
} // end function backupDb

// DB復元
function restoreDb(filename) {
	if ( typeof(filename) == "undefined" ) return ({"error" : "ファイル名がありません。"});

	var response = null;
	try {
		var resp = fireEventForCS("csRestoreDb", filename);

		/** デバッグ用 **/
		var jsonData;
		try {
			jsonData = JSON.parse(resp);
		} catch (e) {
			response = {"error" : "psqlがありません。"};
		}
		if (jsonData) {
			response = jsonData;
		} // end if (jsonData)

	} catch (e) {
		response = {"error" : "DB復元処理失敗しました。"};
	}

	return (response);
} // end function restoreDb

// バックアップリスト
function getFileList(serialNo) {
	if ( typeof(serialNo) == "undefined" ) serialNo = "";

	var response = null;
	try {
		var resp = fireEventForCS("csGetFileList", serialNo);
		resp = resp.replace(/\\/g, '\\\\\\\\');

		/** デバッグ用 **/
		var jsonData;
		try {
			jsonData = JSON.parse(resp);
		} catch (e) {
			response = {"error" : "不明なエラー発生しました。"};
		}
		if (jsonData) {
			response = jsonData;
		} // end if (jsonData)

	} catch (e) {
		response = {"error" : "DBバックアップリスト取得失敗しました。"};
	}

	return (response);
} // end function getFileList
// =============================================================================
