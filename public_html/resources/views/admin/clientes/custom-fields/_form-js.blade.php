{{-- Legacy: JS de search_form_new.php / search_form_update.php (disp() y addForms(): opciones según la フォームタイプ).
     Variables:
       $tableEachformClass  clase del 表示値 de テーブル generado por JS ('writeArea inputM' en el alta, 'writeArea inputL' en la edición) --}}
<script language="JavaScript">
<!--
function disp(parts){
	if(parts.value=='ラジオボタン'||parts.value=='セレクト'||parts.value=='チェックボックス'){
		document.getElementById('optionDisp').innerHTML='オプション数:<input type=\"text\" name=\"flds_row\" id=\"flds_row\" class=\"inputS\" value=\"\" style=\"ime-mode: disabled\" />\n<input type=\"button\" onClick=\"addForms(document.getElementById(\'flds_row\').value,\'\')\" value=\"オプション数をセット\">';
	}else if(parts.value=='テーブル'){
		document.getElementById('optionDisp').innerHTML='行数:<input type=\"text\" name=\"flds_col\" id=\"flds_col\" class=\"inputS\" value=\"\" style=\"ime-mode: disabled\" />\n 列数:<input type=\"text\" name=\"flds_row\" id=\"flds_row\" class=\"inputS\" value=\"\" style=\"ime-mode: disabled\" />\n<input type=\"button\" onClick=\"addForms(document.getElementById(\'flds_row\').value,\'テーブル\')\" value=\"行数・列数をセット\">';
		document.getElementById('optionDisp').innerHTML+='<p>表示値の中に、特性の記述をするとセレクトボックスの作成を行います。</p>\n';
		document.getElementById('optionDisp').innerHTML+='<p>例：性別セレクトボックスの作成<br />select[項目名]:valueの値1,valueの値2,valueの値3・・・</p>';
		$("#unit").attr("disabled", "disabled");
	}else{
		document.getElementById('optionDisp').innerHTML='';
		document.getElementById('eachformData').innerHTML='';
		if($("#unit").attr("disabled")){
			$("#unit").removeAttr("disabled");
		}
	}
}

function addForms(row, parts){
	var eachFromCreate = "";
	var eachform = "";
	var eachsend = "";
	for(each_row = 0;each_row < row ;each_row ++){
		if(document.getElementById("eachform"+each_row)){ eachform = document.getElementById('eachform'+each_row).value; }else{eachform = "";}
		if(document.getElementById("eachsend"+each_row)){ eachsend = document.getElementById('eachsend'+each_row).value; }else{eachsend = "";}
		if(parts == 'テーブル'){
			eachFromCreate += "表示値：<input type=\"text\" name=\"eachform"+each_row+"\" id=\"eachform"+each_row+"\" class=\"{{ $tableEachformClass }}\" value=\""+ eachform +"\" />\n　";
			eachFromCreate += 'スタイル：<select name="eachsend'+each_row+'" id="eachsend'+each_row+'">';
			eachFromCreate += '{!! \App\Services\Customers\CustomFieldService::styleOptions() !!}<br />';
			eachFromCreate += '</select>\n　';
			eachFromCreate += '単位：<input type="text" name="eachunit'+each_row+'" id="eachunit'+each_row+'" class="inputS" />';
			eachFromCreate += '<br />';
		}else{
			eachFromCreate += "表示値：<input type=\"text\" name=\"eachform"+each_row+"\" id=\"eachform"+each_row+"\" onchange=\"document.getElementById('eachsend"+each_row+"').value=this.value\" class=\"writeArea inputM\" value=\""+ eachform +"\" />\n　";
			eachFromCreate += "送信値：<input type=\"text\" name=\"eachsend"+each_row+"\" id=\"eachsend"+each_row+"\" class=\"inputM\" value=\""+ eachsend +"\" /><br />";
		}
	}
	document.getElementById('eachformData').innerHTML=eachFromCreate;
}
//-->
</script>
