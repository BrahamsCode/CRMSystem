{{--
    放映プログラム 詳細 (legacy: movie_program_detail.php).

    Contrato de datos:
      $program           SignageProgram  fila de tb_c7_movie_program:
                           cd, name, telop_comment, vertical_setting, disp_conf, act_flg, feli_act_flg,
                           feli_tb_mycoupon_master_cds, mypage_act_flg, mypage_tb_mycoupon_master_cds, comment,
                           couponCds        array<int,string>  tb_mycoupon_master_cds partido por '<>'
                           systemCouponCds  array<int,string>  system_tb_mycoupon_master_cds partido por '<>'
      $programMaterials  Collection<SignageMaterial>  放映ファイル一覧 (cd, title, comment, filetype):
                           tb_screensaver con cd en tb_screensaver_cds, disp_flg <> '9', filtrado por tienda,
                           ORDER BY rank. OJO: si tb_screensaver_cds está vacío el legacy lista TODOS los materiales.
                           Si viene vacía, el legacy no muestra ni la tabla ni 動作設定.
      $coupons           Collection<TbMycouponMaster>  coupon_kubun = '8', disp_flg <> '9', filtrados por tienda,
                           ORDER BY update_date DESC (cd, title)
      $feliCoupon        TbMycouponMaster|null  cupón de feli_tb_mycoupon_master_cds (disp_flg <> '9')
      $mypageCoupon      TbMycouponMaster|null  cupón de mypage_tb_mycoupon_master_cds (disp_flg <> '9')
      $displaySystem     SignageSystem|null     tb_dispaly_systems de tb_dispaly_systems_cds (disp_flg <> '9')
      $previewDisplayCd  int|null  tb_display.cd cuyo tb_c7_movie_program_cds = cd del programa (disp_flg <> '9');
                           null = sin enlace プレビュー
      $shopCd            int|null  tienda de la sesión (query shop_cd del reproductor)
      $siteName          string    (compartida por el núcleo) nombre del sitio para el <title>

    Request leído directamente (como el legacy): register_type (título), kensu, page, shop_cd.

    Rutas:
      GET|POST admin.terminales.signage-programs.index         (<<一覧へ戻る, POST como el legacy)
      GET|POST admin.terminales.signage-programs.edit    {cd}  (編集する, POST como el legacy)
      POST     admin.terminales.signage-programs.destroy {cd}  (削除する, tras confirm())
      Vista previa: /digitalsignage/index2.php?shop_cd=&display_cd= (URL pública del reproductor, se conserva).

    Diferencias con el legacy (tras la migración a PHP 8 varias salidas quedaron en null; aquí se restaura
    lo que el código pretendía): enlace プレビュー, títulos de cupón de フェリカ / マイページ, nombre del
    sistema y cupones de システム内蔵. Además el legacy no mostraba el cupón seleccionado en la posición 0
    (array_search devuelve 0) y condicionaba el de マイページ a feli_tb_mycoupon_master_cds: corregido.
--}}
@extends('layouts.legacy')

@php
    $couponCds = array_map('strval', $program->couponCds ?? []);
    $systemCouponCds = array_map('strval', $program->systemCouponCds ?? []);
    $isPortrait = $program->vertical_setting == 'portrait';
    $previewWidth = $isPortrait ? '550' : '900';
    $previewHeight = $isPortrait ? '900' : '550';
@endphp

@section('menu', 'terminales')

@section('title', '放映プログラム管理' . (request('register_type') == 'new' ? ' 新規登録' : ' 修正') . ' - ' . ($siteName ?? ''))

@section('css')
<link href="{{ asset('legacy/system/css/popupwindow.css') }}" rel="stylesheet" type="text/css" media="all" />
@endsection

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac1").accordion('option', 'active', 0);
});
</script>
<script type="text/javascript"><!--

function delChk () {
    var flg = confirm ( "削除するとこの番組に登録されている広告も一緒に削除されます。\n削除してもよろしいですか？\n\n削除しない場合は[キャンセル]ボタンを押して下さい");

    return flg;
}



--></script>

<script type="text/javascript" src="{{ asset('legacy/system/js/ajaxupload-3.5.js') }}"></script>
<script type="text/javascript" src="{{ asset('legacy/system/js/jquery.maskedinput-1.2.2.js') }}"></script>


<script type="text/javascript" src="{{ asset('legacy/system/js/popupwindow-1.8.js') }}"></script>
@endsection

@section('content')
@include('admin.terminales.partials.sub-navi', ['family' => 'programs'])
<div class="contents">
<p class="h2Title">■放映プログラム管理 </p>

<div class="form">

<fieldset>
<legend>放映プログラム管理</legend>

<table class="tableBox">




	<tr>
		<th>放映プログラム管理名</th>
		<td>{{ $program->name }}
@if ($previewDisplayCd ?? null)
 <a href="#" onclick="new $pop('{{ url('digitalsignage/index2.php') }}?shop_cd={{ $shopCd ?? '' }}&display_cd={{ $previewDisplayCd }}', {type: 'iframe', title: 'プレビュー',width: {{ $previewWidth }}, height: {{ $previewHeight }}, windowmode:true, resize: true , modal: true,effect: 'normal'} ); return false;">プレビュー</a>
@endif
</td>
	</tr>


	<tr>
		<th>テロップ</th>
		<td>{{ $program->telop_comment }}</td>
	</tr>


	<tr>
		<th>出力セッティング</th>
		<td>
{{ $isPortrait ? '縦長ディスプレイ表示' : '横長ディスプレイ表示' }}
	</td>
	</tr>
	<tr>
		<th>システム選択</th>
		<td>
@if ($program->disp_conf == '1')
ユーザ設定「放映プログラム」
@elseif ($program->disp_conf == '2')
システム内蔵「出力画面」
@endif
	</td>
	</tr>


@if ($program->disp_conf == '1')
@if ($programMaterials->count() > 0)
	<tr>
		<th>放映ファイル一覧</th>
		<td>

	<table class="tableBox">

	<tr>
	<th>ID</th>
	<th>タイトル</th>
	<th>内容</th>
	<th>タイプ</th>
	</tr>
@foreach ($programMaterials as $programMaterial)
	<tr>
	<td>{{ $programMaterial->cd }}</td>
	<td>{{ $programMaterial->title }}</td>
	<td>{{ $programMaterial->comment }}</td>
	<td>{{ $programMaterial->filetype }}</td>
	</tr>
@endforeach
	</table>

	</td>
	</tr>

	<tr>
		<th>動作設定</th>
		<td>


■「画面にタッチした時」⇒
{{ $program->act_flg == '' ? '動作なし' : '' }}
{{ $program->act_flg == '1' ? 'クーポン選択・印刷' : '' }}
<br />
出力クーポン⇒
@foreach ($coupons as $coupon)
@if (in_array((string) $coupon->cd, $couponCds, true))
{{ $coupon->title }}
@endif
@endforeach
<br />

■「フェリカをかざした時」<br />

@if ($program->feli_act_flg == '1')動作⇒無条件<br />@endif
@if ($program->feli_act_flg == '2')動作⇒新規会員登録時<br />@endif
@if ($program->feli_act_flg == '3')動作⇒会員来店時<br />@endif


@if ($program->feli_tb_mycoupon_master_cds)
出力クーポン⇒{{ $feliCoupon?->title }}<br />
@endif

■「会員様がマイページよりクーポン印刷」<br />
@if ($program->mypage_act_flg == '')動作⇒動作なし<br />@endif
@if ($program->mypage_act_flg == '1')動作⇒クーポン印刷<br />@endif


@if ($program->mypage_tb_mycoupon_master_cds && $mypageCoupon)
出力クーポン⇒{{ $mypageCoupon->title }}<br />
@endif

</td>
	</tr>
@endif
@elseif ($program->disp_conf == '2')
	<tr>
		<th>ディスプレイ出力</th>
		<td>


{{ $displaySystem?->name }}<br />

出力クーポン⇒

@foreach ($coupons as $coupon)
@if (in_array((string) $coupon->cd, $systemCouponCds, true))
{{ $coupon->title }} <br />
@endif
@endforeach
<br />

</td>
	</tr>
@endif


	<tr>
		<th>メモ</th>
		<td>{{ $program->comment }}</td>
	</tr>



</table>

<div class="btnBox">


<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.terminales.signage-programs.index') }}">
@csrf
<input type="hidden" name="kensu" id="kensu" value="{{ request('kensu') }}" />
<input type="hidden" name="page" id="page" value="{{ request('page') }}" />
<input type="submit" name="enter" id="enter" class="btn" value="&lt;&lt;一覧へ戻る" />
</form>



<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.terminales.signage-programs.edit', $program->cd) }}">
@csrf
<input type="hidden" name="cd" id="cd" value="{{ $program->cd }}" />
<input type="submit" name="enter" id="enter" class="btn" value="編集する" />
</form>



<form method="post" name="form3" id="form2" class="form" action="{{ route('admin.terminales.signage-programs.destroy', $program->cd) }}" onsubmit="return delChk()">
@csrf
<input type="hidden" name="delete_flg" id="delete_flg" value="del">
<input type="hidden" name="cd" id="cd" value="{{ $program->cd }}" />
<input type="hidden" name="kensu" id="kensu" value="{{ request('kensu') }}" />
<input type="hidden" name="page" id="page" value="{{ request('page') }}" />
<input type="hidden" name="shop_cd" id="shop_cd" value="{{ request('shop_cd') }}" />
<input type="submit" name="delete" id="delete" class="btn" value="削除する">
</form>




</div>
</fieldset>



<!-- / .form --></div>



<!-- / .contents --></div>
@endsection
