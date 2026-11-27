<?php /* Convertido de signage/list/cycle.php del legacy (S4). Variables: ver el controlador. */ ?>
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


		<?php if ( isset( $_request['cd'] ) && (int)($_request['cd'] ?? null) > 0 ) { ?>
			<div>
				<!--
				<h3>更新</h3>
				<div style="float: right; margin-top: -40px;">
					<input type="button" value="新規追加" onClick="location.href='<?=route('admin.senalizacion.cycles.edit', ['packagesCd' => $_packages_data['cd'] ?? 0])?>'">
				</div>
				-->
			</div>
		<?php } else { ?>
			<h3>新規追加</h3>
		<?php } ?>

		<h3>サイクル一覧</h3>
		<?php if ( !count( $data ) ) { ?>
			<p>データがありません。</p>
		<?php } else { ?>
			<form action="<?=$_form_action ?? ''?>" method="POST"><?=csrf_field()?>

				<table class="tablebox">
					<colgroup>
						<col width="50">
						<col width="100">
						<col width="200">
						<col width="">
						<col width="200">
						<col width="80">
					</colgroup>
					<thead>
						<tr>
							<th>サイクル</th>
							<th>状態</th>
							<th>表示方法</th>
							<th>表示時間帯</th>
							<th>表示フレーム</th>
							<th>編集</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $data as $key => $val ) { ?>
						<tr<?php if ( ($_request['cd'] ?? null) == $val['cd'] ) { ?> class="checkColor"<?php }?>>
							<td align="center">
								<input type="hidden" name="cd[]" value="<?=$val['cd']?>">
								<?=($key + 1)?>
							</td>
							<td align="center">
								<?php if ( $val['status'] == 1 ) { ?>
									<span class="active">表示中</span>
								<?php } else { ?>
									<span class="stop">停止中</span>
								<?php } ?>
							</td>
							<td align="center">
								<?php if ( $val['schedule_repeat_type'] == 1 ) { ?>
									<span class="">常に表示</span>
								<?php } else if ( $val['schedule_repeat_type'] == 2 ) { ?>
									<span class="">毎日特定の時間帯のみ</span>
								<?php } else { ?>
									<span class="">特定の期間のみ</span>
								<?php } ?>
							</td>
							<td align="center">
								<?php if ( $val['schedule_repeat_type'] == 1 ) { ?>
									---
								<?php } else if ( $val['schedule_repeat_type'] == 2 ) { ?>
									<?=date("H時i分", strtotime( $val['schedule_start_time'] ))?> から <?=date("H時i分", strtotime( $val['schedule_end_time'] ))?> まで
								<?php } else if ( $val['schedule_repeat_type'] == 3 ) { ?>
									<?=date("Y年m月d日H時i分", strtotime( $val['schedule_start_time'] ))?> から <?=date("Y年m月d日H時i分", strtotime( $val['schedule_end_time'] ))?> まで
								<?php } ?>
							</td>
							<td align="center">

								<?php
									if ( $__design = $_design( "inc_flame_cd", $_packages_data['display_type'] ?? null ) ) {
										require( $__design );
									} else {
										echo "---";
									}
								?>

							</td>

							<td align="center">
								<?php if ( ($_request['cd'] ?? null) == $val['cd'] ) { ?>
									<span class="active">選択中</span>
								<?php } else { ?>
									<input type="button" value="選択" onClick="location.href='<?=route('admin.senalizacion.cycles.edit', ['packagesCd' => $_packages_data['cd'] ?? 0, 'cd' => $val['cd']])?>'">
								<?php } ?>
							</td>
						</tr>
						<?php } ?>
						<tr>
						</tr>
					</tbody>
				</table>

			</form>
		<?php } ?>

		<br>

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
								<?php if ( $_detail_data['status'] == 1 ) { ?>
									<input type="radio" name="status" value="1" checked="checked"> 表示中
								<?php } else { ?>
									<input type="radio" name="status" value="1" checked="checked"> 表示中
								<?php } ?>
							</label>　
							<label>
								<?php if ( $_detail_data['status'] != 1 ) { ?>
									<input type="radio" name="status" value="0" checked="checked"> 停止中
								<?php } else {?>
									<input type="radio" name="status" value="0"> 停止中
								<?php } ?>
							</label>
						</td>
					</tr>
					<tr>
						<th>表示方法</th>
						<td>
							<label>
								<?php if ( $_detail_data['schedule_repeat_type'] == 0 || $_detail_data['schedule_repeat_type'] == 1 ) { ?>
									<input type="radio" name="schedule_repeat_type" value="1" checked="checked"> 常に表示
								<?php } else { ?>
									<input type="radio" name="schedule_repeat_type" value="1"> 常に表示
								<?php } ?>
							<label>　
							<label>
								<?php if ( $_detail_data['schedule_repeat_type'] == 2 ) { ?>
									<input type="radio" name="schedule_repeat_type" value="2" checked="checked"> 毎日特定の時間帯のみ
								<?php } else { ?>
									<input type="radio" name="schedule_repeat_type" value="2"> 毎日特定の時間帯のみ
								<?php } ?>
							<label>　
							<label>
								<?php if ( $_detail_data['schedule_repeat_type'] == 3 ) { ?>
									<input type="radio" name="schedule_repeat_type" value="3" checked="checked"> 特定の期間のみ
								<?php } else { ?>
									<input type="radio" name="schedule_repeat_type" value="3"> 特定の期間のみ
								<?php } ?>
							<label>
						</td>
					</tr>
					<tr>
						<th>表示時間帯</th>
						<td>
							<div class="none_view" style="display: none">『常に表示』の場合は設定が不要になります。</div>
							<div class="active_view">
								開始時間：<select name="start_y">
									<!--option value="">---</option-->
									<?php foreach ( range(date("Y"), date("Y") + 1) as $key => $val ) { ?>
										<?php if ( $val == $_detail_data['schedule_start_time_y'] ) { ?>
											<option value="<?=$val?>" selected><?=$val?></option>
										<?php } else { ?>
											<option value="<?=$val?>"><?=$val?></option>
										<?php } ?>
									<?php } ?>
								</select><span class="start_y">年</span>
								<select name="start_m">
									<!--option value="">---</option-->
									<?php foreach ( range( 1, 12 ) as $key => $val ) { ?>
										<?php if ( $val == $_detail_data['schedule_start_time_m'] ) { ?>
											<option value="<?=$val?>" selected><?=$val?></option>
										<?php } else { ?>
											<option value="<?=$val?>"><?=$val?></option>
										<?php } ?>
									<?php } ?>
								</select><span class="start_m">月</span>
								<select name="start_d">
									<!--option value="">---</option-->
									<?php foreach ( range( 1, 31 ) as $key => $val ) { ?>
										<?php if ( $val == $_detail_data['schedule_start_time_d'] ) { ?>
											<option value="<?=$val?>" selected><?=$val?></option>
										<?php } else { ?>
											<option value="<?=$val?>"><?=$val?></option>
										<?php } ?>
									<?php } ?>
								</select><span class="start_d">日</span>
								<select name="start_h">
									<!--option value="">---</option-->
									<?php foreach ( range( 0, 23 ) as $key => $val ) { ?>
										<?php if ( isset( $_detail_data['schedule_start_time_h'] ) && $val == $_detail_data['schedule_start_time_h'] ) { ?>
											<option value="<?=$val?>" selected><?=$val?></option>
										<?php } else { ?>
											<option value="<?=$val?>"><?=$val?></option>
										<?php } ?>
									<?php } ?>
								</select>時
								<select name="start_i">
									<!--option value="">---</option-->
									<?php foreach ( range( 0, 59, 15 ) as $key => $val ) { ?>
										<?php if ( isset( $_detail_data['schedule_start_time_i'] ) && $val == $_detail_data['schedule_start_time_i'] ) { ?>
											<option value="<?=$val?>" selected><?=$val?></option>
										<?php } else { ?>
											<option value="<?=$val?>"><?=$val?></option>
										<?php } ?>
									<?php } ?>
								</select>分 ～
								終了時間：<select name="end_y">
									<!--option value="">---</option-->
									<?php foreach ( range(date("Y"), date("Y") + 1) as $key => $val ) { ?>
										<?php if ( $val == $_detail_data['schedule_end_time_y'] ) { ?>
											<option value="<?=$val?>" selected><?=$val?></option>
										<?php } else { ?>
											<option value="<?=$val?>"><?=$val?></option>
										<?php } ?>
									<?php } ?>
								</select><span class="end_y">年</span>
								<select name="end_m">
									<!--option value="">---</option-->
									<?php foreach ( range( 1, 12 ) as $key => $val ) { ?>
										<?php if ( $val == $_detail_data['schedule_end_time_m'] ) { ?>
											<option value="<?=$val?>" selected><?=$val?></option>
										<?php } else { ?>
											<option value="<?=$val?>"><?=$val?></option>
										<?php } ?>
									<?php } ?>
								</select><span class="end_m">月</span>
								<select name="end_d">
									<!--option value="">---</option-->
									<?php foreach ( range( 1, 31 ) as $key => $val ) { ?>
										<?php if ( $val == $_detail_data['schedule_end_time_d'] ) { ?>
											<option value="<?=$val?>" selected><?=$val?></option>
										<?php } else { ?>
											<option value="<?=$val?>"><?=$val?></option>
										<?php } ?>
									<?php } ?>
								</select><span class="end_d">日</span>
								<select name="end_h">
									<!--option value="">---</option-->
									<?php foreach ( range( 0, 23 ) as $key => $val ) { ?>
										<?php if ( isset( $_detail_data['schedule_end_time_h'] ) && $val == $_detail_data['schedule_end_time_h'] ) { ?>
											<option value="<?=$val?>" selected><?=$val?></option>
										<?php } else { ?>
											<option value="<?=$val?>"><?=$val?></option>
										<?php } ?>
									<?php } ?>
								</select>時
								<select name="end_i">
									<!--option value="">---</option-->
									<?php foreach ( range( 0, 59, 15 ) as $key => $val ) { ?>
										<?php if ( isset( $_detail_data['schedule_end_time_i'] ) && $val == $_detail_data['schedule_end_time_i'] ) { ?>
											<option value="<?=$val?>" selected><?=$val?></option>
										<?php } else { ?>
											<option value="<?=$val?>"><?=$val?></option>
										<?php } ?>
									<?php } ?>
								</select>分
							</div>
						</td>
					</tr>
					<tr>
						<th>表示フレーム</th>
						<td>
							<?php if ( isset ( $_packages_data['display_number'] ) && $_packages_data['display_number'] > 0 && $_packages_data['display_number'] == 8 ) { ?>
							<span>
								ディスプレイ分割数：<select name="split_number" style="margin-bottom: 5px;">
									<?php
										foreach ( range(  $_packages_data['display_number'], 1 ) as $key => $val ) {

											$check_val = 1;
											if ( $_detail_data['flame_cd'] == 1 ) {
												$check_val = 8;
											} else if ( $_detail_data['flame_cd'] >= 2 && $_detail_data['flame_cd'] <= 11 ) {
												$check_val = 7;
											} else if ( $_detail_data['flame_cd'] >= 12 && $_detail_data['flame_cd'] <= 42 ) {
												$check_val = 6;
											} else if ( $_detail_data['flame_cd'] >= 43 && $_detail_data['flame_cd'] <= 69 ) {
												$check_val = 5;
											} else if ( $_detail_data['flame_cd'] >= 70 && $_detail_data['flame_cd'] <= 97 ) {
												$check_val = 4;
											} else if ( $_detail_data['flame_cd'] >= 98 && $_detail_data['flame_cd'] <= 112 ) {
												$check_val = 3;
											} else if ( $_detail_data['flame_cd'] >= 113 && $_detail_data['flame_cd'] <= 116 ) {
												$check_val = 2;
											} else {
												$check_val = 1;
											}

									?>
										<?php if ( $val == $check_val ) { ?>
											<option value="<?=$val?>" selected><?=$val?></option>
										<?php } else { ?>
											<option value="<?=$val?>"><?=$val?></option>
										<?php } ?>
									<?php } ?>
								</select>
							</span>
							<?php } ?>

							<div class="frame_table_wrap">
								<table class="frame_table<?=$_packages_data['display_number']?>">
									<colgroup>
										<col width="150">
										<col width="">
									</colgroup>

									<?php
										if ( $__design = $_design( "inc", $_packages_data['display_type'] ?? null ) ) {
											require( $__design );
										} else {
											echo "申し訳ありません。現在、準備中です。";
										}
									?>

								</table>
							</div>
						</td>
					</tr>

					<tr>
						<th>画像登録</th>
						<td>
							<table>
								<colgroup>
									<col class="col_padd_base col_padd_1" width="12.5%">
									<col class="col_padd_base col_padd_2" width="12.5%">
									<col class="col_padd_base col_padd_3" width="12.5%">
									<col class="col_padd_base col_padd_4" width="12.5%">
									<col class="col_padd_base col_padd_5" width="12.5%">
									<col class="col_padd_base col_padd_6" width="12.5%">
									<col class="col_padd_base col_padd_7" width="12.5%">
									<col class="col_padd_base col_padd_8" width="12.5%">
								</colgroup>
									<tr>
										<?php foreach ( range( "A", "H" ) as $k => $v ) { ?>
											<td align="center" class="tr_Rebase tr_<?=$v?>">
												<?=$v?>画面
											</td>
										<?php } ?>
									</tr>
									<tr>
										<?php foreach ( range( "A", "H" ) as $k => $v ) { ?>
										<td class="tr_Rebase tr_<?=$v?>" valign="top">
											画面効果：
											<select name="action_ary[<?=$k?>][action]" style="width: 100%; display: block; margin-bottom: -15px; margin-top: 1px; font-size: 17px; font-weight: bold;">
												<?php if ( isset( $_detail_data['action_ary'][$k]['action'] ) && 1 == $_detail_data['action_ary'][$k]['action'] ) { ?>
													<option value="1" selected>縦スライド</option>
												<?php } else { ?>
													<option value="1">縦スライド</option>
												<?php } ?>
												<?php if ( isset( $_detail_data['action_ary'][$k]['action'] ) && 2 == $_detail_data['action_ary'][$k]['action'] ) { ?>
													<option value="2" selected>横スライド</option>
												<?php } else { ?>
													<option value="2">横スライド</option>
												<?php } ?>

												<?php if ( isset( $_detail_data['action_ary'][$k]['action'] ) && 3 == $_detail_data['action_ary'][$k]['action'] ) { ?>
													<option value="3" selected>フェード</option>
												<?php } else { ?>
													<option value="3">フェード</option>
												<?php } ?>
											</select><br>

											<table>
												<colgroup>
													<col width="100%">
												</colgroup>
												<?php foreach ( range( 1, 5 ) as $num => $num_val ) { ?>
													<tr>
														<td valign="top">
															<?=$num_val?>枚目<br>

															<?php if ( count( $_contents_data ) == 0 ) { ?>
																---
															<?php } else { ?>
																<?php $is_file = false; ?>
																<?php foreach ( $_contents_data as $key => $val ) { ?>
																	<?php if ( isset( $_detail_data['img_path_ary'][$k][$num] ) && $val['cd'] == $_detail_data['img_path_ary'][$k][$num] ) { ?>
																		<?php
																			$is_file = true;
																			//コンテンツカテゴリ
																			$_contents_category_data = $_category_by_cd[$val['contents_category_cd']] ?? array();
																			$f = $_signage_root . "/upload/" . $_contents_category_data['width'] . "x" . $_contents_category_data['height'] . "/" . $val['img_path'];
																		?>
																		<a href="javascript:void(0);" class="view_img" onclick="window.open('<?=$_base_url?>/upload/<?=$_contents_category_data['width']?>x<?=$_contents_category_data['height']?>/<?=$val['img_path']?>?q=<?=filemtime($f)?>', '_blank', 'width=800,height=600');">
																			<img src="<?=$_base_url?>/upload/<?=$_contents_category_data['width']?>x<?=$_contents_category_data['height']?>/<?=$val['img_path']?>?q=<?=filemtime($f)?>" width="100%">
																		</a><br>
																	<?php } ?>
																<?php } ?>
																<?php if ( !$is_file ) {?>
																	<a href="javascript:void(0);" class="view_img" onclick="window.open('<?=$_base_url?>/img/blank1920x1080.jpg', '_blank', 'width=800,height=600');">
																		<img src="<?=$_base_url?>/img/blank1920x1080.jpg" width="100%">
																	</a><br>
																<?php } ?>
																画像：<select name="img_path_ary[<?=$k?>][<?=$num?>]" style="width:65%">
																	<option value="blank">空白</option>
																	<?php foreach ( $_contents_data as $key => $val ) { ?>
																		<?php if ( isset( $_detail_data['img_path_ary'][$k][$num] ) && $val['cd'] == $_detail_data['img_path_ary'][$k][$num] ) { ?>
																			<option data-contents_category_cd="<?=$val['contents_category_cd']?>" img_path="<?=$val['img_path']?>" value="<?=$val['cd']?>" selected><?=\App\Services\Senalizacion\LegacyHtml::h($val['name'])?></option>
																		<?php } else { ?>
																			<option data-contents_category_cd="<?=$val['contents_category_cd']?>" img_path="<?=$val['img_path']?>" value="<?=$val['cd']?>"><?=\App\Services\Senalizacion\LegacyHtml::h($val['name'])?></option>
																		<?php } ?>
																	<?php } ?>
																</select> <br>
															<?php } ?>

															<div style="display:none">
															効果：<select name="action_ary[<?=$k?>][<?=$num?>]" style="width:65%">
																<?php if ( isset( $_detail_data['action_ary'][$k][$num] ) && 1 == $_detail_data['action_ary'][$k][$num] ) { ?>
																	<option value="1" selected>縦スライド</option>
																<?php } else { ?>
																	<option value="1">縦スライド</option>
																<?php } ?>

<!--
																<?php if ( isset( $_detail_data['action_ary'][$k][$num] ) && 2 == $_detail_data['action_ary'][$k][$num] ) { ?>
																	<option value="2" selected>横スライド</option>
																<?php } else { ?>
																	<option value="2">横スライド</option>
																<?php } ?>

																<?php if ( isset( $_detail_data['action_ary'][$k][$num] ) && 3 == $_detail_data['action_ary'][$k][$num] ) { ?>
																	<option value="3" selected>フェード</option>
																<?php } else { ?>
																	<option value="3">フェード</option>
																<?php } ?>
-->

															</select>
															</div>
														</td>
													</tr>
												<?php } ?>
											</table>
										</td>
									<?php } ?>
								</tr>
							</table>
						</td>
						※『表示フレーム』を変更された場合、既に設定されている画像は必ず変更ください。レイアウト崩れの原因となります。
					</tr>

				</tbody>
			</table>

			<br>
			<input type="hidden" name="packages_cd" value="<?=(int)($_request['packages_cd'] ?? null)?>">
			<?php if ( isset( $_request['cd'] ) && (int)($_request['cd'] ?? null) > 0 ) { ?>
				<input type="hidden" name="cd" value="<?=(int)($_request['cd'] ?? null)?>">
				<input type="hidden" name="update" value="1">
				<input type='submit' name='action' value='更新'>
			<?php } else { ?>
				<input type="hidden" name="insert" value="1">
				<input type='submit' name='action' value='新規追加'>
			<?php } ?>
		</form>

	</div>
</div>
