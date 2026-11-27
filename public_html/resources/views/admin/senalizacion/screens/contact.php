<?php /* Convertido de signage/mail/index.php del legacy (S4, PROVISIONAL). Variables: ver ContactController. El 本文 del 確認 se escapa (el legacy lo pintaba sin h()). */ ?>
<div id="main">
	<div class="content">
		<h1>GoTel管理</h1>
		
		<h2>お問い合わせ</h2>
		
		<?php if ( isset( $_request['send'] ) && (is_string($_request['send']) && !is_numeric($_request['send']) ? (float) $_request['send'] : $_request['send']) == 1 ) { ?>
			
			<p>
				お問い合わせいただきありがとうございました。<br>
				お問い合わせを受け付けました。<br>
				<br>
				折り返し、担当者よりご連絡いたしますので、 <br>
				恐れ入りますが、しばらくお待ちください。<br>
				<br>
				なお、ご入力いただいたメールアドレス宛に受付完了メールを配信しております。 <br>
				完了メールが届かない場合、処理が正常に行われていない可能性があります。 <br>
				大変お手数ですが、再度お問い合わせの手続きをお願い致します。
			</p>
			
		<?php } else if ( isset( $_request['confirm'] ) && (is_string($_request['confirm']) && !is_numeric($_request['confirm']) ? (float) $_request['confirm'] : $_request['confirm']) == 1 ) { ?>
			
			<form action="<?=e($_form_action)?>" method="POST"><?=csrf_field()?>
				<h3>入力フォーム</h3>
				<table class="tablebox">
					<colgroup>
						<col width="200">
						<col width="">
					</colgroup>
					<tbody>
						<tr>
							<th>件名</th>
							<td>
								<?=e($_request['subject'])?>
								<input type="hidden" name="subject" value="<?=e($_request['subject'])?>">
							</td>
						</tr>
						<tr>
							<th>お名前</th>
							<td>
								<?=e($_request['name'])?>
								<input type="hidden" name="name" value="<?=e($_request['name'])?>">
							</td>
						</tr>
						<tr>
							<th>本文</th>
							<td>
								<?=nl2br(e($_request['message'] ?? ''))?>
								<input type="hidden" name="message" value="<?=e($_request['message'])?>">
							</td>
						</tr>
					</tbody>
				</table>
				
				<input type="hidden" name="send" value="1">
				<br>
				<input type='submit' name='action' value='確認'>
			</form>
			
			<form action="<?=e($_form_action)?>" method="POST"><?=csrf_field()?>
				<input type="hidden" name="subject" value="<?=e($_request['subject'])?>">
				<input type="hidden" name="name" value="<?=e($_request['name'])?>">
				<input type="hidden" name="message" value="<?=e($_request['message'])?>">
				<input type="hidden" name="back" value="1">
				<input style="float: right; margin-top: -20px;" type='submit' name='action' value='戻る'>
			</form>
			
		<?php } else { ?>
			
			<form action="<?=e($_form_action)?>" method="POST"><?=csrf_field()?>
				<h3>入力フォーム</h3>
				<table class="tablebox">
					<colgroup>
						<col width="200">
						<col width="">
					</colgroup>
					<tbody>
						<tr>
							<th>件名</th>
							<td><input type="text" name="subject" value="<?=e($_request['subject'])?>"></td>
						</tr>
						<tr>
							<th>お名前</th>
							<td><input type="text" name="name" value="<?=e($_request['name'])?>"></td>
						</tr>
						<tr>
							<th>本文</th>
							<td><textarea name="message" style="height: 300px"><?=e($_request['message'])?></textarea></td>
						</tr>
					</tbody>
				</table>
				
				<input type="hidden" name="confirm" value="1">
				<br>
				<input type='submit' name='action' value='確認'>
			</form>
			
		<?php } ?>
	</div>
</div>
