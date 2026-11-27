{{--
    <script> que imprime Area::createAreaCheckUi() al final de cada llamada (en el legacy, una vez por
    hueco: 21 copias idénticas). Es idempotente (.off().on() y ocultar), así que aquí va una sola vez.
    $1112 es el alias de jQuery viejo del legacy; solo se usa si $.fn.off no existe.
--}}
<script type="text/javascript">
var clicked = false;
$(function(){

	$(".areaCheckWrap").each(function(){

		var isChildCheck = false;
		$(this).find("input[name^=mypage_config]").each(function(){
			if ( $(this).prop('checked') ) {
				isChildCheck = true;
			}
		});

		if ( !isChildCheck ) {
			$(this).find(".areaShopLabel").hide();
			$(this).find(".areaCheckWrap").hide();
		}

	});

	if ( typeof $('.areaBtn').off != 'function' ) {
		$1112('.areaBtn').off().on('click', function(){

			if (clicked) {
				if ( $1112(this).closest("div").children("div").css('display') == 'block') {
					$1112(this).closest("div").children("div").hide();
				} else {
					$1112(this).closest("div").children("div").show();
				}
				clicked = false;
				return;
			}
			clicked = true;
			setTimeout($1112.proxy(function() {
				if (clicked) {
					var t = $1112(this).parent().find('input[type="checkbox"]');
					var chk = false;
					t.each(function(){
						if ( !$1112(this).prop('checked') ) chk = true;
					});

					if ( chk ) t.prop('checked', true);
					else t.prop('checked', false);
				}
				clicked = false;
			}, this), 300);

		});
	} else {
		$('.areaBtn').off().on('click', function(){

			if (clicked) {
				if ( $(this).closest("div").children("div").css('display') == 'block') {
					$(this).closest("div").children("div").hide();
				} else {
					$(this).closest("div").children("div").show();
				}
				clicked = false;
				return;
			}
			clicked = true;
			setTimeout($.proxy(function() {
				if (clicked) {
					var t = $(this).parent().find('input[type="checkbox"]');
					var chk = false;
					t.each(function(){
						if ( !$(this).prop('checked') ) chk = true;
					});

					if ( chk ) t.prop('checked', true);
					else t.prop('checked', false);
				}
				clicked = false;
			}, this), 300);

		});
	}
});
</script>
