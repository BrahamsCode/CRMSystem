{{-- Fragmento AJAX de 権限管理: system/category0/config/ajax_group_authority.php --}}
<input type="button" value="全て選択する" onclick="$('.kojinArea input:checkbox').attr('checked', 'checked');" />
<input type="button" value="全て選択解除する" onclick="$('.kojinArea input:checkbox').removeAttr('checked', 'checked');" />
<table style="width: 100%; margin: 0 0 5px;">
<tbody>
<tr>
<td style="width:70%;">
@if ($personal->isNotEmpty())
<table class="tableBox kojinArea">
	<tbody>
		<tr>
			<th style="text-align:center;width:13em;">項目名</td>
			<th style="text-align:center;">閲覧</td>
			<th style="text-align:center;">新規</td>
			<th style="text-align:center;">修正</td>
		</tr>
@foreach ($personal as $content)
		<tr>
			<td>{{ $content->name }}</td>
			<td style="text-align:center;"><input type="checkbox" name="etsuran_flg_{{ $content->cd }}" {{ $flag($content->cd, 'etsuran_flg') ? 'checked' : '' }}/></td>
			<td style="text-align:center;"><input type="checkbox" name="shinki_flg_{{ $content->cd }}" {{ $flag($content->cd, 'shinki_flg') ? 'checked' : '' }}/></td>
			<td style="text-align:center;"><input type="checkbox" name="shusei_flg_{{ $content->cd }}" {{ $flag($content->cd, 'shusei_flg') ? 'checked' : '' }}/></td>
		</tr>
@endforeach
	</tbody>
</table>
@endif
@if ($functions->isNotEmpty())
<input type="button" value="全て選択する" onclick="$('.funcArea input:checkbox').attr('checked', 'checked');" />
<input type="button" value="全て選択解除する" onclick="$('.funcArea input:checkbox').removeAttr('checked', 'checked');" />
<table class="tableBox funcArea">
	<tbody>
		<tr>
			<th style="text-align:center;width:13em">機能名</td>
			<th style="text-align:center;">機能1</td>
			<th style="text-align:center;">機能2</td>
			<th style="text-align:center;">機能3</td>
			<th style="text-align:center;">機能4</td>
		</tr>
@foreach ($functions as $content)
		<tr>
			<td>{{ $content->name }}
			@if ($content->comment)
			<span class="tipTitle"><img src="/system/img/icon_help.gif" alt="help" /><span class="tipComment">{!! nl2br(e($content->comment)) !!}</span></span></td>
			@endif
			<td style="text-align:center;"><input type="checkbox" name="etsuran_flg_{{ $content->cd }}" {{ $flag($content->cd, 'etsuran_flg') ? 'checked' : '' }}/></td>
			<td style="text-align:center;"><input type="checkbox" name="shinki_flg_{{ $content->cd }}" {{ $flag($content->cd, 'shinki_flg') ? 'checked' : '' }}/></td>
			<td style="text-align:center;"><input type="checkbox" name="shusei_flg_{{ $content->cd }}" {{ $flag($content->cd, 'shusei_flg') ? 'checked' : '' }}/></td>
			<td style="text-align:center;"><input type="checkbox" name="sakujo_flg_{{ $content->cd }}" {{ $flag($content->cd, 'sakujo_flg') ? 'checked' : '' }}/></td>
		</tr>
@endforeach
	</tbody>
</table>
@endif
<p style="text-align:center;"><input name="send_authority" type="button" value="　登録する　" onclick="submitForm();"></p>
</td>
<td style="width:30%;vertical-align:top;">
<div style="margin-left:30px;padding:3px;border:1px solid #CCCCCC;">
@if ($staff->isNotEmpty())
<p style="font-weight:bold;text-align:center;margin:1px;">このグループに登録されているスタッフ</p>
@php $shopName = ''; @endphp
@foreach ($staff as $member)
@if ($shopName !== (string) $member->kaishamei)
<p style="background-color:#828E93;color:#FFFFFF;margin:1px;padding:2px;">{{ $member->kaishamei }}</p>
@php $shopName = (string) $member->kaishamei; @endphp
@endif
<p style="background-color:#EEEEEE;margin:1px;padding:2px;">{{ $member->name }}</p>
@endforeach
@else
<p style="color:red;font-weight:bold;text-align:center;margin:1px;">このグループに登録されているスタッフがありません。</p>
@endif
</div>
</td>
</tr>
</tbody>
</table>
