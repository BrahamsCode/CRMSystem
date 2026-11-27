{{-- Legacy: system/category1/customer/customer_list.php (顧客一覧・検索).
     Variables:
       $siteName                  nombre del sitio (para <title>)
       $addScripts                JS de personalización que el núcleo genera desde inc/head.php ($add_scripts); null si no hay
       $searched                  bool: hay búsqueda ejecutada (se_flg = 1 o act = up)
       $searchUniqid              id del buscador en sesión (multi-ventana)
       $kensu, $page              表示件数 (10…50) y página actual
       $filters                   array con los valores del buscador actual (incluye coupon_cd)
       $searchQuery               array de filtros actuales para la URL de los CSV
       $itemMaxNo                 número de項目 de tb_koumoku del buscador (item_max_no)
       $total                     clientes encontrados ($kaiin_sum_max)
       $carrierCounts             ['pc' => n, 'docomo' => n, 'au' => n, 'softbank' => n, 'unknown' => n] (mailmaga_email_type)
       $customers                 clientes de la página (tb_customer, ORDER BY cd DESC): 'cd', 'id', 'kanri_id', 'name', 'felica_no', 'raiten_count'
                                  (raiten_count: tb_raiten_rireki con disp_flg <> '9', solo la tienda de la sesión si no es master)
       $showRaitenColumns         bool: tb_base_system_child '来店履歴管理' activo (= $raitenHistoryEnabled)
       $aprintCsvEnabled          bool: tb_base_system_grandchild 'Aprint用CSV出力' activo (DM用CSV y LINE配信)
       $lineLinkedCustomerCount   clientes con LINE (スマトピ) dentro del filtro; 0 = sin botón LINE配信
       $domainSmatopiId           id スマトピ del dominio (Smatopi::getDomainSmatopiId) o null
       $shops                     tiendas tb_shop (disp_flg <> '9', orden cd): 'cd', 'kaishamei', 'name', … (buscador y diálogo LINE)
       $isTablet                  bool: navegador tablet (botón 詳細 con roid.popUp)
       + las de partials/search_set1, search_custom_set1, search_form_field, search_rireki_set1, search_kaikei_set1, search_method_set1:
         $searchItems, $hiddenSearchMenus, $cellTag, $isMaster, $shopCd, $shopName, $areaCheckUi, $jobs, $customerGroups,
         $raitenDoukis, $terminalNos, $staffs, $raitenRanks, $kingakuRanks, $nameInputModeFlg, $applicationUseFlg,
         $customSearchCategories, $yoyakuMenus, $raitenHistoryEnabled, $minYear, $kaikeiResource
--}}
@extends('layouts.legacy')

@section('menu', 'clientes')

@section('title', '顧客一覧・検索 - ' . ($siteName ?? ''))

@section('css')
<link rel="stylesheet" href="{{ asset('legacy/common/jquery-ui-1.11.4/jquery-ui.min.css') }}">
<link href="{{ asset('legacy/system/category3/css/colorbox.css') }}" rel="stylesheet" type="text/css" media="all" />
<link rel="stylesheet" type="text/css" href="{{ asset('legacy/system/plugin/ui-datepicker/ui.datepicker.css') }}" />
<link rel="stylesheet" type="text/css" href="{{ asset('legacy/system/plugin/myflexselect/myFlexSelect.css') }}" />
<style>
.leftNavi dd {
	width: 100%;
    padding: 0 !important;
    background: transparent !important;
    border: none !important;
}

.leftNavi dt {
    border: none !important;
}

.leftNavi dt .ui-icon {
    display: none !important;
}


/* ダイアログ */
.ui-widget-header {
	background: #17c1c0;
	background-color: #17c1c0;
	font-weight: bold;
	border: 1px solid #17c1c0;
	border-radius: 5px !important;
}

.ui-dialog-buttonset button {
	border-radius: 5px !important;
}

.ui-accordion .ui-accordion-icons {
    padding-left: 0.8em !important;
}
</style>
@endsection

@section('js')
<script type="text/javascript" src="{{ asset('legacy/system/category3/js/jquery.colorbox.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/plugin/ui-datepicker/ui.datepicker-ja.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/plugin/ui-datepicker/gcalendar-holidays.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/plugin/myflexselect/myFlexSelect.js') }}"></script>
<script src="{{ asset('legacy/system/category3/smatopi/js/smatopiCommon.js') }}?t=20230521140700"></script>
<script src="{{ asset('legacy/system/category3/smatopi/js/smatopiLine.js') }}"></script>
<script type="text/javascript">
$(document).ready(function() {
{{-- JS de personalización generado por el núcleo (inc/head.php $add_scripts), impreso sin escapar como en el legacy --}}
{!! $addScripts ?? '' !!}
	$(".ac1").accordion({ collapsible: true , active: false});
	$(".ac2").accordion({ collapsible: true , active: false});
	$(".ac3").accordion({ collapsible: true , active: false});
	$(".ac4").accordion({ collapsible: true , active: false});
	$(".ac5").accordion({ collapsible: true , active: false});
	$(".ac6").accordion({ collapsible: true , active: false});
	$(".ac7").accordion({ collapsible: true , active: false});
	$(".ac8").accordion({ collapsible: true , active: false});

	$(".ac1").accordion('option', 'active', 0);
});

//表示件数の制御

function kensuSet(parts){
	document.form_search.kensu.value = parts.value;
	document.form_search.submit();
}
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■顧客検索条件</p>

<form method="post" name="form_search" id="form_search" class="form" action="{{ route('admin.clientes.search') }}">
@csrf

@include('admin.clientes.partials.search_set1')

@include('admin.clientes.partials.search_custom_set1')

@include('admin.clientes.partials.search_rireki_set1')

@include('admin.clientes.partials.search_kaikei_set1')

{{-- POS会計検索 (inc/search_pos_kaikei_set1.php): POS fuera de alcance, no se incluye --}}

@include('admin.clientes.partials.search_method_set1')

<div class="btnBox">
<input type="hidden" name="item_max_no" value="{{ $itemMaxNo }}" />
<input type="hidden" name="act" value="up" id="act" />
<input type="hidden" name="kensu" id="kensu" value="{{ $kensu }}" />
<input type="hidden" name="se_flg" value="1" />
<input type="hidden" name="coupon_cd" value="{{ $filters['coupon_cd'] ?? '' }}" />
<input type="hidden" name="search_uniqid" value="{{ $searchUniqid }}" />
<input type="submit" name="enter" class="btn btnStyleM" value="この条件で検索する" />
</div>

</form>
　<a href="{{ route('admin.clientes.index') }}">検索条件クリア</a>
@if ($searched)
@php
    $exportParams = ['search_uniqid' => $searchUniqid] + ($searchQuery ?? []);
@endphp

<div class="btnBox">

<table class="tableBox7">

<tr>
<th colspan="10"><span class="text3L attention">{{ $total }}</span>件見つかりました。

{{-- この条件で配信する: pantalla de alta de メール配信 de promoción (legacy category3/mail_mag/mail_new.php) --}}
<form action="{{ route('admin.promocion.mail.create') }}" method="get">
<input type="hidden" name="item_max_no" value="{{ $itemMaxNo }}" />
<input type="hidden" name="act" value="up" />
<input type="hidden" name="search_uniqid" value="{{ $searchUniqid }}" />
@can('kengen', [1005, 2])
<input type="submit" name="enter" class="btn btnStyleS" value="この条件で配信する" style="height: 32px;" />
@endcan
</form>

@if ($total > 0)
	{{-- CSV出力の権限 --}}
	@can('kengen', [1002, 1])
<input type="button" class="btn btnStyleS" value="この条件でCSV出力する" style="height: 32px;" onclick="location.href='{{ route('admin.clientes.export', ['format' => 'standard'] + $exportParams) }}'" />

		{{-- 2013.01.09 Aprint連携用CSV出力 (DM用CSV) --}}
		@if ($aprintCsvEnabled)
<input type="button" class="btn btnStyleS" value="DM用CSV出力" style="height: 32px;" onclick="location.href='{{ route('admin.clientes.export', ['format' => 'ex02'] + $exportParams) }}'" />
			@if ($lineLinkedCustomerCount > 0)
<input type="button" id="smatopiLineBtn" class="btn btnStyleS" value="LINE配信">
			@endif
		@endif
	@endcan
@endif

</th>
</tr>

</table>


</div>
@endif


<!-- / .contents --></div>


{{-- Como el legacy, el bloque ■顧客一覧 solo se pinta tras buscar (se_flg=1 o act=up) --}}
@if ($searched)
<div class="contents">
<p id="customerSearchList" class="h2Title">■顧客一覧</p>

@if ($total > 0)
@php
    $from = max(0, ($page - 1) * $kensu);
    $dispLastKen = min($from + $kensu, $total);
@endphp
<div id="form"><p class="maxListLength">表示件数<select name="kensu_act" id="kensu_act" class="selectBox" onchange="kensuSet(this)">
@for ($i = 1; $i <= 5; $i++)
<option value="{{ $i * 10 }}" @selected($kensu == $i * 10)>{{ $i * 10 }}</option>
@endfor
</select></p></div>

<p><span class="attention">{{ $from + 1 }}件～{{ $dispLastKen }}件目/{{ $total }}件中</span>
内訳（
PC：{{ $carrierCounts['pc'] }}</span>件
DoCoMo：{{ $carrierCounts['docomo'] }}</span>件
au：{{ $carrierCounts['au'] }}</span>件
SoftBank：{{ $carrierCounts['softbank'] }}</span>件
未判別：{{ $carrierCounts['unknown'] }}</span>件
）

</p>

<div class="form">
<table class="tableBox2">
	<tr>
	<th style="width: 5em;">会員番号</th>
	<th>管理番号</th>
	<th>名前</th>
@if ($showRaitenColumns)
	<th style="width: 7em;">端末登録有無</th>
	<th style="width: 5em;">来店回数</th>
@endif
	<th style="width: 2em;">&nbsp;</th>
	</tr>
@foreach ($customers as $customer)
@if ($loop->index % 2 != 0)
  <tr class="tableBg">
@else
  <tr>
@endif
	<td>
	{{ $customer['id'] }}
	</td>
	<td>{{ $customer['kanri_id'] }}</td>

	<td>{{ $customer['name'] }}</td>

@if ($showRaitenColumns)
	<td>
@if ($customer['felica_no'])
<span style="color: #115599;">端末登録有</span><br />
@else
<span style="color: #555555;">端末登録無</span><br />
@endif
	</td>
	<td>
@if (($customer['raiten_count'] ?? 0) > 0)
{{ $customer['raiten_count'] }} 回
@endif
</td>
@endif

	<td align="center">
@can('kengen', [1001, 1])
@php
    // 2011.12.1 tablet用にボタンスクリプトを分岐
    // La ficha (C1-b) puede no estar registrada todavía: entonces la URL del legacy
    $shousaiUrl = Route::has('admin.clientes.show')
        ? route('admin.clientes.show', [$customer['cd'], 'wintype' => 'no_headers'])
        : url('system/category1/inc/customer_detail.php?kaiin_cd=' . $customer['cd'] . '&wintype=no_headers');
@endphp
@if ($isTablet)
<input class="btn btnStyle" type="button" value="詳細" onclick="roid.popUp('{{ $shousaiUrl }}')" />
@else
<input class="btn btnStyle" type="button" value="詳細" onclick="javascript:window.open('{{ $shousaiUrl }}', 'mywindow1', 'width=970, height=550, menubar=no, toolbar=no, scrollbars=yes');" />
@endif
@endcan
	</td>
  </tr>
@endforeach
</table>

</div>

@else
該当する顧客がみつかりません。
@endif



<ul class="btnBox2">
@include('admin.clientes.partials.page_control', ['maxPage' => $kensu ? (int) ceil($total / $kensu) : 0])
</ul>

<!-- / .contents --></div>
@endif

{{-- Fuera de #container en el legacy: diálogo LINE配信 (lo maneja smatopiLine.js; envía a /system/category3/smatopi/ajax_smatopiLine.php) --}}
<div id="smatopiLineDialog" style="display: none;padding:20px;">
	<form id="smatopiLineForm">
		@csrf {{-- smatopiLine.js envía el formulario completo (FormData): así lleva el token --}}
		<input type="hidden" id="domainSmatopiId" name="domainSmatopiId" value="{{ $domainSmatopiId ?? '' }}" />
		<p style="margin-bottom:20px;">
			<span>店舗:&emsp;</span>
			<select name="shopCd" id="smatopiLineShopCd" style="width:auto;">
				<option value="">選択してください。</option>
			@foreach ($shops as $shop)
				<option value="{{ $shop['cd'] }}">{{ ($shop['name'] ?? '') . ' - ' . ($domainSmatopiId ?? '') . sprintf('%02d', $shop['cd']) }}</option>
			@endforeach
			</select>
		</p>
		<section id="smatopiLineInputSection" style="display: none;">
			<table class="tableBox" style="border:1px solid #cccccc;">
				<tr>
					<th>タイトル</th>
					<td>
						<input type="text" name="message_title" value="" style="width: 80%;" placeholder="入力してください">
						<span style="color:#ee0000;display: block;margin-top: 4px;">※ユーザーには表示されません</span>
					</td>
				</tr>

				<tr>
					<th>メッセージ</th>
					<td>
						<textarea name="text" style="width:80%;height:100px;resize:none;" placeholder="入力してください"></textarea>
					</td>
				</tr>

				<tr>
					<th>画像</th>
					<td>
						<input type="file" name="image" id="image" />
					</td>
				</tr>

				<tr>
					<th>動画</th>
					<td>
						<input type="file" name="movie" id="movie" />
					</td>
				</tr>

				<tr>
					<th>動画サムネイル</th>
					<td>
						<input type="file" name="thumbnail" id="thumbnail" />
					</td>
				</tr>

				<tr>
					<th>クーポン</th>
					<td>
						<select id="couponId" name="coupon_id" style="width:auto;"></select>
					</td>
				</tr>
			</table>
		</section>
		<input type="hidden" name="token" value="{{ $searchUniqid }}" />
		<input type="hidden" name="act" value="sendMessage" />
	</form>
</div>
@endsection
