$(function() {
	var numberFormat = function(val) {
		return String(val).replace( /(\d)(?=(\d\d\d)+(?!\d))/g, '$1,');
	}

	var getSettings = function() {
		$('#inputSection').hide();
		var shopCd = parseInt($('#shopCd').val()) || 0;
		if ( shopCd > 0 ) {
			mAlert('<p style="padding:20px;">処理中</p>');
			$.ajax({
				type: "POST",
				url: "./" + ajaxPage,
				data: "act=getSettings&shopCd=" + shopCd + "&domainSmatopiId=" + $('#dataForm #domainSmatopiId').val(),
				dataType: 'json',
				success: function(resp) {
					if ( resp.status == 'ok' ) {
						var tagOptions = '<option value=""></option>';
						// タグ
						for (key in resp.tags) {
							tagOptions += '<option value="' + resp.tags[key]['id'] + '">' + resp.tags[key]['name'] + '</option>';
						}
						$('#inputSection #selectTag').html(tagOptions);

						// 配信可能数・配信済み数
						$('#remainingCount').text(numberFormat(resp.messageCount.remaining_message_count || 0));
						$('#sentCount').text(numberFormat(resp.messageCount.delivered_message_count || 0));

						// 友達登録数
						$('#friendsCount').text(numberFormat(resp.friendsCount || 0));

						$('#inputSection').show();
					} else {
						var error = resp.errorMsg ? resp.errorMsg : 'データの取得に失敗しました。';
						alertDialog('警告', error.replaceAll('\n', '<br/>'));
						$('#shopCd').val('');
					}
				},
				error: function() {
					alertDialog('警告', 'データの取得に失敗しました。');
				},
				complete: function() {
					mAlert();
				}
			});
		}
	}

	$('#shopCd').on('change', getSettings);
	$('#shopCd').val('');

	// 配信対象
	$('#sendType').on('change', function(e) {
		var val = $(this).val();
		if (val == '2') {
			$('#segmentArea').show();
			$('#tagsArea').hide();
		} else if (val == '3') {
			$('#segmentArea').hide();
			$('#tagsArea').show();
		} else {
			$('#segmentArea').hide();
			$('#tagsArea').hide();
		}
	});

	// 誕生日月
	$('#birthmonthFlag').on('change', function(e) {
		if (this.checked) {
			$('.noForBirthMonth').hide();
			$('.noForBirthMonth input').prop('checked', false);
		} else {
			$('.noForBirthMonth').show();
		}
	});

	// 繰り返し配信
	$('#repeatSetting').on('change', function(e) {
		if ($(this).val() == '1') {
			$('#scheduledDate').hide();
			$('#repeatArea').show();
		} else {
			$('#repeatArea').hide();
			$('#scheduledDate').show();
		}
	});

	// 繰り返しタイプ
	$('#typePrefectures').on('change', function(e) {
		if ($(this).val() == '1') {
			$('#onlyForWeekly').show();
		} else {
			$('#onlyForWeekly').hide();
		}
	});

	// 繰り返し間隔（単位）
	$('#intervalUnit').on('change', function(e) {
		if ($(this).val() == '6') {
			$('#onlyForCustom').show();
		} else {
			$('#onlyForCustom').hide();
		}
	});

	// 配信終了日タイプ
	$('#endDateType').on('change', function(e) {
		var val = $(this).val();
		$('.noForEndDateType1').hide();
		if (val == '2') {
			$('#onlyForEndDateType2').show();
		} else if (val == '3') {
			$('#onlyForEndDateType3').show();
		}
	});

	$(".datepicker").datepicker({
		changeMonth     : false,
		changeYear      : false,
		showButtonPanel : true,
		dateFormat      : 'yy-mm-dd',
		setDate         : '',
		todayHighlight: true,
	});

	$('#checkReserveSend').on('change', function(e) {
		var val = $(this).val();
		if (val == '1') {
			$('#datetimeArea').show();
		} else {
			$('#datetimeArea').hide();
		}
	});

	$('#inputSection #submitForm').on('click', function() {
		confirmDialog('配信確認', 'この内容で配信してもよろしいですか？', function() {
			var formData = new FormData($('#dataForm').get(0));
			mAlert('<p style="padding:20px;">処理中</p>');
			$.ajax({
				type: "POST",
				url: "./" + ajaxPage,
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
	});
});
