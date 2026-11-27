<?php
/*
 * Legacy: appview/photo_gallery.php. Vista PHP plana (no Blade): salida byte a byte la del legacy (CRLF).
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
<title>フォトギャラリー</title>
<link rel="stylesheet" href="/admin/css/font-awesome.min.css">
<link rel="stylesheet" href="/appview/css/default.css">
<link rel="stylesheet" href="/appview/css/common.css">
<link rel="stylesheet" href="/appview/css/member_inite.css">
<link rel="stylesheet" href="/appview/css/lightbox.css">
<style>
main{
	padding: 0px;
}
.image_list {
	margin: 3px;
	letter-spacing: -0.35em;
}
.image_list li {
    display: inline-block;
    width: 25%;
    text-align: center;
}
.image_list li img {
	width: 100%;
    border: 1px solid #FFF;
}

.title-icon {
	display: block;
	margin: 10px auto 30px;	
	width: 300px;
}
</style>
</head>
<body>

<main>
<img src="./images/photo.png" width= "300" class="title-icon">

<?php if ( isset( $data->status ) && $data->status == "ok" && count( $data->data ) > 0 ) { ?>
	<ul class="image_list">
		<?php foreach ( $data->data as $key => $val ) { ?>
			<li>
				<a href="<?=$val->largeImageUrl?>" rel="lightbox"><img src="<?=$val->largeImageUrl?>"></a>
			</li>
		<?php } ?>
	</ul>
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