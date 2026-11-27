$(function() {
	$('#smatopiLineDialog').dialog({
		autoOpen: false,
		resizable: false,
		draggable: false,
		closeOnEscape: false,
		modal: true,
		title: 'LINE配信',
		width: 650,
		height: 650,
		open: function(event, ui){
			$(".ui-dialog-titlebar-close").hide();
		},
		buttons: [
			{
				text  : '閉じる',
				id    : 'smatopiLineDialogBtn_cancel',
				class : 'btn-base btn-panel btn-dialog btn-cancel fl-right',
				click : function() {
					$(this).dialog('close');
				}
			},
			{
				text  : '実行',
				id    : 'smatopiLineDialogBtn_send',
				class : 'btn-base btn-panel btn-dialog btn-regist fl-right',
				click : function() {
					confirmDialog('配信確認', 'この内容で配信してもよろしいですか？', function() {
						var formData = new FormData($('#smatopiLineForm').get(0));
						mAlert('<p style="padding:20px;">処理中</p>');
						$.ajax({
							type: "POST",
							url: "/system/category3/smatopi/ajax_smatopiLine.php",
							data: formData,
							dataType: 'json',
							processData: false,
							contentType: false,
							success: function(resp) {
								if ( resp.status == 'ok' ) {
									alertDialog('情報', '配信しました。', function() {
										location.reload();
									});
								} else {
									var error = resp.errorMsg ? resp.errorMsg : '配信に失敗しました。';
									alertDialog('警告', error.replaceAll('\n', '<br/>'));
								}
							},
							error: function() {
								alertDialog('警告', '配信に失敗しました。');
							},
							complete: function() {
								mAlert();
							}
						})
					});
				}
			}
		]
	});

	$('#smatopiLineBtn').on('click', function(e) {
		$("#smatopiLineDialog").dialog('open');
	});

	var getSettings = function() {
		$('#smatopiLineInputSection').hide();
		var shopCd = parseInt($('#smatopiLineShopCd').val()) || 0;
		if ( shopCd > 0 ) {
			mAlert('<p style="padding:20px;">処理中</p>');
			$.ajax({
				type: "POST",
				url: "/system/category3/smatopi/ajax_smatopiLine.php",
				data: "act=getSettings&shopCd=" + shopCd + "&domainSmatopiId=" + $('#smatopiLineForm #domainSmatopiId').val(),
				dataType: 'json',
				success: function(resp) {
					if ( resp.status == 'ok' ) {
						var couponOptions = '<option value=""></option>';
						// タグ
						for (key in resp.coupons) {
							couponOptions += '<option value="' + resp.coupons[key]['id'] + '">' + resp.coupons[key]['coupon_name'] + '</option>';
						}
						$('#smatopiLineInputSection #couponId').html(couponOptions);
						$('#smatopiLineInputSection').show();
						$("#smatopiLineDialogBtn_send").show();
					} else {
						var error = resp.errorMsg ? resp.errorMsg : 'データの取得に失敗しました。';
						alertDialog('警告', error.replaceAll('\n', '<br/>'));
						$('#smatopiLineShopCd').val('');
					}
				},
				error: function() {
					alertDialog('警告', 'データの取得に失敗しました。');
				},
				complete: function() {
					mAlert();
				}
			});
		} else {
			$("#smatopiLineDialogBtn_send").hide();
		}
	}

	$('#smatopiLineShopCd').on('change', getSettings);
	$('#smatopiLineShopCd').val('').change();
});