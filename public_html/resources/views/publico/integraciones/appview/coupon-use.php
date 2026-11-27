<?php
/*
 * Legacy: appview/coupon_use.php. Vista PHP plana (no Blade): salida byte a byte la del legacy (CRLF).
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

<?php echo $theme->hrBorder(); // 実線ボーダー*/?>

<h2 bgcolor="#<?=$h2BGColor?>" color="#<?=$h2Color?>"><?=$emoji(65)?>ｸｰﾎﾟﾝ詳細</h2>

<?php

// $coupon_array, $tb_mycoupon_master_array, $user_id, $user_name y $customer_cd llegan del controlador
$attention_array = explode("<>",$tb_mycoupon_master_array['attention'] ?? '');
$attention_array_count = count($attention_array);
?>
<div class="textCenter" align="center">
<span class="attention textS"><font size="-1" color="#FE0000">この画面を店員にお見せください。<br />
<img src="../hp/user/img/spacer.gif" width="1" height="3" alt="" /><br />
使用日時：<?=(is_string($coupon_array['used_date']) && !is_numeric($coupon_array['used_date']) ? '' : date("Y/m/d H:i:s",(int) ($coupon_array['used_date'] ?? 0)))?></font></span><br />
<img src="../hp/user/img/spacer.gif" width="1" height="10" alt="" /><br />

<span class="textS"><font size="-1">会員番号：<?=$user_id?><br />
<?=$user_name?>さんのﾏｲｸｰﾎﾟﾝです。</font></span>
</div>
<br />
<div class="textCenter" align="center">
<table width="90%" bgcolor="#FF9900" cellspacing="2" style="margin: auto; background: #FF9900;">
<tr>
<td class="textCenter" align="center" bgcolor="#FFF5F0">
<img src="../hp/user/img/spacer.gif" width="1" height="3" alt="" /><br />
<font color="#0055FF"><?=$tb_mycoupon_master_array['title']?></font><br />

<span class="textS"><font size="-1" color="#FF0000"><?=nl2br($tb_mycoupon_master_array['comment'] ?? '')?></font></span>
<br /><img src="../hp/user/img/spacer.gif" width="1" height="3" alt="" /><br />

<span class="textS"><font size="-1" color="#000000">発行NO.<?=$coupon_array['cd']?></font></span><br />
<span class="textS"><font size="-1" color="#000000">ｸｰﾎﾟﾝNO.<?=$tb_mycoupon_master_array['cd']?></font></span>
<br /><img src="../hp/user/img/spacer.gif" width="1" height="3" alt="" />

<dl class="dlList">
<dt><span class="textS"><font size="-1" color="#FF0000">有効期限</font></span></dt>
<dd><span class="textS"><font size="-1" color="#FF0000"><?=date ( 'Y/m/d', (int) ($coupon_array['time_limit'] ?? 0) )?></font></span></dd>
</dl><br />
<img src="../hp/user/img/spacer.gif" width="1" height="3" alt="" />
</td>
</tr>


</table>
</div>

<br /><img src="./hp/user/img/spacer.gif" width="1" height="3" alt="" /><br />

<?php
for ($row = 0; $row < $attention_array_count; $row++){

	if($attention_array[$row] != ""){
?>
<span class="textS"><font size="-1"><?=$attention_array[$row]?></font></span><br />
<?php
	} // for ($row = 0; $row < $attention_array_count; $row++)ここまで
}
?>


<div align="right">
<span class="textS"><font size="-1"><a href="./coupon.php?customer_cd=<?=$customer_cd?>">一覧に戻る</a></font></span>
</div>

</body>
</html>