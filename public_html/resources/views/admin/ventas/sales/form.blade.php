{{--
    Legacy: system/category1/karute_menu/karute_new_frm.php (売上データ作成フォーム, ventana emergente).
    Variables:
      $shopCd        ?int                         tienda (master: ?shop=; si no, la de la sesión)
      $saved         bool                         ?status=saved: aviso 「登録しました。」
      $today         string                       fecha de hoy 'Y/m/d' (valor inicial de 登録日時)
      $verifyToken   string                       add_record_verify (contra el doble envío)
      $staffList     Collection<Staff>            担当スタッフ (el legacy lista siempre la tienda 1)
      $menus         Collection<ServiceMenu>      メニュー activas de la tienda (rank, cd)
      $discounts     Collection<Discount>         割引 activos de la tienda (rank, cd)
      $history       Collection<object>           履歴: filas 合計 del 営業日 con id y name del cliente
      $historyTotal  int                          suma de float_data de $history (当日売上金額)
--}}
@extends('layouts.legacy-popup')

@section('title', '売上データ作成 - ' . $siteName)

@section('css')
<link href="{{ asset('legacy/system/category1/karute_menu/css/karute_frm.css') }}" rel="stylesheet" type="text/css" media="all" />
<link href="{{ asset('legacy/system/css/jquery.alerts.css') }}" rel="stylesheet" type="text/css" media="all" />
<link href="{{ asset('legacy/system/css/colorbox.css') }}" rel="stylesheet" type="text/css" media="all" />
<link rel="stylesheet" type="text/css" href="{{ asset('legacy/system/plugin/ui-datepicker/ui.datepicker.css') }}" />
<link rel="stylesheet" type="text/css" href="{{ asset('legacy/system/plugin/myflexselect/myFlexSelect.css') }}" />
@endsection

@section('js')
<script type="text/javascript" src="{{ asset('legacy/system/plugin/ui-datepicker/ui.datepicker-ja.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/plugin/ui-datepicker/gcalendar-holidays.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/plugin/myflexselect/myFlexSelect.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/js/jquery.alerts.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/js/jquery.colorbox.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/js/ajaxupload-3.5.js') }}"></script>

<script type="text/javascript">
<!--

// =======================================================================================
// 初期設定
// =======================================================================================

$(document).ready(function() {




	@if ($saved)
	jAlert("登録しました。", "売上データ登録");
	setTimeout('$.alerts._hide();',1000);
	@endif


	// =======================================================================================
	// イベント設定
	// =======================================================================================

	// 会員検索 ------------------------------------------------------------------------------

	$("#kaiinSearchKeyWord").bind("keypress", function(e) {
		if ( e.keyCode == 13 ) $('#kaiinSearchSubmit').click();
	});

	$('#kaiinSearchSubmit').bind("click", function(){

		var send_val = $("#kaiinSearchKeyWord").val();

		$.ajax({
			type: "POST",
			url: "{{ route('legacy.ventas.search-kaiin') }}",
			data: "keyword="+encodeURIComponent(send_val)+"&shop_cd={{ $shopCd }}",
			success: function(data){
				$("#kaiinSearchResult").empty().html(data);
			}
		});

	});



	// 会員新規登録 --------------------------------------------------------------------------

	$('#kaiinAdd').bind("click", function(){

		window.blur();
		window.opener.focus();
		window.opener.location.href = '/system/category1/customer/customer_new.php';

	});



	// 会員選択 ------------------------------------------------------------------------------

	$('.addKaiin').live('click', function(){

		var cd = ($(this).attr('id')).split('_')[1];
		var id = $('#kaiinData_'+cd).find('td:eq(0)').html();
		var name = $('#kaiinData_'+cd).find('td:eq(1)').html();

		$('#kaiinDisp').empty().html(id+' '+name+' 様');
		$('#kaiinBtn').css('display','inline-block');
		$('#kaiin_cd').val(cd);

	});



	// 会員選択解除 --------------------------------------------------------------------------

	$('#kaiinRemove').live('click', function(){

		$('#kaiinDisp').empty();
		$('#kaiinBtn').hide();
		$('#kaiin_cd').val('');

	});



	// 会員カルテ開く ------------------------------------------------------------------------

	$('#kaiinKarte').live('click', function(){

		window.open('/system/category1/inc/customer_detail.php?kaiin_cd='+$('#kaiin_cd').val()+'&tab=9&search_cate_cd=&wintype=no_headers', 'mywindow1', 'width=970, height=550, menubar=no, toolbar=no, scrollbars=yes');

	});



	// 担当スタッフ選択 ----------------------------------------------------------------------

	$('.addStaff').live('click', function(){

		var cd = ($(this).attr('id')).split('_')[1];
		var name = $('#staffData_'+cd).find('th').html();
		var kihon = $('#staffData_'+cd).find('.staff_kihon').val();
		var buai = $('#staffData_'+cd).find('.staff_buai').val();

		$('#staffDisp').empty().html(name);
		$('#staffRemove').show();
		$('#staff_cd').val(cd);
		$('#staff_name').val(name);
		$('#kingaku_kihon').val(kihon);
		$('#kingaku_buai').val(buai);

	});



	// 担当スタッフ選択解除 ------------------------------------------------------------------

	$('#staffRemove').live('click', function(){

		$('#staffDisp').empty();
		$('#staffRemove').hide();
		$('#staff_cd').val('');
		$('#kingaku_kihon').val('');
		$('#kingaku_buai').val('');

	});



	// メニュー選択 --------------------------------------------------------------------------

	$('.addMenu').live('click', function(){

		var cd = ($(this).attr('id')).split('_')[1];
		var name = $('#menuData_'+cd).find('.menuName').val();
		var kingaku = $('#menuData_'+cd).find('.menuKingaku').val();
		var type = $('#menuData_'+cd).find('.menuType').val();

		var html  = '<tr id="selectedMenu_'+cd+'" class="selectedMenu">';
		    html += '<th>'+name+'</th>';
		    html += '<td>'+number_format(kingaku)+'&nbsp;円</td>';
		    html += '<td>';
		    html += '<input type="button" name="selectedMenuRemove" id="selectedMenuRemove_'+cd+'" class="selectedMenuRemove btn btnStyle" value="　取消　" />';
		    html += '<input type="hidden" name="menu_cd[]" class="selectedMenuCd" value="'+cd+'" />';
		    html += '<input type="hidden" name="menu_name[]" class="selectedMenuName" value="'+name+'" />';
		    html += '<input type="hidden" name="menu_kingaku[]" class="selectedMenuKingaku" value="'+kingaku+'" />';
		    html += '<input type="hidden" name="menu_type[]" class="selectedMenuType" value="'+type+'" />';
		    html += '</td>';
		    html += '</tr>';

		$('#selectedMenuTbl').prepend(html);

		totalPreview();

	});



	// メニュー選択解除 ----------------------------------------------------------------------

	$('.selectedMenuRemove').live('click', function(){

		var cd = ($(this).attr('id')).split('_')[1];

		$('#selectedMenu_'+cd).remove();

		totalPreview();

	});



	// 割引選択 ------------------------------------------------------------------------------

	$('.addWaribiki').live('click', function(){

		var cd = ($(this).attr('id')).split('_')[1];
		var name = $('#waribikiData_'+cd).find('.waribikiName').val();
		var value = $('#waribikiData_'+cd).find('.waribikiValue').val();
		var type = $('#waribikiData_'+cd).find('.waribikiType').val();
		var buaiFlg = $('#waribikiData_'+cd).find('.waribikiBuaiFlg').val();

		var typeStr = (type == '1') ? '％': '円';

		var html  = '<tr id="selectedWaribiki_'+cd+'" class="selectedWaribiki">';
		    html += '<th>'+name+'</th>';
		    html += '<td>'+number_format(value)+'&nbsp;'+typeStr+'</td>';
		    html += '<td>';
		    html += '<input type="button" name="selectedWaribikiRemove" id="selectedWaribikiRemove_'+cd+'" class="selectedWaribikiRemove btn btnStyle" value="　取消　" />';
		    html += '<input type="hidden" name="waribiki_cd[]" class="selectedWaribikiCd" value="'+cd+'" />';
		    html += '<input type="hidden" name="waribiki_name[]" class="selectedWaribikiName" value="'+name+'" />';
		    html += '<input type="hidden" name="waribiki_value[]" class="selectedWaribikiValue" value="'+value+'" />';
		    html += '<input type="hidden" name="waribiki_type[]" class="selectedWaribikiType" value="'+type+'" />';
		    html += '<input type="hidden" name="waribiki_buai_flg[]" class="selectedWaribikibuaiFlg" value="'+buaiFlg+'" />';
		    html += '</td>';
		    html += '</tr>';

		$('#selectedWaribikiTbl').prepend(html);

		totalPreview();

	});



	// 割引選択解除 --------------------------------------------------------------------------

	$('.selectedWaribikiRemove').live('click', function(){

		var cd = ($(this).attr('id')).split('_')[1];

		$('#selectedWaribiki_'+cd).remove();

		totalPreview();

	});



	// その他の割引 --------------------------------------------------------------------------

	$('#otherWaribikiValue').bind('blur', totalPreview);
	$('#otherWaribikiType').bind('change', totalPreview);

	$('#otherWaribikiRemove').bind('click', function(){

		$('#otherWaribikiValue').val('');
		totalPreview();

	});



	// 施術メモ ------------------------------------------------------------------------------

	var sejyutuText = '';
	var shokenText = '';

	$('#memoWindowOpen')
	.colorbox({

		opacity: 0.3,
		width: 400,
		height: 470,
		transition: "none",
		inline: true,
		overlayClose: false,
		scrollbars: false,
		escKey: false,
		href: "#dialog"

	})
	.bind('click', function(){

		sejyutuText = $('#sejyutuText').val();
		shokenText = $('#shokenText').val();

	});

	$('#dialogCancel').bind('click', function(){

		$('#sejyutuText').val(sejyutuText);
		$('#shokenText').val(shokenText);

		$('#memoWindowOpen').colorbox.close();

	});

	$('#dialogEnter').bind('click', function(){

		$('#memoWindowOpen').colorbox.close();

	});



	// メニューのタブ選択 --------------------------------------------------------------------

	$("#menuTab li").bind("click", function(){

		var thisId = ($(this).attr('id')).split('_')[1];

		$("#menuTab li div").removeClass('on');
		$("div",this).addClass('on');

		$("#menuPallet > li").hide();
		$("#menuPallet_"+thisId).show();

	});



	// =======================================================================================
	// 合計金額を計算
	// =======================================================================================

	function totalPreview() {

		var totalPrice = 0;
		var totalCommissionPrice = 0; // 2012.06.05 マルティン

		var v = 0; // 割引合計
		var buai_v = 0; // 歩合に含む割引合計
		var temp;

		// メニュー総額
		$('.selectedMenu').each(function( index, elm ){

			totalPrice += parseInt($('.selectedMenuKingaku',elm).val());

		});

		// 割引総額
		$('.selectedWaribiki').each(function( index, elm ){

			temp = 0;

			switch ($('.selectedWaribikiType',elm).val()) {

				case '1':
					// v += (totalPrice / 100) * parseInt($('.selectedWaribikiValue',elm).val());
					temp = (totalPrice / 100) * parseInt($('.selectedWaribikiValue',elm).val());
					break;

				case '2':
					// v += parseInt($('.selectedWaribikiValue',elm).val());
					temp = parseInt($('.selectedWaribikiValue',elm).val());
					break;

			}
			v += temp;
			if ( $('.selectedWaribikibuaiFlg',elm).val() == '1' ) buai_v += temp; // 2012.06.05 マルティン 歩合に含む割引を加算

		});

		// その他の割引
		if ($('#otherWaribikiValue').val() != '') {

			temp = 0;

			switch ($('#otherWaribikiType').val()) {

				case '1':
					temp = (totalPrice / 100) * parseInt($('#otherWaribikiValue').val());
					break;

				case '2':
					temp = parseInt($('#otherWaribikiValue').val());
					break;

			}
			v += temp;
			buai_v += temp; // 2012.06.19 マルティン 歩合にその他の割引を加算

		} // END --- if ($('#otherWaribikiValue').val() != '')


		// ===============================
		// 2012.06.05 マルティン
		// 歩合対処金額の計算
		totalCommissionPrice = totalPrice - buai_v;
		$('#totalCommissionPrice').val(totalCommissionPrice);
		// ===============================

		// メニュー総額に割引を適用
		totalPrice -= v;

		// 合計を表示
		$('#totalPriceDisplay').empty().html(number_format(totalPrice));
		$('#totalPrice').val(totalPrice);

	} // END --- function totalPreview()



	// =======================================================================================
	// 初期化
	// =======================================================================================

	$("#menuTab_1").find('div').addClass('on');
	$("#menuPallet_1").show();

	$("#update_date").datepicker();

	var options1 = ['00','01','02','03','04','05','06','07','08','09',
			'10','11','12','13','14','15','16','17','18','19',
			'20','21','22','23'];
	$("#update_h").myFlexSelect({ options: options1, dropdownSize: 5 });

	var options2 = ['00','05','10','15','20','25','30','35','40','45','50','55'];
	$("#update_i").myFlexSelect({ options: options2, dropdownSize: 5 });



	// =======================================================================================

});





// =================================================================================================
// 送信確認
// =================================================================================================

function submitChk() {



	var errorMess = "";
	var errorFlg = 0;

	if (!ckDate($('#update_date').val())) {

		errorMess += '・登録日時の書式が正しくありません。\n&nbsp;[ 正しい入力例 ] 2009/01/01\n';
		errorFlg = 1;

	}

	if ($('#kaiin_cd').val() == '') {

		errorMess += '・会員を選択してください。\n';
		errorFlg = 1;

	}

	if ($('#staff_cd').val() == '') {

		errorMess += '・担当スタッフを選択してください。\n';
		errorFlg = 1;

	}

	if ($('#totalPrice').val() < 0) {

		errorMess += '・合計の値がマイナスのため登録出来ません。';
		errorFlg = 1;

	}

	if(errorFlg == 1){
		jAlert(errorMess,'入力エラー');
		return false;
	}else{
		jConfirm (
			"この内容で登録してもよろしいですか？\n書き直す場合は['キャンセル']ボタンを押して下さい。",
			"登録確認",
			 function(ret){

				if (ret == false) {
					return false;
				} else {
					//alert(($('form[name="form1"]').serialize()).replace(/&/g,"\n").replace(/%5B/g,"[").replace(/%5D/g,"]"));
					document.form1.submit();
				}

		});
	}

	return false;



} // function submitChk () --- END





// =======================================================================================

// ◆入力された値が日付として正しいかチェックする

// 機　能： 入力された値が日付でYYYY/MM/DD形式になっているか調べる
// 引　数： datestr　入力された値
// 戻り値： 正 => true,　不正 => false

// =======================================================================================

function ckDate( datestr ) {

	// 正規表現による書式チェック
	if( !datestr.match(/^\d{4}\/\d{2}\/\d{2}$/) ){
		return false;
	}

	var vYear = datestr.substr(0, 4) - 0,
	    vMonth = datestr.substr(5, 2) - 1,
	    vDay = datestr.substr(8, 2) - 0;

	// 月,日の妥当性チェック
	if( vMonth >= 0 && vMonth <= 11 && vDay >= 1 && vDay <= 31 ){

		var vDt = new Date(vYear, vMonth, vDay);

		if( isNaN(vDt) ){
			return false;
		}else if( vDt.getFullYear() == vYear && vDt.getMonth() == vMonth && vDt.getDate() == vDay ){
			return true;
		}else{
			return false;
		}

	}else{
		return false;
	}

} // END --- function ckDate( datestr )





// =================================================================================================
// ナンバーフォーマットfunction
// =================================================================================================

function number_format( num ) {

	return num.toString().replace( /([0-9]+?)(?=(?:[0-9]{3})+$)/g , '$1,' );

} // function number_format( num ) --- END





// =================================================================================================
// 数字のみを入力するfunction
// =================================================================================================

// 使用方法：<input name="price" type="text" onKeyPress="return numbersonly(this, event)" onkeyup="replace_value(this)" onmouseover="replace_value(this)" onchange="replace_value(this)"/>
// イベントの理由
// onKeyPress="return numbersonly(this, event)" 数字キーを押したかどうか。
// onkeyup="replace_value(this)" IMEモードで入力するとonKeyPressイベントは発生しません。
// onmouseover="replace_value(this)" ドラグをする時onKeyPress、onkeyupイベントは発生しません。
// onchange="replace_value(this)" キャッシュを残る時に発生するイベント。

var no_digit = /\D/g;

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





// =======================================================================================

-->
</script>
@endsection

@section('content')
{{-- El legacy pone class="main popup"; el layout emergente no la tiene, así que .popup envuelve el contenido --}}
<div class="popup">
<div class="contents">

@if ($shopCd)

<div id="karteForm">

<div id="leftColumn">

<div id="karuteData">
<p class="h2Title">売上データ内容</p>
<div class="container">

<form class="form" action="{{ route('admin.ventas.sales.store') }}" method="post" name="form1" onsubmit="return submitChk()">
@csrf

<div class="h3Title mB5">基本情報</div>

<table class="tableBox5">

<tr>
<th>登録日時</th>
<td>
<input type="text" name="update_date" id="update_date" class="inputM" value="{{ $today }}" />
<input type="hidden" name="add_record_verify" id="add_record_verify" value="{{ $verifyToken }}" />
<input type="hidden" name="shop" id="shop" value="{{ $shopCd }}" />
</td>
</tr>

<tr>
<th>会員</th>
<td>
<span id="kaiinDisp">&nbsp;</span>
<span id="kaiinBtn">
<input type="button" name="kaiinKarte" id="kaiinKarte" class="btn btnStyle" value="　カルテ　" />
<input type="button" name="kaiinRemove" id="kaiinRemove" class="btn btnStyle" value="　取消　" />
</span>
<input type="hidden" name="kaiin_cd" id="kaiin_cd" value="" />
</td>
</tr>

<tr>
<th>担当スタッフ</th>
<td>
<span id="staffDisp">&nbsp;</span>
<input type="button" name="staffRemove" id="staffRemove" class="btn btnStyle" value="　取消　" />
<input type="hidden" name="staff_cd" id="staff_cd" value="" />
<input type="hidden" name="staff_name" id="staff_name" value="" />
<input type="hidden" name="kingaku_kihon" id="kingaku_kihon" value="" />
<input type="hidden" name="kingaku_buai" id="kingaku_buai" value="" />
</td>
</tr>

</table>

<div class="h3Title mB5">メニュー</div>

<div class="inner">
<table id="selectedMenuTbl">
</table>
<!--□□ /.inner □□--></div>

<div class="h3Title mB5">割引</div>

<div class="inner">
<table id="selectedWaribikiTbl">
</table>
<!--□□ /.inner □□--></div>

<div id="otherWaribiki">
<table>
<tr>
<th>その他の割引</th>
<td>
	<input type="text" name="otherWaribikiValue" id="otherWaribikiValue" class="inputM" onKeyPress="return numbersonly(this, event)" onkeyup="replace_value(this)" onmouseover="replace_value(this)" onchange="replace_value(this)" value="" />
	<select name="otherWaribikiType" id="otherWaribikiType" style="padding: 1px; border: 1px solid #7F9DB9;">
	<option value="1">％</option>
	<option value="2">円</option>
	</select>
</td>
<td><input type="button" name="otherWaribikiRemove" id="otherWaribikiRemove" class="btn btnStyle" value="　取消　" /></td>
</tr>
</table>
<!--□□ /#otherWaribiki □□--></div>

<dl id="resultDisplay"><!--
--><dt><span>合計</span></dt><!--
--><dd><span><em id="totalPriceDisplay">0</em>&nbsp;円</span><input type="hidden" name="totalPrice" id="totalPrice" value="0" /><input type="hidden" name="totalCommissionPrice" id="totalCommissionPrice" value="0" /></dd><!--
--><!--□□ /#resultDisplay □□--></dl>

<div id="btnArea">
<!--
<input type="button" name="memoWindowOpen" id="memoWindowOpen" class="btn btnStyleM" value="　施術メモ　" />
-->
<input type="submit" name="enter" id="enter" class="btn btnStyleM" value="　　確定　　" />
<!--□□ /#btnArea □□--></div>

<div id="dialogWindow">
<div id="dialog">

<div class="h2Title mB5">施術内容</div>
<textarea name="sejyutuText" id="sejyutuText"></textarea>

<div class="h2Title mB5">所見内容</div>
<textarea name="shokenText" id="shokenText"></textarea>

<input type="button" name="dialogCancel" id="dialogCancel" class="btn btnStyleM" value="キャンセル" />
<input type="button" name="dialogEnter" id="dialogEnter" class="btn btnStyleM" value="確定" />

<!--□□ /#dialog □□--></div>
<!--□□ /#dialogWindow □□--></div>

</form>

<!--■■ /.container ■■--></div>
<!--■■ /#karuteData ■■--></div>

<!--■□■ /#leftColumn ■□■--></div>

<div id="rightColumn">

<div id="kaiinSearch">
<p class="h2Title">会員検索</p>

<ul class="form"><li>
<label for="kaiinSearchKeyWord">検索キーワード：</label><input type="text" name="kaiinSearchKeyWord" id="kaiinSearchKeyWord" class="inputL" value="" />
</li><li>
<input type="button" name="kaiinSearchSubmit" id="kaiinSearchSubmit" class="btn btnStyle" value="　　　 検索 　　　" />
<input type="button" name="kaiinAdd" id="kaiinAdd" class="btn btnStyle" value="　　 新規登録 　　" />
&emsp;※最大200件表示
</li></ul>

<div class="inner">
<table id="kaiinSearchResult">
<tr><td class="noData">検索キーワードを入力して検索してください。<br />
※キーワードは ID 管理番号 名前 カナ 電話番号 メールアドレスから検索されます。</td></tr>
</table>
<!--□□ /.inner □□--></div>

<!--■■ /#kaiinSearch ■■--></div>

<div id="staffSelect">
<p class="h2Title">担当スタッフ選択</p>
<div class="inner">
<table>
@forelse ($staffList as $staff)
<tr id="staffData_{{ $staff->cd }}">
<th>{{ $staff->name }}</th>
<td>
	<input type="button" name="staff_{{ $staff->cd }}" id="staff_{{ $staff->cd }}" class="addStaff btn btnStyle" value="　選択　" />
	<input type="hidden" name="staff_kihon" class="staff_kihon" value="{{ $staff->kingaku_kihon }}" />
	<input type="hidden" name="staff_buai" class="staff_buai" value="{{ $staff->kingaku_buai }}" />
</td>
</tr>
@empty
<tr><td class="noData">スタッフが登録されていません。</td></tr>
@endforelse
</table>
<!--□□ /.inner □□--></div>
<!--■■ /#staffSelect ■■--></div>

<div id="menuSelect">

<ul id="menuTab"><!--
--><li id="menuTab_1"><div>メニュー選択</div></li><!--
--><li id="menuTab_2"><div>割引選択</div></li><!--
--></ul>

<ul id="menuPallet"><!--
--><li id="menuPallet_1"><table>@forelse ($menus as $menu)<tr id="menuData_{{ $menu->cd }}"><th>{{ $menu->name }}</th><td><input type="button" name="menu_{{ $menu->cd }}" id="menu_{{ $menu->cd }}" class="addMenu btn btnStyle" value="　選択　" /><input type="hidden" name="menuName" class="menuName" value="{{ $menu->name }}" /><input type="hidden" name="menuKingaku" class="menuKingaku" value="{{ $menu->kingaku }}" /><input type="hidden" name="menuType" class="menuType" value="{{ $menu->type }}" /></td></tr>@empty<tr><td class="noData">メニューが登録されていません。</td></tr>@endforelse</table></li><!--
--><li id="menuPallet_2"><table>@forelse ($discounts as $discount)<tr id="waribikiData_{{ $discount->cd }}"><th>{{ $discount->name }}</th><td><input type="button" name="waribiki_{{ $discount->cd }}" id="waribiki_{{ $discount->cd }}" class="addWaribiki btn btnStyle" value="　選択　" /><input type="hidden" name="waribikiName" class="waribikiName" value="{{ $discount->name }}" /><input type="hidden" name="waribikiValue" class="waribikiValue" value="{{ $discount->value }}" /><input type="hidden" name="waribikiType" class="waribikiType" value="{{ $discount->type }}" /><input type="hidden" name="waribikiBuaiFlg" class="waribikiBuaiFlg" value="{{ $discount->buai_flg }}" /></td></tr>@empty<tr><td class="noData">割引が登録されていません。</td></tr>@endforelse</table></li><!--
--></ul>

<!--■■ /#menuSelect ■■--></div>

<div id="raitenList">
<p class="h2Title">履歴</p>
<div class="inner">
<table>
@forelse ($history as $row)
<tr>
<td>{{ \Illuminate\Support\Carbon::createFromTimestamp((int) $row->touroku_date, 'Asia/Tokyo')->format('Y/m/d H:i:s') }}</td>
<td>【&nbsp;ID：&nbsp;{{ $row->id }}&nbsp;】&emsp;{{ $row->name }}&nbsp;様</td>
<td>{{ number_format((float) $row->float_data) }}円</td>
</tr>
@empty
<tr><td class="noData">履歴がありません。</td></tr>
@endforelse
</table>

<!--□□ /.inner □□--></div>
<table class="tableBox">
<tr>
<th style="width:25%;">総施術顧客数</th>
<td style="width:25%;text-align:center;">{{ number_format($history->count()) }}人</td>
<th style="width:25%;">当日売上金額</th>
<td style="width:25%;text-align:center;">{{ number_format($historyTotal) }}円</td>
</tr>
</table>
<!--■■ /#raitenList ■■--></div>

<!--■□■ /#rightColumn ■□■--></div>

<!--■□■ /#karteForm ■□■--></div>

@endif
<!-- / .contents --></div>
</div>
@endsection
