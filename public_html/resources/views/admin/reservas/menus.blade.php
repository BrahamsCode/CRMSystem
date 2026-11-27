{{--
    Legacy: system/category9/yoyaku/yoyaku_koumoku_new.php (予約項目管理, ventana emergente sin yoyaku_navi).
    Variables:
      $menu             ?ReservationMenu  menú que se edita (?cd=) o null en el alta
      $shopCd           ?int              tienda de la pantalla (-1 = 全店舗); null = master sin tienda elegida
      $shopName         ?string           tb_shop.kaishamei de $shopCd (null para 全店舗)
      $isMaster         bool              rank master: muestra el selector de tiendas
      $shops            Collection<Shop>  opciones de make_shop_options() (solo master)
      $categories       Collection<ReservationMenuCategory>  categorías activas por rank
      $yoyakuSetsubiFlg string            admin_master.yoyaku_setsubi_flg ('1' = 設備設定)
      $facilities       Collection<Facility>  設備 de la tienda (orden cd)
      $menuEndTime      array{0: ?string, 1: ?string}  最終受付時間 [時, 分] del menú
      $imageUrl         ?string           URL de la miniatura 1_s.jpg del menú
    La lista (ajax_yoyaku_koumoku_new.php) se carga por AJAX desde admin.reservas.menus.list / .sort.
--}}
@extends('layouts.legacy-popup')

@section('title', '予約項目管理')

@section('css')
<style type="text/css"><!--

#container {
	min-width: 450px;
}

.main1 {
	background: none;
}

h3 {
	padding: 0 5px;
}

.form {
	margin-bottom: 15px;
}

--></style>
@endsection

@section('js')
<script type="text/javascript">
$(document).ready(function() {

	$("#shop").change(function(){
		document.shop_select.submit();
	});

	//一覧をajaxでロードする処理
	statusControl("");


	//閉じるボタンの挙動
	$(".popCloseBtn").click(function(){
		window.parent.popUpWinClose("");
	});



	// ===============================================================================

	// 2012.05.15【大野】メニュー別最終受付時間の設定を追加。

	$('select[name="type_flg"]').change(menuTypeCheck);

	function menuTypeCheck(){

		if($('select[name="type_flg"]').val() == "1"){
			$("#menu_end_time_column").show();
			menuEndTimeFlgCheck();
		}else{
			$("#menu_end_time_column").hide();
		}

	}

	$('#menu_end_time_flg1,#menu_end_time_flg2').click(menuEndTimeFlgCheck);

	function menuEndTimeFlgCheck(){

		if($('input[name="menu_end_time_flg"]:checked').val() == "1"){
			$("#menu_end_time_select").show();
		}else{
			$("#menu_end_time_select").hide();
		}

	}

	menuTypeCheck();

	// ===============================================================================


	// form の送信に関して
	if ( $("#alertDialog").size() < 1 ) $("body").append('<div id="alertDialog"></div>');
	$("#alertDialog").dialog({
		autoOpen: false,
		open: function(event, ui){
			$(".ui-dialog-titlebar-close").hide();
			$(".ui-dialog").css({
				'top': '250px'
			});
		},
		resizable: false,
		draggable: false,
		closeOnEscape: false,
		modal: true,
		title: '確認',
		width: 300,
		height: 300,
		buttons: {
			'閉じる': function(){
				$(this).dialog('close');
			}
		}
	});

	if ( $("#confirmDialog").size() < 1 ) $("body").append('<div id="confirmDialog"></div>');
	$("#confirmDialog").html("この内容で登録してもよろしいですか？<br><br>書き直す場合は['キャンセル']ボタンを押して下さい");
	$("#confirmDialog").dialog({
		autoOpen: false,
		open: function(event, ui){
			$(".ui-dialog-titlebar-close").hide();
			$(".ui-dialog").css({
				'top': '250px'
			});
		},
		resizable: false,
		draggable: false,
		closeOnEscape: false,
		modal: true,
		title: '確認',
		width: 300,
		height: 300,
		buttons: {
			'登録': function(){
				$("#revise").css("visibility", "hidden");
				$(".ui-dialog-buttonpane button").hide();
				document.form1.submit();
			},
			'閉じる': function(){
				$(this).dialog('close');
			}
		}
	});

	$('form[name="form1"]').bind('submit', function(){
		var errorMess = "";
		var errorFlg = 0;

		if(document.form1.tb_shop_cd.value == ""){
			errorMess += "「店舗」";
			errorFlg = 1;
		}

		if(document.form1.name.value == ""){
			errorMess += "「名称」";
			errorFlg = 1;
		}

		if(document.form1.shoyou_time.value == ""){
			errorMess += "「所要時間」";
			errorFlg = 1;
		}

		if(errorFlg == 1){
			errorMess += "が未入力です。";
			// jAlert(errorMess);
			$("#alertDialog").html(errorMess);
			$("#alertDialog").dialog('open');
		}else{
			$("#confirmDialog").dialog('open');
		}

		return false;
	});


	// 削除について
	if ( $("#koumokuDeleteDialog").size() < 1 ) $("body").append('<div id="koumokuDeleteDialog"></div>');
	$("#koumokuDeleteDialog").html("削除してもよろしいですか？<br><br>削除しない場合は['キャンセル']ボタンを押して下さい");
	$("#koumokuDeleteDialog").dialog({
		autoOpen: false,
		open: function(event, ui){
			$(".ui-dialog-titlebar-close").hide();
		},
		resizable: false,
		draggable: false,
		closeOnEscape: false,
		modal: true,
		title: '予約項目削除',
		width: 300,
		height: 300
	});

});



function submitChk2 (val) {
	var element = eval("document.form_" + val);

	$("#koumokuDeleteDialog").dialog(
		"option",
		"buttons",
		{
			'登録': function(){
				$("#revise").css("visibility", "hidden");
				$(".ui-dialog-buttonpane button").hide();
				element.submit();
			},
			'閉じる': function(){
				$(this).dialog('close');
			}
		}
	);
	$("#koumokuDeleteDialog").dialog('open');

}



// Legacy: .load("ajax_yoyaku_koumoku_new.php"). La lista sale de admin.reservas.menus.list y el ▲▼ de admin.reservas.menus.sort.
function statusControl(shop_cd, type, target_cd, vl){

	var url = (type == "rankup" || type == "rankdown") ? @json(route('admin.reservas.menus.sort')) : @json(route('admin.reservas.menus.list'));

	$(".tableDisp").load(url,{
		shop_cd:@json((string) ($shopCd ?? '')),
		send_type:type,
		send_targetCd:target_cd,
		send_vl:vl
	});
}


</script>

<script type="text/javascript" src="{{ asset('legacy/system/js/ajaxupload-3.5.js') }}"></script>
@endsection

@section('content')
<div class="contents">

<div style="text-align:right; float: right;">
<a href="{{ route('admin.reservas.menu-categories.index') }}">予約項目カテゴリ設定</a>
</div>


@if ($isMaster)
<form action="{{ route('admin.reservas.menus.index') }}" method="get" name="shop_select">
<select name="shop" id="shop">
<option value="">▼店舗選択</option>
<option value="-1" @selected((string) $shopCd === '-1')>全店舗</option>
@foreach ($shops as $shop)
<option value="{{ $shop->cd }}" @selected((string) $shopCd === (string) $shop->cd)>{{ $shop->kaishamei }}</option>
@endforeach
</select>
</form>
<div>※登録・閲覧したい店舗を選択してください。</div>

@endif

@if ($shopCd)

<p class="h2Title">■{{ $shopCd === -1 ? '全店舗用' : $shopName }}</p>

@if ($errors->any())
<div class="attention">@foreach ($errors->all() as $error){{ $error }}<br />@endforeach</div>
@endif

<form method="post" name="form1" id="form1" class="form" action="{{ $menu ? route('admin.reservas.menus.update', $menu->cd) : route('admin.reservas.menus.store') }}" enctype="multipart/form-data">
@csrf
@if ($menu) @method('PUT') @endif


<fieldset>
<legend>予約項目登録情報</legend>

<table class="tableBox" >

	<tr>
	<th style="width:20%;">カテゴリ</th>
	<td>
	<select name="tb_yoyaku_koumoku_category_cd">
	<option value="">&nbsp;</option>
@foreach ($categories as $category)
<option value="{{ $category->cd }}"@if ((string) old('tb_yoyaku_koumoku_category_cd', $menu?->tb_yoyaku_koumoku_category_cd) === (string) $category->cd) selected="selected"@endif>{{ $category->name }}</option>@endforeach
	</select>
	</td>
	</tr>

	<tr>
	<th style="width:20%;">登録タイプ</th>
	<td>
	<select name="type_flg">
	<option value="1"@if ((string) old('type_flg', $menu?->type_flg) === '1') selected="selected"@endif>ベースメニュー</option>
	<option value="2"@if ((string) old('type_flg', $menu?->type_flg) === '2') selected="selected"@endif>オプションメニュー</option>
	</select>
	</td>
	</tr>

	<tr>
	<th style="width:20%;">名称<span class="attention">※</span></th>
	<td><input type="text" name="name" class="inputL" value="{!! old('name') !== null ? e(old('name')) : $menu?->name !!}" /></td>
	</tr>

	<tr>
	<th style="width:20%;">内容</th>
	<td><textarea name="naiyou" id="naiyou" class="writeArea" rows="6">{!! old('naiyou') !== null ? e(old('naiyou')) : $menu?->naiyou !!}</textarea>
	</td>
	</tr>

	<tr>
	<th style="width:20%;">所要時間<span class="attention">※</span></th>
	<td><input type="text" name="shoyou_time" class="inputS" value="{{ old('shoyou_time', $menu?->shoyou_time) }}" style="ime-mode:disabled;" />分<br />
	※半角数字で入力してください。</td>
	</tr>

	{{-- 2012.05.15 予約設備 (admin_master.yoyaku_setsubi_flg = '1' y una tienda concreta) --}}
	@if ($yoyakuSetsubiFlg === '1' && $shopCd > 0)
	<tr class="hideSection">
		<th>設備設定</th>
		<td>
			<select name="tb_setsubi_cd" id="tb_setsubi_cd">
			<option value="">&nbsp;</option>
@foreach ($facilities as $facility)
<option value="{{ $facility->cd }}"@if ((string) old('tb_setsubi_cd', $menu?->tb_setsubi_cd) === (string) $facility->cd) selected="selected"@endif>{{ $facility->name }}</option>
@endforeach
			</select>
		</td>
	</tr>
	@endif

	{{-- 2012.05.15 メニュー別最終受付時間 --}}
	@php
		[$menuEndH, $menuEndM] = $menuEndTime;
		$menuEndH = old('menu_end_time1', $menuEndH);
		$menuEndM = old('menu_end_time2', $menuEndM);
		$menuEndTimeFlg = (string) old('menu_end_time_flg', $menu?->menu_end_time_flg);
	@endphp
	<tr id="menu_end_time_column">
	<th style="width:20%;">最終受付時間</th>
	<td>

		<div class="pB10">
		<label for="menu_end_time_flg1">
		<input type="radio" name="menu_end_time_flg" id="menu_end_time_flg1" class="checkBtn"@if ($menuEndTimeFlg !== '1') checked="checked"@endif value="" />
		このメニューの最終受付時間を設定しない</label><br />
		<label for="menu_end_time_flg2">
		<input type="radio" name="menu_end_time_flg" id="menu_end_time_flg2" class="checkBtn"@if ($menuEndTimeFlg === '1') checked="checked"@endif value="1" />
		このメニューの最終受付時間を設定する</label>
		</div>

		<div id="menu_end_time_select" class="pB10">
		<select name="menu_end_time1" id="menu_end_time1">
		<option value="">&nbsp;</option>
		@for ($i = 0; $i < 30; $i++)
			@php $dispI = sprintf('%02d', $i); @endphp
			<option value="{{ $dispI }}"@if ((string) $menuEndH === $dispI) selected="selected"@endif>{{ $dispI }}</option>
		@endfor
		</select>時

		<select name="menu_end_time2" id="menu_end_time2">
		<option value="">&nbsp;</option>
		@foreach (['00', '30'] as $dispI)
			<option value="{{ $dispI }}"@if ((string) $menuEndM === $dispI) selected="selected"@endif>{{ $dispI }}</option>
		@endforeach
		</select>分
		</div>

		※最終受付時間を設定できるのはベースメニューのみです。<br />
		※最終受付時間を設定したメニューが複数選択された場合、<br />
		&emsp;一番締切時間が早いものが適用されます。

	</td>
	</tr>

	<tr id="menu_end_time_column">
		<th style="width:20%;">画像</th>
		<td>
			@if ($imageUrl)
				<img src="{{ $imageUrl }}">
				<label>
					<input type="checkbox" name="img_del_flg" value="1"> 画像を削除
				</label>
				<br><br>
			@endif
			<input type="file" name="img">
		</td>
	</tr>

</table>

</fieldset>



<div class="btnBox">
<input type="hidden" name="tb_shop_cd" value="{{ $shopCd }}" />
@if ($menu)
<input type="submit" name="enter" id="revise" value="この内容で修正する" />
<input type="hidden" name="act" id="act" value="up" />
<input type="hidden" name="cd" id="cd" value="{{ $menu->cd }}" />
@else
<input type="submit" name="enter" id="reivse" value="この内容で登録する" />
<input type="hidden" name="act" id="act" value="new" />
@endif
</div>

</form>



<!-- / .contents --></div>

<div class="contents">

<div class="tableDisp"></div>


@endif
<!-- / .contents --></div>
@endsection
