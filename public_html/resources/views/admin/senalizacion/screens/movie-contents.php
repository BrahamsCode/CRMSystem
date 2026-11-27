<?php /* Convertido de signage/list/movie_contents.php del legacy (S4). Variables: ver el controlador. */ ?>
<div id="main">
	<div class="content">
		<h1>サイネージ管理</h1>
		
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
		
		<h2><?=\App\Services\Senalizacion\LegacyHtml::h($_contents_category_data['name'])?> 一覧</h2>
		
		<?php if ( isset( $_request['cd'] ) && (int)($_request['cd'] ?? null) > 0 ) { ?>
			<div>
				<h3>更新</h3>
				<div style="float: right; margin-top: -40px;">
					<input type="button" value="新規追加" onClick="location.href='<?=route('admin.senalizacion.contents.index', [$_kind, $_contents_category_data['cd'] ?? 0])?>'">
				</div>
			</div>
		<?php } else { ?>
			<h3>新規追加</h3>
		<?php } ?>
		<form action="<?=$_form_action ?? ''?>" method="POST" id="input_form" enctype="multipart/form-data"><?=csrf_field()?>
			<table class="tablebox">
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
							<?php if ( isset( $_detail_data['movie_path'] ) && !empty( $_detail_data['movie_path'] ) ) { ?>
								<?php $f = $_signage_root . "/upload/movie/" . $_contents_category_data['width'] . "x" . $_contents_category_data['height'] . "/" . $_detail_data['movie_path']; ?>
								<a href="javascript:void(0);" onclick="window.open('<?=$_base_url?>/upload/movie/<?=$_contents_category_data['width']?>x<?=$_contents_category_data['height']?>/<?=$_detail_data['movie_path']?>?q=<?=filemtime($f)?>', '_blank', 'width=800,height=600');">
									<video src="<?=$_base_url?>/upload/movie/<?=$_contents_category_data['width']?>x<?=$_contents_category_data['height']?>/<?=$val['movie_path']?>?q=<?=filemtime($f)?>" width="100" alt="<?=$alt?>" preload="preload" autoplay loop></video>
									<!--img src="<?=$_base_url?>/upload/<?=$_contents_category_data['width']?>x<?=$_contents_category_data['height']?>/<?=$_detail_data['img_path']?>?q=<?=filemtime($f)?>" width="200"-->
								</a><br>
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
			<?php } else { ?>
				<input type="hidden" name="insert" value="1">
				<input type='submit' name='action' value='新規追加'>
			<?php } ?>
		</form>
		
		<br>
		
		<h3>データ一覧</h3>
		<?php if ( !count( $data ) ) { ?>
			<p>データがありません。</p>
		<?php } else { ?>
				<ul class="datatable">
			<?php

				foreach ( $data as $key => $val ) {
				
					if ( empty( $val['name'] ) ) {
						$val['name'] = '名称未設定';
						$alt = '名称未設定';
					} else {
						$alt = $val['name'];
					}

					$f = $_signage_root . "/upload/" . $_contents_category_data['width'] . "x" . $_contents_category_data['height'] . "/" . $val['img_path'];
					
					if ( is_file( $f ) ) {
						$imginfo = getimagesize( $f );
						
						if ( isset( $imginfo[0] ) && isset( $imginfo[1] ) ) {
						
							if ( $imginfo[0] != $_contents_category_data['width'] || $imginfo[1] != $_contents_category_data['height'] ) {
								
								$img_name = <<<_EOL
								<strong style="color:red">
									{$val['name']}
								</strong>
_EOL;
								$img_size = <<<_EOL
								<strong style="color:red">
									{$imginfo[0]} x {$imginfo[1]}
								</strong>
_EOL;
							} else {
								$img_name = <<<_EOL
									{$val['name']}
_EOL;
								$img_size = <<<_EOL
								{$imginfo[0]} x {$imginfo[1]}
_EOL;
							}
						} else {
								$img_name = <<<_EOL
									{$val['name']}
_EOL;

							$img_size = '---';
						}
					} else {
								$img_name = <<<_EOL
									{$val['name']}
_EOL;

						$img_size = '---';
					}

				?>
					<li>
					<form action="<?=$_form_action ?? ''?>" method="POST"><?=csrf_field()?>
						
							<table class="tablebox">
								<colgroup>
									<!--col width="50"-->
									<col width="">
									<col width="">
									<col width="80">
									<col width="80">
									<col width="">
								</colgroup>
								<tbody>
								<tr>
									<td style="max-width: 120px; word-break: break-all; white-space: normal; text-align: center;" colspan="2">
										<?=$img_name?>
									</td>
								</tr>
								<tr>
									<!--td align="center">
										<input type="hidden" name="cd[]" value="<?=$val['cd']?>">
										<?=$val['cd']?>
									</td-->
									<td align="center" colspan="2">
										<!--a href="javascript:void(0);" onclick="window.open('<?=$_base_url?>/upload/<?=$_contents_category_data['width']?>x<?=$_contents_category_data['height']?>/<?=$val['img_path']?>?q=<?=filemtime($f)?>', '_blank', 'width=800,height=600');">
											<img src="<?=$_base_url?>/upload/<?=$_contents_category_data['width']?>x<?=$_contents_category_data['height']?>/<?=$val['img_path']?>?q=<?=filemtime($f)?>" width="100" alt="<?=$alt?>">
										</a-->
										<a href="javascript:void(0);" onclick="window.open('<?=route('admin.senalizacion.contents.detail', [$_kind, $val['cd']])?>', '_blank', 'width=800,height=600');">
											<video src="<?=$_base_url?>/upload/movie/<?=$_contents_category_data['width']?>x<?=$_contents_category_data['height']?>/<?=$val['movie_path']?>?q=<?=filemtime($f)?>" width="100" alt="<?=$alt?>" preload="preload" autoplay loop>
<?php /* ?>
											<img src="<?=$_base_url?>/upload/<?=$_contents_category_data['width']?>x<?=$_contents_category_data['height']?>/<?=$val['img_path']?>?q=<?=filemtime($f)?>" width="100" alt="<?=$alt?>">
<?php */ ?>
										</a>
									</td>
								</tr>
								<tr>
									<td align="center">
										<input type="button" value="変更" onClick="location.href='<?=route('admin.senalizacion.contents.index', [$_kind, $val['contents_category_cd'], 'cd' => $val['cd']])?>'">
									</td>
									<td align="center">
										<input type="button" value="削除" onClick="if( window.confirm('本当によろしいでしょうか？')){ signagePost('<?=route('admin.senalizacion.contents.destroy', [$_kind, $val['cd']])?>') }">
									</td>
								</tr>
								<tr>
									<td align="center" colspan="2">
										<?=$img_size?>
									</td>
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
					</li>
			<?php } ?>
				</ul>
		<?php } ?>
	</div>
