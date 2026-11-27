{{-- 機能設定: system/category0/config/features_config.php (cada bloque es un formulario con su act) --}}
@extends('layouts.legacy')

@section('title', '機能設定 -総合業務管理システム-')

@section('menu', 'base')

@section('css')
<link rel="stylesheet" type="text/css" href="/system/css/popInline.css">
@endsection

@section('js')
<script type="text/javascript" src="/system/js/popInline.js"></script>
<script type="text/javascript">

// #################### 数字のみを入力するファンクション #######################

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

// ############################################################################

function checkSend() {
	var resp = confirm("不達配信数をリセットしてもよろしいですか？\n※同時にメルマガ区分が「不達配信」の場合は「配信あり」に変更されます。");
	return resp;
}

$(document).ready(function() {
	$(".ac1").accordion('option', 'active', 0);

	// ポップアップ
	popIni();
});

</script>
@endsection

@php
    $checked = fn (bool $on) => $on ? ' checked="checked"' : '';
    $numberAttrs = 'onKeyPress="return numbersonly(this, event)" onkeyup="replace_value(this)" onmouseover="replace_value(this)" onchange="replace_value(this)"';
    $sounds = \App\Services\Base\FeatureSettingsService::SOUNDS;
@endphp

@section('content')
<div class="contents MB50">
<p class="h2Title">■機能設定</p>

<fieldset class="form">
<legend>顧客管理</legend>

<form method="post" class="form" id="name_input_mode_flg_form" action="">

<table class="tableBox">
	<colgroup>
		<col style="width:14em;">
		<col style="width:auto;">
	</colgroup>
	<tr>
		<th><label for="name_input_mode_flg1"><small>名前の入力形式</small></label></th>
		<td style="text-align:left;">

		<input type="radio" name="name_input_mode_flg" id="name_input_mode_flg1" value=""{!! $checked($nameInputModeFlg != '1') !!} />
		<label for="name_input_mode_flg1">名前で入力</label><br />
		<input type="radio" name="name_input_mode_flg" id="name_input_mode_flg2" value="1"{!! $checked($nameInputModeFlg == '1') !!} />
		<label for="name_input_mode_flg2">姓＋名で入力</label><br />

		<input type="submit" name="enter" class="btnStyleS" style="color:red!important" value="変更" />

		</td>
	</tr>

</table>

<input type="hidden" name="act" value="change_name_input_mode_flg" />

</form>

<form method="post" class="form" id="reserve_button_position_form" action="">

<table class="tableBox">
	<colgroup>
		<col style="width:14em;">
		<col style="width:auto;">
	</colgroup>
	<tr>
		<th><label for="reserve_button_position1"><small>予約ボタン表示位置</small></label></th>
		<td style="text-align:left;">

		<input type="radio" name="reserve_button_position" id="reserve_button_position1" value=""{!! $checked($reserveButtonPosition != '1') !!} />
		<label for="reserve_button_position1">下部</label><br />
		<input type="radio" name="reserve_button_position" id="reserve_button_position2" value="1"{!! $checked($reserveButtonPosition == '1') !!} />
		<label for="reserve_button_position2">上部</label><br />

		<input type="submit" name="enter" class="btnStyleS" style="color:red!important" value="変更" />

		</td>
	</tr>

</table>

<input type="hidden" name="act" value="change_reserve_button_position" />

</form>

</fieldset>


<fieldset class="form">
<legend>端末音声管理</legend>

<form method="post"  class="form" action="">

<table class="tableBox">
	<colgroup>
		<col style="width:14em;">
		<col style="width:auto;">
	</colgroup>

	<tr>
		<th><label><small>端末音声</small></label></th>
		<td style="text-align:left;">
			@foreach (['新規登録', '来店'] as $n => $label)
			{{ $label }}：&nbsp;
			<select name="sound_select[{{ $n }}]" id="sound_select{{ $n + 1 }}">
				@foreach ($sounds as $i => $sound)
				@continue($i === 6)
			<option value="{{ $i }}"@if ((string) $i === (string) ($soundSelect[$n] ?? '')) selected @endif>{{ $sound }}</option>
				@endforeach
			</select>
			<br/>
			@endforeach
			<input type="hidden" name="sound_select[2]" value="4" />
			<input type="submit" name="enter" class="btn" style="color:red" value="変更" />
		</td>
	</tr>

</table>

<input type="hidden" name="act" value="change_terminal_sound" />

</form>

</fieldset>


<fieldset class="form">
<legend>メルマガ管理</legend>

<form method="post"  class="form" action="">

<table class="tableBox">
	<colgroup>
		<col style="width:14em;">
		<col style="width:auto;">
	</colgroup>

	<tr>
		<th><label><small>メールフッター</small></label></th>
		<td style="text-align:left;">
			@foreach (\App\Services\Base\FeatureSettingsService::MAIL_FOOTERS as $i => $footer)
			<input type="checkbox" name="mail_footer[{{ $i }}]" value="1"@if (($mailFooter[$i] ?? '') === '') checked @endif />{{ $footer }}<br/>
			@endforeach
			<input type="submit" name="enter" class="btn" style="color:red" value="変更" />
		</td>
	</tr>

</table>

<input type="hidden" name="act" value="change_mail_footer" />

</form>

@if ($showShuki)
<form method="post"  class="form" action="">

<table class="tableBox">
	<colgroup>
		<col style="width:14em;">
		<col style="width:auto;">
	</colgroup>

	<tr>
		<th><label><small>最終来店日から来店周期日数後のフォローメール</small></label></th>
		<td style="text-align:left;">
			平均周期日数：&nbsp;
			<input type="text" name="shuki_limit1" id="shuki_limit1" class="input2S" value="{{ $shuki1 }}" maxlength="3" style="ime-mode:disabled;" {!! $numberAttrs !!} />&nbsp;～&nbsp;
			<input type="text" name="shuki_limit2" id="shuki_limit2" class="input2S" value="{{ $shuki2 }}" maxlength="3" style="ime-mode:disabled;" {!! $numberAttrs !!} />&nbsp;日<br/>
			<input type="submit" name="enter" class="btn" style="color:red" value="変更" />
		</td>
	</tr>

</table>

<input type="hidden" name="act" value="change_shuki_limit" />

</form>
@endif

<form method="post"  class="form" action="">

<table class="tableBox">
	<colgroup>
		<col style="width:14em;">
		<col style="width:auto;">
	</colgroup>

	<tr>
		<th><label><small>メール配信フィルター</small></label></th>
		<td style="text-align:left;">
			<input type="checkbox" name="mail_send_filter" id="mail_send_filter" value="1"@if (($admin['mail_send_filter'] ?? '') == '1') checked @endif  />&nbsp;フィルターを無効にする<br/>
			<input type="submit" name="enter" class="btn" style="color:red" value="変更" />
		</td>
	</tr>

</table>

<input type="hidden" name="act" value="change_mail_send_filter" />

</form>

<form method="post"  class="form" action="" onsubmit="return(checkSend());">

<table class="tableBox">
	<colgroup>
		<col style="width:14em;">
		<col style="width:auto;">
	</colgroup>

	<tr>
		<th><label><small>不達配信数のリセット</small></label></th>
		<td style="text-align:left;">
			<input type="submit" name="enter" class="btn" style="color:red" value="不達配信数をリセットする" />
			<br />※同時に<span class="bold">メルマガ区分</span>が「<span class="attention">不達配信</span>」の場合は「<span class="attention">配信あり</span>」に変更されます。
		</td>
	</tr>

</table>

<input type="hidden" name="act" value="reset_error_send_cnt" />

</form>

@if ($isDevelop)
<form method="post"  class="form" action="">

<table class="tableBox">
	<colgroup>
		<col style="width:14em;">
		<col style="width:auto;">
	</colgroup>

	<tr>
		<th><label><small>メルマガ配信時のログ</small></label></th>
		<td style="text-align:left;">
			<input type="radio" name="sendedLogFlg" id="sendedLogFlg1" value=""{!! $checked($sendedLogFlg != '1') !!} />
			<label for="sendedLogFlg1">無効</label>&emsp;
			<input type="radio" name="sendedLogFlg" id="sendedLogFlg2" value="1"{!! $checked($sendedLogFlg == '1') !!} />
			<label for="sendedLogFlg2">有効</label><br />

			最大件数：<input type="text" name="maxSendedLog" id="maxSendedLog" class="input2S" value="{{ $maxSendedLog }}" maxlength="5" style="ime-mode:disabled;" {!! $numberAttrs !!} />&nbsp;件&emsp;<span class="attention">※最大件数は「1500」件まで。</span><br/>

			<input type="submit" name="enter" class="btnStyleS" style="color:red!important" value="変更" />

		</td>
	</tr>

</table>

<input type="hidden" name="act" value="change_sended_log" />

</form>
@endif
</fieldset>


<fieldset class="form">
<legend>店舗の表示形式を店舗グループに従うかどうか</legend>

<form method="post" class="form" id="shop_category_flg_form" action="">

<table class="tableBox">
	<colgroup>
		<col style="width:14em;">
		<col style="width:auto;">
	</colgroup>
	<tr>
		<th><label for="shop_category_flg1"><small>グループ設定</small></label></th>
		<td style="text-align:left;">

		<input type="radio" name="shop_category_flg" id="shop_category_flg1" value="1"{!! $checked(($admin['shop_category_flg'] ?? null) == '1') !!} />
		<label for="shop_category_flg1">店舗グループに従う</label><br />
		<input type="radio" name="shop_category_flg" id="shop_category_flg2" value="0"{!! $checked(($admin['shop_category_flg'] ?? null) != '1') !!} />
		<label for="shop_category_flg2">店舗グループに従わない</label><br />
		<span class="attention">携帯での会員登録時、情報変更時、携帯での店舗情報閲覧時、WEB予約時の情報が同じカテゴリに属する店舗のものしか見れないようになります</span><br/>

		<input type="submit" name="enter" class="btnStyleS" style="color:red!important" value="変更" />

		</td>
	</tr>

</table>

<input type="hidden" name="act" value="change_shop_category_flg" />

</form>

</fieldset>


	<fieldset class="form">
		<legend>プライバシーポリシー</legend>

		<form method="post" class="form" id="privacy_policy_form" action="">
			<table class="tableBox">
				<colgroup>
					<col style="width:14em;">
					<col style="width:auto;">
				</colgroup>
				<tr>
					<th>
						<label for="">プライバシーポリシー文面</label>
					</th>
					<td style="text-align:left;">
						<textarea name="privacy_policy_text">{{ $privacyPolicyText }}</textarea>

						<div class="attention">
							設定値が空の場合は、デフォルトの内容が採用されます。
						</div>

						<input type="submit" name="enter" class="btnStyleS" style="color:red!important" value="変更" />
					</td>
				</tr>
			</table>
			<input type="hidden" name="act" value="privacy_policy_flg" />
		</form>
	</fieldset>


	@foreach (array_filter([
		['予約情報更新時のメール配信', 'mail_send_status', $mailSendStatus],
		$showPushTitle ? ['マイコレ プッシュ配信時にタイトルへの店舗名表示', 'disabled_push_title_flg', $disabledPushTitleFlg] : null,
	]) as [$legend, $name, $value])
	<fieldset class="form">
		<legend>{{ $legend }}</legend>

		<form method="post" class="form" id="{{ $name }}" action="">
			<table class="tableBox">
				<colgroup>
					<col style="width:14em;">
					<col style="width:auto;">
				</colgroup>
				<tr>
					<th>
						<label for="">設定</label>
					</th>
					<td style="text-align:left;">
						<input type="radio" name="{{ $name }}" value="2"{!! $checked($value != 1) !!}> 有効
						<input type="radio" name="{{ $name }}" value="1"{!! $checked($value == 1) !!}> 無効
						<input type="submit" name="enter" class="btnStyleS" style="color:red!important" value="変更" />
					</td>
				</tr>
			</table>
			<input type="hidden" name="act" value="{{ $name }}" />
		</form>
	</fieldset>
	@endforeach

	@if ($mobileshopShops !== null)
		<fieldset class="form">
			<legend>モバイルショップ向け設定</legend>
			<form method="post" class="form" id="mobileshop_auth_passcode" action="">
				<table class="tableBox">
					<colgroup>
						<col style="width:14em;">
						<col style="width:auto;">
					</colgroup>
					<tr>
						<th>
							<label for="">モバイルショップ店舗確認パスコード</label>
						</th>
						<td style="text-align:left;">
						<table style="border: hidden;"> <colgroup> <col style="width:250px;"> <col style="width:250px;"> </colgroup>
						<tr> <td></td> <td style="text-align:center; border: hidden;">
						<input type="text" id="allPasscode" name="" style="width:100px;"/>
						<input type="button" name="" onclick="changePasscode();" value="一括設定する"/></td> </tr></table>
						<script>
							function changePasscode(){
								var passcode = document.querySelectorAll("#passcode");
								for(var i=0; i<passcode.length; i++){
									passcode[i].value = document.getElementById("allPasscode").value
								}
							}
						</script>
						@if ($mobileshopShops)
						<table> <colgroup> <col style="width:250px;"> <col style="width:250px;"> </colgroup>
						<tr> <th><center>店舗名</th> <th><center>パスコード</th> </tr>
						@foreach ($mobileshopShops as $shop)
						<tr> <th>{{ $shop->kaishamei }}</th> <td><center>
						{{-- la パスコード se guarda con htmlspecialchars (como el legacy): se imprime tal cual --}}
						<input type="text" name="mobileshop_auth_passcode[]" id="passcode" style="width:200px;" value="{!! $shop->mobileshop_auth_passcode !!}" />
						</td> </tr>
						<input type="hidden" name="shop_cd[]" value="{{ $shop->cd }}"/>
						@endforeach
						</table>
						@endif
						<table style="border: hidden;"><tr><td width="500px" style="text-align:center; border: hidden;">
						<input type="submit" name="enter" class="btnStyleS" style="color:red!important" value="変更" /></td></tr></table>
						<input type="hidden" name="act" value="mobileshop_auth_passcode" />
						</td>
					</tr>
				</table>
			</form>
		</fieldset>
	@endif
<!-- / .contents --></div>

@if ($showTerminal)
<div class="contents">
<p class="h2Title">■業務用端末設定</p>

<fieldset class="form">
<legend>共通設定</legend>

<form method="post"  class="form" action="">

<table class="tableBox">
	<colgroup>
		<col style="width:14em;">
		<col style="width:auto;">
	</colgroup>

	<tr>
		<th><label for="terminal_logout_time"><small>自動ログアウトするまでの時間</small></label></th>
		<td style="text-align:left;">

		<input type="text" name="terminal_logout_time" id="terminal_logout_time" class="input2S" value="{{ $terminalLogoutTime }}" maxlength="4" style="ime-mode:disabled;" {!! $numberAttrs !!} />分<br />
		<input type="submit" name="enter" class="btn" style="color:red" value="変更" />

		</td>
	</tr>

</table>

<input type="hidden" name="act" value="change_terminal_logout_time" />

</form>

<form method="post" class="form" id="id_raiten_type_form" action="">

<table class="tableBox">
	<colgroup>
		<col style="width:14em;">
		<col style="width:auto;">
	</colgroup>

	<tr>
		<th><label for="id_raiten_type1"><small>ID入力来店処理の入力タイプ</small></label></th>
		<td style="text-align:left;">

		<input type="radio" name="id_raiten_type" id="id_raiten_type1" value=""{!! $checked(($admin['id_raiten_type'] ?? '') != '1') !!} />
		<label for="id_raiten_type1">会員番号</label><br />
		<input type="radio" name="id_raiten_type" id="id_raiten_type2" value="1"{!! $checked(($admin['id_raiten_type'] ?? '') == '1') !!} />
		<label for="id_raiten_type2">管理番号</label><br />

		<input type="submit" name="enter" class="btnStyleS" style="color:red!important" value="変更" />

		</td>
	</tr>

</table>

<input type="hidden" name="act" value="change_id_raiten_type" />

</form>

</fieldset>

<!-- / .contents --></div>
@endif

<br><br><br>

<div class="contents">
<p class="h2Title">■SMTP設定</p>

<form method="post" class="form" id="smtp_form" action="">

<table class="tableBox">
	<colgroup>
		<col style="width:14em;">
		<col style="width:auto;">
	</colgroup>
	@foreach (['smtp_domain' => 'ドメイン', 'smtp_port' => 'ポート', 'smtp_login_id' => 'ログインID', 'smtp_login_pass' => 'ログインパスワード'] as $key => $label)
	<tr>
		<th>
			<label for="{{ $key }}"><small>{{ $label }}</small></label>
		</th>
		<td style="text-align:left;">
			<input type="text" name="{{ $key }}" id="{{ $key }}" value="{{ $admin[$key] ?? '' }}" />
		</td>
	</tr>
	@endforeach
</table>

<input type="submit" name="enter" class="btnStyleS" style="color:red!important" value="変更" />

<input type="hidden" name="act" value="change_id_smtp" />

</form>

<!-- / .contents --></div>
@endsection
