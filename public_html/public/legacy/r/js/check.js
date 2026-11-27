$(function(){
	$('.btnCancelReserve').on('click', function(event){
		var cd = $(this).attr('cd');
		var scd = $(this).attr('scd');
		myConfirm("取消処理実行してもよろしいですか？<br/>取消しない場合【キャンセル】ボタンを押してください。", "予約取消処理", function() {
			var sendData = 'sess=' + $('#sess').val();
			sendData += "&shop_cd=" + scd + "&cd=" + cd;
			showLoadingDivision();

			setTimeout(function(){
				$.ajax({
					type     : "post",
					url      : "./ajax_reserveDelete.php",
					data     : sendData,
					cache    : false,
					//async    : false,
					timeout  : 15000, // タイムアウト：15秒
					dataType : "json"
				}).done(function (res) {
					if (res.status == "error") {
						myAlert(res.errorMsg, "警告");
					} else {
						myAlert("取消完了しました。", "予約取消処理", function(){
							location.reload();
						})
					}
				}).fail(function() {
					myAlert("接続できませんでした。", "警告");
				}).always(function() {
					hideLoadingDivision();
				});
			}, 100);
		});
	});
});
