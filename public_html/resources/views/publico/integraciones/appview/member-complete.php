<?php
/*
 * Legacy: appview/member_complete.php. Vista PHP plana (no Blade): salida byte a byte la del legacy (CRLF).
 * $new_no = cd del socio creado (null si no se registró).
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
<title>登録完了ページ</title>
<link rel="stylesheet" href="/appview/css/default.css">
<link rel="stylesheet" href="/appview/css/common.css">
</head>
<body>

<main>
	<h2 class="tit01">登録完了</h2>
	<form action="./member_complete.php" method="post">
		<dl class="dl_list04">
			<dt>名前</dt>
				<dd><?=$h(($req["name"] ?? null))?></dd>
			<dt>電話番号</dt>
				<dd><?=$h(($req["tel"] ?? null))?></dd>
			<dt>メールアドレス</dt>
				<dd><?=$h(($req["mail"] ?? null))?></dd>
		</dl>
			
		<p class="bt03">
			<a href="javascript:void(0);" class="backLink">
				登録完了しました。
			</a>
		</p>
		
	</form>
</main>

</body>
<script type="text/javascript" src="/appview/js/jquery-2.0.3.min.js"></script>
<script>
$(function(){
	$(".backLink").click(function(){
		var message = {"action":"appCloseDialog", "customer_cd":"<?=$new_no?>"};
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
		return false;
	});
});
</script>
</html>