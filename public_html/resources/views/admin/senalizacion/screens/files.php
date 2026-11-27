<?php /* Convertido de signage/list/signage.php del legacy (S4). Variables: ver el controlador. */ ?>
<div id="main">
	<div class="content">
		<h1>サイネージ管理</h1>
		
		<?php if ( count( $error_msg_ary ) > 0 ) { ?>
			<div class="error_area">
				<?=is_array($error_msg_ary) ? implode("<br>", $error_msg_ary) : null?>
			</div>
		<?php } ?>
		
		<?php if ( count( $success_msg_ary ) > 0 ) { ?>
			<div class="success_area">
				<?=is_array($success_msg_ary) ? implode("<br>", $success_msg_ary) : null?>
			</div>
		<?php } ?>
		
		<h2>新規追加</h2>
		<form action="<?=$_form_action ?? ''?>" method="POST" enctype="multipart/form-data"><?=csrf_field()?>
			<table class="tablebox">
				<colgroup>
					<col width="200">
					<col width="">
				</colgroup>
				<tbody>
					<tr>
						<th>ファイル</th>
						<td>
							<input type="file" name="upfile" size="30" />
						</td>
						<!--
						<th>右側カラー</th>
						<td>
							<input type="text" name="end_color" value="">
						</td>
						-->
					</tr>
				</tbody>
				</tbody>
			</table>
			
			<input type="hidden" name="insert" value="1">
			<br>
			<input type='submit' name='action' value='新規追加'>
		</form>
		
		<br>
		
		<h2>データ一覧</h2>
		<?php if ( !count( $data ) ) { ?>
			<p>データがありません。</p>
		<?php } else { ?>
			<form action="<?=$_form_action ?? ''?>" method="POST"><?=csrf_field()?>
				
				<table class="tablebox">
					<colgroup>
						<col width="50">
						<col width="70">
						<col width="">
						<col width="80">
					</colgroup>
					<thead>
						<tr>
							<th>番号</th>
							<th>画像</th>
							<th>ファイル名</th>
							<!--th>詳細</th-->
							<th>削除</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $data as $key => $val ) { ?>
						<tr>
							<td align="center">
								<input type="hidden" name="cd[]" value="<?=$val['cd']?>">
								<?=$val['cd']?>
							</td>
							<td>
								<img src="<?=$_base_url?>/upload/<?=\App\Services\Senalizacion\LegacyHtml::h($val['color'])?>" width="50">
							</td>
							<td>
								ファイル名 : <?=\App\Services\Senalizacion\LegacyHtml::h($val['color'])?>
								<!--
								右側 ( <?=\App\Services\Senalizacion\LegacyHtml::h($val['color'])?> ) ⇒ 左側( <?=\App\Services\Senalizacion\LegacyHtml::h($val['end_color'])?> )
								-->
							</td>
							<!--td align="center">
								<input type="button" value="詳細" onClick="location.href='<?=$_base_url?>/edit/customer.php?cd=<?=$val['cd']?>'">
							</td-->
							<td align="center">
								<input type="button" value="削除" onClick="if( window.confirm('本当によろしいでしょうか？')){ signagePost('<?=route('admin.senalizacion.files.destroy', $val['cd'])?>') }">
							</td>
						</tr>
						<?php } ?>
						<tr>
						</tr>
					</tbody>
				</table>
				
				<!--
				<br>
				
				<input type="hidden" name="update" value="1">
				<br>
				<input type='submit' name='action' value='更新'>
				-->
			</form>
		<?php } ?>
	</div>
</div>
