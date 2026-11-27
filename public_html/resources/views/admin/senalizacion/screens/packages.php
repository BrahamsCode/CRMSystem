<?php /* Convertido de signage/list/packages.php del legacy (S4). Variables: ver el controlador. */ ?>
<div id="main">
	<div class="content">
		
		<?php if ( (is_countable($error_msg_ary) ? count( $error_msg_ary ) : (isset($error_msg_ary) ? 1 : 0)) > 0 ) { ?>
			<div class="error_area" style="margin:25px 0 8px 0;">
				<?=is_array($error_msg_ary) ? implode("<br>", $error_msg_ary) : null?>
			</div>
		<?php } ?>
		
		<?php if ( (is_countable($success_msg_ary) ? count( $success_msg_ary ) : (isset($success_msg_ary) ? 1 : 0)) > 0 ) { ?>
			<div class="success_area" style="margin:25px 0 8px 0;">
				<?=is_array($success_msg_ary) ? implode("<br>", $success_msg_ary) : null?>
			</div>
		<?php } ?>
		<div class="main-wrap">
			<div class="title-box">
				<h1 class="page-title">パッケージ一覧</h1>
			</div>
			<img src="<?=$_base_url?>/css/img/signage_bottom_arrow.png" style="height:20px; width:22px;">
		</div>
		
		<?php if ( isset( $_request['cd'] ) && (int)($_request['cd'] ?? null) > 0 ) { ?>
			<div>
				<h3>更新</h3>
				<div style="float: right; margin-top: -40px;">
					<input type="button" value="新規追加" onClick="location.href='<?=route('admin.senalizacion.packages.index')?>'">
				</div>
			</div>
		<?php } else { ?>
			<h3>新規追加</h3>
		<?php } ?>
		<form action="<?=$_form_action ?? ''?>" method="POST" enctype="multipart/form-data"><?=csrf_field()?>
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
								<?php if ( $_detail_data['flg'] == 1 ) { ?>
									<input type="radio" name="flg" value="1" checked="checked"> 利用中
								<?php } else { ?>
									<input type="radio" name="flg" value="1" checked="checked"> 利用中
								<?php } ?>
							</label>　
							<label>
								<?php if ( $_detail_data['flg'] != 1 ) { ?>
									<input type="radio" name="flg" value="0" checked="checked"> 停止中
								<?php } else {?>
									<input type="radio" name="flg" value="0"> 停止中
								<?php } ?>
							</label>
						</td>
					</tr>
					<tr>
						<th>名称</th>
						<td>
							<input type="text" name="name" value="<?=\App\Services\Senalizacion\LegacyHtml::h($_detail_data['name'])?>" />
						</td>
					</tr>
					<tr>
						<th>サイクル数</th>
						<td>
							<input type="hidden" name="above_cycle" value="<?=$_detail_data['cycle']?>">
							<select name="cycle">
								<?php foreach ( range(1, 20) as $key => $val ) { ?>
									<?php if ( $_detail_data['cycle'] == $val ) { ?>
										<option value="<?=$val?>" selected><?=$val?></option>
									<?php } else { ?>
										<option value="<?=$val?>"><?=$val?></option>
									<?php } ?>
								<?php } ?>
							<select>
						</td>
					</tr>
					<tr>
						<th>ディスプレイ設定</th>
						<td>
							
							<table width="100%" style="margin-top: 5px">
								<colgroup>
									<col width="150">
									<col width="">
								</colgroup>
								
								<tr>
									<td>ディスプレイ方向</td>
									<td>
										<select name="display_direction">
											<?php foreach ( array( "横", "縦" ) as $key => $val ) { ?>
												<?php if ( $val == $_detail_data['display_direction'] ) { ?>
													<option value="<?=$val?>" selected><?=$val?>方向</option>
												<?php } else { ?>
													<option value="<?=$val?>"><?=$val?>方向</option>
												<?php } ?>
											<?php } ?>
										</select>
									</td>
								</tr>
								<tr>
									<td>ディスプレイ枚数</td>
									<td>
										<select name="display_number">
											<?php 
												$display_num = array(1,2,4);
												foreach ( $display_num as $key => $val ) { ?>
												<?php if ( $val == $_detail_data['display_number'] ) { ?>
													<option value="<?=$val?>" selected><?=$val?></option>
												<?php } else { ?>
													<option value="<?=$val?>"><?=$val?></option>
												<?php } ?>
											<?php } ?>
										</select> 枚
									</td>
								</tr>
								
								<tr>
									<td>ディスプレイパターン</td>
									<td>
										
										<table width="100%" style="margin-top: 5px">
											<colgroup>
												<col width="25%">
												<col width="25%">
												<col width="25%">
												<col width="25%">
											</colgroup>
											
											<!-- 画面1 -->
											<?php foreach ( array( "h", "v" ) as $vh_key => $vh_val ) { ?>
												<?php foreach ( range( 1, 4 /*8*/ ) as $disp_key => $disp_val ) { ?>
													<tr class="display_base display_number_<?=$vh_val?>_<?=$disp_val?>">
													<?php if ( $disp_val !=4) { ?>
														<td valign="top">
															<label>
																<?php if ( $_detail_data['display_type'] == $vh_val . "_" . $disp_val . "_1" ) { ?>
																	<input type="radio" name="display_type" value="<?=$vh_val?>_<?=$disp_val?>_1" checked="checked"> 
																<?php } else { ?>
																	<input type="radio" name="display_type" value="<?=$vh_val?>_<?=$disp_val?>_1"> 
																<?php } ?>
																<?=$disp_val?> 画面 (パターン①)
																<table style="width: auto;">
																	<colgroup>
																		<?php foreach ( range(1, $disp_val) as $key => $val ) { ?>
																			<?php if ( $vh_val == "h" ) { ?>
																				<col width="50">
																			<?php } else { ?>
																				<col width="31">
																			<?php } ?>
																		<?php } ?>
																	</colgroup>
																	<tbody class="b4">
																		<tr>
																			<?php foreach ( range(1, $disp_val) as $key => $val ) { ?>
																				<?php if ( $vh_val == "h" ) { ?>
																					<td align="center"><?=$val?></td>
																				<?php } else { ?>
																					<td align="center" height="39"><?=$val?></td>
																				<?php } ?>
																			<?php } ?>
																		</tr>
																	</tbody>
																</table>
															</label>
														</td>
														<?php } ?>
														<?php if ( $disp_val != 1 and $disp_val != 4) { ?>
															<td valign="top">
																<label>
																	<?php if ( $_detail_data['display_type'] == $vh_val . "_1_" . $disp_val ) { ?>
																		<input type="radio" name="display_type" value="<?=$vh_val?>_1_<?=$disp_val?>" checked="checked"> 
																	<?php } else { ?>
																		<input type="radio" name="display_type" value="<?=$vh_val?>_1_<?=$disp_val?>"> 
																	<?php } ?>
																	<?=$disp_val?> 画面 (パターン②)
																	<table style="width: auto;">
																		<colgroup>
																			<?php if ( $vh_val == "h" ) { ?>
																				<col width="50">
																			<?php } else { ?>
																				<col width="31">
																			<?php } ?>
																		</colgroup>
																		<tbody class="b4">
																			<?php foreach ( range(1, $disp_val) as $key => $val ) { ?>
																				<tr>
																					<?php if ( $vh_val == "h" ) { ?>
																						<td align="center"><?=$val?></td>
																					<?php } else { ?>
																						<td align="center" height="39"><?=$val?></td>
																					<?php } ?>
																				</tr>
																			<?php } ?>
																		</tbody>
																	</table>
																</label>
															</td>
														<?php } ?>
														
														<?php if ( $disp_val == 4 || $disp_val == 6 || $disp_val == 8 ) { ?>
															<td valign="top">
																<label>
																	<?php if ( $_detail_data['display_type'] == $vh_val . "_" . ($disp_val/2) . "_2" ) { ?>
																		<input type="radio" name="display_type" value="<?=$vh_val?>_<?=$disp_val/2?>_2" checked="checked"> 
																	<?php } else { ?>
																		<input type="radio" name="display_type" value="<?=$vh_val?>_<?=$disp_val/2?>_2"> 
																	<?php } ?>
																	<?=$disp_val?> 画面 (パターン①)
																	<table style="width: auto;">
																		<colgroup>
																			<?php foreach ( range(1, $disp_val) as $key => $val ) { ?>
																				<?php if ( $vh_val == "h" ) { ?>
																					<col width="50">
																				<?php } else { ?>
																					<col width="31">
																				<?php } ?>
																			<?php } ?>
																		</colgroup>
																		<tbody class="b4">
																			<tr>
																				<?php foreach ( range(1, $disp_val/2) as $key => $val ) { ?>
																					<?php if ( $vh_val == "h" ) { ?>
																						<td align="center"><?=$val?></td>
																					<?php } else { ?>
																						<td align="center" height="39"><?=$val?></td>
																					<?php } ?>
																				<?php } ?>
																			</tr>
																			<tr>
																				<?php foreach ( range(1, $disp_val/2) as $key => $val ) { ?>
																					<?php if ( $vh_val == "h" ) { ?>
																						<td align="center"><?=$val + $disp_val/2?></td>
																					<?php } else { ?>
																						<td align="center" height="39"><?=$val + $disp_val/2?></td>
																					<?php } ?>
																				<?php } ?>
																			</tr>
																		</tbody>
																	</table>
																</label>
															</td>
														<?php } ?>
														
														<?php if ( $disp_val == 6 ) { ?>
															<td valign="top">
																<label>
																	<?php if ( $_detail_data['display_type'] == $vh_val . "_" . ($disp_val/3) . "_3" ) { ?>
																		<input type="radio" name="display_type" value="<?=$vh_val?>_<?=$disp_val/3?>_3" checked="checked"> 
																	<?php } else { ?>
																		<input type="radio" name="display_type" value="<?=$vh_val?>_<?=$disp_val/3?>_3"> 
																	<?php } ?>
																	<?=$disp_val?> 画面 (パターン④)
																	<table style="width: auto;">
																		<colgroup>
																			<?php foreach ( range(1, $disp_val) as $key => $val ) { ?>
																				<?php if ( $vh_val == "h" ) { ?>
																					<col width="50">
																				<?php } else { ?>
																					<col width="31">
																				<?php } ?>
																			<?php } ?>
																		</colgroup>
																		<tbody class="b4">
																			<tr>
																				<?php foreach ( range(1, $disp_val/3) as $key => $val ) { ?>
																					<?php if ( $vh_val == "h" ) { ?>
																						<td align="center"><?=$val?></td>
																					<?php } else { ?>
																						<td align="center" height="39"><?=$val?></td>
																					<?php } ?>
																				<?php } ?>
																			</tr>
																			<tr>
																				<?php foreach ( range(1, $disp_val/3) as $key => $val ) { ?>
																					<?php if ( $vh_val == "h" ) { ?>
																						<td align="center"><?=$val + $disp_val/3?></td>
																					<?php } else { ?>
																						<td align="center" height="39"><?=$val + $disp_val/3?></td>
																					<?php } ?>
																				<?php } ?>
																			</tr>
																			<tr>
																				<?php foreach ( range(1, $disp_val/3) as $key => $val ) { ?>
																					<?php if ( $vh_val == "h" ) { ?>
																						<td align="center"><?=$val + $disp_val*2/3?></td>
																					<?php } else { ?>
																						<td align="center" height="39"><?=$val + $disp_val*2/3?></td>
																					<?php } ?>
																				<?php } ?>
																			</tr>
																		</tbody>
																	</table>
																</label>
															</td>
														<?php } ?>
														
														<?php if ( $disp_val == 8 ) { ?>
															<td valign="top">
																<label>
																	<?php if ( $_detail_data['display_type'] == $vh_val . "_" . ($disp_val/4) . "_4" ) { ?>
																		<input type="radio" name="display_type" value="<?=$vh_val?>_<?=$disp_val/4?>_4" checked="checked"> 
																	<?php } else { ?>
																		<input type="radio" name="display_type" value="<?=$vh_val?>_<?=$disp_val/4?>_4"> 
																	<?php } ?>
																	<?=$disp_val?> 画面 (パターン④)
																	<table style="width: auto;">
																		<colgroup>
																			<?php foreach ( range(1, $disp_val) as $key => $val ) { ?>
																				<?php if ( $vh_val == "h" ) { ?>
																					<col width="50">
																				<?php } else { ?>
																					<col width="31">
																				<?php } ?>
																			<?php } ?>
																		</colgroup>
																		<tbody class="b4">
																			<tr>
																				<?php foreach ( range(1, $disp_val/4) as $key => $val ) { ?>
																					<?php if ( $vh_val == "h" ) { ?>
																						<td align="center"><?=$val?></td>
																					<?php } else { ?>
																						<td align="center" height="39"><?=$val?></td>
																					<?php } ?>
																				<?php } ?>
																			</tr>
																			<tr>
																				<?php foreach ( range(1, $disp_val/4) as $key => $val ) { ?>
																					<?php if ( $vh_val == "h" ) { ?>
																						<td align="center"><?=$val + $disp_val/4?></td>
																					<?php } else { ?>
																						<td align="center" height="39"><?=$val + $disp_val/4?></td>
																					<?php } ?>
																				<?php } ?>
																			</tr>
																			<tr>
																				<?php foreach ( range(1, $disp_val/4) as $key => $val ) { ?>
																					<?php if ( $vh_val == "h" ) { ?>
																						<td align="center"><?=$val + $disp_val*2/4?></td>
																					<?php } else { ?>
																						<td align="center" height="39"><?=$val + $disp_val*2/4?></td>
																					<?php } ?>
																				<?php } ?>
																			</tr>
																			<tr>
																				<?php foreach ( range(1, $disp_val/4) as $key => $val ) { ?>
																					<?php if ( $vh_val == "h" ) { ?>
																						<td align="center"><?=$val + $disp_val*3/4?></td>
																					<?php } else { ?>
																						<td align="center" height="39"><?=$val + $disp_val*3/4?></td>
																					<?php } ?>
																				<?php } ?>
																			</tr>
																		</tbody>
																	</table>
																</label>
															</td>
														<?php } ?>
													</tr>
												<?php } ?>
											<?php } ?>
										</table>
										<div class="success_area" style="margin-top: 5px">
											現在、デモ運用のため下記のパターンのみ利用いただけます。<br>
											<b>『ディスプレイ方向:横』</b>
										</div>
									</td>
								</tr>
							</table>
						</td>
					</tr>
				</tbody>
				</tbody>
			</table>
			
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
			<form action="<?=$_form_action ?? ''?>" method="POST"><?=csrf_field()?>
				
				<table class="tablebox">
					<colgroup>
						<col width="50">
						<col width="100">
						<col style="display:none" width="200">
						<col width="">
						<col width="200">
						<col width="50">
						<col width="80">
						<col width="80">
						<col width="80">
					</colgroup>
					<thead>
						<tr>
							<th>番号</th>
							<th>状態</th>
							<th style="display:none">端末名称</th>
							<th>名称</th>
							<th>ディスプレイ設定</th>
							<th>サイクル数</th>
							<th>詳細</th>
							<th>変更</th>
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
							<td align="center">
								<?php if ( $val['flg'] == 1 ) { ?>
									<span class="active">利用中</span>
								<?php } else { ?>
									<span class="stop">停止中</span>
								<?php } ?>
							</td>
							<td>
								<?=\App\Services\Senalizacion\LegacyHtml::h($val['name'])?>
							</td>
							<td style="display:none">
								<?php if ( !isset( $_terminal_data[$val['terminal_cd']] ) ) { ?>
									未設定
								<?php } else { ?>
									<?=$_terminal_data[$val['terminal_cd']]['name']?>
								<?php } ?>
							</td>
							<td align="center">
								<?=$val['display_direction']?>画面 <?=$val['display_number']?>枚
							</td>
							<td align="center">
								<?=$val['cycle']?>
							</td>
							<td align="center">
								<?php /* primer ciclo del paquete (SELECT cd FROM cycle WHERE packages_cd ORDER BY cd LIMIT 1) */ ?>
								
								<?php $_check_row = array( 'cd' => $_first_cycle_cd[$val['cd']] ?? '' ); ?>
								<input type="button" value="詳細" onClick="location.href='<?=route('admin.senalizacion.cycles.edit', ['packagesCd' => $val['cd'], 'cd' => $_check_row['cd']])?>'">
							</td>
							<td align="center">
								<input type="button" value="変更" onClick="location.href='<?=route('admin.senalizacion.packages.index', ['cd' => $val['cd']])?>'">
							</td>
							<td align="center">
								<input type="button" value="削除" onClick="if( window.confirm('本当によろしいでしょうか？')){ signagePost('<?=route('admin.senalizacion.packages.destroy', $val['cd'])?>') }">
							</td>
						</tr>
						<!--
						<tr>
							<td colspan="7">
								設定用URL : 
									<a href="http://signage.vizan.co.jp/view?cd=<?=$val['cd']?>" target="_blank">http://signage.vizan.co.jp/view?cd=<?=$val['cd']?></a>
							</td>
						</tr>
						-->
						<?php } ?>
						<tr>
						</tr>
					</tbody>
				</table>
				
			</form>
		<?php } ?>
	</div>
</div>
