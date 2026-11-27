{{-- Legacy: system/category1/inc/kanren_record_new.php (来店履歴の追加・修正; customer/kanren_record_new.php = la misma con menú).
     Variables:
       $popup              bool: ventana emergente (sin menú), versión inc/ del legacy
       $customer           Customer (cd, id, name, stamp_count)
       $packType           string: admin_master.related_features[1] ('1' ポイント, '2' スタンプ, '' ninguno)
       $actualUnits        int: puntos (tb_customer_point.service_point) o sellos (tb_customer.stamp_count) actuales
       $isMaster           bool: el personal es master (elige la tienda)
       $shops              array cd => kaishamei de tb_shop (disp_flg = '1', ORDER BY cd)
       $sessionShopCd      int: tienda de la sesión (sin master)
       $selectedShopCd     int|string|null: tienda seleccionada (la de la visita al corregir o la enviada)
       $raiten             array [año, mes, día, hora, minuto] para setRaitenTimeSelect() ('' = vacío)
       $logArray           array: en corrección, el movimiento enlazado (tb_point_log / tb_stamp_log + staff_name);
                           al volver con errores, lo enviado (shiharai_houhou, kaikei, shouhi_point, sounyu_point, sounyu_point_add_remove_flg)
       $existLogData       bool: hay movimiento enlazado (corrección)
       $category           array|null: tb_search_cate de la configuración (category1, disp_flg <> 9, hyouji_flg 1, record_type 1)
       $fields             array: tb_search_form de la categoría (disp_flg <> 9, hyouji_flg 1 o 2, ORDER BY rank)
       $values             array: valores de los campos (form{cd}…; de tb_customer_sub al corregir, lo enviado con errores)
       $fieldErrors        array: errores del legacy (raiten => fecha, form{cd} => createform_errorcheck)
       $hidden             array: add_record, kaiin_cd, tab, act, related_features_cd, raiten_cd, page
       $verifyToken        string: add_record_verify (time(), evita el doble envío) --}}
@extends(($popup ?? false) ? 'layouts.legacy-popup' : 'layouts.legacy')

@section('menu', 'clientes')
@section('title', '顧客登録情報 - ' . app(\App\Services\Nucleo\LegacyUi::class)->siteName())

@php
    $isUpdate = ($hidden['related_features_cd'] ?? '') !== '';
    $raitenError = $fieldErrors['raiten'] ?? '';
    $log = $logArray ?? [];
    $logBairitsu = '';
    if ($packType === '1') {
        [$logType, $logUnit, $logRateUnit] = ['ポイント', 'Pt', '%'];
        $jidouUnits = $log['jidou_point'] ?? '';
        $sounyuUnits = $log['sounyu_point'] ?? '';
        $pastUnits = (int) ($log['shori_mae_point'] ?? 0);
    } elseif ($packType === '2') {
        [$logType, $logUnit, $logRateUnit] = ['スタンプ', '個', '円で1個'];
        $jidouUnits = $log['jidou_stamp'] ?? '';
        $tempDiv = ((float) ($log['percentage'] ?? 0) > 0) ? (int) ((int) ($log['kaikei'] ?? 0) / $log['percentage']) : 0;
        if ($tempDiv > 0) {
            $logBairitsu = '(' . (int) ((int) $jidouUnits / $tempDiv) . '倍率)';
        }
        $sounyuUnits = $log['sounyu_stamp'] ?? '';
        $pastUnits = (int) ($log['shori_mae_stamp'] ?? 0);
    }
    $sendJs = "'" . implode("', '", array_map(fn ($v) => preg_replace('/[^0-9]/', '', (string) $v), $raiten)) . "'";
    $wintype = ($popup ?? false) ? ['wintype' => 'no_headers'] : [];
@endphp

@section('css')
<style type="text/css"><!--

.genzaiPoint {
	color: #EE0000;
	font-weight: bold;
	font-size: 18px;
}

.kyouchouRed {
	color: #EE0000;
	font-weight: bold;
	font-size: 14px;
}

.kyouchouBlue {
	color: #336699;
	font-weight: bold;
	font-size: 14px;
}
@if (str_contains(request()->userAgent() ?? '', 'Mac OS') && str_contains(request()->userAgent() ?? '', 'Safari'))
{{-- IOS11対応 --}}
#popup_container {
	@if (($hidden['act'] ?? '') == 'new')
	top: 500px  !important;
	@elseif (($hidden['act'] ?? '') == 'up')
	top: 300px  !important;
	@endif
	position: absolute !important;
}
@endif
--></style>
@if (! $isUpdate)
<link href="{{ asset('legacy/system/css/colorbox.css') }}" rel="stylesheet" type="text/css" media="all" />
@endif
@endsection

@section('js')
@if (! $isUpdate)
<script type="text/javascript" src="{{ asset('legacy/system/js/jquery.colorbox.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/category1/inc/js/raiten_list.js') }}"></script>
@endif

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

function submitChk () {
	jConfirm( "この内容で登録してもよろしいですか？\n\n登録しない場合は['キャンセル']ボタンを押して下さい", "登録", function(ret){
		if (ret) {
			document.mainForm.submit();
		} else {
			return false;
		}
	});
}

@if ($packType === '1')
{{-- *********************************** ポイントの場合 *********************************** --}}
function getShopConfig(elem) {

	if (typeof(elem) != "undefined" && elem.is(".raitenTimeSelect")) return; // ポイントは日付と関係ありません。

	if ($("#shop_config_view").length == 0) return;

	var shopCd = $("#selected_shop_cd").val();

	$.getJSON("/system/category1/inc/ajax_shop_point_config.php",{
		shop_cd:shopCd
	},function(data) {
		shopConfig = data; // グローバル変数にデータを挿入
		var view = "";
		view += '<span style="color:blue;font-weight:bold;">'+shopConfig.shop_name+'</span>ポイントルール<br/>';
		view += 'ポイント使用上限：<span style="color:blue;font-weight:bold;">'+shopConfig.shiyou_point_jyougen+'Pt</span><br/>';
		view += '現金還元率：<span style="color:blue;font-weight:bold;">'+shopConfig.point_percentage_genkin+'%</span><br/>';
		view += 'カード還元率：<span style="color:blue;font-weight:bold;">'+shopConfig.point_percentage_card+'%</span><br/>';
		view += 'ポイント計算に使用ポイント：<span style="color:blue;font-weight:bold;">'+(shopConfig.keisan_flg == '2' ? '含む' : '含めない')+'</span><br/>';
		$("#shop_config_view").html(view);
		calculate();
	});

}

function calculate() {
	var genzaiPoint = parseInt($("#genzai_point").val());
	var kaikei = $("#kaikei").val()=="" ? 0 : parseInt($("#kaikei").val());
	var shouhiPoint = $("#shouhi_point").val()=="" ? 0 : parseInt($("#shouhi_point").val());
	var sashihiki = jidouPoint = "";

	// ポイント上限を確認する。
	var maxShohiPoints = Math.min(shopConfig.shiyou_point_jyougen, genzaiPoint);

	// 使用ポイントの調整。
	if (shouhiPoint > maxShohiPoints) shouhiPoint = maxShohiPoints;
	if (shouhiPoint > kaikei) shouhiPoint = kaikei;

	if ($("#shouhi_point").val()!="") $("#shouhi_point").val(shouhiPoint);


	if (kaikei > 0) {
		// 	差引金額
		sashihiki = kaikei - shouhiPoint;

		// 支払方法によって還元率が異なる。
		var percent = ( $("#shiharai_houhou").val() == '2' ) ? shopConfig.point_percentage_card : shopConfig.point_percentage_genkin;
		var amount = ( shopConfig.keisan_flg == '2' ) ? kaikei : sashihiki;
		jidouPoint = Math.floor(amount * percent / 100);
	}

	$("#sashihiki_kaikei").text(sashihiki);
	$("#jidou_point").text(jidouPoint);
}
@endif

@if ($packType === '2')
{{-- *********************************** スタンプの場合 *********************************** --}}
function getShopConfig(elem) {

	if ($("#shop_config_view").length == 0) return;

	var shopCd = $("#selected_shop_cd").val();
	var datetime = $("#raiten_y").val()+","+$("#raiten_m").val()+","+$("#raiten_d").val()+","+$("#raiten_h").val()+","+$("#raiten_i").val();


	$.getJSON("/system/category1/inc/ajax_shop_stamp_config.php",{
		shop_cd:shopCd,
		datetime:datetime
	},function(data) {
		shopConfig = data; // グローバル変数にデータを挿入
		var view = "";
		view += '<span style="color:blue;font-weight:bold;">'+shopConfig.shop_name+'</span>スタンプルール<br/>';
		view += '現金還元：<span style="color:blue;font-weight:bold;">'+shopConfig.stamp_percentage_genkin+'円につき1個</span><br/>';
		view += 'カード還元：<span style="color:blue;font-weight:bold;">'+shopConfig.stamp_percentage_card+'円につき1個</span><br/>';
		view += '<span style="color:blue;font-weight:bold;">'+shopConfig.date_disp+'</span>の倍率：<span style="color:blue;font-weight:bold;">'+shopConfig.stamp_count+'倍</span><br/>';
		$("#shop_config_view").html(view);
		calculate();
	});

}

function calculate() {
	var kaikei = $("#kaikei").val()=="" ? 0 : parseInt($("#kaikei").val());
	var jidouPoint = "";

	if (kaikei > 0) {
		// 支払方法によって還元率が異なる。
		var percent = ( $("#shiharai_houhou").val() == '2' ) ? shopConfig.stamp_percentage_card : shopConfig.stamp_percentage_genkin;

		jidouPoint = Math.floor(kaikei / percent)*shopConfig.stamp_count;
	}

	$("#jidou_point").text(jidouPoint);
}
@endif

function setRaitenTimeSelect(y,m,d,h,i) {
	$("#raiten_timestamp").load("/system/category1/inc/ajax_raiten_time_select.php",{
		y:y,
		m:m,
		d:d,
		h:h,
		i:i
	}, function() {$('.raitenTimeSelect').change();});
}

var shopConfig = new Object();

$(document).ready(function() {
	$.ajaxSetup({async: false}); //非同期をfalseにする。

	$(".numbersonly").css("ime-mode", "disabled");
	$(".numbersonly").css("text-align", "right");

	$(".numbersonly").keypress(function(e){
		var target = $(e.target);
		return(numbersonly(target, e));
	});

	$(".numbersonly").bind('change keyup',function(e){
		$(this).val($(this).val().replace(no_digit, ''));
		calculate();
	});

	$("#shiharai_houhou").bind('change',function(e){
		calculate();
	});

	if (typeof(getShopConfig) == "function") {
		$("#selected_shop_cd, .raitenTimeSelect").live('change', function(){
			getShopConfig($(this));
		});
		getShopConfig();
	}

	$("#now_btn").click(function(){
		$("#raiten_cd").val("");
		var myDate = new Date();

		var yy = myDate.getFullYear();
		var mm = myDate.getMonth() + 1;
		var dd = myDate.getDate();
		var hh = myDate.getHours();
		var ii = myDate.getMinutes();

		setRaitenTimeSelect(yy,mm,dd,hh,ii);
	});
	{{-- $sendJs: solo dígitos --}}
	setRaitenTimeSelect({!! $sendJs !!});
});
//-->
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■顧客登録情報</p>




<form method="post" name="mainForm" id="mainForm" class="form" autocomplete="off" action="{{ route('admin.clientes.visit-records.store', $wintype) }}" onsubmit="submitChk();return false;">
@csrf

<input type="hidden" name="add_record" value="{{ $hidden['add_record'] }}">
<input type="hidden" name="kaiin_cd" value="{{ $hidden['kaiin_cd'] }}">

<input type="hidden" name="tab" value="{{ $hidden['tab'] }}">
<input type="hidden" name="act" value="{{ $hidden['act'] }}">
<input type="hidden" name="related_features_cd" value="{{ $hidden['related_features_cd'] }}">
<input type="hidden" name="raiten_cd" id="raiten_cd" value="{{ $hidden['raiten_cd'] }}">
<input type="hidden" name="page" value="{{ $hidden['page'] }}">

<!--顧客情報table start-->

<fieldset>
	<legend>顧客情報</legend>
	<table class="tableBox">

		<colgroup>
			<col style='width:6em;'>
			<col style='width:auto;'>
		</colgroup>

		<tr>
			<th><label for="user_id">顧客ID</label></th>
			<td>{{ $customer->id }}</td>
		</tr>

		<tr>
			<th><label for="user_id">顧客名</label></th>
			<td>{{ $customer->name }}</td>
		</tr>

		<tr>
			<th><label for="user_id">処理店舗</label></th>
			<td>
			@if ($isMaster)
				<select name="selected_shop_cd" id="selected_shop_cd">
				@foreach ($shops as $shopCd => $shopName)
				<option value="{{ $shopCd }}" @selected((string) $selectedShopCd === (string) $shopCd) >{{ $shopName }}</option>
				@endforeach
				</select>
			@else
				{{ $shops[$sessionShopCd] ?? '' }}
				<input type="hidden" name="selected_shop_cd" id="selected_shop_cd" value="{{ $sessionShopCd }}" />
			@endif

			</td>
		</tr>

	</table>
</fieldset>


<fieldset>
	<legend>来店履歴</legend>
	<table class="tableBox">
		<colgroup>
			<col style='width:6em;'>
			<col style='width:auto;'>
		</colgroup>
		<tr>
			<th>来店日時</th>
			<td @if ($raitenError !== '')class="Ng"@endif>
				<span id="raiten_timestamp">
				</span>

				&emsp;<input type="button" id="now_btn" value="現時刻で新規来店とする" />&emsp;
@if (! $isUpdate)
				<input type="button" id="raiten_btn" value="既に取得済みの来店履歴から流用する" />
@endif

				@if ($raitenError !== '')<br /><p class="m0">{{ $raitenError }}</p>@endif
			</td>
		</tr>
	</table>
</fieldset>


@if ($packType !== '' && isset($logType))
<fieldset>
	<legend>{{ $logType }}管理</legend>
	<table class="tableBox">


@if ($isUpdate)


@if ($existLogData)
		<tr>
			<th style="width: auto;">処理日</th>
			<th style="width: auto;">処理内容</th>

			<th style="width: auto;">支払方法</th>
			<th style="width: auto;">還元率</th>
			<th style="width: auto;">処理前{{ $logType }}</th>
			<th style="width: auto;">明細通知</th>
			<th style="width: auto;">端末No</th>
			<th style="width: auto;">処理スタッフ</th>
		</tr>

		<tr>
			<td>{{ is_numeric($log['touroku_date'] ?? null) ? \Illuminate\Support\Carbon::createFromTimestamp((int) $log['touroku_date'], 'Asia/Tokyo')->format('Y/m/d H:i') : '' }}</td>
			<td>

				<table>

				<tr>
				<th style="width: auto;">会計</th>
				<td><span class="kyouchouBlue">{{ number_format((int) ($log['kaikei'] ?? 0)) }}</span> 円</td>
				</tr>

@if ($packType === '1')
				<tr>
				<th style="width: auto;">{{ $logType }}</th>
				<td><span class="kyouchouRed">{{ $log['shouhi_point'] ?? '' }}</span> {{ $logUnit }} 使用</td>
				</tr>

				<tr>
				<th style="width: auto;">差引会計</th>
				<td><span class="kyouchouBlue">{{ number_format((int) ($log['sashihiki_kaikei'] ?? 0)) }}</span> 円</td>
				</tr>
@endif

				</table>


				自動計算で <span class="kyouchouBlue">{{ $jidouUnits }}</span> {{ $logUnit }} 追加<br />
				追加処理で <span class="kyouchouBlue">{{ $sounyuUnits }}</span> {{ $logUnit }} 追加

			</td>

			<td>{{ ((float) ($log['shiharai_houhou'] ?? 0) == 2) ? 'カード' : '現金' }}</td>
			<td>{{ ($log['percentage'] ?? '') . $logRateUnit }}@if ($logBairitsu !== '')<br/>{{ $logBairitsu }}@endif</td>
			<td>{{ number_format($pastUnits) . $logUnit }}</td>
			<td>{{ ((float) ($log['meisai_mail'] ?? 0) == 1) ? '通知済' : '未' }}</td>
			<td>{{ (($log['tb_terminal_cd'] ?? '') == '-1') ? '管理画面' : ($log['tb_terminal_cd'] ?? '') }}</td>
			<td>{{ $log['staff_name'] ?? '' }}</td>
		</tr>
@else
		<tr>
			<td><span class="kyouchouRed">{{ $logType }}が付けられていません。</span></td>
		</tr>
@endif


@else
		<colgroup>
			<col style='width:6em;'>
			<col style='width:25em;'>
			<col style='width:auto;'>
		</colgroup>

		<tr>
			<th>現在の所有{{ $logType }}</th>
			<td colspan="2"><span class="genzaiPoint">{{ number_format($actualUnits) }}</span> {{ $logUnit }}<input type="hidden" id="genzai_point" value="{{ $actualUnits }}" /></td>
		</tr>
		<tr>
			<th>会計</th>
			<td>

				<table style="table-layout:fixed;border:none;">
					<tr>
						<td align="right" style="border-right:none;">支払方法</td>
						<td align="right" style="border-left:none;">
							<select name="shiharai_houhou" id="shiharai_houhou">
								<option value="1" @selected(($log['shiharai_houhou'] ?? '') == '1')>現金</option>
								<option value="2" @selected(($log['shiharai_houhou'] ?? '') == '2')>カード</option>
							</select>
						</td>
					</tr>
					<tr>
						<td align="right" style="border-right:none;">お会計金額</td>
						<td align="right" style="border-left:none;"><input type="text" name="kaikei" id="kaikei" value="{{ $log['kaikei'] ?? '' }}" maxlength="7" class="numbersonly" />&nbsp;円</td>
					</tr>

@if ($packType === '1')
					<tr>
						<td align="right" style="border-right:none;">{{ $logType }}使用数</td>
						<td align="right" style="border-left:none;"><input type="text" name="shouhi_point" id="shouhi_point" value="{{ $log['shouhi_point'] ?? '' }}" maxlength="6" class="numbersonly" />&nbsp;{{ $logUnit }}</td>
					</tr>
					<tr>
						<td align="right" style="border-right:none;">差引会計金額</td>
						<td align="right" style="border-left:none;"><span id="sashihiki_kaikei"></span>&nbsp;円</td>
					</tr>
@endif

					<tr>
						<td align="right" style="border-right:none;">獲得{{ $logType }}</td>
						<td align="right" style="border-left:none;"><span id="jidou_point"></span>&nbsp;{{ $logUnit }}</td>
					</tr>
					<tr>
						<td align="right" style="border-right:none;">手動{{ $logType }}&nbsp;
						<select name="sounyu_point_add_remove_flg" id="sounyu_point_add_remove_flg">
							<option value="" @selected(($log['sounyu_point_add_remove_flg'] ?? '') != '2')>加算</option>
							<option value="2" @selected(($log['sounyu_point_add_remove_flg'] ?? '') == '2')>減算</option>
						</select>
						</td>
						<td align="right" style="border-left:none;"><input type="text" name="sounyu_point" id="sounyu_point" value="{{ $log['sounyu_point'] ?? '' }}" maxlength="6" class="numbersonly" />&nbsp;{{ $logUnit }}</td>
					</tr>
				</table>

			</td>

			<td>
				<div id="shop_config_view">
				</div>
			</td>
		</tr>


@endif


	</table>
</fieldset>
@endif


@php
    $formMax = count($fields);
    $baseCnt = (int) ($category['disp_column'] ?? 0);
    $cnt = 1;
@endphp
<fieldset>
<legend>{{ $category['name'] ?? '' }}</legend>
<table class="tableBox">


@foreach ($fields as $row => $form)
@php
    $openTr = $baseCnt > 1 ? ($cnt % $baseCnt == 1) : true;
    $formLabelName = match (true) {
        in_array($form['form_type'], ['起算日', '年', '年月', '年月日'], true) => 'form' . $form['cd'] . 'yyy',
        $form['form_type'] === 'ラジオボタン' => 'form' . $form['cd'] . '0',
        in_array($form['form_type'], ['チェックボックス', 'メニュー項目'], true) => 'form' . $form['cd'] . '_0',
        default => 'form' . $form['cd'],
    };
    // 繰り返しが最後の場合は残りの数だけtdにcolspanをつける。
    $colspan = null;
    if ($row == $formMax - 1 && $baseCnt) {
        $colspansNow = ($row + 1) % $baseCnt * 2;
        if ($colspansNow > 0) {
            $colspan = 2 * $baseCnt - $colspansNow + 1;
        }
    }
    if ($baseCnt > 1) {
        $closeTr = ($formMax - $cnt > $baseCnt) ? ($cnt % $baseCnt == 0) : ($formMax + $baseCnt - $cnt == 0);
    } else {
        $closeTr = true;
    }
    $cnt++;
    $fieldError = $fieldErrors['form' . $form['cd']] ?? null;
@endphp
@if ($openTr)<tr>@endif
    <th><label for="{{ $formLabelName }}">{{ $form['name'] }}@if ($form['hissu'] == '1')<em>※</em>@endif</label></th>
    <td @if ($fieldError)class="Ng"@endif @if ($colspan) colspan="{{ $colspan }}"@endif>

@include('admin.clientes.partials.custom_field', ['field' => $form, 'values' => $values, 'mode' => 'input', 'menuItems' => []])
@if ($form['unit'] ?? '')
@if ($form['form_type'] === '起算日')
<br />※検索は年数の範囲を指定します。（{{ $form['unit'] }}）<br />
@elseif ($form['form_type'] !== 'テーブル')
{{ $form['unit'] }}<br />
@endif
@endif
@if ($fieldError)<br /><p class="m0">{{ $fieldError }}</p>@endif



</td>

@if ($closeTr)</tr>@endif
@endforeach

</table>
</fieldset>

<!--カスタマイズ情報table end-->

@if ($category || $formMax > 0)
<div class="btnBox">
<input type="hidden" name="add_record_verify" id="add_record_verify" value="{{ $verifyToken }}" />
<input type="hidden" name="registerBtn" id="registerBtn" value="1" />
<input type="submit" name="enter" id="enter" class="btn btnStyleS" value="この内容で登録する" />
</div>
@else
<div class="caution center">
	追加項目の連結処理にて、フォーム設定が不足しています。<br>
	または、連結された追加項目のカテゴリ設定が複数レコード対応でない可能性があります。<br>
	設定内容をご確認ください。
</div>
@endif


</form>


<form method="get" name="back_form" id="back_form" action="{{ route('admin.clientes.show', $customer->cd) }}">
@foreach (['tab', 'act', 'related_features_cd', 'page'] as $key)
<input type="hidden" name="{{ $key }}" value="{{ $hidden[$key] }}">
@endforeach
@if ($popup ?? false)
<input type="hidden" name="wintype" value="no_headers">
@endif
<input type="hidden" name="from" id="from" value="reg">
<input type="hidden" name="kaiin_cd" id="kaiin_cd" value="{{ $customer->cd }}">
<input type="submit" name="back_input" id="back_input" class="btn backBtnStyle" value="戻る" />

</form>



<!-- / .contents --></div>
@endsection
