//
//  exLink　
//  外部サイトへのリンクに新しいウィンドウで開くアイコンを自動でつける
//
//  迷惑メール対策実験室
//　http://mailspam.cocolog-nifty.com/blog/
//
//　サンプル＆設置説明ページ
//  http://www.nikkei-azabu10.com/exlink/
//

var myHost = location.hostname;
var myArea = "";
var exLink = '<img src="img/link-icon.gif" width="12" height="10" alt="リンクを新しいウインドウで開きます" title="このアイコンをクリックするとリンクを新しいウインドウで開きます" rel="nofollow">';
var pdfType = 1;
var exPdf = '<img src="img/pdf-icon.gif" width="13" height="13" border="0" align="bottom" alt="PDFアイコン" title="このアイコンをクリックするとPDFを新しいウインドウで開きます" rel="nofollow">';
var yesNW = "ynw";
var noNW = "nnw";
var myLimit = 200;
// 　↑　設定ここまで　↑

function exlink(){
	var myLink,myFlg=0,myNW,linkIcon,k=1,i,j;
	var myLinks = document.getElementsByTagName("a");

	for(i = 0; i < myLinks.length; i++){
		myLink = myLinks[i];
	if(!myLink.innerHTML.match(/<img/i) && myLink.className != noNW && myLink.href != ""){
		if(myLink.href.indexOf("\.pdf") > 0){
			if (pdfType == 1){
			myFlg = 1;
			linkIcon = exPdf;
				}
			} else if (myLink.className == yesNW){
			myFlg = 1;
			linkIcon = exLink;
		} else if (myLink.href.indexOf(myHost) == -1 && myArea == "") {
			myFlg = 1;
			linkIcon = exLink;
			}

	if(myFlg == 1){
		myNW = document.createElement("a");
		myNW.innerHTML = linkIcon;
		myNW.href = myLink.href;
		myNW.target = "_blank";
		myLink.parentNode.insertBefore(myNW,myLink.nextSibling);
		myFlg = 0;
		 i++;
			}
	}
	k++;
		if (k > myLimit) {      //リンクチェック数
       			 break;
		}
    }
	for(j = 0; j < myArea.length; j++){
		var checkArea = myArea[j];
	var myLinks = document.getElementById(checkArea).getElementsByTagName("a");

	for(i = 0; i < myLinks.length; i++){
		 myLink = myLinks[i];
	if(!myLink.innerHTML.match(/<img/i) && myLink.className != noNW && myLink.href.indexOf("\.pdf") == -1 && myLink.href != ""){
	if (myLink.href.indexOf(myHost) == -1) {
			myFlg = 1;
			linkIcon = exLink;
			}

	if(myFlg == 1){
		myNW = document.createElement("a");
		myNW.innerHTML = linkIcon;
		myNW.href = myLink.href;
		myNW.target = "_blank";
		myLink.parentNode.insertBefore(myNW,myLink.nextSibling);
		myFlg = 0;
		 i++;
			}
	}
	k++;
		if (k > myLimit) {      //リンクチェック数
       			 break;
		}
      }
   }
}
window.onload = function(){exlink();}