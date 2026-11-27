
<!-- 1の場合 -->
<tr>
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == 1 ) { ?>
				<input type="radio" name="flame_cd" value="1" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="1">
			<?php } ?>
				パターン1 (分割なし)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
			</colgroup>
			<tr>
				<td align="center">A 画面</td>
			</tr>
		</table>
	</td>
</tr>

