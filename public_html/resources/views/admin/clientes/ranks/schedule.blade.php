{{-- Legacy: system/category1/rank/customer_rank_schedule.php (顧客ランク振り分け設定).
     Variables:
       $siteName  nombre del sitio (<title>)
       $kingaku   ?array ajustes de 使用金額ランク (RankScheduleService::settings: active, type_flg, month[], day[], week[],
                  hour, minute, kikan); null si el módulo 顧客ランク（使用金額ランク） no está activo (no se muestra el bloque)
       $raiten    ?array ídem para 来店回数ランク (módulo 顧客ランク（来店回数ランク）)
     Los formularios envían a admin.clientes.rank-schedules.update (antes customer_rank_schedule_register.php).
--}}
@php
    $offStyle = 'color: #FFFFFF; font-size:30px; background: #EE0000; border: 1px solid #EE0000;';
    $onStyle = 'color: #FFFFFF; font-size:30px; background: #336699; border: 1px solid #336699;';
@endphp
@extends('layouts.legacy')

@section('menu', 'clientes')

@section('title', '顧客ランク振り分け設定 - ' . $siteName)

@section('js')
<script type="text/javascript">
<!--

// =================================================================================================
// 数字入力チェック
// =================================================================================================

function replace_value(data) {

	data.value = data.value.replace(/[^0-9\.]/g, '');

}

function numbersonly(myfield, e, dec) {

	var key,keychar;

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
			if ( (myfield.value).indexOf(keychar) > -1 ) {
				return false;
			} else {
				return true;
			}
		}
	else
	   return false;

}


// =================================================================================================
// フォーム送信確認
// =================================================================================================

function submitChk( str ) {


	// 変数設定 --------------------------------------------------------------------------------

	var target,typeFlg,errTxt;

	if (str == '使用金額') {

		target = '#form1';
		typeFlg = $('#kingaku_typeFlgTbl input[type=radio]:checked').val();

	} else {

		target = '#form2';
		typeFlg = $('#raiten_typeFlgTbl input[type=radio]:checked').val();

	} // if (str == '使用金額') --- END


	// エラーチェック --------------------------------------------------------------------------

	errTxt = '';

	// 指定期間
	if ($(target+' input[name=rank_shori_kikan]').val() == '') {

		errTxt += "指定期間を入力してください。\n";

	}

	if (typeFlg == '1') {

		// 指定月
		if ($(target+' input[name="month[]"]:checked').length < 1) {

			errTxt += "指定月を選択してください。\n";

		}
		// 指定日
		if ($(target+' input[name="day[]"]:checked').length < 1) {

			errTxt += "指定日を選択してください。\n";

		}

	} else {

		// 指定曜日
		if ($(target+' input[name="week[]"]:checked').length < 1) {

			errTxt += "指定曜日を選択してください。\n";

		}

	} // if (typeFlg == '1') --- END

	if (errTxt != '') {

		jAlert(errTxt, "入力エラー");
		return false;

	} // if (error) --- END


	// エラーが無ければ送信確認 ----------------------------------------------------------------

	jConfirm("この内容で登録します。\nよろしいですか？",str+"ランク登録確認",
	function(ret){

		if (ret) {
			$(target).submit();
		} else {
			return false;
		}

	});


} // function submitChk( str ) --- END

// =================================================================================================
// 初期設定
// =================================================================================================

$(document).ready(function(){


	// 集計実行方法の変更 ----------------------------------------------------------------------

	$("input[id^='kingaku_type_flg']").change(function(){ changeTypeFlg('kingaku'); });
	$("input[id^='raiten_type_flg']").change(function(){ changeTypeFlg('raiten'); });

	function changeTypeFlg( type ) {

		var value = $('#'+type+'_typeFlgTbl input[type=radio]:checked').val();

		if (value == '1') {

			$('#'+type+'_week_set').hide();
			$('#'+type+'_month_set').show();

		} else {

			$('#'+type+'_month_set').hide();
			$('#'+type+'_week_set').show();

		}

	} // function changeTypeFlg( type ) --- END


	// 初期設定 --------------------------------------------------------------------------------

	changeTypeFlg('kingaku');
	changeTypeFlg('raiten');


});

// =================================================================================================


-->
</script>
@endsection

@section('content')
<div class="contents">

@if ($kingaku)

<p class="h2Title">■使用金額ランク 振り分け設定</p>

<div class="mB10 pB20">

<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.clientes.rank-schedules.update') }}">
@csrf
<fieldset>
<legend style="display:none;">使用金額ランク</legend>

<div class="mB10" style="padding: 0 5px; background: #F5F5F5; border: 1px solid #999999;">現在の振り分け設定は <span style="{{ $kingaku['active'] ? $onStyle : $offStyle }}">{{ $kingaku['active'] ? 'ON' : 'OFF' }}</span> です。</div>

@include('admin.clientes.ranks.partials.schedule_fields', ['type' => 'kingaku', 's' => $kingaku])

</fieldset>

<div class="btnBox">
<input type="button" name="enter" class="btn btnStyleM" value="この内容で設定する" onclick="return submitChk('使用金額');" />
<input type="hidden" name="act" value="kingaku" />
<input type="button" class="btn btnStyleM" value="設定を解除する" onclick="jConfirm('設定を解除してよろしいですか？','確認', function(ret){if(ret){document.kingaku_kaijo_kingaku_form.submit();}})" />
</div>

</form>

<form action="{{ route('admin.clientes.rank-schedules.update') }}" method="post" id="kingaku_kaijo_kingaku_form" name="kingaku_kaijo_kingaku_form" style="display: none;">
@csrf
<input type="hidden" name="act" value="kingaku" />
<input type="submit" value="設定解除" />
</form>

</div>

@endif

@if ($raiten)

<p class="h2Title">■来店回数ランク 振り分け設定</p>

<div class="mB10" style="padding: 0 5px; background: #F5F5F5; border: 1px solid #999999;">現在の振り分け設定は <span style="{{ $raiten['active'] ? $onStyle : $offStyle }}">{{ $raiten['active'] ? 'ON' : 'OFF' }}</span> です。</div>

<div class="pB10">

<form method="post" name="form2" id="form2" class="form" action="{{ route('admin.clientes.rank-schedules.update') }}">
@csrf
<fieldset>
<legend style="display:none;">来店回数ランク</legend>

@include('admin.clientes.ranks.partials.schedule_fields', ['type' => 'raiten', 's' => $raiten])

</fieldset>

<div class="btnBox">
<input type="button" name="enter" class="btn btnStyleM" value="この内容で設定する" onclick="return submitChk('来店回数');" />
<input type="hidden" name="act" value="raiten" />
<input type="button" class="btn btnStyleM" value="設定を解除する" onclick="jConfirm('設定を解除してよろしいですか？','確認', function(ret){if(ret){document.raiten_kaijo_kingaku_form.submit();}})" />
</div>

</form>

<form action="{{ route('admin.clientes.rank-schedules.update') }}" method="post" id="raiten_kaijo_kingaku_form" name="raiten_kaijo_kingaku_form" style="display: none;">
@csrf
<input type="hidden" name="act" value="raiten" />
<input type="submit" value="設定解除" />
</form>

</div>

@endif

<div style="height: 300px;">&nbsp;</div>

<!-- / .contents --></div>
@endsection
