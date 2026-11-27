// ------------------------------------------------------------
// 処理記述部分
var selectedShopCd = selectedStaffCd = selectedMenu = requiredTime = null;
var lastPos = null;
var reloadDataArray = new Array();
var shopSettings = null;

$(function(){
	$('#btnMyReserves').on('click', function(event){
		location.href="./check.php?" + reloadDataArray.join('&');
	});

	$('#shop_choice input').iCheck({
		checkboxClass: 'icheckbox_square-green',
		radioClass: 'iradio_square-green'
	});

	var reserve = new Reserve();

	$('#shop_choice').on('ifClicked', 'input', function(event){
		if (selectedShopCd == $(this).val()) return; // 同じボタン押したら、何もしない
		$('#freeReserveBtn').hide();
		selectedShopCd = $(this).val();
		selectedStaffCd = selectedMenu = null; // リセットする。
		reserve.setShopStaffChoices();
	});

	$('#staff_choice').on('ifClicked', 'input', function(event){
		if (selectedStaffCd == $(this).val()) return; // 同じボタン押したら、何もしない
		selectedStaffCd = $(this).val();
		reserve.setMenuChoices();
		$('#freeReserveBtn').show();
		$('#reserveStatusDisp').hide();
	});

	$('#menu_choice').on('ifChecked ifUnchecked', 'input', function(event){
		var menuCnt = $('#menu_choice input[type=checkbox]:checked').length;
		if ( menuCnt > 0) {
			selectedMenu = new Array();
			$('#menu_choice input[type=checkbox]:checked').each(function(){
				selectedMenu.push($(this).val())
			});
		} else {
			selectedMenu = null;
		}

		if (selectedStaffCd) {
			// すでにスタッフが選択されている場合
			if ( menuCnt > 0 ) reserve.setMenuStaffChoices();
			else reserve.getSchedule();
		} else {
			// スタッフが選択されてない場合
			if ( menuCnt == 0 ) {
				reserve.setShopStaffChoices();
			} else {
				reserve.setMenuStaffChoices();
			}
		}
	});

	$('#scheduleResult').on('click', '.scheduleChangeNext', function(event) {
		reserve.scheduleChange('next');
	})

	$('#scheduleResult').on('click', '.scheduleChangePrev', function(event) {
		reserve.scheduleChange('prev');
	})

	$('#scheduleResult').on('click', '#changeDateMonthNext', function(event) {
		reserve.scheduleChangeMonth('next');
		$('#changeDateMonthPrev').show();
	})

	$('#scheduleResult').on('click', '#changeDateMonthPrev', function(event) {
		reserve.scheduleChangeMonth('prev');
	})

	$('#scheduleResult').on('click', '.reserveIcon', function(event) {
		$('#scheduleResult .active').removeClass('active');
		$(this).addClass('active');

		$('#reserveBtnArea').show();
	})

	$('#freeReserveBtn').on('click', function(event) {
		$('#staff_choice input').iCheck('uncheck');
		selectedStaffCd = null;
		$('#freeReserveBtn').hide();
		$('#reserveStatusDisp').show();

		var menuCnt = 0;
		if ( !shopSettings.allowFreeReserve ) {
			$('#menu_choice input').iCheck('uncheck');
		} else {
			menuCnt = $('#menu_choice input[type=checkbox]:checked').length;
		}

		if ( menuCnt > 0 ) {
			reserve.getSchedule();
		} else {
			reserve.setShopStaffChoices();
		}
	})
	// ---------------------------------
	// 【予約内容の確認】ボタン
	$('#reserveBtn').on('click', function(event){
		var t = $('#scheduleResult #calendarArea .active');

		if ( t.length == 0 ) {
			myAlert("予約日時を選択してください。", "警告");
			return false;
		}

		var html = '';
		html += '<div>';
		html += '<p>店舗：' + shopArray[selectedShopCd].name + '</p>';
		if (selectedStaffCd) {
			html += '<p>スタッフ：' + $('label[for=staff_cd_' + selectedStaffCd + ']').text() + '</p>';
		}
		var menu = '';
		$('#menu_choice input[type=checkbox]:checked').each(function(index, item){
			if ( menu != '' ) menu += '、';
			menu += $('label[for=item_' + $(this).val() + ']').text();

			var price = ( parseInt($(this).attr('price'), 10) || 0 );
			if ( shopSettings.showMenuPrice && price ) {
				menu += '(￥' + number_format(price) + ')';
			}
		});
		html += '<p>メニュー：' + menu + '</p>';

		html += '<p>';
		html += '予約日時：' + t.data('date') + '&nbsp;' + t.data('time') + '～';
		if ( selectedStaffCd && shopSettings.showEstimatedTime ) {
			html += '&nbsp;(目安時間：' + requiredTime[selectedStaffCd] + '分)';
		}
		html += '</p>'
		html += '</div>';
		$('#confirmDialog #reserveDetails').html(html);

		showConfirmDialog();
	});
	// ---------------------------------

	// ---------------------------------
	// 【予約する】ボタン（予約確認ダイアログ内）
	$('#btnSaveOk').on('click', function(event) {
		myConfirm("この内容でよろしいですか？<br/>予約しない場合【キャンセル】ボタンを押してください。", "予約登録処理", function() {
			var t = $('#scheduleResult #calendarArea .active');
			var sendData = 'sess=' + $('#sess').val();
			sendData += "&shop_cd=" + selectedShopCd + "&" + $('#menu_choice input[type=checkbox]:checked').serialize();
			if ( selectedStaffCd ) sendData += '&staff_cd=' + selectedStaffCd;
			sendData += '&selectedDate=' + t.data('date') + '&selectedTime=' + t.data('time');
			sendData += '&' + $('#customerForm').serialize();
			showLoadingDivision();

			setTimeout(function(){
				$.ajax({
					type     : "post",
					url      : "./ajax_reserveRegister.php",
					data     : sendData,
					cache    : false,
					//async    : false,
					timeout  : 15000, // タイムアウト：15秒
					dataType : "json"
				}).done(function (res) {
					if (res.status == "ok") {
						//$("#confirmDialog").modal('hide');
						hideConfirmDialog();
						myAlert("予約登録完了しました。", "予約登録", function(){
							location.href="./index.php?" + reloadDataArray.join('&');
						});
					} else if (res.status == "error") {
						var _callback = null;
						if ( res.refreshSchedule ) {
							hideConfirmDialog();
							_callback = function() {
								reserve.getSchedule();
							}
						}
						myAlert(res.errorMsg, "警告", _callback);
					}
				}).fail(function() {
					myAlert("接続できませんでした。", "警告");
				}).always(function() {
					hideLoadingDivision();
				});
			}, 100);
		});
	});

	$('#btnClose').on('click', function(event){
		hideConfirmDialog();
	});
	// ---------------------------------

	// ---------------------------------
	// 初期の店舗選択
	if ( defaults.shop_cd ) {
		reloadDataArray.push('shop_cd=' + defaults.shop_cd);
		// $_REQUEST['shop_cd']がある時
		$('label[for=shop_cd_' + defaults.shop_cd + ']').click();
		delete(defaults.shop_cd);
	} else {
		// 1個目の店舗を選択する
		$('#shop_choice ins:first').click();
	}
	// ---------------------------------
});
// ------------------------------------------------------------

var showConfirmDialog = function() {
	lastPos = {'top' : window.scrollY, 'left' : window.scrollX};
	$(".container, #reserveBtnArea").hide();
	$("#confirmDialog").show();
	//$(document).scrollTop(0).scrollLeft(0);
	window.scrollTo(0, 0);
}

var hideConfirmDialog = function() {
	$("#confirmDialog").hide();
	$(".container, #reserveBtnArea").show();
	try {
		window.scrollTo(lastPos.left, lastPos.top);
	} catch (ex) {}
}

// 予約クラス
function Reserve() {
	var getPostDate = function() {
		return "start_date=" + $('#start_date').val() + "&end_date=" + $('#end_date').val();
	}

	// ===============================================================================
	// 検索部分
	this.setShopStaffChoices = function() {
		$("#staff_choice, #menu_choice").html( '<span>Loading...</span>' );

		var sendData = getPostDate() + "&shop_cd=" + selectedShopCd;
		getChoices(sendData);
	}

	this.setMenuChoices = function() {
		// スタッフのラジオボタンを押した場合。（指定したスタッフのメニューを取得）
		$("#menu_choice").html( '<span>Loading...</span>' );

		var sendData = getPostDate() + "&shop_cd=" + selectedShopCd + "&staff_cd=" + selectedStaffCd + "&kind=menu";
		getChoices(sendData);
	}

	var setMenuStaffChoices = this.setMenuStaffChoices = function() {
		if ( $('#menu_choice input[type=checkbox]:checked').length == 0 ) {
			selectedMenu = null;
			return;
		}
		// スタッフが選択されてない状態でメニューをチェックするとき。（指定したメニューをできるスタッフを取得）
		$("#staff_choice").html( '<span>Loading...</span>' );

		var sendData = getPostDate() + "&shop_cd=" + selectedShopCd + "&" + $('#menu_choice input[type=checkbox]:checked').serialize() + "&kind=staff";
		getChoices(sendData);
	}

	var getChoices = function(sendData) {

		$('#scheduleResult').empty();
		$('#reserveBtnArea').hide();

		showLoadingDivision();

		setTimeout(function(){
			$.ajax({
				type     : "post",
				url      : "./ajax_getChoiceData.php",
				data     : sendData,
				cache    : false,
				//async    : false,
				timeout  : 3000, // タイムアウト：3秒
				dataType : "json"
			}).done(function (res) {
				if (res.status == "error") {
					myAlert(res.errorMsg, "警告");
				} else {
					var freeReserveName = '';

					if (res.data.settings) {
						setAnnotations(res.data.settings);
						if ( res.data.settings.businessHoursReservationError ) {
							$('#staffArea, #menuArea').hide();
							$('#errorMsg').html('現在のお時間のご予約はお電話でお願いいたします。<br/><a href="tel:' + shopArray[selectedShopCd].tel + '">' + shopArray[selectedShopCd].tel + '</a>');
							$('#errorMsgArea').show();
							return false;
						} else {
							$('#errorMsgArea').hide();
							if ( res.data.settings.allowFreeReserve && !res.data.settings.allowNominationWhenFreeReserveIsAllowed ) $('#staffArea').hide();
							else $('#staffArea').show();
							$('#menuArea').show();
						}

						if (res.data.settings.freeReserveName) {
							freeReserveName = res.data.settings.freeReserveName;
						}
					}

					if (res.data.staff) setStaffList(res.data.staff, freeReserveName);
					if ( defaults.staff_cd && $('label[for=staff_cd_' + defaults.staff_cd + ']').length > 0 ) {
						// $_REQUEST['staff_cd']がある時
						$('label[for=staff_cd_' + defaults.staff_cd + ']').click();
					} else {
						if (res.data.menu) setMenuList(res.data.menu);
						getSchedule();
					}

					if ( defaults.staff_cd ) delete(defaults.staff_cd);
/*
					if (res.data.menu) setMenuList(res.data.menu);
					if ( defaults.staff_cd ) {
						// $_REQUEST['staff_cd']がある時
						$('label[for=staff_cd_' + defaults.staff_cd + ']').click();
						delete(defaults.staff_cd);
					} else {
						getSchedule();
					}
*/
				}
			}).fail(function() {
				myAlert("接続できませんでした。", "警告");
			}).always(function() {
				hideLoadingDivision();
			});
		}, 100);
	}

	var setAnnotations = function(settings) {
		shopSettings = settings;
		$('#selectShopAnnotation').html(shopSettings.selectShopAnnotation || "");
		$('#selectStaffAnnotation').html(shopSettings.selectStaffAnnotation || "");
		$('#selectMenuAnnotation').html(shopSettings.selectMenuAnnotation || "");
		$('#customerFormAnnotation').html(shopSettings.customerFormAnnotation || "");
		if (shopSettings.allowFreeReserve) $('#freeReserveBtn').text('指名無しで予約する');
		else $('#freeReserveBtn').text('条件をリセットする');
	}

	var setStaffList = function(staffData, freeReserveName) {
		if ( !shopSettings.allowFreeReserve ) {
			$('#reserveStatusDisp').text('スタッフを選択してください。');
		} else {
			if ( freeReserveName && freeReserveName != '' ) {
				$('#reserveStatusDisp').text('現在、' + freeReserveName + 'の状態です。');
			} else {
				$('#reserveStatusDisp').text('現在、指名なしの状態です。');
			}
		}

		if (selectedStaffCd) {
			$('#reserveStatusDisp').hide();
		} else {
			$('#reserveStatusDisp').show();
		}
		var html = '';
		if ( staffData.length == 0 ) {
			html += '<div class="none_data">ご希望の条件に沿うスタッフが不在なため、条件を変更ください。</div>';
		} else {
			html += '<div class="reserveList">';
			html += '<div class="parentContainer">';
			html += '<div class="listContainer">';
			html += '<div class="box">';
			for (i in staffData) {
				var checked = ( selectedStaffCd == staffData[i].cd ) ? ' checked="checked"' : '';
				var imgSrc = staffData[i].hasImage ? '/hp/uploads/c5/staff/' + staffData[i].cd + '/' + staffData[i].cd + '.jpg?t=' + (staffData[i].imgLastModify || 0) : '/reserve/img/nophoto.gif';
				html += '<div class="staffBox">';
				html += '<div class="staffImg" style="background-image:url(' + imgSrc + ');background-size:cover;background-position: center center;"></div>'
				html += '<div class="radio">';
				html += '<input type="radio" name="staff_cd" id="staff_cd_' + staffData[i].cd + '" value="' + staffData[i].cd + '"' + checked + ' />';
				html += '<label for="staff_cd_' + staffData[i].cd + '">';
				html += staffData[i].name;
				html += '</label>';
				html += '</div>';
				html += '</div>'
			}
			html += '</div>';
			html += '</div>';
			html += '</div>';
			html += '</div>';
		}
		$('#staff_choice input').iCheck('destroy');
		$("#staff_choice").html( html );

		$('#staff_choice input').iCheck({
			checkboxClass: 'icheckbox_square-green',
			radioClass: 'iradio_square-green'
		});
	}

	var setMenuList = function(menuData) {
		var html = '';
		if ( menuData.length == 0 ) {
			html += '<div class="none_data">ご希望の条件に沿うメニューが抽出出来ないため、条件を変更ください。</div>';
		} else {
			html += '<div class="reserveList">';
			html += '<div class="parentContainer">';
			html += '<div class="listContainer">';
			html += '<div class="box">';
			for (i in menuData) {
				var m = 'item_' + menuData[i].menu_cd;
				var checked = '';
				if ( defaults.menu_cd ) {
					// $_REQUEST['menu_cd'][]がある時
					if ( defaults.menu_cd.indexOf(menuData[i].menu_cd) >= 0 ) checked = ' checked="checked"';
				} else if ( selectedMenu ) {
					if ( selectedMenu.indexOf(menuData[i].menu_cd) >= 0 ) checked = ' checked="checked"';
				} else {
					if ( $('#menu_choice input[id=' + m + ']').is(':checked') ) checked = ' checked="checked"';
				}
				var imgSrc = menuData[i].hasImage ? '/hp/uploads/c9/yoyaku_koumoku/' + menuData[i].menu_cd + '/1_s.jpg?t=' + (menuData[i].imgLastModify || 0) : '/reserve/img/nophoto.gif';
				var optionMsg = menuData[i].type_flg == '2' ? '<span style="background-color:rgba(0,0,0,.4);color:#ffffff;display:inline-block;padding:2px;">オプション</span>' : '';
				html += '<div class="menuBox">';
				html += '<div class="menuImg" style="background-image:url(' + imgSrc + ');background-size:cover;background-position: center center;">' + optionMsg + '</div>'
				html += '<div class="radio">';
				html += '<input type="checkbox" name="menu_cd[]" id="' + m + '" value="' + menuData[i].menu_cd + '" price="' + (menuData[i].price || 0) + '"' + checked + ' />';
				html += '<label for="' + m + '">';
				html += menuData[i].title;
				html += '</label>';
				if ( shopSettings.showMenuPrice && menuData[i].price ) {
					html += '<p class="price">￥' + number_format(menuData[i].price) + '</p>';
				}
				html += '</div>';
				html += '</div>'
			}
			html += '</div>';
			html += '</div>';
			html += '</div>';
			html += '</div>';
		}
		$('#menu_choice input').iCheck('destroy');
		$("#menu_choice").html( html );

		$('input').iCheck({
			checkboxClass: 'icheckbox_square-green',
			radioClass: 'iradio_square-green'
		});
		if ( defaults.menu_cd ) {
			selectedMenu = defaults.menu_cd;
			delete( defaults.menu_cd );
			setMenuStaffChoices();
		}
	}
	// ===============================================================================


	// ===============================================================================
	// 台帳部分
	var getSchedule = this.getSchedule = function() {
		$('#scheduleResult').empty();
		if ( $('#menu_choice input[type=checkbox]:checked').length == 0 || ( !selectedStaffCd && !shopSettings.allowFreeReserve) ) {
			// メニューが選択されてない状態なら進めないように。。。
			$('#reserveBtnArea').hide();

			return false;
		}
		var sendData = $("#reserve_form").serialize() + "&customer_cd=" + $('#customer_cd').val();

		showLoadingDivision();

		setTimeout(function(){
			$.ajax({
				type     : "post",
				url      : "./ajax_getScheduleData.php",
				data     : sendData,
				cache    : false,
				//async    : false,
				timeout  : 30000, // タイムアウト：30秒
				dataType : "json"
			}).done(function (res) {
				if (res.status == "error") {
					myAlert(res.errorMsg, "警告");
				} else {
					requiredTime = res.data.requiredTime;
					renderSchedule(res.data.schedule, res.data.untilReserveDate);
				}
			}).fail(function() {
				myAlert("接続できませんでした。", "警告");
			}).always(function() {
				hideLoadingDivision();
			});
		}, 100);
	}

	// untilReserveDateは予約できる日付（「WEB予約可能上限」設定との関係ある）
	var renderSchedule = function(data, untilReserveDate) {
		$('#reserveBtnArea').hide();
		var html = '';
		var head = '';
		var tr = new Object();
		var dateList = Object.keys(data);
		var	dateFirst = dateList[0];
		var dateLast  = dateList[dateList.length - 1];
		for (var i = 0; i < dateList.length; i++) {
			date = dateList[i];
			if ( i == 0 ) {
				head += '<th>';
				var d1 = Math.floor(new Date().getTime() / 1000);
				var d2 = Math.floor(new Date(date.replace(/-/g, '/')).getTime() / 1000);
				if ( d1 < d2 ) {
					head += '<a href="javascript:void(0)" class="scheduleChangePrev">前へ</a>';
				}
				head += '</th>';
			}
			var temp = date.split('-');
			head += '<th>' + temp[1] + '月' + temp[2] + '日<br/>(' + data[date].youbi + ')</th>';
			if ( i == dateList.length - 1 ) {
				head += '<th>';
				if ( date < untilReserveDate ) head += '<a href="javascript:void(0)" class="scheduleChangeNext">次へ</a>';
				head += '</th>';
			}

			timetable = data[date].timetable;
			for (time in timetable) {
				var symbol = '';

				if ( timetable[time]['available'] ) {
					symbol += '<button type="button" aria-label="Left Align" class="btn btn-default reserveIcon btn-success" data-date="' + date + '" data-time="' + time + '">';
					symbol += '<span class="glyphicon glyphicon-ok-circle" aria-hidden="true"></span>';
					symbol += '</button>';
				} else {
					symbol += '<button type="button" aria-label="Left Align" class="btn btn-default reserveIcon btn-remove" disabled="disabled">';
					symbol += '<span class="glyphicon glyphicon-remove-circle" aria-hidden="true"></span>'
					symbol += '</button>';
				}

				if ( typeof(tr[time]) == 'undefined' ) tr[time] = '<th>' + time + '</th>';
				tr[time] += '<td>' + symbol + '</td>';

				if ( i == dateList.length - 1 ) tr[time] += '<th>' + time + '</th>';
			}
		}

		html += '<h2><span aria-hidden="true" class="glyphicon glyphicon-calendar"></span> 日時選択</h2>';
		html += '<div id="selectTimeAnnotation" class="annotation">';
		try {
			html += shopSettings.selectTimeAnnotation || '';
		} catch(e) {}
		html += '</div>';
		html += '<div id="changeDateMonth" class="changeDateMonthArea">';

		var d1 = Math.floor(new Date().getTime() / 1000);
		var d2 = Math.floor(new Date(dateFirst.replace(/-/g, '/')).getTime() / 1000);
		if ( d1 < d2 ) {
			html += '<button type="button" id="changeDateMonthPrev" class="btn btn-info changeDateMonthBtn prev">前の月</button>';
		}

		if ( dateLast < untilReserveDate ) {
			html += '<button type="button" id="changeDateMonthNext" class="btn btn-info changeDateMonthBtn next" >次の月</button>';
		}

		html += '</div>';
		html += '<div id="calendarArea" class="table-responsive">';
		html += '<table id="calendar">';
		html += '<tr>' + head + '</tr>';
		for (var i = 0, k = Object.keys(tr); i < k.length; i++ ) {
			key = k[i];
			if ( i > 0 && i % 8 == 0 && k.length - i > 4 ) html += '<tr>' + head + '</tr>';
			html += '<tr>' + tr[key] + '</tr>';
		}
		html += '<tr>' + head + '</tr>';
		html += '</table>';
		html += '</div>';

		$('#scheduleResult').html(html);
	}
	// ===============================================================================

	var scheduleChange = this.scheduleChange = function(to) {
		var days = 7;
		if ( to == "prev" ) days *= -1;

		var d1 = new Date($('#start_date').val());
		d1.setDate(d1.getDate() + days);
		$('#start_date').val(d1.toISOString().split('T')[0]);

		var d2 = new Date($('#end_date').val());
		d2.setDate(d2.getDate() + days);
		$('#end_date').val(d2.toISOString().split('T')[0]);

		getSchedule();
	}

	var scheduleChangeMonth = this.scheduleChangeMonth = function(to) {

		var days = 7;
		var today = new Date();

		if ( to == "prev" ) {

			var d1 = new Date($('#start_date').val());
			d1.setMonth(d1.getMonth() - 1)
			$('#start_date').val(d1.toISOString().split('T')[0]);

			var d2 = new Date($('#start_date').val());
			d2.setDate(d1.getDate() + days);
			$('#end_date').val(d2.toISOString().split('T')[0]);

		} else {

			var d1 = new Date($('#start_date').val());
			d1.setMonth(d1.getMonth() + 1)
			$('#start_date').val(d1.toISOString().split('T')[0]);

			var d2 = new Date($('#start_date').val());
			d2.setDate(d1.getDate() + days);
			$('#end_date').val(d2.toISOString().split('T')[0]);

		}
		getSchedule();
	}

}

function number_format(num) {
	return num.toString().replace(/([0-9]+?)(?=(?:[0-9]{3})+$)/g , '$1,');
}
