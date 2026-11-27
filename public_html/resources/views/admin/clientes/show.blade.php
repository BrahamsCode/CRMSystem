{{-- Legacy: system/category1/inc/customer_detail.php (entrada: system/category1/customer/customer_detail.php con $window_type = "ALL").
     Mecánica de pestañas igual que el legacy: send_tab(n) envía #send_tab_form y la página se recarga con ?tab=N;
     la pestaña se incluye en servidor (@include de tabs/tabN). tab vacío o inexistente → tabs/tab1 (detail_tab.php).
     Variables:
       $customer               modelo tb_customer (columnas legacy) + atributo shoukaisha_name ('【id】  name' del 紹介者)
       $tab                    string: '' | número de pestaña | 'custom' (ya validado como el legacy: /^(\d{1,2}|custom)?$/)
       $searchCateCd           string: search_cate_cd de la petición (pestaña personalizada activa)
       $relatedFeaturesConfig  array: explode('<>', admin_master.related_features) (cd = 1)
       $packages               array<string,bool>: módulos activos que muestran pestañas:
                               raiten (来店履歴管理), coupon (マイクーポン管理), album (マイアルバム管理), point (ポイント管理),
                               stamp (スタンプ管理), kounyu (購買履歴), yoyaku (tb_base_system cd 9 予約管理)
       $searchCates            colección tb_search_cate (category = 'category1', disp_flg <> 9, hyouji_flg = 1, orden rank): cd, name
       $karuteTabLabel         string: manageWord('カルテ', 'c1_customer_detail[カルテ]')
       $appLayoutMode          int: contents_master mycolle/app_layout_mode (1 → pestaña 有料会員状況)
       $popup                  bool: true = ficha abierta como ventana (legacy sin $window_type = "ALL": sin menú y con botón 閉じる)
       $isTablet               bool: tableta Android (閉じる llama a roid.windowClose())
       $shopList               array cd => kaishamei de tb_shop (disp_flg != 9), común a varias pestañas
       $isMaster               bool: el staff es master
       + las variables de cada pestaña (ver el comentario inicial de tabs/tabN).
     session('restoreError'): el 復元 no se hizo porque otro socio ya usa el correo (customer_recover_confirm.php). --}}
@extends(($popup ?? false) ? 'layouts.legacy-popup' : 'layouts.legacy')

@section('menu', 'clientes')
@section('wrapClass', 'customerDetail')
@section('title', '顧客登録情報 - ' . config('app.name'))

@section('css')
@if (str_contains(request()->userAgent() ?? '', 'Mac OS') && str_contains(request()->userAgent() ?? '', 'Safari'))
{{-- IOS11対応 --}}
<style>
#popup_container {
	position: absolute !important;
}
</style>
@endif
{{-- 2011.10.24 ohno 入力補助プラグイン追加 --}}
<link rel="stylesheet" type="text/css" href="{{ asset('legacy/system/plugin/myflexselect/myFlexSelect.css') }}" />
@endsection

@section('js')
<script type="text/javascript" src="{{ asset('legacy/system/plugin/myflexselect/myFlexSelect.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/js/jquery.maskedinput-1.2.2.js') }}"></script>

<script type="text/javascript">

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

function submitChk (target) {
	var mess;
	if (typeof(target.delete_flg) != "undefined" && target.delete_flg.value == "recover") {
		mess = "復元";
	} else {
		mess = "削除";
	}


	jConfirm( mess+"してもよろしいですか？\n\n"+mess+"しない場合は['キャンセル']ボタンを押して下さい", mess, function(ret){
		if (ret) {
			target.submit();
		} else {
			return false;
		}
	});

}

function submitChk2 () {
	jConfirm( "カード関連付けを解除してもよろしいですか？\n\n解除しない場合は['キャンセル']ボタンを押して下さい", "カード関連付け削除", function(ret){
		if (ret) {
			document.felica_separate.submit();
		} else {
			return false;
		}
	});
}

{{-- ********************************** スタンプ管理用 ********************************** --}}
function stamp_submitChk () {
	if ($("#stamp_count_subtract").val() != '') {

		jConfirm( "スタンプ処理してもよろしいですか？\n\n実行しない場合は['キャンセル']ボタンを押して下さい", "スタンプ処理", function(ret){

			if (ret) {
				var stamp_count_subtract = $("#stamp_count_subtract").val();
				var stamp_kaiin_cd = $("#stamp_kaiin_cd").val();
				var shouhi_action = $("#shouhi_action").val();
				var oshirase_mail = $("#oshirase_mail:checked").val();
				$.get("/system/category1/inc/stamp_use_register.php?stamp_count_subtract="+stamp_count_subtract+"&kaiin_cd="+stamp_kaiin_cd+"&shouhi_action="+shouhi_action+"&oshirase_mail="+oshirase_mail, function(data){
					send_tab('3', '');
				});
			}

		});

	} else {
		return false;
	}
}

{{-- ********************************** 来店履歴管理用 ********************************** --}}
function raiten_submitChk () {

	jConfirm( "来店履歴処理してもよろしいですか？\n\n実行しない場合は['キャンセル']ボタンを押して下さい", "来店履歴処理", function(ret){

		if (ret) {
			var yy = $("#raiten_y").val();
			var mm = $("#raiten_m").val();
			var dd = $("#raiten_d").val();
			var hh = $("#raiten_h").val();
			var ii = $("#raiten_i").val();
			var raiten_kaiin_cd = $("#raiten_kaiin_cd").val();
			var raiten_shop_cd = $("#raiten_shop_cd").val();
			var raiten_action = $("#raiten_action").val();
			var raiten_count = $("#raiten_count").val(); // 2012.11.01【大野】来店履歴一括登録機能を追加実装
			$.get("/system/category1/inc/raiten_register.php?yy="+yy+"&mm="+mm+"&dd="+dd+"&hh="+hh+"&ii="+ii+"&raiten_shop_cd="+raiten_shop_cd+"&kaiin_cd="+raiten_kaiin_cd+"&raiten_action="+raiten_action+"&raiten_count="+raiten_count, function(data){
				send_tab('4', '');
			});
		}

	});
}

function raiten_delete(raiten_cd) {
	var flg;

	jConfirm( "来店履歴を削除してもよろしいですか？\n\n実行しない場合は['キャンセル']ボタンを押して下さい", "来店履歴削除", function(ret){

		if (ret) {
			var raiten_kaiin_cd = $("#raiten_kaiin_cd").val();
			var raiten_action = '来店履歴削除';
			$.get("/system/category1/inc/raiten_register.php?raiten_cd="+raiten_cd+"&kaiin_cd="+raiten_kaiin_cd+"&raiten_action="+raiten_action, function(data){
				send_tab('4', '');
			});
		}

	});
}

{{-- ********************************** ポイント管理用 ********************************** --}}
function point_submitChk () {
	if ( $("#point_qty").val() != '' && $("#point_action_type").val() != '' ) {

		jConfirm( "ポイント処理してもよろしいですか？\n\n実行しない場合は['キャンセル']ボタンを押して下さい", "ポイント処理", function(ret){

			if (ret) {
				var point_qty = $("#point_qty").val();
				var point_action_type = $("#point_action_type").val();
				var point_kaiin_cd = $("#point_kaiin_cd").val();
				$.get("/system/category1/inc/point_register.php?point_qty="+point_qty+"&point_action_type="+point_action_type+"&kaiin_cd="+point_kaiin_cd, function(data){
					if (data == "") return false;
					var resp = data.split("<>");
					send_tab('8', '');
				});
			}

		});

	} else {
		return false;
	}
}

{{-- ********************************** スタンプ管理用 ********************************** --}}
function stamp_submitChk2 () {
	if ( $("#stamp_qty").val() != '' && $("#stamp_action_type").val() != '' ) {

		jConfirm( "スタンプ処理してもよろしいですか？\n\n実行しない場合は['キャンセル']ボタンを押して下さい", "スタンプ処理", function(ret){

			if (ret) {
				var stamp_qty = $("#stamp_qty").val();
				var stamp_action_type = $("#stamp_action_type").val();
				var stamp_kaiin_cd = $("#stamp_kaiin_cd").val();

				var oshirase_mail_flg = ( $("#oshirase_mail").attr("checked") == true ) ? 1 : 0 ;

				$.get("/system/category1/inc/stamp_register.php?stamp_qty="+stamp_qty+"&stamp_action_type="+stamp_action_type+"&kaiin_cd="+stamp_kaiin_cd+"&oshirase_mail_flg="+oshirase_mail_flg, function(data){
					if (data == "") return false;
					var resp = data.split("<>");
					send_tab('10', '');
				});
			}

		});

	} else {
		return false;
	}
}

@if (session('restoreError'))
$(document).ready(function() {
	alert("すでに再登録されたメールアドレスが存在するため復元できません。");
});
@endif

function send_tab(tabNo, search_cate_cd) {
	document.send_tab_form.tab.value = tabNo;
	document.send_tab_form.search_cate_cd.value = search_cate_cd;
	document.send_tab_form.submit();
}

</script>
@endsection

@section('content')
@php
    // Estilo de pestaña no activa (igual que el legacy)
    $noActiveStyle = ' style="background-color: #A2AEB3"';
    $rfc = $relatedFeaturesConfig ?? [];
    // Vista de la pestaña: '' → tab1 (detail_tab.php); 'custom' → custom; N → tabN si existe, si no tab1 (como el is_file del legacy)
    $tabView = $tab === 'custom' ? 'custom' : 'tab' . ($tab === '' ? '1' : $tab);
    if (! view()->exists('admin.clientes.tabs.' . $tabView)) {
        $tabView = 'tab1';
    }
    // Botones hacia pantallas de otras tareas (edición, カルテ, álbum, registros…): '#' mientras su ruta no exista
    $pendingRoute = fn (string $name, mixed $params = []) => \Illuminate\Support\Facades\Route::has($name) ? route($name, $params) : '#';
    // Ventana emergente: se conserva al cambiar de pestaña y en los formularios
    $popupInput = ($popup ?? false) ? '<input type="hidden" name="wintype" value="no_headers" />' : '';
@endphp
<div class="contents">


<div class="form">

<div class="customerTabArea">
{{-- Barra de pestañas. Sin @can: el legacy no filtra las pestañas por kengen, solo por módulo activo y related_features. --}}
<p class="tabHeadline"{!! $tab != '' ? $noActiveStyle : '' !!}><a onclick="send_tab('',''); return false;">顧客情報</a></p>@if (($rfc[2] ?? '') == '' && ($packages['raiten'] ?? false))<p class="tabHeadline"{!! $tab != '4' ? $noActiveStyle : '' !!}><a onclick="send_tab('4',''); return false;">来店履歴</a></p>@endif
@if ($packages['coupon'] ?? false)<p class="tabHeadline"{!! $tab != '6' ? $noActiveStyle : '' !!}><a onclick="send_tab('6',''); return false;">マイクーポン</a></p>@endif
@if ($packages['album'] ?? false)<p class="tabHeadline"{!! $tab != '7' ? $noActiveStyle : '' !!}><a onclick="send_tab('7',''); return false;">マイアルバム</a></p>@endif
@if (($rfc[1] ?? '') != '1' && ($packages['point'] ?? false))<p class="tabHeadline"{!! $tab != '8' ? $noActiveStyle : '' !!}><a onclick="send_tab('8',''); return false;">ポイント</a></p>@endif
@if (($rfc[2] ?? '') != '')<p class="tabHeadline"{!! $tab != '9' ? $noActiveStyle : '' !!}><a onclick="send_tab('9',''); return false;">{{ $karuteTabLabel }}</a></p>@endif
@if (($rfc[1] ?? '') != '2' && ($packages['stamp'] ?? false))<p class="tabHeadline"{!! $tab != '10' ? $noActiveStyle : '' !!}><a onclick="send_tab('10',''); return false;">スタンプ</a></p>@endif
@foreach ($searchCates as $searchCate)
@continue(($rfc[2] ?? '') != '' && $rfc[2] == $searchCate->cd)
<p class="tabHeadline"{!! $searchCate->cd != $searchCateCd ? $noActiveStyle : '' !!}><a onclick="send_tab('custom','{{ $searchCate->cd }}'); return false;">{{ $searchCate->name }}</a></p>@endforeach
@if ($packages['kounyu'] ?? false)<p class="tabHeadline"{!! $tab != '11' ? $noActiveStyle : '' !!}><a onclick="send_tab('11',''); return false;">購買履歴</a></p>@endif
@if ($packages['yoyaku'] ?? false)<p class="tabHeadline"{!! $tab != '12' ? $noActiveStyle : '' !!}><a onclick="send_tab('12',''); return false;">予約状況</a></p>@endif
<p class="tabHeadline"{!! $tab != '13' ? $noActiveStyle : '' !!}><a onclick="send_tab('13',''); return false;">紹介実績</a></p>@if (($appLayoutMode ?? 0) === 1)<p class="tabHeadline"{!! $tab != '14' ? $noActiveStyle : '' !!}><a onclick="send_tab('14',''); return false;">有料会員状況</a></p>@endif


{{-- El legacy enviaba este formulario por POST a sí mismo; aquí GET a la ficha (?kaiin_cd=&tab=&search_cate_cd=), sin efectos secundarios --}}
<form method="get" class="displayNone" name="send_tab_form" id="send_tab_form" action="{{ route('admin.clientes.show', $customer->cd) }}">
	<input type="hidden" name="kaiin_cd" id="kaiin_cd" value="{{ $customer->cd }}" />
	<input type="hidden" name="tab" id="tab" value="" />
	<input type="hidden" name="search_cate_cd" id="search_cate_cd" value="" />
	{!! $popupInput !!}
</form>
<!-- / div.customerTabArea END --></div>

{{-- モジュールテンプレートを読み込む --}}
@include('admin.clientes.tabs.' . $tabView)

<div class="btnBox">



@can('kengen', [1001, 3])
@if ($customer->disp_flg != 9) {{-- 修正ボタン --}}
<form method="get" name="form1" id="form1" class="form" action="{{ $pendingRoute('admin.clientes.edit', $customer->cd) }}">
<input type="submit" name="enter" id="enter" class="btn btnStyleS" value="顧客情報を修正する" />
<input type="hidden" name="kaiin_cd" id="kaiin_cd" value="{{ $customer->cd }}" />
</form>
@endif
@endcan


@can('kengen', [1001, 4])
@if ($customer->disp_flg != '1') {{-- 削除・復元ボタン --}}
<form method="post" name="form2" id="form2" class="form" action="{{ route('admin.clientes.restore', $customer->cd) }}">
@csrf
{!! $popupInput !!}
<input type="button" name="delete" id="delete" class="btn btnStyleS" value="顧客情報を復元する" onclick="submitChk(document.form2);">
<input type="hidden" name="delete_flg" id="delete_flg" class="btn" value="recover">
<input type="hidden" name="customer_cd" id="customer_cd" class="btn" value="{{ $customer->cd }}">
</form>
@else
<form method="post" name="form2" id="form2" class="form" action="{{ route('admin.clientes.destroy', $customer->cd) }}">
@csrf
@method('DELETE')
{!! $popupInput !!}
<input type="button" name="delete" id="delete" class="btn btnStyleS" value="顧客情報を削除する" onclick="submitChk(document.form2);">
<input type="hidden" name="delete_flg" id="delete_flg" class="btn" value="del">
<input type="hidden" name="customer_cd" id="customer_cd" class="btn" value="{{ $customer->cd }}">
</form>
@endif
@endcan

</div>
<!-- / .form --></div>

@if ($popup ?? false)
<div class="textCenter">
<input type="button" name="close_window" class="btn btnStyle" value="閉じる" onclick="{{ ($isTablet ?? false) ? 'roid.windowClose()' : 'javascript:window.close();' }}" />
</div>
@endif

<!-- / .contents --></div>
@endsection
