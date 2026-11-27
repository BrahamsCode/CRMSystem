{{--
	ポイント仕様管理 — legacy: system/category3/point/point_config.php (cada formulario envía su `act` a esta misma pantalla)
	Variables: $admin (admin_master), $alerts (point_shikkou_alert flg 1, con alert_time_h/m), $shops (con las configuraciones
	  de マイコレ ya deserializadas), $groups (tb_customer_group), $paidMember, $stampEnabled, $pointEnabled, $paidMemberEnabled
--}}
@extends('layouts.legacy')

@section('menu', 'promocion')

@section('title', 'ポイント仕様管理')

@php
	$a = fn (string $k) => (string) ($admin[$k] ?? '');
	$action = route('admin.promocion.points.config');
	$hours = fn ($selected) => collect(range(0, 23))->map(fn ($i) => '<option value="' . sprintf('%02d', $i) . '"' . ((string) $selected === sprintf('%02d', $i) ? ' selected="selected"' : '') . '>' . sprintf('%02d', $i) . '</option>')->implode("\n");
	$halfHours = fn ($selected) => '<option value="00"' . ((string) $selected === '00' ? ' selected="selected"' : '') . '>00</option><option value="30"' . ((string) $selected === '30' ? ' selected="selected"' : '') . '>30</option>';
	$num = 'style="ime-mode:disabled;" onKeyPress="return numbersonly(this, event)" onkeyup="replace_value(this)" onmouseover="replace_value(this)" onchange="replace_value(this)"';
	$dec = 'style="ime-mode:disabled;" onKeyPress="return numbersonly(this, event, 2)" onkeyup="replace_value(this)" onmouseover="replace_value(this)" onchange="replace_value(this)"';
	[$shikkouH, $shikkouM] = explode(':', $a('point_shikkou_time') !== '' ? $a('point_shikkou_time') : '12:00') + [1 => ''];
	[$alertH, $alertM] = explode(':', $a('point_shikkou_alert_time') !== '' ? $a('point_shikkou_alert_time') : '12:00') + [1 => ''];
	// Los legacy guardan title/body con htmlentities(): se imprimen tal cual
	$raw = fn ($v) => (string) ($v ?? '');
@endphp

@section('css')
<link href="{{ asset('legacy/system/css/jquery.alerts.css') }}" rel="stylesheet" type="text/css" media="all" />
<link rel="stylesheet" type="text/css" href="{{ asset('legacy/system/css/popInline.css') }}">
@endsection

@section('js')
<script type="text/javascript" src="{{ asset('legacy/system/js/jquery.alerts.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/js/popInline.js') }}"></script>
<script type="text/javascript">
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

	if ((key==null) || (key==0) || (key==8) ||
	    (key==9) || (key==13) || (key==27) )
	   return true;
	else if ((("0123456789").indexOf(keychar) > -1))
	   return true;
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

$(document).ready(function() {
	$(".ac13").accordion('option', 'active', 0);
	popIni();

	$("#pointAlertMail").click(function(){
		openPopWindow("{{ route('admin.promocion.points.template', ['type' => 'alert']) }}", '650', '');
	});

	$(".point_shikkou_alert_dialog").click(function(){
		openPopWindow("{{ route('admin.promocion.points.template', ['type' => 'shikkou_alert']) }}?cd=" + $(this).attr("data_cd"), '650', '');
	});

	$("#point_yukoukigen_form").submit(function(e){
		e.preventDefault();
		var mess="";
		if( $("input[name=point_yukoukigen_flg]:checked").val() == ''){
			$.ajax({
				type: "POST",
				url: "{{ route('admin.promocion.points.expiry-preview') }}",
				success: function(data){
					if(data == "ng") return false;
					if(data != ""){
						mess = "有効期限の設定を、「有効」に変更しようとしています。\n";
						mess += "設定変更に伴い、"+data+"\n";
						mess += "\n";
						mess += "これらのユーザーのポイント有効期限の設定を有効にしてよろしいですか？";
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
			mess += "ポイントの有効期限を有効にする際は、ユーザーの有効期限は再設定される事になります。\n";
			mess += "\n";
			mess += "設定してよろしいですか？";
			jConfirm( mess, "確認", function(r){
				if(r == true) e.target.submit();
			});
		}
	});
});
</script>
@endsection

@section('content')
@if ($stampEnabled || $pointEnabled)
<div class="contents">
<p class="h2Title">■ポイント仕様管理</p>
@if ($pointEnabled)
<fieldset class="form">
<legend>ポイント管理</legend>

<form method="post" class="form" id="point_yukoukigen_form" action="{{ $action }}">
@csrf
<table class="tableBox">
	<colgroup>
		<col style="width:14em;">
		<col style="width:auto;">
	</colgroup>
	<tr>
		<th><label for="point_yukoukigen_flg1"><small>ポイント有効期間の使用選択</small></label></th>
		<td style="text-align:left;">
		<input type="radio" name="point_yukoukigen_flg" id="point_yukoukigen_flg1" value="" @checked($a('point_yukoukigen_flg') !== '2') />
		<label for="point_yukoukigen_flg1">ポイント有効期間の設定を有効にする</label><br />
		<input type="radio" name="point_yukoukigen_flg" id="point_yukoukigen_flg2" value="2" @checked($a('point_yukoukigen_flg') === '2') />
		<label for="point_yukoukigen_flg2">ポイント有効期間の設定を無効にする（無期限）</label><br />
		<input type="submit" name="enter" class="btn" style="color:red!important" value="変更" />
		</td>
	</tr>
</table>
<input type="hidden" name="act" value="change_point_yukoukigen_flg" />
</form>

<form method="post" class="form" action="{{ $action }}">
@csrf
<table class="tableBox">
	<colgroup>
		<col style="width:14em;">
		<col style="width:auto;">
	</colgroup>
	<tr>
		<th><label for="point_shikkou_sousa1"><small>ポイント有効期間延長動作</small></label></th>
		<td style="text-align:left;">
		お客様のポイント有効期間を延長する動作として<br />
		<input type="radio" name="point_shikkou_sousa" id="point_shikkou_sousa1" value="1" @checked(in_array($a('point_shikkou_sousa'), ['1', ''], true)) />
		<label for="point_shikkou_sousa1">ポイントの増減どちらかがあった場合に延長</label><br />
		<input type="radio" name="point_shikkou_sousa" id="point_shikkou_sousa2" value="2" @checked($a('point_shikkou_sousa') === '2') />
		<label for="point_shikkou_sousa2">ポイントの使用があった場合のみ延長</label><br />
		<input type="submit" name="enter" class="btn" style="color:red" value="変更" />
		</td>
	</tr>
</table>
<input type="hidden" name="act" value="change_point_shikkou_sousa" />
</form>

<form method="post" class="form" action="{{ $action }}">
@csrf
<table class="tableBox">
	<colgroup>
		<col style="width:14em;">
		<col style="width:auto;">
	</colgroup>
	<tr>
		<th><label for="point_shikkou_kikan"><small>有効期間</small></label></th>
		<td style="text-align:left;">
		<input type="text" name="point_shikkou_kikan" id="point_shikkou_kikan" class="input2S" value="{{ $a('point_shikkou_kikan') === '' ? 365 : $a('point_shikkou_kikan') }}" maxlength="3" {!! $num !!} />日<br />
		<input type="submit" name="enter" class="btn" style="color:red" value="変更" />
		<br />※有効期限は0～999日で設定してください。
		</td>
	</tr>
</table>
<input type="hidden" name="act" value="change_point_shikkou_kikan" />
</form>

<form method="post" class="form" action="{{ $action }}">
@csrf
<table class="tableBox">
	<colgroup>
		<col style="width:14em;">
		<col style="width:auto;">
	</colgroup>
	<tr>
		<th><label for="point_shikkou_time_h"><small>失効処理を行う時間</small></label></th>
		<td style="text-align:left;">
		<select name="point_shikkou_time_h" id="point_shikkou_time_h">
		{!! $hours($shikkouH) !!}
		</select>&nbsp;時&nbsp;&nbsp;
		<select name="point_shikkou_time_m" id="point_shikkou_time_m">
		{!! $halfHours($shikkouM) !!}
		</select>&nbsp;分<br />
		※毎日同時刻に失効処理を実行します。<br />
		<input type="submit" name="enter" class="btn" style="color:red" value="変更" />
		</td>
	</tr>
</table>
<input type="hidden" name="act" value="change_point_shikkou_time" />
</form>

<form method="post" class="form" action="{{ $action }}">
@csrf
<table class="tableBox">
	<colgroup>
		<col style="width:14em;">
		<col style="width:auto;">
	</colgroup>
	<tr>
		<th><label for="point_shikkou_alert"><small>ポイント有効期限切れ前通知メール</small></label></th>
		<td style="text-align:left;">
		<div style="display: inline-block; width: 20px; text-align: right;">1. </div>
		<input type="text" name="point_shikkou_alert" id="point_shikkou_alert" class="input2S" value="{{ $a('point_shikkou_alert') === '' ? 30 : $a('point_shikkou_alert') }}" maxlength="3" {!! $num !!} />日前の&nbsp;&nbsp;
		<select name="point_shikkou_alert_time_h" id="point_shikkou_alert_time_h">
		{!! $hours($alertH) !!}
		</select>&nbsp;時&nbsp;&nbsp;
		<select name="point_shikkou_alert_time_m" id="point_shikkou_alert_time_m">
		{!! $halfHours($alertM) !!}
		</select>&nbsp;分に送信
		<input type="button" id="pointAlertMail" value="通知内容" />
		@foreach (range(2, 10) as $key => $number)
			@php $row = $alerts[$key] ?? null; @endphp
			<div style="margin-top: 5px;">
				<div style="display: inline-block; width: 20px; text-align: right;">{{ $number }}. </div>
				<input type="hidden" name="point_shikkou_alert_cd[{{ $key }}]" value="{{ $row?->cd }}">
				<input type="text" name="point_shikkou_alert_days[{{ $key }}]" class="input2S" value="{{ $row?->alert_day }}" maxlength="3" {!! $num !!} />日前の&nbsp;&nbsp;
				<select name="point_shikkou_alert_times_h[{{ $key }}]" class="point_shikkou_alert_times_h">
					{!! $hours($row?->alert_time_h) !!}
				</select>&nbsp;時&nbsp;&nbsp;
				<select name="point_shikkou_alert_times_m[{{ $key }}]" class="point_shikkou_alert_times_m">
					{!! $halfHours($row?->alert_time_m) !!}
				</select>&nbsp;分に送信
				<input type="button" class="point_shikkou_alert_dialog" data_cd="{{ $row?->cd }}" value="通知内容" />
				<br>
			</div>
		@endforeach
		<p style="padding: 10px; font-size: 13px;">※設定をご利用されない場合には『日数』の部分を『空白』ないし『0』でご登録ください。</p>
		<input type="submit" name="enter" class="btn" style="color:red" value="変更" />
		</td>
	</tr>
</table>
<input type="hidden" name="act" value="change_point_shikkou_alert" />
</form>

@foreach ([['act' => 'change_point_settings', 'label' => '各店舗ごとのポイントルール設定', 'rows' => $shops, 'name' => 'kaishamei', 'note' => false],
	['act' => 'change_group_point_settings', 'label' => '顧客グループごとのポイントルール設定', 'rows' => $groups, 'name' => 'name', 'note' => true]] as $block)
<form method="post" class="form" action="{{ $action }}">
@csrf
<table class="tableBox">
	<colgroup>
		<col style="width:14em;">
		<col style="width:auto;">
	</colgroup>
	<tr>
		<th><label for="point_shikkou_alert"><small>{{ $block['label'] }}</small></label></th>
		<td style="text-align:left;">
		<table>
			<tr class="textS">
			<th align="center" valign="middle">&nbsp;</th>
			<th align="center" valign="middle">ポイント使用上限</th>
			<th align="center" valign="middle">現金還元率(％)</th>
			<th align="center" valign="middle">カード還元率(％)</th>
			<th align="center" valign="middle">ポイント計算に使用ポイントを含む </th>
			</tr>
			@foreach ($block['rows'] as $i => $row)
			<tr>
			<td align="center" valign="middle">{{ $row->{$block['name']} }}<input type="hidden" name="point_settings[{{ $i }}][]" value="{{ $row->cd }}" /></td>
			<td align="center" valign="middle"><input type="text" name="point_settings[{{ $i }}][]" class="input2S" value="{{ (string) $row->shiyou_point_jyougen !== '' ? $row->shiyou_point_jyougen : 1000 }}" maxlength="5" {!! $num !!} />P</td>
			<td align="center" valign="middle"><input type="text" name="point_settings[{{ $i }}][]" class="input2S" value="{{ (string) $row->point_percentage_genkin !== '' ? $row->point_percentage_genkin : 1 }}" maxlength="4" {!! $dec !!} />％</td>
			<td align="center" valign="middle"><input type="text" name="point_settings[{{ $i }}][]" class="input2S" value="{{ (string) $row->point_percentage_card !== '' ? $row->point_percentage_card : 1 }}" maxlength="4" {!! $dec !!} />％</td>
			<td align="center" valign="middle"><input type="checkbox" name="point_settings[{{ $i }}][]" value="1" @checked((string) $row->keisan_flg === '2') /></td>
			</tr>
			@endforeach
		</table>
		<input type="submit" name="enter" class="btn" style="color:red" value="変更" />
		@if ($block['note'])
		<div>
			<p>※タブレットCRMポイントアプリによる操作では、当設定は適用されません。</p>
		</div>
		@endif
		</td>
	</tr>
</table>
<input type="hidden" name="act" value="{{ $block['act'] }}" />
</form>
@endforeach

@if ($a('application_use_flg') === '1')
<p class="h2Title" style="margin-top:20px">■マイコレ設定</p>
@if ($a('beacon_use_flg') === '1')
<form method="post" class="form" id="coming_use_beacon_form" action="{{ $action }}">
@csrf
<table class="tableBox MB10">
	<colgroup>
		<col style="width:14em;">
		<col style="width:auto;">
	</colgroup>
	<tr>
		<th><label for="coming_use_beacon1"><small>マイコレにてビーコン利用の来店処理</small></label></th>
		<td style="text-align:left;">
			<select name="coming_use_beacon" id="coming_use_beacon1">
				@foreach (['利用しない', '来店処理を利用する', '会員証提示を利用する', '両方利用する'] as $key => $label)
				<option value="{{ $key }}" @selected($a('coming_use_beacon') === (string) $key)>{{ $label }}</option>
				@endforeach
			</select>
			<input type="submit" name="enter" class="btnStyleS" style="color:red!important" value="変更" />
		</td>
	</tr>
</table>
<input type="hidden" name="act" value="change_coming_use_beacon" />
</form>
@endif

<form method="post" class="form" action="{{ $action }}">
@csrf
<table class="tableBox">
	<tr>
		<td style="text-align:left; vertical-align: top;">
		<legend style="margin-top:10px;">マイコレでの毎日ログインのポイント設定</legend>
		<table style="width:100%">
			<tr class="textS">
			<th align="center" valign="middle">&nbsp;</th>
			<th align="center" valign="middle">利用</th>
			<th align="center" valign="middle">表示</th>
			<th align="center" valign="middle">獲得ポイント数</th>
			</tr>
			@foreach ($shops as $i => $shop)
			@php $s = $shop->login_point_settings; @endphp
			<tr>
			<td align="center" valign="middle">{{ $shop->kaishamei }}<input type="hidden" name="login_point_settings[{{ $i }}][shopCd]" value="{{ $shop->cd }}" /></td>
			<td valign="middle">
				<input type="radio" id="login_point_use_{{ $i }}_0" name="login_point_settings[{{ $i }}][useFlg]" value="0" @checked(($s['useFlg'] ?? null) != 1) />
				<label for="login_point_use_{{ $i }}_0">利用しない</label><br />
				<input type="radio" id="login_point_use_{{ $i }}_1" name="login_point_settings[{{ $i }}][useFlg]" value="1" @checked(($s['useFlg'] ?? null) == 1) />
				<label for="login_point_use_{{ $i }}_1">利用する</label><br />
			</td>
			<td valign="middle">
				<p style="font-size: 11px;">タイトル</p>
				<input type="text" name="login_point_settings[{{ $i }}][title]" value="{!! $raw($s['title'] ?? '') !!}" style="width:150px;margin-bottom:10px;" /><br />
				<p style="font-size: 11px;">内容</p>
				<textarea name="login_point_settings[{{ $i }}][body]" style="width:150px;height:60px;resize:none;">{!! $raw($s['body'] ?? '') !!}</textarea>
			</td>
			<td align="center" valign="middle"><input type="text" name="login_point_settings[{{ $i }}][earnPoints]" class="input2S" value="{{ $s['earnPoints'] ?? '' }}" maxlength="5" {!! $num !!} />P</td>
			</tr>
			@endforeach
		</table>

		@if ($a('beacon_use_flg') === '1')
		<legend style="margin-top:10px;">店舗毎の基礎来店ポイント設定</legend>
		<table style="width:100%">
			<tr class="textS">
			<th align="center" valign="middle">&nbsp;</th>
			<th align="center" valign="middle">利用</th>
			<th align="center" valign="middle">表示</th>
			<th align="center" valign="middle">獲得ポイント数</th>
			</tr>
			@foreach ($shops as $i => $shop)
			@php $s = $shop->login_default_point_settings; @endphp
			<tr>
			<td align="center" valign="middle">{{ $shop->kaishamei }}<input type="hidden" name="login_default_point_settings[{{ $i }}][shopCd]" value="{{ $shop->cd }}" /></td>
			<td valign="middle">
				<input type="radio" id="login_default_point_use_{{ $i }}_0" name="login_default_point_settings[{{ $i }}][useFlg]" value="0" @checked(($s['useFlg'] ?? null) != 1) />
				<label for="login_default_point_use_{{ $i }}_0">利用しない</label><br />
				<input type="radio" id="login_default_point_use_{{ $i }}_1" name="login_default_point_settings[{{ $i }}][useFlg]" value="1" @checked(($s['useFlg'] ?? null) == 1) />
				<label for="login_default_point_use_{{ $i }}_1">利用する</label><br />
			</td>
			<td valign="middle">
				<p style="font-size: 11px;">内容</p>
				<textarea name="login_default_point_settings[{{ $i }}][body]" style="width:150px;height:60px;resize:none;">{!! $raw($s['body'] ?? '') !!}</textarea>
			</td>
			<td align="center" valign="middle">
				<input type="text" name="login_default_point_settings[{{ $i }}][earnPoints]" class="input2S" value="{{ $s['earnPoints'] ?? '' }}" maxlength="5" {!! $num !!} />P</td>
			</tr>
			@endforeach
		</table>
		@endif

		<!-- 友達紹介設定 -->
		<legend style="margin-top:10px;">マイコレでの友達紹介設定</legend>
		<table style="width:100%">
			<tr class="textS">
			<th align="center" valign="middle">&nbsp;</th>
			<th align="center" valign="middle">紹介者の付与ポイント</th>
			<th align="center" valign="middle">紹介者の付与ポイント上限</th>
			<th align="center" valign="middle">被紹介者の付与ポイント</th>
			<th align="center" valign="middle">紹介時のコメント</th>
			</tr>
			@foreach ($shops as $i => $shop)
			@php $s = $shop->introduce_settings; @endphp
			<tr>
			<td align="center" valign="middle">
				{{ $shop->kaishamei }}
				<input type="hidden" name="introduce_settings[{{ $i }}][shopCd]" value="{{ $shop->cd }}" />
			</td>
			<td align="center" valign="middle">
				<input type="text" name="introduce_settings[{{ $i }}][introducePoints]" class="input2S" value="{{ $s['introducePoints'] ?? '' }}" maxlength="5" {!! $num !!} />P
			</td>
			<td align="center" valign="middle">
				月に
				<input type="text" name="introduce_settings[{{ $i }}][introduceMaxMonthPoints]" class="input2S" value="{{ $s['introduceMaxMonthPoints'] ?? '' }}" maxlength="5" {!! $num !!} />P まで
			</td>
			<td valign="middle">
				<input type="text" name="introduce_settings[{{ $i }}][registrationPoints]" class="input2S" value="{{ $s['registrationPoints'] ?? '' }}" maxlength="5" {!! $num !!} />P
			</td>
			<td valign="middle">
				<textarea name="introduce_settings[{{ $i }}][body]" style="width:150px;height:60px;resize:none;">{!! $raw($s['body'] ?? '') !!}</textarea>
			</td>
			</tr>
			@endforeach
		</table>

		<legend style="margin-top:10px;">会員登録時のポイント設定</legend>
		<table style="width:100%">
			<tr class="textS">
			<th align="center" valign="middle">&nbsp;</th>
			<th align="center" valign="middle">付与ポイント</th>
			<th align="center" valign="middle">付与タイミング</th>
			<th align="center" valign="middle">登録時のコメント</th>
			</tr>
			@foreach ($shops as $i => $shop)
			@php $s = $shop->customer_registration_settings; @endphp
			<tr>
			<td align="center" valign="middle">
				{{ $shop->kaishamei }}
				<input type="hidden" name="customer_registration_settings[{{ $i }}][shopCd]" value="{{ $shop->cd }}" />
			</td>
			<td align="center" valign="middle">
				<input type="text" name="customer_registration_settings[{{ $i }}][registPoints]" class="input2S" value="{{ $s['registPoints'] ?? '' }}" maxlength="5" {!! $num !!} />P
			</td>
			<td valign="middle">
				<div>
					<p>
						<span>登録から</span>
						<input name="customer_registration_settings[{{ $i }}][days]" class="input3S" value="{{ $s['days'] ?? '' }}" style="ime-mode: disabled;" type="text" />
						<span>日後</span>
					</p>
					<p style="padding-left:1em;">
						<span>時間：</span>
						<select name="customer_registration_settings[{{ $i }}][hours]" class="selectBox">
							@for ($x = 0; $x <= 23; $x++)
							<option value="{{ sprintf('%02d', $x) }}" @selected(($s['hours'] ?? '') === sprintf('%02d', $x))>{{ sprintf('%02d', $x) }}時</option>
							@endfor
						</select>
						<select name="customer_registration_settings[{{ $i }}][minutes]" class="selectBox">
							@for ($x = 0; $x <= 55; $x += 5)
							<option value="{{ sprintf('%02d', $x) }}" @selected(($s['minutes'] ?? '') === sprintf('%02d', $x))>{{ sprintf('%02d', $x) }}分</option>
							@endfor
						</select>
					</p>
					<p style="color:#ff0000;font-size:85%;">※0日後にすれば登録と同時に付与します</p>
				</div>
			</td>
			<td valign="middle">
				<textarea name="customer_registration_settings[{{ $i }}][body]" style="width:300px;height:60px;resize:none;">{!! $raw($s['body'] ?? '') !!}</textarea>
			</td>
			</tr>
			@endforeach
		</table>
		<input type="submit" name="enter" class="btn" style="color:red" value="変更" />
		</td>
	</tr>
</table>
<input type="hidden" name="act" value="change_login_point_settings" />
</form>
@endif

@if ($paidMemberEnabled)
<p class="h2Title" style="margin-top:20px">■有料会員</p>
<form method="post" class="form" action="{{ $action }}">
@csrf
<table class="tableBox">
	<tr>
		<td>
			<legend style="margin-top:10px;">ポイント付与設定</legend>
			<table style="width:100%">
				<tr class="textS">
					<th align="center" valign="middle">付与ポイント</th>
					<td valign="middle">
						<input type="text" name="paid_member_settings[points]" class="input2S" value="{{ $paidMember['points'] ?? '' }}" maxlength="5" {!! $num !!} />P
					</td>
				</tr>
				<tr class="textS">
					<th align="center" valign="middle">付与タイミング</th>
					<td valign="middle">
						<div>
							<p>
								<span>有料会員登録から</span>
								<input name="paid_member_settings[months]" class="input3S" value="{{ $paidMember['months'] ?? '' }}" style="ime-mode: disabled;" type="text" />
								<span>ヶ月後の</span>
								<select name="paid_member_settings[day]" class="selectBox">
									@for ($x = 1; $x < 28; $x++)
									<option value="{{ $x }}" @selected((int) ($paidMember['day'] ?? 0) === $x)>{{ $x }}日</option>
									@endfor
									<option value="32" @selected((int) ($paidMember['day'] ?? 0) === 32)>末日</option>
								</select>
							</p>
							<p style="color:#ff0000;font-size:85%;">※付与処理は10時に実行されます</p>
						</div>
					</td>
				</tr>
			</table>
			<input type="submit" name="enter" class="btn" style="color:red" value="変更" />
			<input type="hidden" name="act" value="change_paid_member_point_settings" />
		</td>
	</tr>
</table>
</form>
@endif
</fieldset>
@endif
<!-- / .contents --></div>
@endif
@endsection
