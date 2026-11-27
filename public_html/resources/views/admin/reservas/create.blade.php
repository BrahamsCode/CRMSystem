{{--
    Legacy: system/category9/yoyaku/yoyaku.php (予約: datos del cliente, empleado y menú; ventana emergente del 予約表).
    Variables:
      $input           array     petición limpia con form2Db (kaiin, name, name_kana, tel, mail, mark, free_koumoku[], shop, staff, td…)
      $koumoku         array     tb_koumoku del formulario indexado por campo (cYoyaku::yoyakuFormKoumokuCheck)
      $freeFields      array     campos libres activos [N => fila de tb_koumoku]
      $staffWord       string    manageWord('スタッフ', 'c9_yoyaku[スタッフ]')
      $staffWorkWord   string    manageWord('スタッフ施術', 'c9_yoyaku[スタッフ]') (título)
      $siteName        string    $site_name
      $memberLabel     ?string   '【id】 nombre' del socio elegido (?kaiin=)
      $isMaster        bool      usuario master: selector de tiendas
      $sessionShopCd   ?int      tienda de la sesión (no master)
      $sessionShopName ?string   getShopNameFromCd() de la tienda de la sesión
      $shops           Collection  make_shop_options() (solo master)
      $menuCds         string    menús marcados 'cd,cd' al volver de 時間選択 ('' si no)
      $people          int       人数 (1 si no llega o es menor que 2)
      $kijunTime       mixed     día del 予約表 (epoch) para 時間選択
      $isIpad          bool      User-Agent con iPad
--}}
@extends('layouts.legacy-popup')

@section('title', $staffWorkWord . '管理 - ' . $siteName)

@section('css')
<style type="text/css">

#container {
	min-width: 500px;
	position:fixed;
	top:0;
	bottom:0;
	left:0;
	right:0;
	overflow-y: scroll;
	-webkit-overflow-scrolling: touch;
}

.main1 {
	background: none;
}

@if ($isIpad)
#container {
	position:relative;
}
@endif

</style>

<link href="{{ asset('legacy/system/css/colorbox.css') }}" rel="stylesheet" type="text/css" media="all" />
@endsection

@section('js')
<script type="text/javascript" src="{{ asset('legacy/system/js/jquery.colorbox.js') }}"></script>

<script type="text/javascript" src="{{ asset('legacy/system/category9/yoyaku/js/search_kaiin.js') }}"></script>

<script type="text/javascript">

// search_kaiin.js pide "ajax_search_kaiin.php" relativo a la página: aquí va a la URL del legacy
function getMember(e) {
		var send_val = $("#easySearchWord").val();

		$.ajax({
			type: "POST",
			url: {{ \Illuminate\Support\Js::from(route('legacy.reservas.options.members')) }},
			data: "w="+encodeURIComponent(send_val),

			success: function(data){
				$("#result").html(data);
			}
		});
}

// #################### 数字のみを入力するファンクション 始 ####################
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
// #################### 数字のみを入力するファンクション 終 ####################

$(document).ready(function() {

@if ($memberLabel !== null)
	setMember({{ \Illuminate\Support\Js::from((string) $input['kaiin']) }}, {{ \Illuminate\Support\Js::from(e($memberLabel)) }});
@endif

	$("#shop").change(function(){
		shop_cd = $("#shop").val();
		searchStaff(shop_cd);
	});

	$("#staff").change(function(){
		searchMenu('');
	});

@if (($input['shop'] ?? '') !== '' && ($input['staff'] ?? '') !== '')
	$.ajaxSetup({async: false}); //非同期をfalseにする。
	setStaff({{ \Illuminate\Support\Js::from((string) $input['shop']) }}, {{ \Illuminate\Support\Js::from((string) $input['staff']) }});
	$.ajaxSetup({async: true}); //非同期をtrueにする。
@elseif (! $isMaster)
	searchStaff({{ \Illuminate\Support\Js::from((string) $sessionShopCd) }});
@endif


	chengeTrColor();//Trの色変更関数
	$(".menuBtn").click(function(e){
		e.stopPropagation();
		setTimeout(chengeTrColor, 50);
	});
	selectCheckBox();//Tr選択でチェック選択関数



	//閉じるボタンの挙動
	$(".popCloseBtn").click(function(){
		window.parent.popUpWinClose("");
	});

});

function submitChk () {
	var a=false;
	var errorMess = "";
	var errorFlg = 0;

@if (($koumoku['mail']['koumoku_disp'] ?? '') == '1')
	var reg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
	var mail = document.form1.mail.value;
	if(mail != '' && reg.test(mail) == false) {
		alert('メールアドレスは異常な形式です。');
		return false;
	}
@endif
	$(".base_menu_cd").each(function(e) {
		if ($(this).attr("checked") == true) a=true;
	});

	if (a==false) alert ('ベースコースを選択して下さい');

	return a;
}

function searchStaff(shopCD) {
	$("#menu").html("");
	if (shopCD=='') {
		$("#btnBox1").hide();
		$("#staff").html('<option value="">店舗を選択して下さい</option>');
		return;
	} else {
		$("#staff").html('<option value="">Loading...</option>');
		$("#staff").load({{ \Illuminate\Support\Js::from(route('legacy.reservas.options.staff')) }},{
			shop_cd:shopCD
		});
	}
}

function searchMenu(menuCDs) {
	shopCD = $("#shop").val();
	staffCd = $("#staff").val();
	if (shopCD=='' || staffCd=='') {
		$("#btnBox1").hide();
		$("#menu").html("");
		return;
	} else {
		$("#menu").load({{ \Illuminate\Support\Js::from(route('legacy.reservas.options.menus')) }},{
			shop_cd:shopCD,
			staff_cd:staffCd,
			menu_cds:menuCDs
		});
		$("#btnBox1").show();
	}
}
function setStaff(shopCD, staffCD) {
	$("#shop").val(shopCD).change();
	$("#staff").val(staffCD);
	searchMenu({{ \Illuminate\Support\Js::from($menuCds) }});
}




function chengeTrColor(){
		$(".menuBtn").each(function(index){
			if ($(this).attr("checked")) {
				$(this).parents("tr").css("background","#FFBBBB");
			} else {
				$(this).parents("tr").css("background","");
			}
		});
}



function selectCheckBox(){
	$($(".menuBtn").parents("tr")).click(function(){
		var target = $(this).find("input");
		target.click();
	});
}



</script>
@endsection

@section('content')
@if ($isIpad)
<style>#ipad_window_close { cursor: pointer; }</style>
<div style="text-align:right;margin-bottom:15px">
	<button id="ipad_window_close" ontouchstart="window.opener.location.reload();window.close();">閉じる</button>
</div>
@endif

<div class="contents">

<form class="form" action="{{ route('admin.reservas.calendar-select') }}" method="get" name="form1" onsubmit="return submitChk()">
<table class="tableBox">
	<tr>
		<th style="width:25%;">会員</th>
		<td><span id="kaiin_name"></span><input type="button" name="kaiin_btn" id="kaiin_btn" value="会員検索" /><input type="hidden" id="kaiin" name="kaiin" value="" /></td>
	</tr>

	<tr class="NoMemberTR">
		<th>お名前</th>
		<td><input type="text" name="name" value="{{ $input['name'] ?? '' }}" class="inputM NoMember" />様</td>
	</tr>

@if (($koumoku['name_kana']['koumoku_disp'] ?? '') == '1')
	<tr class="NoMemberTR">
		<th>{{ $koumoku['name_kana']['tb_koumoku_name'] }}</th>
		<td><input type="text" name="name_kana" value="{{ $input['name_kana'] ?? '' }}" class="inputM NoMember" /></td>
	</tr>
@endif

@if (($koumoku['tel']['koumoku_disp'] ?? '') == '1')
	<tr class="NoMemberTR">
		<th>{{ $koumoku['tel']['tb_koumoku_name'] }}</th>
		<td><input type="text" name="tel" value="{{ $input['tel'] ?? '' }}" class="inputM NoMember" style="ime-mode:disabled;" onKeyPress="return numbersonly(this, event)" /></td>
	</tr>
@endif

@if (($koumoku['people']['koumoku_disp'] ?? '') == '1')
	<tr class="">
		<th>{{ $koumoku['people']['tb_koumoku_name'] }}</th>
		<td><input type="number" min="1" name="people" value="{{ $people }}" class="inputM" style="ime-mode:disabled;" onKeyPress="return numbersonly(this, event)" /></td>
	</tr>
@endif

@if (($koumoku['mark']['koumoku_disp'] ?? '') == '1')
	<tr class="">
		<th>{{ $koumoku['mark']['tb_koumoku_name'] }}</th>
		<td><input type="text" name="mark" value="{{ $input['mark'] ?? '' }}" class="inputM" /></td>
	</tr>
@endif

@if (($koumoku['mail']['koumoku_disp'] ?? '') == '1')
	<tr class="NoMemberTR">
		<th>{{ $koumoku['mail']['tb_koumoku_name'] }}</th>
		<td><input type="text" name="mail" value="{{ $input['mail'] ?? '' }}" class="inputL NoMember" style="ime-mode:disabled;" /></td>
	</tr>
@endif


@foreach ($freeFields as $n => $field)
	<tr>
		<th>{{ $field['tb_koumoku_name'] }}</th>
		<td>
			<input type="text" name="free_koumoku[{{ $n }}]" value="{{ $input['free_koumoku'][$n] ?? '' }}" class="inputM" ref="{{ $field['tb_koumoku_name'] }}" />
		</td>
	</tr>
@endforeach

	<tr>
		<th>{{ $staffWord }}を選択</th>
		<td>
@if (! $isMaster)
			{{ $sessionShopName }}<input type="hidden" name="shop" id="shop" value="{{ $sessionShopCd }}" />
@else
			<select name="shop" id="shop">
			<option value="">▼店舗選択</option>
@foreach ($shops as $shop)
<option value="{{ $shop->cd }}">{{ $shop->kaishamei }}</option>
@endforeach
			</select>
@endif
			&emsp;&emsp;
			<select name="staff" id="staff">
				<option value="">店舗を選択して下さい</option>
			</select>
		</td>
	</tr>
</table>

<div id="mess" style="display:none;text-align:center;background:#FFFF00;color:red;margin:5px;"></div>

<div id="menu" style="text-align:center;">
</div>

<input type="hidden" name="td" value="{{ $input['td'] ?? '' }}" />
<input type="hidden" name="kijun_time" value="{{ $kijunTime }}" />

</form>


<!-- / .contents --></div>
@endsection
