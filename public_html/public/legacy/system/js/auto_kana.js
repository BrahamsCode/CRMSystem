<!--カナ変換------------------------------------------------------------------------->
/*  auto_kana.js var 1.0.0
 *  Presented by 2008 Source Create co.,ltd.(http://www.srce.jp)
 *  Author Akira Yoshimaru(akira@srce.jp)
 *  
 *--------------------------------------------------------------------------*/

//--------設定--------
var convFlag  = 1;      //モードフラグ ひらがな→0　カタカナ→1
var nameField = 'name'; //名前のID
var kanaField = 'kana'  //カナのID
//--------------------

var baseVal = "";
function setKana(nameId,kanaId) {
	var newVal = $(nameId).value;
	if (baseVal == newVal){return;}
	if (newVal == "") {
  $(kanaId).value="";
		  baseVal = "";
		  return;
	}
	
	var addVal = newVal;
	for(var i=baseVal.length; i>=0; i--) {
		if (newVal.substr(0,i) == baseVal.substr(0,i)) {
			addVal = newVal.substr(i);break;
		}
	}
	baseVal = newVal;
	var addkana = addVal.replace( /[^ 　ぁあ-んァー]/g, "" );
	if (addkana == ""){return;}
	if(convFlag){addkana = convKana(addkana);}
	$(kanaId).value += addkana;
}

function loopTimer(){
	setKana($("name"),'kana');
	timer = setTimeout("loopTimer()",30);
}

function convKana(val){
	var c, a = [];
	for(var i=val.length-1;0<=i;i--){
			c = val.charCodeAt(i);
			a[i] = (0x3041 <= c && c <= 0x3096) ? c + 0x0060 : c;
	}
	return String.fromCharCode.apply(null, a);
}

var timer = false;
window.onload = function(){
	loopTimer();
	$(kanaField).onkeyup = setKana(nameField,kanaField);
}