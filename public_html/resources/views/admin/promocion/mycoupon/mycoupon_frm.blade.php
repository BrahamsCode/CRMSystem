{{--
	クーポン 新規登録 / 修正 — legacy: system/category3/mycoupon/mycoupon_frm.php (+ mycoupon_new.php, mycoupon_update.php)
	Variables: $title, $mode, $action, $data (campos; texto ya con get_form), $errors [campo => HTML del legacy], $cd,
	  $shops, $isMaster, $kubunOptions, $showPoints
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', $title . ' ' . $mode)

@php
	$d = fn (string $k) => (string) ($data[$k] ?? '');
	$bg = fn (string $k) => isset($errors[$k]) ? ' class="Ng"' : '';
	$attention = $d('attention') !== '' ? explode('<>', $d('attention')) : [];
	$kubun = $d('coupon_kubun');
@endphp

@section('css')
<link rel="stylesheet" type="text/css" href="{{ asset('legacy/system/plugin/myflexselect/myFlexSelect.css') }}" />
<link rel="stylesheet" type="text/css" href="{{ asset('legacy/system/plugin/ui-datepicker/ui.datepicker.css') }}" />
@endsection

@section('js')
<script type="text/javascript" src="{{ asset('legacy/system/plugin/myflexselect/myFlexSelect.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/plugin/ui-datepicker/ui.datepicker-ja.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/plugin/ui-datepicker/gcalendar-holidays.js') }}"></script>
<script type="text/javascript">
<!--
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

	if ((key==null) || (key==0) || (key==8) ||
	    (key==9) || (key==13) || (key==27) )
	   return true;
	else if ((("0123456789").indexOf(keychar) > -1))
	   return true;
	else if (dec && (keychar == "."))
	   {
	   myfield.form.elements['dec'].focus();
	   return false;
	   }
	else
	   return false;
}

$(document).ready(function() {
	$(".ac7").accordion('option', 'active', 0);
	window.scroll(0,0);

	$("#coupon_kubun").change(function () {
		var thisVal = $(this).val();
		if (thisVal == '2' || thisVal == '10') {
			$("#shouhi_point").removeAttr("disabled");
		}else{
			$("#shouhi_point").attr("disabled","disabled");
		}
		if (thisVal == '4') {
			$("#saitouroku_flg_block").show();
		}else{
			$("#saitouroku_flg_block").hide();
		}
		if (thisVal == '9') {
			$("#kujibiki_name_block").show();
		}else{
			$("#kujibiki_name_block").hide();
		}
	});

	var limit_flg_val = $('#limit_flg').val();
	$("#time_limit_type2 input").datepicker();

	$("#limit_flg").change(function() {
		limit_flg_val = $('#limit_flg').val();
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

	$("#form1").submit(function() {
		limit_flg_val = $('#limit_flg').val();
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

	$("#use_templete").click(function (){
		$("#attention0").val("※ご来店の際に本クーポンをご提示ください。");
		$("#attention1").val("※発行店舗以外でのご使用はできません。");
		$("#attention2").val("※他のクーポン、割引サービスとは併用できません。");
		$("#attention3").val("※おひとり様一枚に限り使用可能です。");
	});
});
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■{{ $title }} {{ $mode }}</p>
<div>
<form method="post" name="form1" id="form1" class="form" action="{{ $action }}">
@csrf
<fieldset>
<legend>{{ $title }}</legend>
<table class="tableBox">
	<tr>
		<th><label for="coupon_kubun">使用区分</label></th>
		<td{!! $bg('coupon_kubun') !!}>
		<select name="coupon_kubun" id="coupon_kubun">
		@foreach ($kubunOptions as $value => $label)
		<option value="{{ $value }}" @selected($kubun === (string) $value)>{{ $label }}</option>
		@endforeach
		</select>
		{!! $errors['coupon_kubun'] ?? '' !!}
		</td>
	</tr>
	<tr>
		<th><label for="tb_shop_cd">店舗<em>※</em></label></th>
		<td{!! $bg('tb_shop_cd') !!}>
		@if ($shops->count() === 1 || ! $isMaster)
		{{ $shops->first()?->kaishamei }}
		<input type="hidden" name="tb_shop_cd" id="tb_shop_cd" value="{{ $shops->first()?->cd }}">
		@else
		<select name="tb_shop_cd" id="tb_shop_cd">
		<option value="">▼店舗選択</option>
		<option value="-1" @selected($d('tb_shop_cd') === '-1')>全店舗</option>
		@foreach ($shops as $shop)
		<option value="{{ $shop->cd }}" @selected($d('tb_shop_cd') === (string) $shop->cd)>{{ $shop->kaishamei }}</option>
		@endforeach
		</select>
		@endif
		{!! $errors['tb_shop_cd'] ?? '' !!}
		</td>
	</tr>
	<tr>
		<th><label for="title">タイトル<em>※</em></label></th>
		<td{!! $bg('title') !!}>
		<input type="text" name="title" id="title" maxlength="100" size="100" class="writeArea" value="{{ $d('title') }}" />
		{!! $errors['title'] ?? '' !!}</td>
	</tr>
	<tr>
		<th><label for="comment">内容</label></th>
		<td{!! $bg('comment') !!}>
		<textarea name="comment" id="comment" class="writeArea" rows="4">{{ $d('comment') }}</textarea>
		{!! $errors['comment'] ?? '' !!}</td>
	</tr>
@if ($showPoints)
	<tr>
		<th><label for="shouhi_point">消費スタンプ・ポイント数<em>※</em></label></th>
		<td{!! $bg('shouhi_point') !!}>
		<script type="text/javascript">
		$(document).ready(function(){
			var options = [{!! implode(',', array_map(fn ($n) => '"' . $n . '"', range(1, 50))) !!}];
			$('#shouhi_point').myFlexSelect({ options: options });
		});
		</script>
		<input type="text" name="shouhi_point" id="shouhi_point" maxlength="6" size="6" value="{{ $d('shouhi_point') }}" style="ime-mode:disabled;" onkeypress="return numbersonly(this, event)" onkeyup="replace_value(this)" onmouseover="replace_value(this)" onchange="replace_value(this)"@if ($kubun !== '2' && $kubun !== '10') disabled @endif />
		※使用区分がスタンプ・ポイント用の場合のみ選択必須となります。
		{!! $errors['shouhi_point'] ?? '' !!}</td>
	</tr>
@endif
	<tr>
		<th><label for="limit_flg">有効期限<em>※</em></label></th>
		<td{!! $bg('limit_flg') !!}>
		<select name="limit_flg" id="limit_flg" clss="mR10">
		@foreach (\App\Http\Controllers\Admin\Promotions\CouponController::LIMIT_LABELS as $i => $label)
		<option value="{{ $i }}" @selected((int) $d('limit_flg') === $i)>{{ $label === '発行から○○日間' ? '発行から○○日間' : $label }}</option>
		@endforeach
		</select>
		@if ($d('shouhi_point') !== '')<span class="attention mL5">※ユーザーがスタンプと交換してからの期限になります。</span>@endif
		<span id="time_limit_span">
		<span id="time_limit_type1"@if ($d('limit_flg') !== '4') style="display:none;"@endif>日数：<input type="text" name="time_limit_val1" maxlength="3" size="3" value="{{ $d('time_limit_val1') }}" style="ime-mode:disabled;" />&nbsp;日</span>
		<br /><span id="time_limit_type2"@if ($d('limit_flg') !== '5') style="display:none;"@endif>日付：<input type="text" name="time_limit_val2" maxlength="10" size="12" value="{{ $d('time_limit_val2') }}" style="ime-mode:disabled;" />&nbsp; (例: 2009/12/01)</span>
		</span>
		<input type="hidden" name="time_limit" id="time_limit" value="{{ $d('time_limit') }}" />
		{!! $errors['limit_flg'] ?? '' !!}
		</td>
	</tr>
	<tr id="saitouroku_flg_block"@if ($kubun !== '4') style="display: none;"@endif>
		<th><label for="saitouroku_flg1">クーポン発行条件</label></th>
		<td>
		<input type="radio" name="saitouroku_flg" id="saitouroku_flg1" class="checkBtn" @checked($d('saitouroku_flg') !== '1') value="" /><label for="saitouroku_flg1">一度退会して再登録した場合も発行する</label><br />
		<input type="radio" name="saitouroku_flg" id="saitouroku_flg2" class="checkBtn" @checked($d('saitouroku_flg') === '1') value="1" /><label for="saitouroku_flg2">一度退会して再登録した場合は発行しない</label>
		</td>
	</tr>
	<tr id="kujibiki_name_block"@if ($kubun !== '9') style="display: none;"@endif>
		<th><label for="kujibiki_name">ガチャリンランク</label></th>
		<td>
		<select name="kujibiki_name" id="kujibiki_name">
		@foreach (\App\Http\Controllers\Admin\Promotions\CouponController::LOTTERY_RANKS as $rank)
		<option value="{{ $rank }}" @selected($d('kujibiki_name') === $rank)>{{ $rank }}</option>
		@endforeach
		</select>
		</td>
	</tr>
	<tr>
		<th><label for="attention0">注意事項</label></th>
		<td{!! $bg('attention') !!}><input type="button" name="use_templete" id="use_templete" class="btnStyle" value="テンプレートを使用する" /><br />
		@for ($i = 0; $i < 5; $i++)
		<input type="text" name="attention[]" id="attention{{ $i }}" maxlength="100" size="100" value="{{ $attention[$i] ?? '' }}" class="writeArea" style="margin:2px 0;" /><br />
		@endfor
		{!! $errors['attention'] ?? '' !!}</td>
	</tr>
</table>
</fieldset>
<div class="btnBox">
<input type="hidden" name="cd" id="cd" value="{{ $cd }}" />
<input type="hidden" name="act" id="act" value="up" />
<input type="submit" name="send" id="send" class="btn btnStyleM" value="この内容で登録する" />
</div>
</form>
</div>
</div>
@endsection
