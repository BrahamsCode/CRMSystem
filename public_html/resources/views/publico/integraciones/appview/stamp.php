<?php
/*
 * Legacy: appview/stamp.php. Vista PHP plana (no Blade): salida byte a byte la del legacy (CRLF).
 * Las consultas que la plantilla hacía en línea llegan ya resueltas desde el controlador.
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ja" xml:lang="ja" dir="ltr">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width,initial-scale=1">
<title></title>
<link rel="stylesheet" href="/admin/css/font-awesome.min.css">
<style>
body{
	line-height: 19px;
    background: url(css/img.png);
}
.stampWrap {
    margin: 0 auto;
    max-width: 300px;
    padding-top: 10px;
}
.stampWrap table {
	width: 100%;
    background: #ffe1d3;
    margin: auto;
    border: 9px solid #c78d71;
    border-top-width: 150px;
}
.stampWrap table tr {
}
.stampWrap table tr {
}
.stampWrap table tr th {
}
.stampWrap table tr td {
}
/* 03.リボン */
#heading03{
	position:relative;
	margin:0 -10px;
	padding:5px;
	font:normal 28px/1.6 "Times New Roman", Times, serif;
	text-align:center;
	color:#ffe8e8;
	background:#f99;
	-webkit-transform: rotate(-3deg) skew(-3deg);
	-moz-transform: rotate(-3deg) skew(-3deg);
	-o-transform: rotate(-3deg) skew(-3deg);
	transform: rotate(-3deg) skew(-3deg);
	box-shadow:0 1px 2px 0 rgba(0,0,0,0.2);
    background-image: -webkit-gradient(linear, left top, left bottom,
			from(			rgba(0, 0, 0, 0.0)), 
			color-stop(0.08, rgba(255, 255, 255, 0.5)),
			color-stop(0.12, rgba(255, 255, 255, 0.5)),
			color-stop(0.12, rgba(0, 0, 0, 0.0)),
			color-stop(0.88, rgba(0, 0, 0, 0.0)),
			color-stop(0.88, rgba(255, 255, 255, 0.5)),
			color-stop(0.92, rgba(255, 255, 255, 0.5)),
			color-stop(0.92, rgba(0, 0, 0, 0.0)),
			to(				rgba(0, 0, 0, 0.0))
			);
	background-image: -moz-linear-gradient(top,
			transparent 8%,
            rgba(255, 255, 255, 0.5) 8%,
            rgba(255, 255, 255, 0.5) 12%,
            transparent 12%,
            transparent 88%,
            rgba(255, 255, 255, 0.5) 88%,
            rgba(255, 255, 255, 0.5) 92%,
            transparent 92%	
			);
	background-image: -o-linear-gradient(top,
			transparent 8%,
            rgba(255, 255, 255, 0.5) 8%,
            rgba(255, 255, 255, 0.5) 12%,
            transparent 12%,
            transparent 88%,
            rgba(255, 255, 255, 0.5) 88%,
            rgba(255, 255, 255, 0.5) 92%,
            transparent 92%	
			);
	background-image: linear-gradient(to bottom,
			transparent 8%,
            rgba(255, 255, 255, 0.5) 8%,
            rgba(255, 255, 255, 0.5) 12%,
            transparent 12%,
            transparent 88%,
            rgba(255, 255, 255, 0.5) 88%,
            rgba(255, 255, 255, 0.5) 92%,
            transparent 92%	
			);
    margin-bottom: -60px;
}
#heading03:before{
	content:" ";
	position:absolute;
	top:-5px;
	left:0;
	width:0;
	height:0;
	border-width:0 0 5px 10px;
	border-style:solid;
	border-color:transparent;
	border-bottom-color:#c66;
}
#heading03:after{
	content:" ";
	position:absolute;
	top:100%;
	right:0;
	width:0;
	height:0;
	border-width:5px 10px 0 0;
	border-style:solid;
	border-color:transparent;
	border-top-color:#c66;
}
</style>
</head>
<body>


<div class="bgWhite p10">

<div>
	
</div>

<div class="textCenter stampWrap" align="center">

<h3 id="heading03">会員スタンプ</h3>

<p style="margin-top: 80px; margin-bottom: -127px;color:white;font-size: 13px; padding: 0 10px;
">
	商品をご購入頂いたり、店舗に来て頂くことでスタンプを発行させて頂いております。<br>
</p>

<?php

$stamp_page_limit_num = 50;
$stamp_per_line = 5; // 一行で表示するスタンプ数。
$stamp_line_max = $tb_stamp_config_array['stamp_max'] / $stamp_per_line;
$stamp_line_max = ($stamp_line_max > 10) ? 10 : $stamp_line_max ;

// $customer_array (tb_customer), $tb_stamp_config_array y $pic_default_no llegan del controlador

$stamp_count = $customer_array['stamp_count']; // 顧客のスタンプ数
$stamp_count_division_rest = $stamp_count % $stamp_page_limit_num; // 画面に表示するスタンプ数
if($stamp_count > 0 && $stamp_count_division_rest == 0) $stamp_count_division_rest = $stamp_page_limit_num ; // キリのいい数字の場合は、満タン表示にするための代入

// MYスタンプ設定していれば差し替える
if ($customer_array['tb_stamp_pattern_cd'] != "") $pic_default_no = $customer_array['tb_stamp_pattern_cd'];

$stamp_page_count = ($stamp_count > 0) ? $stamp_page_limit_num ? ceil($stamp_count / $stamp_page_limit_num) : 0 : 1 ; // 顧客のスタンプページ数(初期値は1)


?>
<table bgcolor="#666666" cellspacing="1" style="background: #ffe1d3; margin: auto;">
<?php
$cnt = 0;
$page_start_count = max(0, ($stamp_page_count - 1) * $stamp_page_limit_num); // negative OFFSET (page 0): SQL error and fatal in PHP 8.5.12 // 最初のマスの個数

for ($ii=0; $ii < $stamp_line_max; $ii++) {
	for ($i=0; $i<$stamp_per_line; $i++) {
		$cnt++; // $stamp_line_maxのMAXスタンプ数だけ足していく
		$page_start_count++;

		if ($i % $stamp_per_line == 0) echo "<tr>\n";

		if($stamp_count_division_rest >= $cnt){
			$stampImgPath  = '/hp/uploads/c4/stamp/'.$pic_default_no.'.gif';
			$stampImgAlt   = 'ｽﾀﾝﾌﾟ';
			$containedText = '';

		}else if($tb_stamp_config_array['stamp_max'] == $cnt){
			$stampImgPath = '/hp/inc_m/img/stamp_get.gif';
			$stampImgAlt  = 'ｸｰﾎﾟﾝｹﾞｯﾄ！';
			$containedText = '';

		}else{
			//$stampImgPath = $link_path.'../inc_m/img/spacer.gif';
			$stampImgPath = '';
			$stampImgAlt  = '';
			$containedText = $page_start_count;

		}
		echo '<td align="center" bgcolor="#FFFFFF" width="30" height="30" style="background: #FFFFFF;">';
		echo ($stampImgPath != '') ? '<img src="'.$stampImgPath.'" width="30" height="30" alt="'.$stampImgAlt.'" />' : '';
		echo $containedText;
		echo '</td>';
		if ($i % $stamp_per_line == 4) echo "</tr>\n";
	}
}
?>
</table>
</div><br />
<div align="center" class="textCenter">
<span class="textS"><font size="-1" color="#FE0000">
<span id="stampPageDisp">現在 <?=$stamp_page_count?>枚目</span><br />
<span id="stampCountDisp">
<?php
if ($stamp_count == "" || $stamp_count == "0"){
	echo 'ｽﾀﾝﾌﾟはありません。';

} else if ($stamp_count >= $tb_stamp_config_array['stamp_max']) {
	echo $stamp_count.'個目'.$emoji(151);
	echo '<br />※これ以上ｽﾀﾝﾌﾟは貯まりません。';

}else{
		echo $stamp_count.'個目'.$emoji(151);
}
?>
</span>
</font>
</span>
</div>

<?php
//スタンプ有効期限について
// $yukoukigen_flg_max / $yukoukigen_flg_array: admin_master.stamp_shikkou_sousa (del controlador)

if($yukoukigen_flg_max > 0){

	// 2013.08.07【大野】スタンプが0の場合は有効期限は表示しない
	//if($yukoukigen_flg_array['stamp_shikkou_sousa'] == 1){
	if( (is_string($yukoukigen_flg_array['stamp_shikkou_sousa']) && !is_numeric($yukoukigen_flg_array['stamp_shikkou_sousa']) ? (float) $yukoukigen_flg_array['stamp_shikkou_sousa'] : $yukoukigen_flg_array['stamp_shikkou_sousa']) == 1 && $customer_array['stamp_count'] != "" && $customer_array['stamp_count'] != "0" ){
		if($customer_array['stamp_yukoukigen'] != ""){
			echo '<div align="center"><span class="textS"><font size="-1">有効期限は'.date( "Y年m月d日", ($customer_array['stamp_yukoukigen']+1) ).'です</font></span></div>';
		}
	}
}
?>
    
    </body>
</html>