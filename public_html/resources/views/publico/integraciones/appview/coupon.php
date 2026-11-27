<?php
/*
 * Legacy: appview/coupon.php. Vista PHP plana (no Blade): salida byte a byte la del legacy (CRLF).
 * Las consultas que la plantilla hacía en línea llegan ya resueltas desde el controlador.
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<head>
<meta name="viewport" content="width=device-width,initial-scale=1">
<title></title>
<style>
body{
	font-family: 'Meiryo',"ヒラギノ角ゴ Pro W3","ＭＳ Ｐゴシック","Osaka", sans-serif;
}
</style>
</head>

<body>
<?php
$hrDotConfig = ($templateData['クーポン'] ?? null); // 帯画像
if (($hrDotConfig['pic_flg'] ?? null) == '1') {
?>
<div align="center"><img src="/hp/uploads/c6/template/<?=$hrDotConfig['cd']?>/0_m.jpg?t=<?=time()?>" alt="<?=$hrDotConfig['use_case']?>画像" /></div>
<?php
} else {
?>
<h2 align="center" bgcolor="#<?=$h2BGColor?>" color="#<?=$h2Color?>"><?=$emoji(150)?>ｸｰﾎﾟﾝ<?=$emoji(150)?></h2>
<?php
}
?>




<div>
<?php
if($mycoupon_max > 0){
	// Por cupón: [fila de tb_mycoupon, fila de tb_mycoupon_master, nombre de la tienda] (consultas del controlador)
	foreach ($coupons as $i => [$mycoupon_array, $tb_mycoupon_master_array, $shop_name]) {
		// 2011.11.16 ohno 一覧の詳細文字数に一定以上は省略する処理を追加
		if (mb_strlen((string) $tb_mycoupon_master_array['comment']) > 18) $tb_mycoupon_master_array['comment'] = mb_substr($tb_mycoupon_master_array['comment'],0,16).'....';
?>


<div class="kadomaruArea">

<?=$emoji(65)?><?=$tb_mycoupon_master_array['title']?><br />
<span class="textS"><font size="-1"><?=nl2br((string) $tb_mycoupon_master_array['comment'])?><?php if($check_count > 1){ ?><br />(<?=$shop_name?>)<?php } ?></font></span><br />

<div align="right" class="textRight"><span class="textS"><font size="-1"><a href="coupon_detail.php?cd=<?=$mycoupon_array['cd']?>&customer_cd=<?=$h($customer_cd)?>">詳細を見る</a><?php /* $useBtn */ ?></font></span></div>

<!---■■ /.kadomaruArea ■■---></div>

<?php
		if($i != $mycoupon_max-1){
			echo $theme->hrDot(); // dotボーダー
		}
?>

<?php
	}// for($i=0; $i<$mycoupon_max; $i++) end
?>

<!--　ネクストバック　-->
<div>
<?=$page_control?>
</div>

<?php
}else{// if($mycoupon_max > 0)else
?>
<div class="m10 p10 center">現在ｸｰﾎﾟﾝはありません。</div>
<?php
}// if($mycoupon_max > 0)end
?>
</div>





<?php echo $theme->hrBorder(); // 実線ボーダー?>

<?php
// 2012.12.12【大野】マイページのクーポン関連のテキスト追加・変更
/*
<a href="used_coupon_list.php">○使用済みｸｰﾎﾟﾝ</a>※クーポンは必ず店内で使用してください。
*/?>
※<span class="attention"><font color="#FE0000">1回のみの使用</font></span>となりますので「使用する」は<span class="attention"><font color="#FE0000">必ず店内で</font></span>押してください。<br />
<a href="used_coupon_list.php?customer_cd=<?=$h($customer_cd)?>">○使用済みｸｰﾎﾟﾝ</a>


<br />
</body>
</html>