/* ▼ユニックID作成
/* 2010.04.27 マルティン
/* ------------------------------------------------------------------------- */
function generateRandomPrefix(n) {
	var alphanum = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
	var s = "";

	for (i=0; i<n; i++) {
		s += alphanum.charAt(Math.floor(Math.random()*62));
	}
	return s;
}

var uniqid = generateRandomPrefix(12);

/* ------------------------------------------------------------------------- */


/* ▼マウスオーバー
/* ------------------------------------------------------------------------- */
function smartRollover() {
	if(document.getElementsByTagName) {
		var images = document.getElementsByTagName("img");

		for(var i=0; i < images.length; i++) {
			if(images[i].getAttribute("src").match("_off."))
			{
				images[i].onmouseover = function() {
					this.setAttribute("src", this.getAttribute("src").replace("_off.", "_on."));
				}
				images[i].onmouseout = function() {
					this.setAttribute("src", this.getAttribute("src").replace("_on.", "_off."));
				}
			}
		}
	}
}

if(window.addEventListener) {
	window.addEventListener("load", smartRollover, false);
}
else if(window.attachEvent) {
	window.attachEvent("onload", smartRollover);
}


/* ▼テキストエリア幅 */
/* ------------------------------------------------------------------------- */
function resize_textarea(ev){
    //if (ev.keyCode != 13) return;
    var textarea = ev.target || ev.srcElement;
    var value = textarea.value;
    var lines = 3;
    for (var i = 0, l = value.length; i < l; i++){
        if (value.charAt(i) == '\n') lines++;
     }
     textarea.setAttribute("rows", lines);
     // window.status = lines;
}


/* ▼インプットなどカーソルあてると中身消えて、離すと元通り */
/* ------------------------------------------------------------------------- */
function onFocus(formName,elementName){
	var target = document.forms[formName].elements[elementName];
	if(target.value == target.defaultValue){
		target.value = "";
		target.style.color='#000000';
	}
}

function onBlur(formName,elementName){
	var target = document.forms[formName].elements[elementName];
	if(target.value == target.defaultValue || target.value == ""){
		target.value = target.defaultValue;
		target.style.color='#999999';
	}
}





// お気に入りエリア
function BookmarkAreaFunc(){
	var target = $("#bookmarkArea ul");
	var keyBtn = $("#openBookmarkArea img");
	var targetDisplay = target.css("display");
	
	switch(targetDisplay){
		case "block":
			target.hide();
			keyBtn.attr("src", "/system/img/bookmark_area_arrow_btn_2.gif");
			break;
		
		case "none":
			target.show();
			keyBtn.attr("src", "/system/img/bookmark_area_arrow_btn_1.gif");
			break;
	}
	
	//target.animate( {height: "toggle", opacity: "toggle"} );
	setCookie('bookmarkArea', target.css("display"), 30);
}


// クッキーを保存する関数
function setCookie(c_name,value,exdays) {
	var exdate=new Date();
	exdate.setDate(exdate.getDate() + exdays);
	var c_value=escape(value) + ((exdays==null) ? "" : "; expires="+exdate.toUTCString()+";");
	document.cookie=c_name + "=" + c_value + " path=/";
}


/* 2017.03.24 マルティン
/* ------------------------------------------------------------------------- */
// ナンバーフォーマット
var numberFormat = function(val, decimals) {

	try {
		decs = parseInt(decimals);
		if ( isNaN(decs) ) decs = 0;
	} catch(e) {
		decs = 0;
	}

	try {
		if ( decs > 0 ) valFixed = val.toFixed(decs);
		else valFixed = val + "";
		var absVal = Math.abs(val);
	} catch(e) {
		return false;
	}

	var formatNumber = "";

	if (absVal < 1000) {
		formatNumber = valFixed;
	} else if ( !isNaN(absVal) ) {
		var valStr = parseInt(absVal, 10) + "";
		while (valStr.length > 3) {
			formatNumber = "," + valStr.substr(valStr.length-3) + formatNumber;
			valStr = valStr.substr(0, valStr.length-3);
		}

		if (val < 0) valStr = "-" + valStr;
		formatNumber = valStr + formatNumber;

		if ( valFixed.indexOf(".") > -1 ) {
			formatNumber += "."+(valFixed).split(".")[1];
		}

	}

	return (formatNumber);
}
/* ------------------------------------------------------------------------- */
