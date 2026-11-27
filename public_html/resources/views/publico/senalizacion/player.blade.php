{{--
    Reproductor de señalización digital (legacy: digitalsignage/index.php → index2.php). HTML completo,
    sin el layout del personal. Mismo <head>, mismas funciones JS de sondeo e impresión, mismos
    contenedores ocultos; el <object>/<embed> de Flash se sustituye por el reproductor HTML5
    (partials/player-files, partials/floormap, partials/tig-game).

    Contrato de datos (PlayerResolver::resolve):
      $shopCd int · $displayCd int · $requestShopCd / $requestDisplayCd string (para las URL de recarga)
      $program SignageProgram · $programCd int · $xmlMod string (update_date del programa) · $kanshiTime int
      $files bool (disp_conf 1) · $system string (tb_dispaly_systems_cds si disp_conf 2)
      $touchCoupon ?string · $mujyouken / $shinkyaku / $kaiinRaiten / $mypage bool
      $feliCoupon / $mypageCoupon string · $roulette / $slot / $floormap / $tigGame bool · $setting 'landscape'|'portrait'

    <base href=".../digitalsignage/">: el legacy usa URL relativas (./?shop_cd=…, ajax_*.php, ../system/js/…) y
    los sondeos devuelven location.href = "./?…"; Laravel quita la barra final de /digitalsignage/, así que la
    base mantiene esas URL relativas apuntando a /digitalsignage/.
--}}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ja" xml:lang="ja">
<head>
<base href="{{ url('digitalsignage') }}/" />

<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="robots" content="noindex" />
<meta name="robots" content="nofollow" />
<meta name="robot-control" content="deny-all" />
<meta name="robots" content="none" />
<title>digital signage</title>

<meta http-equiv="content-style-type" content="text/css" />
<meta http-equiv="content-script-type" content="text/javascript" />



<style type="text/css"><!--


html {
	height: 100%;
}

body {
	margin: 0;
	padding: 0;
	height: 100%;
	text-align: center;
}

.wrapper {
	width: 100%;
	z-index: 1;
	position: absolute;

}

.backGround {
	width: 100%;
	height: 100%;
	position: fixed;
	top: 0;
}

.backGround embed {
	z-index: 0;
}



.back_btn {
	font-size: 10em;
}

#result {
	display: none;
}


#welcome {
	padding: 30px 0;
	background: #EEEEEE;
	color: #EE1111;
	font-size: 600%;
}

.rotar {
	/* WebKit, Safari */
	-webkit-transform: rotate(90deg);

	/* Firefox 3.5+*/
	-moz-transform: rotate(90deg);

	/* Internet Explorer */
	filter: progid:DXImageTransform.Microsoft.BasicImage(rotation=3);
}

/*印刷物一枚の設定*/
.kobetsuPrintArea {
	font-size: 8px;
	text-align: center;
}

.couponTitle {
	font-size: 18px;
}

.couponComment {
	font-size: 14px;
}

.couponLimit {
	font-size: 14px;
}


/*xmlの更新日時を取得*/
#xmlCheckResult {
	dispaly: none;
}


#ajaxTouchPrintCouponCrawlResult {
	display: none;
}

#ajaxShinkyakuPrintCouponCrawlResult {
	display: none;
}

#ajaxKaiinRaitenPrintCouponCrawlResult {
	display: none;
}

#ajaxFloormapPrintCouponCrawlResult {
	display: none;
}

#ajaxMujyoukenPrintCouponCrawlResult {
	display: none;
}


--></style>

<link rel="stylesheet" type="text/css" media="print" href="coupon_detail_print.css" />


<script type="text/javascript" src="../system/js/jquery-1.3.2.min.js"></script>

<script type="text/javascript"><!--

var xmlMod = "{{ $xmlMod }}";


// FLASHで使用しているXMLの更新があるかどうかをチェック
function xmlCheckCrawl(){


	if(xmlMod == ""){

		location.href = "./?shop_cd={{ $requestShopCd }}&display_cd={{ $requestDisplayCd }}&tb_c7_movie_program_cd={{ $programCd }}";
	}

	$("#xmlCheckResult").load("ajax_xml_check_2.php",{
		xml_mod:xmlMod,
		shop_cd:{{ $shopCd }},
		tb_c7_movie_program_cd:{{ $programCd }},
		display_cd:{{ $displayCd }}
	});
}

// 端末自体の設定を管理画面で更新しているかどうかをチェック
function displayCheckCrawl(){

	$("#ajaxDisplayCheckCrawlResult").load("ajax_display_config_check_2.php",{
		display_check_time:$("#kanshi_time").val(),
		shop_cd:{{ $shopCd }},
		display_cd:{{ $displayCd }}
	});
}

@if ($touchCoupon !== null)
function ajaxTouchPrintCouponCrawl(){

	$("#ajaxTouchPrintCouponCrawlResult").load("ajax_touch_print_coupon_crawl_2.php?time={{ time() }}&tb_mycoupon_master_cd={{ $touchCoupon }}",{
		TouchPrintCoupon_kanshi_time:$("#TouchPrintCoupon_kanshi_time").val(),
		shop_cd:{{ $shopCd }},
		tb_c7_movie_program_cd:{{ $programCd }},
		display_cd:{{ $displayCd }}
	});
}// function ajaxTouchPrintCouponCrawl()--END
@endif

@if ($mujyouken)
function ajaxMujyoukenPrintCouponCrawl(){

	$("#ajaxMujyoukenPrintCouponCrawlResult").load("ajax_Mujyouken_print_coupon_crawl_2.php?time={{ time() }}&tb_mycoupon_master_cd={{ $feliCoupon }}",{
		mujyoukenPrintCoupon_kanshi_time:$("#mujyoukenPrintCoupon_kanshi_time").val(),
		shop_cd:{{ $shopCd }},
		tb_c7_movie_program_cd:{{ $programCd }},
		display_cd:{{ $displayCd }}
	});
}// function ajaxMujyoukenPrintCouponCrawl()--END
@endif

@if ($shinkyaku)
function ajaxShinkyakuPrintCouponCrawl(){

	$("#ajaxShinkyakuPrintCouponCrawlResult").load("ajax_shinkyaku_print_coupon_crawl_2.php?time={{ time() }}&tb_mycoupon_master_cd={{ $feliCoupon }}",{
		ShinkyakuPrintCoupon_kanshi_time:$("#ShinkyakuPrintCoupon_kanshi_time").val(),
		shop_cd:{{ $shopCd }},
		tb_c7_movie_program_cd:{{ $programCd }},
		display_cd:{{ $displayCd }}
	});
}// function ajaxShinkyakuPrintCouponCrawl()--END
@endif

@if ($kaiinRaiten)
function ajaxKaiinRaitenPrintCouponCrawl(){

	$("#ajaxKaiinRaitenPrintCouponCrawlResult").load("ajax_kaiin_raiten_print_coupon_crawl.php?time={{ time() }}&tb_mycoupon_master_cd={{ $feliCoupon }}",{

		KaiinRaitenPrintCoupon_kanshi_time:$("#KaiinRaitenPrintCoupon_kanshi_time").val(),
		shop_cd:{{ $shopCd }},
		tb_c7_movie_program_cd:{{ $programCd }},
		display_cd:{{ $displayCd }}
	});
}// function ajaxKaiinRaitenPrintCouponCrawl()--END
@endif

@if ($mypage)
function ajaxKaiinMycouponPrintCrawl(){

	$("#ajaxKaiinMycouponPrintCrawlResult").load("ajax_kaiin_mycoupon_print_crawl.php?time={{ time() }}&tb_mycoupon_master_cd={{ $mypageCoupon }}",{

		KaiinMycouponPrint_kanshi_time:$("#KaiinMycouponPrint_kanshi_time").val(),
		shop_cd:{{ $shopCd }},
		tb_c7_movie_program_cd:{{ $programCd }},
		display_cd:{{ $displayCd }}
	});
}// function ajaxKaiinMycouponPrintCrawl()--END
@endif

@if ($roulette)
function ajaxTouchRoulettePrintCrawl(){

	location.href = "./roulette.php?shop_cd={{ $requestShopCd }}&display_cd={{ $requestDisplayCd }}";

}// function ajaxTouchRoulettePrintCrawl()--END
@endif

@if ($slot)
function ajaxTouchSlotPrintCrawl(){

	location.href = "./slot.php?shop_cd={{ $requestShopCd }}&display_cd={{ $requestDisplayCd }}";

}// function ajaxTouchSlotPrintCrawl()--END
@endif


function printNaiyouReset() {
	$("#ajaxTouchPrintCouponCrawlResult").empty();
	$("#ajaxTouchPrintCouponCrawlResult").append(
		"<input type=\"hidden\" name=\"TouchPrintCoupon_kanshi_time\" id=\"TouchPrintCoupon_kanshi_time\" value=\"{{ $kanshiTime }}\" />"
	);
}



// 印刷: el legacy usaba la extensión jsPrintSetup de Firefox (impresión silenciosa). Si no está, window.print()
// (en Chrome, modo quiosco con --kiosk-printing para imprimir sin diálogo).
function imprime() {

	if (typeof jsPrintSetup != "undefined") {
		// set portrait orientation
		jsPrintSetup.setOption('orientation', jsPrintSetup.kPortraitOrientation);
		// set top margins in millimeters
		jsPrintSetup.setOption('marginTop', 0);
		jsPrintSetup.setOption('marginBottom', 0);
		jsPrintSetup.setOption('marginLeft', 5);
		jsPrintSetup.setOption('marginRight', 5);
		// set page header
		jsPrintSetup.setOption('headerStrLeft', '');
		jsPrintSetup.setOption('headerStrCenter', '');
		jsPrintSetup.setOption('headerStrRight', '');
		// set empty page footer
		jsPrintSetup.setOption('footerStrLeft', '');
		jsPrintSetup.setOption('footerStrCenter', '');
		jsPrintSetup.setOption('footerStrRight', '');
		// Suppress print dialog
		jsPrintSetup.setSilentPrint(true);

		// Do Print
		jsPrintSetup.print();

		jsPrintSetup.setSilentPrint(true);
	} else {
		window.print();
	}

	setInterval('printNaiyouReset()',2000);

}




$(document).ready(function(){

	setInterval('xmlCheckCrawl()',60000);// xmlの更新日時をチェックして、更新データがあればリロード
	setInterval('displayCheckCrawl()',61000);// displayの更新日時をチェックして、更新データがあればリロード

@if ($touchCoupon !== null)
	$("body").click(function () {
		ajaxTouchPrintCouponCrawl();
	});
@endif

@if ($mujyouken)
	setInterval('ajaxMujyoukenPrintCouponCrawl()',3050);
@endif

@if ($shinkyaku)
	setInterval('ajaxShinkyakuPrintCouponCrawl()',3100);
@endif

@if ($kaiinRaiten)
	setInterval('ajaxKaiinRaitenPrintCouponCrawl()',3000);
@endif

@if ($mypage)
	setInterval('ajaxKaiinMycouponPrintCrawl()',3700);
@endif

@if ($roulette)
	$("body").click(function () {
		ajaxTouchRoulettePrintCrawl();
	});
@endif

@if ($slot)
	$("body").click(function () {
		ajaxTouchSlotPrintCrawl();
	});
@endif

});


--></script>



</head>
<body>

<div id="wrapper"></div>


<div id="result">
<input type="hidden" name="kanshi_time" id="kanshi_time" value="{{ $kanshiTime }}" />
</div>

@if ($touchCoupon !== null)
<div id="ajaxTouchPrintCouponCrawlResult">
<input type="hidden" name="TouchPrintCoupon_kanshi_time" id="TouchPrintCoupon_kanshi_time" value="{{ $kanshiTime }}" />
</div>
@endif

@if ($mujyouken)
<div id="ajaxMujyoukenPrintCouponCrawlResult">
<input type="hidden" name="mujyoukenPrintCoupon_kanshi_time" id="mujyoukenPrintCoupon_kanshi_time" value="{{ $kanshiTime }}" />
</div>
@endif

@if ($shinkyaku)
<div id="ajaxShinkyakuPrintCouponCrawlResult">
<input type="hidden" name="ShinkyakuPrintCoupon_kanshi_time" id="ShinkyakuPrintCoupon_kanshi_time" value="{{ $kanshiTime }}" />
</div>
@endif

{{-- El legacy pintaba este contenedor con la condición de act_flg (no la de feli_act_flg = 3), así que el
     sondeo 会員来店時 no tenía dónde escribir: aquí sale con su propia condición --}}
@if ($kaiinRaiten)
<div id="ajaxKaiinRaitenPrintCouponCrawlResult">
<input type="hidden" name="KaiinRaitenPrintCoupon_kanshi_time" id="KaiinRaitenPrintCoupon_kanshi_time" value="{{ $kanshiTime }}" />
</div>
@endif

@if ($mypage)
<div id="ajaxKaiinMycouponPrintCrawlResult">
<input type="hidden" name="KaiinMycouponPrint_kanshi_time" id="KaiinMycouponPrint_kanshi_time" value="{{ $kanshiTime }}" />
</div>

<div id="infoXmlCheckResult"></div>
@endif


<div id="xmlCheckResult"></div>


<div id="ajaxDisplayCheckCrawlResult"></div>


<div id="ajaxFloormapPrintCouponCrawlResult"></div>


<div class="backGround">

@if ($floormap)
@include('publico.senalizacion.partials.floormap')
@endif

@if ($tigGame)
@include('publico.senalizacion.partials.tig-game')
@endif

@if ($files)
@include('publico.senalizacion.partials.player-files')
@endif

@if ($roulette || $slot)
{{-- ルーレット / スロット: el legacy no mostraba nada aquí (5 era el swf de 不動産, descartado); un toque abre el juego --}}
<img src="digitalsignage_logo.jpg" alt="" style="max-width:100%;max-height:100%;" />
@endif

</div>

</body>
</html>
