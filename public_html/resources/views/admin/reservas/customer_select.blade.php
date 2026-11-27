{{--
    Legacy: system/category9/yoyaku/yoyaku_customer_select.php (顧客情報入力: socio o datos libres, y 登録).
    Variables:
      $input           array    petición limpia con form2Db (name/name_kana con 「ゲスト」 por defecto, shop, staff, menu[], option_menu[], yoyaku_date, plus, kijun_time…)
      $getData         string   consulta para volver a 時間選択
      $koumoku         array    tb_koumoku del formulario indexado por campo
      $freeFields      array    campos libres activos [N => fila de tb_koumoku]
      $shopName        ?string  getShopNameFromCd()
      $staffName       ?string  tb_staff.name
      $staffLabel      string   manageWord('担当スタッフ', 'c9_yoyaku[スタッフ]')
      $menus           Collection  menús elegidos (name, shoyou_time)
      $totalMinutes    int      suma de shoyou_time
      $dispDate        string   'YYYY年MM月DD日(曜) HH時MM分～' (HH + 24 con plus=1)
      $showOshirase    bool     contents_master mail_send_status != 1: casilla visible
      $memberLabel     ?string  '【id】 nombre' del socio elegido
      $registerVerify  int      marca del anti doble envío (time())
    Sesión: 'reservationError' (aforo superado al registrar; texto nuevo, el legacy no lo comprobaba).
--}}
@extends('layouts.legacy-popup')

@section('title', 'トップ -総合業務管理システム-')

@section('css')
<style type="text/css">

#container {
	min-width: 500px;
}

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

function submitChk () {

	var reg = /^([A-Za-z0-9_\-\.\/\+\=])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
	var mail = "";
	var errorMess = "";
	var errorFlg = 0;
	var peopleColumn = $("#people-input");

	$("#errMessArea").html("");

	if(document.form1.kaiin.value == "" && document.form1.name.value == ""){
		errorMess += "「お名前」";
		errorFlg = 1;
	}

	if(peopleColumn.length > 0){
		if(document.form1.kaiin.value == "" && document.form1.people.value == ""){
			errorMess += "「人数」";
			errorFlg = 1;
		}
	}

	$(".free_req").each(function(){
		if(document.form1.kaiin.value != "" && $(this).is('.NoMember')){
			return true; // continue;
		} else if ($(this).val() == "") {
			errorMess += "「"+$(this).attr('ref')+"」";
			errorFlg = 1;
		}
	});

	if (typeof(document.form1.mail) != "undefined") {
		reg = /^([A-Za-z0-9_\-\.\/\+\=])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
		mail = document.form1.mail.value;
	}

	if (errorFlg == 1){
		errorMess += "が未入力です。";
		$("#errMessArea").html("※"+errorMess);
		return false;

	} else if (mail != "" && reg.test(mail) == false) {
		$("#errMessArea").html('※メールアドレスが不適切な形式です。');
		return false;

	}else{
		return true;
	}

}

$(document).ready(function() {

@if ($memberLabel !== null)
	setMember({{ \Illuminate\Support\Js::from((string) $input['kaiin']) }}, {{ \Illuminate\Support\Js::from(e($memberLabel)) }});
@endif

	//閉じるボタンの挙動
	$(".popCloseBtn").click(function(){

		if ( window == window.parent ) {
			window.close();
		} else {
			window.parent.popUpWinClose("");
		}

	});



	// 2012.04.16【大野】フォームの初期値＆入力補助を設定 ================================================

	var defColer = '#999999';	// 初期値の文字色
	var focusColer = '#2F2F2F';	// 通常入力時の文字色

	var defaultValue = {

		"name"      : "ゲスト",
		"name_kana" : "ゲスト"

	};

	$('input[name="name"],input[name="name_kana"]')
	.css('color',defColer)
	.focus(function(){
		if($(this).val() == defaultValue[$(this).attr("name")]){
			$(this).val('').css('color', focusColer);
		}
	})
	.blur(function(){
		if($(this).val() == defaultValue[$(this).attr("name")] | $(this).val() == ''){
			$(this).val(defaultValue[$(this).attr("name")]).css('color',defColer);
		}
	});

	// ===================================================================================================



});

</script>
@endsection

@section('content')
<div class="contents">

<p class="h2Title">顧客情報入力</p>

<div style="margin-bottom: 10px;">
<a href="{{ route('admin.reservas.calendar-select') }}?{{ $getData }}">&lt;&lt;戻る</a>
</div>



<form action="{{ route('admin.reservas.store') }}" method="post" name="form1" id="form1" class="form" onsubmit="return submitChk()">
@csrf

<fieldset>
<legend>予約内容</legend>

<table class="tableBox wordBreakAll">
	<tr>
		<th style="width:25%;">店舗</th>
		<td>{{ $shopName }}</td>
	</tr>

	<tr>
		<th>{{ $staffLabel }}</th>
		<td>{{ $staffName }}</td>
	</tr>

	<tr>
		<th>施術内容</th>
		<td>@foreach ($menus as $menu){{ $menu->name }}<span style="font-size:90%;">&nbsp;({{ $menu->shoyou_time }}分)</span><br/>
@endforeach
		&emsp;計：{{ $totalMinutes }}分</td>
	</tr>

	<tr>
		<th>日時</th>
		<td>{{ $dispDate }}</td>
	</tr>
	<tr @if (! $showOshirase) style="display:none" @endif>
	<td colspan="2">
	<input type="checkbox" name="oshirase" id="oshirase" class="checkBtn" value="1" /><label for="oshirase"><strong>予約受付の旨をお客様にお知らせする</strong></label>
	<br />※お知らせするにはメールアドレスが必須です。
	<br />メールアドレスが間違えている場合や、ドメイン指定解除されていない場合はメールが届かない事が考えられます。
	</td>
	</tr>
</table>



</fieldset>

<fieldset>
<legend>顧客情報</legend>

<table class="tableBox">
	<tr>
		<th style="width:25%;">会員</th>
		<td><span id="kaiin_name"></span><input type="button" name="kaiin_btn" id="kaiin_btn" value="会員検索" /><input type="hidden" id="kaiin" name="kaiin" value="" /></td>
	</tr>

	<tr class="NoMemberTR">
		<th>お名前<span class="attention">※</span></th>
		<td><input type="text" name="name" value="{{ $input['name'] }}" class="inputM NoMember" />様</td>
	</tr>

@if (($koumoku['name_kana']['koumoku_disp'] ?? '') == '1')
@php $kanaRequired = ($koumoku['name_kana']['koumoku_check'] ?? '') == '1'; @endphp
	<tr class="NoMemberTR">
		<th>お名前(カナ)@if ($kanaRequired)<span class="attention">※</span>@endif</th>
		<td><input type="text" name="name_kana" value="{{ $input['name_kana'] }}" class="inputM NoMember{{ $kanaRequired ? ' free_req' : '' }}" ref="お名前(カナ)" />様</td>
	</tr>
@endif

@if (($koumoku['tel']['koumoku_disp'] ?? '') == '1')
	<tr class="NoMemberTR">
		<th>電話番号</th>
		<td><input type="text" name="tel" value="{{ $input['tel'] ?? '' }}" class="inputM NoMember" ref="電話番号" style="ime-mode:disabled;" onKeyPress="return numbersonly(this, event)" /></td>
	</tr>
@endif

@if (($koumoku['people']['koumoku_disp'] ?? '') == '1')
	<tr class="">
		<th>予約人数</th>
		<td><input id="people-input" type="number" min="1" name="people" value="{{ (int) (is_numeric($input['people'] ?? null) ? $input['people'] : 0) }}" class="inputM" ref="予約人数" style="ime-mode:disabled;" onKeyPress="return numbersonly(this, event)" /></td>
	</tr>
@endif

@if (($koumoku['mark']['koumoku_disp'] ?? '') == '1')
	<tr class="">
		<th>マーク</th>
		<td><input type="text" name="mark" value="{{ $input['mark'] ?? '' }}" class="inputM" ref="マーク" style="ime-mode:disabled;"/></td>
	</tr>
@endif

@if (($koumoku['mail']['koumoku_disp'] ?? '') == '1')
	<tr class="NoMemberTR">
		<th>メールアドレス</th>
		<td><input type="text" name="mail" value="{{ $input['mail'] ?? '' }}" class="inputL NoMember" ref="メールアドレス" style="ime-mode:disabled;" /></td>
	</tr>
@endif

@foreach ($freeFields as $n => $field)
@php $required = ($field['koumoku_check'] ?? '') == '1'; @endphp
	<tr>
		<th>{{ $field['tb_koumoku_name'] }}@if ($required)<span class="attention">※</span>@endif</th>
		<td>
			<input type="text" name="free_koumoku[{{ $n }}]" value="{{ $input['free_koumoku'][$n] ?? '' }}" class="inputM{{ $required ? ' free_req' : '' }}" ref="{{ $field['tb_koumoku_name'] }}" />
		</td>
	</tr>
@endforeach

	<tr>
		<th>備考</th>
		<td><textarea name="comment" style="width:300px;" rows="5">{{ $input['comment'] ?? '' }}</textarea></td>
	</tr>
</table>



</fieldset>

<div id="errMessArea" style="text-align: center; color: #EE0000;">@if (session('reservationError'))※{{ session('reservationError') }}@endif</div>

<div class="btnBox">

<input type="submit" name="soushin_btn" id="soushin_btn" value="登録" />
<input type="button" value="閉じる" class="popCloseBtn" />

<input type="hidden" name="shop" value="{{ $input['shop'] }}" />
<input type="hidden" name="staff" value="{{ $input['staff'] }}" />
<input type="hidden" name="kijun_time" value="{{ $input['kijun_time'] ?? '' }}" />
<input type="hidden" name="yoyaku_date" value="{{ $input['yoyaku_date'] }}" />
<input type="hidden" name="shoyou_time" value="{{ $totalMinutes }}" />
@if (array_key_exists('plus', $input))
<input type="hidden" name="plus" value="{{ $input['plus'] }}" />
@endif
@foreach ((array) $input['menu'] as $key => $val)
<input type="hidden" name="menu[{{ $key }}]" value="{{ $val }}" />
@endforeach
@foreach ((array) ($input['option_menu'] ?? []) as $key => $val)
<input type="hidden" name="option_menu[{{ $key }}]" value="{{ $val }}" />
@endforeach
{{-- F5キーとダブルクリックを防ぐ --}}
<input type="hidden" name="register_verify" id="register_verify" value="{{ $registerVerify }}" />


<input type="hidden" name="act" value="new" />

</div>

</form>


<!-- / .contents --></div>
@endsection
