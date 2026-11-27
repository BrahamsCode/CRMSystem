{{--
    JS de la カバーフロー (mobile_mycolle_edit_coverflow.php). Llama a legacy.mypage.apli-file-upload:
      - subida de archivo: FormData { file, type: 'picUpload' } → { status, imageName | errMsg }
      - borrado:           type=del&pic=<n>                    → { status, res }
      - guardado:          serialize() de form[name=uploadPicForm] (type=sendData, pic[n], target_url[n],
                           mypage_config[cover][n][shop_cd]) → { status, result | errMsg }
--}}
<script type="text/javascript">

$(function(){

	var checkNumber = 0;
	$(".imageDisplay").each(function(){
		var areaNonShopLabelCount = $(this).find(".areaNonShopLabel").length;
		var areaCheckBtnCount = $(this).find("input[id^=area_check_btn_]").length;
		var areaBtnCount = $(this).find(".areaBtn").length;

		if ( areaBtnCount == 0 && areaNonShopLabelCount != 0 && areaCheckBtnCount != 0 && areaNonShopLabelCount == areaCheckBtnCount ) {

			$("input[id=mypage_config_cover_" + checkNumber + "_0]").after("全店<br>");
			$("label[for=mypage_config_cover_" + checkNumber + "_0]").css("float", "");

		}
		checkNumber++;
	});


	$("#uploadDialog").dialog({
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


	$(".addImage").on("change", function(){
		var fd = new FormData();
		var tg = $(this);

		if ( $(this).val()!== '' ) {
			fd.append( "file", tg.prop("files")[0] );
			fd.append( "type", 'picUpload' );
		}

		var postData = {
			type : "POST",
			dataType : "json",
			data : fd,
			processData : false,
			contentType : false
		};

		$(this).closest(".imageDisplay").find(".imageFrame").addClass("loading");

		window.onbeforeunload = function(e) { console.log("aaa"); return '現在ファイルの処理中になります。本当によろしいですか？'; };

		$.ajax(
			"{{ route('legacy.mypage.apli-file-upload') }}", postData
		).done(function( json ){
			if(json.status == 'ok'){

				var pre_url = tg.parent().parent().find(".url_param input").val();
				pre_url = (typeof pre_url === "undefined") ? '' : pre_url ;
				tg.parent().parent().find(".url_param").remove();
				tg.parent().parent().find(".del_pic_wrap").remove();

				tg.parent().prev().html('<img src="{{ $tempImageUrl }}'+json.imageName+'" style="width:100%;"><input type="hidden" name="pic[' + tg[0].name.split("image").join("") + ']" value="'+json.imageName+'">');
				tg.parent().parent().append('<div class="url_param"><input type="text" name="target_url[' + tg[0].name.split("image").join("") + ']" value="'+pre_url+'" placeholder="http, mailto, telから入力" style="width:96%;"></div>');
			}

			window.onbeforeunload = null;
			$(".imageFrame").removeClass("loading");
		});
	});

	$(".deletePic").on("click", function(){
		var t = $(this);
		$("#uploadDialog").html('削除しますか？');
		$("#uploadDialog").dialog({
			autoOpen: true,
			buttons: {
				'削除する': function(){
					$.ajax({
						url: "{{ route('legacy.mypage.apli-file-upload') }}",
						method: 'post',
						dataType: "json",
						data: "type=del&pic="+t.attr("target")
					}).done(function( res ) {
						if(res.status == "ok"){
							$("#uploadDialog").html(res.res + "\n\n画面をリロードします。");
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

	$("#picUploadBtn").on("click", function(){
		$("#uploadDialog").text('更新中です');
		$("#uploadDialog").dialog({
			autoOpen: true,
			buttons: {}
		});

		$.ajax({
			url: "{{ route('legacy.mypage.apli-file-upload') }}",
			method: 'post',
			dataType: "json",
			data: $("form[name='uploadPicForm']").serialize()
		}).done(function( res ) {
			if(res.status == 'ok'){
				$("#uploadDialog").text('アップロードが完了しました。');
				$("#uploadDialog").dialog({
					buttons: {
						'閉じる': function(){
							$(this).dialog('close');
						}
					}
				});
				location.reload(false);
			}else{
				$("#uploadDialog").text( res.errMsg );
				$("#uploadDialog").dialog({
					buttons: {
						'閉じる': function(){
							$(this).dialog('close');
						}
					}
				});
			}
		}).fail(function( jqXHR, textStatus, errorThrown ) {
			//console.log( 'ERROR', jqXHR, textStatus, errorThrown );

			$("#uploadDialog").text(jqXHR + textStatus + errorThrown);
			$("#uploadDialog").dialog({
				buttons: {
					'閉じる': function(){
						$(this).dialog('close');
					}
				}
			});
		});
	});
});
</script>
