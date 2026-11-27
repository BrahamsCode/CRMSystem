$(document).ready(function () {
	var timeline;
	var targetDate = td.substr(0, 4) + "/" + td.substr(4, 2) + "/" + td.substr(6, 2);

	// CSS
	$('html > head').append('<style>.vis-item .vis-delete {display: none !important;}</style>');

	// データを取得
	$.ajax({
		type: "POST",
		dataType: "json",
		url: "ajax_getReservationData.php",
		data: "shopCd=" + shopCd + "&targetDate=" + targetDate,
		success: function (response) {
			if ( response.status == "error" ) {
				alert(response.errorMsg || 'エラー発生しました。');
			} else {
				var data = response.data;
				var options = {
					start: data.config.start_timestamp,
					end: data.config.end_timestamp,
					min: data.config.start_timestamp,
					max: data.config.end_timestamp,
					orientation: {
						axis: 'top',
						item: 'top'
					},
					zoomMin: 3600000 * 2,
					zoomMax: 3600000 * 10,
					margin: {
						item: 0,
						axis: 0,
						item: {
							horizontal: -1
						}
					},
					groupTemplate: function (group) {
						var html = '';
						html += '<div class="staffData" cd="' + group.id + '">';
						html += '<a class="underline" href="time_table_staff.php?shop=' + shopCd + '&staff="' + group.id + '&d=' + td + '>' + group.content + '</a>';
						html += '<br/>';
						html += '<a class="popWindowAnchor btnStyle underline" onclick="wopen(\'' + group.id + '\');">予約</a>';
						html += '<a class="popWindowAnchor btnStyle underline" onclick="wopen_config(\'' + group.id + '\', \'\');">設定</a>';
						html += '</div>';
						var container = document.createElement('div');
						container.innerHTML = html;
						return container;
					},
					snap: function (date, scale, step) {
						var hour = 5 * 60 * 1000; // 移動時の間隔を5分にする
						return Math.round(date / hour) * hour;
					},
					editable: true,
					showCurrentTime: true
				};

				var container = document.getElementById('visualization');
				var groups = new vis.DataSet(generateGroupFromStaffList(data.staff));
				var items = new vis.DataSet(generateItemsFromReservations(data.reservations));

				timeline = new vis.Timeline(container, items, groups, options);
				window.debug = timeline;
			}
		},
		error: function() {
			console.log('エラー発生しました！！！');
		}
	});

	var generateGroupFromStaffList = function(staffList) {
		var resp = new Array();
		try {
			for (var key in staffList) {
				var item = new Object();
				item.id = staffList[key].cd;
				item.content = staffList[key].name;
				item.order = key;
				resp.push(item);
			}
		} catch (e) {}

		return resp;
	};

	var generateItemsFromReservations = function(reservations) {
		var resp = new Array();
		try {
			for (var key in reservations) {
				var item = new Object();
				item.id = reservations[key].tb_yoyaku_cd;
				item.content = reservations[key].name;
				item.start = reservations[key].start_timestamp;
				item.end = reservations[key].end_timestamp;
				item.group = reservations[key].tb_staff_cd;
				item.className = 'reservationObj ';
				if ( !reservations[key].tb_customer_cd ) item.className += 'guest';
				else item.className += 'member';
				resp.push(item);
			}
		} catch (e) {}

		return resp;
	}
});
