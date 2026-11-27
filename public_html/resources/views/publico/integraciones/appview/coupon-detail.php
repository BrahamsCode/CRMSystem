<?php
/*
 * Legacy: appview/coupon_detail.php. Vista PHP plana (no Blade): salida byte a byte la del legacy (CRLF).
 * Las consultas que la plantilla hacía en línea llegan ya resueltas desde el controlador.
 */
?>


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

<h2 bgcolor="#<?=$h2BGColor?>" color="#<?=$h2Color?>"><?=$emoji(65)?>ｸｰﾎﾟﾝ詳細</h2>
<?php

// $mycoupon_array, $tb_mycoupon_master_array, $tb_shop_name_array, $shop_option_list y $shop_max llegan del
// controlador (las consultas que el legacy hacía aquí)
$attention_array = explode("<>", (string) $tb_mycoupon_master_array['attention']);
$attention_array_count = count($attention_array);

if(($SMART_PHONE ?? null)){

	// スマホ表示
?>
<dl class="center p10 bgWhite">
<dt class="strongLevel1"><?=$tb_mycoupon_master_array['title']?></dt>
<dd><?=nl2br((string) $tb_mycoupon_master_array['comment'])?></dd>
</dl>
<?php

}else{

	// ガラケー表示
?>
<div class="textCenter" align="center">
<?=$tb_mycoupon_master_array['title']?><br>
<span class="textS"><font size="-1"><?=nl2br((string) $tb_mycoupon_master_array['comment'])?></font></span><br>

<img src="../hp/user/img/spacer.gif" width="1" height="3" alt=""><br>

</div>
<?php

} // END --- if($SMART_PHONE)

?>





<div class="textLeft" align="left">
<?php


if(($SMART_PHONE ?? null)){
	// スマホ表示
	echo '<div class="bgWhite p10">';
}


if($shop_max > 1){

?>
<span class="textS"><font size="-1">発行店舗：<?=$tb_shop_name_array['kaishamei']?></font></span><br>
<?php

} // END --- if($shop_max > 1)

?>
<span class="textS"><font size="-1">有効期限：<?=date ( 'Y/m/d', (int) ($mycoupon_array['time_limit'] ?? 0) )?>まで</font></span><br>
<img src="../hp/user/img/img/spacer.gif" width="1" height="3" alt=""><br>
<?php

for ($row = 0; $row < $attention_array_count; $row++){

	if($attention_array[$row] != ""){
?>
<span class="textS"><font size="-1"><?=$attention_array[$row]?></font></span><br>
<?php
	} // END --- if($attention_array[$row] != "")

} // END --- for ($row = 0; $row < $attention_array_count; $row++)


if(($SMART_PHONE ?? null)){
	// スマホ表示
	echo '</div>';
}


?>





<?php echo $theme->hrBorder(); // 実線ボーダー?>

<div class="textCenter p10" align="center">

<span class="textS"><font size="-1">1回のみの使用となりますので<br>下記ﾎﾞﾀﾝは<br>必ず店内で押してください。</font></span><br>

<form method="post" name="form1" id="form1" class="form" action="coupon_control.php">

<?php

// 全店舗の時のみセレクトを表示
if ($tb_mycoupon_master_array['tb_shop_cd'] == "-1") {

?>
※使用店舗を選択してください。<br>
<select name="tb_shop_cd" id="tb_shop_cd" class="m10">
<?=$shop_option_list?>
</select><br>
<?php

}else{

?>
<input type="hidden" name="tb_shop_cd" id="tb_shop_cd" value="<?=$tb_mycoupon_master_array['tb_shop_cd']?>">
<?php

} // END --- if ($tb_mycoupon_master_array['tb_shop_cd'] == "-1")

?>
<input type="hidden" name="customer_cd" value="<?=$h($customer_cd)?>">
<input type="hidden" name="coupon_cd" id="coupon_cd" value="<?=$tb_mycoupon_master_array['cd']?>">
<input type="hidden" name="my_coupon_cd" id="my_coupon_cd" value="<?=$mycoupon_array['cd']?>">
<input type="hidden" name="act" id="act" value="use">
<input type="submit" name="enter" id="enter" value="使用する">
</form>

<?php

// 印刷表示するかどうか
$tb_base_system_grandchild_max = $print_count; // tb_base_system_grandchild 印刷 (system_flg = '1')

if($tb_base_system_grandchild_max > 0){

?>
<br>
<form method="post" name="form2" id="form2" class="form" action="coupon_control.php">

<br><span class="textS"><font size="-1">※印刷する場合は店舗を<br>選択してください。</font></span><br>
<select name="tb_shop_cd" id="tb_shop_cd">
<?=$shop_option_list?>
</select>

<input type="hidden" name="coupon_cd" id="coupon_cd" value="<?=$tb_mycoupon_master_array['cd']?>">
<input type="hidden" name="my_coupon_cd" id="my_coupon_cd" value="<?=$mycoupon_array['cd']?>">
<input type="hidden" name="act" id="act" value="print">
<input type="submit" name="print_enter" id="print_enter" value="お店で印刷する">
</form>
<?php

} // END --- if($tb_base_system_grandchild_max > 0)

?>
</div>





<div align="right">
<span class="textS"><font size="-1"><a href="./coupon.php?customer_cd=<?=$h($customer_cd)?>">一覧に戻る</a></font></span>
</div>

</body>
</html>