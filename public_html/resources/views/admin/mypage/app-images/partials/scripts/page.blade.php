{{--
    <script> del <head> del legacy (común a las 5 pantallas). Incluye las líneas de inc/ac_2.php que el
    legacy mete con require dentro del $(document).ready. Los manejadores de #shop_cd y
    #shop_cd_osusume y sendSelForm() no tienen elementos en esta pantalla (código muerto del legacy),
    se conservan igual.
    No se reproduce displayJS() de base_color_function.php (plantillas de color de la HP móvil): en
    esta pantalla no hay ninguno de sus elementos y es código muerto.
--}}
<script type="text/javascript">

function submitChk () {
	var flg = confirm ( "この内容で登録してもよろしいですか？\n\n書き直す場合は[キャンセル]ボタンを押して下さい");
	return flg;
}

$(document).ready(function() {

	$(".ac1").accordion({ collapsible: true , active: false});
	$(".ac2").accordion({ collapsible: true , active: false});
	$(".ac3").accordion({ collapsible: true , active: false});
	$(".ac4").accordion({ collapsible: true , active: false});
	$(".ac5").accordion({ collapsible: true , active: false});
	$(".ac6").accordion({ collapsible: true , active: false});
	$(".ac7").accordion({ collapsible: true , active: false});
	$(".ac8").accordion({ collapsible: true , active: false});


	//$(".ac1").accordion('option', 'active', 0);
	$(".ac1").accordion({active: 0});


	// マイページトップ フリースペーステキスト設定　店舗選択 ===================================

	$("#shop_cd").change(function(){

		$("#reload_shop_cd").val($(this).val());
		$("#reload_form").submit();
		return false;

	});

	// マイページトップ オススメ表示件数設定　店舗選択 =========================================

	$("#shop_cd_osusume").change(function(){

		$("#reload_shop_cd_osusume").val($(this).val());
		$("#reload_form_osusume").submit();
		return false;

	});

	// =========================================================================================



});

function sendSelForm(val) {
	document.sel_form.sel.value = val;
	document.sel_form.submit();
}

</script>
