{{--
    Estilos de la pantalla, en el mismo orden que el legacy:
    1) el <style> del <head> (los sample_* usan $tb_color_array, que en esta pantalla nunca está
       definido: el legacy imprime "#;" y aquí se deja igual);
    2) los <style> que el legacy pone dentro de .main, uno por sección. Se imprimen todos aunque la
       sección no se muestre, porque son globales y se pisan entre sí (cascada idéntica al legacy);
    3) el <style> que genera Area::createAreaCheckUi() (solo cover/banner con tiendas).
--}}
<style type="text/css">

.sample_field {
	width: 300px;
	height: 570px;
	background: url({{ asset('legacy/system/img/mobileframe.gif') }});
	overflow: hidden;
}

.sample_bodyColor{
	width: 200px;
	height: 235px;
	margin: 134px auto;
	background:#;
	overflow-y: scroll;
}

.sample_bodyColor img {
	width: 100%;
}

.sample_fontColor{color:#;}

.sample_linkColor{color:#;}

.sample_alinkColor{
	background:#;
	color:#;
}

.sample_vlinkColor{color:#;}




.templateThumBox {
	position: relative;
	display: inline-block;
	margin: 0 5px 5px 0;
}

.templateThum {
	width: 120px;
	background: #CCC;
	border: 1px solid #999;
	vertical-align: bottom!important;
}


.selectFamily {
	margin: 0 0 5px;
}

.selectFamily li {
	display: inline-block;
	padding: 0 10px;
	border: 1px solid #CCCCCC;
}




</style>

@if ($applicationUseFlg)
	{{-- マイページアプリのカバーフロー設定 --}}
	<style type="text/css">
	.imageDisplay {
		display: inline-block;
		overflow: hidden;
		width: 200px;
		margin-bottom: 10px;
		vertical-align: top;
	}
	.imageFrame {
		min-height: 140px;
		padding: 5px;
		background-color: #EEEEEE;
	}
	@if (in_array($section, ['cover', 'banner'], true))
	.areaCheckWrap{
		margin-left: 19px;
	}
	.areaCheckWrap .areaCheckWrap{
		margin-left: 0px;
		text-indent: -20px;
		padding-left: 10px;
	}
	.areaCheckWrap .areaShopLabel {
		margin-left: 35px !important;
	}
	@endif
	</style>

	{{-- マイページアプリのバナー設定 --}}
	<style type="text/css">
	.bannerDisplay {
		display: inline-block;
		/*overflow: hidden;*/
		width: 200px;
		margin-bottom: 10px;
		vertical-align: top;
	}
	.bannerFrame {
		min-height: 59px;
		padding: 5px;
		background-color: #EEEEEE;
		position:relative;
	}
	.bannerStatusList li{
		display: inline-block;
		cursor: pointer;
	}
	.bannerStatusList li label{
		cursor: pointer;
	}
	.bannerStatusList li input{
		cursor: pointer;
	}
	.bannerCell2{
		display:none;
	}
	.jqis_header img{
		width: 100% !important;
		height: auto !important;
	}
	.jqis_header{
		width: 96% !important;
		height: auto !important;
		text-align: center;
		border: 5px solid rgb(238, 238, 238) !important;
		background-color: rgb(238, 238, 238) !important;
		/* padding-top: 3px !important; */
	}
	.jqis{
		width: calc(100% - 2px) !important;
	}
	.jqis {
		margin-bottom :26px;
		height: auto !important;
	}
	.jqis_dropdown{
		overflow-y: scroll;
		width: 200px !important;
		height: 300px !important;
		min-height: 300px !important;
	}
	.jqis_dropdown{
		position: relative;
	}
	.jqis_dropdown img {
		margin-bottom: 5px;
	}
	.bannerCell1{
		height: 90px !important;
	}
	.bannerCell2{
		height: 90px !important;
	}
	</style>

	{{-- マイページアプリのHOMEに表示されるメインイメージ (también da el .loading de cover/banner) --}}
	<style type="text/css">
	.imageDisplay {
		display: inline-block;
		overflow: hidden;
		width: 200px;
		margin-bottom: 10px;
		vertical-align: top;
	}
	.imageFrame {
		min-height: 140px;
		padding: 5px;
		background-color: #EEEEEE;
		position: relative;
	}
	.bannerFrame.loading:after,
	.imageFrame.loading:after {
		content: " ";
		display: block;
		background: url({{ asset('legacy/system/category6/user_hp/img/loading.gif') }});
		background-size: 60px;
		width: 60px;
		height: 60px;
		position: absolute;
		top: 0;
		bottom: 0;
		right: 0;
		left: 0;
		margin: auto;
	}
	</style>

	{{-- マイページアプリのHOMEに表示されるサムネイル画像 --}}
	<style type="text/css">
	.imageDisplay {
		display: inline-block;
		overflow: hidden;
		width: 200px;
		margin-bottom: 10px;
		vertical-align: top;
	}
	.imageFrame {
		min-height: 140px;
		padding: 5px;
		background-color: #EEEEEE;
	}
	.leftArea{
		float: left;
	}
	@if (in_array($section, ['cover', 'banner'], true))
	.rightArea{
	}
	.rightArea .imageDisplay{
	}
	@endif
	</style>

	{{-- Estilo que imprime Area::createAreaCheckUi() (en el legacy, una vez por hueco; aquí una sola vez) --}}
	@if (in_array($section, ['cover', 'banner'], true) && count($shops))
	<style type="text/css">
	.areaCheckWrap{ margin-bottom: 2px; }
	.areaCheckWrap .areaShopLabel { margin-left: {{ (int) ($areaDepthMax * 15) }}px; }
	.areaCheckWrap .areaCheckWrap{
		margin-left: 0px;
		text-indent: -20px;
		padding-left: 5px;
	}
	.areaCheckWrap .areaShopLabel {
		margin-left: 40px !important;
		text-indent: -40px;
	}
	</style>
	@endif
@endif
