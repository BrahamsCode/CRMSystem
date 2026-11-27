<?php
/*
 * Legacy: appview/member_regist.php. Vista PHP plana (no Blade): salida byte a byte la del legacy (CRLF).
 */
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>新規会員登録フォーム</title>
		<link href="bower_components/bootswatch-dist/css/bootstrap.min.css" rel="stylesheet">
		<link href="./css/add.css" rel="stylesheet">
		<script src="bower_components/jquery/dist/jquery.min.js"></script>
		<script src="js/jquery.jpostal.js"></script>
		<script src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
		<script src="bower_components/bootstrap-validator/dist/validator.min.js"></script>
		<script src="js/bootstrap-dialog.min.js"></script>
		<script src="js/common.js"></script>
		<script>
		$(window).ready( function() {

			var setting_add = {};
			if ( $('select[name=pref]')[0] ) {
				setting_add['select[name=pref]'] = '%3';
			}
			if ( $('#add')[0] ) {
				setting_add['#add'] = '%4%5';
			}
			if ( $('#zip')[0] ) {
				$('#zip').jpostal({
					click : '#ajax3zip_zip',
					postcode : [ '#zip' ],
					address : setting_add
				});
			}

			setting_add = {};
			if ( $('select[name=home_pref]')[0] ) {
				setting_add['select[name=home_pref]'] = '%3';
			}
			if ( $('#home_city')[0] ) {
				setting_add['#home_city'] = '%4';
			}
			if ( $('#home_town')[0] ) {
				setting_add['#home_town'] = '%5';
			}
			if ( $('#home_zip')[0] ) {
				$('#home_zip').jpostal({
					click : '#ajax3zip_home_zip',
					postcode : [ '#home_zip' ],
					address : setting_add
				});
			}

			setting_add = {};
			if ( $('select[name=work_pref]')[0] ) {
				setting_add['select[name=work_pref]'] = '%3';
			}
			if ( $('#work_city')[0] ) {
				setting_add['#work_city'] = '%4';
			}
			if ( $('#work_town')[0] ) {
				setting_add['#work_town'] = '%5';
			}
			if ( $('#work_zip')[0] ) {
				$('#work_zip').jpostal({
					click : '#ajax3zip_work_zip',
					postcode : [ '#work_zip' ],
					address : setting_add
				});
			}

		});
		</script>
		<style>
		.bootstrap-dialog.type-primary .modal-header {background-color: #428bca;}
		.bootstrap-dialog-title {color: #fff; display: inline-block; font-size: 16px;}
		</style>
	</head>
	<body>
		<div class="col-xs-12 col-xs-offset-0 well"<?php if ($res['status'] == "ok") echo ' style="display:none;"'; ?>>
			<legend>新規会員登録</legend>
			<?php if ( isset( $req["touroku_kubun"] ) ) { ?>
				<div>
					<a href="./member_init.php?introducer_cd=<?=$h(($req["introducer_cd"] ?? null))?>">&lt; 戻る</a>
				</div>
			<?php } ?>
			<div class="text-center">
				<small>
					<?=$shopMasterName?>(以下、当社)に送信いただいた個人情報は、会員認証のため、その他<a href="./privacyPage.php?domain=<?=$h($domain)?>">プライバシーポリシー</a>に定められた用途に従って利用いたします。
				</small>
			</div><br>
			<?php if (isset($res['status'])): ?>
				<?php if ($res['status'] == "ok"): ?>
				<div class="alert alert-success">
					会員登録が完了しました
				</div>
				<script>
				myFunction = function() {

					var message = {"action":"appCloseDialog", "customer_cd":"<?=$res['data']['cd']?>", "customer_login_id":"<?=$res['data']['login_id']?>", "touroku_kubun":"<?=$res['data']['touroku_kubun']?>"};
					try {
						if (typeof androidCallback.postMessage == 'function') {
							androidCallback.postMessage( JSON.stringify(message) );
						}
						else {
							webkit.messageHandlers.callbackHandler.postMessage( message );
						}
					}
					catch (e) {
						webkit.messageHandlers.callbackHandler.postMessage( message );
					}

				}
			<?php if ( $alertMessage ) { ?>
				myAlert('<?=$alertMessage?>', 'ポイント付与', myFunction);
			<?php } else { ?>
				myFunction();
			<?php } ?>
				</script>
				<?php endif; ?>
				<?php if ($res['status'] == "error"): ?>
				<div class="alert alert-warning">
					<?=$res['errMsg']?>
				</div>
				<?php endif; ?>
			<?php endif; ?>

			<form id="signUpForm" action="/appview/member_regist.php" method="post" data-toggle="validator" role="form">
				<div class="form-group has-success">
					<label for="shop" class="control-label"><i class="glyphicon glyphicon glyphicon-home"></i> 登録店舗</label>

					<?php if ( count( $shopList ) == 1 ) { ?>
						<?php if ( isset( $req['shop_cd'] ) ) {?>
							<p><?=($shopList[ (int)($req['shop_cd'] ?? null) ]['shop_name'] ?? null)?></p>
							<input type="hidden" name="shop_cd" value="<?=(int)($req['shop_cd'] ?? null)?>">
						<?php } else { ?>
							<?php reset($shopList);?>
							<?php $key = key( $shopList ); ?>
							<p><?=$shopList[ $key ]['shop_name']?></p>
							<input type="hidden" name="shop_cd" value="<?=$key?>">
						<?php } ?>
					<?php } else { ?>
						<select name="shop_cd" class="form-control">
							<?php foreach ((array)$shopList as $key => $val): ?>
							<option value="<?=$key?>"<?php if (($req['shop_cd'] ?? null) == $key) echo ' selected'; ?>><?=$val['shop_name']?></option>
							<?php endforeach; ?>
						</select>
					<?php } ?>
				</div>
				<?=$form?>
				<?php if ( isset( $req["touroku_kubun"] ) && !empty( $req["touroku_kubun"] ) ) { ?>
				<input type="hidden" name="touroku_kubun" value="<?=$h(($req["touroku_kubun"] ?? null))?>">
				<?php } ?>
				<input type="hidden" name="domain" value="<?=$h($domain)?>">
				<input type="hidden" name="introducer_cd" value="<?php if ($introducerData) echo $introducerData['cd']; ?>">
				<button type="button" id="signUpBtn" class="btn btn-success">登録する</button>
			</form>
		</div>
		<script>
		$(function() {
			$('#signUpBtn').on('click', function() {
				$('#signUpForm').submit();
			});
			$('#signUpForm').validator().on('submit', function(e) {
				if (!e.isDefaultPrevented()) {
					$('#signUpBtn').attr('disabled', 'disabled');
				}
			});
		});
		</script>
	</body>
</html>
