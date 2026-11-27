<?php /* Convertido de signage/list/contents_category.php del legacy (S4). Variables: ver el controlador. */ ?>
<div id="main">
	<div class="content">
	
		<?php if ( count( $error_msg_ary ) > 0 ) { ?>
			<div class="error_area" style="margin:25px 0 8px 0;">
				<?=is_array($error_msg_ary) ? implode("<br>", $error_msg_ary) : null?>
			</div>
		<?php } ?>
		
		<?php if ( count( $success_msg_ary ) > 0 ) { ?>
			<div class="success_area" style="margin:25px 0 8px 0;">
				<?=is_array($success_msg_ary) ? implode("<br>", $success_msg_ary) : null?>
			</div>
		<?php } ?>
		
		<!--
		<h2>新規追加</h2>
		<form action="<?=$_form_action ?? ''?>" method="POST"><?=csrf_field()?>
			<table class="tablebox">
				<colgroup>
					<col width="200">
					<col width="">
				</colgroup>
				<tbody>
					<tr>
						<th>状態</th>
						<td>
							<label>
								<input type="radio" name="flg" value="1" checked="checked"> 使用中
							</label>　
							<label>
								<input type="radio" name="flg" value="0"> 停止中
							</label>
						</td>
					</tr>
					<tr>
						<th>項目名</th>
						<td>
							<input type="text" name="name" value="">
						</td>
					</tr>
					<tr>
						<th>横幅 ( Width )</th>
						<td>
							<input type="text" name="width" value="">
						</td>
					</tr>
					<tr>
						<th>縦幅 ( Height )</th>
						<td>
							<input type="text" name="height" value="">
						</td>
					</tr>
				</tbody>
			</table>
			
			<input type="hidden" name="insert" value="1">
			<br>
			<input type='submit' name='action' value='新規追加'>
		</form>
		
		<br>
		-->
		<div class="main-wrap">
			<div class="title-box">
				<h1 class="page-title">画像サイズ別一覧</h1>
			</div>
			<img src="<?=$_base_url?>/css/img/signage_bottom_arrow.png" style="height:20px; width:22px;">
		</div>
		<?php if ( !count( $data ) ) { ?>
			<p>データがありません。</p>
		<?php } else { ?>
			<form action="<?=$_form_action ?? ''?>" method="POST"><?=csrf_field()?>
				
				<table class="tablebox">
					<colgroup>
						<col width="">
						<col width="80">
						<col width="80">
						<col width="80">
						<col width="80">
					</colgroup>
					<thead>
						<tr>
							<th>サイズ</th>
							<th>横幅</th>
							<th>縦幅</th>
							<th>設定数</th>
							<th>設定一覧</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $data as $key => $val ) { ?>
						<tr>
							<td>
								<?=$val['width']?>px × <?=$val['height']?>px サイズ
							</td>
							<td align="center">
								<?=$val['width']?> px
							</td>
							<td align="center">
								<?=$val['height']?> px
							</td>
							<td align="center">
								<?php $data_num = $_counts[$val['cd']] ?? 0; /* nº de contenidos de la categoría */ ?>
								<?=number_format( $data_num )?> 件
							</td>
							<td align="center">
								<input type="button" value="一覧" onClick="location.href='<?=route('admin.senalizacion.contents.index', [$_kind, $val['cd']])?>'">
							</td>
						</tr>
						<?php } ?>
						<tr>
						</tr>
					</tbody>
				</table>
				
			</form>
		<?php } ?>
	</div>
</div>
