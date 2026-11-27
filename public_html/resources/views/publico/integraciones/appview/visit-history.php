<?php
/*
 * Legacy: appview/visit_history.php. Vista PHP plana (no Blade): salida byte a byte la del legacy (CRLF).
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
<title>来店履歴</title>
<link rel="stylesheet" href="/admin/css/font-awesome.min.css">
<link rel="stylesheet" href="/appview/css/default.css">
<link rel="stylesheet" href="/appview/css/common.css?r=1">
<link rel="stylesheet" href="/appview/css/member_inite.css">
<link rel="stylesheet" href="/appview/css/lightbox.css">
<style>
main{
	padding: 0px;
}
.none_data{
    text-align: center;
    height: 100px;
    display: block;
    line-height: 100px;
}
.history_list {
}
.history_list li {
    padding: 10px;
    border-bottom: 1px solid #e2e2e2;
}
.history_list .shop_name {
}
.history_list .coming_date_time {
    text-align: right;
    font-size: 12px;
    color: #969696;
}
.none_data{
}
</style>
</head>
<body>

<main>
<?php if ( isset( $data->status ) && $data->status == "ok" && count( $data->data ) > 0 ) { ?>
	<ul class="history_list">
		<?php foreach ( $data->data as $key => $val ) { ?>
			<li>
				<p class="shop_name"><?=$val->shop_name?></p>
				<p class="coming_date_time"><?=$val->coming_date_time?></p>
			</li>
		<?php } ?>
	</ul>
<?php } else { ?>
<p class="none_data">
	来店履歴はありません。
</p>
<?php } ?>
</main>

</body>
<script type="text/javascript" src="/appview/js/jquery-2.0.3.min.js"></script>
<script type="text/javascript" src="/appview/js/lightbox.js"></script>
<script>
$(function(){
});
</script>
</html>