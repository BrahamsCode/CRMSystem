
<!-- 1の場合 -->
<tr>
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == 1 ) { ?>
				<input type="radio" name="flame_cd" value="1" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="1">
			<?php } ?>
				パターン1 (3分割)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td align="center">A 画面</td>
				<td align="center">B 画面</td>
				<td align="center">C 画面</td>
			</tr>
		</table>
	</td>
</tr>

<tr>
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == 2 ) { ?>
				<input type="radio" name="flame_cd" value="2" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="2">
			<?php } ?>
				パターン2 (2分割①)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="50%">
			</colgroup>
			<tr>
				<td align="center">A 画面</td>
				<td align="center">B 画面</td>
			</tr>
		</table>
	</td>
</tr>

<tr>
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == 3 ) { ?>
				<input type="radio" name="flame_cd" value="3" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="3">
			<?php } ?>
				パターン3 (2分割②)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="50%">
				<col width="25%">
			</colgroup>
			<tr>
				<td align="center">A 画面</td>
				<td align="center">B 画面</td>
			</tr>
		</table>
	</td>
</tr>

<tr>
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == 4 ) { ?>
				<input type="radio" name="flame_cd" value="4" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="4">
			<?php } ?>
				パターン4 (分割なし)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="50%">
			</colgroup>
			<tr>
				<td align="center">A 画面</td>
			</tr>
		</table>
	</td>
</tr>

