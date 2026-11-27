<?php
/*
 * Legacy: appview/member_comfirm.php. Vista PHP plana (no Blade): salida byte a byte la del legacy (CRLF).
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
<title>登録確認ページ</title>
<link rel="stylesheet" href="/appview/css/default.css">
<link rel="stylesheet" href="/appview/css/common.css">
</head>
<body>

<main>
	<h2 class="tit01">登録確認</h2>
	<form action="./member_complete.php" method="post">
		<input type="hidden" name="regist" value="1">
		<input type="hidden" name="_token" value="<?=$token?>">
		<input type="hidden" name="name" value="<?=$h(($req["name"] ?? null))?>">
		<input type="hidden" name="mail" value="<?=$h(($req["mail"] ?? null))?>">
		<input type="hidden" name="tel" value="<?=$h(($req["tel"] ?? null))?>">
		<dl class="dl_list04">
			<dt>名前</dt>
				<dd><?=$h(($req["name"] ?? null))?></dd>
			<dt>メールアドレス</dt>
				<dd><?=$h(($req["mail"] ?? null))?></dd>
			<dt>電話番号</dt>
				<dd><?=$h(($req["tel"] ?? null))?></dd>
		</dl>
		
		<ul class="bt_list01">
			<li><input type="submit" value="登録"></li>
			<li><a href="#" onclick="javascript:window.history.back(-1);return false;">戻る</a></li>
		</ul>
	</form>
</main>

</body>
</html>