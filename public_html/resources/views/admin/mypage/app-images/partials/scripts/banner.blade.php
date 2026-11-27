{{--
    JS de la バナー (mobile_mycolle_edit_banner.php). Requiere js/imageselect.js (se carga antes en edit).
    Llama a legacy.mypage.apli-file-upload:
      - subida de archivo: FormData { file, type: 'bannerUpload' } → { status, imageName | errMsg }
      - borrado:           type=bannerdel&pic=<n>                 → { status, res }
      - guardado:          serialize() de form[name=uploadBannerForm] (type=sendBannerData, pic[n],
                           target_url[n], mypage_config[banner][n][shop_cd], mypage_config[cell_status][n],
                           mypage_config[cell_img][n]) → { status, result | errMsg }
                           Los <select name="mypage_config_cell_img_<n>"> se renombran a
                           mypage_config[cell_img][<n>] justo antes de serializar y se devuelven después.
--}}
<script type="text/javascript">
	$(function() {
		$('select[name^=mypage_config_cell_img_]').ImageSelect({
			dropdownWidth:425, //幅指定
			dropdownHeight:100, //高さ指定
			backgroundColor:"#FFFFFF", //背景色
			borderColor:"#CCCCCC" //枠線の色
		});
		$(".bannerStatusList input[type=radio]").click(function(){
			$(this).parent().parent().parent().parent().find(".bannerCell1").hide();
			$(this).parent().parent().parent().parent().find(".bannerCell2").hide();
			$(this).parent().parent().parent().parent().find(".bannerCell" + $(this).val()).show();
		});

	});
</script>

<script type="text/javascript">
$(function(){

	var checkNumber = 0;
	$(".bannerDisplay").each(function(){
		var areaNonShopLabelCount = $(this).find(".areaNonShopLabel").length;
		var areaCheckBtnCount = $(this).find("input[id^=area_check_btn_]").length;
		var areaBtnCount = $(this).find(".areaBtn").length;

		if ( areaBtnCount == 0 && areaNonShopLabelCount != 0 && areaCheckBtnCount != 0 && areaNonShopLabelCount == areaCheckBtnCount ) {

			$("input[id=mypage_config_banner_" + checkNumber + "_0]").after("全店<br>");
			$("label[for=mypage_config_banner_" + checkNumber + "_0]").css("float", "");

		}
		checkNumber++;
	});

	$("#uploadBannerDialog").dialog({
		autoOpen: false,
		open: function(event, ui){ $(".ui-dialog-titlebar-close").hide(); },
		resizable: false,
		draggable: false,
		closeOnEscape: false,
		modal: true,
		title: 'Infomation',
		width: 300,
		height: 300,
		buttons: {}
	});


	$(".addBanner").on("change", function(){
		var fd = new FormData();
		var tg = $(this);

		if ( $(this).val()!== '' ) {
			fd.append( "file", tg.prop("files")[0] );
			fd.append( "type", 'bannerUpload' );
		}

		var postData = {
			type : "POST",
			dataType : "json",
			data : fd,
			processData : false,
			contentType : false
		};

		$(this).closest(".bannerDisplay").find(".bannerFrame").addClass("loading");

		window.onbeforeunload = function(e) { console.log("aaa"); return '現在ファイルの処理中になります。本当によろしいですか？'; };

		$.ajax(
			"{{ route('legacy.mypage.apli-file-upload') }}", postData
		).done(function( json ){
			if(json.status == 'ok'){

				var pre_url = tg.parent().parent().find(".url_param input").val();
				pre_url = (typeof pre_url === "undefined") ? '' : pre_url ;
				tg.parent().parent().find(".url_param").remove();
				tg.parent().parent().find(".del_pic_wrap").remove();

				tg.parent().prev().html('<img src="{{ $tempImageUrl }}'+json.imageName+'" style="width:100%;"><input type="hidden" name="pic[' + tg[0].name.split("banner").join("") + ']" value="'+json.imageName+'">');
				//tg.parent().parent().append('<div class="url_param"><input type="text" name="target_url[]" value="'+pre_url+'" placeholder="http, mailto, telから入力" style="width:96%;"></div>');


			}
			window.onbeforeunload = null;
			$(".bannerFrame").removeClass("loading");
		});
	});

	$(".deleteBanner").on("click", function(){
		var t = $(this);
		$("#uploadBannerDialog").html('削除しますか？');
		$("#uploadBannerDialog").dialog({
			autoOpen: true,
			buttons: {
				'削除する': function(){
					$.ajax({
						url: "{{ route('legacy.mypage.apli-file-upload') }}",
						method: 'post',
						dataType: "json",
						data: "type=bannerdel&pic="+t.attr("target")
					}).done(function( res ) {
						if(res.status == "ok"){
							$("#uploadBannerDialog").html(res.res + "\n\n画面をリロードします。");
							location.reload(false);
						}
					}).fail(function( jqXHR, textStatus, errorThrown ) {
						console.log( 'ERROR', jqXHR, textStatus, errorThrown );
					});
				},
				'キャンセル': function(){
					$(this).dialog('close');
				}
			}
		});
	});

	$("#bannerUploadBtn").on("click", function(){

		$("select[name^=mypage_config_cell_img_]").each(function(){
			tmp = $(this).attr("name").split("mypage_config_cell_img_").join("");
			$(this).attr("name", "mypage_config[cell_img][" + tmp + "]");
		});

		$("#uploadBannerDialog").text('更新中です');
		$("#uploadBannerDialog").dialog({
			autoOpen: true,
			buttons: {}
		});

		$.ajax({
			url: "{{ route('legacy.mypage.apli-file-upload') }}",
			method: 'post',
			dataType: "json",
			data: $("form[name='uploadBannerForm']").serialize()
		}).done(function( res ) {
			if(res.status == 'ok'){
				$("#uploadBannerDialog").text('アップロードが完了しました。');
				$("#uploadBannerDialog").dialog({
					buttons: {
						'閉じる': function(){
							$(this).dialog('close');
						}
					}
				});
				location.reload(false);
			}else{
				$("#uploadBannerDialog").text( res.errMsg );
				$("#uploadBannerDialog").dialog({
					buttons: {
						'閉じる': function(){
							$(this).dialog('close');
						}
					}
				});
			}

			$("select[name^=mypage_config\\[cell_img\\]]").each(function(){
				tmp = $(this).attr("name").split("mypage_config[cell_img][").join("");
				tmp = tmp.split("]").join("");
				$(this).attr("name", "mypage_config_cell_img_" + tmp);
			});

		}).fail(function( jqXHR, textStatus, errorThrown ) {
			//console.log( 'ERROR', jqXHR, textStatus, errorThrown );

			$("#uploadBannerDialog").text(jqXHR + textStatus + errorThrown);
			$("#uploadBannerDialog").dialog({
				buttons: {
					'閉じる': function(){
						$(this).dialog('close');
					}
				}
			});

			$("select[name^=mypage_config\\[cell_img\\]]").each(function(){
				tmp = $(this).attr("name").split("mypage_config[cell_img][").join("");
				tmp = tmp.split("]").join("");
				$(this).attr("name", "mypage_config_cell_img_" + tmp);
			});
		});
	});
});
</script>
