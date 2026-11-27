{{-- signage/pdf/invoice.php y receipt.php (PROVISIONAL): mismo HTML; importes fijos de muestra como el legacy. $title: 請求書 o 領収書 --}}
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
@if ($edit)
<link rel="stylesheet" href="/signage/css/pdf.css?q={{ rand() }}">
@else
<style>{!! $css !!}</style>
@endif
<title>管理画面</title>
</head>

<body>
	
	<table width="100%">
		<tr>
			<td width="400" align="right"><h1>{{ $title }}</h1></td>
			<td align="right">
				<table>
					<tr><td align="right">No. : </td><td align="right">{{ sprintf("%04d", (int) $cd) }}</td></tr>
					<tr><td>発行日時 :</td><td>{{ date("Y年m月d日") }}</td></tr>
				</table>
			</td>
		</tr>
	</table>
	
	<table width="100%">
		<tr>
			<td width="400" align="right"></td>
			<td align="right">
				<img src="/img/pdf_icon.png" width="156" height="76">
			</td>
		</tr>
	</table>
	
	<table width="100%">
		<tr>
			<td width="400" align="right" style="border-bottom: 2px solid;">
				<h2>{{ $customer?->name }}　<small>御中</small></h2>
			</td>
			<td align="right">
			</td>
		</tr>
	</table>
	
	<br><br>
	
	
	<table width="100%" style="border-collapse: collapse;">
		<tr bgcolor="#EEEEEE">
			<td align="right">
				<br>
				<h2>　請求金額</h2>
				<br>
				<br>
			</td>
			<td align="center">
				<h1>￥ 99,999 <small>-</small></h1> 
			</td>
		</tr>
	</table>
	
	
	<br>
	
	<table width="100%" border="1" bordercolor="#AAAAAA" style="border-collapse: collapse;">
		<tr bgcolor="#AAAAAA">
			<th width="500">品名</th>
			<th>値段</th>
		</tr>
		<tr bgcolor="#FFFFFF">
			<td>GoTEL ライト (初期費用)</td>
			<td align="right">1,000 円</td>
		</tr>
		<tr bgcolor="#EEEEEE">
			<td>GoTEL ライト (月額費用)</td>
			<td align="right">1,000 円</td>
		</tr>
		@foreach (range(1, 9) as $val)
			<tr bgcolor="#FFFFFF">
				<td>&nbsp;</td>
				<td align="right">&nbsp;</td>
			</tr>
			<tr bgcolor="#EEEEEE">
				<td>&nbsp;</td>
				<td align="right">&nbsp;</td>
			</tr>
		@endforeach
		<tr bgcolor="#FFFFFF">
			<td bgcolor="#AAAAAA" align="right">小計</td>
			<td align="right">&nbsp;</td>
		</tr>
		<tr bgcolor="#FFFFFF">
			<td bgcolor="#AAAAAA" align="right">消費税</td>
			<td align="right">&nbsp;</td>
		</tr>
		<tr bgcolor="#FFFFFF">
			<td bgcolor="#AAAAAA" align="right">合計</td>
			<td align="right">&nbsp;</td>
		</tr>
	</table>
	
	
	<br><br>
	
	
	<table width="100%" style="border-collapse: collapse; width:100%">
		<tr>
			<td width="340" align="center" style="border: 1px solid;">
				
				振り込み先
				
			</td>
			
			<td width="20" align="right" style="border-color: #FFFFFF">
				
			</td>
			
			<td align="center" style="border: 1px solid;">
				
				備考
				
			</td>
		</tr>
		<tr>
			<td width="325" align="center" style="border: 1px solid; height: 150px">
			</td>
			
			<td width="50" align="right" style="border-color: #FFFFFF">
				
			</td>
			
			<td align="center" style="border: 1px solid; height: 150px">
			</td>
		</tr>
	</table>
	
</body>

</html>
