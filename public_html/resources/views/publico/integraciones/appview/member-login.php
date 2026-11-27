<?php
/*
 * Legacy: appview/member_login.php. Vista PHP plana (no Blade): salida byte a byte la del legacy (CRLF).
 * $h = h() del legacy (htmlspecialchars sin doble codificación); $req = parámetros de la petición.
 */
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta http-equiv="content-language" content="ja">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex">
<meta name="robots" content="nofollow">
<meta name="robot-control" content="deny-all">
<meta name="robots" content="none">
<title>会員ページ</title>
<link rel="stylesheet" href="/admin/css/font-awesome.min.css">
<link rel="stylesheet" href="/appview/css/default.css">
<link rel="stylesheet" href="/appview/css/common.css">
<link rel="stylesheet" href="/appview/css/member_inite.css?r=1">
</head>
<body style="background: #f1f1f1;">

<main class="member_login_page">
	
	<?php if ( $isPost && $_is_login ) { ?>
		
		<script>
		myFunction = function() {
			
			var message = {"action":"appCloseDialog", "customer_cd":"<?=$_customer_data['cd']?>", "touroku_kubun":"<?=$_customer_data['touroku_kubun']?>"};
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
		myFunction();
		</script>
		
	<?php } else { ?>
		
		<div>
			<a href="./member_init.php?introducer_cd=<?=$h(($req["introducer_cd"] ?? null))?>">&lt; 戻る</a>
		</div>
		
		<form action="?" method="POST" class="member_form">
			
			<?php if ( $isPost && !$_is_login ) { ?>
				<p class="member_login_error" >
					ログイン情報に間違いがあります。<br>
					情報を変更の上、再度ログインをお試しください。
				</p>
			<?php } ?>
			
			<div>
				<i class="fa fa-user-circle" aria-hidden="true"></i>
				<p>ログインID : </p>
				<input type="text" name="login_id" value="<?=$h(($req["login_id"] ?? null))?>">
			</div>
			<div>
				<i class="fa fa-unlock-alt" aria-hidden="true"></i>
				<p>ログインPW : </p>
				<input type="password" name="login_pass" value="<?=$h(($req["login_pass"] ?? null))?>">
			</div>
			<input type="submit" value="ログイン">
		</form>
		
	<?php } ?>
	
</main>

</body>
<script type="text/javascript" src="/appview/js/jquery-2.0.3.min.js"></script>
<script>
$(function(){
});
</script>
</html>