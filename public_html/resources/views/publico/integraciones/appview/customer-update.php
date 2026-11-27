<?php
/*
 * Legacy: appview/customerUpdate.php. Vista PHP plana (no Blade): salida byte a byte la del legacy (CRLF).
 */
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>個人情報の登録変更フォーム</title>
		<link href="bower_components/bootswatch-dist/css/bootstrap.min.css" rel="stylesheet">
		<link href="./css/add.css" rel="stylesheet">
		<script src="bower_components/jquery/dist/jquery.min.js"></script>
		<script src="js/jquery.jpostal.js"></script>
		<script src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
		<script src="bower_components/bootstrap-validator/dist/validator.min.js"></script>
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
	</head>
	<body>
		<div class="col-xs-12 col-xs-offset-0 well">
			<legend>個人情報変更</legend>
			<?php if (isset($res['status'])): ?>
				<?php if ($res['status'] == "ok"): ?>
				<div class="alert alert-success">
					変更が完了しました
				</div>
				<?php /* 
				<script>
				try {
					if (typeof android !== 'undefined' && typeof android.signUp === 'function') {
						android.signUp('<?=$h($domain)?>', '<?=$res['data']['login_id']?>', '<?=$res['data']['login_pass']?>');
					} else {
						location.href = 'app-ios://complete?domain=<?=$h($domain)?>&id=<?=$res['data']['login_id']?>&pw=<?=$res['data']['login_pass']?>';
					}
				} catch (e) {
					location.href = 'app-ios://complete?domain=<?=$h($domain)?>&id=<?=$res['data']['login_id']?>&pw=<?=$res['data']['login_pass']?>';
				}
				</script>
				*/ ?>
				<?php endif; ?>
				<?php if ($res['status'] == "error"): ?>
				<div class="alert alert-warning">
					<?=$res['errMsg']?>
				</div>
				<?php endif; ?>
			<?php endif; ?>

			<div style="margin-bottom: 10px;">ID: <?=$id?></div>

			<form id="signUpForm" action="/appview/customerUpdate.php" method="post" data-toggle="validator" role="form">
				<div class="form-group">
					<label for="shop" class="control-label">登録店舗</label>
					<select name="shop_cd" class="form-control">
						<?php foreach ((array)$shopList as $key => $val): ?>
						<option value="<?=$key?>"<?php if (($post['shop_cd'] ?? null) == $key) echo ' selected'; ?>><?=$val?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<?=$form?>
				<input type="hidden" name="domain" value="<?=$h($domain)?>">
				<input type="hidden" name="id" value="<?=$id?>">
				<input type="hidden" name="mpa_action_status" value="update">
				<button type="button" id="signUpBtn" class="btn btn-success">変更する</button>
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
