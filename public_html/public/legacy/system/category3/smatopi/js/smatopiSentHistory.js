$(function() {
	var numberFormat = function(val) {
		return String(val).replace( /(\d)(?=(\d\d\d)+(?!\d))/g, '$1,');
	}

	var tags = null;
	var coupons = null;
	var getSettings = function() {
		$('#resultSection').hide();
		var shopCd = parseInt($('#shopCd').val()) || 0;
		if ( shopCd > 0 ) {
			mAlert('<p style="padding:20px;">処理中</p>');
			$.ajax({
				type: "POST",
				url: "./" + ajaxPage,
				data: "act=getSettings&shopCd=" + shopCd + "&domainSmatopiId=" + domainSmatopiId,
				dataType: 'json',
				success: function(resp) {
					if ( resp.status == 'ok' ) {
						tags = resp.tags;
						coupons = resp.coupons;
						$('#resultSection').show();
						updateResults();
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

	var prevDate;
	$(".datepicker").datepicker({
		changeMonth     : false,
		changeYear      : false,
		showButtonPanel : true,
		dateFormat      : 'yy-mm-dd',
		setDate         : '',
		todayHighlight: true,
		beforeShow: function(elem) {
			prevDate = elem.value;
			if (prevDate != '') {
				setTimeout(function () {
					// $(elem).datepicker("widget").find(".ui-datepicker-current[data-handler=today]").hide();
					var buttonPane = $(elem).datepicker( "widget" ).find( ".ui-datepicker-buttonpane" );
					var btn = $('<button class="ui-datepicker-current ui-state-default ui-priority-secondary ui-corner-all" type="button">クリア</button>');
					btn.off("click").on("click", function () {
						$.datepicker._clearDate(elem);
					});
					btn.appendTo( buttonPane );
				}, 1 );
			}
		},
		onSelect: function(selectedDate) {
			if (prevDate != selectedDate) {
				updateResults();
			}
		}
	}).attr("readonly", true);

	$('#result').on('click', '.item', function() {
		$('#details-view #details').empty();
		$('#main-view').hide();
		$('#details-view').show();
		updateDetails($(this).attr('data-cd'));
	});

	$('#back-button').on('click', function() {
		$('#details-view').hide();
		$('#main-view').show();
	});

	var updateResults = function() {
		mAlert('<p style="padding:20px;">処理中</p>');
		$.ajax({
			type: "POST",
			url: "./" + ajaxPage,
			data: $('#searchForm').serialize() + "&shopCd=" + $('#shopCd').val(),
			dataType: 'json',
			success: function(resp) {
				var html = '';
				if ( resp.status == 'ok' && resp.data) {
					for (const key in resp.data) {
						var item = resp.data[key];
						html += '<div class="item" data-cd="' + item.cd + '">';
						html += '<p class="title">' + item.title + '</p>';
						html += '<p class="subtitle">' + item.record_date + '</p>';
						html += '</div>';
					}
				} else {
					html = '<p style="color:#ee0000;text-align:center;">該当データがありません。</p>';
				}
				$('#result').html(html);
			},
			error: function() {
				alertDialog('警告', 'データの取得に失敗しました。');
			},
			complete: function() {
				mAlert();
			}
		})
	};

	var generateDetailBlock = function(title, body) {
		var html = '';
		html += '<div class="formBlock">';
		html += '<div class="formItem">';
		html += '<label class="formLabel formSmall">' + title + '</label>';
		html += '<div class="formSet">' + body + '</div>';
		html += '</div>';
		html += '</div>';

		return html;
	}

	var updateDetails = function(cd) {
		mAlert('<p style="padding:20px;">処理中</p>');
		$.ajax({
			type: "POST",
			url: "./" + ajaxPage,
			data: "act=getDetails&cd=" + cd,
			dataType: 'json',
			success: function(resp) {
				var html = '';
				if ( resp.status == 'ok' && resp.data) {
					var item = resp.data;
					html += generateDetailBlock('登録日時', item.record_date);
					html += generateDetailBlock('タイトル', item.title);
					html += generateDetailBlock('メッセージ', item.message);

					try {
						var requestData = JSON.parse(item.request_data);

						if (item.has_query == 1) {
							// 顧客管理・販売促進、顧客検索の「LINE配信」から配信した場合

							// クーポン
							if (requestData['coupon_id']) {
								var selectedCoupon = 'クーポン id ' + requestData['coupon_id'];
								if (coupons) {
									for (const key in coupons) {
										if (coupons[key].id == requestData['coupon_id']) {
											selectedCoupon = coupons[key].coupon_name;
											break;
										}
									}
								}
								html += generateDetailBlock('クーポン', selectedCoupon);
							}

							if (requestData['image_files'] && requestData['image_files'][0]) {
								html += generateDetailBlock('画像', '<img src="' + requestData['image_files'][0]['image_url'] + '"></img>');
							}

							if (requestData['movie_url']) {
								html += generateDetailBlock('動画', '<video controls><source src="' + requestData['movie_url'] + '" type="video/mp4"></video>');
							}
						} else {
							// スマトピ連携のメッセージ作成から配信した場合

							// -------------------------------
							// 配信タイプ
							var sendType = '全員';
							if (requestData['send_type'] == "2") {
								sendType = 'セグメント';
							} else if (requestData['send_type'] == "3") {
								sendType = 'タグ';
							}
							html += generateDetailBlock('配信対象', sendType);

							// タグ配信の場合
							if (requestData['select-tag']) {
								var selectedTag = 'タグ id ' + requestData['select-tag'];
								if (tags) {
									for (const key in tags) {
										if (tags[key].id == requestData['select-tag']) {
											selectedTag = tags[key].name;
											break;
										}
									}
								}
								html += generateDetailBlock('タグ', selectedTag);
							}

							// セグメント配信の場合
							if (requestData['birthmonth_flag'] && requestData['birthmonth_flag'] == 1) {
								html += generateDetailBlock('配信の日時指定をする', 'ON');
							}

							if (requestData['age_flag'] && requestData['age_flag'] == 1 && requestData['age_min'] && requestData['age_max']) {
								html += generateDetailBlock('年齢', requestData['age_min'] + '歳から' + requestData['age_max'] + '歳まで');
							}

							var genders = new Array();
							if (requestData['gender_men_flag'] && requestData['gender_men_flag'] == 1) genders.push('男性');
							if (requestData['gender_women_flag'] && requestData['gender_women_flag'] == 1) genders.push('女性');
							if (requestData['gender_other_flag'] && requestData['gender_other_flag'] == 1) genders.push('その他');
							if (genders.length > 0) {
								html += generateDetailBlock('性別', genders.join('・'));
							}

							// -------------------------------
							if (requestData['repeat_setting']) {
								var repeatSetting = requestData['repeat_setting'] == 1 ? 'あり' : 'なし';
								html += generateDetailBlock('繰り返し配信', repeatSetting);
							}

							if (requestData['repeat_setting'] == 1) {
								// 繰り返し配信　あり
								html += generateDetailBlock('配信時刻', requestData['scheduled_date_time']);
								html += generateDetailBlock('配信開始日', requestData['scheduled_date_day']);

								if (requestData['type_prefectures']) {
									var typePrefectures = '';
									if (requestData['type_prefectures'] == 1) typePrefectures = '週間';
									else if (requestData['type_prefectures'] == 2) typePrefectures = 'ヶ月';
									else if (requestData['type_prefectures'] == 3) typePrefectures = '年間';
									else if (requestData['type_prefectures'] == 4) typePrefectures = '日';
									html += generateDetailBlock('繰り返しタイプ', typePrefectures);
								}

								if (requestData['week']) {
									var dow = ['', '月', '火', '水', '木', '金', '土', '日'];
									var days = new Array();
									for (const key in requestData['week']) {
										if (dow[requestData['week'][key]]) {
											days.push(dow[requestData['week'][key]]);
										}
									}
									html += generateDetailBlock('配信する曜日', days.join('・'));
								}

								if (requestData['interval_num']) {
									html += generateDetailBlock('繰り返し間隔（数値）', requestData['interval_num']);
								}

								if (requestData['interval_unit']) {
									var intervalUnit = '';
									if (requestData['interval_unit'] == 1) intervalUnit = '週';
									else if (requestData['interval_unit'] == 2) intervalUnit = '月';
									else if (requestData['interval_unit'] == 3) intervalUnit = '年';
									else if (requestData['interval_unit'] == 4) intervalUnit = '日';
									else if (requestData['interval_unit'] == 6) intervalUnit = 'カスタム';
									html += generateDetailBlock('繰り返し間隔（単位）', intervalUnit);
								}

								if (requestData['interval_unit'] == 6 && requestData['end_date']) {
									var endDateType = '指定しない';
									if (requestData['end_date'] == 1) endDateType = '日付で終了';
									else if (requestData['end_date'] == 1) endDateType = '回数で終了';
									html += generateDetailBlock('配信終了日タイプ', endDateType);

									if (requestData['remining']) {
										generateDetailBlock('繰り返しの回数', requestData['remining']);
									} else if (requestData['end-date']) {
										generateDetailBlock('配信終了日', requestData['end-date']);
									}
								}

								if (requestData['holiday_flag']) {
									html += generateDetailBlock('休日配信', requestData['holiday_flag'] == 1 ? 'する' : 'しない');
								}
							} else {
								// 繰り返し配信　なし
								if (requestData['check_reserve_send'] && requestData['scheduled_date_day'] && requestData['scheduled_date_time']) {
									html += generateDetailBlock('配信時間', '時間指定');
									html += generateDetailBlock('配信日時', requestData['scheduled_date_day'] + ' ' + requestData['scheduled_date_time']);
								} else {
									html += generateDetailBlock('配信時間', '即時配信');
								}
							}

							if (requestData['imageLink'] && requestData['imageLink'][0]) {
								html += generateDetailBlock('画像', '<img src="' + requestData['imageLink'][0] + '"></img>');
							}
						}
					}
					catch(e) {}

				} else {
					html = '<p style="color:#ee0000;text-align:center;">該当データがありません。</p>';
				}
				$('#details-view #details').html(html);
			},
			error: function() {
				alertDialog('警告', 'データの取得に失敗しました。');
			},
			complete: function() {
				mAlert();
			}
		})
	}

	// updateResults();
});
