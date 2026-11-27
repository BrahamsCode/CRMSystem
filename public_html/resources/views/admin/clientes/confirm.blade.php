{{-- Legacy: system/category1/inc/customer_new_confirm.php (rama sin errores: pantalla de confirmación).
     La rama con errores del legacy (re-pintar el formulario con class="Ng") la cubre form.blade.php con $errors
     tras fallar la validación de admin.clientes.confirm.
     Variables:
       $customer          Customer|null  null en alta; en edición el cliente (destino de admin.clientes.update)
       $data              array  datos enviados ya normalizados como el legacy ($data_array): teléfonos sin guiones,
                          mes/día de fechas con 2 dígitos, name/name_kana unidos con espacio en modo 姓/名 y name = 'ゲスト' si vacío,
                          name_kana en katakana, searchKaiin_cd/searchKaiin_name, campos personalizados form{cd}…, act, customer_cd, kaiin_cd…
                          (sin _token ni _method). Se reenvían todos como campos ocultos.
       $shops             colección de tb_shop (cd, kaishamei) visibles para el usuario
       $staffName         nombre del 指名担当者 elegido (null si no eligió); la fila sale si $data trae la clave tb_staff_cd
       $imeiEnabled       bool  checkImeiItem()
       $reminderEnabled   bool  tb_base_system cd IN (9,11) activo
       $customerGroupName nombre de tb_customer_group del cd elegido (o null)
       $raitenDoukiName   nombre de tb_raiten_douki_master del cd elegido (o null)
       $customCategories  categorías de campos personalizados (ver partials/custom_fields)
       $menuItems         filas de tb_yoyaku_koumoku_master para campos 'メニュー項目' (opcional)
       $siteName          nombre del sitio para el <title> --}}
@extends('layouts.legacy')

@php
    $act = $data['act'] ?? ($customer ? 'up' : 'new');
    $d = fn (string $key) => $data[$key] ?? null;
    $pageTitle = $act === 'new' ? '顧客新規登録' : '顧客登録情報 修正';
    $shopNames = collect($shops)->pluck('kaishamei', 'cd');
    $kojinBirth = $d('kojin_birth_y').'/'.$d('kojin_birth_m').'/'.$d('kojin_birth_d');
    $daihyoushaBirth = $d('daihyousha_birth_y').'/'.$d('daihyousha_birth_m').'/'.$d('daihyousha_birth_d');
    $houjinSetsuritsu = $d('houjin_setsuritsu_y').'/'.$d('houjin_setsuritsu_m').'/'.$d('houjin_setsuritsu_d');
    // Campos que se reenvían ocultos (el legacy reenviaba todo $data_array)
    $hiddenData = collect($data)->except(['_token', '_method']);
@endphp

@section('menu', 'clientes')
@section('title'){{ $pageTitle }} - {{ $siteName ?? '' }}@endsection

@section('css')
<link href="{{ asset('legacy/system/css/colorbox.css') }}" rel="stylesheet" type="text/css" media="all" />
<style type="text/css">


#kojinOption,
#houjinOption {
	display: none;
}


</style>
@endsection

@section('js')
@include('admin.clientes.partials.ac')
<script type="text/javascript" src="{{ asset('legacy/system/js/jquery.colorbox.js') }}"></script>

<script type="text/javascript" src="{{ asset('legacy/system/category1/inc/js/search_kaiin.js') }}"></script>

<script type="text/javascript">
$(document).ready(function() {

	$(".ac1").accordion('option', 'active', 0);
	$("#zip").mask("9999999",{placeholder:""});

	$("#zip_search").click(function() {
		$("#add").attr("value","")
		zip = $("#zip").attr("value");
		{{-- 住所検索: misma URL del legacy (el legacy usaba "zip.php" relativo a /system/category1/customer/) --}}
		$.getJSON("/system/category1/customer/zip.php",{
						zip: zip
		}, change_addr)
	});

@if ($d('searchKaiin_cd') && $d('searchKaiin_name'))
	setMember({{ Js::from((string) $d('searchKaiin_cd')) }}, {{ Js::from((string) $d('searchKaiin_name')) }});
@endif

	$('#kojin_birth_y,#kojin_birth_m,#kojin_birth_d').change(ageCheck);

	ageCheck();

});

// 生年月日から年齢を自動計算して表示 ==============================================================
// 2012.05.30 大野 要望を受け仕様追加。
function ageCheck() {

	var yy = $('input[name="kojin_birth_y"]').val() + '';
	var mm = $('input[name="kojin_birth_m"]').val() + '';
	var dd = $('input[name="kojin_birth_d"]').val() + '';

	var age = '';

	if (( yy == "" || mm == "" || dd == "" ) || ( yy.match(/[^0-9]/g) || mm.match(/[^0-9]/g) || dd.match(/[^0-9]/g) )) {

		$('#ageDisplay').empty();
		return false;

	} else {

		mm = ("0"+mm).substr(-2);
		dd = ("0"+dd).substr(-2);

		age = calculateAge( yy+'-'+mm+'-'+dd );

	}

	$('#ageDisplay').empty().html('（満&nbsp;'+age+'&nbsp;歳）');

}

function calculateAge( birthday ) {

	var today = new Date();

	today = today.getFullYear()*10000+today.getMonth()*100+100+today.getDate();
	birthday = parseInt(birthday.replace(/-/g,''));

	return(Math.floor((today-birthday)/10000));

} // END --- function calculateAge( birthday )

</script>


<script type="text/javascript" src="{{ asset('legacy/system/js/jquery.maskedinput-1.2.2.js') }}"></script>

<script type="text/javascript">


function change_addr(data) {
		$("#pref").attr("value",data.zip_name);
		$("#add").attr("value",data.town_name);
		$("#add_kana").attr("value",data.town_name_kana);
}

</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■{{ $pageTitle }} 確認</p>

<div class="form">

<fieldset>
<legend>顧客情報</legend>
<table class="tableBox">
	<colgroup>
		<col style='width:7em;'>
		<col style='width:7em;'>
		<col style='width:7em;'>
		<col style='width:auto;'>
		<col style='width:7em;'>
		<col style='width:auto;'>
	</colgroup>
	<tr>
		<th scope="col">会員番号</th>
		<td scope="col">{{ $d('id') }}</td>
		<th scope="col">管理番号</th>
		<td scope="col">{{ $d('kanri_id') }}</td>
		<th>登録店舗</th>
		<td scope="col">{{ $shopNames[$d('tb_shop_cd')] ?? '' }}</td>
	</tr>
	<tr>
<!--		<th>登録年月日</th>
		<td>{{ $d('touroku') }}</td> -->
		<th>個人・法人</th>
		<td colspan="6">{{ $d('type') }}</td>
	</tr>
	<tr>
		<th><small>フリガナ</small></th>
		<td colspan="6"><small>{{ $d('name_kana') }}</small></td>
	</tr>
	<tr>
		<th>名前</th>
		<td colspan="6">{{ $d('name') }}</td>
	</tr>
	<tr>
		<th rowspan="7">住所</th>
		<th>郵便番号</th>
		<td colspan="4">
		@can('kengen', [9, 1]){{ $d('zip') }}@else*******@endcan
		</td>
	</tr>
	<tr>
		<th>都道府県</th>
		<td colspan="4">
		@can('kengen', [10, 1]){{ $d('pref') }}@else****@endcan
		</td>
	</tr>
	<tr>
		<th>市区町村(カナ)</th>
		<td colspan="4">
		@can('kengen', [11, 1]){{ $d('add_kana') }}@else**********@endcan
		</td>
	</tr>
	<tr>
		<th>市区町村</th>
		<td colspan="4">
		@can('kengen', [14, 1]){{ $d('add') }}@else**********@endcan
		</td>
	</tr>
	<tr>
		<th>丁目・番地</th>
		<td colspan="4">
		@can('kengen', [15, 1]){{ $d('add_number') }}@else*******@endcan
		</td>
	</tr>
	<tr>
		<th><small>マンション・ビル名(カナ)</small></th>
		<td colspan="4">
		<small>@can('kengen', [12, 1]){{ $d('add_build_kana') }}@else**********@endcan</small>
		</td>
	</tr>
	<tr>
		<th>マンション・ビル名</th>
		<td colspan="4">
		@can('kengen', [13, 1]){{ $d('add_build') }}@else**********@endcan
		</td>
	</tr>
	<tr>
		<th rowspan="3">連絡先</th>
		<th>電話番号</th>
		<td>@can('kengen', [18, 1]){{ $d('tel') }}@else**********@endcan</td>
		<th>FAX番号</th>
		<td colspan="2">@can('kengen', [19, 1]){{ $d('fax') }}@else**********@endcan</td>
	</tr>
	<tr>
		<th>メールアドレス1</th>
		<td colspan="4">
		@can('kengen', [20, 1]){{ $d('email1') }}@else**********@endcan
		</td>
	</tr>
	<tr>
		<th>メールアドレス2</th>
		<td colspan="4">
		@can('kengen', [21, 1]){{ $d('email2') }}@else**********@endcan
		</td>
	</tr>
@if (array_key_exists('tb_staff_cd', $data))
	{{-- 2010.03.10 マルティン 指名担当者 --}}
	<tr>
		<th>指名担当者</th>
		<td colspan="6">@if ($d('tb_staff_cd') != ''){{ $staffName }}@else&nbsp;@endif</td>
	</tr>
@endif

	<tr>
		<th>紹介者</th>
		<td colspan="6">{{ $d('searchKaiin_name') }}</td>
	</tr>
</table>
</fieldset>
<!--基本情報table start-->



<!--個人用追加入力table start-->
<div id="kojinOption" @if ($d('type') == '個人')style="display: block;"@endif>

<fieldset>
<legend>個人情報</legend>
<table class="tableBox">
	<colgroup>
		<col style='width:7em;'>
		<col style='width:auto;'>
		<col style='width:7em;'>
		<col style='width:auto;'>
		<col style='width:7em;'>
		<col style='width:auto;'>
	</colgroup>
	<tr>
		<th>生年月日</th>
		<td>
		@can('kengen', [23, 1]){{ $kojinBirth }}@else****/**/**@endcan
		@can('kengen', [23, 1])<span id="ageDisplay"></span>@else（満&nbsp;**&nbsp;歳@endcan</td>
		<th>性別</th>
		<td>@can('kengen', [24, 1]){{ $d('kojin_sex') }}@else*****@endcan</td>
		<th>血液型</th>
		<td>@can('kengen', [25, 1]){{ $d('kojin_bloodtype') }}@else*****@endcan</td>
	</tr>
	<tr>
		<th>携帯電話番号</th>
		<td colspan="5">@can('kengen', [26, 1]){{ $d('kojin_mobile') }}@else**********@endcan</td>
	</tr>
@if ($imeiEnabled)
	<tr>
		<th>IMEI</th>
		<td colspan="5">@can('kengen', [51, 1]){{ $d('imei') }}@else**********@endcan</td>
	</tr>
@endif
	<tr>
		<th>個人メールアドレス</th>
		<td colspan="5">@can('kengen', [27, 1]){{ $d('kojin_mail') }}@else**********@endcan</td>
	</tr>
	<tr>
		<th><small>勤務先 名前(カナ)</small></th>
		<td colspan="5">@can('kengen', [29, 1]){{ $d('kinmusaki_name_kana') }}@else**********@endcan</td>
	</tr>

	<tr>
		<th><small>職業</small></th>
		<td colspan="5">@can('kengen', [28, 1]){{ $d('job') }}@else*****@endcan</td>
	</tr>

	<tr>
		<th>勤務先 名前</th>
		<td colspan="5">@can('kengen', [30, 1]){{ $d('kinmusaki_name') }}@else**********@endcan</td>
	</tr>
	<tr>
		<th>勤務先 業種</th>
		<td colspan="5">@can('kengen', [31, 1]){{ $d('kinmusaki_gyoushu') }}@else*****@endcan</td>
	</tr>
	<tr>
		<th>勤務先 電話番号</th>
		<td>@can('kengen', [32, 1]){{ $d('kinmusaki_tel') }}@else**********@endcan</td>
		<th>勤務先 ファックス番号</th>
		<td colspan="5">@can('kengen', [33, 1]){{ $d('kinmusaki_fax') }}@else**********@endcan</td>
	</tr>
</table>
</fieldset>
</div>
<!--個人用追加入力table end-->






<!--法人用追加入力table start-->
<div id="houjinOption" @if ($d('type') == '法人')style="display: block;"@endif>

<fieldset>
<legend>法人情報</legend>
<table class="tableBox">
	<colgroup>
		<col style='width:7em;'>
		<col style='width:auto;'>
		<col style='width:7em;'>
		<col style='width:auto;'>
	</colgroup>
	<tr>
		<th>設立年月日</th>
		<td>{{ $houjinSetsuritsu }}</td>
		<th>資本金</th>
		<td>{{ $d('houjin_shihon') }}</td>
	</tr>
	<tr>
		<th><small>代表者(カナ)</small></th>
		<td colspan="3">{{ $d('daihyousha_name_kana') }}
		</td>
	</tr>
	<tr>
		<th>代表者 名前</th>
		<td colspan="3">{{ $d('daihyousha_name') }}</td>
	</tr>
	<tr>
		<th>代表者 生年月日</th>
		<td>{{ $daihyoushaBirth }}</td>
		<th>代表者 性別</th>
		<td>{{ $d('daihyousha_sex') }}</td>
	</tr>
	<tr>
		<th>業種</th>
		<td colspan="3">{{ $d('houjin_gyoushu') }}</td>
	</tr>
	<tr>
		<th>部署</th>
		<td colspan="3">{{ $d('houjin_busho') }}</td>
	</tr>
	<tr>
		<th><small>担当者 名前(カナ)</small></th>
		<td colspan="3">{{ $d('tantou_name_kana') }}</td>
	</tr>
	<tr>
		<th>担当者 名前</th>
		<td colspan="3">{{ $d('tantou_name') }}</td>
	</tr>
	<tr>
		<th>担当者 電話番号</th>
		<td>{{ $d('tantou_tel') }}</td>
		<th>担当者 ファックス番号</th>
		<td>{{ $d('tantou_fax') }}</td>
	</tr>
	<tr>
		<th>担当者 メールアドレス</th>
		<td colspan="3">{{ $d('tantou_mail') }}</td>
	</tr>
</table>
</fieldset>
</div>
<!--法人用追加入力table end-->

<!--販促用情報table start-->
<fieldset>
<legend>販促用情報</legend>

<table class="tableBox">
	<colgroup>
		<col style='width:7em;'>
		<col style='width:auto;'>
		<col style='width:7em;'>
		<col style='width:auto;'>
	</colgroup>
	<tr>
		<th>メールマガジン配信有無</th>
		<td>@if ((float) $d('mailmaga_flg') == 1)配信する@elseif ((float) $d('mailmaga_flg') == 0)配信しない@elseif ((float) $d('mailmaga_flg') == 9)不達配信@endif</td>

		<th>アドレス区分</th>
		<td>{{ $d('mailmaga_email_type') }}</td>
	</tr>

@if ($reminderEnabled)
	<tr>
		<th>予約リマインダーメール配信有無</th>
		<td colspan="3">{{ $d('stop_yoyaku_reminder_mail_flg') == '1' ? '配信しない' : '配信する' }}</td>
	</tr>
@endif

	<tr>
		<th>顧客グループ</th>
		<td>
		{{ $customerGroupName }}
		</td>

		<th>新規来店動機</th>
		<td>
		{{ $raitenDoukiName }}
		</td>
	</tr>

	<tr>
		<th>備考</th>
		<td colspan="3">{{ $d('bikou') }}</td>
	</tr>
</table>
</fieldset>
<!--販促用情報table end-->




@include('admin.clientes.partials.custom_fields', ['categories' => $customCategories, 'values' => $data, 'mode' => 'confirm', 'menuItems' => $menuItems ?? []])




<fieldset>
<legend>ログイン</legend>
<table class="tableBox">
	<colgroup>
		<col style='width:7em;'>
		<col style='width:auto;'>
	</colgroup>
  <tr>
    <th>ID</th>
    <td>@can('kengen', [1, 1]){{ $d('id') }}@else**********@endcan</td>
  </tr>
  <tr>
    <th><label for="bikou">パスワード</label></th>
    <td>@can('kengen', [2, 1]){{ $d('login_pass') }}@else**********@endcan</td>
  </tr>
</table>
</fieldset>



<div class="btnBox">

{{-- 書き直す: vuelve al formulario con los mismos datos (equivale al POST con "modoru" a customer_new.php) --}}
<form method="post" action="{{ route('admin.clientes.rewrite') }}">
@csrf
@include('admin.clientes.partials.hidden_fields', ['fields' => $hiddenData])

<input type="submit" name="modoru" value="書き直す" class="existBtn btnStyleM" />
</form>

<form method="post" action="{{ $customer ? route('admin.clientes.update', $customer->cd) : route('admin.clientes.store') }}">
@csrf
@if ($customer)
@method('PUT')
@endif
@include('admin.clientes.partials.hidden_fields', ['fields' => $hiddenData])

<input type="hidden" name="touroku_kubun" value="{{ $d('touroku_kubun') }}">

<input type="submit" name="enter" id="enter" class="btn btnStyleM" value="この内容で登録する">


</form>

</div>

<!-- / .form --></div>

<!-- / .contents --></div>
@endsection
