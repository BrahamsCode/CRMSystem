{{--
    放映プログラム 新規登録 / 修正 (legacy: movie_program_new.php y movie_program_update.php → movie_program_frm.php).
    El legacy no tiene confirmación: form1 envía, valida y pasa a «登録中です。» (partials/processing).
    form2 es el buscador de 選択可能ファイル: vuelve a pintar esta misma pantalla (create/edit por POST).

    Contrato de datos:
      $program             SignageProgram|null  null (o sin exists) = alta; en edición, la fila de tb_c7_movie_program:
                             cd, name, telop_comment, vertical_setting ('landscape'|'portrait'), disp_conf ('1'|'2'),
                             act_flg, feli_act_flg, feli_tb_mycoupon_master_cds, mypage_act_flg,
                             mypage_tb_mycoupon_master_cds, tb_dispaly_systems_cds, comment,
                             couponCds        array<int,string>  tb_mycoupon_master_cds partido por '<>' (POSICIONAL:
                                                                 el índice i corresponde a la casilla i de $coupons)
                             systemCouponCds  array<int,string>  system_tb_mycoupon_master_cds partido por '<>' (posicional)
      $nonCouponFlg        string|null  non_coupon_flg del sistema elegido (oculta «出力クーポン選択» si es '1').
                                        Ojo: la subconsulta del legacy (movie_program_update.php) está invertida y
                                        siempre devuelve '' cuando hay sistema; el controlador decide.
      $coupons             Collection<TbMycouponMaster>  coupon_kubun = '8', disp_flg <> '9', filtrados por tienda,
                                                         ORDER BY update_date DESC (cd, title, comment)
      $systems             Collection<SignageSystem>     tb_dispaly_systems, disp_flg <> '9', ORDER BY rank (cd, name, non_coupon_flg)
      $categories          Collection<string>            categorize_value_0 distintos de tb_screensaver (buscador)
      $selectedMaterials   Collection<SignageMaterial>   放映するファイル: tb_screensaver_cds del programa
                                                         (disp_flg = '1') en el orden guardado (cd, title, comment, filetype)
      $availableMaterials  Collection<SignageMaterial>   選択可能ファイル: disp_flg = '1', no incluidos en
                                                         $selectedMaterials, filtrados por search_p (title/comment LIKE)
                                                         y search_categorize_value_0, ORDER BY cd DESC
      $siteName            string        (compartida por el núcleo) nombre del sitio para el <title>
      $errors              ViewErrorBag  name (必須項目です。), telop_comment, comment, tb_c7_movie_program_cds
                                         → class="Ng" + <p class="m0">※mensaje</p>

    Request leído directamente (como el legacy): search_p, search_categorize_value_0 (form2), page, kensu (fnc_reload).
    Old input: tras un error se reutilizan los valores enviados (el legacy perdía las casillas de cupones).

    Rutas:
      POST     admin.terminales.signage-programs.store          (form1, alta)
      POST     admin.terminales.signage-programs.update  {cd}   (form1, edición: 「…選択中のディスプレイに反映する」)
      GET|POST admin.terminales.signage-programs.create         (form2 buscador en alta; fnc_reload)
      GET|POST admin.terminales.signage-programs.edit    {cd}   (form2 buscador en edición; fnc_reload)
      GET|POST admin.terminales.signage-programs.index          (<<一覧へ戻る)
      GET|POST admin.terminales.signage-programs.show    {cd}   (<<詳細へ戻る)
      GET      admin.terminales.signage-materials.create        (popup «ファイル追加 +»)
      GET      admin.terminales.signage-materials.show   {cd}   (popup «編集する»)
    Campos de form1 (los que lee movie_program_register.php): name, telop_comment, vertical_setting, disp_conf,
      act_flg, tb_mycoupon_master_cds{i} + tb_mycoupon_master_max, feli_act_flg, feli_tb_mycoupon_master_cds,
      mypage_act_flg, mypage_tb_mycoupon_master_cds, tb_dispaly_systems_cds, non_coupon_flg,
      system_tb_mycoupon_master_cds{i} + system_tb_mycoupon_master_max, comment, hb_which (solo alta),
      tb_screensaver_cd[] (en el orden de #list_trash), cd, act.
    {!! !!} solo imprime atributos fijos escritos en la vista (class="Ng", style="display:none"), nunca datos.
--}}
@extends('layouts.legacy')

@php
    $isNew = ! (isset($program) && $program->exists);
    $mode = $isNew ? '新規登録' : '修正';
    $programCd = $isNew ? '' : $program->cd;
    $hasOld = session()->hasOldInput();
    // Valor de un campo: lo enviado si vuelve con error; si no, la columna del programa
    $field = fn (string $name) => old($name, $isNew ? '' : ($program->{$name} ?? ''));
    $dispConf = (string) $field('disp_conf');
    $couponCds = $isNew ? [] : ($program->couponCds ?? []);
    $systemCouponCds = $isNew ? [] : ($program->systemCouponCds ?? []);
    // Casilla de cupón i: el legacy compara por posición (cd == lista[i])
    $couponChecked = fn (string $prefix, array $saved, int $index, $cd): bool => $hasOld
        ? (string) old($prefix . $index) === (string) $cd
        : (string) ($saved[$index] ?? '') === (string) $cd;
    $nonCouponFlgValue = old('non_coupon_flg', $nonCouponFlg ?? '');
    $selfUrl = $isNew ? route('admin.terminales.signage-programs.create') : route('admin.terminales.signage-programs.edit', $programCd);
    // El legacy usa en los id de 選択可能ファイル el cd del ÚLTIMO elemento de 放映するファイル (variable reutilizada)
    $lastSelectedCd = $selectedMaterials->last()?->cd;
@endphp

@section('menu', 'terminales')

@section('title', '放映プログラム管理 ' . $mode . ' - ' . ($siteName ?? ''))

@section('css')
<link href="{{ asset('legacy/system/css/popupwindow.css') }}" rel="stylesheet" type="text/css" media="all" />
<style>
/* Interaction Cues
----------------------------------*/
.ui-state-highlight, .ui-widget-content .ui-state-highlight {border: 1px solid #fcefa1; background: #fbf9ee; }

</style>
@endsection

@section('js')
<script type="text/javascript" src="{{ asset('legacy/system/category7/digital_signage/js/user.js') }}"></script>



<script type="text/javascript" src="{{ asset('legacy/system/js/jquery-1.3.2.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/js/jquery-ui-1.7.2.custom.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/js/jquery-ui-dialog-compat.js') }}"></script>


{{-- ポップアップ　閉じたときにリロードする --}}
<script type="text/javascript" src="{{ asset('legacy/system/js/popupwindow-1.8_vizan.js') }}"></script>
<script>
function fnc_reload (){

	location.href="{{ $selfUrl }}?cd={{ $programCd }}&page={{ request('page') }}&kensu={{ request('kensu') }}";
}
</script>
{{-- ポップアップ　閉じたときにリロードするEND --}}


<script type="text/javascript">
$(document).ready(function() {

	$(".ac1").accordion('option', 'active', 0);





	//ディスプレイ出力　表示制御
	$("#disp_conf").change(function(){

		if($("#disp_conf").val()=='1'){
			$("#selected_program1").css("display","block");
			$("#selected_program1_2").css("display","none");
			$("#selected_program1_3").css("display","none");
		}else{
			$("#selected_program1").css("display","none");

			$("#selected_program1_2").css("display","block");
			$("#selected_program1_3").css("display","block");
		}

		if($("#disp_conf").val()=='2'){
			$("#selected_program2").css("display","block");
			$("#selected_program1_2").css("display","none");
			$("#selected_program1_3").css("display","none");

		}else{
			$("#selected_program2").css("display","none");

			$("#selected_program1_2").css("display","block");
			$("#selected_program1_3").css("display","block");
		}
	});



	//ディスプレイ出力　表示制御


	  jQuery(function($){

		   var context=$("#act_flg"),
		        //チェックボックス
		        cbox=context.find("[type=checkbox]");

		    //チェックボックスクリック時の処理
		    cbox.click(function(e){

		      var msg="";//メッセージ

		      //チェックされている要素をすべて検索し名前を記録
		      $(":checked",context).each(function (i,elem) {
		         msg += elem.name +" ";
		      })

		      //出力

			if(msg){
				$("#selected_kaiin_flg").css("display","block");
			}else{
				$("#selected_kaiin_flg").css("display","none");
			}



		    });

	});



});


	$(function() {
		$("#list_trash").sortable({
			connectWith: '.ui-widget-content',
			 placeholder: 'ui-state-highlight',
			forcePlaceholderSize: true
		}).disableSelection();
	});





</script>

<script type="text/javascript" src="{{ asset('legacy/system/category7/digital_signage/js/jquery-ui-1.8rc3.custom.min.js') }}"></script>
<script src="{{ asset('legacy/system/category7/digital_signage/js/drag_io.js') }}" type="text/javascript"></script>
@endsection

@section('content')
@include('admin.terminales.partials.sub-navi', ['family' => 'programs'])
<div class="contents">
<p class="h2Title">■放映プログラム管理 {{ $mode }}</p>

<div>



<form method="post" name="form1" id="form1" class="form" action="{{ $isNew ? route('admin.terminales.signage-programs.store') : route('admin.terminales.signage-programs.update', $programCd) }}" enctype="multipart/form-data">
@csrf

<fieldset>
<legend>放映プログラム管理</legend>

<table class="tableBox">




	<tr>
		<th><label for="title">放映プログラム管理名<em>※</em></label></th>
		<td{!! $errors->has('name') ? ' class="Ng"' : '' !!}>
		<input type="text" name="name" id="name" maxlength="100" class="writeArea" value="{{ $field('name') }}" />
		@error('name')<p class="m0">※{{ $message }}</p>@enderror</td>
	</tr>

	<tr>
		<th><label for="title">テロップ<em>※</em></label></th>
		<td{!! $errors->has('telop_comment') ? ' class="Ng"' : '' !!}>
		<textarea name="telop_comment" id="telop_comment" class="writeArea" />{{ $field('telop_comment') }}</textarea>
		@error('telop_comment')<p class="m0">※{{ $message }}</p>@enderror</td>
	</tr>

	<tr>
		<th><label for="vertical_setting">出力セッティング</label></th>
		<td{!! $errors->has('vertical_setting') ? ' class="Ng"' : '' !!}>

<select name="vertical_setting" id="vertical_setting">
<option value="landscape"{{ $field('vertical_setting') == 'landscape' ? ' selected' : '' }}>横長ディスプレイ表示</option>
<option value="portrait"{{ $field('vertical_setting') == 'portrait' ? ' selected' : '' }}>縦長ディスプレイ表示</option>
</select>

</td>
	</tr>


	<tr>
		<th><label for="disp_conf">システム選択</label></th>
		<td{!! $errors->has('disp_conf') ? ' class="Ng"' : '' !!}>

<select name="disp_conf" id="disp_conf">
<option value="1"{{ $dispConf == '1' ? ' selected' : '' }}>放映ファイル選択</option>
<option value="2"{{ $dispConf == '2' ? ' selected' : '' }}>システム内蔵</option>
</select>

</td>
	</tr>





	<tr>
		<th><label for="tb_c7_movie_program_cds">動作設定</label></th>
		<td{!! $errors->has('tb_c7_movie_program_cds') ? ' class="Ng"' : '' !!}>

<!--###################### ユーザ設定「放映プログラム」選択時 ########################-->
<div id="selected_program1"{!! ($dispConf != '1' && $dispConf != '') ? ' style="display:none"' : '' !!}>


「画面にタッチした時」<br />
<select name="act_flg" id="act_flg">
<option value=""{{ $field('act_flg') == '' ? ' selected' : '' }}>なし</option>
<option value="1"{{ $field('act_flg') == '1' ? ' selected' : '' }}>クーポン選択・印刷</option>
</select><br />
出力クーポン選択<br />
@foreach ($coupons as $coupon)


<input type="checkbox" name="tb_mycoupon_master_cds{{ $loop->index }}" id="tb_mycoupon_master_cds{{ $loop->index }}" value="{{ $coupon->cd }}"{{ $couponChecked('tb_mycoupon_master_cds', $couponCds, $loop->index, $coupon->cd) ? ' checked' : '' }}>
<span title="{{ $coupon->comment }}">：{{ $coupon->title }}</span><br />

@endforeach
<input type="hidden" name="tb_mycoupon_master_max" value="{{ $coupons->count() }}">

<hr />



「フェリカをかざした時」<br />
<select name="feli_act_flg" id="feli_act_flg">
<option value="1"{{ $field('feli_act_flg') == '1' ? ' selected' : '' }}>無条件</option>
<option value="2"{{ $field('feli_act_flg') == '2' ? ' selected' : '' }}>新規会員登録時</option>
<option value="3"{{ $field('feli_act_flg') == '3' ? ' selected' : '' }}>会員来店時</option>

</select>

<br />
出力クーポン選択<br />
<select name="feli_tb_mycoupon_master_cds" id="feli_tb_mycoupon_master_cds">
<option value="">なし</option>
@foreach ($coupons as $coupon)
<option value="{{ $coupon->cd }}"{{ (string) $coupon->cd === (string) $field('feli_tb_mycoupon_master_cds') ? ' selected' : '' }}>{{ $coupon->title }}</option>
@endforeach
</select>

<hr />


「会員様がマイページよりクーポン印刷」<br />
<select name="mypage_act_flg" id="act_flg">
<option value=""{{ $field('mypage_act_flg') == '' ? ' selected' : '' }}>なし</option>
<option value="1"{{ $field('mypage_act_flg') == '1' ? ' selected' : '' }}>クーポン印刷</option>
</select>

<br />
出力クーポン選択<br />
<select name="mypage_tb_mycoupon_master_cds" id="mypage_tb_mycoupon_master_cds">
<option value="">なし</option>
@foreach ($coupons as $coupon)
<option value="{{ $coupon->cd }}"{{ (string) $coupon->cd === (string) $field('mypage_tb_mycoupon_master_cds') ? ' selected' : '' }}>{{ $coupon->title }}</option>
@endforeach
</select>



</div>
<!--###################### ユーザ設定「放映プログラム」選択時　END ########################-->



<!--###################### システム内蔵「出力画面」選択時 ########################-->
<div id="selected_program2"{!! $dispConf != '2' ? ' style="display:none"' : '' !!}>
システム内蔵「出力画面」選択<br />

<select name="tb_dispaly_systems_cds" id="tb_dispaly_systems_cds">

@foreach ($systems as $system)
<option id="tb_dispaly_systems_cds{{ $system->cd }}" non_coupon_flg="{{ $system->non_coupon_flg }}" value="{{ $system->cd }}"{{ (string) $system->cd === (string) $field('tb_dispaly_systems_cds') ? ' selected' : '' }}>{{ $system->name }}</option>

@endforeach


</select><br />

<script>
$("#tb_dispaly_systems_cds").change(function(){

	var tb_dispaly_systems_cd = $("#tb_dispaly_systems_cds").val();
	if($('#tb_dispaly_systems_cds'+tb_dispaly_systems_cd).attr('non_coupon_flg')!='1'){
		$("#selectCoupon").css("display","block");

	}else{
		$("#selectCoupon").css("display","none");
	}

	//戻ってきた時用にhiddenでnon_coupon_flgを渡す
		$("#non_coupon_flg").val(tb_dispaly_systems_cd);
}
);
</script>
<input type="hidden" name="non_coupon_flg" value="{{ $nonCouponFlgValue }}">
{{-- 実際のディスプレイ出力はその都度で設定書いているかと思います。ここはフォームの制御の為にnon_coupon_flgでクーポン発行しないものの非表示をしています。 --}}
<div id="selectCoupon" style="display:{{ $nonCouponFlgValue == '1' ? 'none' : 'block' }}">
出力クーポン選択<br />
@foreach ($coupons as $coupon)


<input type="checkbox" name="system_tb_mycoupon_master_cds{{ $loop->index }}" id="system_tb_mycoupon_master_cds{{ $loop->index }}" value="{{ $coupon->cd }}"{{ $couponChecked('system_tb_mycoupon_master_cds', $systemCouponCds, $loop->index, $coupon->cd) ? ' checked' : '' }}>
<span title="{{ $coupon->comment }}">：{{ $coupon->title }}</span><br />

@endforeach



<input type="hidden" name="system_tb_mycoupon_master_max" value="{{ $coupons->count() }}">
</div><!--#selectCoupon-->


</div>
<!--###################### システム内蔵「出力画面」選択時　EMD ########################-->


</td>
	</tr>





	<tr>
		<th><label for="comment">メモ</label></th>
		<td{!! $errors->has('comment') ? ' class="Ng"' : '' !!}>
		<textarea name="comment" id="comment" class="writeArea" />{{ $field('comment') }}</textarea>
		@error('comment')<p class="m0">※{{ $message }}</p>@enderror</td>
	</tr>



@if ($isNew)

	<tr>
		<th><label for="filetype">追加方法<em>※</em></label></th>
		<td>

	先頭に追加：<input type="radio" name="hb_which" value="1" checked />
	最後に追加：<input type="radio" name="hb_which" value="0" /><br />

	</tr>
@endif


</table>

</fieldset>



{{-- ファイル選択　START --}}


<!--###################### ユーザ設定「放映プログラム」選択時 ########################-->
<div id="selected_program1_2"{!! ($dispConf != '1' && $dispConf != '') ? ' style="display:none"' : '' !!}>
<fieldset>
<legend>放映ファイル選択</legend>

<p>左の選択中リストは上下にドラッグして並びを入れ替えることができます。</p>



    <div style="border: medium solid #ccc;width:47%;float:left;padding:5px;height:500px; overflow-x:scroll;" id="trash" class="ui-widget-content ui-state-default">


    <h4 class="ui-widget-header"><span>放映するファイル</span></h4>

<div id="list_trash" class="list_gallery ui-helper-reset ui-sortable"/>


@foreach ($selectedMaterials as $selectedMaterial)
      <div id="listItem-{{ $selectedMaterial->cd }}" style="margin:3px;display:block;border:1px solid #ccc ;padding:5px;" class="ui-widget-content ui-corner-tr ui-draggable">
        <h5 class="ui-widget-header">【{{ $selectedMaterial->cd }}】{{ $selectedMaterial->title }}（{{ $selectedMaterial->filetype }}）</h5>
        <!--<img src="/hp/uploads/c2/catalog/39/39.jpg" alt="Sea #1" width="96" height="72" /><br />-->
	『{{ $selectedMaterial->comment }}』<br />

	<a href="#" title="編集・削除します" onclick="new $pop('{{ route('admin.terminales.signage-materials.show', $selectedMaterial->cd) }}', {type: 'iframe', title: 'データ編集',width: 900, height: 420, windowmode:true, resize: true, modal: true,effect: 'normal' } ); return false;">編集する</a>

	<a class="ui-icon ui-icon-refresh" title="右のリストへ戻します" href="#nogo">選択解除</a>


	<input type="hidden" name="tb_screensaver_cd[]" value="{{ $selectedMaterial->cd }}" />
      </div>



@endforeach

</div>


    </div>



</fieldset>
</div>
<!--###################### ユーザ設定「放映プログラム」選択時　END ########################-->

{{-- 親側の必要情報　本来のsubmitの位置 --}}

<input type="hidden" name="cd" id="cd" value="{{ $programCd }}" />
<input type="hidden" name="act" id="act" value="up" />


</form>


<div class="btnBox">{{-- 選択用フォームをむりやりフィールドセットに入れるためにposition:relative;を使っているので空白があくのを避けるため仕方なくここに。。。 --}}

<form method="post" name="formback_list" id="formback_list" class="form" action="{{ route('admin.terminales.signage-programs.index') }}">
@csrf
<input type="submit" name="enter" id="enter" class="btn" value="&lt;&lt;一覧へ戻る" />
</form>


{{-- En alta el legacy envía a movie_program_detail.php sin cd, que redirige fuera; aquí va al listado --}}
<form method="post" name="formback" id="formback" class="form" action="{{ $isNew ? route('admin.terminales.signage-programs.index') : route('admin.terminales.signage-programs.show', $programCd) }}">
@csrf
<input type="hidden" name="cd" id="kensu" value="{{ $programCd }}" />
<input type="submit" name="enter" id="enter" class="btn" value="&lt;&lt;詳細へ戻る" />
</form>



@if (! $isNew)
<input type="button" name="send" id="send" onclick="document.form1.submit()" class="btn" value="この内容を登録し、選択中のディスプレイに反映する" />
@else
<input type="button" name="send" id="send" onclick="document.form1.submit()" class="btn" value="この内容で登録する" />
@endif

</div>

<form method="post" name="form2" action="{{ $selfUrl }}" style="position:relative; top:-586px; left:50%">{{-- 上とは別フォームで囲わないと上のフォームにデータがいってしまう --}}
@csrf

{{-- 選択一覧 --}}
<!--###################### ユーザ設定「放映プログラム」選択時 ########################-->
<div id="selected_program1_3"{!! ($dispConf != '1' && $dispConf != '') ? ' style="display:none"' : '' !!}>
  <div style="border: medium solid #ccc;width:45%;float:left;margin-left:10px;padding:5px;height:500px; overflow-x:scroll;">



    <h4 class="ui-widget-header">

<span class="ui-icon ui-icon-trash">選択可能ファイル</span>
<span style="float:right;"><a href="#" onclick="new $pop('{{ route('admin.terminales.signage-materials.create') }}', {type: 'iframe', title: 'データ作成',width: 900, height: 550, windowmode:true, resize: true , modal: true,effect: 'normal'} ); return false;">ファイル追加 +</a></span></h4>




    <h4 class="ui-widget-header">

カテゴリ：<select name="search_categorize_value_0" id="search_categorize_value_0" />

<option value="">選択して下さい</option>
@foreach ($categories as $category)
<option value="{{ $category }}"{{ request('search_categorize_value_0') == $category ? ' selected' : '' }}>{{ $category }}</option>
@endforeach
		</select>
キーワード：<input type="text" name="search_p" value="{{ request('search_p') }}">
<input type="submit" value="検索">

</h4>




    <div id="list_gallery" class="list_gallery ui-helper-reset ui-helper-clearfix">

@foreach ($availableMaterials as $availableMaterial)
      <div id="listItem-{{ $lastSelectedCd }}" style="margin:3px;display:block;border:1px solid #ccc ;padding:5px;" class="ui-widget-content ui-corner-tr">
        <h5 class="ui-widget-header">【{{ $availableMaterial->cd }}】{{ $availableMaterial->title }}（{{ $availableMaterial->filetype }}）</h5>
        <!--<img src="/hp/uploads/c2/catalog/39/39.jpg" alt="Sea #1" width="96" height="72" /> <br />-->
	『{{ $availableMaterial->comment }}』<br />

	<a href="#" title="編集・削除します" onclick="new $pop('{{ route('admin.terminales.signage-materials.show', $availableMaterial->cd) }}', {type: 'iframe', title: 'データ編集',width: 900, height: 550, windowmode:true, resize: true, modal: true,effect: 'normal' } ); return false;">編集する</a>


        <a href="#nogo" title="左のリストに入れます" class="ui-icon ui-icon-trash">選択する</a>



	<input type="hidden" name="tb_screensaver_cd[]" value="{{ $availableMaterial->cd }}">
      </div>

@endforeach
    </div>

  </div>
</div>
<!--###################### ユーザ設定「放映プログラム」選択時　END ########################-->


{{-- ファイル選択　END --}}
{{-- 親側の必要情報　本来のsubmitの位置 --}}
<input type="hidden" name="cd" id="cd" value="{{ $programCd }}" />
<input type="hidden" name="act" id="act" value="search" />
</form>







<!-- / .form --></div>

<!-- / .contents --></div>
@endsection
