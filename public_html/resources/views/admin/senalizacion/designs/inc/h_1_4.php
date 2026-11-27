
<!-- 1の場合 -->
<tr>
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == 1 ) { ?>
				<input type="radio" name="flame_cd" value="1" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="1">
			<?php } ?>
				パターン1 (4分割)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="100%">
			</colgroup>
			<tr>
				<td align="center">A 画面</td>
			</tr>
			<tr>
				<td align="center">B 画面</td>
			</tr>
			<tr>
				<td align="center">C 画面</td>
			</tr>
			<tr>
				<td align="center">D 画面</td>
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
				パターン2 (3分割①)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="100%">
			</colgroup>
			<tr>
				<td align="center" style="height: 50px;">A 画面</td>
			</tr>
			<tr>
				<td align="center">B 画面</td>
			</tr>
			<tr>
				<td align="center">C 画面</td>
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
				パターン3 (3分割②)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="100%">
			</colgroup>
			<tr>
				<td align="center">A 画面</td>
			</tr>
			<tr>
				<td align="center" style="height: 50px;">B 画面</td>
			</tr>
			<tr>
				<td align="center">C 画面</td>
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
				パターン4 (3分割③)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="100%">
			</colgroup>
			<tr>
				<td align="center">A 画面</td>
			</tr>
			<tr>
				<td align="center">B 画面</td>
			</tr>
			<tr>
				<td align="center" style="height: 50px;">C 画面</td>
			</tr>
		</table>
	</td>
</tr>

<tr>
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == 5 ) { ?>
				<input type="radio" name="flame_cd" value="5" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="5">
			<?php } ?>
				パターン5 (2分割①)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="100%">
			</colgroup>
			<tr>
				<td align="center" style="height: 50px;">A 画面</td>
			</tr>
			<tr>
				<td align="center" style="height: 50px;">B 画面</td>
			</tr>
		</table>
	</td>
</tr>

<tr>
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == 6 ) { ?>
				<input type="radio" name="flame_cd" value="6" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="6">
			<?php } ?>
				パターン6 (2分割②)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="100%">
			</colgroup>
			<tr>
				<td align="center" style="height: 75px;">A 画面</td>
			</tr>
			<tr>
				<td align="center">B 画面</td>
			</tr>
		</table>
	</td>
</tr>

<tr>
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == 7 ) { ?>
				<input type="radio" name="flame_cd" value="7" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="7">
			<?php } ?>
				パターン7 (2分割③)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="100%">
			</colgroup>
			<tr>
				<td align="center">A 画面</td>
			</tr>
			<tr>
				<td align="center" style="height: 75px;">B 画面</td>
			</tr>
		</table>
	</td>
</tr>

<tr>
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == 8 ) { ?>
				<input type="radio" name="flame_cd" value="8" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="8">
			<?php } ?>
				パターン8 (分割なし)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="100%">
			</colgroup>
			<tr>
				<td align="center" style="height: 100px;">A 画面</td>
			</tr>
		</table>
	</td>
</tr>

