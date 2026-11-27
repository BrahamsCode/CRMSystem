{{--
    JS de メインイメージ ($home = 'main') y サムネイル ($home = 'thumbnail'). Llama a legacy.mypage.apli-file-upload:
      - subida:   FormData { file, type: 'picMainUpload' | 'picThumbnailUpload' } → { status, imageName | errMsg }
      - borrado:  type=delMain&dir=apli_home_image&num=<target>
                  type=delThumbnail&dir=apli_home_thumbnail&num=<target>           → { status, res }
      - guardado: serialize() del form (type=sendMainData|sendThumbnailData, dir, pic[<i>],
                  mypage_config[home_image_flg|home_thum_flg][shop_cd]) → { status, result | errMsg }
--}}
@php
    $isMain = $home === 'main';
    $inputClass = $isMain ? 'addMainImage' : 'addThumbnailImage';
    $deleteClass = $isMain ? 'deleteMainPic' : 'deleteThumbnailPic';
    $buttonId = $isMain ? 'picMainUploadBtn' : 'picThumbnailUploadBtn';
    $formName = $isMain ? 'uploadMainPicForm' : 'uploadThumbnailPicForm';
    $uploadType = $isMain ? 'picMainUpload' : 'picThumbnailUpload';
@endphp
<script type="text/javascript">
$(function(){
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


	$(".{{ $inputClass }}").on("change", function(){
		var fd = new FormData();
		var tg = $(this);
		var shop_cd = $(this).attr("name").split("image").join("");

		if ( $(this).val()!== '' ) {
			fd.append( "file", tg.prop("files")[0] );
			fd.append( "type", '{{ $uploadType }}' );
		}

		var loader = '<div class="loader"></div>';
		tg.parent().prev().html(loader);

		var postData = {
			type : "POST",
			dataType : "json",
			data : fd,
			processData : false,
			contentType : false
		};
		$.ajax(
			"{{ route('legacy.mypage.apli-file-upload') }}", postData
		).done(function( json ){
			if(json.status == 'ok'){

				tg.parent().parent().find(".del_pic_wrap").remove();

				var html= '<input type="hidden" name="pic[' + shop_cd + ']" value="'+json.imageName+'">';

				tg.parent().prev().html('<img src="{{ $tempImageUrl }}'+json.imageName+'" style="width:100%;">' + html);
				$('#{{ $buttonId }}').show();
			} else {
				tg.parent().prev().html('');
				alert( json.errMsg );
			}
		});
	});

	$(".{{ $deleteClass }}").on("click", function(){
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
						@if ($isMain)
						data: "type=delMain&dir=apli_home_image&num="+t.attr("target")
						@else
						data: "type=delThumbnail&dir=apli_home_thumbnail&num="+t.attr("target")
						@endif
					}).done(function( res ) {
						@if (! $isMain)
						console.log(res);
						@endif
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

	$("#{{ $buttonId }}").on("click", function(){
		$("#uploadDialog").text('更新中です');
		$("#uploadDialog").dialog({
			autoOpen: true,
			buttons: {}
		});

		$.ajax({
			url: "{{ route('legacy.mypage.apli-file-upload') }}",
			method: 'post',
			dataType: "json",
			data: $("form[name='{{ $formName }}']").serialize()
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
