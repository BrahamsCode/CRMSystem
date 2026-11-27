{{-- Legacy: system/category1/inc/customer_new.php (alta y edición; también la vuelta "書き直す" desde la confirmación)
     y la pantalla de errores de customer_new_confirm.php (al fallar la validación se vuelve aquí con $errors).
     Variables:
       $customer         Customer|null  null en alta; en edición el cliente (tb_customer)
       $data             array  valores iniciales con las claves del $data_array legacy: columnas de tb_customer
                         (id, kanri_id, tb_shop_cd, type, name, name_kana, zip, pref, add_kana, add, add_number, add_build_kana,
                         add_build, tel, fax, email1, email2, tb_staff_cd, kojin_sex, kojin_bloodtype, kojin_mobile, imei,
                         kojin_mail, job, kinmusaki_*, houjin_*, daihyousha_*, tantou_*, mailmaga_flg, mailmaga_email_type,
                         stop_yoyaku_reminder_mail_flg, tb_customer_group_cd, tb_raiten_douki_master_cd, bikou, login_pass, cd)
                         + derivadas: name_sei, name_mei, name_kana_sei, name_kana_mei, kojin_birth_y/m/d, daihyousha_birth_y/m/d,
                         houjin_setsuritsu_y/m/d, shoukai_tb_customer_cd, shoukaisha_name ('【id】  nombre').
                         En alta: id = siguiente 会員番号 (hatuban), login_pass = contraseña aleatoria de 4 dígitos, tel = ?tel= de CTI.
       $nameInputMode    '1' = 姓/名 separados; otro valor = nombre junto (contents_master name_input_mode_flg)
       $isMaster         bool  rank master
       $shops            colección de tb_shop (cd, kaishamei) visibles para el usuario (disp_flg<>'9'; si no es master, solo su tienda)
       $currentShopCd    cd de la tienda de la sesión (no master)
       $currentShopName  nombre de la tienda de la sesión (no master)
       $staffEnabled     bool  módulo スタッフ管理 activo (tb_base_system_child)
       $staffs           colección de tb_staff (cd, name, shop_cd) con disp_flg='1' y reserve_flg='1', ORDER BY shop_cd, cd
       $jobs             array de nombres de tb_job ORDER BY cd
       $imeiEnabled      bool  checkImeiItem() (tb_koumoku 'imei')
       $reminderEnabled  bool  tb_base_system cd IN (9,11) activo
       $customerGroups   colección de tb_customer_group (cd, name) disp_flg='1' ORDER BY rank
       $raitenDoukis     colección de tb_raiten_douki_master (cd, name) disp_flg='1' ORDER BY rank
       $customCategories categorías de campos personalizados (ver partials/custom_fields)
       $customValues     array de valores de los campos personalizados (de tb_customer_sub en edición; vacío en alta)
       $menuItems        filas de tb_yoyaku_koumoku_master para campos 'メニュー項目' (opcional)
       $siteName         nombre del sitio para el <title>
     Errores ($errors): claves de campo del legacy (tb_shop_cd, type, name, name_kana, zip, add_kana, add_build_kana, tel, fax,
       email1, email2, kojin_birth, kojin_mobile, imei, kojin_mail, kinmusaki_name_kana, kinmusaki_tel, kinmusaki_fax,
       houjin_setsuritsu, houjin_shihon, daihyousha_name_kana, daihyousha_birth, tantou_name_kana, tantou_tel, tantou_fax,
       tantou_mail, login_pass, form{cd}); el mensaje es el texto legacy (p. ej. "※必須入力項目です。"). --}}
@extends('layouts.legacy')

@php
    $act = $customer ? 'up' : 'new';
    // 2011.04.26 権限: 2 = 新規, 3 = 編集
    $koumokuUpFlg = $act === 'new' ? 2 : 3;
    $v = fn (string $key) => old($key, $data[$key] ?? null);
    $ng = fn (string $key) => $errors->has($key) ? ' class="Ng"' : '';
    $customValuesResolved = session()->hasOldInput() ? session()->getOldInput() : ($customValues ?? []);
    $pageTitle = $act === 'new' ? '顧客新規登録' : '顧客登録情報 修正';
    $prefNames = ['▼都道府県選択', '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県', '茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県', '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県', '静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府', '兵庫県', '奈良県', '和歌山県', '鳥取県', '島根県', '岡山県', '広島県', '山口県', '徳島県', '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県', '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県'];
    $prefValues = array_merge([''], array_slice($prefNames, 1));
    $gyoushuList = ['農業', '林業', '漁業', '鉱業', '採石業', '砂利採取業', '建設業', '製造業', '電気・ガス・熱供給・水道業', '情報通信業', '運輸業', '郵便業', '卸売業', '小売業', '金融業', '保険業', '不動産業', '物品賃貸業', '学術研究', '専門・技術サービス業', '宿泊業', '飲食サービス業', '生活関連サービス業', '娯楽業', '教育', '学習支援業', '医療', '福祉', '他の営利事業', 'その他（政治・経済・文化・宗教団体など）'];
    $shopNames = collect($shops)->pluck('kaishamei', 'cd');
    $shoukaiCd = old('searchKaiin_cd', $data['shoukai_tb_customer_cd'] ?? null);
    $shoukaiName = old('searchKaiin_name', $data['shoukaisha_name'] ?? null);
@endphp

@section('menu', 'clientes')
@section('title'){{ $pageTitle }} - {{ $siteName ?? '' }}@endsection

@section('css')
<link href="{{ asset('legacy/system/css/colorbox.css') }}" rel="stylesheet" type="text/css" media="all" />
<style type="text/css">
#kojinOption, #houjinOption {
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
		$("#add").attr("value","");
		zip = $("#zip").attr("value");
		{{-- 住所検索: misma URL del legacy (ruta legacy) --}}
		$.getJSON("/system/category1/customer/zip.php",{
			zip: zip
		}, change_addr)
	});

@if ($shoukaiCd && $shoukaiName)
	setMember({{ Js::from((string) $shoukaiCd) }}, {{ Js::from((string) $shoukaiName) }});
@endif

	$('#kojin_birth_y,#kojin_birth_m,#kojin_birth_d').change(ageCheck);
	ageCheck();


});

function calculateAge( birthday ) {

	var today = new Date();

	today = today.getFullYear()*10000+today.getMonth()*100+100+today.getDate();
	birthday = parseInt(birthday.replace(/-/g,''));

	return(Math.floor((today-birthday)/10000));

} // END --- function calculateAge( birthday )

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

</script>

<script type="text/javascript" src="{{ asset('legacy/system/category1/inc/js/user.js') }}"></script>
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
<p class="h2Title">■{{ $pageTitle }}</p>

<p><span class="attention">※</span>マークがある項目は、必須項目です。（空白にはしないで下さい）</p>

<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.clientes.confirm') }}">
@csrf

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
@php
    if (count($shops) == 1) {
        $columns = ' colspan="3"';
        $dispClass = ' class="displayNone"';
    } else {
        $columns = ' colspan="1"';
        $dispClass = '';
    }
    $memberId = sprintf('%06d', (int) $v('id'));
@endphp
		<th scope="col"><label for="id">会員番号</label></th>
		<td{!! $columns !!} scope="col">{{ $memberId }}<input type="hidden" name="id" id="id" class="inputM" value="{{ $memberId }}" /></td>
		<th><label for="kanri_id">管理番号</label></th>
		<td><input type="text" name="kanri_id" id="kanri_id" class="inputM" value="{{ $v('kanri_id') }}" style="ime-mode: disabled;" /></td>
		<th{!! $dispClass !!}><label for="tb_shop_cd">登録店舗<em>※</em></label></th>
		<td{!! $dispClass !!}{!! $ng('tb_shop_cd') !!}>
@if ($isMaster)
		<select name="tb_shop_cd" id="tb_shop_cd">
@foreach ($shops as $shop)
<option value="{{ $shop->cd }}" @selected((string) $shop->cd === (string) $v('tb_shop_cd')) >{{ $shop->kaishamei }}</option>
@endforeach
		</select>
@else
		{{ $currentShopName }}
		<input type="hidden" name="tb_shop_cd" id="tb_shop_cd" value="{{ $currentShopCd }}" />

@endif
		@error('tb_shop_cd')<p class="m0">{{ $message }}</p>@enderror
		</td>

	</tr>
	<tr>

		<th><label for="type1">個人・法人<em>※</em></label></th>
		<td colspan="5"{!! $ng('type') !!}>
			<ul class="textBox">
			<li><input type="radio" name="type" id="type1" class="checkBtn" value="個人"@if ($v('type') == '個人' || $v('type') == '') checked="checked"@endif /><label for="type1">個人</label></li>
			<li><input type="radio" name="type" id="type2" class="checkBtn" value="法人"@if ($v('type') == '法人') checked="checked"@endif /><label for="type2">法人</label></li>
			</ul>@error('type')<p class="m0">{{ $message }}</p>@enderror</td>
	</tr>

	{{-- ◆2012.09.21【大野】名前入力についてフラグ制御で姓と名の入力を分ける仕様 (contents_master name_input_mode_flg) --}}
	<tr>
		<th><label for="name_kana"><small>名前(カナ)</small></label></th>
		<td colspan="5"{!! $ng('name_kana') !!}>@if ($nameInputMode != '1')<input type="text" name="name_kana" id="name_kana" class="writeArea inputSmall" value="{{ $v('name_kana') }}" />@else【姓】<input type="text" name="name_kana_sei" id="name_kana_sei" class="writeArea inputM" value="{{ $v('name_kana_sei') }}" />&nbsp;【名】<input type="text" name="name_kana_mei" id="name_kana_mei" class="writeArea inputM" value="{{ $v('name_kana_mei') }}" />@endif

		@include('admin.clientes.partials.tooltip', ['n' => 8])
		@error('name_kana')<p class="m0">{{ $message }}</p>@enderror
		</td>
	</tr>
	<tr>
		<th><label for="name">名前</label></th>
		<td colspan="5"{!! $ng('name') !!}>@if ($nameInputMode != '1')<input type="text" name="name" id="name" class="writeArea" value="{{ $v('name') }}" />@else【姓】<input type="text" name="name_sei" id="name_sei" class="writeArea inputM" value="{{ $v('name_sei') }}" />&nbsp;【名】<input type="text" name="name_mei" id="name_mei" class="writeArea inputM" value="{{ $v('name_mei') }}" />@endif

		@include('admin.clientes.partials.tooltip', ['n' => 7])
		@error('name')<p class="m0">{{ $message }}</p>@enderror
		</td>
	</tr>
	<tr>
		<th rowspan="7">住所</th>
		<th><label for="zip">郵便番号</label></th>
		<td colspan="4"{!! $ng('zip') !!}>
		@include('admin.clientes.partials.kaiin_field', ['kengen' => 9, 'upType' => $koumokuUpFlg, 'type' => 'text', 'name' => 'zip', 'value' => $v('zip'), 'class' => 'inputS', 'style' => 'ime-mode: disabled;'])
		@can('kengen', [9, 1])&nbsp;<input type="button" name="zip_search" id="zip_search" value="住所検索" />@endcan
		@include('admin.clientes.partials.tooltip', ['n' => 0])
		@error('zip')<p class="m0">{{ $message }}</p>@enderror
		</td>
	</tr>
	<tr>
		<th>都道府県</th>
		<td colspan="4">
		@include('admin.clientes.partials.kaiin_field', ['kengen' => 10, 'upType' => $koumokuUpFlg, 'type' => 'select', 'name' => 'pref', 'names' => $prefNames, 'values' => $prefValues, 'selected' => $v('pref')])

		</td>
	</tr>
	<tr>
		<th><label for="add_kana"><small>市区町村(カナ)</small></label></th>
		<td colspan="4"{!! $ng('add_kana') !!}>
		@include('admin.clientes.partials.kaiin_field', ['kengen' => 11, 'upType' => $koumokuUpFlg, 'type' => 'text', 'name' => 'add_kana', 'value' => $v('add_kana'), 'class' => 'writeArea inputSmall'])
		@include('admin.clientes.partials.tooltip', ['n' => 41])
		@error('add_kana')<p class="m0">{{ $message }}</p>@enderror
		</td>
	</tr>
	<tr>
		<th><label for="add">市区町村</label></th>
		<td colspan="4">
		@include('admin.clientes.partials.kaiin_field', ['kengen' => 14, 'upType' => $koumokuUpFlg, 'type' => 'text', 'name' => 'add', 'value' => $v('add'), 'class' => 'writeArea'])
		@include('admin.clientes.partials.tooltip', ['n' => 40])
		</td>
	</tr>
	<tr>
		<th><label for="add_number">丁目・番地</label></th>
		<td colspan="4">
		@include('admin.clientes.partials.kaiin_field', ['kengen' => 15, 'upType' => $koumokuUpFlg, 'type' => 'text', 'name' => 'add_number', 'value' => $v('add_number'), 'class' => 'inputL'])
		@include('admin.clientes.partials.tooltip', ['n' => 2])
		</td>
	</tr>
	<tr>
		<th><label for="add_build_kana"><small>マンション・ビル名(カナ)</small></label></th>
		<td colspan="4"{!! $ng('add_build_kana') !!}>
		@include('admin.clientes.partials.kaiin_field', ['kengen' => 12, 'upType' => $koumokuUpFlg, 'type' => 'text', 'name' => 'add_build_kana', 'value' => $v('add_build_kana'), 'class' => 'writeArea inputSmall'])
		@include('admin.clientes.partials.tooltip', ['n' => 11])
		@error('add_build_kana')<p class="m0">{{ $message }}</p>@enderror
		</td>
	</tr>
	<tr>
		<th><label for="add_build">マンション・ビル名</label></th>
		<td colspan="4">
		@include('admin.clientes.partials.kaiin_field', ['kengen' => 13, 'upType' => $koumokuUpFlg, 'type' => 'text', 'name' => 'add_build', 'value' => $v('add_build'), 'class' => 'writeArea'])
		@include('admin.clientes.partials.tooltip', ['n' => 10])
		</td>
	</tr>
	<tr>
		<th rowspan="3">連絡先</th>
		<th><label for="tel">電話番号</label></th>
		<td colspan="2" style="width: 150px;"{!! $ng('tel') !!}>
		@include('admin.clientes.partials.kaiin_field', ['kengen' => 18, 'upType' => $koumokuUpFlg, 'type' => 'text', 'name' => 'tel', 'value' => $v('tel'), 'class' => 'inputM', 'style' => 'ime-mode: disabled;'])
		@include('admin.clientes.partials.tooltip', ['n' => 0])
		@error('tel')<p class="m0">{{ $message }}</p>@enderror
		</td>
		<th><label for="fax">FAX番号</label></th>
		<td colspan="2"{!! $ng('fax') !!}>
		@include('admin.clientes.partials.kaiin_field', ['kengen' => 19, 'upType' => $koumokuUpFlg, 'type' => 'text', 'name' => 'fax', 'value' => $v('fax'), 'class' => 'inputM', 'style' => 'ime-mode: disabled;'])
		@include('admin.clientes.partials.tooltip', ['n' => 0])
		@error('fax')<p class="m0">{{ $message }}</p>@enderror
		</td>
	</tr>
	<tr>
		<th><label for="email1">メールアドレス1</label></th>
		<td colspan="4"{!! $ng('email1') !!}>
		@include('admin.clientes.partials.kaiin_field', ['kengen' => 20, 'upType' => $koumokuUpFlg, 'type' => 'text', 'name' => 'email1', 'value' => $v('email1'), 'class' => 'inputL', 'style' => 'ime-mode: disabled;'])
		@error('email1')<p class="m0">{{ $message }}</p>@enderror
		</td>
	</tr>
	<tr>
		<th><label for="email2">メールアドレス2</label></th>
		<td colspan="4"{!! $ng('email2') !!}>
		@include('admin.clientes.partials.kaiin_field', ['kengen' => 21, 'upType' => $koumokuUpFlg, 'type' => 'text', 'name' => 'email2', 'value' => $v('email2'), 'class' => 'inputL', 'style' => 'ime-mode: disabled;'])
		@error('email2')<p class="m0">{{ $message }}</p>@enderror
		</td>
	</tr>

@if ($staffEnabled)
	{{-- 2010.03.10 マルティン 指名担当者を選択する --}}
	<tr>
		<th><label for="tb_staff_cd">指名担当者</label></th>
		<td colspan="5">
			<select name="tb_staff_cd" id="tb_staff_cd">
@if (count($staffs) > 0)
<option value="">▼担当者選択</option>
@php $tempShopCd = ''; @endphp
@foreach ($staffs as $staff)
@if ($isMaster && (string) $tempShopCd !== (string) $staff->shop_cd)
@if (! $loop->first)
</optgroup>
@endif
<optgroup label="{{ $shopNames[$staff->shop_cd] ?? '' }}">
@php $tempShopCd = $staff->shop_cd; @endphp
@endif
<option value="{{ $staff->cd }}" @selected((string) $staff->cd === (string) $v('tb_staff_cd')) >{{ $staff->name }}</option>
@endforeach
@if ($isMaster)
</optgroup>
@endif
@else
<option value="">&nbsp;</option>
@endif
			</select>
		</td>
	</tr>
@endif

	{{-- 2012.05.05 マルティン 紹介者を追加 --}}
	<tr>
		<th><label for="shoukai_tb_customer_cd">紹介者</label></th>
		<td colspan="5">
			<span id="searchKaiin_nameDisp"></span>
			<input type="button" id="searchKaiin_btn" value="会員検索" />
			<input type="hidden" id="searchKaiin_cd" name="searchKaiin_cd" value="" />
			<input type="hidden" id="searchKaiin_name" name="searchKaiin_name" value="" />
		</td>
	</tr>
</table>
</fieldset>


<!--基本情報table start-->



<!--個人用追加入力table start-->
<div id="kojinOption">

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
		<th><label for="kojin_birth">生年月日</label></th>
		<td{!! $ng('kojin_birth') !!}>
			西暦@include('admin.clientes.partials.kaiin_field', ['kengen' => 23, 'upType' => $koumokuUpFlg, 'type' => 'text', 'name' => 'kojin_birth_y', 'value' => $v('kojin_birth_y'), 'class' => 'input2S', 'other' => ' style="ime-mode: disabled;" size="4" maxlength="4"'])年
			@include('admin.clientes.partials.kaiin_field', ['kengen' => 23, 'upType' => $koumokuUpFlg, 'type' => 'text', 'name' => 'kojin_birth_m', 'value' => $v('kojin_birth_m'), 'class' => 'input3S', 'other' => ' style="ime-mode: disabled;" size="2" maxlength="2"'])月
			@include('admin.clientes.partials.kaiin_field', ['kengen' => 23, 'upType' => $koumokuUpFlg, 'type' => 'text', 'name' => 'kojin_birth_d', 'value' => $v('kojin_birth_d'), 'class' => 'input3S', 'other' => ' style="ime-mode: disabled;" size="2" maxlength="2"'])日

			{{-- 2012.05.30 大野 生年月日から年齢を自動計算して出力する仕様を追加 --}}
			<span id="ageDisplay"></span>
			@error('kojin_birth')<p class="m0">{{ $message }}</p>@enderror

		</td>

		<th><label for="kojin_sex1">性別</label></th>
		<td>
			<ul class="textBox">
			<li>@include('admin.clientes.partials.kaiin_field', ['kengen' => 24, 'upType' => $koumokuUpFlg, 'type' => 'radio', 'name' => 'kojin_sex', 'idSuffix' => '1', 'value' => '男性', 'class' => 'checkBtn', 'checked' => $v('kojin_sex') == '男性'])</li>
			<li>@include('admin.clientes.partials.kaiin_field', ['kengen' => 24, 'upType' => $koumokuUpFlg, 'type' => 'radio', 'name' => 'kojin_sex', 'idSuffix' => '2', 'value' => '女性', 'class' => 'checkBtn', 'checked' => $v('kojin_sex') == '女性'])</li>
			</ul>
		</td>

		<th>血液型</th>
		<td>
			@include('admin.clientes.partials.kaiin_field', ['kengen' => 25, 'upType' => $koumokuUpFlg, 'type' => 'select', 'name' => 'kojin_bloodtype', 'names' => ['', 'A', 'B', 'O', 'AB'], 'values' => ['', 'A', 'B', 'O', 'AB'], 'selected' => $v('kojin_bloodtype')])
		</td>
	</tr>

	<tr>
		<th><label for="kojin_mobile">携帯電話番号</label></th>
		<td colspan="5"{!! $ng('kojin_mobile') !!}>
		@include('admin.clientes.partials.kaiin_field', ['kengen' => 26, 'upType' => $koumokuUpFlg, 'type' => 'text', 'name' => 'kojin_mobile', 'value' => $v('kojin_mobile'), 'class' => 'inputM', 'style' => 'ime-mode: disabled;'])
		@include('admin.clientes.partials.tooltip', ['n' => 0])
		@error('kojin_mobile')<p class="m0">{{ $message }}</p>@enderror
		</td>
	</tr>

@if ($imeiEnabled)
	<tr>
		<th><label for="imei">IMEI</label></th>
		<td colspan="5"{!! $ng('imei') !!}>
			@include('admin.clientes.partials.kaiin_field', ['kengen' => 51, 'upType' => $koumokuUpFlg, 'type' => 'text', 'name' => 'imei', 'value' => $v('imei'), 'class' => 'inputL', 'style' => 'ime-mode: disabled;'])
			@error('imei')<p class="m0">{{ $message }}</p>@enderror
		</td>
	</tr>
@endif

	<tr>
		<th><label for="kojin_mail">個人メールアドレス</label></th>
		<td colspan="5"{!! $ng('kojin_mail') !!}>
		@include('admin.clientes.partials.kaiin_field', ['kengen' => 27, 'upType' => $koumokuUpFlg, 'type' => 'text', 'name' => 'kojin_mail', 'value' => $v('kojin_mail'), 'class' => 'inputL', 'style' => 'ime-mode: disabled;', 'other' => ' size="40"'])
		@error('kojin_mail')<p class="m0">{{ $message }}</p>@enderror
		</td>
	</tr>

	<tr>
		<th><label for="job">職業</label></th>
		<td colspan="5">
		@include('admin.clientes.partials.kaiin_field', ['kengen' => 28, 'upType' => $koumokuUpFlg, 'type' => 'select', 'name' => 'job', 'names' => array_merge(['▼職業選択'], array_values($jobs)), 'values' => array_merge([''], array_values($jobs)), 'selected' => $v('job')])
		</td>
	</tr>

	<tr>
		<th><label for="kinmusaki_name_kana"><small>勤務先 名前(カナ)</small></label></th>
		<td colspan="5"{!! $ng('kinmusaki_name_kana') !!}>
		@include('admin.clientes.partials.kaiin_field', ['kengen' => 29, 'upType' => $koumokuUpFlg, 'type' => 'text', 'name' => 'kinmusaki_name_kana', 'value' => $v('kinmusaki_name_kana'), 'class' => 'writeArea inputSmall', 'other' => ' size="70"'])
		@include('admin.clientes.partials.tooltip', ['n' => 6])
		@error('kinmusaki_name_kana')<p class="m0">{{ $message }}</p>@enderror
		</td>
	</tr>
		<tr>
		<th><label for="kinmusaki_name">勤務先 名前</label></th>
		<td colspan="5">
		@include('admin.clientes.partials.kaiin_field', ['kengen' => 30, 'upType' => $koumokuUpFlg, 'type' => 'text', 'name' => 'kinmusaki_name', 'value' => $v('kinmusaki_name'), 'class' => 'writeArea', 'other' => ' size="70"'])
		@include('admin.clientes.partials.tooltip', ['n' => 5])
		</td>
	</tr>
	<tr>
		<th>勤務先 業種</th>
		<td colspan="5">
		@include('admin.clientes.partials.kaiin_field', ['kengen' => 31, 'upType' => $koumokuUpFlg, 'type' => 'select', 'name' => 'kinmusaki_gyoushu', 'names' => array_merge(['▼業種選択'], $gyoushuList), 'values' => array_merge([''], $gyoushuList), 'selected' => $v('kinmusaki_gyoushu')])
		</td>
	</tr>
	<tr>
		<th><label for="kinmusaki_tel">勤務先 電話番号</label></th>
		<td{!! $ng('kinmusaki_tel') !!}>
		@include('admin.clientes.partials.kaiin_field', ['kengen' => 32, 'upType' => $koumokuUpFlg, 'type' => 'text', 'name' => 'kinmusaki_tel', 'value' => $v('kinmusaki_tel'), 'class' => 'inputM', 'style' => 'ime-mode: disabled;', 'other' => ' size="20"'])
		@include('admin.clientes.partials.tooltip', ['n' => 0])
		@error('kinmusaki_tel')<p class="m0">{{ $message }}</p>@enderror
		</td>
		<th><label for="kinmusaki_fax">勤務先 ファックス番号</label></th>
		<td colspan="3"{!! $ng('kinmusaki_fax') !!}>
		@include('admin.clientes.partials.kaiin_field', ['kengen' => 33, 'upType' => $koumokuUpFlg, 'type' => 'text', 'name' => 'kinmusaki_fax', 'value' => $v('kinmusaki_fax'), 'class' => 'inputM', 'style' => 'ime-mode: disabled;', 'other' => ' size="20"'])
		@include('admin.clientes.partials.tooltip', ['n' => 0])
		@error('kinmusaki_fax')<p class="m0">{{ $message }}</p>@enderror
		</td>
	</tr>
</table>
</fieldset>
</div>
<!--個人用追加入力table end-->






<!--法人用追加入力table start-->
<div id="houjinOption">

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
		<th><label for="houjin_setsuritsu">設立年月日</label></th>
		<td{!! $ng('houjin_setsuritsu') !!}>
			西暦<input type="text" name="houjin_setsuritsu_y" id="houjin_setsuritsu_y" size="4" maxlength="4" value="{{ $v('houjin_setsuritsu_y') }}" class="inputS" style="ime-mode: disabled;" />年
			<input type="text" name="houjin_setsuritsu_m" id="houjin_setsuritsu_m" size="2" maxlength="2" value="{{ $v('houjin_setsuritsu_m') }}" class="input2S" style="ime-mode: disabled;" />月
			<input type="text" name="houjin_setsuritsu_d" id="houjin_setsuritsu_d" size="2" maxlength="2" value="{{ $v('houjin_setsuritsu_d') }}" class="input2S" style="ime-mode: disabled;" />日
			@error('houjin_setsuritsu')<p class="m0">{{ $message }}</p>@enderror
		</td>

		<th><label for="houjin_shihon">資本金</label></th>
		<td{!! $ng('houjin_shihon') !!}><input type="text" name="houjin_shihon" id="houjin_shihon" class="inputS" value="{{ $v('houjin_shihon') }}" style="ime-mode: disabled;" />円
		@include('admin.clientes.partials.tooltip', ['n' => 0])
		@error('houjin_shihon')<p class="m0">{{ $message }}</p>@enderror
		</td>
	</tr>
	<tr>
		<th><label for="daihyousha_name_kana"><small>代表者 名前(カナ)</small></label></th>
		<td colspan="3"{!! $ng('daihyousha_name_kana') !!}><input type="text" name="daihyousha_name_kana" id="daihyousha_name_kana" class="inputL inputSmall" value="{{ $v('daihyousha_name_kana') }}" />
		@include('admin.clientes.partials.tooltip', ['n' => 4])
		@error('daihyousha_name_kana')<p class="m0">{{ $message }}</p>@enderror
		</td>
	</tr>
	<tr>
		<th><label for="daihyousha_name">代表者 名前</label></th>
		<td colspan="3"><input type="text" name="daihyousha_name" id="daihyousha_name" class="inputL" value="{{ $v('daihyousha_name') }}" />
		@include('admin.clientes.partials.tooltip', ['n' => 3])
		</td>
	</tr>
	<tr>
		<th><label for="daihyousha_birth">代表者 生年月日</label></th>
		<td{!! $ng('daihyousha_birth') !!}>
			西暦<input type="text" name="daihyousha_birth_y" id="daihyousha_birth_y" size="4" maxlength="4" value="{{ $v('daihyousha_birth_y') }}" class="inputS" style="ime-mode: disabled;" />年
			<input type="text" name="daihyousha_birth_m" id="daihyousha_birth_m" size="2" maxlength="2" value="{{ $v('daihyousha_birth_m') }}" class="input2S" style="ime-mode: disabled;" />月
			<input type="text" name="daihyousha_birth_d" id="daihyousha_birth_d" size="2" maxlength="2" value="{{ $v('daihyousha_birth_d') }}" class="input2S" style="ime-mode: disabled;" />日
			@error('daihyousha_birth')<p class="m0">{{ $message }}</p>@enderror
		</td>
		<th>代表者 性別</th>
		<td>
			<ul class="textBox">
				<li><input type="radio" name="daihyousha_sex" id="daihyousha_sex1" class="checkBtn" value="男性" @if ($v('daihyousha_sex') == '男性')checked="checked"@endif /><label for="daihyousha_sex1">男性</label></li>
				<li><input type="radio" name="daihyousha_sex" id="daihyousha_sex2" class="checkBtn" value="女性" @if ($v('daihyousha_sex') == '女性')checked="checked"@endif /><label for="kojin_sex2">女性</label></li>
			</ul>
		</td>
	</tr>
	<tr>
		<th>業種</th>
		<td colspan="3"><select name="houjin_gyoushu" id="houjin_gyoushu" class="selectBox">
			<option value="">▼業種選択</option>
@foreach ($gyoushuList as $gyoushu)
<option value="{{ $gyoushu }}" @selected($v('houjin_gyoushu') == $gyoushu)>{{ $gyoushu }}</option>
@endforeach
		</select></td>
	</tr>
	<tr>
		<th><label for="houjin_busho">部署</label></th>
		<td colspan="3"><input type="text" name="houjin_busho" id="houjin_busho" class="inputD" size="60" value="{{ $v('houjin_busho') }}" /></td>
	</tr>
	<tr>
		<th><label for="tantou_name_kana"><small>担当者 名前(カナ)</small></label></th>
		<td colspan="3"{!! $ng('tantou_name_kana') !!}><input type="text" name="tantou_name_kana" id="tantou_name_kana" class="inputD inputSmall" size="40" value="{{ $v('tantou_name_kana') }}" />
		@include('admin.clientes.partials.tooltip', ['n' => 4])
		@error('tantou_name_kana')<p class="m0">{{ $message }}</p>@enderror
		</td>
	</tr>
	<tr>
		<th><label for="tantou_name">担当者 名前</label></th>
		<td colspan="3"><input type="text" name="tantou_name" id="tantou_name" class="inputD" size="40" value="{{ $v('tantou_name') }}" />
		@include('admin.clientes.partials.tooltip', ['n' => 3])
		</td>
	</tr>
	<tr>
		<th><label for="tantou_tel">担当者 電話番号</label></th>
		<td{!! $ng('tantou_tel') !!}><input type="text" name="tantou_tel" id="tantou_tel" class="inputD" value="{{ $v('tantou_tel') }}" style="ime-mode: disabled;" />
		@include('admin.clientes.partials.tooltip', ['n' => 0])
		@error('tantou_tel')<p class="m0">{{ $message }}</p>@enderror
		</td>

		<th><label for="tantou_fax">担当者 ファックス番号</label></th>
		<td{!! $ng('tantou_fax') !!}><input type="text" name="tantou_fax" id="tantou_fax" class="inputD" value="{{ $v('tantou_fax') }}" style="ime-mode: disabled;" />
		@include('admin.clientes.partials.tooltip', ['n' => 0])
		@error('tantou_fax')<p class="m0">{{ $message }}</p>@enderror
		</td>
	</tr>
		<tr>
		<th><label for="tantou_mail">担当者 メールアドレス</label></th>
		<td colspan="3"{!! $ng('tantou_mail') !!}><input type="text" name="tantou_mail" id="tantou_mail" class="inputD" size="40" value="{{ $v('tantou_mail') }}" style="ime-mode: disabled;" />@error('tantou_mail')<p class="m0">{{ $message }}</p>@enderror</td>
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
		<th><label for="mailmaga_flg1">メルマガ配信</label></th>
		<td>
			<ul class="textBox">
			<li><input type="radio" name="mailmaga_flg" id="mailmaga_flg1" class="checkBtn" value="1"@if ($v('mailmaga_flg') == '' || $v('mailmaga_flg') == '1') checked="checked"@endif /><label for="mailmaga_flg1">配信する</label></li>
			<li><input type="radio" name="mailmaga_flg" id="mailmaga_flg2" class="checkBtn" value="0"@if ((string) $v('mailmaga_flg') === '0') checked="checked"@endif /><label for="mailmaga_flg2">配信しない</label></li>
			<li><input type="radio" name="mailmaga_flg" id="mailmaga_flg3" class="checkBtn" value="9"@if ($v('mailmaga_flg') == '9') checked="checked"@endif /><label for="mailmaga_flg3">不達配信</label></li>
			</ul>
		</td>

		<th>アドレス区分</th>
		<td>
			<select name="mailmaga_email_type" id="mailmaga_email_type">
			<option value="{{ $v('mailmaga_email_type') }}" selected="selected">{{ $v('mailmaga_email_type') }}</option>
			<option value="パソコン">パソコン</option>
			<option value="DoCoMo">DoCoMo</option>
			<option value="AU">AU</option>
			<option value="Softbank">Softbank</option>
			<option value="その他">その他</option>
			</select>
		</td>
	</tr>

@if ($reminderEnabled)
	<tr>
		<th><label for="stop_yoyaku_reminder_mail_flg">予約リマインダーメール</label></th>
		<td colspan="3">
			<li><input type="radio" name="stop_yoyaku_reminder_mail_flg" id="stop_yoyaku_reminder_mail_flg1" class="checkBtn" value=""@if ($v('stop_yoyaku_reminder_mail_flg') == '') checked="checked"@endif /><label for="stop_yoyaku_reminder_mail_flg1">配信する</label></li>
			<li><input type="radio" name="stop_yoyaku_reminder_mail_flg" id="stop_yoyaku_reminder_mail_flg2" class="checkBtn" value="1"@if ($v('stop_yoyaku_reminder_mail_flg') == '1') checked="checked"@endif /><label for="stop_yoyaku_reminder_mail_flg2">配信しない</label></li>
		</td>
	</tr>
@endif

	<tr>
		<th>顧客グループ</th>
		<td>
			<select name="tb_customer_group_cd" id="tb_customer_group_cd">
			<option value="">&nbsp;</option>
@foreach ($customerGroups as $group)
<option value="{{ $group->cd }}" @selected((string) $v('tb_customer_group_cd') === (string) $group->cd)>{{ $group->name }}</option>
@endforeach
			</select>
		</td>

		<th><label for="tb_raiten_douki_master_cd">新規来店動機</label></th>
		<td>
			<select name="tb_raiten_douki_master_cd" id="tb_raiten_douki_master_cd">
				<option value="">&nbsp;</option>
@foreach ($raitenDoukis as $douki)
<option value="{{ $douki->cd }}" @selected((string) $v('tb_raiten_douki_master_cd') === (string) $douki->cd)>{{ $douki->name }}</option>
@endforeach
			</select>
		</td>
	</tr>

	<tr>
		<th><label for="bikou">備考</label></th>
		<td colspan="3"><textarea name="bikou" id="bikou" rows="4" cols="40" class="writeArea">{{ $v('bikou') }}</textarea></td>
	</tr>

</table>
</fieldset>



<!--個人用入力table end-->


@include('admin.clientes.partials.custom_fields', ['categories' => $customCategories, 'values' => $customValuesResolved, 'mode' => 'input', 'menuItems' => $menuItems ?? []])


<fieldset>
<legend>ログイン</legend>
<table class="tableBox">
	<colgroup>
		<col style='width:7em;'>
		<col style='width:auto;'>
		<col style='width:7em;'>
		<col style='width:auto;'>
	</colgroup>
	<tr>
	<th>ID</th>
	<td>
	@can('kengen', [1, 1]){{ $v('id') }}@else**********@endcan
	<input type="hidden" name="login_id" id="login_id" class="inputM" value="{{ $v('id') }}" />
	</td>
	</tr>

	<tr>
	<th><label for="bikou">パスワード<em>※</em></label></th>
	<td{!! $ng('login_pass') !!}>
	@include('admin.clientes.partials.kaiin_field', ['kengen' => 2, 'upType' => $koumokuUpFlg, 'type' => 'text', 'name' => 'login_pass', 'value' => $v('login_pass'), 'class' => 'inputL', 'style' => 'ime-mode: disabled;'])
	@error('login_pass')<p class="m0">{{ $message }}</p>@enderror
	</td>
	</tr>
</table>
</fieldset>






<div class="btnBox">

<input type="hidden" name="touroku_kubun" value="2">
<input type="hidden" name="customer_cd" id="customer_cd" value="{{ $customer?->cd }}">
<input type="hidden" name="kaiin_cd" id="kaiin_cd" value="{{ $customer?->cd }}">
<input type="hidden" name="act" value="{{ $act }}">
<input type="submit" name="enter" id="enter" class="btn btnStyleM" value="この内容で確認する">
</div>

</form>

<!-- / .contents --></div>
@endsection
