
<?php if ( ($_detail_data['flame_cd'] ?? null) == 1 ) { ?>
<style>
.view_img img{
    height: 50px;
    width: auto;
}
</style>
<?php } else if ( ($_detail_data['flame_cd'] ?? null) >= 2 && ($_detail_data['flame_cd'] ?? null) <= 11 ) { ?>
<style>
.view_img img{
    height: 50px;
    width: auto;
}
</style>
<?php } else if ( ($_detail_data['flame_cd'] ?? null) >= 12 && ($_detail_data['flame_cd'] ?? null) <= 42 ) { ?>
<style>
.view_img img{
    height: 50px;
    width: auto;
}
</style>
<?php } else if ( ($_detail_data['flame_cd'] ?? null) >= 43 && ($_detail_data['flame_cd'] ?? null) <= 69 ) { ?>
<style>
.view_img img{
    height: 50px;
    width: auto;
}
</style>
<?php } else if ( ($_detail_data['flame_cd'] ?? null) >= 70 && ($_detail_data['flame_cd'] ?? null) <= 97 ) { ?>
<style>
.view_img img{
    height: 50px;
    width: auto;
}
</style>
<?php } else if ( ($_detail_data['flame_cd'] ?? null) >= 98 && ($_detail_data['flame_cd'] ?? null) <= 112 ) { ?>
<style>
.view_img img{
    height: 50px;
    width: auto;
}
</style>
<?php } else if ( ($_detail_data['flame_cd'] ?? null) >= 113 && ($_detail_data['flame_cd'] ?? null) <= 116 ) { ?>
<style>
.view_img img{
    height: 60px;
    width: auto;
}
</style>
<?php } else { ?>
<style>
.view_img img{
    height: 60px;
    width: auto;
}
</style>
<?php } ?>

<?php $i = 1; ?>

<tr class="split_8">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (8分割)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td align="center">A 画面</td>
				<td align="center">B 画面</td>
				<td align="center">C 画面</td>
				<td align="center">D 画面</td>
			</tr>
			<tr>
				<td align="center">E 画面</td>
				<td align="center">F 画面</td>
				<td align="center">G 画面</td>
				<td align="center">H 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_7">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (7分割①)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td colspan="2" align="center">A 画面</td>
				<td align="center">B 画面</td>
				<td align="center">C 画面</td>
			</tr>
			<tr>
				<td align="center">D 画面</td>
				<td align="center">E 画面</td>
				<td align="center">F 画面</td>
				<td align="center">G 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_7">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (7分割②)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td align="center">A 画面</td>
				<td colspan="2"  align="center">B 画面</td>
				<td align="center">C 画面</td>
			</tr>
			<tr>
				<td align="center">D 画面</td>
				<td align="center">E 画面</td>
				<td align="center">F 画面</td>
				<td align="center">G 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_7">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (7分割③)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td align="center">A 画面</td>
				<td align="center">B 画面</td>
				<td colspan="2" align="center">C 画面</td>
			</tr>
			<tr>
				<td align="center">D 画面</td>
				<td align="center">E 画面</td>
				<td align="center">F 画面</td>
				<td align="center">G 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_7">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (7分割④)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td align="center">A 画面</td>
				<td align="center">B 画面</td>
				<td align="center">C 画面</td>
				<td align="center">D 画面</td>
			</tr>
			<tr>
				<td colspan="2" align="center">E 画面</td>
				<td align="center">F 画面</td>
				<td align="center">G 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_7">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (7分割⑤)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td align="center">A 画面</td>
				<td align="center">B 画面</td>
				<td align="center">C 画面</td>
				<td align="center">D 画面</td>
			</tr>
			<tr>
				<td align="center">E 画面</td>
				<td colspan="2" align="center">F 画面</td>
				<td align="center">G 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_7">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (7分割⑥)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td align="center">A 画面</td>
				<td align="center">B 画面</td>
				<td align="center">C 画面</td>
				<td align="center">D 画面</td>
			</tr>
			<tr>
				<td align="center">E 画面</td>
				<td align="center">F 画面</td>
				<td colspan="2" align="center">G 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_7">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (7分割⑦)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="2" align="center">A 画面</td>
				<td align="center">B 画面</td>
				<td align="center">C 画面</td>
				<td align="center">D 画面</td>
			</tr>
			<tr>
				<td align="center">E 画面</td>
				<td align="center">F 画面</td>
				<td align="center">G 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_7">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (7分割⑧)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td align="center">A 画面</td>
				<td rowspan="2" align="center">B 画面</td>
				<td align="center">C 画面</td>
				<td align="center">D 画面</td>
			</tr>
			<tr>
				<td align="center">E 画面</td>
				<td align="center">F 画面</td>
				<td align="center">G 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_7">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (7分割⑨)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td align="center">A 画面</td>
				<td align="center">B 画面</td>
				<td rowspan="2" align="center">C 画面</td>
				<td align="center">D 画面</td>
			</tr>
			<tr>
				<td align="center">E 画面</td>
				<td align="center">F 画面</td>
				<td align="center">G 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_7">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (7分割⑩)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td align="center">A 画面</td>
				<td align="center">B 画面</td>
				<td align="center">C 画面</td>
				<td rowspan="2" align="center">D 画面</td>
			</tr>
			<tr>
				<td align="center">E 画面</td>
				<td align="center">F 画面</td>
				<td align="center">G 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_6">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (6分割①)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td colspan="3" align="center">A 画面</td>
				<td align="center">B 画面</td>
			</tr>
			<tr>
				<td align="center">C 画面</td>
				<td align="center">D 画面</td>
				<td align="center">E 画面</td>
				<td align="center">F 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_6">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (6分割②)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td align="center">A 画面</td>
				<td colspan="3" align="center">B 画面</td>
			</tr>
			<tr>
				<td align="center">C 画面</td>
				<td align="center">D 画面</td>
				<td align="center">E 画面</td>
				<td align="center">F 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_6">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (6分割③)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td align="center">A 画面</td>
				<td align="center">B 画面</td>
				<td align="center">C 画面</td>
				<td align="center">D 画面</td>
			</tr>
			<tr>
				<td colspan="3" align="center">E 画面</td>
				<td align="center">F 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_6">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (6分割④)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td align="center">A 画面</td>
				<td align="center">B 画面</td>
				<td align="center">C 画面</td>
				<td align="center">D 画面</td>
			</tr>
			<tr>
				<td align="center">E 画面</td>
				<td colspan="3" align="center">F 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_6">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (6分割⑤)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td colspan="2" align="center">A 画面</td>
				<td colspan="2" align="center">B 画面</td>
			</tr>
			<tr>
				<td align="center">C 画面</td>
				<td align="center">D 画面</td>
				<td align="center">E 画面</td>
				<td align="center">F 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_6">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (6分割⑥)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td colspan="2" align="center">A 画面</td>
				<td align="center">B 画面</td>
				<td align="center">C 画面</td>
			</tr>
			<tr>
				<td colspan="2" align="center">D 画面</td>
				<td align="center">E 画面</td>
				<td align="center">F 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_6">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (6分割⑦)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td colspan="2" align="center">A 画面</td>
				<td align="center">B 画面</td>
				<td align="center">C 画面</td>
			</tr>
			<tr>
				<td align="center">D 画面</td>
				<td colspan="2" align="center">E 画面</td>
				<td align="center">F 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_6">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (6分割⑧)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td colspan="2" align="center">A 画面</td>
				<td align="center">B 画面</td>
				<td align="center">C 画面</td>
			</tr>
			<tr>
				<td align="center">D 画面</td>
				<td align="center">E 画面</td>
				<td colspan="2" align="center">F 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_6">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (6分割⑨)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td align="center">A 画面</td>
				<td colspan="2" align="center">B 画面</td>
				<td align="center">C 画面</td>
			</tr>
			<tr>
				<td colspan="2" align="center">D 画面</td>
				<td align="center">E 画面</td>
				<td align="center">F 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_6">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (6分割⑩)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td align="center">A 画面</td>
				<td colspan="2" align="center">B 画面</td>
				<td align="center">C 画面</td>
			</tr>
			<tr>
				<td align="center">D 画面</td>
				<td colspan="2" align="center">E 画面</td>
				<td align="center">F 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_6">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (6分割⑪)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td align="center">A 画面</td>
				<td colspan="2" align="center">B 画面</td>
				<td align="center">C 画面</td>
			</tr>
			<tr>
				<td align="center">D 画面</td>
				<td align="center">E 画面</td>
				<td colspan="2" align="center">F 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_6">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (6分割⑫)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td align="center">A 画面</td>
				<td align="center">B 画面</td>
				<td colspan="2" align="center">C 画面</td>
			</tr>
			<tr>
				<td colspan="2" align="center">D 画面</td>
				<td align="center">E 画面</td>
				<td align="center">F 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_6">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (6分割⑬)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td align="center">A 画面</td>
				<td align="center">B 画面</td>
				<td colspan="2" align="center">C 画面</td>
			</tr>
			<tr>
				<td align="center">D 画面</td>
				<td colspan="2" align="center">E 画面</td>
				<td align="center">F 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_6">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (6分割⑭)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td align="center">A 画面</td>
				<td align="center">B 画面</td>
				<td colspan="2" align="center">C 画面</td>
			</tr>
			<tr>
				<td align="center">D 画面</td>
				<td align="center">E 画面</td>
				<td colspan="2" align="center">F 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_6">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (6分割⑮)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="2" align="center">A 画面</td>
				<td colspan="2" align="center">B 画面</td>
				<td align="center">C 画面</td>
			</tr>
			<tr>
				<td align="center">D 画面</td>
				<td align="center">E 画面</td>
				<td align="center">F 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_6">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (6分割⑯)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="2" align="center">A 画面</td>
				<td align="center">B 画面</td>
				<td colspan="2" align="center">C 画面</td>
			</tr>
			<tr>
				<td align="center">D 画面</td>
				<td align="center">E 画面</td>
				<td align="center">F 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_6">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (6分割⑰)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="2" align="center">A 画面</td>
				<td align="center">B 画面</td>
				<td align="center">C 画面</td>
				<td align="center">D 画面</td>
			</tr>
			<tr>
				<td colspan="2" align="center">E 画面</td>
				<td align="center">F 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_6">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (6分割⑱)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="2" align="center">A 画面</td>
				<td align="center">B 画面</td>
				<td align="center">C 画面</td>
				<td align="center">D 画面</td>
			</tr>
			<tr>
				<td align="center">E 画面</td>
				<td colspan="2" align="center">F 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_6">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (6分割⑲)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="2" align="center">A 画面</td>
				<td rowspan="2" align="center">B 画面</td>
				<td align="center">C 画面</td>
				<td align="center">D 画面</td>
			</tr>
			<tr>
				<td align="center">E 画面</td>
				<td align="center">F 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_6">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (6分割⑳)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="2" align="center">A 画面</td>
				<td align="center">B 画面</td>
				<td rowspan="2" align="center">C 画面</td>
				<td align="center">D 画面</td>
			</tr>
			<tr>
				<td align="center">E 画面</td>
				<td align="center">F 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_6">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (6分割㉑)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="2" align="center">A 画面</td>
				<td align="center">B 画面</td>
				<td align="center">C 画面</td>
				<td rowspan="2" align="center">D 画面</td>
			</tr>
			<tr>
				<td align="center">E 画面</td>
				<td align="center">F 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_6">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (6分割㉒)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td align="center">A 画面</td>
				<td rowspan="2" align="center">B 画面</td>
				<td colspan="2" align="center">C 画面</td>
			</tr>
			<tr>
				<td align="center">D 画面</td>
				<td align="center">E 画面</td>
				<td align="center">F 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_6">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (6分割㉓)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td align="center">A 画面</td>
				<td rowspan="2" align="center">B 画面</td>
				<td align="center">C 画面</td>
				<td align="center">D 画面</td>
			</tr>
			<tr>
				<td align="center">E 画面</td>
				<td colspan="2" align="center">F 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_6">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (6分割㉔)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td align="center">A 画面</td>
				<td rowspan="2" align="center">B 画面</td>
				<td rowspan="2" align="center">C 画面</td>
				<td align="center">D 画面</td>
			</tr>
			<tr>
				<td align="center">E 画面</td>
				<td align="center">F 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_6">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (6分割㉕)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td align="center">A 画面</td>
				<td rowspan="2" align="center">B 画面</td>
				<td align="center">C 画面</td>
				<td rowspan="2" align="center">D 画面</td>
			</tr>
			<tr>
				<td align="center">E 画面</td>
				<td align="center">F 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_6">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (6分割㉖)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td colspan="2" align="center">A 画面</td>
				<td rowspan="2" align="center">B 画面</td>
				<td align="center">C 画面</td>
			</tr>
			<tr>
				<td align="center">D 画面</td>
				<td align="center">E 画面</td>
				<td align="center">F 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_6">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (6分割㉗)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td align="center">A 画面</td>
				<td align="center">B 画面</td>
				<td rowspan="2" align="center">C 画面</td>
				<td align="center">D 画面</td>
			</tr>
			<tr>
				<td colspan="2" align="center">E 画面</td>
				<td align="center">F 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_6">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (6分割㉘)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td colspan="2" align="center">A 画面</td>
				<td align="center">B 画面</td>
				<td rowspan="2" align="center">C 画面</td>
			</tr>
			<tr>
				<td align="center">D 画面</td>
				<td align="center">E 画面</td>
				<td align="center">F 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_6">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (6分割㉙)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td align="center">A 画面</td>
				<td colspan="2" align="center">B 画面</td>
				<td rowspan="2" align="center">C 画面</td>
			</tr>
			<tr>
				<td align="center">D 画面</td>
				<td align="center">E 画面</td>
				<td align="center">F 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_6">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (6分割㉚)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td align="center">A 画面</td>
				<td align="center">B 画面</td>
				<td align="center">C 画面</td>
				<td rowspan="2" align="center">D 画面</td>
			</tr>
			<tr>
				<td colspan="2" align="center">E 画面</td>
				<td align="center">F 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_6">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (6分割㉛)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td align="center">A 画面</td>
				<td align="center">B 画面</td>
				<td align="center">C 画面</td>
				<td rowspan="2" align="center">D 画面</td>
			</tr>
			<tr>
				<td align="center">E 画面</td>
				<td colspan="2" align="center">F 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_5">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (5分割①)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td colspan="4" align="center">A 画面</td>
			</tr>
			<tr>
				<td align="center">B 画面</td>
				<td align="center">C 画面</td>
				<td align="center">D 画面</td>
				<td align="center">E 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_5">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (5分割②)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td align="center">A 画面</td>
				<td align="center">B 画面</td>
				<td align="center">C 画面</td>
				<td align="center">D 画面</td>
			</tr>
			<tr>
				<td colspan="4" align="center">E 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_5">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (5分割③)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="50%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="2" align="center">A 画面</td>
				<td align="center">B 画面</td>
				<td align="center">C 画面</td>
			</tr>
			<tr>
				<td align="center">D 画面</td>
				<td align="center">E 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_5">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (5分割④)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="50%">
				<col width="25%">
			</colgroup>
			<tr>
				<td align="center">A 画面</td>
				<td rowspan="2" align="center">B 画面</td>
				<td align="center">C 画面</td>
			</tr>
			<tr>
				<td align="center">D 画面</td>
				<td align="center">E 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_5">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (5分割⑤)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="50%">
			</colgroup>
			<tr>
				<td align="center">A 画面</td>
				<td align="center">B 画面</td>
				<td rowspan="2" align="center">C 画面</td>
			</tr>
			<tr>
				<td align="center">D 画面</td>
				<td align="center">E 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_5">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (5分割⑥)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td colspan="3" align="center">A 画面</td>
				<td align="center">B 画面</td>
			</tr>
			<tr>
				<td colspan="2" align="center">C 画面</td>
				<td align="center">D 画面</td>
				<td align="center">E 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_5">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (5分割⑦)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td colspan="3" align="center">A 画面</td>
				<td align="center">B 画面</td>
			</tr>
			<tr>
				<td align="center">C 画面</td>
				<td colspan="2" align="center">D 画面</td>
				<td align="center">E 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_5">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (5分割⑧)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td colspan="3" align="center">A 画面</td>
				<td align="center">B 画面</td>
			</tr>
			<tr>
				<td align="center">C 画面</td>
				<td align="center">D 画面</td>
				<td colspan="2" align="center">E 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_5">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (5分割⑨)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td colspan="3" align="center">A 画面</td>
				<td rowspan="2" align="center">B 画面</td>
			</tr>
			<tr>
				<td align="center">C 画面</td>
				<td align="center">D 画面</td>
				<td align="center">E 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_5">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (5分割⑩)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td align="center">A 画面</td>
				<td align="center">B 画面</td>
				<td align="center">C 画面</td>
				<td rowspan="2" align="center">D 画面</td>
			</tr>
			<tr>
				<td colspan="3" align="center">E 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_5">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (5分割⑪)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="2" align="center">A 画面</td>
				<td colspan="3" align="center">B 画面</td>
			</tr>
			<tr>
				<td align="center">C 画面</td>
				<td align="center">D 画面</td>
				<td align="center">E 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_5">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (5分割⑫)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="2" align="center">A 画面</td>
				<td align="center">B 画面</td>
				<td align="center">C 画面</td>
				<td align="center">D 画面</td>
			</tr>
			<tr>
				<td colspan="3" align="center">E 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_5">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (5分割⑬)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td colspan="2" align="center">A 画面</td>
				<td colspan="2" align="center">B 画面</td>
			</tr>
			<tr>
				<td colspan="2" align="center">C 画面</td>
				<td align="center">D 画面</td>
				<td align="center">E 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_5">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (5分割⑭)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td colspan="2" align="center">A 画面</td>
				<td colspan="2" align="center">B 画面</td>
			</tr>
			<tr>
				<td align="center">C 画面</td>
				<td colspan="2" align="center">D 画面</td>
				<td align="center">E 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_5">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (5分割⑮)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td colspan="2" align="center">A 画面</td>
				<td colspan="2" align="center">B 画面</td>
			</tr>
			<tr>
				<td align="center">C 画面</td>
				<td align="center">D 画面</td>
				<td colspan="2" align="center">E 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_5">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (5分割⑯)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td colspan="2" align="center">A 画面</td>
				<td align="center">B 画面</td>
				<td align="center">C 画面</td>
			</tr>
			<tr>
				<td colspan="2" align="center">D 画面</td>
				<td colspan="2" align="center">E 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_5">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (5分割⑰)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td colspan="2" align="center">A 画面</td>
				<td rowspan="2" align="center">B 画面</td>
				<td align="center">C 画面</td>
			</tr>
			<tr>
				<td colspan="2" align="center">D 画面</td>
				<td align="center">E 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_5">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (5分割⑱)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td colspan="2" align="center">A 画面</td>
				<td  align="center">B 画面</td>
				<td rowspan="2"align="center">C 画面</td>
			</tr>
			<tr>
				<td colspan="2" align="center">D 画面</td>
				<td align="center">E 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_5">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (5分割㉑)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td  align="center">A 画面</td>
				<td colspan="2" align="center">B 画面</td>
				<td rowspan="2" align="center">C 画面</td>
			</tr>
			<tr>
				<td align="center">D 画面</td>
				<td colspan="2" align="center">E 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_5">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (5分割㉒)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td  align="center">A 画面</td>
				<td colspan="2" align="center">B 画面</td>
				<td rowspan="2" align="center">C 画面</td>
			</tr>
			<tr>
				<td colspan="2" align="center">D 画面</td>
				<td align="center">E 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_5">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (5分割㉓)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td colspan="2"  align="center">A 画面</td>
				<td align="center">B 画面</td>
				<td rowspan="2" align="center">C 画面</td>
			</tr>
			<tr>
				<td align="center">D 画面</td>
				<td colspan="2" align="center">E 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_5">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (5分割㉔)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td align="center">A 画面</td>
				<td rowspan="2"align="center">B 画面</td>
				<td colspan="2" align="center">C 画面</td>
			</tr>
			<tr>
				<td align="center">D 画面</td>
				<td colspan="2" align="center">E 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_5">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (5分割㉕)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="2" align="center">A 画面</td>
				<td align="center">B 画面</td>
				<td colspan="2" align="center">C 画面</td>
			</tr>
			<tr>
				<td align="center">D 画面</td>
				<td colspan="2" align="center">E 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_5">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (5分割㉖)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="2" align="center">A 画面</td>
				<td colspan="2" align="center">B 画面</td>
				<td align="center">C 画面</td>
			</tr>
			<tr>
				<td align="center">D 画面</td>
				<td colspan="2" align="center">E 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_5">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (5分割㉗)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="2" align="center">A 画面</td>
				<td colspan="2" align="center">B 画面</td>
				<td align="center">C 画面</td>
			</tr>
			<tr>
				<td colspan="2" align="center">D 画面</td>
				<td align="center">E 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_5">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (5分割㉘)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="2" align="center">A 画面</td>
				<td align="center">B 画面</td>
				<td colspan="2" align="center">C 画面</td>
			</tr>
			<tr>
				<td colspan="2" align="center">D 画面</td>
				<td align="center">E 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_5">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (5分割㉘)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="2" align="center">A 画面</td>
				<td align="center">B 画面</td>
				<td colspan="2" align="center">C 画面</td>
			</tr>
			<tr>
				<td colspan="2" align="center">D 画面</td>
				<td align="center">E 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_4">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (4分割①)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td colspan="2" align="center">A 画面</td>
				<td colspan="2" align="center">B 画面</td>
			</tr>
			<tr>
				<td colspan="2" align="center">C 画面</td>
				<td colspan="2" align="center">D 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_4">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (4分割②)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td colspan="1" align="center">A 画面</td>
				<td colspan="3" align="center">B 画面</td>
			</tr>
			<tr>
				<td colspan="2" align="center">C 画面</td>
				<td colspan="2" align="center">D 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_4">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (4分割③)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td colspan="3" align="center">A 画面</td>
				<td colspan="1" align="center">B 画面</td>
			</tr>
			<tr>
				<td colspan="2" align="center">C 画面</td>
				<td colspan="2" align="center">D 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_4">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (4分割④)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td colspan="2" align="center">A 画面</td>
				<td colspan="2" align="center">B 画面</td>
			</tr>
			<tr>
				<td colspan="1" align="center">C 画面</td>
				<td colspan="3" align="center">D 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_4">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (4分割⑤)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td colspan="2" align="center">A 画面</td>
				<td colspan="2" align="center">B 画面</td>
			</tr>
			<tr>
				<td colspan="3" align="center">C 画面</td>
				<td colspan="1" align="center">D 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_4">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (4分割⑥)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td colspan="1" align="center">A 画面</td>
				<td colspan="3" align="center">B 画面</td>
			</tr>
			<tr>
				<td colspan="1" align="center">C 画面</td>
				<td colspan="3" align="center">D 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_4">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (4分割⑦)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td colspan="3" align="center">A 画面</td>
				<td colspan="1" align="center">B 画面</td>
			</tr>
			<tr>
				<td colspan="3" align="center">C 画面</td>
				<td colspan="1" align="center">D 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_4">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (4分割⑧)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="2" align="center">A 画面</td>
				<td colspan="3" align="center">B 画面</td>
			</tr>
			<tr>
				<td colspan="2" align="center">C 画面</td>
				<td colspan="1" align="center">D 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_4">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (4分割⑨)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="2" align="center">A 画面</td>
				<td colspan="3" align="center">B 画面</td>
			</tr>
			<tr>
				<td colspan="1" align="center">C 画面</td>
				<td colspan="2" align="center">D 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_4">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (4分割⑩)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="2" align="center">A 画面</td>
				<td colspan="2" align="center">B 画面</td>
				<td colspan="1" align="center">C 画面</td>
			</tr>
			<tr>
				<td colspan="3" align="center">D 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_4">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (4分割⑪)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="2" align="center">A 画面</td>
				<td colspan="1" align="center">B 画面</td>
				<td colspan="2" align="center">C 画面</td>
			</tr>
			<tr>
				<td colspan="3" align="center">D 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_4">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (4分割⑫)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="2" colspan="2" align="center">A 画面</td>
				<td colspan="1" align="center">B 画面</td>
				<td align="center">C 画面</td>
			</tr>
			<tr>
				<td colspan="2" align="center">D 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_4">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (4分割⑬)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="2" colspan="2" align="center">A 画面</td>
				<td colspan="2" align="center">B 画面</td>
			</tr>
			<tr>
				<td align="center">C 画面</td>
				<td colspan="1" align="center">D 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_4">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (4分割⑭)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="2" colspan="1" align="center">A 画面</td>
				<td colspan="2" align="center">B 画面</td>
				<td rowspan="2" colspan="1" align="center">C 画面</td>
			</tr>
			<tr>
				<td colspan="2" align="center">D 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_4">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (4分割⑮)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="2" colspan="1" align="center">A 画面</td>
				<td rowspan="2" colspan="2" align="center">B 画面</td>
				<td colspan="1" align="center">C 画面</td>
			</tr>
			<tr>
				<td colspan="2" align="center">D 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_4">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (4分割⑯)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="2" colspan="2" align="center">A 画面</td>
				<td rowspan="2" colspan="1" align="center">B 画面</td>
				<td colspan="1" align="center">C 画面</td>
			</tr>
			<tr>
				<td colspan="2" align="center">D 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_4">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (4分割⑰)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="2" colspan="2" align="center">A 画面</td>
				<td colspan="1" align="center">B 画面</td>
				<td rowspan="2" colspan="1" align="center">C 画面</td>
			</tr>
			<tr>
				<td colspan="1" align="center">D 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_4">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (4分割⑰)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="1" colspan="1" align="center">A 画面</td>
				<td rowspan="2" colspan="1" align="center">B 画面</td>
				<td rowspan="2" colspan="2" align="center">C 画面</td>
			</tr>
			<tr>
				<td colspan="1" align="center">D 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_4">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (4分割⑱)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="1" colspan="1" align="center">A 画面</td>
				<td rowspan="2" colspan="2" align="center">B 画面</td>
				<td rowspan="2" colspan="1" align="center">C 画面</td>
			</tr>
			<tr>
				<td colspan="1" align="center">D 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_4">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (4分割⑲)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="1" colspan="2" align="center">A 画面</td>
				<td rowspan="2" colspan="1" align="center">B 画面</td>
				<td rowspan="2" colspan="1" align="center">C 画面</td>
			</tr>
			<tr>
				<td colspan="2" align="center">D 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_4">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (4分割⑳)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="1" colspan="4" align="center">A 画面</td>
			</tr>
			<tr>
				<td rowspan="1" colspan="1" align="center">B 画面</td>
				<td rowspan="1" colspan="1" align="center">C 画面</td>
				<td rowspan="1" colspan="2" align="center">D 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_4">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (4分割㉑)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="1" colspan="4" align="center">A 画面</td>
			</tr>
			<tr>
				<td rowspan="1" colspan="1" align="center">B 画面</td>
				<td rowspan="1" colspan="2" align="center">C 画面</td>
				<td rowspan="1" colspan="1" align="center">D 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_4">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (4分割㉒)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="1" colspan="4" align="center">A 画面</td>
			</tr>
			<tr>
				<td rowspan="1" colspan="2" align="center">B 画面</td>
				<td rowspan="1" colspan="1" align="center">C 画面</td>
				<td rowspan="1" colspan="1" align="center">D 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_4">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (4分割㉓)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="1" colspan="1" align="center">A 画面</td>
				<td rowspan="1" colspan="1" align="center">B 画面</td>
				<td rowspan="1" colspan="2" align="center">C 画面</td>
			</tr>
			<tr>
				<td rowspan="1" colspan="4" align="center">D 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_4">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (4分割㉔)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="1" colspan="1" align="center">A 画面</td>
				<td rowspan="1" colspan="2" align="center">B 画面</td>
				<td rowspan="1" colspan="1" align="center">C 画面</td>
			</tr>
			<tr>
				<td rowspan="1" colspan="4" align="center">D 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_4">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (4分割㉕)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="1" colspan="2" align="center">A 画面</td>
				<td rowspan="1" colspan="1" align="center">B 画面</td>
				<td rowspan="1" colspan="1" align="center">C 画面</td>
			</tr>
			<tr>
				<td rowspan="1" colspan="4" align="center">D 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_4">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (4分割㉖)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="2" colspan="1" height="51" align="center">A 画面</td>
				<td rowspan="2" colspan="1" align="center">B 画面</td>
				<td rowspan="2" colspan="1" align="center">C 画面</td>
				<td rowspan="2" colspan="1" align="center">D 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_4">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (4分割㉗)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="2" colspan="1" align="center">A 画面</td>
				<td rowspan="2" colspan="1" align="center">B 画面</td>
				<td colspan="2" align="center">C 画面</td>
			</tr>
			<tr>
				<td colspan="2" align="center">D 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_3">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (3分割①)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="1" colspan="2" align="center">A 画面</td>
				<td rowspan="1" colspan="2" align="center">B 画面</td>
			</tr>
			<tr>
				<td rowspan="1" colspan="4" align="center">C 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_3">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (3分割②)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="1" colspan="3" align="center">A 画面</td>
				<td rowspan="1" colspan="1" align="center">B 画面</td>
			</tr>
			<tr>
				<td rowspan="1" colspan="4" align="center">C 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_3">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (3分割③)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="1" colspan="1" align="center">A 画面</td>
				<td rowspan="1" colspan="3" align="center">B 画面</td>
			</tr>
			<tr>
				<td rowspan="1" colspan="4" align="center">C 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_3">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (3分割④)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="1" colspan="4" align="center">A 画面</td>
			</tr>
			<tr>
				<td rowspan="1" colspan="3" align="center">B 画面</td>
				<td rowspan="1" colspan="1" align="center">C 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_3">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (3分割⑤)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="1" colspan="4" align="center">A 画面</td>
			</tr>
			<tr>
				<td rowspan="1" colspan="1" align="center">B 画面</td>
				<td rowspan="1" colspan="3" align="center">C 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_3">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (3分割⑥)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="1" colspan="4" align="center">A 画面</td>
			</tr>
			<tr>
				<td rowspan="1" colspan="2" align="center">B 画面</td>
				<td rowspan="1" colspan="2" align="center">C 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_3">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (3分割⑦)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="2" colspan="1" align="center">A 画面</td>
				<td rowspan="1" colspan="3" align="center">B 画面</td>
			</tr>
			<tr>
				<td rowspan="1" colspan="3" align="center">C 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_3">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (3分割⑧)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="2" colspan="2" align="center">A 画面</td>
				<td rowspan="1" colspan="2" align="center">B 画面</td>
			</tr>
			<tr>
				<td rowspan="1" colspan="2" align="center">C 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_3">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (3分割⑨)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="2" colspan="3" align="center">A 画面</td>
				<td rowspan="1" colspan="1" align="center">B 画面</td>
			</tr>
			<tr>
				<td rowspan="1" colspan="1" align="center">C 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_3">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (3分割⑩)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="1" colspan="3" align="center">A 画面</td>
				<td rowspan="2" colspan="1" align="center">B 画面</td>
			</tr>
			<tr>
				<td rowspan="1" colspan="3" align="center">C 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_3">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (3分割⑪)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="1" colspan="2" align="center">A 画面</td>
				<td rowspan="2" colspan="2" align="center">B 画面</td>
			</tr>
			<tr>
				<td rowspan="1" colspan="2" align="center">C 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_3">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (3分割⑫)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="1" colspan="1" align="center">A 画面</td>
				<td rowspan="2" colspan="3" align="center">B 画面</td>
			</tr>
			<tr>
				<td rowspan="1" colspan="1" align="center">C 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_3">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (3分割⑬)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="1" height="51" colspan="1" align="center">A 画面</td>
				<td rowspan="1" height="51" colspan="2" align="center">B 画面</td>
				<td rowspan="1" height="51" colspan="1" align="center">C 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_3">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (3分割⑭)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="1" height="51" colspan="2" align="center">A 画面</td>
				<td rowspan="1" height="51" colspan="1" align="center">B 画面</td>
				<td rowspan="1" height="51" colspan="1" align="center">C 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_3">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (3分割⑮)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="1" height="51" colspan="1" align="center">A 画面</td>
				<td rowspan="1" height="51" colspan="1" align="center">B 画面</td>
				<td rowspan="1" height="51" colspan="2" align="center">C 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_2">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (2分割①)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="1" colspan="4" align="center">A 画面</td>
			<tr>
			</tr>
				<td rowspan="1" colspan="4" align="center">B 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_2">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (2分割②)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="1" height="51" colspan="1" align="center">A 画面</td>
				<td rowspan="1" height="51" colspan="3" align="center">B 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_2">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (2分割③)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="1" height="51" colspan="2" align="center">A 画面</td>
				<td rowspan="1" height="51" colspan="2" align="center">B 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_2">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (2分割④)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="1" height="51" colspan="3" align="center">A 画面</td>
				<td rowspan="1" height="51" colspan="1" align="center">B 画面</td>
			</tr>
		</table>
	</td>
</tr>

<?php $i++; ?>

<tr class="split_1">
	<td>
		<label>
			<?php if ( ($_detail_data['flame_cd'] ?? null) == $i ) { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>" checked="checked">
			<?php } else { ?>
				<input type="radio" name="flame_cd" value="<?=$i?>">
			<?php } ?>
				パターン<?=$i?> (分割なし)
		</label>
	</td>
	<td>
		<table>
			<colgroup>
				<col width="25%">
				<col width="25%">
				<col width="25%">
				<col width="25%">
			</colgroup>
			<tr>
				<td rowspan="1" height="51" colspan="4" align="center">A 画面</td>
			</tr>
		</table>
	</td>
</tr>

