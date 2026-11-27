<?php /* Convertido de signage/list/contents_detail.php del legacy (S4). Variables: ver el controlador. */ ?>
<div id="main">
	<div class="content">
		
		<?php if ( (is_countable($error_msg_ary) ? count( $error_msg_ary ) : (isset($error_msg_ary) ? 1 : 0)) > 0 ) { ?>
			<div class="error_area">
				<?=is_array($error_msg_ary) ? implode("<br>", $error_msg_ary) : null?>
			</div>
		<?php } ?>
		
		<?php if ( (is_countable($success_msg_ary) ? count( $success_msg_ary ) : (isset($success_msg_ary) ? 1 : 0)) > 0 ) { ?>
			<div class="success_area">
				<?=is_array($success_msg_ary) ? implode("<br>", $success_msg_ary) : null?>
			</div>
		<?php } ?>
		
		<h2><?=\App\Services\Senalizacion\LegacyHtml::h($_contents_category_data['name'])?>サイズ編集</h2>
		
		<form action="<?=$_form_action ?? ''?>"  id="input_form" method="POST" enctype="multipart/form-data"><?=csrf_field()?>
			<table>
				<colgroup>
					<col width="200">
					<col width="">
				</colgroup>
				<tbody>
					<tr>
						<th>名称</th>
						<td>
							<input type="text" name="name" value="<?=\App\Services\Senalizacion\LegacyHtml::h($_detail_data['name'])?>" />
						</td>
					</tr>
					<tr>
						<th>ファイル</th>
						<td>
							<?php if ( isset( $_detail_data['img_path'] ) && !empty( $_detail_data['img_path'] ) ) { ?>
								<?php $f = $_signage_root . "/upload/" . $_contents_category_data['width'] . "x" . $_contents_category_data['height'] . "/" . ($_detail_data['img_path'] ?? null); ?>
									<img src="<?=$_base_url?>/upload/<?=$_contents_category_data['width']?>x<?=$_contents_category_data['height']?>/<?=$_detail_data['img_path']?>?q=<?=filemtime($f)?>" style="max-height:300px;max-widht:300px">
								<br>
							<?php } ?>
							<input type="file" name="upfile" size="30" />
						</td>
					</tr>
				</tbody>
				</tbody>
			</table>
			
			<input type="hidden" name="contents_category_cd" value="<?=(int)($_request['contents_category_cd'] ?? null)?>">
			<br>
			<?php if ( isset( $_request['cd'] ) && (int)($_request['cd'] ?? null) > 0 ) { ?>
				<input type="hidden" name="cd" value="<?=(int)($_request['cd'] ?? null)?>">
				<input type="hidden" name="update" value="1">
				<input type='submit' name='action' value='更新'>
				<!--input type="button" value="削除" onClick="if( window.confirm('本当によろしいでしょうか？')){ location.href='<?=$_base_url?>/list/contents.php?contents_category_cd=<?=$_detail_data['contents_category_cd']?>&delete_cd=<?=$_detail_data['cd']?>' }" style="float: right"-->
			<?php } ?>
		</form>
		<div style="margin: 0 auto; text-align: center">
		<input type="button" value="閉じる" onClick="window.opener.location.href='<?=route('admin.senalizacion.contents.index', [$_kind, $_detail_data['contents_category_cd'] ?? 0])?>'; window.close();" style="align: center">
		</div>
</div>
