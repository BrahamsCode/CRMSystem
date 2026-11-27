<?php
/*
 * Legacy: system/desktop_request/customerDetailHtml.php (ficha del socio para la ventana CTI).
 * Vista PHP plana (no Blade) para que la salida sea byte a byte la del legacy (CRLF y saltos tras ?>).
 * Variables: $array (tb_customer, claves vacías si no existe), $getStaff(cd) y $getCustomer(cd) (filas o
 * claves vacías), $visits (últimas 5 visitas con kaishamei).
 */
?>
<html>
<head>

<meta charset="UTF-8">

<meta name="robots" content="noindex">
<meta name="robots" content="nofollow">
<meta name="robot-control" content="deny-all">
<meta name="robots" content="none">

<style type="text/css">
<!--

body {
	width: 100%;
	background: transparent;
	color: #2F2F2F;
	font-size: 12px;/* 文字サイズ計算＝【基本サイズ ÷ 表示したいサイズ * 100 （小数点以下切り上げ） */
	font-family: Osaka,"ＭＳ Ｐゴシック","MS PGothic",Sans-Serif;
	text-align: center;
	line-height: 1.6;
}


#wrapper {
	text-align: left;
}

.detailTableLayout {
	width: 300px;
	border-collapse: collapse;
	border-spacing: 0;
	margin-bottom: 10px;
}

.detailTableLayout th {
	width: 50px;
	background: #EEEEEE;
	border: 1px solid #000000;
	font-size: 8px;
}

.detailTableLayout td {
	border: 1px solid #000000;
}


-->
</style>

</head>
<body>


<div id="wrapper">

<div>■基本情報</div>
<table class="detailTableLayout" style="margin-bottom: 10px;">

	<tr>
	<th>ID</th>
	<td><?=$array['id']?></td>
	<th>管理番号</th>
	<td><?=$array['kanri_id']?></td>
	</tr>

	<tr>
	<th>フリガナ</th>
	<td colspan="3"><?=$array['name_kana']?></td>
	</tr>

	<tr>
	<th>名前</th>
	<td colspan="3"><?=$array['name']?></td>
	</tr>

	<tr>
	<th>性別</th>
	<td><?=$array['kojin_sex']?></td>
	<th>生年月日</th>
	<td><?=$array['kojin_birth']?></td>
	</tr>

	<tr>
	<th>住所</th>
	<td colspan="3"><?=$array['pref']?>&emsp;<?=$array['add']?></td>
	</tr>

	<tr>
	<th>指名担当者</th>
	<td colspan="3">
<?php
	if( $array['tb_staff_cd'] != "" ){
		$shimeitantousha_arr = $getStaff( $array['tb_staff_cd'] );
		echo $shimeitantousha_arr['name'];
	}
?>
	</td>
	</tr>

	<tr>
	<th>紹介者</th>
	<td colspan="3">
<?php
	if( $array['shoukai_tb_customer_cd'] != "" ){
		$shoukaisha_arr = $getCustomer( $array['shoukai_tb_customer_cd'] );
		echo "【".$shoukaisha_arr['id']."】".$shoukaisha_arr['name'];
	}
?>
	</td>
	</tr>

	<tr>
	<th>備考</th>
	<td colspan="3"><?=nl2br(htmlentities($array['bikou'], ENT_QUOTES, mb_internal_encoding()))?></td>
	</tr>

</table>


<?php
if ( count($visits) > 0 ) {
	$youbi = array("日", "月", "火", "水", "木", "金", "土");

?>
	<div>■来店履歴(過去最大5回まで表示されます。)</div>
	<table class="detailTableLayout">
		<tr>
			<th style="width:40%;">日時</th>
			<th>来店店舗</th>
		</tr>
	<?php
	foreach ( $visits as $customer_raiten_array )  {
	?>
		<tr>
			<td><?=(is_string($customer_raiten_array['raiten_time']) && !is_numeric($customer_raiten_array['raiten_time']) ? '' : date("Y/m/d", $customer_raiten_array['raiten_time'] ?? 0)).' ('.($youbi[(is_string($customer_raiten_array['raiten_time']) && !is_numeric($customer_raiten_array['raiten_time']) ? '' : date('w', $customer_raiten_array['raiten_time'] ?? 0))] ?? '').')<br/>'.(is_string($customer_raiten_array['raiten_time']) && !is_numeric($customer_raiten_array['raiten_time']) ? '' : date("H:i", $customer_raiten_array['raiten_time'] ?? 0))?></td>
			<td><?=$customer_raiten_array['kaishamei']?></td>
		</tr>
	<?php
	} // end while
	?>
	</table>

<?php
}
?>


<!-- div#wrapper END --></div>


</body>
</html>