<?php
/*
 * Legacy: appview/used_coupon_list.php. Vista PHP plana (no Blade): salida byte a byte la del legacy (CRLF).
 * Las consultas que la plantilla hacía en línea llegan ya resueltas desde el controlador.
 */
?>

<head>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
body{
	font-family: 'Meiryo',"ヒラギノ角ゴ Pro W3","ＭＳ Ｐゴシック","Osaka", sans-serif;
}
</style>
</head>

<body>
<h2 bgcolor="#<?=$h2BGColor?>" color="#<?=$h2Color?>"><?=$emoji(65)?>使用済みｸｰﾎﾟﾝ</h2>

<div>
<?php
if($mycoupon_max > 0){
?>

<div align="right" class="textRight"><span class="textS"><font size="-1">※最新10件を表示</font></span></div>

<?php
	// Por cupón usado: [fila de tb_mycoupon_used, fila de tb_mycoupon_master, nombre de la tienda] (del controlador)
	foreach ($coupons as $i => [$mycoupon_array, $tb_mycoupon_master_array, $shop_name]) {

		if(($SMART_PHONE ?? null)){

			// スマホ表示
?>
<div class="kadomaruArea">
<p class="strongLevel1 pB10"><?=$tb_mycoupon_master_array['title']?></p>
<p><?=nl2br((string) $tb_mycoupon_master_array['comment'])?><br />
<?php if($check_count > 1){ ?>(<?=$shop_name?>)&nbsp;<?php } ?>
<?=(is_string($mycoupon_array['used_date']) && !is_numeric($mycoupon_array['used_date']) ? '' : date("Y/m/d H:i:s",(int) ($mycoupon_array['used_date'] ?? 0)))?></p>
</div>
<?php

		}else{

			// ガラケー表示
?>
<hr />
<img src="../hp/user/img/spacer.gif" width="1" height="3" alt="" /><br />
<?=$tb_mycoupon_master_array['title']?><br />
<span class="textS"><font size="-1"><?=nl2br((string) $tb_mycoupon_master_array['comment'])?><br /><?php if($check_count > 1){ ?>(<?=$shop_name?>)&nbsp;<?php } ?><?=(is_string($mycoupon_array['used_date']) && !is_numeric($mycoupon_array['used_date']) ? '' : date("Y/m/d H:i:s",(int) ($mycoupon_array['used_date'] ?? 0)))?></font></span><br /><img src="../hp/user/img/spacer.gif" width="1" height="3" alt="" />



<?php
		} // END --- if($SMART_PHONE)

	}// for($i=0; $i<$mycoupon_max; $i++) end
?>

<!--　ネクストバック　-->
<div class="spPageControl">
<?=$page_control?>
</div>

<?php

}else{// if($mycoupon_max > 0)else

?>
<div class="m10 p10 center">現在使用済みｸｰﾎﾟﾝはありません。</div>
<?php

}// if($mycoupon_max > 0)end

?>
</div>

<div align="right">
<span class="textS"><font size="-1"><a href="./coupon.php?customer_cd=<?=$h($customer_cd)?>">一覧に戻る</a></font></span>
</div>

</body>
</html>