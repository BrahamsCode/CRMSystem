{{--
    ガチャリン管理 各種設定 (system/category6/kujibiki/kujibiki_config.php): cuatro formularios, cada uno con su act=,
    que se envían a la misma pantalla (admin.mypage.lottery.update).

    CONTRATO DE DATOS
        $config    array  LotteryConfig::read():
                   disp_flg ?string ('1' = 使用する), max ?int, limit_flg ?string ('0'..'5'), limit_num ?int
                   (días o epoch), prizes [ダイヤ|ゴールド|シルバー|ブロンズ => [rate ?float, num ?int]],
                   miss_rate float (ハズレ, 100 si no hay fila)
        $siteName  string (núcleo)
--}}
@extends('layouts.legacy')

@section('title', 'ガチャリン管理 各種設定 - ' . $siteName)

@section('menu', 'mypage')

@section('css')
<style type="text/css">
<!--

/* CSS code */

-->
</style>

<link rel="stylesheet" type="text/css" href="{{ asset('legacy/system/plugin/ui-datepicker/ui.datepicker.css') }}" />
@endsection

@section('js')
<script type="text/javascript" src="{{ asset('legacy/system/plugin/ui-datepicker/ui.datepicker-ja.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/plugin/ui-datepicker/gcalendar-holidays.js') }}"></script>

<script type="text/javascript">
<!--

// =================================================================================================
// 数字のみを入力するファンクション
// =================================================================================================

var no_digit = /[^0-9\.]/g;

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
// 初期設定
// =================================================================================================

$(document).ready(function() {

	// 抽選設定の変更ボタン
	$('#kujibiki_rate_enter').bind('click',function(){

		var rateDia = $('#kujibiki_dia_rate').val() -0;
		var rateGold = $('#kujibiki_gold_rate').val() -0;
		var rateSilver = $('#kujibiki_silver_rate').val() -0;
		var rateBronze = $('#kujibiki_bronze_rate').val() -0;

		if( ( rateDia + rateGold + rateSilver + rateBronze ) > 100 ) {

			jAlert("抽選確率(％)は、ハズレ以外の4項目の合計が100以内になるように入力してください。","抽選設定　入力エラー");

		} else {

			$('#kujibiki_rate_form').submit();

		}

	});


	// クーポンとの交換期限
	var limit_flg_val = $('#kujibiki_limit_flg').val();
	$("#time_limit_type2 input").datepicker();
	$("#kujibiki_limit_flg").change(function() {

		limit_flg_val = $('#kujibiki_limit_flg').val();
		switch (limit_flg_val) {

			case "4":
				$("#time_limit_type1").show();
				$("#time_limit_type2").hide();
				break;
			case "5":
				$("#time_limit_type1").hide();
				$("#time_limit_type2").show();
				break;
			default:
				$("#time_limit_type1").hide();
				$("#time_limit_type2").hide();
				break;

		}

	});
	$("#kujibiki_limit_flg_form").submit(function() {

		limit_flg_val = $('#kujibiki_limit_flg').val();
		switch (limit_flg_val) {

			case "4":
				$('#time_limit').val($("#time_limit_type1 input").val());
				break;
			case "5":
				$('#time_limit').val($("#time_limit_type2 input").val());
				break;
			default:
				$('#time_limit').val('');
				break;

		}

	});

	$(".ac5").accordion('option', 'active', 0);

});

-->
</script>
@endsection

@section('content')
@php
    $limitOptions = ['', '当選から1週間', '当選から1ヶ月', '当選から3ヶ月', '当選から○○日間', '指定日付まで'];
    $limitFlg = $config['limit_flg'];
    $timeLimit = $config['limit_num'];
    $timeLimitDays = $limitFlg === '4' ? $timeLimit : null;
    if ($limitFlg === '5') {
        $timeLimit = date('Y/m/d', (int) $timeLimit);
    }
    $timeLimitDate = $limitFlg === '5' ? $timeLimit : null;
    $enabled = $config['disp_flg'] === '1';
    $updateUrl = route('admin.mypage.lottery.update');
    $numberEvents = 'onKeyPress="return numbersonly(this, event)" onkeyup="replace_value(this)" onmouseover="replace_value(this)" onchange="replace_value(this)"';
    $prizeRows = ['ダイヤ' => 'dia', 'ゴールド' => 'gold', 'シルバー' => 'silver', 'ブロンズ' => 'bronze'];
@endphp
<div class="contents">
<p class="h2Title">■各種設定</p>





<form method="post" class="form" action="{{ $updateUrl }}">
@csrf

<table class="tableBox">

	<tr>
		<th><label for="kujibiki_disp_flg1"><small>利用設定</small></label></th>
		<td style="text-align:left;">

		<input type="radio" name="kujibiki_disp_flg" id="kujibiki_disp_flg1" value="9"@if (! $enabled) checked="checked"@endif />
		<label for="kujibiki_disp_flg1">ガチャリンを使用しない</label><br />
		<input type="radio" name="kujibiki_disp_flg" id="kujibiki_disp_flg2" value="1"@if ($enabled) checked="checked"@endif />
		<label for="kujibiki_disp_flg2">ガチャリンを使用する</label><br />

		<input type="submit" name="enter" id="kujibiki_disp_flg_enter" class="btn" style="color:red" value="変更" />

		</td>
	</tr>

</table>

<input type="hidden" name="act" value="change_kujibiki_disp_flg" />

</form>





<form method="post" class="form" action="{{ $updateUrl }}">
@csrf

<table class="tableBox">

	<tr>
		<th><label for="kujibiki_max"><small>1日の利用可能回数</small></label></th>
		<td style="text-align:left;">

		1日に&nbsp;<input type="text" name="kujibiki_max" id="kujibiki_max" class="input2S" value="{{ $config['max'] }}" maxlength="4" style="ime-mode:disabled;" {!! $numberEvents !!} />回<br />
		<input type="submit" name="enter" id="kujibiki_max_enter" class="btn" style="color:red" value="変更" />

		</td>
	</tr>

</table>

<input type="hidden" name="act" value="change_kujibiki_max" />

</form>





<form method="post" class="form" id="kujibiki_limit_flg_form" action="{{ $updateUrl }}">
@csrf

<table class="tableBox">

	<tr>
		<th><label for="kujibiki_limit_flg"><small>クーポンとの交換期限</small></label></th>
		<td style="text-align:left;">

		<select name="kujibiki_limit_flg" id="kujibiki_limit_flg" style="margin-right:10px;">
		@foreach ($limitOptions as $i => $label)
<option value="{{ $i }}"{{ (float) $limitFlg == $i ? ' selected' : '' }}>{{ $label }}</option>
		@endforeach
		</select>

		<span id="time_limit_span">

			<span id="time_limit_type1"@if ($limitFlg !== '4') style="display:none;"@endif>
			日数：<input type="text" name="time_limit_val1" maxlength="3" size="3" value="{{ $timeLimitDays }}" {!! $numberEvents !!} style="ime-mode:disabled;" />&nbsp;日
			</span>

			<span id="time_limit_type2"@if ($limitFlg !== '5') style="display:none;"@endif>
			日付：<input type="text" name="time_limit_val2" maxlength="10" size="12" value="{{ $timeLimitDate }}" style="ime-mode:disabled;" />&nbsp; (例: 2009/12/01)
			</span>

		</span>
		<input type="hidden" name="time_limit" id="time_limit" value="{{ $timeLimit }}" /><br />

		<input type="submit" name="enter" id="kujibiki_limit_flg_enter" class="btn" style="color:red" value="変更" />

		</td>
	</tr>

</table>

<input type="hidden" name="act" value="change_kujibiki_limit_flg" />

</form>





<form method="post" class="form" id="kujibiki_rate_form" action="{{ $updateUrl }}">
@csrf

<table class="tableBox">

	<tr>
		<th><label for="kujibiki_dia_rate"><small>抽選設定</small></label></th>
		<td style="text-align:left;">

		※抽選確率(％)はハズレ以外の4項目の合計が100以内になるように入力してください。<br />
		<table>

			<tr class="textS">
			<th align="center" valign="middle">&nbsp;</th>
			<th align="center" valign="middle">抽選確率(％)</th>
			<th align="center" valign="middle">1日の当選本数</th>
			</tr>

			@foreach ($prizeRows as $name => $prefix)
			<tr>
			<td align="center" valign="middle">{{ $name }}</td>
			<td align="center" valign="middle"><input type="text" name="kujibiki_{{ $prefix }}_rate" id="kujibiki_{{ $prefix }}_rate" class="input2S" value="{{ $config['prizes'][$name]['rate'] }}" maxlength="3" style="ime-mode:disabled;" {!! $numberEvents !!} />％</td>
			<td align="center" valign="middle"><input type="text" name="kujibiki_{{ $prefix }}_num" id="kujibiki_{{ $prefix }}_num" class="input2S" value="{{ $config['prizes'][$name]['num'] }}" maxlength="5" style="ime-mode:disabled;" onKeyPress="return numbersonly(this, event, 2)" onkeyup="replace_value(this)" onmouseover="replace_value(this)" onchange="replace_value(this)" />本</td>
			</tr>

			@endforeach
			<tr>
			<td align="center" valign="middle">ハズレ</td>
			<td align="center" valign="middle">{{ $config['miss_rate'] }}&nbsp;％</td>
			<td align="center" valign="middle">無制限</td>
			</tr>

		</table>

		<input type="button" name="enter" id="kujibiki_rate_enter" class="btn" style="color:red" value="変更" />

		</td>
	</tr>

</table>

<input type="hidden" name="act" value="change_kujibiki_rate" />

</form>





<!-- / .contents --></div>
@endsection
