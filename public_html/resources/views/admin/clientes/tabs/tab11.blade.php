{{-- Legacy: system/category1/inc/detail_tab11.php (pestaña 購買履歴 de la ficha; parcial sin @extends).
     El POS está fuera de alcance: el HTML y las llamadas AJAX se conservan tal cual; las rutas legacy
     /system/category1/inc/ajax_connectPosData.php, ajax_connectPosKoubaiData.php y ajax_sendPosKoubaiMemoData.php
     responden vacío (PosGateway nulo), así que se ve 「購買履歴はありません。」.
     Variables:
       $customer             (modelo tb_customer: cd)
       $posShops             (colección/array de tb_shop con coalesce(pos_domain,'') <> '' ordenados por cd: cd, pos_domain, kaishamei)
       $posKoubaiFormName    (string|null: tb_search_form.name de admin_master.pos_koubai_tb_search_form_cd)
       $posKoubaiOptions     (colección/array de tb_customer_sub (disp_flg '1', del cliente, de ese tb_search_form): cd, text_data;
                              vacía si no hay campo configurado o no hay filas)
     Selección inicial de tb_customer_sub_cd: request('tb_customer_sub_cd') (enlace 購買履歴 de la pestaña de categoría).
     JS: jQuery UI dialog (#popUpDialog), jAlert (jquery.alerts.js) que carga la ficha. --}}
@php
    $posShops = collect($posShops ?? []);
    $posKoubaiOptions = collect($posKoubaiOptions ?? []);
    $startShop = $posShops->first();
@endphp


<style type="text/css">
<!--
.tableBox th {
/*	width: auto;*/
}
-->
</style>

<!--マイクーポンtable start-->
<div class="tabBody">

@if ($posShops->count() > 0)
<select name="pos_tenpo" id="pos_tenpo">
@foreach ($posShops as $posShop)
<option cd="{{ $posShop['cd'] }}" value="{{ $posShop['pos_domain'] }}">{{ $posShop['kaishamei'] }}</option>
@endforeach
</select>&emsp;
@endif

@if ($posKoubaiOptions->count() > 0)
{{ $posKoubaiFormName }}：
<select name="tb_customer_sub_cd" id="tb_customer_sub_cd">
<option value="">&nbsp;</option>
@foreach ($posKoubaiOptions as $posKoubaiOption)
<option value="{{ $posKoubaiOption['cd'] }}"@selected($posKoubaiOption['cd'] == request('tb_customer_sub_cd'))>{{ $posKoubaiOption['text_data'] }}</option>
@endforeach
</select>
@endif


<div id="koubaiKekkaResult" style="margin:10px;"></div>

<script type="text/javascript">

var start_limit = 10;
var start_offset = 0;
var rirekiObj = null;

$(document).ready(function(){

	$("#pos_tenpo, #tb_customer_sub_cd").bind("change",function(){
		getPosData(
			$("#pos_tenpo").val(),
			 $("#pos_tenpo option:selected").attr("cd"),
			 $("#pos_tenpo option:selected").text(),
			 start_limit,
			 start_offset
			);
	});

@if ($startShop && ($startShop['pos_domain'] ?? '') != '')
	getPosData(@json((string) $startShop['pos_domain']), @json((string) $startShop['cd']), @json((string) $startShop['kaishamei']), start_limit, start_offset);
@endif
});


// =============================================================================
// ナンバーフォーマット関数
// 2012.02.01 高山マルティン
function numberFormat(val) {
	var absVal = Math.abs(val);

	var formatNumber = "";
	//if (val.length < 4) {
	if (absVal < 1000) {
		formatNumber = val + ""; // stringへ
	} else if ( !isNaN(absVal) ) {
		var valStr = absVal + ""; // stringへ
		while (valStr.length > 3) {
			formatNumber = "," + valStr.substr(valStr.length-3) + formatNumber;
			valStr = valStr.substr(0, valStr.length-3);
		}
		if (val < 0) valStr = "-" + valStr;
		formatNumber = valStr + formatNumber;
	}

	return (formatNumber);
}
// =============================================================================

// 日付の整形*****************************
function getNichijiText(jsonData) {
	var dateData = "";
	var hidukeArr = '';
	var jikanArr = '';
	var nichijiText = '';

	dateData = jsonData.order_date;
	dateArr = dateData.split(" ");

	hidukeArr = dateArr[0].split("/");
	jikanArr = dateArr[1].split(":");

	nichijiText = '';
	nichijiText += hidukeArr[0]+"年";
	nichijiText += hidukeArr[1]+"月";
	nichijiText += hidukeArr[2]+"日";
	if(jsonData.youbi) nichijiText += "("+getYoubi(jsonData.youbi)+")";
	nichijiText += " "+jikanArr[0]+"時";
	nichijiText += jikanArr[1]+"分";

	return nichijiText;
}
// ***************************************

// UNIXTIMEを日付に変換*****************************
function toTimestamp(val) {
	var timestamp = "";
	if ( val > 0 ) {
		var date = new Date();
		date.setTime(val*1000);// ミリ秒対策

		var year = date.getFullYear();
		var month = date.getMonth() + 1; 
		var day = date.getDate();
		var hours = date.getHours();
		var minutes = date.getMinutes();
		var seconds = date.getSeconds();
		var utcday = date.getUTCDay();
		
		timestamp = hours + "時" + minutes + "分";
	}
	
	return timestamp;
}
// *************************************************


function getPosData(posDom, shopCd, shopName, limit, offset){
	$("#koubaiKekkaResult").html('<p>Loading...</p>');

	var tb_customer_sub_cd = ( $("#tb_customer_sub_cd").length > 0 ) ? $("#tb_customer_sub_cd").val() : '';

	rirekiObj = new Array();
	$.ajax({
		type: "POST",
		dataType: "json",
		url: "/system/category1/inc/ajax_connectPosData.php?url="+posDom+"&shop_cd="+shopCd+"&tb_customer_cd={{ $customer->cd }}&limit="+limit+"&offset="+offset+"&tb_customer_sub_cd="+tb_customer_sub_cd, // 2014.09.25 マルティン 購買履歴に連係するフィールドを追加
		success: function(json) {
			if(json.data){
				html = '';
				
				//html += '■'+shopName;
				
				html += '<table class="tableBox">';
				html += '<tr>';
				html += '<th>' + '日付' + '</th>';
				html += '<th>' + '天気' + '</th>';
				
				if(json.mode == 2){// 飲食のとき
					html += '<th>' + '人数' + '</th>';
					html += '<th>' + 'メモ' + '</th>';
				}// if(json.mode == 2)--end
				
				html += '<th>' + '会計金額' + '</th>';
				html += '<th></th>';
				html += '</tr>';
				
				for(i=0; i<json.data.length; i++){
					html += '<tr>';
					
					html += '<td>';
					//html += nichijiText;
					html += getNichijiText(json.data[i]);
					html += '</td>';
					
					html += '<td>';
					if(json.data[i].tenki) html += getTenki(json.data[i].tenki);
					html += '</td>';
					
					if(json.mode == 2){// 飲食のとき
						html += '<td align="right">';
						if(json.data[i].people) html += json.data[i].people+'人';
						html += '</td>';
						
						html += '<td>';
						if(json.data[i].comment) html += "有";
						html += '</td>';
					}// if(json.mode == 2)--end
					
					html += '<td align="right">';
					html += numberFormat(json.data[i].order_sum)+' 円';
					html += '</td>';
					rirekiObj[i] = json.data[i];
					//html += '<td align="center"><input type="button" class="btn btnStyleS" value="詳細" onclick="rirekiShousaiDialog(\''+posDom+'\',\''+json.data[i].cd+'\', \''+nichijiText+'\', \''+getTenki(json.data[i].tenki)+'\', \''+json.data[i].people+'\', \''+json.data[i].people_num+'\', \''+json.data[i].comment+'\', \''+json.data[i].order_sum+'\')"></td>';
					html += '<td align="center"><input type="button" class="btn btnStyleS" value="詳細" onclick="rirekiShousaiDialog(\''+posDom+'\','+i+')"></td>';
					html += '</tr>';
				}
				
				html += '</table>';
				
				
				html += '<div style="overflow: hidden;">';
				
				// 前への処理
				if(offset > 0){
					html += '<input type="button" id="preRireki" class="btn btnStyleS" value="前へ" onclick="getPosData(\''+posDom+'\',\''+shopCd+'\',\''+shopName+'\',\''+limit+'\',\''+(Number(offset) - Number(limit))+'\');" style="float:left;">';
				}
				
				// 次への処理
				if(json.data.length >= limit){
					html += '<input type="button" id="nextRireki" class="btn btnStyleS" value="次へ" onclick="getPosData(\''+posDom+'\',\''+shopCd+'\',\''+shopName+'\',\''+limit+'\',\''+(Number(offset) + Number(limit))+'\');" style="float:right;">';
				}
				
				$("#koubaiKekkaResult").html(html);
				
				
			}else{
				if(offset == 0){
					$("#koubaiKekkaResult").html("購買履歴はありません。");
				}else{
					//次へボタンを省く
					$("#nextRireki").remove();
				}
			}
		},
		error: function() {
			$("#koubaiKekkaResult").html("購買履歴はありません。");
		}
	});
}


//function rirekiShousaiDialog(posDom, cd, nichiji, tenki, people, people_num, comment, order_sum){
function rirekiShousaiDialog(posDom, index){
	var cd = rirekiObj[index].cd;
	var nichiji = getNichijiText(rirekiObj[index]);
	var tenki = getTenki(rirekiObj[index].tenki);
	var people = rirekiObj[index].people;
	var comment = rirekiObj[index].comment;
	var order_sum = rirekiObj[index].order_sum;

	var tax = rirekiObj[index].tax;
	var taxFlg = rirekiObj[index].tax_flg;

	var used_points = parseInt(rirekiObj[index].used_points, 10);
	if (isNaN(used_points)) used_points = 0;

	var variable_waribiki = parseInt(rirekiObj[index].variable_waribiki, 10);
	if (isNaN(variable_waribiki)) variable_waribiki = 0;

	$( "#popUpDialog" ).dialog( "destroy" );
	
	$("#popUpDialog").html("読み込み中...");
	
	$.getJSON (
		"/system/category1/inc/ajax_connectPosKoubaiData.php?url="+posDom+"&cd="+cd, 
		
		function(json) {
			$( "#popUpDialog" ).html(json);
		
		
			if(json.data){
				
				html = '';
			
				if (rirekiObj[index].people_num) {
					var ninzuUchiwake = rirekiObj[index].people_num.split("<>");
					var dansei = ninzuUchiwake[0];
					var josei = ninzuUchiwake[1];
					var kodomo = ninzuUchiwake[2];
				}
			
				html += '<table class="tableBox" style="margin-bottom: 10px;">';
				html += '<tr><th>会計日</th><td>'+nichiji+' ('+tenki+')</td></tr>';
				if (rirekiObj[index].sejutsu_start_time && rirekiObj[index].sejutsu_end_time && rirekiObj[index].sejutsu_start_time!="" && rirekiObj[index].sejutsu_end_time!="") {
					var startTime = parseInt(rirekiObj[index].sejutsu_start_time, 10);
					if (isNaN(startTime)) startTime = 0;
					var endTime = parseInt(rirekiObj[index].sejutsu_end_time, 10);
					if (isNaN(endTime)) endTime = 0;
					var elapsed = endTime - startTime;
					var mins = 0;
					if (elapsed > 0) {
						mins = Math.floor(elapsed / 60);
					} // end if
					html += '<tr><th>施術時間</th><td>開始：'+toTimestamp(startTime)+' 終了：'+toTimestamp(endTime)+'('+mins+'分)</td></tr>';
				}
				html += '<tr><th>会計金額</th><td>'+numberFormat(order_sum)+' 円';
				if (rirekiObj[index].ikkatsu_tb_order_main_cd != "" && rirekiObj[index].ikkatsu_tb_order_main_cd != cd) html += '&emsp;<span style="color:#FF0000;">(一括精算で支払していただいています。)</span>'
				html += '</td></tr>';
				
				if(json.mode == 2){// 飲食のとき
					if(people) html += '<tr><th>人数</th><td>'+people+'人';
					
					html += '<small>';
					if(dansei) html += '&emsp;男性：'+dansei+'人';
					if(josei) html += '&emsp;女性：'+josei+'人';
					if(kodomo) html += '&emsp;子供：'+kodomo+'人';
					html += '</small>';
					
					html += '</td></tr>';
					
					if(comment) html += '<tr><th>業務メモ</th><td>'+comment+'</td></tr>';
				}// end if(json.mode == 2)
				
				html += "</table>";
				
				
				
				html += '■注文明細';
				
				html += '<div style="border: 1px solid #CCCCCC;height:300px;overflow-y:auto;">';
				html += '<form id="sendDataForm">';
				html += '<table class="tableBox" style="margin: 0px; table-layout: fixed;">';
				html += '<tr>';
				html += '<th width=120>メニュー</th>';

				// ===================================================
				// 2012.08.16 マルティン
				if(json.mode == 1){// 美容のとき
					html += '<th width=80>担当</th>';
				}
				// ===================================================

				html += '<th width=30>個数</th>';
				html += '<th width=50>単価</th>';

				// ===================================================
				// 2012.08.16 マルティン
				if(json.mode == 1){// 美容のとき
					html += '<th width=50>割引</th>';
					html += '<th width=50>ｻｰﾋﾞｽ</th>';
				}
				// ===================================================

				html += '<th width=50>金額</th>';
				
				if(json.mode == 1){// 美容のとき
					html += '<th>メモ</th>';
					html += '<th>顧客へのコメント</th>';
				}
				
				html += '</tr>';
				
				var tempCd = cd; // これが変わると他人の分になります。
				for(i=0; i<json.data.length; i++){
					
					// 2012.12.03 マルティン
					// 一括払いの顧客名（子）を追加
					// （後付でエラーを防ぐためにトライ・キャッチに囲む）
					try {
						if ( json.mode == 1 && tempCd != json.data[i].tb_order_main_cd ) {
							tempCd = json.data[i].tb_order_main_cd;
							dispName = (json.data[i].customer_name != "") ? json.data[i].customer_name+"様の分" : ("00000000"+tempCd).substr(-8);
							html += '<tr>';
							html += '<td colspan="7" style="font-weight:bold;text-align:left;padding: 5px;border: 1px solid #CCCCCC;">'+dispName+'</td>';
							html += '</tr>';
						}
					} catch(e) {}

					var detailComment = decodeURIComponent(json.data[i].comment);
					var customerComment = decodeURIComponent(json.data[i].member_price);
					//detailComment = detailComment.replace(/\n/g, '<br />');
					
					// ===================================================
					// 2012.08.16 マルティン
					var order_kosu = parseInt(json.data[i].order_kosu);
					if (isNaN(order_kosu)) order_kosu = 0;

					var tanka = parseInt(json.data[i].tanka);
					if (isNaN(tanka)) tanka = 0;

					var waribiki_price = parseInt(json.data[i].waribiki_price);
					if (isNaN(waribiki_price)) waribiki_price = 0;

					var service_price = parseInt(json.data[i].service_price);
					if (isNaN(service_price)) service_price = 0;

					var subTotal = (order_kosu * tanka) - (waribiki_price + service_price);
					// ===================================================

					html += '<tr>';
					html += '<td>' + json.data[i].item_name + '</td>';

					// ===================================================
					// 2012.08.16 マルティン
					if(json.mode == 1){// 美容のとき
						html += '<td style="text-align: left;overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">'+json.data[i].tantou_name+'</td>';
					}
					// ===================================================

					html += '<td style="text-align: right;">' + numberFormat(order_kosu) + '</td>';
					html += '<td style="text-align: right;">' + numberFormat(tanka) + '</td>';

					// ===================================================
					// 2012.08.16 マルティン
					if(json.mode == 1){// 美容のとき
						html += '<td style="text-align: right;">'+numberFormat(waribiki_price)+'</td>';
						html += '<td style="text-align: right;">'+numberFormat(service_price)+'</td>';
					}
					// ===================================================

					html += '<td style="text-align: right;">' + numberFormat(subTotal) + '</td>';
					
					if(json.mode == 1){// 美容のとき
						// 2012.12.03 マルティン
						// 2013.11.08 マルティン 「顧客へのコメント」を追加（tb_order_sub.member_price項目)
						if ( cd == json.data[i].tb_order_main_cd) {
							html += '<td><textarea rows="2" name="memo['+json.data[i].cd+']" class="item_memo" style="width:95%;resize: both;">' + detailComment + '</textarea></td>';
							html += '<td><textarea rows="2" name="customer_comment['+json.data[i].cd+']" class="item_memo" style="width:95%;resize:both;">' + customerComment + '</textarea></td>';
						} else {
							html += '<td><div style="overflow-x:hidden;overflow-y:auto;max-height:100px;">' + detailComment + '</div></td>';
							html += '<td><div style="overflow-x:hidden;overflow-y:auto;max-height:100px;">' + customerComment + '</div></td>';
						}
					}
					
					html += '</tr>';
				}



					// =============================================================================
					// 2012.11.13 マルティン
					var colspan = (json.mode == 1) ? "6" : "3"; // 美容か飲食
					
					// 割増
					if ( rirekiObj[index].warimashi_name != "" ) {
						var names = rirekiObj[index].warimashi_name.split("<>");
						var prices = rirekiObj[index].warimashi_price.split("<>");
						for (n=0; n<names.length; n++) {
							html += '<tr>';
							html += '<td colspan="'+colspan+'" style="padding: 5px; text-align: right;border: 1px solid #CCCCCC;">'+decodeURIComponent(names[n])+'</td>';
							html += '<td style="padding: 5px; text-align: right;border: 1px solid #CCCCCC;">'+numberFormat(prices[n])+'</td>';
							if (json.mode == 1) html += '<td colspan="2"></td>';
							html += '</tr>';
						} // end for n
					} // end

					// 割引
					if ( rirekiObj[index].waribiki_name != "" ) {
						var names = rirekiObj[index].waribiki_name.split("<>");
						var prices = rirekiObj[index].waribiki_price.split("<>");
						for (n=0; n<names.length; n++) {
							html += '<tr>';
							html += '<td colspan="'+colspan+'" style="padding: 5px; text-align: right;border: 1px solid #CCCCCC;">'+decodeURIComponent(names[n])+'</td>';
							html += '<td style="color:#FF0000;padding: 5px; text-align: right;border: 1px solid #CCCCCC;">'+numberFormat(prices[n] * -1)+'</td>';
							if (json.mode == 1) html += '<td colspan="2"></td>';
							html += '</tr>';
						} // end for n
					} // end

					if ( variable_waribiki > 0 ) {
						html += '<tr>';
						html += '<td colspan="'+colspan+'" style="padding: 5px; text-align: right;border: 1px solid #CCCCCC;">割引額</td>';
						html += '<td style="color:#FF0000;padding: 5px; text-align: right;border: 1px solid #CCCCCC;">'+numberFormat(variable_waribiki * -1)+'</td>';
						if (json.mode == 1) html += '<td colspan="2"></td>';
						html += '</tr>';
					} // end if

					if ( used_points > 0 ) {
						html += '<tr>';
						html += '<td colspan="'+colspan+'" style="padding: 5px; text-align: right;border: 1px solid #CCCCCC;">ポイント使用</td>';
						html += '<td style="color:#FF0000;padding: 5px; text-align: right;border: 1px solid #CCCCCC;">'+numberFormat(used_points * -1)+'</td>';
						if (json.mode == 1) html += '<td colspan="2"></td>';
						html += '</tr>';
					} // end if

					if (taxFlg == "2") {
						html += '<tr>';
						html += '<td colspan="'+colspan+'" style="padding: 5px; text-align: right;border: 1px solid #CCCCCC;">消費税</td>';
						html += '<td style="padding: 5px; text-align: right;border: 1px solid #CCCCCC;">'+numberFormat(tax)+'</td>';
						if (json.mode == 1) html += '<td colspan="2"></td>';
						html += '</tr>';
					} // end if

					html += '<tr>';
					html += '<td colspan="'+colspan+'" style="padding: 5px; text-align: right;border: 1px solid #CCCCCC;">合計</td>';
					html += '<td style="padding: 5px; text-align: right;border: 1px solid #CCCCCC;">'+numberFormat(order_sum)+'</td>';
					if (json.mode == 1) html += '<td colspan="2"></td>';
					html += '</tr>';

					if (taxFlg == "1") {
						html += '<tr>';
						html += '<td colspan="'+colspan+'" style="padding: 5px; text-align: right;border: 1px solid #CCCCCC;">内消費税</td>';
						html += '<td style="padding: 5px; text-align: right;border: 1px solid #CCCCCC;">'+numberFormat(tax)+'</td>';
						if (json.mode == 1) html += '<td colspan="2"></td>';
						html += '</tr>';
					} // end if
					// =============================================================================



				
				html += '</table>';
				html += '</form>';

				html += '</div>';

				// if(json.mode == 1) html += '<div style="text-align:center;margin-top:15px;"><input type="button" class="btn btnStyleS" onclick="saveMemo(\'' + posDom + '\')" value="メモを更新"></div>'; // 美容のとき
				if(json.mode == 1) html += '<input type="hidden" id="memo_value" value="'+posDom+'">'; // 美容のとき

				$("#popUpDialog").html(html);
				
			}else{
				$("#popUpDialog").html("購買履歴はありません。");
			}
		}
	);
	
	$( "#popUpDialog" ).dialog({

		autoOpen: true,
		open: function(event, ui){ $(".ui-dialog-titlebar-close").hide(); },
		resizable: false,
		draggable: false,
		closeOnEscape: false,
		modal: true,
		title: 'Infomation',
		width: $(window).width() - 100,
		height: $(window).height() - 100,
		buttons: {
			'メモを更新': function(){
				saveMemo( $('#memo_value').val() );
			},
			'閉じる': function(){
				$(this).dialog('close');
			}
		}

	});
}


// =============================================================================
function saveMemo(posDom) {
	// var sendData = $("#sendDataForm").serialize();
	var sendData = "url=" + posDom;
	$(".item_memo").each(function(ind){
		sendData += "&"+$(this).attr("name")+"="+encodeURIComponent($(this).val());
	});

	$.ajax({
		type: "POST",
		dataType: "json",
		url: "/system/category1/inc/ajax_sendPosKoubaiMemoData.php",
		data: sendData,

		success: function(data) {
			if (data.status == "error") jAlert(data.message, "警告");
			else jAlert(data.message, "");
		},
		error: function() {
			jAlert("接続エラー発生しました", "警告");
		}
	});
}
// =============================================================================


// =============================================================================
// 曜日取得関数
// 2012.06.12 yoshida
function getYoubi(num){
	var relult = "";
	
	if(num != ""){
		var youbi = new Array("日", "月", "火", "水", "木", "金", "土");
		relult = youbi[num];
	}
	return relult;
}
// =============================================================================


// =============================================================================
// 天気取得関数
// 2012.06.12 yoshida
function getTenki(num){
	var relult = "";
	
	if(num != ""){
		var tenki = new Array("晴","曇","雨","雪");
		relult = tenki[num];
	}
	return relult;
}
// =============================================================================

</script>

<div id="popUpDialog" style="font-size:12px;"></div>


</div>
<!--マイクーポンtable end-->
