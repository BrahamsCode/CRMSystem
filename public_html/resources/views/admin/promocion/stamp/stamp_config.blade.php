{{--
	スタンプ仕様管理 — legacy: system/category3/stamp/stamp_config.php (cada formulario envía su `act` a stamp_config_register)
	Variables: $shops (tiendas del personal), $intervals (cd => segundos), $general (tb_stamp_config de tb_shop_cd 1),
	  $admin (admin_master), $isMaster, $isDevelop, $isTablet, $stampAccounting (スタンプ会計処理), $rateShops, $ended
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', 'スタンプ仕様管理' . ' - ' . $siteName)

@php
	use App\Http\Controllers\Admin\Promotions\StampConfigController as Config;

	$a = fn (string $k) => (string) ($admin[$k] ?? '');
	$g = fn (string $k) => (string) ($general[$k] ?? '');
	$action = route('admin.promocion.stamps.config.update');
	$hours = fn ($selected) => collect(range(0, 23))->map(fn ($i) => '<option value="' . sprintf('%02d', $i) . '"' . ((string) $selected === sprintf('%02d', $i) ? ' selected="selected"' : '') . '>' . sprintf('%02d', $i) . '</option>')->implode("\n");
	$halfHours = fn ($selected) => '<option value="00"' . ((string) $selected === '00' ? ' selected="selected"' : '') . '>00</option>' . "\n" . '<option value="30"' . ((string) $selected === '30' ? ' selected="selected"' : '') . '>30</option>';
	$num = 'style="ime-mode:disabled;" onKeyPress="return numbersonly(this, event)" onkeyup="replace_value(this)" onmouseover="replace_value(this)" onchange="replace_value(this)"';
	$dec = 'style="ime-mode:disabled;" onKeyPress="return numbersonly(this, event, 2)" onkeyup="replace_value(this)" onmouseover="replace_value(this)" onchange="replace_value(this)"';
	[$shikkouH, $shikkouM] = explode(':', $a('stamp_shikkou_time') !== '' ? $a('stamp_shikkou_time') : '12:00') + [1 => ''];
	[$alertH, $alertM] = explode(':', $a('stamp_shikkou_alert_time') !== '' ? $a('stamp_shikkou_alert_time') : '12:00') + [1 => ''];
	$perLine = $shops->count() > 1 ? 2 : 1; // 一行で表示する店舗数
	$shopCount = $shops->count();
@endphp

@section('css')
<link href="{{ asset('legacy/system/css/jquery.alerts.css') }}" rel="stylesheet" type="text/css" media="all" />
<link rel="stylesheet" type="text/css" href="{{ asset('legacy/system/css/popInline.css') }}" />
@endsection

@section('js')
<script type="text/javascript" src="{{ asset('legacy/system/js/jquery.alerts.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/js/popInline.js') }}"></script>
<script type="text/javascript">
// 数字のみを入力するファンクション
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

function submitCeck(){
	$(".submitForm").submit(function(e){
		e.preventDefault();
		jConfirm("更新してよろしいですか？", "確認", function(r){
			if(r == true) e.target.submit();
		});
	});
}

function change_stamp_shikkou_sousa_func(target){
	var mess = "";

	$("#"+target).submit(function(e){
		e.preventDefault();

		if( $("input[name=stamp_shikkou_sousa]:checked").val() == 1){
			$.ajax({
				type: "POST",
				url: "{{ route('admin.promocion.stamps.expiry-preview') }}",
				success: function(data){
					if(data == "ng") return false;

					if(data != ""){
						mess = "有効期限の設定を、「有効」に変更しようとしています。\n";
						mess += "設定変更に伴い、"+data+"\n";
						mess += "\n";
						mess += "これらのユーザーのスタンプ有効期限の設定を有効にしてよろしいですか？";
					}else{
						mess = "設定してよろしいですか？";
					}

					jConfirm( mess, "確認", function(r){
						if(r == true) e.target.submit();
					});
				}
			});

		}else{
			mess = "有効期限の設定を、「無制限」へ変更しようとしています。\n";
			mess += "\n";
			mess += "スタンプの有効期限を有効にする際は、ユーザーの有効期限は再設定される事になります。\n";
			mess += "\n";
			mess += "設定してよろしいですか？";

			jConfirm( mess, "確認", function(r){
				if(r == true) e.target.submit();
			});
		}
	});
}

$(document).ready(function() {
	$(".ac6").accordion('option', 'active', 0);
	window.scroll(0,0);

	popIni();

	// Tablet (CRMRW107 / CRMH580VT_A): la app abre la plantilla con roid.popUp y URL absoluta
	$("#stampShikkouMail").click(function(){
		@if ($isTablet)
		roid.popUp("{{ route('admin.promocion.stamps.template', ['type' => 'shikkou']) }}");
		@else
		openPopWindow("{{ route('admin.promocion.stamps.template', ['type' => 'shikkou']) }}", '650', '');
		@endif
	});

	$("#stampAlertMail").click(function(){
		@if ($isTablet)
		roid.popUp("{{ route('admin.promocion.stamps.template', ['type' => 'alert']) }}");
		@else
		openPopWindow("{{ route('admin.promocion.stamps.template', ['type' => 'alert']) }}", '650', '');
		@endif
	});

	$("#zen_kankaku").change(function(){
		val = $("#zen_kankaku").val();
		if (val != "") $(".kankaku").val(val);
	});

	$(".kankaku").change(function(){
		$("#zen_kankaku").val("");
	});

	submitCeck();
	change_stamp_shikkou_sousa_func("change_stamp_shikkou_sousa_form");

@if ($ended)
	jAlert("更新されました。", "通知");
@endif
});
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■スタンプ仕様管理</p>

<div>

@if ($shopCount > 0)
<form method="post" name="form1" id="form1" class="form submitForm" action="{{ $action }}">
@csrf

<fieldset>
<legend>スタンプ動作設定</legend>

@if ($isMaster)
<table class="tableBox">
	<colgroup>
		<col style="width:14em;">
		<col style="width:auto;">
	</colgroup>
<tr>
	<th><label for="stamp_max">マイページ表示スタンプ数</label></th>
	<td>
		<select name="stamp_max" id="stamp_max">
		@foreach (Config::MAX_OPTIONS as $label => $value)
			<option value="{{ $value }}" @if ($g('stamp_max') === (string) $value) selected @endif>{{ $label }}</option>
		@endforeach
		</select><br />
		<span class="textS">※スタンプのMAX個数を設定する事ができます。<br />
		※50個を超える設定の場合は表示形式が変更されます。</span>
	</td>
</tr>
<tr>
	<th><label for="touroku_bonus">新規登録スタンプ数</label></th>
	<td>
		<select name="touroku_bonus" id="touroku_bonus">
		@for ($row = 0; $row <= 10; $row++)
			<option value="{{ $row }}" @if ((int) $g('touroku_bonus') === $row) selected @endif>{{ $row }}個</option>
		@endfor
		</select><br />
		<span class="textS">※新規登録時の登録ボーナススタンプを設定する事ができます。</span>
	</td>
</tr>
<tr>
	<th>マイページ スタンプ交換用<br />クーポン表示形式</th>
	<td>
	<select name="mypage_stamp_hyouji_flg" id="mypage_stamp_hyouji_flg">
	<option value=""@if ($a('mypage_stamp_hyouji_flg') === '') selected="selected"@endif>店舗別表示</option>
	<option value="1"@if ($a('mypage_stamp_hyouji_flg') === '1') selected="selected"@endif>一括表示</option>
	<option value="2"@if ($a('mypage_stamp_hyouji_flg') === '2') selected="selected"@endif>登録店舗のみ表示</option>
	</select><br />
	<span class="textS">※店舗別表示では、登録店舗分が優先して表示され、各店舗毎に表示を切り替える形式です。<br />
	※一括表示では、店舗ごとの区分は無く、全てが一括で表示される形式です。</span>
	</td>
</tr>
</table>
<br />
@endif

@if ($shopCount > 1)
<table class="tableBox" style="width:50%;">
<tr>
	<th style="width:60%; text-align:center;">&nbsp;</th>
	<th style="width:40%; text-align:center;">間隔</th>
</tr>
<tr>
	<th style="text-align:center;">全店舗</th>
	<td style="text-align:center;">
		<select id="zen_kankaku">
			<option value=""></option>
		@foreach (Config::INTERVALS as $seconds => $label)
			<option value="{{ $seconds }}">{{ $label }}</option>
		@endforeach
		</select>
	</td>
</tr>
</table>

<p><span class="textS">※全店舗変更をすると以前の設定が消えてしまいますので、変更の際はご注意ください。</span></p><br />
@endif
<table class="tableBox">
<tr>
@for ($i = 0; $i < $perLine; $i++)
		<th style="width:30%;">店舗名</th>
		<th style="width:20%; text-align:center;">間隔</th>
@endfor
</tr>
@foreach ($shops->values() as $i => $shop)
	@if ($i % $perLine === 0)
<tr>
	@endif
		<th>{{ $shop->kaishamei }}</th>
		<td style="text-align:center;">
		<select name="kankaku[{{ $shop->cd }}]" class="kankaku">
		@foreach (Config::INTERVALS as $seconds => $label)
			<option value="{{ $seconds }}" @if (($intervals[$shop->cd] ?? 1800) === $seconds) selected @endif>{{ $label }}</option>
		@endforeach
		</select>
		</td>
	@if ($i % $perLine === $perLine - 1)
</tr>
	@endif
@endforeach
@if (($shopCount - 1) % $perLine !== $perLine - 1)
		<td colspan="{{ (($perLine - 1) - ($shopCount - 1) % $perLine) * 3 }}">&nbsp;</td>
	</tr>
@endif

</table>
</fieldset>

<div class="btnBox">
<input type="hidden" name="act" id="act" value="up" />
<input type="hidden" name="anchor_id" value="form1" />
<input type="submit" name="send" id="send" class="btnStyleM" value="更新" />
</div>

</form>

@else
設定はありません。
@endif

<!-- / .form --></div>

<!-- / .contents --></div>

@if ($isMaster && $admin !== [])
<div class="contents">
<p id="stampYukoukigenSettei" class="h2Title">■スタンプ有効期限設定</p>

<fieldset class="form">
<legend>スタンプ管理</legend>

{{-- 来店時にスタンプを獲得するかどうか --}}
<form method="post" name="change_raiten_stamp_flg_form" id="change_raiten_stamp_flg_form" class="form" action="{{ $action }}">
@csrf
<table class="tableBox">
	<colgroup>
		<col style="width:14em;">
		<col style="width:auto;">
	</colgroup>
	<tr>
		<th><label for="raiten_stamp_flg1"><small>来店時のスタンプ</small></label></th>
		<td style="text-align:left;">
		<input type="radio" name="raiten_stamp_flg" id="raiten_stamp_flg1" value=""@if ($a('raiten_stamp_flg') !== '2') checked="checked"@endif />
		<label for="raiten_stamp_flg1">スタンプを獲得する</label><br />
		<input type="radio" name="raiten_stamp_flg" id="raiten_stamp_flg2" value="2"@if ($a('raiten_stamp_flg') === '2') checked="checked"@endif />
		<label for="raiten_stamp_flg2">スタンプを獲得しない</label><br />

		<input type="submit" name="enter" class="btnStyleS" style="color:red!important" value="変更" />
		</td>
	</tr>
</table>
<input type="hidden" name="anchor_id" value="change_raiten_stamp_flg_form" />
<input type="hidden" name="act" value="change_raiten_stamp_flg" />
</form>

{{-- スタンプ有効期間の使用選択 --}}
<form method="post" name="change_stamp_shikkou_sousa_form" id="change_stamp_shikkou_sousa_form" class="form" action="{{ $action }}">
@csrf
<table class="tableBox">
	<colgroup>
		<col style="width:14em;">
		<col style="width:auto;">
	</colgroup>
	<tr>
		<th><label for="stamp_shikkou_sousa1"><small>スタンプ有効期間の使用選択</small></label></th>
		<td style="text-align:left;">
		<input type="radio" name="stamp_shikkou_sousa" id="stamp_shikkou_sousa1" value="1"@if (in_array($a('stamp_shikkou_sousa'), ['1', ''], true)) checked="checked"@endif />
		<label for="stamp_shikkou_sousa1">スタンプ有効期間の設定を有効にする</label><br />
		<input type="radio" name="stamp_shikkou_sousa" id="stamp_shikkou_sousa2" value="2"@if ($a('stamp_shikkou_sousa') !== '1') checked="checked"@endif />
		<label for="stamp_shikkou_sousa2">スタンプ有効期間の設定を無効にする（無期限）</label><br />

		<input type="submit" name="enter" class="btnStyleS" style="color:red!important" value="変更" />
		</td>
	</tr>
</table>
<input type="hidden" name="anchor_id" value="change_stamp_shikkou_sousa_form" />
<input type="hidden" name="act" value="change_stamp_shikkou_sousa" />
</form>

{{-- 有効期間 (vacío: 365日) --}}
<form method="post" name="change_stamp_shikkou_kikan_form" id="change_stamp_shikkou_kikan_form" class="form submitForm" action="{{ $action }}">
@csrf
<table class="tableBox">
	<colgroup>
		<col style="width:14em;">
		<col style="width:auto;">
	</colgroup>
	<tr>
		<th><label for="stamp_shikkou_kikan"><small>有効期間</small></label></th>
		<td style="text-align:left;">
		<input type="text" name="stamp_shikkou_kikan" id="stamp_shikkou_kikan" class="input2S" value="{{ $a('stamp_shikkou_kikan') === '' ? 365 : $a('stamp_shikkou_kikan') }}" maxlength="3" {!! $num !!} />日<br />
		<input type="submit" name="enter" class="btnStyleS" style="color:red!important" value="変更" />
		<br />※有効期限は0～999日で設定してください。
		</td>
	</tr>
</table>
<input type="hidden" name="anchor_id" value="change_stamp_shikkou_kikan_form" />
<input type="hidden" name="act" value="change_stamp_shikkou_kikan" />
</form>

{{-- 失効処理を行う時間 (vacío: 12:00) --}}
<form method="post" name="change_stamp_shikkou_time_form" id="change_stamp_shikkou_time_form" class="form submitForm" action="{{ $action }}">
@csrf
<table class="tableBox">
	<colgroup>
		<col style="width:14em;">
		<col style="width:auto;">
	</colgroup>
	<tr>
		<th><label for="stamp_shikkou_time_h"><small>失効処理を行う時間</small></label></th>
		<td style="text-align:left;">
		<select name="stamp_shikkou_time_h" id="stamp_shikkou_time_h">
		{!! $hours($shikkouH) !!}
		</select>&nbsp;時&nbsp;&nbsp;
		<select name="stamp_shikkou_time_m" id="stamp_shikkou_time_m">
		{!! $halfHours($shikkouM) !!}
		</select>&nbsp;分<br />

		※毎日同時刻に失効処理を実行します。<br />

		<input type="submit" name="enter" class="btnStyleS" style="color:red!important" value="変更" />
		<input type="button" id="stampShikkouMail" class="btnStyleS" value="通知内容" />
		</td>
	</tr>
</table>
<input type="hidden" name="anchor_id" value="change_stamp_shikkou_sousa_form" />
<input type="hidden" name="act" value="change_stamp_shikkou_time" />
</form>

{{-- スタンプ有効期限切れ前通知メール (vacío: 30日, 12:00) --}}
<form method="post" name="change_stamp_shikkou_alert_form" id="change_stamp_shikkou_alert_form" class="form submitForm" action="{{ $action }}">
@csrf
<table class="tableBox">
	<colgroup>
		<col style="width:14em;">
		<col style="width:auto;">
	</colgroup>
	<tr>
		<th><label for="stamp_shikkou_alert"><small>スタンプ有効期限切れ前通知メール</small></label></th>
		<td style="text-align:left;">
		<input type="text" name="stamp_shikkou_alert" id="stamp_shikkou_alert" class="input2S" value="{{ $a('stamp_shikkou_alert') === '' ? 30 : $a('stamp_shikkou_alert') }}" maxlength="3" {!! $num !!} />日前の&nbsp;&nbsp;
		<select name="stamp_shikkou_alert_time_h" id="stamp_shikkou_alert_time_h">
		{!! $hours($alertH) !!}
		</select>&nbsp;時&nbsp;&nbsp;
		<select name="stamp_shikkou_alert_time_m" id="stamp_shikkou_alert_time_m">
		{!! $halfHours($alertM) !!}
		</select>&nbsp;分に送信<br />

		<input type="submit" name="enter" class="btnStyleS" style="color:red!important" value="変更" />
		<input type="button" id="stampAlertMail" class="btnStyleS" value="通知内容" />
		</td>
	</tr>
</table>
<input type="hidden" name="anchor_id" value="change_stamp_shikkou_alert_form" />
<input type="hidden" name="act" value="change_stamp_shikkou_alert" />
</form>

@if ($isDevelop)
{{-- スタンプ有効期間更新の仕様 (solo desarrollo, como el legacy) --}}
<form method="post" name="change_stamp_update_type_form" id="change_stamp_update_type_form" class="form" action="{{ $action }}">
@csrf
<table class="tableBox">
	<colgroup>
		<col style="width:14em;">
		<col style="width:auto;">
	</colgroup>
	<tr>
		<th><label for="stamp_update_type_flg1"><small>スタンプ有効期間更新の仕様</small></label></th>
		<td style="text-align:left;">
		<input type="radio" name="stamp_update_type_flg" id="stamp_update_type_flg1" value="0"@if ($a('stamp_update_type_flg') !== '1') checked="checked"@endif />
		<label for="stamp_update_type_flg1">取得のたびに期限を更新</label><br />
		<input type="radio" name="stamp_update_type_flg" id="stamp_update_type_flg2" value="1"@if ($a('stamp_update_type_flg') === '1') checked="checked"@endif />
		<label for="stamp_update_type_flg2">期限が切れるまで初めの取得から固定</label><br />

		<input type="submit" name="enter" class="btnStyleS" style="color:red!important" value="変更" />
		</td>
	</tr>
</table>
<input type="hidden" name="anchor_id" value="change_stamp_update_type_flg_form" />
<input type="hidden" name="act" value="change_stamp_update_type_flg" />
</form>
@endif

</fieldset>
<!-- / div.contents END --></div>

@if ($stampAccounting)
<div class="contents">

<p id="stampApliSettei" class="h2Title">■スタンプアプリ設定</p>

<form method="post" name="change_stamp_settings_form" id="change_stamp_settings_form" class="form submitForm" action="{{ $action }}">
@csrf
<table class="tableBox">
	<colgroup>
		<col style="width:14em;">
		<col style="width:auto;">
	</colgroup>
	<tr>
		<th><label for="stamp_shikkou_alert"><small>各店舗ごとのスタンプ還元設定</small></label></th>
		<td style="text-align:left;">

		<table>
			<tr class="textS">
			<th align="center" valign="middle">&nbsp;</th>
			<th align="center" valign="middle">現金での還元</th>
			<th align="center" valign="middle">カードでの還元</th>
			</tr>
		@foreach ($rateShops as $i => $shop)
			<tr>
			<td align="center" valign="middle">{{ $shop->kaishamei }}<input type="hidden" name="stamp_settings[{{ $i }}][]" value="{{ $shop->cd }}" /></td>
			<td align="center" valign="middle"><input type="text" name="stamp_settings[{{ $i }}][]" class="input2S" value="{{ (string) $shop->stamp_percentage_genkin !== '' ? $shop->stamp_percentage_genkin : 1000 }}" maxlength="4" {!! $dec !!} />円につき1個</td>
			<td align="center" valign="middle"><input type="text" name="stamp_settings[{{ $i }}][]" class="input2S" value="{{ (string) $shop->stamp_percentage_card !== '' ? $shop->stamp_percentage_card : 1000 }}" maxlength="4" {!! $dec !!} />円につき1個</td>
			</tr>
		@endforeach
		</table>

		<input type="submit" name="enter" class="btnStyleS" style="color:red!important" value="変更" />
		</td>
	</tr>
</table>
<input type="hidden" name="anchor_id" value="change_stamp_settings_form" />
<input type="hidden" name="act" value="change_stamp_settings" />
</form>

<!-- / div.contents END --></div>
@endif
@endif
@endsection
